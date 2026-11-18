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
@php($superadmin = request()->routeIs('admins.index', 'admins.settings'))
<div @class(['shell', 'developer-shell' => auth()->guest() && ! $superadmin])>
    @if(auth()->check() || $superadmin)
    <aside @class(['sidebar', 'superadmin-sidebar' => $superadmin])>
        <a class="brand" href="{{ route($superadmin ? 'admins.index' : 'dashboard') }}">
            <span class="logo"><x-icon name="Heart" /></span>
            <span><strong>KaSenior</strong><small>{{ $superadmin ? 'Superadmin' : 'Census & Contributions' }}</small></span>
        </a>
        <div class="account">
            <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'Superadmin', 0, 1)) }}</span>
            <strong>{{ auth()->user()?->name ?? 'Superadmin' }}</strong>
        </div>
        <hr class="sidebar-divider">
        <nav aria-label="Main navigation">
            @if($superadmin)
                <a href="{{ route('admins.index') }}" @class(['selected' => request()->routeIs('admins.index')]) @if(request()->routeIs('admins.index')) aria-current="page" @endif>
                    <x-icon name="Users" :screen="request()->routeIs('admins.index') ? 3 : 2" />Admin Accounts
                </a>
            @else
            @foreach (['dashboard' => ['Dashboard', 'Home'], 'members.index' => ['Members', 'Users'], 'census' => ['Census Reports', 'Chart'], 'events.index' => ['Events', 'Calendar']] as $route => [$label, $icon])
                @php($activeNav = request()->routeIs(explode('.', $route)[0].'*'))
                <a href="{{ route($route) }}" class="{{ $activeNav ? 'selected' : '' }}" @if($activeNav) aria-current="page" @endif>
                    <x-icon :name="$icon" :screen="$activeNav ? ['dashboard' => 2, 'members.index' => 3, 'census' => 5, 'events.index' => 6][$route] : ($route === 'dashboard' ? 3 : 2)" />
                    {{ $label }}
                </a>
            @endforeach
            @endif
            <a href="{{ route($superadmin ? 'admins.settings' : 'settings') }}" @class(['selected' => request()->routeIs('admins.settings', 'settings', 'settings.*', 'templates.*')]) @if(request()->routeIs('admins.settings', 'settings', 'settings.*', 'templates.*')) aria-current="page" @endif>
                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9.5 3h5l.6 2.5 1.6.9 2.5-.7 2.5 4.3-1.9 1.8v1.8l1.9 1.8-2.5 4.3-2.5-.7-1.6.9-.6 2.1h-5l-.6-2.1-1.6-.9-2.5.7-2.5-4.3 1.9-1.8v-1.8l-1.9-1.8 2.5-4.3 2.5.7 1.6-.9z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Settings
            </a>
        </nav>
        <form class="signout-form" method="post" data-confirm="Sign out as “{username}”?" data-confirm-account="{{ auth()->user()?->username ?? auth()->user()?->name ?? 'Superadmin' }}" @if($superadmin) data-confirm-role="Superadmin" @endif action="{{ route($superadmin ? 'admins.exit' : 'logout') }}">
            @csrf
            <button class="button outline signout-button" type="submit">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5M15 8l4 4-4 4M8 12h11"/>
                </svg>
                Sign out
            </button>
        </form>
    </aside>
    @endif
    <main id="main" class="main">
        @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())
            <div class="error" role="alert"><strong>Please check the following</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </main>
</div>
    <x-confirmation-dialog />
    <dialog id="print-options" class="confirmation-dialog print-dialog no-print" aria-labelledby="print-options-title" data-report="{{ request()->routeIs('events.show') ? 'events' : (request()->routeIs('census') ? 'census' : 'members') }}" data-event="{{ request()->route('event')?->id }}">
        <form method="dialog" @class(['stack' => request()->routeIs('census')])>
            @if(request()->routeIs('census'))
            <header class="page-header">
                <div><h2 id="print-options-title">Print Census Report</h2><p>Review the report details, then open the PDF to print.</p></div>
                <button class="button outline" value="cancel" formnovalidate>Close</button>
            </header>
            @else
            <header class="print-dialog-header">
                <span class="print-dialog-icon"><x-icon name="Printer" /></span>
                <div><h2 id="print-options-title">Print options</h2><p>{{ request()->routeIs('events.show') ? 'Event collection report' : (request()->routeIs('census') ? 'Census report' : 'Member list') }}</p></div>
            </header>
            @endif
            <fieldset class="print-layouts" id="print-template">
                <legend>{{ request()->routeIs('census') ? 'Choose a template' : 'Choose a layout' }}</legend>
                @unless(request()->routeIs('census'))<p class="print-layout-help">Select a layout for your report.</p>@endunless
                <label class="print-layout-card">
                    <input type="radio" name="template" value="{{ request()->routeIs('census') ? route('census.pdf') : '' }}" @if(request()->routeIs('census')) data-format="pdf" data-census-default @endif checked>
                    <span><span class="print-layout-title">{{ request()->routeIs('census') ? 'Chapter data sheet PDF' : 'Default layout' }} <span class="print-recommended">{{ request()->routeIs('census') ? 'Default' : 'Recommended' }}</span></span><small>{{ request()->routeIs('census') ? 'Census totals for the selected members' : 'Standard report with all matching records' }}</small></span>
                </label>
                @foreach($printTemplates as $printTemplate)
                    <label class="print-layout-card">
                        <input type="radio" name="template" value="{{ route('templates.report', $printTemplate) }}" data-format="{{ $printTemplate->format }}">
                        <span><span class="print-layout-title">{{ $printTemplate->name }}</span><small>{{ strtoupper($printTemplate->format) }}@if($printTemplate->created_at) · <x-template-added :template="$printTemplate" />@endif</small></span>
                    </label>
                @endforeach
            </fieldset>
            @auth<a class="print-manage" href="{{ route('templates.index') }}"><x-icon name="Plus" />Upload or manage templates</a>@endauth
            @if(request()->routeIs('census'))
                @include('components.census-print-fields')
            @endif
            <p data-print-hint hidden></p>
            <div class="confirmation-actions print-dialog-actions">
                <button value="cancel" formnovalidate>Cancel</button>
                <button value="print"><x-icon name="Printer" /><span data-print-action>{{ request()->routeIs('census') ? 'Generate Report' : 'Print' }}</span></button>
            </div>
        </form>
    </dialog>
</body>
</html>


