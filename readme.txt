=== wp-random-slug ===
Tags: slug, permalink, random
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 0.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto slug generator for WordPress.

== Description ==

Sets a generated slug when a post is first published.

Format: random 4 digits + date (Ymd) + post ID, e.g. `250420250403595`.

* Applies only when the post has no slug yet. Existing slugs are kept.
* Applies to `post` only by default. Use the `wprs_target_post_types` filter to change it.
* "Regenerate slug" button in the post editor (block editor and classic editor) to switch an existing post to the same format. It only replaces the slug in the editor; click "Update" to save. The old URL redirects to the new one.

== Changelog ==

= 0.0.3 =
* Add: "Regenerate slug" button for published posts. Block editor: "Random slug" panel in the Document sidebar. Classic editor: "Random slug" meta box.
* Add: REST endpoint `POST /wprs/v1/slug/<post id>` that returns a new slug without saving it (requires permission to edit the post).
* Fix: a slug made for the post is no longer changed to `...-2` by WordPress when the permalink structure is `/%postname%/`.
* Add: integration tests (`tests/integration-test.php`, run with `wp eval-file`).

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
