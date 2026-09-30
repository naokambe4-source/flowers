<?php
/**
 * Derin Tasarım Stüdyosu: solda ayar paneli, sağda sitenin canlı önizlemesi.
 * Yazıya tıkla-düzenle, görsel değiştir, bölüm sırala/gizle/tasarla, tüm tema ayarları,
 * masaüstü/tablet/mobil önizleme, geri al/ileri al. Değişiklikler "Yayınla"ya kadar taslaktır;
 * ziyaretçiler taslağı görmez.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menü.
 */
function df_studio_menu() {
	$hook = add_menu_page(
		'Tasarım Stüdyosu',
		'Tasarım Stüdyosu',
		'edit_theme_options',
		'df-studio',
		'df_studio_page',
		'dashicons-art',
		3
	);
	add_action( 'load-' . $hook, 'df_studio_load' );
}
add_action( 'admin_menu', 'df_studio_menu', 8 );

/**
 * Stüdyo sayfası açılırken: bildirimleri sustur, taslağı hazırla.
 */
function df_studio_load() {
	remove_all_actions( 'admin_notices' );
	remove_all_actions( 'all_admin_notices' );
	add_filter(
		'admin_body_class',
		function ( $c ) {
			return $c . ' df-studio-screen';
		}
	);
	add_filter( 'admin_title', fn() => 'Tasarım Stüdyosu ‹ ' . get_bloginfo( 'name' ) );
}

/**
 * Taslak (yoksa yayındaki ayarlardan oluşturulur).
 *
 * @param bool $create Yoksa oluştur.
 * @return array
 */
function df_studio_draft( $create = true ) {
	$draft = get_option( DF_OPTION_DRAFT, null );
	if ( is_array( $draft ) ) {
		return array_merge( df_defaults(), $draft );
	}
	$live = get_option( DF_OPTION, array() );
	$live = array_merge( df_defaults(), is_array( $live ) ? $live : array() );
	if ( $create ) {
		update_option( DF_OPTION_DRAFT, $live, false );
	}
	return $live;
}

/**
 * Taslak yayındakinden farklı mı?
 *
 * @return bool
 */
function df_studio_has_changes() {
	$draft = get_option( DF_OPTION_DRAFT, null );
	if ( ! is_array( $draft ) ) {
		return false;
	}
	$live = get_option( DF_OPTION, array() );
	$live = array_merge( df_defaults(), is_array( $live ) ? $live : array() );
	return wp_json_encode( array_merge( df_defaults(), $draft ) ) !== wp_json_encode( $live );
}

/**
 * Varlıklar.
 *
 * @param string $hook Sayfa.
 */
function df_studio_assets( $hook ) {
	if ( 'toplevel_page_df-studio' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'df-admin', DF_URI . '/assets/admin/admin.css', array(), DF_VERSION );
	wp_enqueue_style( 'df-studio', DF_URI . '/assets/admin/studio.css', array( 'df-admin' ), DF_VERSION );
	$deps = array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' );
	if ( df_wc() ) {
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_script( 'wc-enhanced-select' );
		$deps[] = 'wc-enhanced-select';
	}
	wp_enqueue_script( 'df-studio', DF_URI . '/assets/admin/studio.js', $deps, DF_VERSION, true );
	$icons = array();
	foreach ( df_icon_library() as $key => $icon ) {
		$icons[ $key ] = df_icon( $key, array( 'size' => 24 ) );
	}
	wp_localize_script(
		'df-studio',
		'DFStudio',
		array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'df_studio' ),
			'home'    => home_url( '/' ),
			'option'  => DF_OPTION,
			'icons'   => $icons,
			'exit'    => df_wc() ? admin_url( 'admin.php?page=df-dashboard' ) : admin_url(),
			'labels'  => df_home_section_labels(),
			'changed' => df_studio_has_changes(),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'df_studio_assets' );

/**
 * Önizlenebilir sayfalar.
 *
 * @return array<string,string> URL => etiket
 */
function df_studio_pages() {
	$pages = array( home_url( '/' ) => 'Ana Sayfa' );
	foreach ( array( 'hakkimizda' => 'Hakkımızda', 'iletisim' => 'İletişim' ) as $slug => $label ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$pages[ get_permalink( $p ) ] = $label;
		}
	}
	if ( df_wc() ) {
		$shop = wc_get_page_permalink( 'shop' );
		if ( $shop ) {
			$pages[ $shop ] = 'Mağaza (ürün listesi)';
		}
		$cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 1,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( $cats && ! is_wp_error( $cats ) ) {
			$pages[ get_term_link( $cats[0] ) ] = 'Kategori: ' . $cats[0]->name;
		}
		$prod = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'ids',
			)
		);
		if ( $prod ) {
			$pages[ get_permalink( $prod[0] ) ] = 'Ürün: ' . get_the_title( $prod[0] );
		}
		$pages[ wc_get_page_permalink( 'cart' ) ]      = 'Sepet';
		$pages[ wc_get_page_permalink( 'myaccount' ) ] = 'Hesabım / Giriş';
	}
	$track = absint( df_opt( 'track_page' ) );
	if ( $track ) {
		$pages[ get_permalink( $track ) ] = 'Sipariş Takip';
	}
	$blog = absint( get_option( 'page_for_posts' ) );
	if ( $blog ) {
		$pages[ get_permalink( $blog ) ] = 'Blog';
	}
	return $pages;
}

/**
 * Tüm ayar gruplarını (taslak değerlerle) çizer.
 *
 * @param array $opts Değerler.
 */
function df_studio_render_groups( $opts ) {
	foreach ( df_options_schema() as $tab_id => $tab ) {
		foreach ( $tab['groups'] as $gi => $group ) {
			$sec = isset( $group['section'] ) ? $group['section'] : ( 'hero' === $tab_id && 0 === $gi ? 'hero' : '' );
			$ids = array();
			foreach ( $group['fields'] as $f ) {
				if ( ! empty( $f['id'] ) ) {
					$ids[] = $f['id'];
				}
			}
			// Bölüm sırası "Bölümler" sekmesinde ayrıca gösterilir.
			if ( 'home_sections' === implode( '', $ids ) ) {
				continue;
			}
			printf(
				'<div class="dfs-group" data-g="%1$s" data-tab="%2$s" data-section="%3$s" data-fields="%4$s" data-title="%5$s" hidden>',
				esc_attr( $tab_id . '-' . $gi ),
				esc_attr( $tab_id ),
				esc_attr( $sec ),
				esc_attr( implode( ' ', $ids ) ),
				esc_attr( $group['title'] )
			);
			if ( ! empty( $group['desc'] ) ) {
				echo '<p class="df-group__desc">' . wp_kses_post( $group['desc'] ) . '</p>';
			}
			echo '<div class="df-fields">';
			foreach ( $group['fields'] as $field ) {
				$value = isset( $opts[ $field['id'] ] ) ? $opts[ $field['id'] ] : '';
				df_render_field( $field, $value, DF_OPTION . '[' . $field['id'] . ']', 'dfs_' . $field['id'] );
			}
			echo '</div></div>';
		}
	}
}

/**
 * Stüdyo sayfası.
 */
function df_studio_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$opts   = df_studio_draft();
	$pages  = df_studio_pages();
	$schema = df_options_schema();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$start = isset( $_GET['url'] ) ? esc_url_raw( wp_unslash( $_GET['url'] ) ) : '';
	if ( ! $start || 0 !== strpos( $start, home_url() ) ) {
		$start = home_url( '/' );
	}
	?>
	<div class="dfs" id="dfs">
		<header class="dfs-top">
			<a class="dfs-brand" href="<?php echo esc_url( df_wc() ? admin_url( 'admin.php?page=df-dashboard' ) : admin_url() ); ?>" title="Yönetim Paneline dön">
				<span class="dfs-brand__logo"><?php df_the_icon( 'bouquet', array( 'size' => 22 ) ); ?></span>
				<span><strong>Tasarım Stüdyosu</strong><small><?php echo esc_html( df_opt( 'logo_text', 'Derin Flowers' ) ); ?></small></span>
			</a>
			<label class="dfs-page">
				<span class="screen-reader-text">Önizlenen sayfa</span>
				<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
				<select id="dfs-page">
					<?php foreach ( $pages as $url => $label ) : ?>
						<option value="<?php echo esc_attr( $url ); ?>" <?php selected( untrailingslashit( $url ), untrailingslashit( $start ) ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
					<option value="" data-custom hidden>Diğer sayfa</option>
				</select>
			</label>
			<div class="dfs-devices" role="group" aria-label="Önizleme boyutu">
				<button type="button" class="is-active" data-device="desktop" title="Masaüstü"><span class="dashicons dashicons-desktop"></span></button>
				<button type="button" data-device="tablet" title="Tablet"><span class="dashicons dashicons-tablet"></span></button>
				<button type="button" data-device="mobile" title="Mobil"><span class="dashicons dashicons-smartphone"></span></button>
			</div>
			<div class="dfs-history">
				<button type="button" id="dfs-undo" disabled title="Geri al (Ctrl+Z)"><span class="dashicons dashicons-undo"></span></button>
				<button type="button" id="dfs-redo" disabled title="İleri al (Ctrl+Y)"><span class="dashicons dashicons-redo"></span></button>
			</div>
			<span class="dfs-status" id="dfs-status" aria-live="polite"></span>
			<div class="dfs-actions">
				<a class="dfs-btn dfs-btn--ghost" id="dfs-open" href="<?php echo esc_url( $start ); ?>" target="_blank" rel="noopener" title="Yayındaki sayfayı yeni sekmede aç"><span class="dashicons dashicons-external"></span></a>
				<button type="button" class="dfs-btn dfs-btn--ghost" id="dfs-discard">Vazgeç</button>
				<button type="button" class="dfs-btn" id="dfs-publish" disabled>Yayınla</button>
				<a class="dfs-btn dfs-btn--icon" id="dfs-exit" href="<?php echo esc_url( df_wc() ? admin_url( 'admin.php?page=df-dashboard' ) : admin_url() ); ?>" title="Stüdyodan çık"><span class="dashicons dashicons-no-alt"></span></a>
			</div>
		</header>

		<div class="dfs-body">
			<aside class="dfs-side df-panel" id="dfs-side">
				<nav class="dfs-tabs" role="tablist">
					<button type="button" class="is-active" data-view="sections"><span class="dashicons dashicons-layout"></span>Sayfa</button>
					<button type="button" data-view="edit"><span class="dashicons dashicons-edit"></span>Düzenle</button>
					<button type="button" data-view="site"><span class="dashicons dashicons-admin-generic"></span>Site</button>
				</nav>

				<form id="dfs-form" autocomplete="off" onsubmit="return false">
					<!-- Bölümler -->
					<section class="dfs-view is-active" data-view="sections">
						<div id="dfs-context" class="dfs-context" hidden></div>
						<div id="dfs-home">
							<p class="dfs-help">Ana sayfa bölümlerini <strong>sürükleyerek</strong> sıralayın, anahtarla gizleyin. <strong>Düzenle</strong> ile içeriğini ve tasarımını açın. Önizlemede yazılara tıklayıp doğrudan yazabilir, görsellerin üzerindeki düğmeyle değiştirebilirsiniz.</p>
							<?php df_render_sections_field( $opts['home_sections'], DF_OPTION . '[home_sections]' ); ?>
						</div>
						<div class="dfs-shortcuts">
							<h3>Diğer alanlar</h3>
							<button type="button" data-open="header">Header & menü</button>
							<button type="button" data-open="footer">Footer & iletişim bilgileri</button>
							<button type="button" data-open="pages">Hakkımızda & İletişim sayfaları</button>
							<button type="button" data-open="appearance-1">Renkler</button>
							<button type="button" data-open="appearance-2">Yazı tipleri</button>
							<button type="button" data-open="product">Ürün sayfası & kartlar</button>
						</div>
					</section>

					<!-- Düzenle -->
					<section class="dfs-view" data-view="edit">
						<div class="dfs-edit-head">
							<button type="button" class="dfs-back" id="dfs-back" title="Geri"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
							<h2 id="dfs-edit-title">Bir alan seçin</h2>
						</div>
						<p class="dfs-help" id="dfs-edit-empty">Önizlemede bir yazıya, görsele ya da bölüme tıklayın; ayarları burada açılır. Ya da <strong>Sayfa</strong> / <strong>Site</strong> sekmesinden seçin.</p>
						<div class="dfs-groups">
							<?php df_studio_render_groups( $opts ); ?>
						</div>
					</section>

					<!-- Site -->
					<section class="dfs-view" data-view="site">
						<p class="dfs-help">Sitenin tüm ayarları. Bir başlığa tıklayın, değişiklik önizlemede hemen görünür.</p>
						<?php foreach ( $schema as $tab_id => $tab ) : ?>
							<?php
							if ( 'home' === $tab_id ) {
								continue;
							}
							?>
							<div class="dfs-nav">
								<h3><span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span><?php echo esc_html( $tab['title'] ); ?></h3>
								<?php foreach ( $tab['groups'] as $gi => $group ) : ?>
									<button type="button" data-open="<?php echo esc_attr( $tab_id . '-' . $gi ); ?>"><?php echo esc_html( $group['title'] ); ?><span class="dashicons dashicons-arrow-right-alt2"></span></button>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
						<div class="dfs-nav">
							<h3><span class="dashicons dashicons-admin-links"></span>WordPress</h3>
							<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" target="_blank" rel="noopener">Menüler<span class="dashicons dashicons-external"></span></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=derin-flowers-tools' ) ); ?>" target="_blank" rel="noopener">Araçlar, yedek & hazır düzen<span class="dashicons dashicons-external"></span></a>
						</div>
					</section>
				</form>
			</aside>

			<main class="dfs-stage" id="dfs-stage">
				<div class="dfs-frame-wrap" id="dfs-frame-wrap" data-device="desktop">
					<iframe id="dfs-frame" title="Site önizlemesi" data-src="<?php echo esc_url( $start ); ?>"></iframe>
					<div class="dfs-loading" id="dfs-loading"><span></span>Önizleme yükleniyor…</div>
				</div>
			</main>
		</div>
		<div class="dfs-modal" id="dfs-modal" hidden>
			<div class="dfs-modal__box" role="dialog" aria-modal="true" aria-labelledby="dfs-modal-title">
				<header class="dfs-modal__head">
					<strong id="dfs-modal-title">Düzenle</strong>
					<span class="dfs-modal__hint">Değişiklikleri editörün kendi <em>Güncelle</em> düğmesiyle kaydedin.</span>
					<button type="button" class="dfs-btn" id="dfs-modal-close">Kapat ve önizlemeyi yenile</button>
				</header>
				<iframe id="dfs-modal-frame" title="Editör"></iframe>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Ortak yetki kontrolü.
 */
function df_studio_check() {
	check_ajax_referer( 'df_studio', 'nonce' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
	}
}

/**
 * Formdan gelen seçenekleri taslak olarak kaydet.
 */
function df_studio_ajax_draft() {
	df_studio_check();
	$raw = isset( $_POST['form'] ) ? (string) wp_unslash( $_POST['form'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- alan alan temizlenir.
	$in  = array();
	parse_str( $raw, $in );
	if ( empty( $in[ DF_OPTION ] ) || ! is_array( $in[ DF_OPTION ] ) ) {
		wp_send_json_error( array( 'message' => 'Form verisi okunamadı.' ) );
	}
	// Formda olmayan (ör. başka araçlarla eklenen) anahtarlar korunur.
	$clean = df_sanitize_options( $in[ DF_OPTION ] );
	$draft = array_merge( df_studio_draft( false ), $clean );
	update_option( DF_OPTION_DRAFT, $draft, false );
	wp_send_json_success( array( 'changed' => df_studio_has_changes() ) );
}
add_action( 'wp_ajax_df_studio_draft', 'df_studio_ajax_draft' );

/**
 * Taslağın güncel formu (geri al / ileri al sonrası).
 */
function df_studio_ajax_form() {
	df_studio_check();
	$opts = df_studio_draft();
	ob_start();
	df_studio_render_groups( $opts );
	$groups = ob_get_clean();
	ob_start();
	df_render_sections_field( $opts['home_sections'], DF_OPTION . '[home_sections]' );
	$sections = ob_get_clean();
	wp_send_json_success(
		array(
			'groups'   => $groups,
			'sections' => $sections,
		)
	);
}
add_action( 'wp_ajax_df_studio_form', 'df_studio_ajax_form' );

/**
 * Yayınla.
 */
function df_studio_ajax_publish() {
	df_studio_check();
	$draft = get_option( DF_OPTION_DRAFT, null );
	if ( ! is_array( $draft ) ) {
		wp_send_json_error( array( 'message' => 'Yayınlanacak değişiklik yok.' ) );
	}
	$draft['__df_clean'] = 1;
	update_option( DF_OPTION, $draft );
	delete_option( DF_OPTION_DRAFT );
	// Sayfa önbelleği eklentileri için.
	do_action( 'litespeed_purge_all' );
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	wp_send_json_success( array( 'message' => 'Yayınlandı' ) );
}
add_action( 'wp_ajax_df_studio_publish', 'df_studio_ajax_publish' );

/**
 * Vazgeç: taslağı sil.
 */
function df_studio_ajax_discard() {
	df_studio_check();
	delete_option( DF_OPTION_DRAFT );
	wp_send_json_success();
}
add_action( 'wp_ajax_df_studio_discard', 'df_studio_ajax_discard' );

/**
 * Sayfa listesinde "Tasarım Stüdyosu'nda aç" bağlantısı.
 *
 * @param array   $actions Bağlantılar.
 * @param WP_Post $post    Sayfa.
 * @return array
 */
function df_studio_row_action( $actions, $post ) {
	if ( 'publish' === $post->post_status && current_user_can( 'edit_theme_options' ) ) {
		$actions['df_studio'] = '<a href="' . esc_url( df_studio_url( get_permalink( $post ) ) ) . '" style="font-weight:600">Tasarım Stüdyosu\'nda aç</a>';
	}
	return $actions;
}
add_filter( 'page_row_actions', 'df_studio_row_action', 10, 2 );

/**
 * Hızlı düzenleme (ürün / kategori / sayfa). Bu değişiklikler taslak değildir, hemen kaydedilir.
 */
function df_studio_ajax_quick() {
	check_ajax_referer( 'df_studio', 'nonce' );
	$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$f    = isset( $_POST['f'] ) && is_array( $_POST['f'] ) ? wp_unslash( $_POST['f'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- alan alan temizlenir.

	if ( 'product' === $type ) {
		$p = df_wc() ? wc_get_product( $id ) : null;
		if ( ! $p || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
		}
		if ( isset( $f['title'] ) && '' !== trim( $f['title'] ) ) {
			$p->set_name( sanitize_text_field( $f['title'] ) );
		}
		if ( ! $p->is_type( 'variable' ) ) {
			if ( isset( $f['regular'] ) ) {
				$p->set_regular_price( wc_format_decimal( sanitize_text_field( $f['regular'] ) ) );
			}
			if ( isset( $f['sale'] ) ) {
				$sale = wc_format_decimal( sanitize_text_field( $f['sale'] ) );
				if ( '' !== $sale && (float) $sale >= (float) $p->get_regular_price( 'edit' ) ) {
					wp_send_json_error( array( 'message' => 'İndirimli fiyat normal fiyattan düşük olmalı.' ) );
				}
				$p->set_sale_price( $sale );
			}
		}
		if ( isset( $f['short'] ) ) {
			$p->set_short_description( wp_kses_post( $f['short'] ) );
		}
		if ( isset( $f['stock'] ) && array_key_exists( $f['stock'], wc_get_product_stock_status_options() ) ) {
			$p->set_stock_status( $f['stock'] );
		}
		if ( isset( $f['image'] ) ) {
			$p->set_image_id( absint( $f['image'] ) );
		}
		$p->save();
		wp_send_json_success( array( 'message' => 'Ürün kaydedildi' ) );
	}

	if ( 'term' === $type ) {
		$tax = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
		if ( ! taxonomy_exists( $tax ) || ! current_user_can( 'edit_term', $id ) ) {
			wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
		}
		$args = array();
		if ( isset( $f['title'] ) && '' !== trim( $f['title'] ) ) {
			$args['name'] = sanitize_text_field( $f['title'] );
		}
		if ( isset( $f['desc'] ) ) {
			$args['description'] = wp_kses_post( $f['desc'] );
		}
		$res = wp_update_term( $id, $tax, $args );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		if ( 'product_cat' === $tax && isset( $f['image'] ) ) {
			update_term_meta( $id, 'thumbnail_id', absint( $f['image'] ) );
		}
		wp_send_json_success( array( 'message' => 'Kategori kaydedildi' ) );
	}

	if ( 'post' === $type ) {
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
		}
		if ( isset( $f['title'] ) && '' !== trim( $f['title'] ) ) {
			wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => sanitize_text_field( $f['title'] ),
				)
			);
		}
		wp_send_json_success( array( 'message' => 'Kaydedildi' ) );
	}
	wp_send_json_error( array( 'message' => 'Bilinmeyen içerik.' ) );
}
add_action( 'wp_ajax_df_studio_quick', 'df_studio_ajax_quick' );
