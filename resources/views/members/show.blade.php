@extends('layouts.app')
@section('title', $member->full_name)
@section('content')
<header class="page-header"><div><x-back-link :href="route('members.index', $member->trashed() ? ['status' => 'Archived'] : [])">Back to Members</x-back-link><h1>{{ $member->full_name }}</h1><p>{{ $member->age }} years old{{ $member->status === 'Deceased' ? ' at death' : '' }} · {{ $member->location }}</p></div>@if($member->trashed())<span class="badge">Archived</span>@else<a class="button" href="{{ route('members.edit', $member) }}">Edit Member</a>@endif</header>
<section class="panel"><h2>Member Profile</h2><dl class="details">
@foreach(['name_extension' => 'Name Extension', 'gender' => 'Gender', 'status' => 'Membership Status', 'location' => 'Purok', 'barangay' => 'Barangay', 'district' => 'District', 'mobile' => 'Contact Number', 'senior_citizen_id' => 'SC ID Number'] as $key => $label)<div><dt>{{ $label }}</dt><dd>{{ $member->$key ?: '—' }}</dd></div>@endforeach
<div><dt>ID Date Issued</dt><dd>{{ $member->id_issued_at?->format('F j, Y') ?? '—' }}</dd></div>
<div><dt>Date of Birth</dt><dd>{{ $member->birth_date->format('F j, Y') }}</dd></div><div><dt>Date Registered</dt><dd>{{ $member->registered_at->format('F j, Y') }}</dd></div>
</dl></section>
@unless($member->trashed())
<form method="post" action="{{ route('members.destroy', $member) }}" data-confirm="Archive this member? You can find them again using the Archived status filter. Their event contributions will be retained.">@csrf @method('delete')<button class="button danger">Archive Member</button></form>
@endunless
@endsection
