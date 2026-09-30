<?php
/**
 * "Derin Flowers" tema yönetim paneli.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menü.
 */
function df_admin_menu() {
	add_menu_page(
		'Derin Flowers Tema Paneli',
		'Derin Flowers',
		'edit_theme_options',
		'derin-flowers',
		'df_admin_page',
		'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="1.6" stroke-linecap="round"><circle cx="8.5" cy="7" r="2.6"/><circle cx="15.5" cy="7" r="2.6"/><circle cx="12" cy="4.8" r="2.3"/><path d="M8 11.5h8l-2.6 3.1h-2.8L8 11.5Z"/><path d="M10.6 14.6 9.5 21h5l-1.1-6.4"/></svg>' ), // phpcs:ignore
		58
	);
	add_submenu_page( 'derin-flowers', 'Tema Ayarları', 'Tema Ayarları', 'edit_theme_options', 'derin-flowers', 'df_admin_page' );
	add_submenu_page( 'derin-flowers', 'Araçlar & Kurulum', 'Araçlar & Kurulum', 'edit_theme_options', 'derin-flowers-tools', 'df_tools_page' );
}
add_action( 'admin_menu', 'df_admin_menu', 9 );

/**
 * Ayar kaydı.
 */
function df_admin_register_setting() {
	register_setting(
		'df_options_group',
		DF_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'df_sanitize_options',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'df_admin_register_setting' );

/**
 * Seçenekler kaydedildiğinde WooCommerce ayarlarını eşitle.
 *
 * @param mixed $old Eski.
 * @param mixed $new Yeni.
 */
function df_sync_wc_settings( $old, $new ) {
	// Yayındaki ayar değişti: Tasarım Stüdyosu'ndaki eski taslak geçersiz.
	delete_option( DF_OPTION_DRAFT );
	if ( ! is_array( $new ) || ! df_wc() ) {
		return;
	}
	if ( isset( $new['reg_on'] ) ) {
		update_option( 'woocommerce_enable_myaccount_registration', $new['reg_on'] ? 'yes' : 'no' );
	}
	if ( isset( $new['shop_columns'] ) ) {
		update_option( 'woocommerce_catalog_columns', absint( $new['shop_columns'] ) );
	}
}
add_action( 'update_option_' . DF_OPTION, 'df_sync_wc_settings', 10, 2 );

/**
 * Panel varlıkları.
 *
 * @param string $hook Sayfa.
 */
function df_admin_assets( $hook ) {
	$is_panel = false !== strpos( $hook, 'derin-flowers' );
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'product' === get_post_type() ) {
		wp_enqueue_media();
		wp_enqueue_style( 'df-admin', DF_URI . '/assets/admin/admin.css', array(), DF_VERSION );
		wp_enqueue_script( 'df-product-admin', DF_URI . '/assets/admin/product.js', array( 'jquery', 'jquery-ui-sortable' ), DF_VERSION, true );
	}
	if ( 'nav-menus.php' === $hook || 'edit-tags.php' === $hook || 'term.php' === $hook ) {
		wp_enqueue_media();
	}
	if ( ! $is_panel ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'df-admin', DF_URI . '/assets/admin/admin.css', array(), DF_VERSION );
	$deps = array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' );
	if ( df_wc() ) {
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_script( 'wc-enhanced-select' );
		$deps[] = 'wc-enhanced-select';
	}
	wp_enqueue_script( 'df-admin', DF_URI . '/assets/admin/admin.js', $deps, DF_VERSION, true );
	$icons = array();
	foreach ( df_icon_library() as $key => $icon ) {
		$icons[ $key ] = df_icon( $key, array( 'size' => 24 ) );
	}
	wp_localize_script( 'df-admin', 'DFAdmin', array( 'icons' => $icons ) );
}
add_action( 'admin_enqueue_scripts', 'df_admin_assets' );

/**
 * Panel sayfası.
 */
function df_admin_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$schema   = df_options_schema();
	$opts     = df_options();
	$sections = array();
	foreach ( (array) $opts['home_sections'] as $row ) {
		if ( ! empty( $row['id'] ) ) {
			$sections[ $row['id'] ] = ! empty( $row['on'] );
		}
	}
	?>
	<div class="wrap df-panel">
		<h1 class="screen-reader-text">Derin Flowers Tema Paneli</h1>
		<div class="df-panel__top">
			<div class="df-panel__brand">
				<span class="df-panel__logo"><?php df_the_icon( 'bouquet', array( 'size' => 30 ) ); ?></span>
				<div>
					<strong>Derin Flowers</strong>
					<span>Tema Paneli · v<?php echo esc_html( DF_VERSION ); ?></span>
				</div>
			</div>
			<div class="df-panel__links">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=df-studio' ) ); ?>">Tasarım Stüdyosu'nda aç</a>
				<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">Siteyi görüntüle</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Menüler</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=derin-flowers-tools' ) ); ?>">Araçlar & Kurulum</a>
			</div>
		</div>

		<?php settings_errors(); ?>

		<form method="post" action="options.php" class="df-panel__form" id="df-panel-form">
			<?php settings_fields( 'df_options_group' ); ?>
			<input type="hidden" name="df_active_tab" id="df-active-tab" value="">
			<div class="df-panel__layout">
				<nav class="df-panel__nav" aria-label="Panel sekmeleri">
					<?php foreach ( $schema as $tab_id => $tab ) : ?>
						<a href="#<?php echo esc_attr( $tab_id ); ?>" class="df-panel__tab" data-tab="<?php echo esc_attr( $tab_id ); ?>">
							<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span><?php echo esc_html( $tab['title'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<div class="df-panel__content">
					<?php foreach ( $schema as $tab_id => $tab ) : ?>
						<section class="df-panel__pane" id="df-pane-<?php echo esc_attr( $tab_id ); ?>" data-pane="<?php echo esc_attr( $tab_id ); ?>">
							<h2 class="df-panel__title"><?php echo esc_html( $tab['title'] ); ?></h2>
							<?php foreach ( $tab['groups'] as $gi => $group ) : ?>
								<?php
								$sec_id  = isset( $group['section'] ) ? $group['section'] : '';
								$sec_off = $sec_id && isset( $sections[ $sec_id ] ) && ! $sections[ $sec_id ];
								?>
								<div class="df-group<?php echo $sec_id ? ' df-group--collapsible is-collapsed' : ''; ?>" <?php echo $sec_id ? 'id="df-group-' . esc_attr( $sec_id ) . '" data-section="' . esc_attr( $sec_id ) . '"' : ''; ?>>
									<div class="df-group__head">
										<h3><?php echo esc_html( $group['title'] ); ?></h3>
										<?php if ( $sec_id ) : ?>
											<span class="df-badge <?php echo $sec_off ? 'is-off' : 'is-on'; ?>"><?php echo $sec_off ? 'Kapalı' : 'Açık'; ?></span>
											<span class="dashicons dashicons-arrow-down-alt2 df-group__chev"></span>
										<?php endif; ?>
									</div>
									<div class="df-group__body">
										<?php if ( ! empty( $group['desc'] ) ) : ?>
											<p class="df-group__desc"><?php echo wp_kses_post( $group['desc'] ); ?></p>
										<?php endif; ?>
										<div class="df-fields">
											<?php
											foreach ( $group['fields'] as $field ) {
												$value = isset( $opts[ $field['id'] ] ) ? $opts[ $field['id'] ] : '';
												df_render_field( $field, $value, DF_OPTION . '[' . $field['id'] . ']', 'df_' . $field['id'] );
											}
											?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</section>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="df-panel__savebar">
				<span class="df-panel__hint">Değişiklikler kaydedildiğinde sitede hemen uygulanır.</span>
				<?php submit_button( 'Değişiklikleri Kaydet', 'primary large', 'submit', false ); ?>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Panel kaydedildikten sonra sekmeye geri dön.
 *
 * @param string $location Yönlendirme.
 * @return string
 */
function df_admin_redirect_keep_tab( $location ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( isset( $_POST['option_page'] ) && 'df_options_group' === $_POST['option_page'] && ! empty( $_POST['df_active_tab'] ) ) {
		$location .= '#' . sanitize_key( wp_unslash( $_POST['df_active_tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
	return $location;
}
add_filter( 'wp_redirect', 'df_admin_redirect_keep_tab' );
