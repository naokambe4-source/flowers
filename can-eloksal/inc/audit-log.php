<?php
/**
 * Audit log: önemli yönetim işlemlerini {prefix}ce_audit_log tablosuna kaydeder.
 * Kaydedilenler: giriş/çıkış/başarısız giriş, içerik oluşturma/güncelleme/çöpe taşıma/silme,
 * tema ayarları, kullanıcı işlemleri, talep durum değişiklikleri.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tablo adı.
 *
 * @return string
 */
function ce_audit_table() {
	global $wpdb;
	return $wpdb->prefix . 'ce_audit_log';
}

/**
 * Tabloyu oluşturur/günceller.
 */
function ce_audit_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = ce_audit_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_login varchar(60) NOT NULL DEFAULT '',
			action varchar(40) NOT NULL DEFAULT '',
			module varchar(40) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			object_title varchar(255) NOT NULL DEFAULT '',
			ip varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY action (action),
			KEY module (module),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset};"
	);
}

/**
 * Kayıt ekler.
 *
 * @param string $action Eylem (login, logout, create, update, trash, delete, settings…).
 * @param string $module Modül (auth, service, post, settings…).
 * @param int    $object_id Kayıt ID.
 * @param string $title  Kayıt başlığı / açıklama.
 * @param int    $user_id Kullanıcı (0 = geçerli).
 */
function ce_audit( $action, $module, $object_id = 0, $title = '', $user_id = 0 ) {
	global $wpdb;
	static $table_ok = null;
	if ( null === $table_ok ) {
		$table_ok = (bool) get_option( 'ce_db_version' );
	}
	if ( ! $table_ok ) {
		return;
	}
	$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	$ua   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		ce_audit_table(),
		array(
			'user_id'      => $user && $user->ID ? (int) $user->ID : 0,
			'user_login'   => $user && $user->ID ? $user->user_login : '',
			'action'       => substr( sanitize_key( $action ), 0, 40 ),
			'module'       => substr( sanitize_key( $module ), 0, 40 ),
			'object_id'    => absint( $object_id ),
			'object_title' => mb_substr( wp_strip_all_tags( (string) $title ), 0, 250 ),
			'ip'           => ce_client_ip(),
			'user_agent'   => mb_substr( $ua, 0, 250 ),
			'created_at'   => current_time( 'mysql', true ),
		),
		array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
	);
}

/**
 * İzlenen içerik türleri → modül adı.
 *
 * @return array<string,string>
 */
function ce_audit_modules() {
	return array(
		'post'         => 'blog',
		'page'         => 'page',
		'ce_service'   => 'service',
		'ce_slide'     => 'slider',
		'ce_sector'    => 'sector',
		'ce_gallery'   => 'gallery',
		'ce_reference' => 'reference',
		'ce_bank'      => 'bank',
		'ce_message'   => 'message',
		'ce_quote'     => 'quote',
		'nav_menu_item' => 'menu',
	);
}

add_action( 'wp_login', 'ce_audit_login', 20, 2 );
/**
 * Giriş.
 *
 * @param string  $login Kullanıcı adı.
 * @param WP_User $user  Kullanıcı.
 */
function ce_audit_login( $login, $user ) {
	ce_audit( 'login', 'auth', $user->ID, $login, $user->ID );
}

add_action( 'wp_logout', 'ce_audit_logout' );
/**
 * Çıkış.
 *
 * @param int $user_id Kullanıcı.
 */
function ce_audit_logout( $user_id = 0 ) {
	$user = get_userdata( $user_id );
	ce_audit( 'logout', 'auth', $user_id, $user ? $user->user_login : '', $user_id );
}

add_action( 'transition_post_status', 'ce_audit_post_status', 10, 3 );
/**
 * Oluşturma / güncelleme / yayınlama / çöpe taşıma.
 *
 * @param string  $new  Yeni durum.
 * @param string  $old  Eski durum.
 * @param WP_Post $post Yazı.
 */
function ce_audit_post_status( $new, $old, $post ) {
	$modules = ce_audit_modules();
	if ( ! isset( $modules[ $post->post_type ] ) || 'nav_menu_item' === $post->post_type || wp_is_post_revision( $post ) || 'auto-draft' === $new ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		return; // Ziyaretçi form gönderimleri audit'e yazılmaz.
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( 'trash' === $new ) {
		$action = 'trash';
	} elseif ( 'trash' === $old ) {
		$action = 'restore';
	} elseif ( in_array( $old, array( 'new', 'auto-draft' ), true ) ) {
		$action = 'create';
	} elseif ( 'publish' === $new && 'publish' !== $old ) {
		$action = 'publish';
	} else {
		$action = 'update';
	}
	// Aynı istekte tekrar eden kayıtları önle (blok editörü + meta kutusu kaydı).
	static $seen = array();
	$key = $post->ID . $action;
	if ( isset( $seen[ $key ] ) ) {
		return;
	}
	$seen[ $key ] = true;
	ce_audit( $action, $modules[ $post->post_type ], $post->ID, $post->post_title );
}

add_action( 'before_delete_post', 'ce_audit_delete', 10, 2 );
/**
 * Kalıcı silme.
 *
 * @param int     $post_id Yazı.
 * @param WP_Post $post    Yazı.
 */
function ce_audit_delete( $post_id, $post = null ) {
	$post    = $post ? $post : get_post( $post_id );
	$modules = ce_audit_modules();
	if ( $post && isset( $modules[ $post->post_type ] ) && 'nav_menu_item' !== $post->post_type && is_user_logged_in() ) {
		ce_audit( 'delete', $modules[ $post->post_type ], $post_id, $post->post_title );
	}
}

add_action( 'wp_update_nav_menu', 'ce_audit_menu' );
/**
 * Menü güncelleme.
 *
 * @param int $menu_id Menü.
 */
function ce_audit_menu( $menu_id ) {
	static $done = false;
	if ( $done || ! is_user_logged_in() ) {
		return;
	}
	$done = true;
	$menu = wp_get_nav_menu_object( $menu_id );
	ce_audit( 'update', 'menu', $menu_id, $menu ? $menu->name : '' );
}

add_action( 'user_register', static fn( $id ) => is_user_logged_in() && ce_audit( 'create', 'user', $id, get_userdata( $id )->user_login ) );
add_action( 'profile_update', static fn( $id ) => is_user_logged_in() && ce_audit( 'update', 'user', $id, get_userdata( $id )->user_login ) );
add_action( 'delete_user', static fn( $id ) => ce_audit( 'delete', 'user', $id, get_userdata( $id ) ? get_userdata( $id )->user_login : '' ) );
add_action( 'set_user_role', static fn( $id, $role ) => is_user_logged_in() && ce_audit( 'role', 'user', $id, get_userdata( $id )->user_login . ' → ' . $role ), 10, 2 );
add_action( 'switch_theme', static fn( $name ) => ce_audit( 'switch', 'theme', 0, $name ) );
add_action( 'activated_plugin', static fn( $plugin ) => ce_audit( 'activate', 'plugin', 0, $plugin ) );
add_action( 'deactivated_plugin', static fn( $plugin ) => ce_audit( 'deactivate', 'plugin', 0, $plugin ) );

add_action( 'ce_daily_maintenance', 'ce_audit_prune' );
/**
 * 365 günden eski kayıtları temizler.
 */
function ce_audit_prune() {
	global $wpdb;
	$table = ce_audit_table();
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", gmdate( 'Y-m-d H:i:s', time() - YEAR_IN_SECONDS ) ) ); // phpcs:ignore
}

add_action( 'init', 'ce_schedule_maintenance' );
/**
 * Günlük bakım görevi.
 */
function ce_schedule_maintenance() {
	if ( ! wp_next_scheduled( 'ce_daily_maintenance' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ce_daily_maintenance' );
	}
}
