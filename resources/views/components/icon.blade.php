@props(['name', 'screen' => 2])
<img src="{{ asset('icons/'.$screen.'-imgIcon'.$name.'.svg') }}" alt="" {{ $attributes->class('icon') }}>
