<dialog id="member-roster-dialog" class="confirmation-dialog roster-dialog no-print" aria-labelledby="roster-dialog-title" @if($openRoster ?? false) data-open-on-load @endif>
<header class="page-header"><div><h2 id="roster-dialog-title">Print Member Roster</h2><p>Review the report details, then open the PDF to print.</p></div><button type="button" class="button outline" data-close-roster aria-label="Close print settings">Close</button></header>
<form method="get" action="{{ route('members.roster') }}" class="stack" target="_blank" data-roster-form>
<input type="hidden" name="download" value="1">
<input type="hidden" name="report" value="members">
<fieldset class="print-layouts">
    <legend>Choose a template</legend>
    <label class="print-layout-card">
        <input type="radio" name="template_url" value="" data-format="pdf" checked>
        <span><span class="print-layout-title">Senior citizen roster PDF <span class="print-recommended">Default</span></span><small>Roster layout with both logos, member details, and signature lines</small></span>
    </label>
    @foreach($printTemplates as $template)
        <label class="print-layout-card">
            <input type="radio" name="template_url" value="{{ route('templates.report', $template) }}" data-format="{{ $template->format }}">
            <span><span class="print-layout-title">{{ $template->name }}</span><small>{{ strtoupper($template->format) }}@if($template->created_at) · <x-template-added :template="$template" />@endif</small></span>
        </label>
    @endforeach
</fieldset>
<a class="print-manage" href="{{ route('templates.index') }}"><x-icon name="Plus" />Upload or manage templates</a>
<section class="panel"><h2>Report heading</h2><div class="form-grid">
<label>As of Date<input type="date" name="as_of" value="{{ ($asOf ?? today())->format('Y-m-d') }}" required><small>The quarter and year follow this date. Ages are calculated on this date, or at death if earlier. Membership filters use current saved records.</small></label>
@foreach(['association' => ['Name of Association', ($association ?? 'PUROK 7-C')], 'report_barangay' => ['Barangay', ($reportBarangay ?? 'Baliok')], 'report_district' => ['District', ($reportDistrict ?? 'Talomo')]] as $key => [$label, $value])
<label>{{ $label }}<input name="{{ $key }}" value="{{ $value }}" maxlength="100"></label>
@endforeach
<label>Date Organized / Reorganized<input type="date" name="organized_at" value="{{ ($organizedAt ?? '2023-09-09') }}"></label>
</div></section>
<section class="panel"><h2>Members to include</h2><div class="form-grid">
<label>Search by Name<input name="q" value="{{ request('q') }}" maxlength="255"></label>
<label>Status<select name="status"><option value="">All statuses</option>@foreach(['Active', 'Deceased', 'Archived'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></label>
<label>Purok<select name="location"><option value="">All puroks</option>@foreach($locations as $location)<option @selected(request('location') === $location)>{{ $location }}</option>@endforeach</select></label>
</div></section>
<section class="panel"><h2>Signatories</h2><div class="form-grid">
@foreach(['submitted_by' => ['Submitted by — Chapter President', ($submittedBy ?? '')], 'verified_by' => ['Verified by — Barangay Social Worker / DSI', ($verifiedBy ?? '')], 'noted_by' => ['Noted by — District Head', ($notedBy ?? '')]] as $key => [$label, $value])
<label>{{ $label }}<input name="{{ $key }}" value="{{ $value }}" maxlength="100"></label>
@endforeach
</div></section>
<div class="actions end"><button type="button" class="button outline" data-close-roster>Cancel</button><button class="button">Generate Report</button></div>
</form>

</dialog>
