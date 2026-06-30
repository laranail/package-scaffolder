<section {{ $attributes->merge(['class' => 'blog__comments']) }}>
    <h3 class="blog__comments-title">{{ __('modules/blog::blog.comments') }}</h3>

    @forelse ($comments as $comment)
        <div class="blog__comment">
            <strong class="blog__comment-author">{{ $comment->author_name }}</strong>
            <p class="blog__comment-body">{{ $comment->body }}</p>
        </div>
    @empty
        <p class="blog__comments-empty">{{ __('modules/blog::blog.no_comments') }}</p>
    @endforelse

    @if ($comments instanceof \Illuminate\Contracts\Pagination\Paginator)
        {{ $comments->links() }}
    @endif
</section>
