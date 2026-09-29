<?php
/**
 * Arama formu (blog içinde arar).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;
$ce_sid = wp_unique_id( 'ce-s-' );
?>
<form role="search" method="get" class="ce-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $ce_sid ); ?>">Blogda ara</label>
	<?php echo ce_icon( 'search', 18, 'ce-searchform__icon' ); // phpcs:ignore ?>
	<input type="search" id="<?php echo esc_attr( $ce_sid ); ?>" class="ce-searchform__input" placeholder="Blogda ara…" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" minlength="2">
	<input type="hidden" name="post_type" value="post">
	<button type="submit" class="ce-searchform__btn">Ara</button>
</form>
