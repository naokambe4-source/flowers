<?php
/**
 * İçerik sözlüğü araması: arama öncelikli editoryal bant.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$popular = cr_pairs( cr_opt( 'glossary_popular' ) );
$archive = get_post_type_archive_link( 'icerik' );
$count   = (int) wp_count_posts( 'icerik' )->publish;
?>
<section class="cr-section cr-glossary"<?php echo cr_section_attr( 'glossary' ); // phpcs:ignore ?>>
	<div class="cr-container cr-glossary__grid">
		<div class="cr-glossary__head cr-reveal">
			<p class="cr-eyebrow"<?php echo cr_edit( 'glossary_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'glossary_eyebrow' ); ?></p>
			<h2 class="cr-h2"<?php echo cr_edit( 'glossary_title' ); // phpcs:ignore ?>><?php cr_t( 'glossary_title' ); ?></h2>
			<p class="cr-lead"<?php echo cr_edit( 'glossary_text' ); // phpcs:ignore ?>><?php cr_t( 'glossary_text' ); ?></p>
			<?php if ( $count ) : ?>
				<p class="cr-glossary__count"><strong><?php echo (int) $count; ?></strong> içerik · A’dan Z’ye</p>
			<?php endif; ?>
		</div>
		<div class="cr-glossary__search cr-reveal">
			<form class="cr-linesearch" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" data-ing-search>
				<label class="screen-reader-text" for="cr-ing-q">İçerik ara</label>
				<?php echo cr_icon( 'search', 22 ); // phpcs:ignore ?>
				<input id="cr-ing-q" type="search" name="s" autocomplete="off" placeholder="<?php echo esc_attr( cr_opt( 'glossary_placeholder' ) ); ?>" aria-controls="cr-ing-results">
				<input type="hidden" name="tur" value="icerik">
				<button type="submit">Ara <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></button>
				<div class="cr-bigsearch__results" id="cr-ing-results" data-ing-results hidden></div>
			</form>
			<?php if ( $popular ) : ?>
				<ul class="cr-glossary__list">
					<?php foreach ( $popular as $i => $p ) : ?>
						<li>
							<a href="<?php echo esc_url( cr_url( isset( $p[1] ) ? $p[1] : '' ) ); ?>">
								<span class="cr-glossary__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
								<span class="cr-glossary__name"><?php echo esc_html( $p[0] ); ?></span>
								<?php echo cr_icon( 'arrow-up-right', 16 ); // phpcs:ignore ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<a class="cr-btn-line" href="<?php echo esc_url( $archive ); ?>">Sözlüğün Tamamı</a>
		</div>
	</div>
</section>
