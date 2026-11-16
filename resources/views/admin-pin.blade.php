<dialog class="confirmation-dialog pin-dialog" aria-labelledby="pin-title" @if($openPin ?? false) data-pin-auto-open @endif>
<form class="pin-panel" method="post" action="{{ route('admins.unlock') }}" data-pin-form>
    @csrf
    <button type="button" class="pin-cancel" data-pin-close aria-label="Back to sign in" title="Back to sign in">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m12 19-7-7 7-7M5 12h14"/>
        </svg>
    </button>
    <h2 id="pin-title">Enter Superadmin PIN</h2>
    @error('pin')<p class="errors" role="alert">{{ $message }}</p>@enderror
    <label>Nine-digit PIN<input type="password" name="password" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" autocomplete="off" required></label>
    <div class="pin-pad" hidden>
        @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 'Clear', 0, 'Delete'] as $key)
            <button type="button" class="pin-key" data-pin-key="{{ $key }}">{{ $key }}</button>
        @endforeach
    </div>
    <button class="submit">Unlock</button>
</form>
</dialog>
