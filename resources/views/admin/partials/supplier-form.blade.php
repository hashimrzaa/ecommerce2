@php $sid = $supplier?->id ?? 'new'; @endphp
<form method="post" action="{{ $supplier ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store') }}" class="space-y-4" data-once>
    @csrf
    @if ($supplier) @method('put') @endif
    <x-field label="Supplier name" name="name" id="s-name-{{ $sid }}" required :value="$supplier?->name" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field label="Contact person" name="contact_person" id="s-contact-{{ $sid }}" :value="$supplier?->contact_person" />
        <x-field label="Phone / WhatsApp" name="phone" id="s-phone-{{ $sid }}" type="tel" :value="$supplier?->phone" />
        <x-field label="City" name="city" id="s-city-{{ $sid }}" :value="$supplier?->city" />
        <x-field label="Order lead time (days)" name="lead_days" id="s-lead-{{ $sid }}" type="number" min="0" required :value="$supplier?->lead_days ?? 2" />
    </div>
    <x-field label="Return / defect policy" name="return_policy" id="s-policy-{{ $sid }}" rows="2" :value="$supplier?->return_policy" />
    <x-field label="Notes" name="notes" id="s-notes-{{ $sid }}" rows="2" :value="$supplier?->notes" />
    <label class="flex min-h-9 items-center gap-3 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="size-4" @checked($supplier?->is_active ?? true)>Active supplier</label>
    <button class="btn btn-primary w-full">{{ $supplier ? 'Save supplier' : 'Add supplier' }}</button>
</form>
