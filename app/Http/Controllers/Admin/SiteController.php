<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Homepage layout and store-wide settings, both editable without touching code. */
class SiteController extends Controller
{
    /* ---------- Homepage sections ---------- */

    public function homepage()
    {
        return view('admin.homepage', [
            'sections' => HomeSection::orderBy('position')->get(),
            'sources' => HomeSection::sources(),
        ]);
    }

    public function storeSection(Request $request)
    {
        $data = $this->sectionData($request);
        HomeSection::create($data + ['kind' => 'rail', 'position' => (int) HomeSection::max('position') + 10]);

        return back()->with('toast', 'Product row added to the bottom of the homepage.');
    }

    public function updateSection(Request $request, HomeSection $section)
    {
        $section->update($this->sectionData($request, $section));

        return back()->with('toast', 'Section saved.');
    }

    /** Swap places with the neighbour above or below. */
    public function moveSection(Request $request, HomeSection $section)
    {
        $up = $request->input('direction') === 'up';
        $neighbour = HomeSection::where('position', $up ? '<' : '>', $section->position)
            ->orderBy('position', $up ? 'desc' : 'asc')->first();
        if ($neighbour) {
            [$a, $b] = [$section->position, $neighbour->position];
            $section->update(['position' => $b]);
            $neighbour->update(['position' => $a]);
        }

        return back();
    }

    public function destroySection(HomeSection $section)
    {
        // Built-in sections can be hidden but not removed, so the layout can always be restored.
        abort_unless($section->kind === 'rail', 422, 'Only product rows can be removed. Hide this section instead.');
        $section->delete();

        return back()->with('toast', 'Product row removed.');
    }

    private function sectionData(Request $request, ?HomeSection $section = null): array
    {
        $needsSource = ! $section || in_array($section->kind, ['rail', 'showcase'], true);
        $data = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:60'],
            'title' => [Rule::requiredIf(! $section || $section->kind === 'rail'), 'nullable', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:160'],
            'source' => [Rule::requiredIf($needsSource), 'nullable', Rule::in(array_keys(HomeSection::sources()))],
            'item_limit' => ['nullable', 'integer', 'between:1,12'],
        ]);
        if (! $needsSource) {
            unset($data['source']);
        }

        return ['item_limit' => (int) ($data['item_limit'] ?? ($section->item_limit ?? 5)), 'is_active' => $request->boolean('is_active', true)] + $data;
    }

    /* ---------- Store settings ---------- */

    public function settings()
    {
        return view('admin.settings', [
            'groups' => collect(Setting::FIELDS)->map(fn ($field, $key) => [
                'key' => $key, 'label' => $field[1], 'type' => $field[2], 'group' => $field[3], 'help' => $field[4],
                'value' => config($field[0]),
            ])->groupBy('group'),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $rules = [];
        foreach (Setting::FIELDS as $key => [, , $type]) {
            $rules[$key] = match ($type) {
                'number' => ['required', 'integer', 'min:0', 'max:1000000'],
                'email' => ['required', 'email', 'max:120'],
                'url' => ['nullable', 'url', 'max:255'],
                default => ['required', 'string', 'max:160'],
            };
        }
        $rules['whatsapp'] = ['required', 'regex:/^\d{10,15}$/'];
        $data = $request->validate($rules, ['whatsapp.regex' => 'Enter the WhatsApp number as digits only with the country code, e.g. 923001234567.']);

        Setting::saveMany($data);

        return back()->with('toast', 'Store settings saved.');
    }
}
