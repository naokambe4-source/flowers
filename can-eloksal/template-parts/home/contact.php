<?php
/**
 * İletişim CTA.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="ce-section ce-contact-cta" aria-labelledby="<?php echo esc_attr( ce_uid( $args, 'ce-contact-title' ) ); ?>">
	<div class="ce-container ce-contact-cta__inner">
		<div class="ce-contact-cta__intro" data-reveal>
			<p class="ce-eyebrow">İletişim</p>
			<h2 id="<?php echo esc_attr( ce_uid( $args, 'ce-contact-title' ) ); ?>" class="ce-display ce-display--md"><?php echo esc_html( ce_hopt( $args, 'contact_title' ) ); ?></h2>
			<?php if ( ce_hopt( $args, 'contact_text' ) ) : ?>
				<p class="ce-lead"><?php echo esc_html( ce_hopt( $args, 'contact_text' ) ); ?></p>
			<?php endif; ?>
			<div class="ce-btn-row">
				<?php echo ce_button( 'Teklif Al', ce_quote_url(), 'primary', 'arrow-right' ); // phpcs:ignore ?>
				<?php echo ce_button( 'İletişim Sayfası', ce_template_url( 'contact', '/iletisim/' ), 'ghost', 'arrow-up-right' ); // phpcs:ignore ?>
			</div>
		</div>
		<?php get_template_part( 'template-parts/components/contact-cards' ); ?>
	</div>
</section>
