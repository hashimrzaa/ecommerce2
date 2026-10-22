@extends('layouts.admin')

@section('title', 'Reviews')

@section('content')
    <x-admin.tabs :options="['pending' => 'Awaiting approval', 'approved' => 'Approved', 'rejected' => 'Rejected']" :counts="$counts" all="All reviews" />

    <ul class="mt-4 space-y-3">
        @forelse ($reviews as $review)
            <li class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <x-rating :value="$review->rating" size="size-4" />
                        <span class="badge badge-{{ ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'neutral'][$review->status] }}">{{ ucfirst($review->status) }}</span>
                        <span class="text-[0.8125rem] text-muted">{{ $review->created_at->format('j M Y') }}</span>
                    </div>
                    <p class="mt-2 text-[0.9375rem] text-ink-2">{{ $review->body }}</p>
                    <p class="mt-2 text-[0.8125rem] text-muted"><span class="font-semibold text-ink">{{ $review->name }}</span>@if ($review->city) · {{ $review->city }}@endif · on <a href="{{ $review->product->url }}" target="_blank" rel="noopener" class="link">{{ $review->product->title }}</a></p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    @foreach (['approved' => ['Approve', 'btn-primary'], 'rejected' => ['Reject', 'btn-outline']] as $status => [$label, $class])
                        @continue($review->status === $status)
                        <form method="post" action="{{ route('admin.reviews.update', $review) }}">@csrf @method('patch')<input type="hidden" name="status" value="{{ $status }}"><button class="btn btn-sm {{ $class }}">{{ $label }}</button></form>
                    @endforeach
                    <form method="post" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Delete this review permanently?')">@csrf @method('delete')<button class="btn btn-ghost btn-sm text-danger" aria-label="Delete review"><x-lucide-trash-2 class="size-4" aria-hidden="true" /></button></form>
                </div>
            </li>
        @empty
            <li class="card p-12 text-center text-muted">{{ request('status') === 'pending' ? 'No reviews are waiting for approval.' : 'No reviews in this view.' }}</li>
        @endforelse
    </ul>
    <div class="mt-5">{{ $reviews->links() }}</div>
@endsection
