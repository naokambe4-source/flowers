<?php
/**
 * Rol bazlı yetkilendirme (RBAC).
 *
 * Roller:
 *   Yönetici (administrator)       → Süper Admin: her şey + kullanıcılar + audit log.
 *   Site Yöneticisi (ce_admin)     → İçerik + talepler + tema ayarları + menüler. Kullanıcı/eklenti yönetemez.
 *   İçerik Editörü (ce_editor)     → Hizmet, blog, galeri, sayfa içerikleri. Ayar ve taleplere erişemez.
 *
 * Özel yetkiler:
 *   ce_manage_inquiries  İletişim ve teklif taleplerini görme/yönetme, dosya indirme.
 *   ce_manage_settings   Tema Ayarları (SEO, SMTP, iletişim bilgileri…).
 *   ce_view_audit        Audit log.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rolleri ve yetkileri kurar (tema etkinleştirmede).
 */
function ce_install_roles() {
	$content_caps = array(
		'read'                   => true,
		'upload_files'           => true,
		'edit_posts'             => true,
		'edit_others_posts'      => true,
		'edit_published_posts'   => true,
		'publish_posts'          => true,
		'delete_posts'           => true,
		'delete_published_posts' => true,
		'edit_pages'             => true,
		'edit_others_pages'      => true,
		'edit_published_pages'   => true,
		'publish_pages'          => true,
		'manage_categories'      => true,
		'moderate_comments'      => true,
		'unfiltered_html'        => false,
	);

	remove_role( 'ce_editor' );
	add_role( 'ce_editor', 'İçerik Editörü', $content_caps );

	$admin_caps = array_merge(
		$content_caps,
		array(
			'delete_others_posts'  => true,
			'delete_pages'         => true,
			'delete_others_pages'  => true,
			'delete_published_pages' => true,
			'delete_private_posts' => true,
			'delete_private_pages' => true,
			'edit_private_posts'   => true,
			'edit_private_pages'   => true,
			'read_private_posts'   => true,
			'read_private_pages'   => true,
			'edit_theme_options'   => true,
			'ce_manage_inquiries'  => true,
			'ce_manage_settings'   => true,
		)
	);
	remove_role( 'ce_admin' );
	add_role( 'ce_admin', 'Site Yöneticisi', $admin_caps );

	$administrator = get_role( 'administrator' );
	if ( $administrator ) {
		foreach ( array( 'ce_manage_inquiries', 'ce_manage_settings', 'ce_view_audit' ) as $cap ) {
			$administrator->add_cap( $cap );
		}
	}
	$editor = get_role( 'editor' );
	if ( $editor ) {
		$editor->add_cap( 'ce_manage_inquiries' );
	}
}

add_action( 'switch_theme', 'ce_remove_caps_on_switch' );
/**
 * Tema değiştirildiğinde çekirdek rollere eklenen özel yetkileri geri alır (özel roller ve kullanıcıları korunur).
 */
function ce_remove_caps_on_switch() {
	foreach ( array( 'administrator', 'editor' ) as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( array( 'ce_manage_inquiries', 'ce_manage_settings', 'ce_view_audit' ) as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
