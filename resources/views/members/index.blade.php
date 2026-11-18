@extends('layouts.app')
@section('title', 'Members')
@section('content')
<header class="page-header">
    <div><h1>Members</h1><p>{{ $active }} active · {{ $deceased }} deceased · {{ $active + $deceased }} total</p></div>
    <div class="actions no-print"><button type="button" class="button outline" data-open-roster><x-icon name="Printer" screen="3" />Print PDF</button><a class="button" href="{{ route('members.create') }}"><x-icon name="Plus" />Add Member</a></div>
</header>
<form class="filters no-print" data-live-search method="get">
    <input aria-label="Search by name" name="q" value="{{ request('q') }}" placeholder="Search by name…">
    <label class="member-filter"><span>Status</span>
        <select name="status"><option value="">All statuses</option>@foreach(['Active', 'Deceased', 'Archived'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select>
    </label>
    <label class="member-filter"><span>Puroks</span>
        <select name="location"><option value="">All Puroks</option>@foreach($locations as $location)<option @selected(request('location') === $location)>{{ $location }}</option>@endforeach</select>
    </label>
    <label class="member-filter"><span>Sort</span>
        <select name="sort">
            <option value="alphabetical" @selected(request('sort', 'alphabetical') === 'alphabetical')>Alphabetical</option>
            <option value="recent" @selected(request('sort') === 'recent')>Recently added</option>
        </select>
    </label>
    <a class="button outline" href="{{ route('members.index') }}">Reset</a>
</form>

<p class="sr-only no-print" role="status" data-search-status></p>
<section class="panel table-panel" data-search-results>
<div class="table-scroll"><table>
    <thead><tr><th>Member</th><th>Age (auto)</th><th>Gender</th><th>Location</th><th>Status</th><th class="no-print"><span class="sr-only">Actions</span></th></tr></thead>
    <tbody>@forelse($members as $member)
    <tr><td><div class="person"><span class="avatar">{{ $member->initials }}</span><div><strong>{{ $member->full_name }}</strong><small>Born {{ $member->birth_date->format('M j, Y') }}</small></div></div></td><td><strong>{{ $member->age }} yrs</strong></td><td>{{ $member->gender }}</td><td>{{ $member->location }}</td><td><span class="badge {{ $member->trashed() ? 'Archived' : $member->status }}">{{ $member->trashed() ? 'Archived' : $member->status }}</span></td><td class="no-print"><a class="button small outline" href="{{ route('members.show', $member) }}">View</a></td></tr>
    @empty<tr><td colspan="6" class="empty">No members match these filters.</td></tr>@endforelse</tbody>
</table></div>
@include('components.pagination', ['rows' => $members])
</section>
@include('members.roster')
@endsection

