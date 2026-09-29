<?php
/**
 * Footer + çekmeceler (mobil menü, kategori menüsü, arama).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$social = df_social_links();
?>
</main>

<footer class="df-footer">
	<div class="df-container">
		<div class="df-footer__grid">
			<div class="df-footer__brand">
				<?php df_logo( 'footer' ); ?>
				<?php if ( df_opt( 'footer_about' ) ) : ?>
					<p class="df-footer__about"><?php echo esc_html( df_opt( 'footer_about' ) ); ?></p>
				<?php endif; ?>
				<?php if ( $social ) : ?>
					<ul class="df-social">
						<?php foreach ( $social as $net => $url ) : ?>
							<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $net ) ); ?>"><?php df_the_icon( $net, array( 'size' => 20 ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php foreach ( array( 'footer_1' => 'footer_col2_title', 'footer_2' => 'footer_col3_title' ) as $loc => $title_key ) : ?>
				<div class="df-footer__col">
					<h2 class="df-footer__title"><?php echo esc_html( df_opt( $title_key ) ); ?></h2>
					<?php
					if ( has_nav_menu( $loc ) ) {
						wp_nav_menu(
							array(
								'theme_location' => $loc,
								'container'      => false,
								'menu_class'     => 'df-footer__menu',
								'depth'          => 1,
							)
						);
					} elseif ( 'footer_2' === $loc && df_wc() ) {
						echo '<ul class="df-footer__menu">';
						echo '<li><a href="' . esc_url( df_page_url( 'track_page', 'siparis-takip' ) ) . '">Sipariş Takip</a></li>';
						echo '<li><a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">Hesabım</a></li>';
						echo '<li><a href="' . esc_url( df_page_url( 'wishlist_page', 'favorilerim' ) ) . '">Favorilerim</a></li>';
						echo '<li><a href="' . esc_url( wc_get_cart_url() ) . '">Sepetim</a></li>';
						echo '</ul>';
					}
					?>
				</div>
			<?php endforeach; ?>

			<div class="df-footer__col df-footer__contact">
				<h2 class="df-footer__title"><?php echo esc_html( df_opt( 'footer_col4_title' ) ); ?></h2>
				<ul class="df-footer__list">
					<?php foreach ( array( 'contact_phone1', 'contact_phone2' ) as $k ) : ?>
						<?php if ( df_opt( $k ) ) : ?>
							<li><a href="<?php echo esc_url( df_tel( df_opt( $k ) ) ); ?>"><?php echo esc_html( df_opt( $k ) ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ( df_opt( 'contact_email' ) ) : ?>
						<li><a href="mailto:<?php echo esc_attr( antispambot( df_opt( 'contact_email' ) ) ); ?>"><?php echo esc_html( antispambot( df_opt( 'contact_email' ) ) ); ?></a></li>
					<?php endif; ?>
				</ul>
				<?php if ( df_opt( 'contact_address' ) ) : ?>
					<address class="df-footer__address">
						<?php if ( df_opt( 'contact_map' ) ) : ?>
							<a href="<?php echo esc_url( df_opt( 'contact_map' ) ); ?>" target="_blank" rel="noopener"><?php echo df_nl2br( df_opt( 'contact_address' ) ); // phpcs:ignore ?></a>
						<?php else : ?>
							<?php echo df_nl2br( df_opt( 'contact_address' ) ); // phpcs:ignore ?>
						<?php endif; ?>
					</address>
				<?php endif; ?>
				<?php if ( df_opt( 'contact_hours' ) ) : ?>
					<p class="df-footer__hours"><?php echo df_nl2br( df_opt( 'contact_hours' ) ); // phpcs:ignore ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="df-footer__bottom">
			<p class="df-footer__copy"><?php echo esc_html( df_vars( df_opt( 'footer_copyright' ) ) ); ?></p>
			<?php
			if ( has_nav_menu( 'legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'legal',
						'container'      => false,
						'menu_class'     => 'df-footer__legal',
						'depth'          => 1,
					)
				);
			}
			?>
			<?php if ( df_opt( 'footer_payment' ) ) : ?>
				<div class="df-footer__payment"><?php echo wp_get_attachment_image( absint( df_opt( 'footer_payment' ) ), 'medium', false, array( 'alt' => 'Ödeme yöntemleri', 'loading' => 'lazy' ) ); ?></div>
			<?php else : ?>
				<p class="df-footer__secure"><?php df_the_icon( 'lock', array( 'size' => 16 ) ); ?><span>256-bit SSL · 3D Secure güvenli ödeme</span></p>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php /* Mobil menü çekmecesi */ ?>
<div class="df-drawer df-drawer--left" id="df-menu-drawer" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Menü">
	<div class="df-drawer__panel">
		<div class="df-drawer__head">
			<?php df_logo( 'drawer' ); ?>
			<button type="button" class="df-drawer__close" data-df-close aria-label="Kapat"><?php df_the_icon( 'close' ); ?></button>
		</div>
		<div class="df-drawer__body">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'df-mnav',
					'depth'          => 2,
					'fallback_cb'    => function () {
						df_menu_fallback( array( 'menu_class' => 'df-mnav' ) );
					},
				)
			);
			?>
			<?php if ( df_wc() ) : ?>
				<div class="df-drawer__links">
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php df_the_icon( 'user', array( 'size' => 20 ) ); ?><?php echo is_user_logged_in() ? 'Hesabım' : 'Giriş Yap / Üye Ol'; ?></a>
					<a href="<?php echo esc_url( df_page_url( 'wishlist_page', 'favorilerim' ) ); ?>"><?php df_the_icon( 'heart', array( 'size' => 20 ) ); ?>Favorilerim</a>
					<a href="<?php echo esc_url( df_page_url( 'track_page', 'siparis-takip' ) ); ?>"><?php df_the_icon( 'truck', array( 'size' => 20 ) ); ?>Sipariş Takip</a>
				</div>
			<?php endif; ?>
			<div class="df-drawer__contact">
				<?php if ( df_opt( 'contact_phone1' ) ) : ?>
					<a href="<?php echo esc_url( df_tel( df_opt( 'contact_phone1' ) ) ); ?>"><?php df_the_icon( 'phone', array( 'size' => 18 ) ); ?><?php echo esc_html( df_opt( 'contact_phone1' ) ); ?></a>
				<?php endif; ?>
				<p><?php echo esc_html( df_opt( 'topbar_right' ) ); ?></p>
			</div>
		</div>
	</div>
</div>

<?php if ( df_wc() && df_opt( 'header_cat_btn' ) ) : ?>
	<?php /* Kategori çekmecesi (ikonlu) */ ?>
	<div class="df-drawer df-drawer--left df-cat-drawer" id="df-cat-drawer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="df-cat-drawer-title">
		<div class="df-drawer__panel">
			<div class="df-drawer__head">
				<h2 class="df-drawer__title" id="df-cat-drawer-title"><?php echo esc_html( df_opt( 'drawer_title', 'Kategoriler' ) ); ?></h2>
				<button type="button" class="df-drawer__close" data-df-close aria-label="Kapat"><?php df_the_icon( 'close' ); ?></button>
			</div>
			<div class="df-drawer__body">
				<a class="df-catlist__all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php df_the_icon( 'grid', array( 'size' => 20 ) ); ?><span>Tüm Ürünler</span><?php df_the_icon( 'arrow-right', array( 'size' => 18 ) ); ?></a>
				<?php df_category_drawer_list(); ?>
				<?php if ( df_opt( 'drawer_image' ) ) : ?>
					<div class="df-drawer__image"><?php echo wp_get_attachment_image( absint( df_opt( 'drawer_image' ) ), 'df-wide', false, array( 'loading' => 'lazy' ) ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php /* Arama katmanı */ ?>
<div class="df-search" id="df-search" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Ürün ara">
	<div class="df-search__panel">
		<div class="df-container">
			<form class="df-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php df_the_icon( 'search', array( 'size' => 26 ) ); ?>
				<label class="screen-reader-text" for="df-search-input">Ürün ara</label>
				<input type="search" id="df-search-input" name="s" placeholder="Ne arıyorsunuz? Güller, orkide, doğum günü…" autocomplete="off" data-df-live-search>
				<?php if ( df_wc() ) : ?>
					<input type="hidden" name="post_type" value="product">
				<?php endif; ?>
				<button type="button" class="df-search__close" data-df-close aria-label="Kapat"><?php df_the_icon( 'close', array( 'size' => 26 ) ); ?></button>
			</form>
			<div class="df-search__results" data-df-search-results aria-live="polite"></div>
			<?php if ( df_wc() ) : ?>
				<div class="df-search__popular">
					<span>Popüler:</span>
					<?php foreach ( df_top_product_cats( 6 ) as $term ) : ?>
						<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="df-overlay" data-df-overlay hidden></div>
<div class="df-toast" data-df-toast role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>
