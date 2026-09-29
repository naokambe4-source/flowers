<?php
/**
 * Yorumlar.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="df-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="df-comments__title"><?php echo esc_html( sprintf( '%d yorum', get_comments_number() ) ); ?></h2>
		<ol class="df-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php
	comment_form(
		array(
			'title_reply'  => 'Yorum yazın',
			'label_submit' => 'Gönder',
		)
	);
	?>
</section>
