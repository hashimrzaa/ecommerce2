<form method="post" action="{{ route('admin.categories.update', $category) }}">
    @csrf @method('patch')
    <input type="hidden" name="is_visible" value="{{ $category->is_visible ? 0 : 1 }}">
    <button class="btn btn-sm {{ $category->is_visible ? 'btn-outline' : 'btn-dark' }}" aria-pressed="{{ $category->is_visible ? 'true' : 'false' }}">
        @if ($category->is_visible)<x-lucide-eye class="size-4 text-success" aria-hidden="true" />Visible @else<x-lucide-eye-off class="size-4" aria-hidden="true" />Hidden @endif
    </button>
</form>
