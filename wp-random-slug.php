<?php
/*
* Plugin Name: wp-random-slug
* Plugin URI: https://github.com/FL1NE/wp-random-slug/
* Description: Auto slug generator for Wordpress.
* Version: 0.0.2
* Requires at least: 5.3
* Requires PHP: 7.0
* Author: Tomoki SHISHIKURA (FL1NE)
* Author URI: https://tomoki-shishikura.com
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: wp-random-slug
*/
if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * 乱数文字列を生成する(暗号論的に安全な乱数を使用)
 *
 * @param int $length 桁数
 * @return string
 */
function wprs_make_rand_str( $length = 4 ) {
    $chars = '0123456789';
    $max   = strlen( $chars ) - 1;
    $str   = '';
    for ( $i = 0; $i < $length; ++$i ) {
        $str .= $chars[ random_int( 0, $max ) ];
    }
    return $str;
}


/**
 * Slugを生成する: 乱数4桁 + 日付(Ymd) + 記事ID
 * 日付はPHPの設定ではなく、WordPressのタイムゾーン設定に従う
 *
 * @param int $post_ID 記事ID
 * @return string
 */
function wprs_generate_slug( $post_ID ) {
    return wprs_make_rand_str( 4 ) . wp_date( 'Ymd' ) . $post_ID;
}


/**
 * Slugを自動設定する(wp_unique_post_slug フィルタ)
 *
 * 記事にまだSlug(post_name)が無い場合にだけランダムSlugを設定する。
 * 下書き中にSlugが保存されていた場合や、公開済みの記事は元のSlugを維持する。
 *
 * @param string $slug        WordPressが決定したSlug
 * @param int    $post_ID     記事ID(wp_insert_post()で新規挿入する場合は0)
 * @param string $post_status 記事ステータス
 * @param string $post_type   投稿タイプ
 * @return string
 */
function wprs_apply_random_slug( $slug, $post_ID, $post_status, $post_type ) {

    // 記事IDが無い場合は、IDを含むSlugを作れず、判定もできないので何もしない
    if ( empty( $post_ID ) ) {
        return $slug;
    }

    // 対象の投稿タイプ以外は何もしない(フィルタで変更可能)
    $target_post_types = (array) apply_filters( 'wprs_target_post_types', array( 'post' ) );
    if ( ! in_array( $post_type, $target_post_types, true ) ) {
        return $slug;
    }

    // DBに保存されている記事を取得(グローバルの $post を参照しないよう、IDは必ず渡す)
    $post = get_post( $post_ID );
    if ( ! $post ) {
        return $slug;
    }

    // すでにSlugがある場合(公開済み・下書き中に設定済み)は維持
    if ( '' !== $post->post_name ) {
        return $slug;
    }

    return wprs_generate_slug( $post_ID );
}

add_filter( 'wp_unique_post_slug', 'wprs_apply_random_slug', 10, 4 );
