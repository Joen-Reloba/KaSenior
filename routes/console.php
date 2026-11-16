<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('kasenior:create-user', function (): int {
    $attributes = [
        'name' => $this->ask('Full name'),
        'username' => $this->ask('Username'),
        'email' => $this->ask('Email address'),
        'password' => $this->secret('Password'),
    ];

    $validator = Validator::make($attributes, [
        'name' => ['required', 'string', 'max:255'],
        'username' => ['required', 'alpha_dash', 'max:255', 'unique:users'],
        'email' => ['required', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'max:72', Password::min(12)],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return 1;
    }

    User::create($validator->validated());
    $this->info('Sign-in account created.');

    return 0;
})->purpose('Create a KaSenior sign-in account');
