<?php
/**
 * Yönetim: Can Eloksal → Sayfa Oluşturucu ekranı.
 * - Ana sayfayı düzenlenebilir bloklara aktar / tema bölümlerine geri dön
 * - Hazır düzenden yeni sayfa oluştur
 * - Elementor kısayolları
 *
 * @package CanEloksalBuilder
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'ceb_admin_menu', 20 );
/**
 * Alt menü.
 */
function ceb_admin_menu() {
	add_submenu_page( 'ce-dashboard', 'Sayfa Oluşturucu', 'Sayfa Oluşturucu', 'edit_pages', 'ceb-builder', 'ceb_render_admin' );
}

/**
 * Ekran.
 */
function ceb_render_admin() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$front     = (int) get_option( 'page_on_front' );
	$converted = $front && ce_is_builder_content( $front );
	$msg       = isset( $_GET['ceb'] ) ? sanitize_key( wp_unslash( $_GET['ceb'] ) ) : ''; // phpcs:ignore
	$messages  = array(
		'converted' => 'Ana sayfa bloklara aktarıldı. Artık sayfayı blok editöründe istediğiniz gibi düzenleyebilirsiniz.',
		'restored'  => 'Ana sayfa tema bölümlerine geri döndürüldü (Tema Ayarları → Ana Sayfa).',
		'nofront'   => 'Önce Ayarlar → Okuma\'dan statik bir ana sayfa seçin (Kurulum aracı bunu otomatik yapar).',
		'styles'    => 'Canlı editör stilleri güncellendi.',
	);

	echo '<div class="wrap ce-admin">';
	ce_admin_header( 'Sayfa Oluşturucu', 'Sayfaları sürükle-bırak bloklarla tasarlayın. Tüm bloklar "+" menüsünde "Can Eloksal" kategorisindedir.' );
	if ( isset( $messages[ $msg ] ) ) {
		echo '<div class="ce-toast ce-toast--success" role="status"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> ' . esc_html( $messages[ $msg ] ) . '</div>';
	}

	echo '<div class="ce-dash__grid">';

	// Canlı editör.
	echo '<section class="ce-card"><header class="ce-card__head"><h2>Canlı Editör</h2></header><div class="ce-card__body">';
	echo '<p>Sayfayı gerçek görünümüyle açın; metne tıklayıp yazın, görselleri değiştirin, renk / yazı / boşluk ayarlarını masaüstü, tablet ve mobil için ayrı ayrı yapın, bölümleri taşıyın. Değişiklikler kaynağına (Tema Ayarları, sayfa alanları, bloklar) kaydedilir.</p>';
	echo '<p><a class="button button-primary button-hero" href="' . esc_url( ceb_live_url( home_url( '/' ) ) ) . '">Canlı Editörü aç</a></p>';
	echo '<p class="description">Sitede gezinirken üst çubuktaki <strong>Canlı Düzenle</strong> düğmesiyle veya sayfa listelerindeki bağlantıyla da açabilirsiniz.</p>';
	echo '</div></section>';

	// Canlı stiller.
	if ( current_user_can( 'edit_theme_options' ) ) {
		$styles  = ceb_live_styles();
		$history = get_option( 'ceb_live_styles_history', array() );
		echo '<section class="ce-card"><header class="ce-card__head"><h2>Canlı editör stilleri</h2></header><div class="ce-card__body">';
		if ( $styles ) {
			echo '<ul class="ce-list">';
			foreach ( $styles as $ctx => $devices ) {
				$n = 0;
				foreach ( (array) $devices as $sels ) {
					$n += count( (array) $sels );
				}
				echo '<li><strong>' . esc_html( ceb_context_label( $ctx ) ) . '</strong><span>' . (int) $n . ' kural</span></li>';
			}
			echo '</ul>';
		} else {
			echo '<p>Henüz canlı editörle kaydedilmiş stil yok.</p>';
		}
		if ( $history ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-confirm="Seçilen kayıt geri yüklenecek. Devam edilsin mi?" style="margin-top:12px">';
			wp_nonce_field( 'ceb_live_restore' );
			echo '<input type="hidden" name="action" value="ceb_live_restore"><label for="ceb-hist"><strong>Geçmişten geri yükle</strong></label><br><select id="ceb-hist" name="index" class="ce-input">';
			foreach ( $history as $i => $h ) {
				echo '<option value="' . (int) $i . '">' . esc_html( wp_date( 'd.m.Y H:i', $h['time'] ) . ' — ' . $h['user'] ) . '</option>';
			}
			echo '</select> <button class="button">Geri yükle</button></form>';
		}
		if ( $styles ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-confirm="Canlı editörle yapılan TÜM stil değişiklikleri kaldırılacak (geçmişten geri alınabilir). Devam edilsin mi?" style="margin-top:12px">';
			wp_nonce_field( 'ceb_live_restore' );
			echo '<input type="hidden" name="action" value="ceb_live_restore"><input type="hidden" name="index" value="-1"><button class="button button-link-delete">Tüm canlı stilleri sıfırla</button></form>';
		}
		echo '</div></section>';
	}

	// Ana sayfa.
	echo '<section class="ce-card"><header class="ce-card__head"><h2>Ana sayfa</h2></header><div class="ce-card__body">';
	if ( $converted ) {
		echo '<p><strong>Durum:</strong> Ana sayfa blok editöründen yönetiliyor.</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( get_edit_post_link( $front ) ) . '">Ana sayfayı editörde aç</a> <a class="button" href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">Görüntüle ↗</a></p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-confirm="Ana sayfa içeriği yedeğine döner ve Tema Ayarları\'ndaki bölümler kullanılır. Devam edilsin mi?">';
		wp_nonce_field( 'ceb_restore_home' );
		echo '<input type="hidden" name="action" value="ceb_restore_home"><button class="button">Tema bölümlerine geri dön</button></form>';
	} else {
		echo '<p><strong>Durum:</strong> Ana sayfa Tema Ayarları → Ana Sayfa bölümünden yönetiliyor.</p>';
		echo '<p>Aşağıdaki buton ana sayfanın şu anki görünümünü (bölüm sırası ve tüm metinler) bloklara çevirir. Ardından bölümleri sürükleyip sıralayabilir, silebilir, çoğaltabilir, araya kendi içeriğinizi ekleyebilirsiniz. Önceki içerik yedeklenir; istediğiniz zaman geri dönebilirsiniz.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ceb_convert_home' );
		echo '<input type="hidden" name="action" value="ceb_convert_home"><button class="button button-primary">Ana sayfayı düzenlenebilir bloklara aktar</button></form>';
	}
	echo '</div></section>';

	// Yeni sayfa.
	echo '<section class="ce-card"><header class="ce-card__head"><h2>Hazır düzenden yeni sayfa</h2></header><div class="ce-card__body">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'ceb_create_page' );
	echo '<input type="hidden" name="action" value="ceb_create_page">';
	echo '<p><label for="ceb-title"><strong>Sayfa başlığı</strong></label><br><input id="ceb-title" class="ce-input" name="title" required placeholder="Ör. Savunma Sanayi Çözümleri"></p>';
	echo '<fieldset><legend class="screen-reader-text">Düzen</legend>';
	foreach ( ceb_layouts() as $slug => $layout ) {
		if ( 'home' === $slug ) {
			continue;
		}
		printf(
			'<label class="ceb-layout"><input type="radio" name="layout" value="%1$s" %4$s><span><strong>%2$s</strong><small>%3$s</small></span></label>',
			esc_attr( $slug ),
			esc_html( $layout['title'] ),
			esc_html( $layout['description'] ),
			checked( 'corporate', $slug, false )
		);
	}
	echo '</fieldset><p><button class="button button-primary">Taslak oluştur ve editörde aç</button></p></form>';
	echo '</div></section>';

	// Nasıl kullanılır.
	echo '<section class="ce-card"><header class="ce-card__head"><h2>Nasıl kullanılır?</h2></header><div class="ce-card__body"><ol>';
	echo '<li>Herhangi bir sayfayı düzenleyin, sol üstteki <strong>+</strong> butonundan <strong>Can Eloksal</strong> kategorisindeki bloğu ekleyin.</li>';
	echo '<li>Bloğu seçince sağ paneldeki <strong>Bölüm ayarları</strong>ndan başlık, metin, görsel, sayı ve kategori gibi alanları değiştirin; önizleme anında güncellenir.</li>';
	echo '<li>Blokları sürükleyerek veya ↑↓ oklarıyla sıralayın; ⋮ menüsünden çoğaltın, silin, "Desen olarak kaydet" ile tekrar kullanın.</li>';
	echo '<li><strong>Bölüm (Kapsayıcı)</strong> bloğunun içine paragraf, görsel, sütunlar, butonlar, tablo, video gibi WordPress bloklarını koyabilirsiniz.</li>';
	echo '<li>İçinde Can Eloksal bloğu olan sayfalar otomatik olarak tam genişlikte çizilir. Boş bir sayfa için şablon olarak <strong>Boş Tuval</strong> seçebilirsiniz.</li>';
	echo '<li>Renkler, fontlar, köşe ve genişlik: <a href="' . esc_url( admin_url( 'customize.php?autofocus[panel]=ce_design' ) ) . '">Görünüm → Özelleştir → Can Eloksal Tasarım</a> (canlı önizleme).</li>';
	echo '</ol></div></section>';

	// Elementor.
	echo '<section class="ce-card"><header class="ce-card__head"><h2>Elementor</h2></header><div class="ce-card__body">';
	if ( did_action( 'elementor/loaded' ) ) {
		echo '<p><span class="dashicons dashicons-yes-alt" style="color:#0E8A5F"></span> Elementor etkin. Sayfayı "Elementor ile Düzenle" ile açabilirsiniz. Elementor ile hazırlanan sayfalar tema header/footer\'ı ile tam genişlikte gösterilir; Elementor Pro Theme Builder ile header/footer da değiştirilebilir.</p>';
	} else {
		echo '<p>Tema Elementor ile tam uyumludur. Elementor\'u kullanmak isterseniz ücretsiz sürümünü WordPress eklenti dizininden kurabilirsiniz.</p>';
		if ( current_user_can( 'install_plugins' ) ) {
			echo '<p><a class="button" href="' . esc_url( admin_url( 'plugin-install.php?s=elementor&tab=search&type=term' ) ) . '">Elementor\'u kur</a></p>';
		}
	}
	echo '</div></section>';

	echo '</div></div>';
	echo '<style>.ceb-layout{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border:1px solid #dce3e7;border-radius:12px;margin:0 0 8px;cursor:pointer}.ceb-layout small{display:block;color:#5b6873}.ceb-layout:has(input:checked){border-color:#00AFC1;background:#f0fbfc}</style>';
}

add_action( 'admin_post_ceb_convert_home', 'ceb_convert_home' );
/**
 * Ana sayfayı bloklara aktarır (önceki içerik yedeklenir).
 */
function ceb_convert_home() {
	check_admin_referer( 'ceb_convert_home' );
	$front = (int) get_option( 'page_on_front' );
	if ( ! $front || 'page' !== get_option( 'show_on_front' ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=ceb-builder&ceb=nofront' ) );
		exit;
	}
	if ( ! current_user_can( 'edit_post', $front ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	update_post_meta( $front, '_ceb_backup_content', get_post_field( 'post_content', $front ) );
	wp_update_post( array( 'ID' => $front, 'post_content' => wp_slash( ceb_home_markup() ) ) );
	if ( function_exists( 'ce_audit' ) ) {
		ce_audit( 'update', 'page', $front, 'Ana sayfa bloklara aktarıldı' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=ceb-builder&ceb=converted' ) );
	exit;
}

add_action( 'admin_post_ceb_restore_home', 'ceb_restore_home' );
/**
 * Tema bölümlerine geri döner.
 */
function ceb_restore_home() {
	check_admin_referer( 'ceb_restore_home' );
	$front = (int) get_option( 'page_on_front' );
	if ( ! $front || ! current_user_can( 'edit_post', $front ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$backup = (string) get_post_meta( $front, '_ceb_backup_content', true );
	if ( false !== strpos( $backup, '<!-- wp:ce/' ) ) {
		$backup = '';
	}
	wp_update_post( array( 'ID' => $front, 'post_content' => wp_slash( $backup ) ) );
	if ( function_exists( 'ce_audit' ) ) {
		ce_audit( 'update', 'page', $front, 'Ana sayfa tema bölümlerine döndürüldü' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=ceb-builder&ceb=restored' ) );
	exit;
}

add_action( 'admin_post_ceb_create_page', 'ceb_create_page' );
/**
 * Hazır düzenden taslak sayfa.
 */
function ceb_create_page() {
	check_admin_referer( 'ceb_create_page' );
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$layouts = ceb_layouts();
	$slug    = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : 'blank';
	$slug    = isset( $layouts[ $slug ] ) ? $slug : 'blank';
	$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	$title   = $title ? $title : $layouts[ $slug ]['title'];
	$id      = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'draft',
			'post_title'   => $title,
			'post_content' => wp_slash( call_user_func( $layouts[ $slug ]['content'] ) ),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		wp_die( esc_html( $id->get_error_message() ) );
	}
	wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
	exit;
}
