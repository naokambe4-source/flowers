<?php
/**
 * 404.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="cr-404">
	<div class="cr-container cr-container--narrow">
		<p class="cr-404__num" aria-hidden="true">404</p>
		<h1 class="cr-archive-hero__title"<?php echo cr_edit( 'notfound_title' ); // phpcs:ignore ?>><?php cr_t( 'notfound_title' ); ?></h1>
		<p class="cr-lead"<?php echo cr_edit( 'notfound_text' ); // phpcs:ignore ?>><?php cr_t( 'notfound_text' ); ?></p>
		<form class="cr-bigsearch" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
			<?php echo cr_icon( 'search', 24 ); // phpcs:ignore ?>
			<label class="screen-reader-text" for="cr-404-q">Ara</label>
			<input id="cr-404-q" type="search" name="s" placeholder="<?php echo esc_attr( cr_opt( 'search_placeholder' ) ); ?>">
			<button class="cr-btn cr-btn--primary" type="submit">Ara</button>
		</form>
		<div class="cr-404__links">
			<a class="cr-btn cr-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Ana sayfa</a>
			<a class="cr-btn cr-btn--ghost" href="<?php echo esc_url( cr_url( cr_opt( 'bn_explore_url' ) ) ); ?>">Rehberler</a>
			<a class="cr-btn cr-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'icerik' ) ); ?>">İçerik sözlüğü</a>
		</div>
	</div>
</section>
<?php
get_footer();
