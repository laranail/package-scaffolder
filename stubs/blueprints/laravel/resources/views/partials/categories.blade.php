{{-- Auto-injected $categories via View::composer. Optional $class to restyle. --}}
<ul class="{{ $class ?? 'blog__categories' }}">
    @foreach ($categories as $category)
        <li class="blog__categories-item">
            <a href="{{ $blogRoute('index', ['category' => $category->slug]) }}">
                {{ $category->name }}
                <span class="blog__count">{{ $category->posts_count }}</span>
            </a>
        </li>
    @endforeach
</ul>
