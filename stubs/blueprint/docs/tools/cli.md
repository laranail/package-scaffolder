# CLI

All commands are built on the `laranail/console` toolkit (rich tables, progress
bars, status lines and prompts) and delegate to the same Actions/manager as the
HTTP layer.

| Command | Description |
| --- | --- |
| `blog:install` | Interactive setup: publish config/assets, run migrations |
| `blog:stats` | Key/value summary of posts, comments and pending moderation |
| `blog:post:list [--status=] [--limit=]` | Table of posts |
| `blog:post:create [--title=] [--status=]` | Create a post (prompts for body) |
| `blog:post:publish {post}` | Publish a post by id or slug |
| `blog:post:unpublish {post}` | Revert a post to draft |
| `blog:post:delete {post} [--force]` | Delete (confirms first) |
| `blog:category:create [name]` | Create a category |
| `blog:category:list` | Table of categories with post counts |
| `blog:comment:list [--pending]` | Table of comments |
| `blog:comment:approve {comment} [--all]` | Approve one or all pending comments |
| `blog:publish-scheduled [--queue]` | Publish due scheduled posts (self-scheduled every minute) |

## Scheduling

The package self-registers `blog:publish-scheduled` every minute (configurable
via `modules.blog.scheduling`). Disable it and call it yourself:

```php
Schedule::command('blog:publish-scheduled')->everyFiveMinutes();
```
