<?php
/**
 * Sektör panelleri (masaüstünde genişleyen akordeon, mobilde dikey kartlar).
 *
 * @package CanEloksal
 *
 * @var array $args heading (h2|h3).
 */

defined( 'ABSPATH' ) || exit;

$ce_sectors = ce_get_items( 'ce_sector', 8 );
if ( ! $ce_sectors ) {
	return;
}
$ce_h = $args['heading'] ?? 'h3';
?>
<div class="ce-sectors" data-sectors>
	<?php foreach ( $ce_sectors as $ce_i => $ce_s ) : ?>
		<?php
		$ce_link = ce_meta( $ce_s->ID, 'link' );
		$ce_num  = ce_meta( $ce_s->ID, 'number', sprintf( '%02d', $ce_i + 1 ) );
		$ce_tag  = $ce_link ? 'a' : 'div';
		?>
		<<?php echo esc_html( $ce_tag ); ?> class="ce-sector<?php echo 0 === $ce_i ? ' is-open' : ''; ?>" <?php echo $ce_link ? 'href="' . esc_url( ce_url( $ce_link ) ) . '"' : 'tabindex="0"'; ?> data-reveal style="--i:<?php echo (int) $ce_i; ?>">
			<span class="ce-sector__media"><?php echo ce_post_visual( $ce_s->ID, 'ce-tall', ce_meta( $ce_s->ID, 'tone', 'graphite' ), array( 'sizes' => '(min-width: 1024px) 40vw, 100vw' ) ); // phpcs:ignore ?></span>
			<span class="ce-sector__body">
				<span class="ce-sector__num"><?php echo esc_html( $ce_num ); ?></span>
				<<?php echo esc_html( $ce_h ); ?> class="ce-sector__title"><?php echo esc_html( get_the_title( $ce_s ) ); ?></<?php echo esc_html( $ce_h ); ?>>
				<?php if ( has_excerpt( $ce_s ) ) : ?>
					<span class="ce-sector__text"><?php echo esc_html( get_the_excerpt( $ce_s ) ); ?></span>
				<?php endif; ?>
			</span>
		</<?php echo esc_html( $ce_tag ); ?>>
	<?php endforeach; ?>
</div>
