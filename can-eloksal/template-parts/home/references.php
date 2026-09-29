<?php
/**
 * Referans logoları (yalnızca logosu olan kayıtlar; kayıt yoksa bölüm gizlenir).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_refs = ce_get_items( 'ce_reference', 40, array( 'meta_key' => '_thumbnail_id' ) );
if ( ! $ce_refs ) {
	return;
}
$ce_marquee = count( $ce_refs ) >= 5;
?>
<section class="ce-section ce-section--compact ce-refs" aria-labelledby="ce-refs-title">
	<div class="ce-container">
		<h2 id="ce-refs-title" class="ce-refs__title"><?php echo esc_html( ce_opt( 'refs_title' ) ); ?></h2>
	</div>
	<div class="ce-refs__viewport<?php echo $ce_marquee ? ' is-marquee' : ''; ?>">
		<?php for ( $ce_loop = 0; $ce_loop < ( $ce_marquee ? 2 : 1 ); $ce_loop++ ) : ?>
			<ul class="ce-refs__track" <?php echo $ce_loop ? 'aria-hidden="true"' : ''; ?>>
				<?php foreach ( $ce_refs as $ce_ref ) : ?>
					<?php $ce_site = ce_meta( $ce_ref->ID, 'website' ); ?>
					<li class="ce-refs__item">
						<?php if ( $ce_site ) : ?>
							<a href="<?php echo esc_url( $ce_site ); ?>" target="_blank" rel="noopener nofollow" <?php echo $ce_loop ? 'tabindex="-1"' : ''; ?>>
						<?php endif; ?>
						<?php echo ce_img( get_post_thumbnail_id( $ce_ref ), 'ce-logo', array( 'alt' => get_the_title( $ce_ref ), 'sizes' => '180px' ) ); // phpcs:ignore ?>
						<?php if ( $ce_site ) : ?>
							</a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endfor; ?>
	</div>
</section>
