<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | KaSenior</title>
    <link rel="stylesheet" href="{{ asset('login.css') }}">
    <script src="{{ asset('app.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('confirmation.css') }}">
</head>
<body>
    <main class="login">
        <section class="welcome" aria-labelledby="welcome-title">
            <div class="brand">
                <span class="brand-icon"><img src="{{ asset('icons/heart.svg') }}" alt=""></span>
                <div><strong>KaSenior</strong><p>Census &amp; Contributions</p></div>
            </div>
            <h1 id="welcome-title">Welcome back</h1>
            <p class="intro">Keep every member record, census count and event contribution in one calm, easy-to-read place.</p>
            <ul class="features">
                @foreach ([
                    'Ages update automatically' => 'No more editing spreadsheets every year.',
                    'Census in one click' => 'Counts by age group, gender and location.',
                    'Know who has paid' => 'Track every event contribution clearly.',
                ] as $title => $description)
                    <li>
                        <span class="check"><img src="{{ asset('icons/check.svg') }}" alt=""></span>
                        <div><strong>{{ $title }}</strong><p>{{ $description }}</p></div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="form-area" aria-labelledby="signin-title">
            <form class="card" method="post" data-confirm="Sign in as “{username}”?" action="{{ route('login.store') }}">
                @csrf
                <header>
                    <h2 id="signin-title">Sign in</h2>
                    <p>Please enter your username and password.</p>
                </header>
                @if ($errors->any())
                    <ul class="errors" role="alert">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" placeholder="Enter your username" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password">
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                        <button id="toggle-password" type="button" aria-controls="password" hidden>Show</button>
                    </div>
                </div>
                <button class="submit" type="submit">Sign In</button>
                <p class="help">Forgot your password? Please ask the association administrator.</p>
            </form>
        </section>
    </main>
    <script>
        const password = document.getElementById('password');
        const toggle = document.getElementById('toggle-password');
        toggle.hidden = false;
        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.textContent = show ? 'Hide' : 'Show';
        });
    </script>

    <a class="button outline superadmin-shortcut no-print" href="{{ route('admins.pin') }}" aria-label="Superadmin" title="Superadmin">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s7-3 7-10V5l-7-3-7 3v7c0 7 7 10 7 10Z"/><path d="m9 12 2 2 4-4"/></svg>
    </a>

    @include('admin-pin')
    <x-confirmation-dialog />
</body>
</html>

