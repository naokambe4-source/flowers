<?php
/**
 * Site başlığı: şeffaf → kaydırınca yapışkan/bulanık header ve mobil çekmece menü.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;
$ce_cta_label = ce_opt( 'header_cta_label' );
$ce_cta_url   = ce_opt( 'header_cta_url' ) ? ce_url( ce_opt( 'header_cta_url' ) ) : ce_quote_url();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="ce-skip" href="#main">İçeriğe geç</a>

<?php if ( ! ce_elementor_location( 'header' ) ) : ?>
<header class="ce-header" data-header>
	<div class="ce-header__inner">
		<?php ce_logo( 'header' ); ?>

		<nav class="ce-nav" aria-label="Ana menü">
			<?php ce_render_header_menu(); ?>
		</nav>

		<div class="ce-header__actions">
			<?php if ( ce_opt( 'phone' ) ) : ?>
				<a class="ce-header__phone" href="<?php echo esc_attr( ce_tel() ); ?>">
					<?php echo ce_icon( 'phone', 16 ); // phpcs:ignore ?>
					<span><?php echo esc_html( ce_opt( 'phone' ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $ce_cta_label ) : ?>
				<a class="ce-btn ce-btn--primary ce-btn--sm ce-header__cta" href="<?php echo esc_url( $ce_cta_url ); ?>">
					<span><?php echo esc_html( $ce_cta_label ); ?></span><?php echo ce_icon( 'arrow-up-right', 16, 'ce-btn__icon' ); // phpcs:ignore ?>
				</a>
			<?php endif; ?>
			<button class="ce-burger" type="button" aria-controls="ce-drawer" aria-expanded="false" data-drawer-open>
				<span class="ce-burger__lines" aria-hidden="true"><span></span><span></span></span>
				<span class="screen-reader-text">Menüyü aç</span>
			</button>
		</div>
	</div>
</header>

<div class="ce-drawer" id="ce-drawer" role="dialog" aria-modal="true" aria-label="Site menüsü" hidden data-drawer>
	<div class="ce-drawer__backdrop" data-drawer-close></div>
	<div class="ce-drawer__panel">
		<div class="ce-drawer__top">
			<?php ce_logo( 'drawer' ); ?>
			<button class="ce-drawer__close" type="button" data-drawer-close>
				<?php echo ce_icon( 'close', 22 ); // phpcs:ignore ?><span class="screen-reader-text">Menüyü kapat</span>
			</button>
		</div>
		<nav class="ce-drawer__nav" aria-label="Mobil menü">
			<?php ce_render_drawer_menu(); ?>
		</nav>
		<div class="ce-drawer__foot">
			<?php if ( $ce_cta_label ) : ?>
				<a class="ce-btn ce-btn--primary ce-btn--block" href="<?php echo esc_url( $ce_cta_url ); ?>"><span><?php echo esc_html( $ce_cta_label ); ?></span><?php echo ce_icon( 'arrow-right', 18, 'ce-btn__icon' ); // phpcs:ignore ?></a>
			<?php endif; ?>
			<ul class="ce-drawer__contact">
				<?php if ( ce_opt( 'phone' ) ) : ?>
					<li><a href="<?php echo esc_attr( ce_tel() ); ?>"><?php echo ce_icon( 'phone', 18 ); // phpcs:ignore ?><?php echo esc_html( ce_opt( 'phone' ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( ce_opt( 'email' ) ) : ?>
					<li><a href="mailto:<?php echo esc_attr( ce_opt( 'email' ) ); ?>"><?php echo ce_icon( 'mail', 18 ); // phpcs:ignore ?><?php echo esc_html( ce_opt( 'email' ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( ce_whatsapp_url() ) : ?>
					<li><a href="<?php echo esc_url( ce_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'whatsapp', 18 ); // phpcs:ignore ?>WhatsApp ile yazın</a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</div>

<?php endif; ?>

<main id="main" class="ce-main" tabindex="-1">
