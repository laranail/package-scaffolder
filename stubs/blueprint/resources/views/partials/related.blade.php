{{-- $related passed in by the post component (context-dependent, not composer-backed). --}}
@if (isset($related) && $related->isNotEmpty())
    <section class="{{ $class ?? 'blog__related' }}">
        <h3 class="blog__related-title">{{ __('modules/blog::blog.related') }}</h3>
        <ul class="blog__related-list">
            @foreach ($related as $post)
                <li class="blog__related-item">
                    <a href="{{ $blogRoute('show', $post) }}">{{ $post->title }}</a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
