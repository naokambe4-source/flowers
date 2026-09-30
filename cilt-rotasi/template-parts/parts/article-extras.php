<?php
/**
 * Makale sonu: adım adım (HowTo), SSS, kaynaklar, sağlık uyarısı.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$pid     = get_the_ID();
$steps   = cr_steps( $pid );
$faq     = cr_faq_items( $pid );
$sources = cr_sources( $pid );
?>
<?php if ( $steps ) : ?>
	<section class="cr-steps" aria-labelledby="adim-adim">
		<h2 id="adim-adim" class="cr-steps__title"><?php echo esc_html( cr_meta( '_cr_steps_title' ) ? cr_meta( '_cr_steps_title' ) : 'Adım adım' ); ?></h2>
		<?php if ( cr_meta( '_cr_steps_time' ) ) : ?>
			<p class="cr-steps__time"><?php echo cr_icon( 'clock', 16 ); // phpcs:ignore ?> Toplam yaklaşık <?php echo (int) cr_meta( '_cr_steps_time' ); ?> dakika</p>
		<?php endif; ?>
		<ol class="cr-steps__list">
			<?php foreach ( $steps as $i => $s ) : ?>
				<li id="adim-<?php echo (int) $i + 1; ?>">
					<span class="cr-steps__num" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
					<div>
						<h3><?php echo esc_html( $s['name'] ); ?></h3>
						<?php if ( $s['text'] ) : ?>
							<p><?php echo esc_html( $s['text'] ); ?></p>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>
<?php endif; ?>

<?php if ( $faq ) : ?>
	<section class="cr-article-faq" aria-labelledby="sik-sorulan-sorular">
		<h2 id="sik-sorulan-sorular">Sık sorulan sorular</h2>
		<div class="cr-accordion">
			<?php foreach ( $faq as $i => $f ) : ?>
				<details class="cr-acc"<?php echo 0 === $i ? ' open' : ''; ?>>
					<summary><span><?php echo esc_html( $f['q'] ); ?></span><span class="cr-acc__icon" aria-hidden="true"><?php echo cr_icon( 'plus', 20 ); // phpcs:ignore ?></span></summary>
					<div class="cr-acc__body"><?php echo wp_kses_post( wpautop( $f['a'] ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( $sources ) : ?>
	<section class="cr-sources" aria-labelledby="kaynaklar">
		<h2 id="kaynaklar" class="cr-sources__title"><?php echo cr_icon( 'book', 18 ); // phpcs:ignore ?> Kaynaklar</h2>
		<ol>
			<?php foreach ( $sources as $s ) : ?>
				<li>
					<?php if ( $s['url'] ) : ?>
						<a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $s['title'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $s['title'] ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>
<?php endif; ?>

<?php if ( cr_opt( 'art_disclaimer' ) && 'page' !== get_post_type() ) : ?>
	<p class="cr-disclaimer"><?php echo cr_icon( 'info', 18 ); // phpcs:ignore ?><span><?php cr_t( 'art_disclaimer' ); ?></span></p>
<?php endif; ?>
