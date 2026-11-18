@props(['template'])
@if($template->created_at)
    Added <time datetime="{{ $template->created_at->toDateString() }}">{{ $template->created_at->format('M j, Y') }}</time>
@endif
