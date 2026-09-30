<?php
/**
 * Template Name: Tam genişlik (blok düzenleyici ile serbest tasarım)
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'cr-fullwidth' ); ?>>
		<h1 class="screen-reader-text"><?php the_title(); ?></h1>
		<div class="cr-prose cr-prose--wide entry-content"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;
get_footer();
