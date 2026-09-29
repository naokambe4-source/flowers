<?php
/**
 * Sayfa başlığı alanı.
 *
 * @package DerinFlowers
 *
 * @var array $args title, sub, eyebrow.
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args(
	$args,
	array(
		'title'   => '',
		'sub'     => '',
		'eyebrow' => '',
		'crumbs'  => true,
	)
);
?>
<header class="df-pagehead">
	<div class="df-container">
		<?php
		if ( $args['crumbs'] && function_exists( 'woocommerce_breadcrumb' ) ) {
			woocommerce_breadcrumb();
		}
		?>
		<?php if ( $args['eyebrow'] ) : ?>
			<p class="df-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
		<?php endif; ?>
		<h1 class="df-pagehead__title"><?php echo esc_html( $args['title'] ); ?></h1>
		<?php if ( $args['sub'] ) : ?>
			<div class="df-pagehead__sub"><?php echo wp_kses_post( $args['sub'] ); ?></div>
		<?php endif; ?>
	</div>
</header>
