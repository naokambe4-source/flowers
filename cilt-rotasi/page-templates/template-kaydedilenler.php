<?php
/**
 * Template Name: Kaydedilenler
 *
 * Kaydedilen içerikler ziyaretçinin tarayıcısında saklanır (üyelik gerekmez).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="cr-archive-hero">
	<div class="cr-container">
		<?php cr_breadcrumb_html(); ?>
		<p class="cr-eyebrow"><?php echo cr_icon( 'bookmark', 14 ); // phpcs:ignore ?> Kişisel arşivin</p>
		<h1 class="cr-archive-hero__title"<?php echo cr_edit( 'saved_title' ); // phpcs:ignore ?>><?php cr_t( 'saved_title' ); ?></h1>
		<div class="cr-archive-hero__desc"><p>Kaydettiklerin bu cihazda saklanır; hesap açman gerekmez.</p></div>
	</div>
</section>
<section class="cr-section cr-section--tight">
	<div class="cr-container">
		<div class="cr-saved" data-saved-list>
			<div class="cr-toolbar" data-saved-toolbar hidden>
				<p class="cr-saved__count" data-saved-total></p>
				<button type="button" class="cr-filter" data-saved-clear>Tümünü temizle</button>
			</div>
			<div class="cr-archive__grid" data-saved-grid></div>
			<div class="cr-empty-state" data-saved-empty hidden>
				<?php echo cr_icon( 'bookmark', 40 ); // phpcs:ignore ?>
				<p<?php echo cr_edit( 'saved_empty' ); // phpcs:ignore ?>><?php cr_t( 'saved_empty' ); ?></p>
				<a class="cr-btn cr-btn--primary" href="<?php echo esc_url( cr_blog_url() ); ?>">Blog yazılarını keşfet</a>
			</div>
		</div>
		<?php
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) :
				?>
				<div class="cr-prose cr-container--narrow"><?php the_content(); ?></div>
				<?php
			endif;
		endwhile;
		?>
	</div>
</section>
<?php
get_footer();
