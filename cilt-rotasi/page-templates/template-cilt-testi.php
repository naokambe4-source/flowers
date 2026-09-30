<?php
/**
 * Template Name: Cilt Testi
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="cr-quizpage">
	<div class="cr-container">
		<?php cr_breadcrumb_html(); ?>
		<div class="cr-quizpage__grid">
			<div class="cr-quizpage__visual" aria-hidden="true">
				<?php echo cr_img( cr_opt( 'quick_items' )[0]['image'] ?? cr_default_img( 'structure' ), 'cr-card', array( 'alt' => '', 'loading' => 'eager' ) ); // phpcs:ignore ?>
			</div>
			<div class="cr-quizpage__main">
				<?php echo cr_quiz_html(); // phpcs:ignore ?>
			</div>
		</div>
		<?php
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) :
				?>
				<div class="cr-prose cr-container--narrow cr-quizpage__content"><?php the_content(); ?></div>
				<?php
			endif;
		endwhile;
		?>
	</div>
</section>
<?php
get_footer();
