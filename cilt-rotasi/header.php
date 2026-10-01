<?php
/**
 * Header.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="cr-skip" href="#main">İçeriğe geç</a>

<?php if ( cr_opt( 'announce_on' ) && cr_opt( 'announce_text' ) ) : ?>
	<div class="cr-announce">
		<a href="<?php echo esc_url( cr_url( cr_opt( 'announce_url' ) ) ); ?>"><?php echo cr_icon( 'sparkle', 16 ); // phpcs:ignore ?><span<?php echo cr_edit( 'announce_text' ); // phpcs:ignore ?>><?php cr_t( 'announce_text' ); ?></span><?php echo cr_icon( 'arrow-right', 14 ); // phpcs:ignore ?></a>
	</div>
<?php endif; ?>

<header class="cr-header" id="cr-header">
	<div class="cr-container cr-header__inner">
		<?php echo cr_logo( 'header' ); // phpcs:ignore ?>

		<nav class="cr-nav" aria-label="Ana menü">
			<?php cr_primary_menu( 'desktop' ); ?>
		</nav>

		<div class="cr-header__actions">
			<?php if ( cr_opt( 'header_search' ) ) : ?>
				<button type="button" class="cr-icon-btn" data-search-open aria-label="Ara" aria-controls="cr-search" aria-expanded="false">
					<?php echo cr_icon( 'search', 22 ); // phpcs:ignore ?>
				</button>
			<?php endif; ?>
			<?php if ( cr_opt( 'header_saved' ) ) : ?>
				<a class="cr-icon-btn cr-hide-mobile" href="<?php echo esc_url( cr_saved_url() ); ?>" aria-label="Kaydedilenler">
					<?php echo cr_icon( 'bookmark', 22 ); // phpcs:ignore ?>
					<span class="cr-count" data-saved-count hidden>0</span>
				</a>
			<?php endif; ?>
			<?php if ( cr_opt( 'header_cta_text' ) ) : ?>
				<a class="cr-btn cr-btn--solid cr-btn--sm cr-header__cta" href="<?php echo esc_url( cr_url( cr_opt( 'header_cta_url' ) ) ); ?>"><span<?php echo cr_edit( 'header_cta_text' ); // phpcs:ignore ?>><?php cr_t( 'header_cta_text' ); ?></span></a>
			<?php endif; ?>
			<button type="button" class="cr-icon-btn cr-burger" data-drawer-open aria-label="Menüyü aç" aria-controls="cr-drawer" aria-expanded="false">
				<?php echo cr_icon( 'menu', 24 ); // phpcs:ignore ?>
			</button>
		</div>
	</div>
	<?php if ( is_singular( array( 'post', 'icerik', 'urun_rehberi' ) ) && cr_opt( 'art_progress' ) ) : ?>
		<div class="cr-progress" aria-hidden="true"><span data-progress></span></div>
	<?php endif; ?>
</header>

<?php get_template_part( 'template-parts/parts/search-panel' ); ?>
<?php get_template_part( 'template-parts/parts/drawer' ); ?>

<main id="main" class="cr-main" tabindex="-1">
