@extends('layouts.app')
@section('title', 'Superadmin')
@section('content')
<header class="page-header">
    <h1>Admin Accounts</h1>
    <form method="post" action="{{ route('admins.exit') }}" data-confirm="Sign out of Superadmin?" data-confirm-account="Superadmin" data-confirm-role="Superadmin">
        @csrf
        <button class="button small outline admin-reset" type="submit" aria-label="Exit Superadmin" title="Exit Superadmin">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5M15 8l4 4-4 4M8 12h11"/>
            </svg>
        </button>
    </form>
</header>
<form class="panel stack" method="post" action="{{ route('admins.store') }}">
    @csrf
    <h2>Create Admin</h2>
    <div class="form-grid">
        <label>Full Name<input name="name" value="{{ old('name') }}" required maxlength="255"></label>
        <label>Username<input name="username" value="{{ old('username') }}" required maxlength="255"></label>
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required maxlength="255"></label>
        <label>Default Password<input type="password" name="password" autocomplete="new-password" minlength="12" required></label>
    </div>
    <p>Choose at least 12 characters. Share the default password with the admin privately.</p>
    <div class="actions"><button class="button">Create Admin</button></div>
</form>
<section class="panel table-panel">
    <div class="table-scroll"><table>
        <thead><tr><th>Admin</th><th>Created</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($admins as $admin)
            <tr>
                <td><div class="person">
                    <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($admin->username ?? $admin->name, 0, 2)) }}</span>
                    <div><strong>{{ $admin->username ?? $admin->name }}</strong><small>{{ $admin->email }}</small></div>
                </div></td>
                <td>{{ $admin->created_at?->format('M j, Y, g:i:s A') ?? '—' }}</td>
                <td><span class="badge Active">Active</span></td>
                <td>
            @if($admin->default_password_hash)
                <form method="post" action="{{ route('admins.reset', $admin) }}" data-confirm="Reset the password for {{ $admin->username ?? $admin->name }} to its default?">
                    @csrf
                    @method('PUT')
                    <button class="button small outline admin-reset" type="submit" aria-label="Reset password to default" title="Reset password to default">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="8" cy="8" r="4"/><path d="m11 11 9 9m-5-5 2-2m0 4 2-2"/>
                        </svg>
                    </button>
                </form>
            @else
                No default password
            @endif
            </td></tr>
        @empty
            <tr><td colspan="4" class="empty">No admin accounts yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @include('components.pagination', ['rows' => $admins])
</section>
@endsection
