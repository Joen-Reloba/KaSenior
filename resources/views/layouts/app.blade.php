<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | KaSenior</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}">
    <script src="{{ asset('app.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('confirmation.css') }}">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div @class(['shell', 'developer-shell' => auth()->guest()])>
    @auth
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="logo"><x-icon name="Heart" /></span>
            <span><strong>KaSenior</strong><small>Census &amp; Contributions</small></span>
        </a>
        <div class="account">
            <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <strong>{{ auth()->user()->name }}</strong>
        </div>
        <hr class="sidebar-divider">
        <nav aria-label="Main navigation">
            @foreach (['dashboard' => ['Dashboard', 'Home'], 'members.index' => ['Members', 'Users'], 'census' => ['Census Reports', 'Chart'], 'events.index' => ['Events', 'Calendar']] as $route => [$label, $icon])
                @php($activeNav = request()->routeIs(explode('.', $route)[0].'*'))
                <a href="{{ route($route) }}" class="{{ $activeNav ? 'selected' : '' }}" @if($activeNav) aria-current="page" @endif>
                    <x-icon :name="$icon" :screen="$activeNav ? ['dashboard' => 2, 'members.index' => 3, 'census' => 5, 'events.index' => 6][$route] : ($route === 'dashboard' ? 3 : 2)" />
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('settings') }}" @class(['selected' => request()->routeIs('settings')]) @if(request()->routeIs('settings')) aria-current="page" @endif>
                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9.5 3h5l.6 2.5 1.6.9 2.5-.7 2.5 4.3-1.9 1.8v1.8l1.9 1.8-2.5 4.3-2.5-.7-1.6.9-.6 2.1h-5l-.6-2.1-1.6-.9-2.5.7-2.5-4.3 1.9-1.8v-1.8l-1.9-1.8 2.5-4.3 2.5.7 1.6-.9z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Settings
            </a>
        </nav>
        <form class="signout-form" method="post" data-confirm="Sign out as “{username}”?" data-confirm-account="{{ auth()->user()->username ?? auth()->user()->name }}" action="{{ route('logout') }}">
            @csrf
            <button class="button outline signout-button" type="submit">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5M15 8l4 4-4 4M8 12h11"/>
                </svg>
                Sign out
            </button>
        </form>
    </aside>
    @endauth
    <main id="main" class="main">
        @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())
            <div class="error" role="alert"><strong>Please check the following</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </main>
</div>
    <x-confirmation-dialog />
</body>
</html>


