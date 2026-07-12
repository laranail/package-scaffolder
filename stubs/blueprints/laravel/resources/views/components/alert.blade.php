@props(['type' => 'info'])

<div {{ $attributes->merge(['class' => 'blog__alert blog__alert--'.$type, 'role' => 'status']) }}>
    {{ $slot }}
</div>
