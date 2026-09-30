<?php
/**
 * Yönetim menüsü ve Tema Ayarları sayfası.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menüler.
 */
function cr_admin_menu() {
	$heart = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M10 17.5s-6.5-4-7.8-8.3A4.3 4.3 0 0 1 10 5.9a4.3 4.3 0 0 1 7.8 3.3C16.5 13.5 10 17.5 10 17.5z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	add_menu_page( 'Cilt Rotası', 'Cilt Rotası', 'edit_posts', 'cilt-rotasi', 'cr_dashboard_page', $heart, 2 );
	add_submenu_page( 'cilt-rotasi', 'Kontrol Paneli', 'Kontrol Paneli', 'edit_posts', 'cilt-rotasi', 'cr_dashboard_page' );
	add_submenu_page( 'cilt-rotasi', 'Tema Ayarları', 'Tema Ayarları', 'edit_theme_options', 'cilt-rotasi-ayarlar', 'cr_settings_page' );
	add_submenu_page( 'cilt-rotasi', 'SEO · AEO · GEO', 'SEO · AEO · GEO', 'edit_theme_options', 'admin.php?page=cilt-rotasi-ayarlar&tab=seo' );
	add_submenu_page( 'cilt-rotasi', 'SEO Denetimi', 'SEO Denetimi', 'edit_posts', 'cilt-rotasi-seo', 'cr_seo_audit_page' );
	add_submenu_page( 'cilt-rotasi', 'Canlı Düzenle', '✎ Canlı Düzenle', 'edit_theme_options', add_query_arg( 'cr_edit', '1', home_url( '/' ) ) );
	add_submenu_page( 'cilt-rotasi', 'Bülten Aboneleri', 'Bülten Aboneleri', 'edit_theme_options', 'cilt-rotasi-aboneler', 'cr_subscribers_page' );
	add_submenu_page( 'cilt-rotasi', 'Arama Analitiği', 'Arama Analitiği', 'edit_posts', 'cilt-rotasi-aramalar', 'cr_searches_page' );
	add_submenu_page( 'cilt-rotasi', 'Araçlar & Kurulum', 'Araçlar & Kurulum', 'edit_theme_options', 'cilt-rotasi-araclar', 'cr_tools_page' );
}
add_action( 'admin_menu', 'cr_admin_menu' );

/**
 * Yönetim varlıkları.
 *
 * @param string $hook Sayfa.
 */
function cr_admin_assets( $hook ) {
	$is_cr = false !== strpos( (string) $hook, 'cilt-rotasi' );
	wp_enqueue_style( 'cr-admin-fonts', 'https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap&subset=latin-ext', array(), null ); // phpcs:ignore
	wp_enqueue_style( 'cr-admin', CR_URI . '/assets/admin/admin.css', array(), CR_VERSION );
	if ( $is_cr || in_array( $hook, array( 'post.php', 'post-new.php', 'term.php', 'edit-tags.php', 'profile.php', 'user-edit.php' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'cr-admin', CR_URI . '/assets/admin/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), CR_VERSION, true );
		wp_localize_script(
			'cr-admin',
			'CRAdmin',
			array(
				'home'  => home_url( '/' ),
				'site'  => get_bloginfo( 'name' ),
				'sep'   => cr_opt( 'seo_sep', '—' ),
				'hearts'=> cr_opt( 'adm_hearts' ) ? 1 : 0,
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'cr_admin_assets' );

/**
 * Ayarları kaydet.
 */
function cr_save_settings() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	check_admin_referer( 'cr_settings' );
	$tab   = isset( $_POST['cr_tab'] ) ? sanitize_key( wp_unslash( $_POST['cr_tab'] ) ) : 'brand';
	$input = isset( $_POST['cr'] ) && is_array( $_POST['cr'] ) ? wp_unslash( $_POST['cr'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cr_sanitize_tab ile temizlenir.
	cr_snapshot( 'Tema ayarları: ' . $tab );
	$saved = get_option( CR_OPTION, array() );
	$saved = is_array( $saved ) ? $saved : array();
	$saved = array_merge( $saved, cr_sanitize_tab( $tab, $input ) );
	update_option( CR_OPTION, $saved );
	cr_flush_options_cache();
	cr_purge_caches();
	wp_safe_redirect( add_query_arg( array( 'page' => 'cilt-rotasi-ayarlar', 'tab' => $tab, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_cr_save_settings', 'cr_save_settings' );

/**
 * Panel üst şeridi.
 *
 * @param string $title Başlık.
 * @param string $sub   Alt başlık.
 */
function cr_admin_header( $title, $sub = '' ) {
	?>
	<div class="cr-admin-top">
		<div class="cr-admin-top__brand">
			<span class="cr-admin-top__logo">♥</span>
			<div>
				<p class="cr-admin-top__eyebrow"><?php echo esc_html( cr_opt( 'adm_greeting' ) ); ?></p>
				<h1 class="cr-admin-top__title"><?php echo esc_html( $title ); ?></h1>
				<?php if ( $sub ) : ?>
					<p class="cr-admin-top__sub"><?php echo esc_html( $sub ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<div class="cr-admin-top__actions">
			<a class="cr-abtn cr-abtn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Siteyi gör ↗</a>
			<a class="cr-abtn" href="<?php echo esc_url( add_query_arg( 'cr_edit', '1', home_url( '/' ) ) ); ?>">✎ Canlı düzenle</a>
		</div>
	</div>
	<?php
}

/**
 * Tema Ayarları sayfası.
 */
function cr_settings_page() {
	$schema = cr_options_schema();
	$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'brand'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! isset( $schema[ $tab ] ) ) {
		$tab = 'brand';
	}
	$opts = cr_options();
	?>
	<div class="wrap cr-admin">
		<?php cr_admin_header( 'Tema Ayarları', 'Sitenin her köşesini buradan ya da canlı düzenleyiciden yönetebilirsin.' ); ?>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="cr-notice cr-notice--ok">✓ Kaydedildi. Değişiklikler sitede yayında.</div>
		<?php endif; ?>
		<?php if ( 'seo' === $tab && cr_seo_plugin() ) : ?>
			<div class="cr-notice"><?php echo esc_html( cr_seo_plugin() ); ?> etkin: tema meta etiketlerini ve genel şemayı otomatik olarak devre dışı bıraktı. Sözlük (DefinedTerm), SSS, HowTo ve ürün şemaları, llms.txt ve robots.txt kuralları çalışmaya devam eder.</div>
		<?php endif; ?>
		<div class="cr-settings">
			<nav class="cr-tabs" aria-label="Ayar sekmeleri">
				<div class="cr-tabs__search"><input type="search" placeholder="Ayar ara…" data-settings-search aria-label="Ayar ara"></div>
				<?php foreach ( $schema as $key => $t ) : ?>
					<a class="cr-tabs__item<?php echo $key === $tab ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'cilt-rotasi-ayarlar', 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>">
						<span class="cr-tabs__icon"><?php echo cr_icon( $t['icon'], 18 ); // phpcs:ignore ?></span><?php echo esc_html( $t['title'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<form class="cr-settings__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cr_save_settings">
				<input type="hidden" name="cr_tab" value="<?php echo esc_attr( $tab ); ?>">
				<?php wp_nonce_field( 'cr_settings' ); ?>
				<?php foreach ( $schema[ $tab ]['groups'] as $group ) : ?>
					<section class="cr-card-a">
						<header class="cr-card-a__head">
							<h2><?php echo esc_html( $group['title'] ); ?></h2>
							<?php if ( ! empty( $group['desc'] ) ) : ?>
								<p><?php echo esc_html( $group['desc'] ); ?></p>
							<?php endif; ?>
						</header>
						<div class="cr-card-a__body">
							<?php
							foreach ( $group['fields'] as $f ) {
								cr_field_row( $f, isset( $opts[ $f['id'] ] ) ? $opts[ $f['id'] ] : '' );
							}
							?>
						</div>
					</section>
				<?php endforeach; ?>
				<div class="cr-savebar">
					<span class="cr-savebar__hint" data-dirty-hint>Değişiklikleri kaydetmeyi unutma.</span>
					<?php if ( 'home' === $tab || 'hero' === $tab ) : ?>
						<a class="cr-abtn cr-abtn--ghost" href="<?php echo esc_url( add_query_arg( 'cr_edit', '1', home_url( '/' ) ) ); ?>">Ana sayfada canlı düzenle</a>
					<?php endif; ?>
					<button type="submit" class="cr-abtn cr-abtn--lg">Kaydet</button>
				</div>
			</form>
		</div>
	</div>
	<?php
}
