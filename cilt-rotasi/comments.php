<?php
/**
 * Yorumlar.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="cr-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="cr-h3"><?php echo esc_html( get_comments_number() ); ?> yorum</h2>
		<ol class="cr-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 44,
				)
			);
			?>
		</ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>
	<?php
	comment_form(
		array(
			'title_reply'  => 'Deneyimini paylaş',
			'label_submit' => 'Gönder',
			'class_submit' => 'cr-btn cr-btn--primary',
		)
	);
	?>
</section>
