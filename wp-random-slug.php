<?php
/*
* Plugin Name: wp-random-slug
* Plugin URI: https://github.com/FL1NE/wp-random-slug/
* Description: Auto slug generator for Wordpress.
* Version: 0.1.0
* Requires at least: 5.3
* Requires PHP: 7.0
* Author: Tomoki SHISHIKURA (FL1NE)
* Author URI: https://tomoki-shishikura.com
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: wp-random-slug
*/
if ( ! defined( 'ABSPATH' ) ) { exit; }


define( 'WPRS_VERSION', '0.1.0' );


/**
 * Slugを自動設定する対象の投稿タイプ
 * 設定ページで選んだ値(未設定なら post のみ)に、wprs_target_post_types フィルタを重ねて適用する
 *
 * @return string[]
 */
function wprs_target_post_types() {
    return (array) apply_filters( 'wprs_target_post_types', (array) get_option( 'wprs_post_types', array( 'post' ) ) );
}


/**
 * 設定ページで選べる投稿タイプ(公開されているもの。添付ファイルは除く)
 *
 * @return WP_Post_Type[] スラッグをキーにした配列
 */
function wprs_selectable_post_types() {
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    unset( $post_types['attachment'] );
    return $post_types;
}


/**
 * 設定値の検証: 選べる投稿タイプだけを残す。チェックが1つも無い場合(未送信)は空にする
 *
 * @param mixed $value 送信された値
 * @return string[]
 */
function wprs_sanitize_post_types( $value ) {
    return array_values( array_intersect( (array) $value, array_keys( wprs_selectable_post_types() ) ) );
}


/**
 * 管理画面で「Slugを再生成」ボタンを出す記事ステータスか
 * 下書きにも出す(公開前にSlugを揃えられる)。まだ保存されていない新規記事(auto-draft)とゴミ箱には出さない
 *
 * @param string $status 記事ステータス
 * @return bool
 */
function wprs_is_regenerable_status( $status ) {
    return ! in_array( $status, array( 'auto-draft', 'trash' ), true );
}


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
 * @param int    $post_parent 親記事のID(未使用)
 * @param string $original_slug WordPressが重複回避を行う前のSlug
 * @return string
 */
function wprs_apply_random_slug( $slug, $post_ID, $post_status, $post_type, $post_parent = 0, $original_slug = '' ) {

    // 記事IDが無い場合は、IDを含むSlugを作れず、判定もできないので何もしない
    if ( empty( $post_ID ) ) {
        return $slug;
    }

    // 対象の投稿タイプ以外は何もしない
    if ( ! in_array( $post_type, wprs_target_post_types(), true ) ) {
        return $slug;
    }

    // この記事用に作ったSlug(乱数4桁 + 日付 + この記事のID)が渡された場合(エディターのボタンで作ったもの)は、そのまま使う。
    // - WordPressは数字だけのSlugを、パーマリンクが /%postname%/ のとき日付アーカイブとの衝突を避けるため "-2" に変えてしまう。
    // - ブロックエディターは下書きの保存でも、重複確認のため publish として呼ぶ。このとき作り直さず、選んだSlugを守る。
    // 外すのは日付アーカイブとの衝突回避だけ。別の記事がすでに使っているSlug(手入力・インポート済みなど)は重複回避を通す。
    if ( 1 === preg_match( '/^\d{12}' . (int) $post_ID . '$/', (string) $original_slug ) ) {
        global $wpdb;
        // WordPress本体の wp_unique_post_slug() と同じ検索。結果をキャッシュすると重複を見逃すので、直接問い合わせる。
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $used_by_other = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 1",
                $original_slug,
                $post_type,
                $post_ID
            )
        );
        return $used_by_other ? $slug : $original_slug;
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

add_filter( 'wp_unique_post_slug', 'wprs_apply_random_slug', 10, 6 );


/**
 * 記事の作成時に、既定のSlugとしてランダムSlugを保存する(save_post)
 *
 * 「新規追加」で作られる自動下書きや、下書きのSlugが空なら、その場でランダムSlugを入れる。
 * エディターに最初から表示されるSlugが、そのまま公開後のURLになる(日付は作成日)。
 * すでにSlugがある記事、対象外の投稿タイプ、公開済みの記事は何もしない。
 *
 * @param int     $post_ID 記事ID
 * @param WP_Post $post    記事
 */
function wprs_set_default_slug( $post_ID, $post ) {
    if ( ! in_array( $post->post_type, wprs_target_post_types(), true ) ) {
        return;
    }
    if ( ! in_array( $post->post_status, array( 'auto-draft', 'draft', 'pending' ), true ) || '' !== $post->post_name ) {
        return;
    }
    global $wpdb;
    // save_post の中なので wp_update_post() は使わず(再帰と余計なリビジョンを避ける)、直接書き込む。
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->update( $wpdb->posts, array( 'post_name' => wprs_generate_slug( $post_ID ) ), array( 'ID' => $post_ID ) );
    clean_post_cache( $post_ID );
}

add_action( 'save_post', 'wprs_set_default_slug', 10, 2 );


/* ---------------------------------------------------------------------------
 * 管理画面: 設定ページ(設定 > Random Slug)で、対象の投稿タイプを選ぶ
 * ------------------------------------------------------------------------- */

function wprs_register_settings() {
    register_setting(
        'wprs',
        'wprs_post_types',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'wprs_sanitize_post_types',
            'default'           => array( 'post' ),
        )
    );
}
add_action( 'admin_init', 'wprs_register_settings' );


function wprs_add_settings_page() {
    add_options_page(
        __( 'Random Slug', 'wp-random-slug' ),
        __( 'Random Slug', 'wp-random-slug' ),
        'manage_options',
        'wprs-settings',
        'wprs_render_settings_page'
    );
}
add_action( 'admin_menu', 'wprs_add_settings_page' );


function wprs_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $selected = (array) get_option( 'wprs_post_types', array( 'post' ) );

    echo '<div class="wrap"><h1>' . esc_html__( 'Random Slug', 'wp-random-slug' ) . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'wprs' );
    echo '<h2 class="title">' . esc_html__( 'Target post types', 'wp-random-slug' ) . '</h2>';
    echo '<p>' . esc_html__( 'A random slug is set when a post of the selected types is first published. The "Regenerate slug" button is shown for these types as well.', 'wp-random-slug' ) . '</p>';
    echo '<fieldset>';
    foreach ( wprs_selectable_post_types() as $slug => $post_type ) {
        printf(
            '<label><input type="checkbox" name="wprs_post_types[]" value="%1$s" %2$s> %3$s <code>%1$s</code></label><br>',
            esc_attr( $slug ),
            checked( in_array( $slug, $selected, true ), true, false ),
            esc_html( $post_type->labels->name )
        );
    }
    echo '</fieldset>';
    submit_button();
    echo '</form></div>';
}


/* ---------------------------------------------------------------------------
 * 管理画面: 「Slugを再生成」ボタン
 *
 * 新しいSlugを作って編集欄に入れるだけで、保存はしない(「更新」で反映)。
 * 保存時は、記事にSlugがあるので渡されたSlugがそのまま使われる。
 * ------------------------------------------------------------------------- */

/**
 * 新しいSlugを返すREST API(保存はしない)
 */
function wprs_register_rest_route() {
    register_rest_route(
        'wprs/v1',
        '/slug/(?P<id>\d+)',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'wprs_rest_generate_slug',
            'permission_callback' => 'wprs_rest_permission',
            'args'                => array(
                'id' => array( 'sanitize_callback' => 'absint' ),
            ),
        )
    );
}
add_action( 'rest_api_init', 'wprs_register_rest_route' );


/**
 * 権限チェック: 編集権限のあるユーザーだけ。存在確認は権限確認のあとに行う
 * (ログインしていない人に、記事IDの存在を知らせないため)
 *
 * @param WP_REST_Request $request
 * @return bool|WP_Error
 */
function wprs_rest_permission( $request ) {
    if ( ! current_user_can( 'edit_posts' ) ) {
        return false;
    }
    $post = get_post( $request['id'] );
    if ( ! $post ) {
        return new WP_Error( 'wprs_post_not_found', __( 'Post not found.', 'wp-random-slug' ), array( 'status' => 404 ) );
    }
    return current_user_can( 'edit_post', $post->ID );
}


/**
 * @param WP_REST_Request $request
 * @return array|WP_Error
 */
function wprs_rest_generate_slug( $request ) {
    $post = get_post( $request['id'] );
    if ( ! in_array( $post->post_type, wprs_target_post_types(), true ) ) {
        return new WP_Error( 'wprs_invalid_post_type', __( 'This post type is not a target of wp-random-slug.', 'wp-random-slug' ), array( 'status' => 400 ) );
    }
    return array( 'slug' => wprs_generate_slug( $post->ID ) );
}


/**
 * ブロックエディター: サイドバーのパネル用スクリプト
 */
function wprs_enqueue_block_editor_assets() {
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, wprs_target_post_types(), true ) ) {
        return;
    }
    wp_enqueue_script(
        'wprs-editor',
        plugins_url( 'assets/editor.js', __FILE__ ),
        array( 'wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-i18n' ),
        WPRS_VERSION,
        true
    );
    wp_set_script_translations( 'wprs-editor', 'wp-random-slug' );
}
add_action( 'enqueue_block_editor_assets', 'wprs_enqueue_block_editor_assets' );


/**
 * クラシックエディター: メタボックス(ブロックエディターには出さない)
 *
 * @param string  $post_type 投稿タイプ
 * @param WP_Post $post      記事
 */
function wprs_add_meta_box( $post_type, $post ) {
    if ( ! in_array( $post_type, wprs_target_post_types(), true ) || ! wprs_is_regenerable_status( $post->post_status ) ) {
        return;
    }
    add_meta_box(
        'wprs_regenerate',
        __( 'Random slug', 'wp-random-slug' ),
        'wprs_render_meta_box',
        $post_type,
        'side',
        'default',
        array( '__back_compat_meta_box' => true )
    );
}
add_action( 'add_meta_boxes', 'wprs_add_meta_box', 10, 2 );


function wprs_render_meta_box() {
    echo '<p><button type="button" class="button" id="wprs-regenerate">' . esc_html__( 'Regenerate slug', 'wp-random-slug' ) . '</button></p>';
    echo '<p class="description">' . esc_html__( 'Replaces the slug field. Save the post to apply it.', 'wp-random-slug' ) . '</p>';
}


/**
 * クラシックエディター: 記事編集画面でボタン用スクリプトを読み込む
 *
 * @param string $hook_suffix 管理画面のフック名
 */
function wprs_enqueue_classic_assets( $hook_suffix ) {
    if ( 'post.php' !== $hook_suffix ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || $screen->is_block_editor() || ! in_array( $screen->post_type, wprs_target_post_types(), true ) ) {
        return;
    }
    wp_enqueue_script(
        'wprs-classic',
        plugins_url( 'assets/classic.js', __FILE__ ),
        array( 'jquery', 'wp-api-fetch', 'wp-i18n' ),
        WPRS_VERSION,
        true
    );
    wp_set_script_translations( 'wprs-classic', 'wp-random-slug' );
}
add_action( 'admin_enqueue_scripts', 'wprs_enqueue_classic_assets' );
