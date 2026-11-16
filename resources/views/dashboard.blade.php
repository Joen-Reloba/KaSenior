@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<header class="page-header">
    <div><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}!</h1><p>{{ now()->format('l, F j, Y') }}</p></div>
    <a class="button" href="{{ route('members.create') }}"><x-icon name="Plus" />Add Member</a>
</header>
<div class="stats dashboard-stats">
    @foreach ([
        ['Total Registered', $total, 'All members on record', 'Users1', 'blue'],
        ['Active Members', $active, 'Currently active', 'Check', 'green'],
        ['Deceased', $deceased, 'Kept for records', 'Ribbon', 'gray'],
        ['Unpaid Contributions', $summary['unpaid'] ?? 0, $event ? 'For '.$event->name : 'No event yet', 'Coins', 'amber'],
    ] as [$label, $value, $caption, $icon, $color])
    <section class="stat panel"><span class="stat-icon {{ $color }}"><x-icon :name="$icon" /></span><p>{{ $label }}</p><strong>{{ $value }}</strong><small>{{ $caption }}</small></section>
    @endforeach
</div>
<div class="split">
    <section class="panel">
        <h2>Members by Age Group</h2><p class="subtitle">Calculated automatically from birthdates</p>
        @foreach($groups as $label => $group)
        <div class="bar-row"><span>{{ $label }}</span><progress max="{{ max(1, collect($groups)->max('total')) }}" value="{{ $group['total'] }}"></progress><strong>{{ $group['total'] }}</strong></div>
        @endforeach
    </section>
    <section class="panel">
        <h2>Current Event</h2>
        @if($event)
            <p class="subtitle">{{ $event->name }} · {{ $event->event_date->format('M j, Y') }}</p>
            <p class="collection"><strong>₱{{ number_format($summary['collected'] / 100, 2) }}</strong> of ₱{{ number_format($summary['expected'] / 100, 2) }}</p>
            <progress max="100" value="{{ $summary['percent'] }}"></progress>
            <div class="actions badges"><span class="badge Active">{{ $summary['paid'] }} paid</span><span class="badge Unpaid">{{ $summary['unpaid'] }} unpaid</span></div>
            <a class="button outline full" href="{{ route('events.show', $event) }}">Open Event</a>
        @else
            <p class="empty">Create an event to start tracking contributions.</p><a class="button outline full" href="{{ route('events.create') }}">Create Event</a>
        @endif
    </section>
</div>
<section class="panel">
    <h2>Recently Added Members</h2>
    @forelse($recent as $member)
    <a class="member-row" href="{{ route('members.show', $member) }}"><span class="avatar">{{ $member->initials }}</span><div><strong>{{ $member->full_name }}</strong><p>{{ $member->age }} yrs · {{ $member->location }}</p></div><span class="badge {{ $member->status }}">{{ $member->status }}</span></a>
    @empty<p class="empty">No members yet. Add your first member to begin.</p>@endforelse
</section>
@endsection
