<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function unlock(Request $request): RedirectResponse
    {
        $pinHash = config('kasenior.superadmin_pin_hash');

        $pin = $request->input('password');
        if (! is_string($pinHash) || $pinHash === '' || ! is_string($pin) || ! preg_match('/^[0-9]{9}$/D', $pin)
            || ! Hash::check($pin, $pinHash)) {
            return redirect()->route('admins.pin')->withErrors(['pin' => 'The PIN is incorrect or has not been configured.']);
        }

        $request->session()->regenerate();
        $request->session()->put('superadmin_unlock', hash('sha256', $pinHash));

        return redirect()->route('admins.index');
    }

    private function isUnlocked(Request $request): bool
    {
        $pinHash = config('kasenior.superadmin_pin_hash');

        return is_string($pinHash) && $pinHash !== '' && hash_equals(hash('sha256', $pinHash), (string) $request->session()->get('superadmin_unlock'));
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->isUnlocked($request)) {
            return redirect()->route('admins.pin');
        }

        return view('admins', ['admins' => User::query()->orderBy('name')->paginate(15)]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isUnlocked($request), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:255', 'unique:users'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'max:72', Password::min(12)],
        ]);

        $admin = new User($data);
        $admin->default_password_hash = $admin->password;
        $admin->save();

        return back()->with('success', 'Admin account created.');
    }

    public function reset(Request $request, User $admin): RedirectResponse
    {
        abort_unless($this->isUnlocked($request), 403);
        abort_if(! $admin->default_password_hash, 404);

        $admin->forceFill([
            'password' => $admin->default_password_hash,
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('success', 'Admin password reset to its default.');
    }

    public function exit(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'max:72', 'confirmed', 'different:current_password', Password::min(12)],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return back()->with('success', 'Password changed.');
    }
}
