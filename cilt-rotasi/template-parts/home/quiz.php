<?php
/**
 * Cilt tipini keşfet: yüz diyagramı + test çağrısı (açık sage zemin).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$points = array_slice( cr_lines( cr_opt( 'quiz_points' ) ), 0, 4 );
?>
<section class="cr-section cr-quizcta"<?php echo cr_section_attr( 'quiz' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<div class="cr-quizcta__box">
			<div class="cr-quizcta__visual cr-reveal" aria-hidden="true">
				<svg class="cr-face" viewBox="0 0 400 440" fill="none">
					<defs>
						<radialGradient id="crFaceGlow" cx="50%" cy="45%" r="55%">
							<stop offset="0" stop-color="#fff" stop-opacity=".95"/>
							<stop offset="1" stop-color="#fff" stop-opacity="0"/>
						</radialGradient>
					</defs>
					<circle cx="200" cy="215" r="190" fill="url(#crFaceGlow)"/>
					<circle cx="200" cy="215" r="176" stroke="currentColor" stroke-opacity=".18" stroke-dasharray="2 8"/>
					<path class="cr-face__line" d="M200 58c70 0 112 52 112 128 0 96-50 176-112 176S88 282 88 186c0-76 42-128 112-128z" stroke="currentColor" stroke-width="1.6"/>
					<path class="cr-face__line" d="M150 176c10-8 26-8 36 0M214 176c10-8 26-8 36 0M200 196v44c0 6-6 10-14 10M176 290c16 10 32 10 48 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
					<g class="cr-face__zones">
						<ellipse cx="200" cy="112" rx="58" ry="22" fill="currentColor" fill-opacity=".08"/>
						<ellipse cx="136" cy="246" rx="30" ry="24" fill="currentColor" fill-opacity=".08"/>
						<ellipse cx="264" cy="246" rx="30" ry="24" fill="currentColor" fill-opacity=".08"/>
						<rect x="186" y="150" width="28" height="100" rx="14" fill="currentColor" fill-opacity=".08"/>
						<ellipse cx="200" cy="330" rx="36" ry="18" fill="currentColor" fill-opacity=".08"/>
					</g>
					<g class="cr-face__dots">
						<circle cx="200" cy="112" r="6"/><circle cx="136" cy="246" r="6"/><circle cx="200" cy="210" r="6"/><circle cx="200" cy="330" r="6"/>
					</g>
				</svg>
				<?php
				$pos = array( 'cr-face__tag--1', 'cr-face__tag--2', 'cr-face__tag--3', 'cr-face__tag--4' );
				foreach ( $points as $i => $p ) :
					?>
					<span class="cr-face__tag <?php echo esc_attr( $pos[ $i ] ); ?>"><?php echo esc_html( $p ); ?></span>
				<?php endforeach; ?>
			</div>
			<div class="cr-quizcta__copy cr-reveal">
				<p class="cr-eyebrow"><?php echo cr_icon( 'target', 14 ); // phpcs:ignore ?> Cilt testi</p>
				<h2 class="cr-h2"<?php echo cr_edit( 'quiz_title' ); // phpcs:ignore ?>><?php cr_t( 'quiz_title' ); ?></h2>
				<p class="cr-lead"<?php echo cr_edit( 'quiz_text' ); // phpcs:ignore ?>><?php cr_t( 'quiz_text' ); ?></p>
				<ul class="cr-quizcta__list">
					<li><?php echo cr_icon( 'check', 18 ); // phpcs:ignore ?> Cilt tipi + mevcut durum</li>
					<li><?php echo cr_icon( 'check', 18 ); // phpcs:ignore ?> Kişisel bakım rotası</li>
					<li><?php echo cr_icon( 'check', 18 ); // phpcs:ignore ?> Kayıt gerekmez</li>
				</ul>
				<div class="cr-quizcta__actions">
					<a class="cr-btn cr-btn--primary cr-btn--lg" href="<?php echo esc_url( cr_url( cr_opt( 'quiz_url' ) ) ); ?>"><span<?php echo cr_edit( 'quiz_cta' ); // phpcs:ignore ?>><?php cr_t( 'quiz_cta' ); ?></span><?php echo cr_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
					<span class="cr-quizcta__note"><?php echo cr_icon( 'clock', 16 ); // phpcs:ignore ?><span<?php echo cr_edit( 'quiz_note' ); // phpcs:ignore ?>><?php cr_t( 'quiz_note' ); ?></span></span>
				</div>
			</div>
		</div>
	</div>
</section>
