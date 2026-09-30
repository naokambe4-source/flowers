<?php
/**
 * İçerik sözlüğü: arama öncelikli.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$popular = cr_pairs( cr_opt( 'glossary_popular' ) );
$archive = get_post_type_archive_link( 'icerik' );
$letters = array( 'A', 'B', 'C', 'Ç', 'D', 'E', 'F', 'G', 'H', 'I', 'İ', 'J', 'K', 'L', 'M', 'N', 'O', 'Ö', 'P', 'R', 'S', 'Ş', 'T', 'U', 'Ü', 'V', 'Y', 'Z' );
?>
<section class="cr-section cr-glossary"<?php echo cr_section_attr( 'glossary' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<div class="cr-glossary__box cr-reveal">
			<svg class="cr-glossary__mol" viewBox="0 0 220 220" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.3"><path d="M110 40l52 30v60l-52 30-52-30V70z"/><path d="M162 70l40-20M58 130l-40 20M110 160v44M110 40V8"/></g><g fill="currentColor"><circle cx="110" cy="40" r="5"/><circle cx="162" cy="70" r="5"/><circle cx="162" cy="130" r="5"/><circle cx="110" cy="160" r="5"/><circle cx="58" cy="130" r="5"/><circle cx="58" cy="70" r="5"/><circle cx="202" cy="50" r="4"/><circle cx="18" cy="150" r="4"/></g></svg>
			<p class="cr-eyebrow"><?php echo cr_icon( 'flask', 14 ); // phpcs:ignore ?> INCI rehberi</p>
			<h2 class="cr-h2"<?php echo cr_edit( 'glossary_title' ); // phpcs:ignore ?>><?php cr_t( 'glossary_title' ); ?></h2>
			<p class="cr-lead"<?php echo cr_edit( 'glossary_text' ); // phpcs:ignore ?>><?php cr_t( 'glossary_text' ); ?></p>

			<form class="cr-bigsearch" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" data-ing-search>
				<label class="screen-reader-text" for="cr-ing-q">İçerik ara</label>
				<?php echo cr_icon( 'search', 24 ); // phpcs:ignore ?>
				<input id="cr-ing-q" type="search" name="s" autocomplete="off" placeholder="<?php echo esc_attr( cr_opt( 'glossary_placeholder' ) ); ?>" aria-controls="cr-ing-results">
				<input type="hidden" name="tur" value="icerik">
				<button class="cr-btn cr-btn--primary" type="submit">Ara</button>
				<div class="cr-bigsearch__results" id="cr-ing-results" data-ing-results hidden></div>
			</form>

			<?php if ( $popular ) : ?>
				<div class="cr-glossary__popular">
					<span class="cr-glossary__label">Popüler:</span>
					<?php foreach ( $popular as $p ) : ?>
						<a class="cr-chip cr-chip--soft" href="<?php echo esc_url( cr_url( isset( $p[1] ) ? $p[1] : '' ) ); ?>"><?php echo esc_html( $p[0] ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<nav class="cr-az" aria-label="Harf dizini">
				<?php foreach ( $letters as $l ) : ?>
					<a href="<?php echo esc_url( $archive . '#harf-' . rawurlencode( $l ) ); ?>"><?php echo esc_html( $l ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
	</div>
</section>
