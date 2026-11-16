@extends('layouts.app')
@section('title', $member->full_name)
@section('content')
<header class="page-header"><div><x-back-link :href="route('members.index')">Back to Members</x-back-link><h1>{{ $member->full_name }}</h1><p>{{ $member->age }} years old · {{ $member->location }}</p></div><a class="button" href="{{ route('members.edit', $member) }}">Edit Member</a></header>
<section class="panel"><h2>Member Profile</h2><dl class="details">
@foreach(['gender' => 'Gender', 'status' => 'Membership Status', 'location' => 'Location', 'address' => 'Address', 'mobile' => 'Mobile Number', 'emergency_contact' => 'Emergency Contact'] as $key => $label)<div><dt>{{ $label }}</dt><dd>{{ $member->$key ?: '—' }}</dd></div>@endforeach
<div><dt>Date of Birth</dt><dd>{{ $member->birth_date->format('F j, Y') }}</dd></div><div><dt>Date Registered</dt><dd>{{ $member->registered_at->format('F j, Y') }}</dd></div>
</dl></section>
<form method="post" action="{{ route('members.destroy', $member) }}" data-confirm="Remove this member from the member list? Their event contribution records will be retained.">@csrf @method('delete')<button class="button danger">Remove Member</button></form>
@endsection
