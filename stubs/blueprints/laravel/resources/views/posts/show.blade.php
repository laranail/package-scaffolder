@extends($blogLayout)

@section('title', $post->meta_title ?: $post->title)

@push('head')
    <x-dynamic-component :component="$blogComponentPrefix.'::meta'" :post="$post" />
@endpush

@section('content')
    @can('update', $post)
        <a class="blog__edit-link" href="{{ $blogRoute('edit', $post) }}">{{ __('modules/blog::blog.edit') }}</a>
    @endcan

    {{-- The single source of post markup — also embeddable in a host layout. --}}
    <x-dynamic-component :component="$blogComponentPrefix.'::post'" :post="$post" />
@endsection
