<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($posts as $post)
    <url>
        <loc>{{ $blogRoute('show', $post) }}</loc>
        <lastmod>{{ ($post->updated_at ?? $post->published_at)?->toAtomString() }}</lastmod>
    </url>
    @endforeach
</urlset>
