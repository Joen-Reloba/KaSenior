@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<header><h1>Settings</h1></header>
<form class="panel stack" method="post" action="{{ route('settings.password') }}">
    @csrf
    @method('PUT')
    <h2>Change Password</h2>
    <label>Current Password<input type="password" name="current_password" autocomplete="current-password" required></label>
    <label>New Password<input type="password" name="password" autocomplete="new-password" minlength="12" required></label>
    <label>Confirm New Password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required></label>
    <p>Use at least 12 characters.</p>
    <div class="actions"><button class="button">Change Password</button></div>
</form>
@endsection
