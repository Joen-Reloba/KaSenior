@extends('layouts.app')
@section('title', $event->name)
@section('content')
<header class="page-header"><div><x-back-link :href="route('events.index')">Back to Events</x-back-link><h1>{{ $event->name }}</h1><p>{{ $event->event_date->format('F j, Y') }} · Contribution ₱{{ number_format($event->amount_cents / 100, 2) }} per member</p></div><div class="actions no-print"><a class="button outline" href="{{ route('events.edit', $event) }}"><x-icon name="Edit" screen="6" />Edit Event</a><a class="button" @if(request()->boolean('print')) href="#" data-print data-print-on-load @else href="{{ route('events.show', ['event' => $event, 'print' => 1]) }}" @endif><x-icon name="Printer" screen="6" />Print Collection Report</a></div></header>
@if($event->description)<p>{{ $event->description }}</p>@endif
<div class="stats">
@foreach([['Total Expected', $summary['expected'], $summary['count'].' members', 'blue'], ['Collected', $summary['collected'], $summary['paid'].' members paid', 'green'], ['Outstanding', $summary['outstanding'], $summary['unpaid'].' members unpaid', 'amber']] as [$label, $value, $caption, $color])
<div class="summary {{ $color }}"><p>{{ $label }}</p><strong>₱{{ number_format($value / 100, 2) }}</strong><small>{{ $caption }}</small></div>
@endforeach
<div class="summary panel"><p>Collection Progress</p><strong>{{ $summary['percent'] }}% paid</strong><progress max="100" value="{{ $summary['percent'] }}"></progress></div>
</div>
<form method="get" class="filters no-print" data-live-search><select name="status" aria-label="Payment status"><option value="">All Members {{ $summary['count'] }}</option><option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid {{ $summary['unpaid'] }}</option><option value="paid" @selected(request('status') === 'paid')>Paid {{ $summary['paid'] }}</option></select><input name="q" aria-label="Search member" placeholder="Search member…" value="{{ request('q') }}"><button class="button">Search</button></form>

<p class="sr-only no-print" role="status" data-search-status></p>
<section class="panel table-panel" data-search-results><div class="table-scroll"><table><thead><tr><th>Member</th><th>Location</th><th>Status</th><th>Amount</th><th>Date Paid</th><th class="no-print"><span class="sr-only">Actions</span></th></tr></thead><tbody>
@forelse($contributions as $contribution)
@php($paid = $contribution->amount_cents >= $event->amount_cents)
<tr class="{{ $paid ? '' : 'unpaid-row' }}"><td><div class="person"><span class="avatar">{{ $contribution->member->initials }}</span><strong>{{ $contribution->member->full_name }}</strong></div></td><td>{{ $contribution->member->location }}</td><td><span class="badge {{ $paid ? 'Active' : 'Unpaid' }}"><x-icon :name="$paid ? 'Check' : 'Clock'" screen="6" />{{ $paid ? 'Paid' : ($contribution->amount_cents ? 'Partial' : 'Unpaid') }}</span></td><td>₱{{ number_format($contribution->amount_cents / 100, 2) }}</td><td>{{ $contribution->paid_at?->format('M j, Y') ?? '—' }}</td><td class="no-print"><a class="button small {{ $paid ? 'outline' : '' }}" href="{{ route('payments.edit', [$event, $contribution]) }}">@if(!$paid)<x-icon name="Check1" screen="6" />@endif{{ $paid ? 'Edit' : 'Mark Paid' }}</a></td></tr>
@empty<tr><td colspan="6" class="empty">No members match this payment list.</td></tr>@endforelse
</tbody></table></div>@include('components.pagination', ['rows' => $contributions])</section>
@endsection

