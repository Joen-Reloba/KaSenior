<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function (): void {
    Route::view('/superadmin/pin', 'login', ['openPin' => true])->name('admins.pin');
    Route::post('/superadmin/pin', [App\Http\Controllers\AccountController::class, 'unlock'])
        ->middleware('throttle:5,1')->name('admins.unlock');
    Route::get('/superadmin', [App\Http\Controllers\AccountController::class, 'index'])->name('admins.index');
    Route::post('/superadmin/admins', [App\Http\Controllers\AccountController::class, 'store'])->name('admins.store');
    Route::put('/superadmin/admins/{admin}/reset', [App\Http\Controllers\AccountController::class, 'reset'])->name('admins.reset');
    Route::post('/superadmin/exit', [App\Http\Controllers\AccountController::class, 'exit'])->name('admins.exit');

    Route::view('/login', 'login')->name('login');

    Route::post('/login', function (Request $request): RedirectResponse {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['username' => 'The username or password is incorrect.'])
                ->onlyInput('username');
        }

        $request->session()->forget('superadmin_unlock');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    })->middleware('throttle:10,1')->name('login.store');
});

Route::middleware(['auth', 'auth.session'])->group(function (): void {
    Route::view('/settings', 'settings')->name('settings');
    Route::put('/settings/password', [App\Http\Controllers\AccountController::class, 'changePassword'])->middleware('throttle:10,1')->name('settings.password');
    Route::get('/dashboard', [App\Http\Controllers\ReportController::class, 'dashboard'])->name('dashboard');
    Route::resource('members', App\Http\Controllers\MemberController::class);
    Route::get('/census', [App\Http\Controllers\ReportController::class, 'census'])->name('census');
    Route::resource('events', App\Http\Controllers\EventController::class)->except('destroy');
    Route::get('/events/{event}/payments/{contribution}', [App\Http\Controllers\EventController::class, 'payment'])->name('payments.edit');
    Route::put('/events/{event}/payments/{contribution}', [App\Http\Controllers\EventController::class, 'savePayment'])->name('payments.update');

    Route::post('/logout', function (Request $request): RedirectResponse {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});


