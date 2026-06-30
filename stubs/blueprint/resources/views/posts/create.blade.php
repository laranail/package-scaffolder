@extends($blogLayout)

@section('title', __('modules/blog::blog.new_post'))

@section('content')
    <h1>{{ __('modules/blog::blog.new_post') }}</h1>

    <form method="POST" action="{{ route('blog.store') }}">
        @include('modules/blog::posts.form', ['post' => null])

        <button type="submit">{{ __('modules/blog::blog.create') }}</button>
    </form>
@endsection
