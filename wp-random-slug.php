<?php
/*
* Plugin Name: wp-random-slug
* Plugin URI: https://github.com/FL1NE/wp-random-slug/
* Description: Auto slug generator for Wordpress.
* Version: 0.0.1
* Author: Tomoki SHISHIKURA (FL1NE)
* Author URI: tomoki-shishikura.com
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: wp-random-slug
* Domain Path: /languages
*/
if(!defined('ABSPATH')) { exit; }


// Slugを自動生成する関数
function generateSlug($post_ID) {
    $slug = makeRandStr(4) . date('Ymd') . $post_ID;
    return $slug;
}


// 乱数生成関数
function makeRandStr($length = 8){
    static $chars = '0123456789';
    $str = '';
    for ($i = 0; $i < $length; ++$i) {
        $str .= $chars[mt_rand(0, 9)];
    }
    return $str;
}


/* Slugを自動設定するやつ */
function randomSlugApply($slug, $post_ID, $post_status, $post_type) {

    // 記事IDからを記事情報を取得
    $post = get_post($post_ID);

    // 初回の記事保存時にのみ、記事のSlugを記事IDに設定
    if ( $post_type == 'post' && $post->post_date_gmt == '0000-00-00 00:00:00' ) {
        $slug = generateSlug($post_ID);
        return $slug;
    }

    // 2回目以降は元のSlugを維持
    return $slug;
}

add_filter('wp_unique_post_slug', 'randomSlugApply', 10, 4);





