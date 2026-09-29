<?php
/**
 * Arama formu.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$df_uid = wp_unique_id( 'df-s-' );
?>
<form role="search" method="get" class="df-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $df_uid ); ?>">Ara</label>
	<input type="search" id="<?php echo esc_attr( $df_uid ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Ürün veya yazı arayın…">
	<?php if ( df_wc() ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<button type="submit" aria-label="Ara"><?php df_the_icon( 'search', array( 'size' => 20 ) ); ?></button>
</form>
