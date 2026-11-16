@extends('layouts.app')
@section('title', 'Census Report')
@section('content')
<header class="page-header"><div><h1>Census Report</h1><p>Counts of active members as of {{ today()->format('F j, Y') }}</p></div><button class="button no-print" data-print><x-icon name="Printer" screen="5" />Print Report</button></header>
<form class="panel filters no-print" method="get">
<label>Age Group<select name="age"><option value="">All ages (60 and above)</option value="60" @selected(request('age') == 60)>60 – 69</option><option value="70" @selected(request('age') == 70)>70 – 79</option><option value="80" @selected(request('age') == 80)>80 – 89</option><option value="90" @selected(request('age') == 90)>90 and above</option></select></label>
<label>Gender<select name="gender"><option value="">All</option>@foreach(['Female', 'Male'] as $gender)<option @selected(request('gender') === $gender)>{{ $gender }}</option>@endforeach</select></label>
<label>Location<select name="location"><option value="">All Puroks</option>@foreach($locations as $location)<option @selected(request('location') === $location)>{{ $location }}</option>@endforeach</select></label>
<button class="button">Update</button>
</form>
<p class="print-only">Filters · Age {{ request('age', 'All') }} · Gender {{ request('gender') ?: 'All' }} · Location {{ request('location') ?: 'All' }}</p>
<div class="stats three">
@foreach([['Total Active Members', $members->count(), 'green'], ['Female', $members->where('gender', 'Female')->count(), 'peach'], ['Male', $members->where('gender', 'Male')->count(), 'blue']] as [$label, $value, $color])
<div class="summary {{ $color }}"><p>{{ $label }}</p><strong>{{ $value }}</strong></div>
@endforeach
</div>
<div class="split">
<section class="panel"><h2>By Age Group and Gender</h2><div class="table-scroll"><table class="census-table"><thead><tr><th>Age Group</th><th>Female</th><th>Male</th><th>Total</th></tr></thead><tbody>
@foreach($groups as $label => $group)<tr><td>{{ $label }}</td><td>{{ $group['female'] }}</td><td>{{ $group['male'] }}</td><td><strong>{{ $group['total'] }}</strong></td></tr>@endforeach
</tbody><tfoot><tr><th>Total</th><th>{{ $members->where('gender', 'Female')->count() }}</th><th>{{ $members->where('gender', 'Male')->count() }}</th><th>{{ $members->count() }}</th></tr></tfoot></table></div></section>
<section class="panel"><h2>By Location</h2>@forelse($byLocation as $location => $count)<div class="location-bar"><div><span>{{ $location }}</span><strong>{{ $count }}</strong></div><progress value="{{ $count }}" max="{{ max(1, $byLocation->max()) }}"></progress></div>@empty<p class="empty">No active members match these filters.</p>@endforelse</section>
</div>
@endsection
