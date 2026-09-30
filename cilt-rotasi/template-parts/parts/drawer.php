<?php
/**
 * Mobil menü çekmecesi.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cr-drawer" id="cr-drawer" role="dialog" aria-modal="true" aria-label="Menü" hidden>
	<div class="cr-drawer__backdrop" data-drawer-close></div>
	<div class="cr-drawer__panel">
		<div class="cr-drawer__head">
			<?php echo cr_logo( 'drawer' ); // phpcs:ignore ?>
			<button type="button" class="cr-icon-btn" data-drawer-close aria-label="Menüyü kapat"><?php echo cr_icon( 'close', 24 ); // phpcs:ignore ?></button>
		</div>
		<nav class="cr-drawer__nav" aria-label="Mobil menü">
			<?php cr_primary_menu( 'drawer' ); ?>
		</nav>
		<div class="cr-drawer__extra">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'icerik' ) ); ?>"><?php echo cr_icon( 'flask', 20 ); // phpcs:ignore ?>İçerik sözlüğü</a>
			<a href="<?php echo esc_url( cr_saved_url() ); ?>"><?php echo cr_icon( 'bookmark', 20 ); // phpcs:ignore ?>Kaydedilenler <span class="cr-count" data-saved-count hidden>0</span></a>
			<a href="<?php echo esc_url( cr_url( cr_opt( 'quiz_url' ) ) ); ?>"><?php echo cr_icon( 'target', 20 ); // phpcs:ignore ?>Cilt testi</a>
		</div>
		<?php if ( cr_opt( 'header_cta_text' ) ) : ?>
			<a class="cr-btn cr-btn--primary cr-btn--block" href="<?php echo esc_url( cr_url( cr_opt( 'header_cta_url' ) ) ); ?>"><?php cr_t( 'header_cta_text' ); ?></a>
		<?php endif; ?>
		<?php $social = cr_social_links(); ?>
		<?php if ( $social ) : ?>
			<div class="cr-social">
				<?php foreach ( $social as $k => $u ) : ?>
					<a href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $k ) ); ?>"><?php echo cr_icon( $k, 20 ); // phpcs:ignore ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
