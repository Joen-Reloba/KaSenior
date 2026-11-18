@extends('layouts.app')
@section('title', $member->exists ? 'Edit Member' : 'Add New Member')
@section('content')
<header><x-back-link :href="route('members.index')">Back to Members</x-back-link><h1>{{ $member->exists ? 'Edit Member' : 'Add New Member' }}</h1><p>Fill in the details below. Required fields are marked with *.</p></header>
<form class="stack" method="post" action="{{ $member->exists ? route('members.update', $member) : route('members.store') }}">
@csrf @if($member->exists) @method('put') @endif
<section class="panel"><h2>Personal Information</h2><p class="subtitle">Basic details of the senior citizen</p>
<div class="form-grid">
@foreach(['first_name' => 'First Name *', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name *'] as $key => $label)
<label>{{ $label }}<input name="{{ $key }}" value="{{ old($key, $member->$key) }}" maxlength="255" @required($key !== 'middle_name')></label>
@endforeach
<label>Name Extension<input name="name_extension" value="{{ old('name_extension', $member->name_extension) }}" maxlength="40" placeholder="e.g. Jr., Sr., III"></label>
<label>Gender *<select name="gender" required><option value="">Select gender</option>@foreach(['Female', 'Male'] as $gender)<option @selected(old('gender', $member->gender) === $gender)>{{ $gender }}</option>@endforeach</select></label>
<label>Date of Birth *<input type="date" id="birth-date" name="birth_date" value="{{ old('birth_date', $member->birth_date?->format('Y-m-d')) }}" max="{{ today()->subYears(60)->format('Y-m-d') }}" required></label>
<div class="notice age-hint"><x-icon name="Info" screen="4" /><div><strong>Age is calculated automatically</strong><p id="age-preview" aria-live="polite">Enter a birthdate to see the current age.</p></div></div>
</div></section>
<section class="panel"><h2>Address &amp; Contact</h2><p class="subtitle">Where the member lives and how to reach them</p>
<div class="form-grid">
@foreach(['location' => 'Purok *', 'barangay' => 'Barangay *', 'district' => 'District *', 'mobile' => 'Contact Number'] as $key => $label)
<label>{{ $label }}<input name="{{ $key }}" value="{{ old($key, $member->$key) }}" @if(in_array($key, ['location', 'barangay', 'district'])) data-default-value="{{ ['location' => '7C', 'barangay' => 'Baliok', 'district' => 'Talomo'][$key] }}" @endif maxlength="{{ $key === 'mobile' ? 40 : 255 }}" @required(in_array($key, ['location', 'barangay', 'district']))></label>
@endforeach
</div></section>
<section class="panel"><h2>Senior Citizen Identification</h2><p class="subtitle">Leave these blank if the ID details are not yet available.</p><div class="form-grid">
<label>SC ID Number<input name="senior_citizen_id" value="{{ old('senior_citizen_id', $member->senior_citizen_id) }}" maxlength="100"></label>
<label>ID Date Issued<input type="date" name="id_issued_at" value="{{ old('id_issued_at', $member->id_issued_at?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}"></label>
</div></section>
<section class="panel"><h2>Membership</h2><p class="subtitle">Status of this member in the association</p><div class="form-grid">
<label>Membership Status *<select name="status" id="membership-status">@foreach(['Active', 'Deceased'] as $status)<option @selected(old('status', $member->status) === $status)>{{ $status }}</option>@endforeach</select></label>
<label>Date of Death (for deceased members)<input type="date" name="deceased_at" id="deceased-at" @disabled(old('status', $member->status) !== 'Deceased') value="{{ old('deceased_at', $member->deceased_at?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}"><small>Leave blank to use the date the member is marked deceased.</small></label>
<label>Date Registered *<input type="date" name="registered_at" value="{{ old('registered_at', $member->registered_at?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required></label>
</div></section>
<div class="actions end"><a class="button outline" href="{{ route('members.index') }}">Cancel</a><button class="button"><x-icon name="Check" screen="4" />Save Member</button></div>
</form>
@endsection
