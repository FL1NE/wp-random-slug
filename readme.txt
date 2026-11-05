=== wp-random-slug ===
Tags: slug, permalink, random
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto slug generator for WordPress.

== Description ==

Sets a generated slug as soon as a post is created, so the slug you see in the editor is the one that gets published.

Format: random 4 digits + date (Ymd) + post ID, e.g. `250420250403595`. The date is the day the post was created.

* Applies only when the post has no slug yet. Existing slugs are kept.
* A post that is published without a slug gets one on first publish.
* Choose the target post types (including custom post types such as `news`) in Settings > Random Slug. Default: `post` only. The `wprs_target_post_types` filter is applied on top of the setting.
* "Regenerate slug" button in the post editor (block editor and classic editor) to switch a post to the same format, including drafts. It only replaces the slug in the editor; save the post to apply it. For a published post, the old URL redirects to the new one.

== Changelog ==

= 0.1.0 =
* Add: the random slug is now set as soon as a post is created (the draft made by "Add New", or a draft saved without a slug). The slug shown in the editor stays the same when saving and publishing. The date in the slug is the creation date.
* Add: the "Regenerate slug" button is also shown for drafts and pending posts (not for brand-new unsaved posts).
* Fix: in the block editor, saving a draft after "Regenerate slug" replaced the chosen slug with another random one. The slug chosen in the editor is now kept.

= 0.0.4 =
* Add: Settings > Random Slug page to choose the target post types, including custom post types (e.g. `news`).
* Fix: when a generated slug is saved under `/%postname%/`, it is no longer used if another post already has the same slug (duplicate slugs).

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
