<?php
/**
 * Proses zaman çizelgesi (masaüstü yatay, mobil dikey).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_steps = (array) ce_opt( 'process_steps' );
if ( ! $ce_steps ) {
	return;
}
?>
<section class="ce-section ce-process" aria-labelledby="ce-process-title">
	<div class="ce-container">
		<?php ce_section_head( array( 'eyebrow' => ce_opt( 'process_eyebrow' ), 'title' => ce_opt( 'process_title' ), 'id' => 'ce-process-title', 'align' => 'center' ) ); ?>
		<ol class="ce-timeline" style="--n:<?php echo count( $ce_steps ); ?>">
			<?php foreach ( $ce_steps as $ce_i => $ce_step ) : ?>
				<li class="ce-timeline__step" data-reveal style="--i:<?php echo (int) $ce_i; ?>">
					<span class="ce-timeline__node" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $ce_i + 1 ) ); ?></span>
					<h3 class="ce-timeline__title"><?php echo esc_html( $ce_step['title'] ?? '' ); ?></h3>
					<?php if ( ! empty( $ce_step['text'] ) ) : ?>
						<p class="ce-timeline__text"><?php echo esc_html( $ce_step['text'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
