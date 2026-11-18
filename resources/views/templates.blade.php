@extends('layouts.app')
@section('title', 'Print templates')
@section('content')
<header class="page-header">
    <div><x-back-link :href="route('settings')">Back to Settings</x-back-link><h1>Print templates</h1><p>Saved templates are available to all signed-in users.</p></div>
</header>
<section class="panel stack">
    <h2>Prepare your template</h2>
    <p>Upload Word (.docx), Excel (.xlsx), or PDF files up to 2 MB. Save older .doc and .xls files in the newer formats first.</p>
    <p>For Word, place <code>${member_name}</code> in a table row. For Excel, place it once in an unmerged row. That row repeats for each matching member. Keep formulas outside the repeating Excel row.</p>
    <p>Available row fields: <code>${number}</code>, <code>${member_name}</code>, <code>${age}</code>, <code>${gender}</code>, <code>${location}</code>, <code>${status}</code>, <code>${birth_date}</code>, <code>${amount_paid}</code>, <code>${balance}</code>, <code>${paid_at}</code>.</p>
    <p>Additional roster row fields: <code>${first_name}</code>, <code>${middle_name}</code>, <code>${last_name}</code>, <code>${name_extension}</code>, <code>${purok}</code>, <code>${barangay}</code>, <code>${district}</code>, <code>${senior_citizen_id}</code>, <code>${id_issued_at}</code>, <code>${mobile}</code>.</p>
    <p>Report fields: <code>${report_title}</code>, <code>${report_date}</code>, <code>${total_members}</code>, <code>${event_name}</code>, <code>${event_date}</code>. Payment and event fields are blank on member and census reports.</p>
    <p>For a completed member roster PDF (including multiple pages), choose Roster reference. Reports use the built-in senior citizen roster layout and the two saved logos, with current saved members; the uploaded names and data are never imported or printed. This does not automatically reproduce arbitrary PDF layouts. For a blank one-page PDF, choose Letterhead; margins control the table placement.</p>
</section>
<form class="panel stack" method="post" action="{{ route('templates.store') }}" enctype="multipart/form-data">
    @csrf
    <h2>Upload template</h2>
    <label>Template name<input name="name" value="{{ old('name') }}" maxlength="100" required></label>
    <label>Report category<select name="category" required>
        <option value="">Choose a category</option>
        @foreach(\App\Models\PrintTemplate::CATEGORIES as $value => $label)
            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
        @endforeach
    </select></label>
    <label>Template file<input type="file" name="file" accept=".docx,.xlsx,.pdf" required></label>
    <label>PDF layout<select name="pdf_layout">
        <option value="roster" @selected(old('pdf_layout', 'roster') === 'roster')>Roster reference — Members only, one or more pages</option>
        <option value="letterhead" @selected(old('pdf_layout') === 'letterhead')>Blank one-page letterhead</option>
    </select><small>Applies to PDFs only. Roster references use the built-in layout; margins below apply only to letterheads.</small></label>
    <fieldset class="stack"><legend>Letterhead PDF margins (mm)</legend>
        <label>Top<input type="number" name="pdf_top" value="{{ old('pdf_top', 45) }}" min="5" max="200" required></label>
        <label>Bottom<input type="number" name="pdf_bottom" value="{{ old('pdf_bottom', 20) }}" min="5" max="100" required></label>
        <label>Left and right<input type="number" name="pdf_margin" value="{{ old('pdf_margin', 15) }}" min="5" max="50" required></label>
    </fieldset>
    <div class="actions"><button class="button">Save template</button></div>
</form>
@forelse($templates->groupBy('category') as $category => $categoryTemplates)
<h2>{{ \App\Models\PrintTemplate::CATEGORIES[$category] ?? 'Uncategorized — choose a category to enable printing' }}</h2>
<div class="template-card-grid">
@foreach($categoryTemplates as $template)
<section class="panel template-card">
    <div class="template-card-info">
        <span class="template-file-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h8l5 5v15H6zM14 2v6h5M9 12h7m-7 4h5"/></svg></span>
        <div class="template-card-copy">
            <header class="template-card-header">
                <h3>{{ $template->name }}</h3>
                <span class="template-format">{{ strtoupper($template->format) }}</span>
            </header>
            @if($template->usesRosterLayout())<p>Uses the senior citizen roster layout.</p>@endif
            @if($template->created_at)
                <p class="template-card-date"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span><x-template-added :template="$template" /></span></p>
            @endif
        </div>
    </div>
    <div class="actions template-card-actions">
        @php($previewLabel = $template->format === 'pdf' ? 'Preview sample' : 'Download sample')
        <a class="button template-action" href="{{ route('templates.preview', $template) }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>{{ $previewLabel }}</a>
        <a class="button outline template-action" href="{{ route('templates.download', $template) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M4 16v4h16v-4"/></svg>Download original</a>
        <button type="button" class="button outline template-action" data-template-edit aria-controls="template-edit-{{ $template->id }}" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16-1 5 5-1L21 7l-4-4ZM14 6l4 4"/></svg>Edit</button>
        <form method="post" action="{{ route('templates.destroy', $template) }}" data-confirm="Delete this saved template?">
            @csrf @method('DELETE')
            <button class="button outline template-action template-delete"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M9 6V3h6v3M6 6l1 15h10l1-15"/></svg>Delete</button>
        </form>
    </div>
    <form id="template-edit-{{ $template->id }}" class="stack template-edit" method="post" action="{{ route('templates.update', $template) }}" hidden>
            @csrf @method('PUT')
            <label>Template name<input name="name" value="{{ $template->name }}" maxlength="100" required></label>
            <label>Report category<select name="category" required>
                <option value="">Choose a category</option>
                @foreach(\App\Models\PrintTemplate::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected($template->category === $value)>{{ $label }}</option>
                @endforeach
            </select></label>
            @if($template->format === 'pdf')
            <label>PDF layout<select name="pdf_layout"><option value="roster" @selected($template->pdf_layout === 'roster')>Roster reference — Members only</option><option value="letterhead" @selected($template->pdf_layout !== 'roster')>Blank one-page letterhead</option></select></label>
            @endif
            @foreach(['pdf_top' => 'Top', 'pdf_bottom' => 'Bottom', 'pdf_margin' => 'Left and right'] as $field => $label)
                <label @if($template->format !== 'pdf') hidden @endif>{{ $label }} (mm)<input type="number" name="{{ $field }}" value="{{ $template->$field }}" min="5" max="{{ ['pdf_top' => 200, 'pdf_bottom' => 100, 'pdf_margin' => 50][$field] }}" required></label>
            @endforeach
            <div class="actions"><button class="button">Save changes</button></div>
    </form>
</section>
@endforeach
</div>
@empty
<p>No custom templates saved yet. Default printing is available from each report.</p>
@endforelse
@endsection
