<?php
/**
 * Integration tests: run against a real WordPress with the plugin active.
 *
 *   wp eval-file wp-content/plugins/wp-random-slug/tests/integration-test.php
 *
 * Exit code is 1 when any check fails. Creates and deletes its own posts and users.
 */
if ( ! defined( 'WP_CLI' ) ) {
    exit( "Run with: wp eval-file tests/integration-test.php\n" );
}

require_once ABSPATH . 'wp-admin/includes/admin.php';

// wp eval-file runs this file inside a function, so bind to the globals the helpers use.
global $failures, $cleanup;
$failures = 0;
$cleanup  = array( 'posts' => array(), 'users' => array() );

function t_check( $label, $condition, $detail = '' ) {
    global $failures;
    echo ( $condition ? 'PASS' : 'FAIL' ) . "  $label" . ( ! $condition && $detail ? "  [$detail]" : '' ) . "\n";
    if ( ! $condition ) {
        ++$failures;
    }
}

function t_post( $status, $type = 'post' ) {
    global $cleanup;
    $id = wp_insert_post( array( 'post_title' => 'Test ' . wp_generate_password( 6, false ), 'post_status' => $status, 'post_type' => $type ) );
    $cleanup['posts'][] = $id;
    return $id;
}

function t_user( $role ) {
    global $cleanup;
    $id = wp_insert_user( array( 'user_login' => 'wprs_' . $role . wp_generate_password( 5, false ), 'user_pass' => wp_generate_password(), 'role' => $role ) );
    $cleanup['users'][] = $id;
    return $id;
}

function t_request( $post_id ) {
    $response = rest_do_request( new WP_REST_Request( 'POST', '/wprs/v1/slug/' . $post_id ) );
    return array( $response->get_status(), $response->get_data() );
}

function t_has_meta_box( $post, $box_id ) {
    global $wp_meta_boxes;
    $wp_meta_boxes = array();
    set_current_screen( $post->post_type );
    do_action( 'add_meta_boxes', $post->post_type, $post );
    foreach ( (array) ( $wp_meta_boxes[ $post->post_type ] ?? array() ) as $contexts ) {
        foreach ( $contexts as $priority_boxes ) {
            if ( isset( $priority_boxes[ $box_id ] ) && false !== $priority_boxes[ $box_id ] ) {
                return true;
            }
        }
    }
    return false;
}

// パーマリンク設定でスラッグの扱いが変わるので、プレーンに固定して始め、最後に元へ戻す
$original_structure = get_option( 'permalink_structure' );
update_option( 'permalink_structure', '' );

$admin = t_user( 'administrator' );
wp_set_current_user( $admin );
$today = ( new DateTime( 'now', wp_timezone() ) )->format( 'Ymd' );

// --- REST: スラッグ生成 ---------------------------------------------------
$published = t_post( 'publish' );
$before    = get_post( $published )->post_name;
list( $status, $data ) = t_request( $published );
t_check( 'POST /wprs/v1/slug/<id> returns 200', 200 === $status, "status=$status" );
t_check(
    'slug = 4 random digits + today (site timezone) + post ID',
    is_array( $data ) && isset( $data['slug'] ) && 1 === preg_match( '/^\d{4}' . $today . $published . '$/', $data['slug'] ),
    wp_json_encode( $data )
);
t_check( 'endpoint does not save the slug', 200 === $status && get_post( $published )->post_name === $before );

// --- REST: 権限 ----------------------------------------------------------
wp_set_current_user( t_user( 'subscriber' ) );
list( $status ) = t_request( $published );
t_check( 'subscriber is rejected (403)', 403 === $status, "status=$status" );

wp_set_current_user( 0 );
list( $status ) = t_request( $published );
t_check( 'logged-out visitor is rejected (401)', 401 === $status, "status=$status" );
wp_set_current_user( $admin );

// --- REST: 入力検証 ------------------------------------------------------
// ルート自体が無い場合も 404 になるので、rest_no_route は「存在しない記事」とは見なさない
list( $status, $data ) = t_request( 99999999 );
$code = is_array( $data ) ? ( $data['code'] ?? '' ) : '';
t_check( 'missing post -> 404 from the route (not rest_no_route)', 404 === $status && 'rest_no_route' !== $code, "status=$status code=$code" );

$page = t_post( 'publish', 'page' );
list( $status, $data ) = t_request( $page );
$code = is_array( $data ) ? ( $data['code'] ?? '' ) : '';
t_check( 'non-target post type (page) -> 400', 400 === $status && 'rest_no_route' !== $code, "status=$status code=$code" );

// --- 保存: エディターが渡したスラッグが保存される ---------------------------
list( , $data ) = t_request( $published );
$new = is_array( $data ) ? ( $data['slug'] ?? '' ) : '';
wp_update_post( array( 'ID' => $published, 'post_name' => $new ) );
t_check( 'saving the generated slug keeps it', '' !== $new && get_post( $published )->post_name === $new );
t_check( 'old slug is kept for redirects', in_array( $before, get_post_meta( $published, '_wp_old_slug' ), true ) );

// --- 保存: パーマリンクが /%postname%/ でも "-2" が付かない ---------------------
// WordPress は、数字だけのスラッグを日付アーカイブとの衝突を避けるため "-2" に変える。
// このプラグインのスラッグは数字だけなので、自分の記事用に作ったものは変えずに保存する。
update_option( 'permalink_structure', '/%postname%/' );

$p = t_post( 'publish' );
list( , $data ) = t_request( $p );
$mine = is_array( $data ) ? ( $data['slug'] ?? '' ) : '';
wp_update_post( array( 'ID' => $p, 'post_name' => $mine ) );
t_check( 'under /%postname%/: generated slug is saved as is (no "-2")', '' !== $mine && get_post( $p )->post_name === $mine, 'saved=' . get_post( $p )->post_name . " wanted=$mine" );

// 狭さの確認: 他の記事のIDを持つスラッグや、形式の違う数字だけのスラッグは、WordPressの扱いのまま
$q = t_post( 'publish' );
$other_id_slug = '1234' . $today . ( $q + 1 );
wp_update_post( array( 'ID' => $q, 'post_name' => $other_id_slug ) );
t_check( 'under /%postname%/: slug carrying another post ID is still de-conflicted by WordPress', get_post( $q )->post_name !== $other_id_slug, 'saved=' . get_post( $q )->post_name );
wp_update_post( array( 'ID' => $q, 'post_name' => '2026' ) );
t_check( 'under /%postname%/: other numeric slug is still de-conflicted by WordPress', '2026' !== get_post( $q )->post_name, 'saved=' . get_post( $q )->post_name );

update_option( 'permalink_structure', '' );

// --- クラシックエディター: メタボックス -------------------------------------
foreach ( array( 'publish' => true, 'private' => true, 'draft' => false, 'pending' => false ) as $post_status => $expected ) {
    $id = t_post( $post_status );
    t_check( "classic meta box for post/$post_status: " . ( $expected ? 'shown' : 'hidden' ), t_has_meta_box( get_post( $id ), 'wprs_regenerate' ) === $expected );
}
t_check( 'classic meta box hidden for page (non-target)', false === t_has_meta_box( get_post( $page ), 'wprs_regenerate' ) );

add_filter( 'wprs_target_post_types', function ( $types ) { $types[] = 'page'; return $types; } );
t_check( 'classic meta box shown for page when added via filter', true === t_has_meta_box( get_post( $page ), 'wprs_regenerate' ) );

// --- 後始末 --------------------------------------------------------------
update_option( 'permalink_structure', $original_structure );
foreach ( $cleanup['posts'] as $id ) { wp_delete_post( $id, true ); }
foreach ( $cleanup['users'] as $id ) { wp_delete_user( $id ); }

echo $failures ? "\n$failures check(s) failed\n" : "\nAll checks passed\n";
exit( $failures ? 1 : 0 );
