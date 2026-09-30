<?php
/**
 * Global arama paneli (tam ekran değil, geniş panel).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$popular = cr_lines( cr_opt( 'search_popular' ) );
?>
<div class="cr-search" id="cr-search" role="dialog" aria-modal="true" aria-label="Site içi arama" hidden>
	<div class="cr-search__backdrop" data-search-close></div>
	<div class="cr-search__panel">
		<form class="cr-search__form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
			<?php echo cr_icon( 'search', 24, 'cr-search__icon' ); // phpcs:ignore ?>
			<label class="screen-reader-text" for="cr-search-input">Ara</label>
			<input id="cr-search-input" type="search" name="s" autocomplete="off" spellcheck="false" placeholder="<?php echo esc_attr( cr_opt( 'search_placeholder' ) ); ?>" aria-controls="cr-search-results" aria-autocomplete="list">
			<kbd class="cr-search__kbd" aria-hidden="true">Esc</kbd>
			<button type="button" class="cr-icon-btn cr-search__close" data-search-close aria-label="Aramayı kapat"><?php echo cr_icon( 'close', 22 ); // phpcs:ignore ?></button>
		</form>
		<div class="cr-search__tabs" role="tablist" aria-label="Sonuç türü">
			<button type="button" role="tab" class="is-active" data-search-type="" aria-selected="true">Tümü</button>
			<button type="button" role="tab" data-search-type="post" aria-selected="false">Makaleler</button>
			<button type="button" role="tab" data-search-type="icerik" aria-selected="false">İçerikler</button>
			<button type="button" role="tab" data-search-type="urun_rehberi" aria-selected="false">Ürün rehberleri</button>
		</div>
		<div class="cr-search__body">
			<div class="cr-search__empty" data-search-empty>
				<?php if ( $popular ) : ?>
					<p class="cr-search__label">Popüler aramalar</p>
					<div class="cr-chips">
						<?php foreach ( $popular as $p ) : ?>
							<button type="button" class="cr-chip" data-search-fill="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<p class="cr-search__label">Hızlı erişim</p>
				<div class="cr-search__quick">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'icerik' ) ); ?>"><?php echo cr_icon( 'flask', 20 ); // phpcs:ignore ?> İçerik sözlüğü</a>
					<a href="<?php echo esc_url( cr_url( cr_opt( 'quiz_url' ) ) ); ?>"><?php echo cr_icon( 'target', 20 ); // phpcs:ignore ?> Cilt testi</a>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'urun_rehberi' ) ); ?>"><?php echo cr_icon( 'drop', 20 ); // phpcs:ignore ?> Ürün rehberi</a>
					<a href="<?php echo esc_url( cr_saved_url() ); ?>"><?php echo cr_icon( 'bookmark', 20 ); // phpcs:ignore ?> Kaydedilenler</a>
				</div>
				<p class="cr-search__recent-wrap" hidden><span class="cr-search__label">Son aramaların</span><span class="cr-chips" data-search-recent></span></p>
			</div>
			<div class="cr-search__results" id="cr-search-results" data-search-results aria-live="polite"></div>
		</div>
		<p class="cr-search__foot"><span><kbd>↑</kbd><kbd>↓</kbd> gez</span><span><kbd>Enter</kbd> aç</span><span><kbd>/</kbd> her yerden ara</span></p>
	</div>
</div>
