<div style="text-align:center;">
<strong>LEAGUE OF SENIOR CITIZENS ASSOCIATION OF BRGY. BALIOK<br>(LOSCABB)</strong><br>
Senior Citizens Bldg., Baliok,<br>Talomo District, Davao City<br><br>
<strong>CHAPTER DATA SHEET</strong><br>
AS OF {{ [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'][$asOf->quarter] }} Quarter of {{ $asOf->year }}
</div><br>
<table cellpadding="4" cellspacing="0">
@foreach(['NAME OF CHAPTER:' => $association, 'PRESIDENT:' => ($details['president'] ?? ''), 'ADDRESS:' => ($details['chapter_address'] ?? '')] as $label => $value)
<tr><td width="30%">{{ $label }}</td><td width="70%" style="border-bottom:0.5pt solid black;">{{ $value ?: ' ' }}</td></tr>
@endforeach
</table><br>
<div style="text-align:center;"><strong>ROSTER PROFILE</strong><br><br>
MALE: <u>&nbsp; {{ $members->where('gender', 'Male')->count() }} &nbsp;</u><br>
FEMALE: <u>&nbsp; {{ $members->where('gender', 'Female')->count() }} &nbsp;</u><br>
TOTAL = <u>&nbsp; {{ $members->count() }} &nbsp;</u><br><br>
TOTAL SOCIAL PENSIONERS: <u>&nbsp; {{ $details['pensioners'] ?? '________' }} &nbsp;</u><br>
TOTAL WAITLISTED: <u>&nbsp; {{ $details['waitlisted'] ?? '________' }} &nbsp;</u>
</div><br>
<strong>AGE BRACKET:</strong><br>
@foreach([80, 85, 90, 95, 100] as $age)
{{ $age }} YO - <u>&nbsp; {{ $members->filter(fn ($member) => $member->age === $age)->count() }} &nbsp;</u><br>
@endforeach
<br><br>
<table cellpadding="3" cellspacing="0" nobr="true">
@foreach(['SUBMITTED BY:' => ($details['submitted_by'] ?? ''), 'POSITION:' => ($details['position'] ?? 'President')] as $label => $value)
<tr><td width="40%"></td><td width="25%">{{ $label }}</td><td width="35%" align="center" style="border-bottom:0.5pt solid black;">{{ $value ?: ' ' }}</td></tr>
@endforeach
</table>
