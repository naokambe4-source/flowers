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
add_filter( 'get_user_option_meta-box-order_product', '__return_false' );
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
	</style>
	<script>
	jQuery( window ).on( 'load', function () {
		// Kutuları sürükleme kapalı (WordPress'in kendi sıralayıcısı varsa yalnızca devre dışı bırakılır).
		jQuery( '.meta-box-sortables.ui-sortable' ).sortable( 'option', 'disabled', true );
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
