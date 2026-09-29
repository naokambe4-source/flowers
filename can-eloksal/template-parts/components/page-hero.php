<?php
/**
 * İç sayfa hero'su: koyu zemin, breadcrumb, başlık, alt başlık, opsiyonel görsel.
 *
 * @package CanEloksal
 *
 * @var array $args title, subtitle, eyebrow, image (ek ID), tone, size (md|lg), meta (HTML).
 */

defined( 'ABSPATH' ) || exit;

$ce_image = absint( $args['image'] ?? 0 );
$ce_size  = $args['size'] ?? 'md';
?>
<section class="ce-phero ce-phero--<?php echo esc_attr( $ce_size ); ?><?php echo $ce_image ? ' has-image' : ''; ?>">
	<div class="ce-phero__media" aria-hidden="true">
		<?php if ( $ce_image ) : ?>
			<?php echo ce_img( $ce_image, 'ce-hero', array( 'sizes' => '100vw', 'loading' => 'eager', 'alt' => '' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<span class="ce-phero__art ce-phero__art--<?php echo esc_attr( $args['tone'] ?? 'steel' ); ?>"></span>
		<?php endif; ?>
	</div>
	<div class="ce-container ce-phero__inner">
		<?php ce_breadcrumbs( 'ce-breadcrumb--light' ); ?>
		<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
			<p class="ce-eyebrow ce-eyebrow--light"><?php echo esc_html( $args['eyebrow'] ); ?></p>
		<?php endif; ?>
		<h1 class="ce-phero__title"><?php echo esc_html( $args['title'] ?? '' ); ?></h1>
		<?php if ( ! empty( $args['subtitle'] ) ) : ?>
			<p class="ce-phero__sub"><?php echo esc_html( $args['subtitle'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $args['meta'] ) ) : ?>
			<div class="ce-phero__meta"><?php echo wp_kses_post( $args['meta'] ); ?></div>
		<?php endif; ?>
	</div>
</section>
