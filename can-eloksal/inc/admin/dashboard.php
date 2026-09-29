<?php
/**
 * Kontrol paneli: özet kartları, son talepler, son yazılar, hızlı işlemler, global arama
 * ve yeni talep bildirimleri (menü rozeti + üst çubuk).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Yeni (işlenmemiş) talep sayısı.
 *
 * @param string $type ce_message|ce_quote.
 * @return int
 */
function ce_new_inquiry_count( $type ) {
	$cache = wp_cache_get( 'ce_new_' . $type, 'ce' );
	if ( false !== $cache ) {
		return (int) $cache;
	}
	$q     = new WP_Query(
		array(
			'post_type'              => $type,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'meta_key'               => '_ce_status',
			'meta_value'             => 'new',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	$count = (int) $q->found_posts;
	wp_cache_set( 'ce_new_' . $type, $count, 'ce', 60 );
	return $count;
}

add_action( 'admin_menu', 'ce_inquiry_menu_bubbles', 99 );
/**
 * Menüde yeni talep rozetleri.
 */
function ce_inquiry_menu_bubbles() {
	global $menu;
	if ( ! current_user_can( 'ce_manage_inquiries' ) || ! is_array( $menu ) ) {
		return;
	}
	foreach ( $menu as $i => $item ) {
		foreach ( array( 'ce_message', 'ce_quote' ) as $type ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=' . $type === $item[2] ) {
				$count = ce_new_inquiry_count( $type );
				if ( $count ) {
					$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . number_format_i18n( $count ) . '</span></span>'; // phpcs:ignore
				}
			}
		}
	}
}

add_action( 'admin_bar_menu', 'ce_admin_bar_notifications', 90 );
/**
 * Üst çubukta bildirimler.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function ce_admin_bar_notifications( $bar ) {
	if ( ! is_user_logged_in() || ! current_user_can( 'ce_manage_inquiries' ) ) {
		return;
	}
	$messages = ce_new_inquiry_count( 'ce_message' );
	$quotes   = ce_new_inquiry_count( 'ce_quote' );
	$total    = $messages + $quotes;
	$bar->add_node(
		array(
			'id'    => 'ce-notifications',
			'title' => '<span class="ab-icon dashicons dashicons-bell" aria-hidden="true"></span><span class="ab-label">' . ( $total ? (int) $total : '0' ) . '</span><span class="screen-reader-text"> yeni talep</span>',
			'href'  => admin_url( 'admin.php?page=ce-dashboard' ),
			'meta'  => array( 'class' => $total ? 'ce-has-new' : '' ),
		)
	);
	$bar->add_node( array( 'parent' => 'ce-notifications', 'id' => 'ce-n-quotes', 'title' => 'Yeni teklif talebi: ' . (int) $quotes, 'href' => admin_url( 'edit.php?post_type=ce_quote&ce_status=new' ) ) );
	$bar->add_node( array( 'parent' => 'ce-notifications', 'id' => 'ce-n-messages', 'title' => 'Yeni iletişim mesajı: ' . (int) $messages, 'href' => admin_url( 'edit.php?post_type=ce_message&ce_status=new' ) ) );
}

/**
 * Global arama sonuçları.
 *
 * @param string $term Terim.
 * @return array<string,WP_Post[]>
 */
function ce_admin_search( $term ) {
	$groups = array(
		'ce_service' => 'Hizmetler',
		'post'       => 'Blog yazıları',
		'page'       => 'Sayfalar',
	);
	if ( current_user_can( 'ce_manage_inquiries' ) ) {
		$groups['ce_message'] = 'İletişim talepleri';
		$groups['ce_quote']   = 'Teklif talepleri';
	}
	$out = array();
	foreach ( $groups as $type => $label ) {
		$found = get_posts(
			array(
				'post_type'   => $type,
				'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				's'           => $term,
				'numberposts' => 10,
			)
		);
		if ( in_array( $type, array( 'ce_message', 'ce_quote' ), true ) ) {
			$by_meta = get_posts(
				array(
					'post_type'   => $type,
					'post_status' => 'publish',
					'numberposts' => 10,
					'meta_query'  => array(
						'relation' => 'OR',
						array( 'key' => '_ce_f_email', 'value' => $term, 'compare' => 'LIKE' ),
						array( 'key' => '_ce_f_company', 'value' => $term, 'compare' => 'LIKE' ),
						array( 'key' => '_ce_f_phone', 'value' => $term, 'compare' => 'LIKE' ),
						array( 'key' => '_ce_f_message', 'value' => $term, 'compare' => 'LIKE' ),
					),
				)
			);
			$ids   = wp_list_pluck( $found, 'ID' );
			foreach ( $by_meta as $p ) {
				if ( ! in_array( $p->ID, $ids, true ) ) {
					$found[] = $p;
				}
			}
		}
		if ( $found ) {
			$out[ $label ] = $found;
		}
	}
	return $out;
}

/**
 * Kontrol paneli ekranı.
 */
function ce_render_dashboard() {
	$can_inq = current_user_can( 'ce_manage_inquiries' );
	$term    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore

	$search_form = '<form class="ce-search" method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '" role="search"><input type="hidden" name="page" value="ce-dashboard"><span class="dashicons dashicons-search" aria-hidden="true"></span><label class="screen-reader-text" for="ce-q">Ara</label><input id="ce-q" type="search" name="q" value="' . esc_attr( $term ) . '" placeholder="Hizmet, blog, mesaj, teklif ara…"></form>';

	echo '<div class="wrap ce-admin ce-dash">';
	ce_admin_header( 'Kontrol Paneli', 'Hoş geldiniz, ' . wp_get_current_user()->display_name . '.', $search_form );

	if ( $term ) {
		$results = ce_admin_search( $term );
		echo '<section class="ce-card"><header class="ce-card__head"><h2>"' . esc_html( $term ) . '" için sonuçlar</h2><a href="' . esc_url( admin_url( 'admin.php?page=ce-dashboard' ) ) . '">Temizle</a></header>';
		if ( ! $results ) {
			echo '<div class="ce-empty"><span class="dashicons dashicons-search" aria-hidden="true"></span><p>Sonuç bulunamadı.</p></div>';
		}
		foreach ( $results as $label => $posts ) {
			echo '<h3 class="ce-dash__group">' . esc_html( $label ) . '</h3><ul class="ce-list">';
			foreach ( $posts as $p ) {
				printf(
					'<li><a href="%1$s"><strong>%2$s</strong></a><span>%3$s</span></li>',
					esc_url( get_edit_post_link( $p->ID ) ),
					esc_html( $p->post_title ? $p->post_title : '(başlıksız)' ),
					esc_html( get_the_date( 'd.m.Y H:i', $p ) )
				);
			}
			echo '</ul>';
		}
		echo '</section>';
	}

	// Özet kartları.
	$stats = array(
		array( 'Toplam Hizmet', (int) wp_count_posts( 'ce_service' )->publish, 'dashicons-admin-generic', admin_url( 'edit.php?post_type=ce_service' ) ),
		array( 'Toplam Blog', (int) wp_count_posts( 'post' )->publish, 'dashicons-edit-page', admin_url( 'edit.php' ) ),
		array( 'Galeri Görselleri', (int) wp_count_posts( 'ce_gallery' )->publish, 'dashicons-format-gallery', admin_url( 'edit.php?post_type=ce_gallery' ) ),
	);
	if ( $can_inq ) {
		$stats[] = array( 'Yeni İletişim', ce_new_inquiry_count( 'ce_message' ), 'dashicons-email', admin_url( 'edit.php?post_type=ce_message&ce_status=new' ), true );
		$stats[] = array( 'Yeni Teklif', ce_new_inquiry_count( 'ce_quote' ), 'dashicons-clipboard', admin_url( 'edit.php?post_type=ce_quote&ce_status=new' ), true );
	}
	echo '<div class="ce-stats">';
	foreach ( $stats as $s ) {
		printf(
			'<a class="ce-stat%5$s" href="%4$s"><span class="ce-stat__icon dashicons %3$s" aria-hidden="true"></span><span class="ce-stat__value">%2$s</span><span class="ce-stat__label">%1$s</span></a>',
			esc_html( $s[0] ),
			esc_html( number_format_i18n( $s[1] ) ),
			esc_attr( $s[2] ),
			esc_url( $s[3] ),
			! empty( $s[4] ) && $s[1] ? ' ce-stat--alert' : ''
		);
	}
	echo '</div>';

	// Hızlı işlemler.
	$actions = array(
		array( 'Yeni hizmet', 'post-new.php?post_type=ce_service', 'dashicons-plus-alt2', 'edit_posts' ),
		array( 'Yeni blog yazısı', 'post-new.php', 'dashicons-welcome-write-blog', 'edit_posts' ),
		array( 'Galeriye toplu ekle', 'edit.php?post_type=ce_gallery&page=ce-gallery-bulk', 'dashicons-images-alt2', 'upload_files' ),
		array( 'Yeni slayt', 'post-new.php?post_type=ce_slide', 'dashicons-slides', 'edit_posts' ),
		array( 'Tema ayarları', 'admin.php?page=ce-settings', 'dashicons-admin-settings', 'ce_manage_settings' ),
		array( 'Menüler', 'nav-menus.php', 'dashicons-menu', 'edit_theme_options' ),
	);
	echo '<div class="ce-quick">';
	foreach ( $actions as $a ) {
		if ( current_user_can( $a[3] ) ) {
			printf( '<a class="ce-quick__item" href="%2$s"><span class="dashicons %3$s" aria-hidden="true"></span>%1$s</a>', esc_html( $a[0] ), esc_url( admin_url( $a[1] ) ), esc_attr( $a[2] ) );
		}
	}
	echo '</div>';

	echo '<div class="ce-dash__grid">';
	if ( $can_inq ) {
		ce_dashboard_inquiry_card( 'ce_quote', 'Son teklif talepleri' );
		ce_dashboard_inquiry_card( 'ce_message', 'Son iletişim mesajları' );
	}
	// Son bloglar.
	$posts = get_posts( array( 'numberposts' => 5, 'post_status' => array( 'publish', 'draft', 'future' ) ) );
	echo '<section class="ce-card"><header class="ce-card__head"><h2>Son blog yazıları</h2><a href="' . esc_url( admin_url( 'edit.php' ) ) . '">Tümü</a></header>';
	if ( $posts ) {
		echo '<ul class="ce-list">';
		foreach ( $posts as $p ) {
			$status = 'publish' === $p->post_status ? '' : ' <em class="ce-pill">' . esc_html( get_post_status_object( $p->post_status )->label ) . '</em>';
			printf( '<li><a href="%1$s"><strong>%2$s</strong>%4$s</a><span>%3$s</span></li>', esc_url( get_edit_post_link( $p->ID ) ), esc_html( $p->post_title ), esc_html( get_the_date( 'd.m.Y', $p ) ), $status ); // phpcs:ignore
		}
		echo '</ul>';
	} else {
		echo '<div class="ce-empty"><span class="dashicons dashicons-edit-page" aria-hidden="true"></span><p>Henüz blog yazısı yok.</p></div>';
	}
	echo '</section>';

	// Sistem özeti.
	echo '<section class="ce-card"><header class="ce-card__head"><h2>Site durumu</h2></header><ul class="ce-checks">';
	$checks = array(
		array( 'Kalıcı bağlantılar (SEO URL)', (bool) get_option( 'permalink_structure' ) ),
		array( 'Arama motorlarına açık', (bool) get_option( 'blog_public' ) ),
		array( 'SMTP yapılandırıldı', (bool) ( ce_opt( 'smtp_enabled' ) && ce_opt( 'smtp_host' ) ) ),
		array( 'HTTPS', is_ssl() ),
		array( 'WebP desteği', wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ),
		array( 'Başlangıç kurulumu yapıldı', (bool) get_option( 'ce_setup_done' ) ),
	);
	foreach ( $checks as $c ) {
		printf( '<li class="%2$s"><span class="dashicons %3$s" aria-hidden="true"></span>%1$s</li>', esc_html( $c[0] ), $c[1] ? 'is-ok' : 'is-warn', $c[1] ? 'dashicons-yes-alt' : 'dashicons-warning' );
	}
	echo '</ul></section>';
	echo '</div></div>';
}

/**
 * Talep kartı.
 *
 * @param string $type  Tür.
 * @param string $title Başlık.
 */
function ce_dashboard_inquiry_card( $type, $title ) {
	$items    = get_posts( array( 'post_type' => $type, 'numberposts' => 6, 'post_status' => 'publish' ) );
	$statuses = ce_inquiry_statuses( 'ce_message' === $type ? 'message' : 'quote' );
	echo '<section class="ce-card"><header class="ce-card__head"><h2>' . esc_html( $title ) . '</h2><a href="' . esc_url( admin_url( 'edit.php?post_type=' . $type ) ) . '">Tümü</a></header>';
	if ( ! $items ) {
		echo '<div class="ce-empty"><span class="dashicons dashicons-inbox" aria-hidden="true"></span><p>Henüz talep yok.</p></div></section>';
		return;
	}
	echo '<ul class="ce-list">';
	foreach ( $items as $p ) {
		$status = (string) get_post_meta( $p->ID, '_ce_status', true );
		printf(
			'<li><a href="%1$s"><strong>%2$s</strong><small>%5$s</small></a><span class="ce-status ce-status--%3$s">%4$s</span></li>',
			esc_url( get_edit_post_link( $p->ID ) ),
			esc_html( $p->post_title ),
			esc_attr( $status ),
			esc_html( $statuses[ $status ] ?? $status ),
			esc_html( get_the_date( 'd.m.Y H:i', $p ) . ' · ' . get_post_meta( $p->ID, '_ce_f_email', true ) )
		);
	}
	echo '</ul></section>';
}

add_action( 'wp_dashboard_setup', 'ce_wp_dashboard_widget' );
/**
 * WordPress ana panelinde kısa özet.
 */
function ce_wp_dashboard_widget() {
	if ( ! current_user_can( 'ce_manage_inquiries' ) ) {
		return;
	}
	wp_add_dashboard_widget(
		'ce_inquiries_widget',
		'Can Eloksal — Talepler',
		static function () {
			printf(
				'<p><strong>%1$d</strong> yeni teklif talebi, <strong>%2$d</strong> yeni iletişim mesajı.</p><p><a class="button button-primary" href="%3$s">Kontrol paneline git</a></p>',
				(int) ce_new_inquiry_count( 'ce_quote' ),
				(int) ce_new_inquiry_count( 'ce_message' ),
				esc_url( admin_url( 'admin.php?page=ce-dashboard' ) )
			);
		}
	);
}
