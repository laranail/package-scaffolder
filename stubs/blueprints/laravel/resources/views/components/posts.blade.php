<div {{ $attributes->merge(['class' => 'blog__posts']) }}>
    @forelse ($posts as $post)
        <x-dynamic-component :component="$blogComponentPrefix.'::post-card'" :post="$post" />
    @empty
        <p class="blog__empty">{{ __('modules/blog::blog.empty') }}</p>
    @endforelse

    @if ($posts instanceof \Illuminate\Contracts\Pagination\Paginator || $posts instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
        {{ $posts->links() }}
    @endif
</div>
