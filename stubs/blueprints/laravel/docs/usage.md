# Usage

## The Blog manager / facade

```php
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Facades\Blog as BlogFacade;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;

$blog = app(Blog::class);

// Create (author is set explicitly, never from user input)
$post = $blog->create(
    PostData::fromArray(['title' => 'Hello', 'body' => '…', 'status' => 'published'])
        ->withAuthor($userId)
);

// Read
BlogFacade::feed();                                   // published, paginated
BlogFacade::search(['search' => 'laravel', 'sort' => 'title', 'direction' => 'asc']);
BlogFacade::findBySlug('hello');

// Widgets
BlogFacade::related($post);  BlogFacade::recentPosts();  BlogFacade::popularPosts();
BlogFacade::featured();                              // pinned posts
BlogFacade::recordView($post);                       // atomic view counter

// Lifecycle
BlogFacade::publish($post);
BlogFacade::unpublish($post);
BlogFacade::delete($post);     // soft delete
BlogFacade::restore($post);
BlogFacade::forceDelete($post);

// Dashboard counts
BlogFacade::stats();           // ['posts' => …, 'published' => …, …]
```

## Authorization

```php
$user->can('update', $post);              // PostPolicy::update (owner/admin)
$user->can('publish', $post);             // PostPolicy::publish (owner/admin)
Gate::allows('blog.moderate-comments');   // standalone moderation gate
```

## Events

```php
use Some\NamespacePath\Blog\Events\PostPublished;

Event::listen(PostPublished::class, function (PostPublished $event) {
    // $event->post just went live
});
```

`PostPublished` is dispatched exactly once per transition (by the observer),
whether a post is created already-published or published later. A full set of lifecycle
events (post/comment/category/tag created/updated/deleted, plus the
published/unpublished/approved transitions) fires for every writer — see the events table in
[extending.md](tools/extending.md), which also covers macros, the body pipeline, decoration and
swappable search drivers.
