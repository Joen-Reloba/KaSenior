@extends('layouts.app')
@section('title', 'Superadmin Settings')
@section('content')
<header><h1>Settings</h1></header>
<form class="panel stack" method="post" action="{{ route('admins.settings.pin') }}">
    @csrf
    @method('PUT')
    <h2>Change PIN</h2>
    <p>Use nine digits for the new PIN. Changing it signs out other superadmin sessions.</p>
    <div class="form-grid">
        <label>Current PIN<input type="password" name="current_password" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" autocomplete="current-password" required></label>
        <label>New PIN<input type="password" name="password" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" autocomplete="new-password" required></label>
        <label>Confirm New PIN<input type="password" name="password_confirmation" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" autocomplete="new-password" required></label>
    </div>
    <div class="actions"><button class="button">Change PIN</button></div>
</form>
@endsection
