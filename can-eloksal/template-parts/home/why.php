<?php
/**
 * Neden Can Eloksal (bento ızgara).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_items = (array) ce_opt( 'why_items' );
if ( ! $ce_items ) {
	return;
}
?>
<section class="ce-section ce-section--tint ce-why" aria-labelledby="ce-why-title">
	<div class="ce-container">
		<?php ce_section_head( array( 'eyebrow' => ce_opt( 'why_eyebrow' ), 'title' => ce_opt( 'why_title' ), 'id' => 'ce-why-title', 'align' => 'left' ) ); ?>
		<ul class="ce-bento">
			<?php foreach ( $ce_items as $ce_i => $ce_item ) : ?>
				<li class="ce-bento__item<?php echo 0 === $ce_i ? ' ce-bento__item--feature' : ''; ?>" data-reveal style="--i:<?php echo (int) $ce_i; ?>">
					<span class="ce-bento__num"><?php echo esc_html( sprintf( '%02d', $ce_i + 1 ) ); ?></span>
					<span class="ce-bento__icon"><?php echo ce_icon( $ce_item['icon'] ?? 'check', 26 ); // phpcs:ignore ?></span>
					<h3 class="ce-bento__title"><?php echo esc_html( $ce_item['title'] ?? '' ); ?></h3>
					<?php if ( ! empty( $ce_item['text'] ) ) : ?>
						<p class="ce-bento__text"><?php echo esc_html( $ce_item['text'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
