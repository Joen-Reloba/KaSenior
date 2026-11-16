@extends('layouts.app')
@section('title', 'Record Payment')
@section('content')
<header><x-back-link :href="route('events.show', $event)">Back to Event</x-back-link><h1>Record Payment</h1><p>{{ $event->name }} · {{ $contribution->member->full_name }}</p></header>
<form class="stack" method="post" action="{{ route('payments.update', [$event, $contribution]) }}">
@csrf @method('put')
<input type="hidden" name="version" value="{{ $contribution->version }}">
<section class="panel"><h2>Contribution</h2><p class="subtitle">Required contribution ₱{{ number_format($event->amount_cents / 100, 2) }}. Enter the total amount received for this member.</p><div class="form-grid">
<label>Total Received (₱) *<input type="number" name="amount" min="0" max="{{ $event->amount_cents / 100 }}" step="0.01" value="{{ old('amount', ($contribution->amount_cents ?: $event->amount_cents) / 100) }}" required></label>
<label>Date Paid<input type="date" name="paid_at" max="{{ today()->format('Y-m-d') }}" value="{{ old('paid_at', ($contribution->paid_at ?? today())->format('Y-m-d')) }}"></label>
</div><p class="subtitle">Enter 0 to correct a payment back to unpaid.</p></section>
<div class="actions end"><a class="button outline" href="{{ route('events.show', $event) }}">Cancel</a><button class="button">Save Payment</button></div>
</form>
@endsection
