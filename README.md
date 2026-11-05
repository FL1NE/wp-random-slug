# wp-random-slug

Auto slug generator for Wordpress.

Generate slug on first publish, format looks like below.

```
https://tomoki-shishikura.com/news/250420250403595/

250420250403595 = wprs_make_rand_str(4) + wp_date('Ymd') + $post_ID;
                  (random 4 digits) + (date) + (post ID)
```

## Behavior

- Applies only when the post has no slug (`post_name`) yet, i.e. on first publish.
  - A slug that was already saved (e.g. set manually while still a draft) is kept.
  - Published posts keep their slug, even if unpublished and published again.
- Drafts are never touched.
- Applies to `post` only by default.
- The date follows the WordPress timezone setting.
- Posts inserted via `wp_insert_post()` without an ID (e.g. some importers, `wp post create`) are skipped, because the post ID is part of the slug.

## Regenerate the slug of an existing post

To switch an already published post to the same format, use the **Regenerate slug** button.

- Block editor: "Random slug" panel in the Document sidebar.
- Classic editor: "Random slug" meta box in the side column.

The button only replaces the slug in the editor, it does not save. Click **Update** to save it. WordPress redirects the old URL to the new one. The button is shown for published, private and scheduled posts of the target post types (drafts get their slug when published).

The new slug comes from `POST /wp-json/wprs/v1/slug/<post id>` (requires permission to edit the post). It does not save anything.

## Customize

Change the target post types with the `wprs_target_post_types` filter.

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
