@extends($blogLayout)

@section('title', __('modules/blog::blog.posts'))

@section('content')
    <header class="blog__header">
        <h1>{{ __('modules/blog::blog.posts') }}</h1>
        @can('create', \Some\NamespacePath\Blog\Models\Post::class)
            <a href="{{ $blogRoute('create') }}">{{ __('modules/blog::blog.new_post') }}</a>
        @endcan
    </header>

    {{-- The single source of list markup — also embeddable in a host layout. --}}
    <x-dynamic-component :component="$blogComponentPrefix.'::posts'" :posts="$posts" />
@endsection
