<article {{ $attributes->merge(['class' => 'blog__post-card']) }}>
    @if ($post->featured_image)
        <img class="blog__post-card-image" src="{{ $post->featured_image }}" alt="{{ $post->title }}">
    @endif

    <h2 class="blog__post-card-title">
        <a href="{{ $blogRoute('show', $post) }}">{{ $post->title }}</a>
    </h2>

    <p class="blog__meta">
        {{ $post->category?->name }}
        <x-dynamic-component :component="$blogComponentPrefix.'::status-badge'" :status="$post->status" />
        @if ($post->published_at)
            &middot; {{ $post->published_at->toFormattedDateString() }}
        @endif
        &middot; {{ $post->reading_time }} {{ __('modules/blog::blog.min_read') }}
    </p>

    <p class="blog__post-card-excerpt">{{ $post->excerpt }}</p>
</article>
