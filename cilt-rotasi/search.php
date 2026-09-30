<?php
/**
 * Arama sonuçları.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();
global $wp_query;
$cr_type  = isset( $_GET['tur'] ) ? sanitize_key( wp_unslash( $_GET['tur'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$cr_types = array(
	''             => 'Tümü',
	'post'         => 'Makaleler',
	'icerik'       => 'İçerikler',
	'urun_rehberi' => 'Ürün rehberleri',
);
?>
<section class="cr-archive-hero">
	<div class="cr-container">
		<?php cr_breadcrumb_html(); ?>
		<p class="cr-eyebrow"><?php echo esc_html( (int) $wp_query->found_posts ); ?> sonuç</p>
		<h1 class="cr-archive-hero__title">“<?php echo esc_html( get_search_query() ); ?>”</h1>
		<form class="cr-bigsearch" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
			<?php echo cr_icon( 'search', 24 ); // phpcs:ignore ?>
			<label class="screen-reader-text" for="cr-s-q">Ara</label>
			<input id="cr-s-q" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
			<?php if ( $cr_type ) : ?>
				<input type="hidden" name="tur" value="<?php echo esc_attr( $cr_type ); ?>">
			<?php endif; ?>
			<button class="cr-btn cr-btn--primary" type="submit">Ara</button>
		</form>
		<nav class="cr-filters" aria-label="Sonuç türü">
			<?php foreach ( $cr_types as $k => $label ) : ?>
				<a class="cr-filter<?php echo $k === $cr_type ? ' is-active' : ''; ?>" href="<?php echo esc_url( $k ? add_query_arg( array( 's' => get_search_query(), 'tur' => $k ), home_url( '/' ) ) : add_query_arg( 's', get_search_query(), home_url( '/' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
	</div>
</section>
<section class="cr-section cr-section--tight">
	<div class="cr-container">
		<?php if ( have_posts() ) : ?>
			<div class="cr-results">
				<?php
				while ( have_posts() ) :
					the_post();
					$type_label = array( 'post' => 'Makale', 'icerik' => 'İçerik', 'urun_rehberi' => 'Ürün rehberi', 'page' => 'Sayfa' );
					?>
					<a class="cr-result" href="<?php the_permalink(); ?>">
						<span class="cr-result__img"><?php echo cr_post_image( get_the_ID(), 'cr-thumb', array( 'alt' => '' ) ); // phpcs:ignore ?></span>
						<span class="cr-result__body">
							<span class="cr-result__type"><?php echo esc_html( isset( $type_label[ get_post_type() ] ) ? $type_label[ get_post_type() ] : '' ); ?></span>
							<span class="cr-result__title"><?php the_title(); ?></span>
							<span class="cr-result__excerpt"><?php echo esc_html( cr_excerpt( get_the_ID(), 24 ) ); ?></span>
						</span>
						<?php echo cr_icon( 'arrow-right', 20, 'cr-result__go' ); // phpcs:ignore ?>
					</a>
				<?php endwhile; ?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => cr_icon( 'arrow-left', 18 ) . '<span class="screen-reader-text">Önceki</span>',
					'next_text' => '<span class="screen-reader-text">Sonraki</span>' . cr_icon( 'arrow-right', 18 ),
					'class'     => 'cr-pagination',
				)
			);
			?>
		<?php else : ?>
			<div class="cr-empty-state">
				<?php echo cr_icon( 'search', 40 ); // phpcs:ignore ?>
				<p>Bu aramayla eşleşen içerik bulamadık. Farklı bir kelime dene ya da popüler başlıklara göz at.</p>
				<div class="cr-chips cr-chips--center">
					<?php foreach ( cr_lines( cr_opt( 'search_popular' ) ) as $p ) : ?>
						<a class="cr-chip" href="<?php echo esc_url( add_query_arg( 's', rawurlencode( $p ), home_url( '/' ) ) ); ?>"><?php echo esc_html( $p ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
