=== wp-random-slug ===
Tags: slug, permalink, random
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 0.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto slug generator for WordPress.

== Description ==

Sets a generated slug when a post is first published.

Format: random 4 digits + date (Ymd) + post ID, e.g. `250420250403595`.

* Applies only when the post has no slug yet. Existing slugs are kept.
* Applies to `post` only by default. Use the `wprs_target_post_types` filter to change it.

== Changelog ==

= 0.0.2 =
* Fix: skip when the post ID is 0 or the post cannot be loaded (avoids reading the global `$post` and PHP warnings).
* Fix: detect "first publish" by an empty `post_name` instead of `post_date_gmt`, so drafts with a manually set date are handled.
* Change: prefix all functions with `wprs_` to avoid name collisions.
* Change: use `random_int()` instead of `mt_rand()`.
* Change: use `wp_date()` so the WordPress timezone is respected.
* Add: `wprs_target_post_types` filter.
* Remove: unused `Domain Path` header.

= 0.0.1 =
* Initial release.
