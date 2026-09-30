<?php
/**
 * İçindekiler listesi.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$toc = isset( $args['toc'] ) ? $args['toc'] : array();
if ( ! $toc ) {
	return;
}
?>
<ol class="cr-toc" data-toc>
	<?php foreach ( $toc as $h ) : ?>
		<li class="cr-toc__item cr-toc__item--h<?php echo (int) $h['level']; ?>"><a href="#<?php echo esc_attr( $h['id'] ); ?>"><?php echo esc_html( $h['text'] ); ?></a></li>
	<?php endforeach; ?>
</ol>
