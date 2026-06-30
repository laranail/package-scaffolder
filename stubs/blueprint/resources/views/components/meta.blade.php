<title>{{ $title }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
@if ($url)
    <link rel="canonical" href="{{ $url }}">
@endif

<meta property="og:type" content="article">
<meta property="og:title" content="{{ $title }}">
@if ($description)<meta property="og:description" content="{{ $description }}">@endif
@if ($url)<meta property="og:url" content="{{ $url }}">@endif
@if ($image)<meta property="og:image" content="{{ $image }}">@endif

<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title }}">
@if ($description)<meta name="twitter:description" content="{{ $description }}">@endif

<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $title,
    'description' => $description,
    'image' => $image,
    'url' => $url,
    'datePublished' => $post->published_at?->toIso8601String(),
]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}
</script>
