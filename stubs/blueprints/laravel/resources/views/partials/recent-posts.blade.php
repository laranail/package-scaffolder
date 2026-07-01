{{-- Auto-injected $recentPosts via View::composer. Optional $class to restyle. --}}
<ul class="{{ $class ?? 'blog__recent-posts' }}">
    @foreach ($recentPosts as $post)
        <li class="blog__recent-posts-item">
            <a href="{{ $blogRoute('show', $post) }}">{{ $post->title }}</a>
        </li>
    @endforeach
</ul>
