<fieldset class="stack" data-census-fields aria-label="Chapter data sheet">
<section class="panel">
<h2>Chapter details</h2>
<div class="census-print-grid">
@foreach(['chapter' => ['Name of Chapter', 'Kalmado Senior Citizen Association - Purok 7C'], 'president' => ['President', ''], 'chapter_address' => ['Chapter Address', '']] as $key => [$label, $default])
<label @class(['census-print-wide' => $key === 'chapter'])>{{ $label }}<input name="{{ $key }}" value="{{ old($key, $default) }}" maxlength="{{ $key === 'chapter_address' ? 255 : ($key === 'chapter' ? 150 : 100) }}"></label>
@endforeach
</div>
</section>
<section class="panel">
<h2>Pension totals</h2>
<div class="census-print-grid">
<label>Total Social Pensioners<input type="number" name="pensioners" min="0" step="1" value="{{ old('pensioners') }}"></label>
<label>Total Waitlisted<input type="number" name="waitlisted" min="0" step="1" value="{{ old('waitlisted') }}"></label>
<small>Enter totals for the selected members. Leave unknown totals blank.</small>
</div>
</section>
<section class="panel">
<h2>Signatory</h2>
<div class="census-print-grid">
<label>Submitted by<input name="submitted_by" value="{{ old('submitted_by') }}" maxlength="100"></label>
<label>Position<input name="position" value="{{ old('position', 'President') }}" maxlength="100"></label>
</div>
</section>
</fieldset>
