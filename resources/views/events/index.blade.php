@extends('layouts.app')
@section('title', 'Events')
@section('content')
<header class="page-header"><div><h1>Events</h1><p>Manage association events and member contributions.</p></div><a class="button" href="{{ route('events.create') }}"><x-icon name="Plus" />Create Event</a></header>
<div class="event-grid">
@forelse($events as $event)
@php($summary = $event->summary())
<section class="panel"><h2>{{ $event->name }}</h2><p class="subtitle">{{ $event->event_date->format('F j, Y') }} · ₱{{ number_format($event->amount_cents / 100, 2) }} per member</p><p class="collection"><strong>₱{{ number_format($summary['collected'] / 100, 2) }}</strong> collected</p><progress max="100" value="{{ $summary['percent'] }}"></progress><p class="subtitle">{{ $summary['paid'] }} paid · {{ $summary['unpaid'] }} unpaid</p><a class="button outline full" href="{{ route('events.show', $event) }}">Open Event</a></section>
@empty<section class="panel"><h2>No events yet</h2><p class="empty">Create your first event to start tracking contributions.</p></section>@endforelse
</div>
@include('components.pagination', ['rows' => $events])
@endsection
