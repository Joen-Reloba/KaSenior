@if(!request()->boolean('print'))
<div class="pagination">
    <p>Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }}</p>
    <div class="actions">
        @if($rows->previousPageUrl())<a class="button small outline" href="{{ $rows->previousPageUrl() }}">‹ Previous</a>@endif
        @if($rows->nextPageUrl())<a class="button small" href="{{ $rows->nextPageUrl() }}">Next ›</a>@endif
    </div>
</div>
@endif
