{{-- Auto-injected $popularPosts via View::composer. Optional $class to restyle. --}}
<ul class="{{ $class ?? 'blog__popular-posts' }}">
    @foreach ($popularPosts as $post)
        <li class="blog__popular-posts-item">
            <a href="{{ $blogRoute('show', $post) }}">{{ $post->title }}</a>
            <span class="blog__count">{{ $post->comments_count }}</span>
        </li>
    @endforeach
</ul>
