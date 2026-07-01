{{--
    A classless (anonymous) component — no PHP class, resolved via the package's
    anonymous component path. Restylable: forwards class/id/style/attrs.
    Usage: <x-{prefix}::meta-item label="Reading time">5 min</x-{prefix}::meta-item>
--}}
@props(['label' => null])

<span {{ $attributes->merge(['class' => 'blog__meta-item']) }}>
    @if ($label)<span class="blog__meta-item-label">{{ $label }}:</span> @endif{{ $slot }}
</span>
