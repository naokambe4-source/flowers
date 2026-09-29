<?php
/**
 * Template Name: Tam Genişlik (başlıksız)
 *
 * Blok düzenleyici ile tam genişlik landing sayfaları için.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="df-fullwidth df-prose">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
