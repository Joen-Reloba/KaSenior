@extends('layouts.app')
@section('title', $event->exists ? 'Edit Event' : 'Create Event')
@section('content')
<header><x-back-link :href="route('events.index')">Back to Events</x-back-link><h1>{{ $event->exists ? 'Edit Event' : 'Create Event' }}</h1><p>Required fields are marked with *.</p></header>
<form method="post" class="stack" action="{{ $event->exists ? route('events.update', $event) : route('events.store') }}">
@csrf @if($event->exists) @method('put') @endif
<section class="panel"><h2>Event Details</h2><div class="form-grid">
<label>Event Name *<input name="name" maxlength="255" value="{{ old('name', $event->name) }}" required></label>
<label>Event Date *<input type="date" name="event_date" value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}" required></label>
<label>Contribution per Member (₱) *<input type="number" min="0.01" max="1000000" step="0.01" name="amount" value="{{ old('amount', $event->exists ? $event->amount_cents / 100 : '') }}" required></label>
<div class="notice"><p>{{ $memberCount }} {{ $event->exists ? 'members in this event' : 'active members will be included' }}. Each event keeps its own member list.</p></div>
<label class="span-two">Description<textarea name="description" rows="4" maxlength="5000">{{ old('description', $event->description) }}</textarea></label>
</div></section><div class="actions end"><a class="button outline" href="{{ route('events.index') }}">Cancel</a><button class="button">Save Event</button></div>
</form>
@endsection
