<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('modules.blog.name', 'Blog') }}</title>
        <link>{{ url('/') }}</link>
        <description>{{ config('modules.blog.name', 'Blog') }}</description>
        <atom:link href="{{ $blogRoute('feed') }}" rel="self" type="application/rss+xml" />
        @foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ $blogRoute('show', $post) }}</link>
            <guid isPermaLink="true">{{ $blogRoute('show', $post) }}</guid>
            <pubDate>{{ $post->published_at?->toRssString() }}</pubDate>
            <description>{{ $post->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($post->body), 200) }}</description>
        </item>
        @endforeach
    </channel>
</rss>
