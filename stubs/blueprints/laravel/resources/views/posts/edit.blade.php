@extends($blogLayout)

@section('title', __('modules/blog::blog.edit'))

@section('content')
    <h1>{{ __('modules/blog::blog.edit') }}</h1>

    <form method="POST" action="{{ route('blog.update', $post) }}">
        @method('PUT')
        @include('modules/blog::posts.form', ['post' => $post])

        <button type="submit">{{ __('modules/blog::blog.update') }}</button>
    </form>
@endsection
