<?php
/**
 * Header.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="<?php echo esc_attr( df_opt( 'header_bg', '#FFFFFF' ) ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="df-skip" href="#df-main">İçeriğe geç</a>

<?php if ( df_opt( 'topbar_on' ) ) : ?>
	<div class="df-topbar">
		<div class="df-container df-topbar__inner">
			<p class="df-topbar__left">
				<?php if ( df_opt( 'topbar_link' ) ) : ?>
					<a href="<?php echo esc_url( df_url( df_opt( 'topbar_link' ) ) ); ?>"><?php echo esc_html( df_opt( 'topbar_left' ) ); ?></a>
				<?php else : ?>
					<?php echo esc_html( df_opt( 'topbar_left' ) ); ?>
				<?php endif; ?>
			</p>
			<p class="df-topbar__right"><?php df_the_icon( 'clock', array( 'size' => 14 ) ); ?><span><?php echo esc_html( df_opt( 'topbar_right' ) ); ?></span></p>
		</div>
	</div>
<?php endif; ?>

<header class="df-header" id="df-header">
	<div class="df-header__main">
		<div class="df-container df-header__inner">
			<div class="df-header__left">
				<button type="button" class="df-iconbtn df-header__burger" data-df-open="df-menu-drawer" aria-controls="df-menu-drawer" aria-expanded="false" aria-label="Menüyü aç">
					<?php df_the_icon( 'menu', array( 'size' => 24 ) ); ?>
				</button>
				<?php if ( df_opt( 'header_cat_btn' ) && df_wc() ) : ?>
					<button type="button" class="df-header__cats" data-df-open="df-cat-drawer" aria-controls="df-cat-drawer" aria-expanded="false">
						<?php df_the_icon( 'grid', array( 'size' => 18 ) ); ?>
						<span><?php echo esc_html( df_opt( 'header_cat_label', 'Kategoriler' ) ); ?></span>
					</button>
				<?php endif; ?>
				<?php if ( df_opt( 'header_phone_on' ) && df_opt( 'contact_phone1' ) ) : ?>
					<a class="df-header__phone" href="<?php echo esc_url( df_tel( df_opt( 'contact_phone1' ) ) ); ?>"><?php df_the_icon( 'phone', array( 'size' => 16 ) ); ?><span><?php echo esc_html( df_opt( 'contact_phone1' ) ); ?></span></a>
				<?php endif; ?>
				<?php if ( 'left' === df_opt( 'header_layout' ) ) : ?>
					<?php df_logo( 'header' ); ?>
				<?php endif; ?>
			</div>

			<?php if ( 'left' !== df_opt( 'header_layout' ) ) : ?>
				<div class="df-header__brand"><?php df_logo( 'header' ); ?></div>
			<?php endif; ?>

			<div class="df-header__right">
				<?php if ( df_opt( 'header_show_search' ) ) : ?>
					<button type="button" class="df-iconbtn" data-df-open="df-search" aria-controls="df-search" aria-expanded="false" aria-label="Ara">
						<?php df_the_icon( 'search' ); ?><span class="df-iconbtn__label">Arama</span>
					</button>
				<?php endif; ?>
				<?php if ( df_wc() && df_opt( 'header_show_account' ) ) : ?>
					<a class="df-iconbtn df-hide-sm" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php echo is_user_logged_in() ? 'Hesabım' : 'Giriş yap / Üye ol'; ?>">
						<?php df_the_icon( 'user' ); ?><span class="df-iconbtn__label"><?php echo is_user_logged_in() ? 'Hesabım' : 'Giriş'; ?></span>
					</a>
				<?php endif; ?>
				<?php if ( df_wc() && df_opt( 'header_show_wishlist' ) ) : ?>
					<?php $fav_count = count( df_wishlist_ids() ); ?>
					<a class="df-iconbtn df-hide-sm" href="<?php echo esc_url( df_page_url( 'wishlist_page', 'favorilerim' ) ); ?>" aria-label="Favorilerim">
						<?php df_the_icon( 'heart' ); ?><span class="df-iconbtn__label">Favoriler</span>
						<span class="df-fav-count<?php echo $fav_count ? '' : ' is-empty'; ?>"><?php echo (int) $fav_count; ?></span>
					</a>
				<?php endif; ?>
				<?php if ( df_wc() && df_opt( 'header_show_cart' ) ) : ?>
					<?php $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
					<a class="df-iconbtn df-cart-toggle" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-df-open="df-cart-drawer" aria-label="Sepetim">
						<?php df_the_icon( 'bag' ); ?><span class="df-iconbtn__label">Sepet</span>
						<span class="df-cart-count<?php echo $cart_count ? '' : ' is-empty'; ?>"><?php echo (int) $cart_count; ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<nav class="df-nav df-nav--<?php echo esc_attr( df_opt( 'nav_align', 'center' ) ); ?>" aria-label="Ana menü">
		<div class="df-container">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'df-nav__list',
					'depth'          => 3,
					'fallback_cb'    => 'df_menu_fallback',
				)
			);
			?>
		</div>
	</nav>
</header>

<main id="df-main" class="df-main">
