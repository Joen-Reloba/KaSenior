<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('kasenior:migrate-tidb', function (): int {
    $connection = DB::connection();
    if ($connection->getDriverName() !== 'mysql') {
        $this->error('Select the MySQL connection for TiDB migrations.');

        return 1;
    }

    $lockName = 'kasenior:migrate:'.substr(hash('sha256', $connection->getDatabaseName()), 0, 32);
    $lock = $connection->selectOne('SELECT GET_LOCK(?, 20) AS acquired', [$lockName], false);
    if ((int) ($lock->acquired ?? 0) !== 1) {
        $this->error('Could not acquire the TiDB migration lock.');

        return 1;
    }

    try {
        return $this->call('migrate', ['--force' => true]);
    } finally {
        $connection->selectOne('SELECT RELEASE_LOCK(?)', [$lockName], false);
    }
})->purpose('Apply the KaSenior schema to TiDB without seeding');

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
