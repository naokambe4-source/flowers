<?php
/**
 * Cilt problemleri: yatay kaydırmalı, snap destekli şerit.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

$items = (array) cr_opt( 'problems_items' );
$auto  = false;
if ( ! $items ) {
	$auto  = true;
	$terms = get_terms( array( 'taxonomy' => 'cilt_sorunu', 'hide_empty' => false, 'parent' => 0 ) );
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$items[] = array(
				'title' => $t->name,
				'text'  => wp_trim_words( $t->description, 8, '…' ),
				'image' => cr_term_image( $t ),
				'url'   => get_term_link( $t ),
			);
		}
	}
}
if ( ! $items ) {
	return;
}
?>
<section class="cr-section cr-problems"<?php echo cr_section_attr( 'problems' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<div class="cr-head cr-head--split cr-reveal">
			<div>
				<h2 class="cr-h2"<?php echo cr_edit( 'problems_title' ); // phpcs:ignore ?>><?php cr_t( 'problems_title' ); ?></h2>
				<p class="cr-lead"<?php echo cr_edit( 'problems_text' ); // phpcs:ignore ?>><?php cr_t( 'problems_text' ); ?></p>
			</div>
			<div class="cr-rail-ctrl" aria-hidden="true">
				<button type="button" class="cr-round-btn" data-rail-prev tabindex="-1"><?php echo cr_icon( 'arrow-left', 20 ); // phpcs:ignore ?></button>
				<button type="button" class="cr-round-btn" data-rail-next tabindex="-1"><?php echo cr_icon( 'arrow-right', 20 ); // phpcs:ignore ?></button>
			</div>
		</div>
	</div>
	<div class="cr-rail" data-rail>
		<ul class="cr-rail__track" role="list">
			<?php foreach ( $items as $i => $it ) : ?>
				<li class="cr-rail__item">
					<a class="cr-problem" href="<?php echo esc_url( cr_url( $it['url'] ) ); ?>">
						<span class="cr-problem__media"<?php echo $auto ? '' : cr_edit_img( 'problems_items.' . $i . '.image' ); // phpcs:ignore ?>>
							<?php echo cr_img( $it['image'], 'cr-card', array( 'alt' => '', 'class' => 'cr-zoom', 'sizes' => '(max-width: 768px) 70vw, 22vw' ) ); // phpcs:ignore ?>
						</span>
						<span class="cr-problem__body">
							<span class="cr-problem__title"<?php echo $auto ? '' : cr_edit( 'problems_items.' . $i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $it['title'] ); ?></span>
							<span class="cr-problem__text"<?php echo $auto ? '' : cr_edit( 'problems_items.' . $i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $it['text'] ); ?></span>
						</span>
						<span class="cr-problem__go" aria-hidden="true"><?php echo cr_icon( 'arrow-up-right', 18 ); // phpcs:ignore ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
