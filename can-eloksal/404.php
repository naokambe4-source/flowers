<?php
/**
 * 404 — sayfa bulunamadı.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="ce-404">
	<div class="ce-404__art" aria-hidden="true"><span>404</span></div>
	<div class="ce-container ce-404__inner">
		<p class="ce-eyebrow ce-eyebrow--light">Hata 404</p>
		<h1 class="ce-404__title">Aradığınız sayfa bulunamadı.</h1>
		<p class="ce-404__text">Sayfa taşınmış, kaldırılmış ya da adres hatalı yazılmış olabilir.</p>
		<div class="ce-btn-row">
			<?php echo ce_button( 'Ana Sayfaya Dön', home_url( '/' ), 'primary', 'arrow-right' ); // phpcs:ignore ?>
			<?php echo ce_button( 'Hizmetlere Git', ce_services_url(), 'ghost-light', 'arrow-up-right' ); // phpcs:ignore ?>
		</div>
		<?php $ce_services = ce_get_items( 'ce_service', 6 ); ?>
		<?php if ( $ce_services ) : ?>
			<ul class="ce-404__links" aria-label="Popüler hizmetler">
				<?php foreach ( $ce_services as $ce_s ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $ce_s ) ); ?>"><?php echo esc_html( get_the_title( $ce_s ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
