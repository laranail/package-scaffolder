@foreach ($styles as $href)
    <link rel="stylesheet" href="{{ $href }}">
@endforeach
@foreach ($scripts as $src)
    <script type="module" src="{{ $src }}"></script>
@endforeach
