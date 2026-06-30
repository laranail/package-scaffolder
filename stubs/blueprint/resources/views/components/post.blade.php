<article {{ $attributes->merge(['class' => 'blog__post']) }}>
    @if ($post->featured_image)
        <img class="blog__post-image" src="{{ $post->featured_image }}" alt="{{ $post->title }}">
    @endif

    <h1 class="blog__post-title">{{ $post->title }}</h1>

    <p class="blog__meta">
        {{ $post->category?->name }}
        <x-dynamic-component :component="$blogComponentPrefix.'::status-badge'" :status="$post->status" />
        @if ($post->published_at)
            &middot; {{ $post->published_at->toFormattedDateString() }}
        @endif
        &middot; {{ $post->reading_time }} {{ __('modules/blog::blog.min_read') }}
    </p>

    {{-- Body is sanitized to an allow-list on save and rendered as HTML here;
         Markdown is rendered on display when config('modules.blog.processing.markdown') is on. --}}
    <div class="blog__post-body">
        {!! $post->rendered_body !!}
    </div>

    @if ($post->relationLoaded('tags') ? $post->tags->isNotEmpty() : $post->tags()->exists())
        <ul class="blog__post-tags">
            @foreach ($post->tags as $tag)
                <li><a href="{{ $blogRoute('index', ['tag' => $tag->slug]) }}">#{{ $tag->name }}</a></li>
            @endforeach
        </ul>
    @endif

    @include('modules/blog::partials.related', ['related' => $related])

    <x-dynamic-component :component="$blogComponentPrefix.'::comments'" :post="$post" />
    <x-dynamic-component :component="$blogComponentPrefix.'::comment-form'" :post="$post" />
</article>
