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

## Customize

Change the target post types with the `wprs_target_post_types` filter.

```php
add_filter( 'wprs_target_post_types', function ( $post_types ) {
    $post_types[] = 'page';
    return $post_types;
} );
```

## Requirements

- PHP 7.0+ (uses `random_int()`)
