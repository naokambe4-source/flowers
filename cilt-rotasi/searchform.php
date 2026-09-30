<?php
/**
 * Arama formu.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$cr_sid = wp_unique_id( 'cr-sf-' );
?>
<form class="cr-bigsearch cr-bigsearch--sm" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<?php echo cr_icon( 'search', 20 ); // phpcs:ignore ?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $cr_sid ); ?>">Ara</label>
	<input id="<?php echo esc_attr( $cr_sid ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( cr_opt( 'search_placeholder' ) ); ?>">
	<button class="cr-btn cr-btn--primary cr-btn--sm" type="submit">Ara</button>
</form>
