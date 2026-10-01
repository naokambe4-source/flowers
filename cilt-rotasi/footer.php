<?php
/**
 * Footer (minimal dergi tarzı).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$social = cr_social_links();
$names  = array( 'instagram' => 'Instagram', 'pinterest' => 'Pinterest', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X (Twitter)' );
?>
</main>

<footer class="cr-footer">
	<div class="cr-container">
		<div class="cr-footer__top">
			<div class="cr-footer__brand">
				<?php echo cr_logo( 'footer' ); // phpcs:ignore ?>
				<p<?php echo cr_edit( 'footer_text' ); // phpcs:ignore ?>><?php cr_t( 'footer_text' ); ?></p>
			</div>
			<div class="cr-footer__cols">
				<?php foreach ( array( 1, 2, 3 ) as $i ) : ?>
					<div class="cr-footer__col">
						<p class="cr-eyebrow cr-footer__title"<?php echo cr_edit( 'footer_col' . $i ); // phpcs:ignore ?>><?php cr_t( 'footer_col' . $i ); ?></p>
						<?php cr_footer_menu( 'footer_' . $i ); ?>
					</div>
				<?php endforeach; ?>
				<?php if ( $social ) : ?>
					<div class="cr-footer__col">
						<p class="cr-eyebrow cr-footer__title">Sosyal Medya</p>
						<ul class="cr-footer__list">
							<?php foreach ( $social as $k => $u ) : ?>
								<li><a href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener me"><?php echo esc_html( $names[ $k ] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<p class="cr-footer__disc"<?php echo cr_edit( 'footer_disclaimer' ); // phpcs:ignore ?>><?php cr_t( 'footer_disclaimer' ); ?></p>
		<div class="cr-footer__bottom">
			<p><?php echo esc_html( str_replace( '{yil}', gmdate( 'Y' ), cr_opt( 'footer_copy' ) ) ); ?></p>
			<p<?php echo cr_edit( 'footer_tagline' ); // phpcs:ignore ?>><?php cr_t( 'footer_tagline' ); ?></p>
		</div>
	</div>
</footer>

<?php if ( cr_opt( 'bottom_nav' ) ) : ?>
	<nav class="cr-bottom-nav" aria-label="Hızlı gezinme">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo is_front_page() ? 'is-active' : ''; ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>><?php echo cr_icon( 'home', 22 ); // phpcs:ignore ?><span><?php cr_t( 'bn_home' ); ?></span></a>
		<a href="<?php echo esc_url( cr_url( cr_opt( 'bn_explore_url' ) ) ); ?>" class="<?php echo ( is_home() || is_archive() ) ? 'is-active' : ''; ?>"><?php echo cr_icon( 'compass', 22 ); // phpcs:ignore ?><span><?php cr_t( 'bn_explore' ); ?></span></a>
		<button type="button" data-search-open aria-controls="cr-search" aria-expanded="false"><span class="cr-bottom-nav__search"><?php echo cr_icon( 'search', 22 ); // phpcs:ignore ?></span><span><?php cr_t( 'bn_search' ); ?></span></button>
		<a href="<?php echo esc_url( cr_saved_url() ); ?>" class="<?php echo is_page_template( 'page-templates/template-kaydedilenler.php' ) ? 'is-active' : ''; ?>"><?php echo cr_icon( 'bookmark', 22 ); // phpcs:ignore ?><span><?php cr_t( 'bn_saved' ); ?></span><span class="cr-count" data-saved-count hidden>0</span></a>
		<button type="button" data-drawer-open aria-controls="cr-drawer" aria-expanded="false"><?php echo cr_icon( 'grid', 22 ); // phpcs:ignore ?><span><?php cr_t( 'bn_menu' ); ?></span></button>
	</nav>
<?php endif; ?>

<div class="cr-toast" role="status" aria-live="polite" hidden></div>
<?php wp_footer(); ?>
</body>
</html>
