<?php
/**
 * Site altı: teklif bandı, koyu footer, WhatsApp butonu, çerez bandı.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;
$ce_is_quote = is_page_template( 'page-templates/quote.php' );
?>
</main>

<?php if ( ce_opt( 'footer_cta' ) && ! $ce_is_quote && ! is_front_page() ) : ?>
	<section class="ce-prefooter" aria-labelledby="ce-prefooter-title">
		<div class="ce-container ce-prefooter__inner">
			<div>
				<p class="ce-eyebrow ce-eyebrow--light">Teklif Talebi</p>
				<h2 id="ce-prefooter-title" class="ce-prefooter__title">Parçalarınız için doğru yüzey çözümünü birlikte belirleyelim.</h2>
			</div>
			<div class="ce-prefooter__actions">
				<?php echo ce_button( 'Teklif Al', ce_quote_url(), 'primary', 'arrow-right' ); // phpcs:ignore ?>
				<?php if ( ce_opt( 'phone' ) ) : ?>
					<a class="ce-btn ce-btn--ghost-light" href="<?php echo esc_attr( ce_tel() ); ?>"><?php echo ce_icon( 'phone', 18, 'ce-btn__icon' ); // phpcs:ignore ?><span><?php echo esc_html( ce_opt( 'phone' ) ); ?></span></a>
				<?php endif; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<footer class="ce-footer">
	<div class="ce-container">
		<div class="ce-footer__grid">
			<div class="ce-footer__brand">
				<?php ce_logo( 'footer' ); ?>
				<?php if ( ce_opt( 'footer_text' ) ) : ?>
					<p class="ce-footer__text"><?php echo esc_html( ce_opt( 'footer_text' ) ); ?></p>
				<?php endif; ?>
				<?php $ce_socials = ce_socials(); ?>
				<?php if ( $ce_socials ) : ?>
					<ul class="ce-socials">
						<?php foreach ( $ce_socials as $ce_net => $ce_url ) : ?>
							<li><a href="<?php echo esc_url( $ce_url ); ?>" target="_blank" rel="noopener me"><?php echo ce_icon( $ce_net, 18 ); // phpcs:ignore ?><span class="screen-reader-text"><?php echo esc_html( ucfirst( $ce_net ) ); ?></span></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="ce-footer__col">
				<h2 class="ce-footer__title"><?php echo esc_html( ce_menu_title( 'footer_corporate', 'Kurumsal' ) ); ?></h2>
				<?php ce_render_list_menu( 'footer_corporate' ); ?>
			</div>
			<div class="ce-footer__col">
				<h2 class="ce-footer__title"><?php echo esc_html( ce_menu_title( 'footer_services', 'Hizmetler' ) ); ?></h2>
				<?php ce_render_list_menu( 'footer_services' ); ?>
			</div>
			<div class="ce-footer__col">
				<h2 class="ce-footer__title"><?php echo esc_html( ce_menu_title( 'footer_quick', 'Hızlı Linkler' ) ); ?></h2>
				<?php ce_render_list_menu( 'footer_quick' ); ?>
			</div>

			<div class="ce-footer__col ce-footer__contact">
				<h2 class="ce-footer__title">İletişim</h2>
				<ul class="ce-footer__info">
					<?php if ( ce_opt( 'phone' ) ) : ?>
						<li><?php echo ce_icon( 'phone', 18 ); // phpcs:ignore ?><a href="<?php echo esc_attr( ce_tel() ); ?>"><?php echo esc_html( ce_opt( 'phone' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( ce_opt( 'email' ) ) : ?>
						<li><?php echo ce_icon( 'mail', 18 ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( ce_opt( 'email' ) ); ?>"><?php echo esc_html( ce_opt( 'email' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( ce_opt( 'address' ) ) : ?>
						<li><?php echo ce_icon( 'map-pin', 18 ); // phpcs:ignore ?><address><?php echo ce_nl2br( ce_opt( 'address' ) ); // phpcs:ignore ?></address></li>
					<?php endif; ?>
					<?php if ( ce_opt( 'working_hours' ) ) : ?>
						<li><?php echo ce_icon( 'clock', 18 ); // phpcs:ignore ?><span><?php echo esc_html( ce_opt( 'working_hours' ) ); ?></span></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>

		<div class="ce-footer__bottom">
			<p><?php echo esc_html( str_replace( '{year}', wp_date( 'Y' ), ce_opt( 'copyright' ) ) ); ?></p>
			<div class="ce-footer__legal">
				<?php ce_render_list_menu( 'footer_legal', 'ce-footer__legal-links' ); ?>
				<?php if ( ce_opt( 'cookie_enabled' ) ) : ?>
					<button type="button" class="ce-linkbtn" data-cookie-open>Çerez tercihleri</button>
				<?php endif; ?>
			</div>
		</div>
	</div>
</footer>

<?php if ( ce_opt( 'float_whatsapp' ) && ce_whatsapp_url() ) : ?>
	<a class="ce-float-wa" href="<?php echo esc_url( ce_whatsapp_url() ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp ile yazın">
		<?php echo ce_icon( 'whatsapp', 24 ); // phpcs:ignore ?><span class="ce-float-wa__label">WhatsApp</span>
	</a>
<?php endif; ?>

<?php if ( ce_opt( 'cookie_enabled' ) ) : ?>
	<div class="ce-cookie" role="region" aria-label="Çerez bildirimi" hidden data-cookie>
		<p><?php echo esc_html( ce_opt( 'cookie_text' ) ); ?>
			<?php if ( absint( ce_opt( 'cookie_page' ) ) ) : ?>
				<?php echo wp_kses_post( ce_legal_link( 'cookie_page', 'Çerez Politikası' ) ); ?>
			<?php endif; ?>
		</p>
		<div class="ce-cookie__actions">
			<button type="button" class="ce-btn ce-btn--ghost ce-btn--sm" data-cookie-choice="necessary">Sadece zorunlu</button>
			<button type="button" class="ce-btn ce-btn--primary ce-btn--sm" data-cookie-choice="all">Kabul et</button>
		</div>
	</div>
<?php endif; ?>

<div class="ce-toasts" aria-live="polite" aria-atomic="true" data-toasts></div>
<?php wp_footer(); ?>
</body>
</html>
