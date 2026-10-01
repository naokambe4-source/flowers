<?php
/**
 * Cilt testi çağrısı: görsel + editoryal metin (kum zemin).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$points = array_slice( cr_lines( cr_opt( 'quiz_points' ) ), 0, 4 );
?>
<section class="cr-section cr-quizcta"<?php echo cr_section_attr( 'quiz' ); // phpcs:ignore ?>>
	<div class="cr-container cr-quizcta__grid">
		<div class="cr-quizcta__copy cr-reveal">
			<p class="cr-eyebrow cr-eyebrow--line">Cilt Testi</p>
			<h2 class="cr-h2"<?php echo cr_edit( 'quiz_title' ); // phpcs:ignore ?>><?php cr_t( 'quiz_title' ); ?></h2>
			<p class="cr-lead"<?php echo cr_edit( 'quiz_text' ); // phpcs:ignore ?>><?php cr_t( 'quiz_text' ); ?></p>
			<?php if ( $points ) : ?>
				<ol class="cr-quizcta__list">
					<?php foreach ( $points as $p ) : ?>
						<li><?php echo esc_html( $p ); ?></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
			<div class="cr-quizcta__actions">
				<a class="cr-btn cr-btn--solid" href="<?php echo esc_url( cr_url( cr_opt( 'quiz_url' ) ) ); ?>"><span<?php echo cr_edit( 'quiz_cta' ); // phpcs:ignore ?>><?php cr_t( 'quiz_cta' ); ?></span></a>
				<span class="cr-quizcta__note"><?php echo cr_icon( 'clock', 16 ); // phpcs:ignore ?><span<?php echo cr_edit( 'quiz_note' ); // phpcs:ignore ?>><?php cr_t( 'quiz_note' ); ?></span></span>
			</div>
		</div>
		<a class="cr-quizcta__media cr-reveal" href="<?php echo esc_url( cr_url( cr_opt( 'quiz_url' ) ) ); ?>" tabindex="-1" aria-hidden="true"<?php echo cr_edit_img( 'quiz_image' ); // phpcs:ignore ?>>
			<?php echo cr_img( cr_opt( 'quiz_image' ), 'cr-card', array( 'alt' => '', 'class' => 'cr-zoom', 'sizes' => '(max-width: 900px) 100vw, 40vw' ) ); // phpcs:ignore ?>
			<span class="cr-quizcta__badge"><strong><?php echo (int) max( 1, count( (array) cr_opt( 'qz_questions' ) ) ); ?></strong> soru<br><em>kayıt gerekmez</em></span>
		</a>
	</div>
</section>
