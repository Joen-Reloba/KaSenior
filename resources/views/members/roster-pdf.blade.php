<table cellpadding="0" cellspacing="0"><tr>
<td width="18%"></td><td width="10%" align="center"><img src="{{ public_path('davao-city-logo.png') }}" width="52" height="52"></td>
<td width="44%" align="center" style="font-size:8pt;"><strong>Republic of the Philippines<br>City Social Welfare and the Development Office City of Davao</strong><br>
Tel Nos. (082) 227-1617/ (082) 225-0417 Telefax c/o: CSWDO<br>
<span style="color:#aaaaaa;"><u>cssdo@davaocity.gov.ph</u></span><br>
<strong>SENIOR CITIZEN ORGANIZATION ROSTER As of</strong> <u>{{ [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'][$asOf->quarter] }} Quarter, {{ $asOf->year }}</u></td>
<td width="10%" align="center"><img src="{{ public_path('cswdo-logo.png') }}" width="68" height="68"></td><td width="18%"></td>
</tr></table>
<br>
<table cellpadding="0" cellspacing="0"><tr>
<td width="29%"><strong>NAME OF ASSOCIATION:</strong> <u>{{ mb_strtoupper($association ?? '') }}</u></td>
<td width="18%"><strong>BARANGAY:</strong> <u>{{ mb_strtoupper($reportBarangay ?? '') }}</u></td>
<td width="16%"><strong>DISTRICT:</strong> <u>{{ mb_strtoupper($reportDistrict ?? '') }}</u></td>
<td width="37%"><strong>DATE ORGANIZED/REORGANIZED:</strong> <u>{{ $organizedAt ? mb_strtoupper(\Illuminate\Support\Carbon::parse($organizedAt)->format('M j, Y')) : '' }}</u></td>
</tr></table>
<table border="1" cellpadding="0" cellspacing="0" style="text-align:center;">
<tr style="font-weight:bold;">
@foreach(['NO.' => 3, 'LAST NAME' => 8.4, 'FIRST NAME' => 9, 'MIDDLE NAME' => 8.2, 'EXT.' => 4, 'MM/DD/YY' => 7.4, 'AGE' => 4, 'SEX' => 5, 'PUROK' => 7.4, 'BARANGAY' => 8.2, 'DISTRICT' => 7.4, 'SC ID #' => 7.4, 'DATE ISSUED' => 9, 'CONTACT NUMBER' => 11.6] as $label => $width)
<th width="{{ $width }}%"><strong>
@if($label === 'MIDDLE NAME')
MIDDLE<br>NAME
@else
{{ $label }}
@endif
</strong></th>
@endforeach
</tr>
@forelse($members as $member)
@php
    $ageDate = $member->status === 'Deceased' && $member->deceased_at && $member->deceased_at->lt($asOf) ? $member->deceased_at : $asOf;
@endphp
<tr nobr="true">
<td width="3%">{{ $loop->iteration }}.</td>
<td width="8.4%">{{ $member->last_name }}</td><td width="9%">{{ $member->first_name }}</td><td width="8.2%">{{ $member->middle_name }}</td><td width="4%">{{ $member->name_extension }}</td>
<td width="7.4%">{{ $member->birth_date->format('m/d/Y') }}</td><td width="4%">{{ $member->birth_date->lte($ageDate) ? (int) $member->birth_date->diffInYears($ageDate) : '' }}</td><td width="5%">{{ $member->gender === 'Female' ? 'F' : 'M' }}</td>
<td width="7.4%">{{ $member->location }}</td><td width="8.2%">{{ $member->barangay }}</td><td width="7.4%">{{ $member->district }}</td><td width="7.4%">{{ $member->senior_citizen_id }}</td><td width="9%">{{ $member->id_issued_at?->format('m/d/Y') }}</td><td width="11.6%">{{ $member->mobile }}</td>
</tr>
@empty<tr><td colspan="14">No members match these filters.</td></tr>@endforelse
</table>
<br><br><br><br>
<table cellpadding="0" cellspacing="0" nobr="true"><tr>
@foreach([['Submitted by:', $submittedBy, 'Chapter President'], ['Verified by:', $verifiedBy, 'Barangay Social Worker/DSI'], ['Noted by:', $notedBy, 'District Head']] as [$label, $name, $role])
<td width="33.33%"><table cellpadding="0" cellspacing="0"><tr>
<td width="27%">{{ $label }}</td><td width="63%" align="center" style="border-bottom:0.6pt solid #000000;">{{ $name ?: ' ' }}</td><td width="10%"></td>
</tr><tr><td width="27%"></td><td width="63%" align="center">{{ $role }}</td><td width="10%"></td></tr></table></td>
@endforeach
</tr></table>
