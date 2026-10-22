@extends('layouts.admin')

@section('title', 'Staff & permissions')

@section('actions')
    <button type="button" class="btn btn-primary btn-sm" x-data @click="$dispatch('open-modal', 'staff-new')"><x-lucide-plus class="size-4" aria-hidden="true" />Add staff</button>
@endsection

@php
    $roles = config('store.roles');
    $sections = config('store.sections');
    $editable = collect($roles)->except('owner');
@endphp

@section('content')
    <section class="card overflow-hidden">
        <h2 class="border-b border-line px-5 py-4 text-base font-bold">Team</h2>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th><span class="sr-only">Edit</span></th></tr></thead>
                <tbody x-data>
                    @foreach ($staff as $member)
                        <tr>
                            <td class="font-medium">{{ $member->name }} @if ($member->is(auth()->user()))<span class="badge badge-neutral ml-1">You</span>@endif</td>
                            <td class="text-muted">{{ $member->email }}</td>
                            <td><span class="badge {{ $member->role === 'owner' ? 'badge-primary' : 'badge-neutral' }}">{{ $member->role_label }}</span></td>
                            <td><span class="badge {{ $member->is_active ? 'badge-success' : 'badge-danger' }}">{{ $member->is_active ? 'Active' : 'Disabled' }}</span></td>
                            <td class="text-right"><button type="button" class="btn btn-ghost btn-sm" @click="$dispatch('open-modal', 'staff-{{ $member->id }}')">Edit</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="card mt-6 overflow-hidden">
        <div class="border-b border-line px-5 py-4">
            <h2 class="text-base font-bold">Role permissions</h2>
            <p class="mt-0.5 text-[0.8125rem] text-muted">Choose which admin sections each role can open. Owners always have full access.</p>
        </div>
        <form method="post" action="{{ route('admin.staff.permissions') }}" data-once>
            @csrf @method('put')
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Section</th><th class="text-center">Owner</th>@foreach ($editable as $label)<th class="text-center">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($sections as $key => [$label, $icon])
                            <tr>
                                <th scope="row" class="px-4 py-2 text-left font-medium"><span class="flex items-center gap-2.5">@svg('lucide-'.$icon, 'size-4 text-muted', ['aria-hidden' => 'true']){{ $label }}</span></th>
                                <td class="text-center"><x-lucide-check class="mx-auto size-4 text-success" aria-label="Always allowed" /></td>
                                @foreach ($editable as $role => $roleLabel)
                                    <td class="text-center">
                                        <input type="checkbox" name="perm[{{ $role }}][]" value="{{ $key }}" class="size-[1.125rem]" @checked(($matrix[$role] ?? collect())->contains($key)) aria-label="{{ $roleLabel }} can access {{ $label }}">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end border-t border-line p-4"><button class="btn btn-primary">Save permissions</button></div>
        </form>
    </section>

    @foreach ($staff->concat([null]) as $member)
        @php $mid = $member?->id ?? 'new'; @endphp
        <x-modal name="staff-{{ $mid }}" :title="$member ? 'Edit '.$member->name : 'Add staff member'">
            <form method="post" action="{{ $member ? route('admin.staff.update', $member) : route('admin.staff.store') }}" class="space-y-4" data-once>
                @csrf
                @if ($member) @method('put') @endif
                <x-field label="Full name" name="name" id="st-name-{{ $mid }}" required :value="$member?->name" />
                @if ($member)
                    <div><p class="label">Email</p><p class="flex h-11 items-center rounded-control bg-sunken px-3 text-[0.9375rem] text-muted">{{ $member->email }}</p></div>
                @else
                    <x-field label="Email" name="email" id="st-email-{{ $mid }}" type="email" required />
                @endif
                <x-field label="Role" name="role" id="st-role-{{ $mid }}" required :options="$roles" :value="$member?->role ?? 'orders'" />
                <x-field :label="$member ? 'New password' : 'Password'" name="password" id="st-pass-{{ $mid }}" type="password" :required="! $member" autocomplete="new-password" :hint="$member ? 'Leave empty to keep the current password.' : 'At least 8 characters.'" />
                @if ($member)
                    <label class="flex min-h-9 items-center gap-3 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="size-4" @checked($member->is_active)>Account is active (can sign in)</label>
                @endif
                <button class="btn btn-primary w-full">{{ $member ? 'Save changes' : 'Add staff member' }}</button>
            </form>
        </x-modal>
    @endforeach
@endsection
