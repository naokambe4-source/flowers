<?php
/**
 * Tam genişlik CTA.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_img = absint( ce_hopt( $args, 'cta_image' ) );
?>
<section class="ce-cta<?php echo $ce_img ? ' has-image' : ''; ?>" aria-labelledby="<?php echo esc_attr( ce_uid( $args, 'ce-cta-title' ) ); ?>">
	<div class="ce-cta__media" aria-hidden="true"<?php echo ce_edimg( ce_src( $args, 'cta_image' ) ); // phpcs:ignore ?>>
		<?php echo $ce_img ? ce_img( $ce_img, 'ce-hero', array( 'sizes' => '100vw', 'alt' => '' ) ) : '<span class="ce-cta__art"></span>'; // phpcs:ignore ?>
	</div>
	<div class="ce-container ce-cta__inner" data-reveal>
		<h2 id="<?php echo esc_attr( ce_uid( $args, 'ce-cta-title' ) ); ?>" class="ce-cta__title"<?php echo ce_ed( ce_src( $args, 'cta_title' ), 'nl' ); // phpcs:ignore ?>><?php echo ce_nl2br( ce_hopt( $args, 'cta_title' ) ); // phpcs:ignore ?></h2>
		<?php if ( ce_hopt( $args, 'cta_text' ) ) : ?>
			<p class="ce-cta__text"<?php echo ce_ed( ce_src( $args, 'cta_text' ) ); // phpcs:ignore ?>><?php echo esc_html( ce_hopt( $args, 'cta_text' ) ); ?></p>
		<?php endif; ?>
		<div class="ce-cta__actions">
			<?php echo ce_button( ce_hopt( $args, 'cta_button' ), ce_hopt( $args, 'cta_link' ), 'primary', 'arrow-right', ce_src( $args, 'cta_button' ), ce_src( $args, 'cta_link' ) ); // phpcs:ignore ?>
			<?php if ( ce_hopt( $args, 'phone' ) ) : ?>
				<a class="ce-cta__phone" href="<?php echo esc_attr( ce_tel() ); ?>">
					<span class="ce-cta__phone-icon"><?php echo ce_icon( 'phone', 20 ); // phpcs:ignore ?></span>
					<span><small>Hemen arayın</small><strong<?php echo ce_ed( 'opt:phone' ); // phpcs:ignore ?>><?php echo esc_html( ce_hopt( $args, 'phone' ) ); ?></strong></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
