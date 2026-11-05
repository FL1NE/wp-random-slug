# wp-random-slug

Auto slug generator for Wordpress.

The slug is generated as soon as a post is created (the slug you see in the editor is the final one), format looks like below.

```
https://tomoki-shishikura.com/news/250420250403595/

250420250403595 = wprs_make_rand_str(4) + wp_date('Ymd') + $post_ID;
                  (random 4 digits) + (date) + (post ID)
```

## Behavior

- When a post is created (the draft that "Add New" makes, or a draft saved without a slug), the random slug is saved right away. The date is the day the post was created. It stays the same when the post is saved again and when it is published.
- Applies only when the post has no slug (`post_name`) yet.
  - A slug that was already saved (e.g. set manually) is kept.
  - Published posts keep their slug, even if unpublished and published again.
- A post that reaches publish without a slug (e.g. created by an importer) gets the random slug on first publish.
- Applies to `post` only by default. Choose the post types in **Settings > Random Slug** (custom post types such as `news` are listed too).
- The date follows the WordPress timezone setting.
- Posts inserted via `wp_insert_post()` without an ID (e.g. some importers, `wp post create`) are skipped, because the post ID is part of the slug.

## Regenerate the slug

To switch a post (published or draft) to the same format, use the **Regenerate slug** button.

- Block editor: "Random slug" panel in the Document sidebar.
- Classic editor: "Random slug" meta box in the side column.

The button only replaces the slug in the editor, it does not save. Save the post to apply it (**Update** for a published post, **Save draft** for a draft). For a published post, WordPress redirects the old URL to the new one. The button is shown for every saved post of the target post types, including drafts. It is not shown for a brand-new post that has not been saved yet.

The new slug comes from `POST /wp-json/wprs/v1/slug/<post id>` (requires permission to edit the post). It does not save anything.

## Customize

The target post types are chosen in **Settings > Random Slug** (default: `post` only). To add post types from code, use the `wprs_target_post_types` filter. It is applied on top of the setting.

```php
add_filter( 'wprs_target_post_types', function ( $post_types ) {
    $post_types[] = 'page';
    return $post_types;
} );
```

## Tests

Integration tests run against a real WordPress with the plugin active (they create and delete their own posts and users).

```
wp eval-file wp-content/plugins/wp-random-slug/tests/integration-test.php
```

## Requirements

- WordPress 5.3+ (uses `wp_date()`)
- PHP 7.0+ (uses `random_int()`)
