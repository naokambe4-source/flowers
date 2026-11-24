<?php
/**
 * Ürün ekleme / düzenleme ekranı sabit düzen: kutular sürüklenip yer değiştirmez, kapanıp küçülmez,
 * ekran tek sütuna düşmez. Rank Math "Content AI" kapalı (ürün ekranındaki kalabalığı ve gereksiz istekleri kaldırır).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ürün ekranında kullanıcıya kaydedilmiş kutu düzenini yok say: herkes aynı, varsayılan düzeni görür.
 */
add_filter(
	'get_user_option_meta-box-order_product',
	function () {
		// Sağ sütun: önce ürün görseli ve galerisi, sonra kategoriler, etiketler; Yayınla en altta.
		return array(
			'side'     => 'postimagediv,woocommerce-product-images,product_catdiv,tagsdiv-product_tag,submitdiv',
			'normal'   => '',
			'advanced' => '',
		);
	}
);
add_filter( 'get_user_option_closedpostboxes_product', '__return_empty_array' );
add_filter( 'get_user_option_metaboxhidden_product', '__return_empty_array' );
add_filter(
	'get_user_option_screen_layout_product',
	function () {
		return 2;
	}
);

/**
 * Kutuların sürüklenmesini, kapanmasını ve sıra değiştirme oklarını kapat.
 */
function df_ps_lock() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'product' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}
	?>
	<style>
		.post-type-product .postbox .handle-actions .handle-order-higher,
		.post-type-product .postbox .handle-actions .handle-order-lower,
		.post-type-product .postbox .handle-actions .handlediv { display: none !important; }
		.post-type-product .postbox .postbox-header .hndle { cursor: default !important; }
		.post-type-product .df-top-boxes { margin: 16px 0 4px; }
		/* Telefonda başlığa dokununca kutu kapanmasın (Ürün Asistanı kaybolmasın). */
		.post-type-product .postbox.closed .inside { display: block !important; }
		.post-type-product .postbox .postbox-header .hndle { pointer-events: none; }
	</style>
	<script>
	jQuery( window ).on( 'load', function () {
		// Kutuları sürükleme kapalı (WordPress'in kendi sıralayıcısı varsa yalnızca devre dışı bırakılır).
		jQuery( '.meta-box-sortables.ui-sortable' ).sortable( 'option', 'disabled', true );
		jQuery( '.postbox' ).removeClass( 'closed' );
		jQuery( document ).on( 'postbox-toggled', function ( e, box ) { jQuery( box ).removeClass( 'closed' ); } );
	} );
	</script>
	<?php
}
add_action( 'admin_head', 'df_ps_lock' );

/**
 * Rank Math Content AI modülünü kapat (ürün / sayfa ekranlarında görünmesin).
 *
 * @param mixed $mods Etkin modüller.
 * @return mixed
 */
function df_ps_no_content_ai( $mods ) {
	return is_array( $mods ) ? array_values( array_diff( $mods, array( 'content-ai' ) ) ) : $mods;
}
add_filter( 'option_rank_math_modules', 'df_ps_no_content_ai' );

/**
 * Yönetim panelinin tamamında rakamlar düz görünsün (başlık yazı tiplerinde "eski stil" rakam olmasın).
 */
function df_admin_lining_nums() {
	echo '<style>body.wp-admin,body.wp-admin input,body.wp-admin textarea,body.wp-admin select,body.wp-admin h1,body.wp-admin h2,body.wp-admin h3{font-variant-numeric:lining-nums;font-feature-settings:"lnum" 1}</style>';
}
add_action( 'admin_head', 'df_admin_lining_nums' );

