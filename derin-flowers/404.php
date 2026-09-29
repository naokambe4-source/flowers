<?php
/**
 * 404.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="df-404">
	<div class="df-container df-container--narrow">
		<p class="df-eyebrow">404</p>
		<h1>Aradığınız sayfa açmamış olabilir.</h1>
		<p>Sayfa taşınmış ya da kaldırılmış olabilir. Koleksiyonumuza göz atabilir veya arama yapabilirsiniz.</p>
		<?php get_search_form(); ?>
		<div class="df-404__actions">
			<?php echo df_button( 'Ana sayfaya dön', home_url( '/' ), 'outline' ); // phpcs:ignore ?>
			<?php if ( df_wc() ) : ?>
				<?php echo df_button( 'Koleksiyonu keşfet', wc_get_page_permalink( 'shop' ), 'solid' ); // phpcs:ignore ?>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
