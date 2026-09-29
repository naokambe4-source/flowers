<?php
/**
 * Blog listesi (ana döngü): kategori sekmeleri, arama, kartlar, sayfalama, boş durum.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_blog  = (int) get_option( 'page_for_posts' );
$ce_cats  = get_categories( array( 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
$ce_curr  = is_category() ? get_queried_object_id() : 0;
?>
<section class="ce-section">
	<div class="ce-container">
		<div class="ce-toolbar">
			<?php if ( $ce_cats ) : ?>
				<nav class="ce-filter ce-filter--scroll" aria-label="Blog kategorileri">
					<a class="ce-filter__btn<?php echo $ce_curr || is_search() || is_tag() ? '' : ' is-active'; ?>" href="<?php echo esc_url( $ce_blog ? get_permalink( $ce_blog ) : home_url( '/' ) ); ?>">Tümü</a>
					<?php foreach ( $ce_cats as $ce_c ) : ?>
						<a class="ce-filter__btn<?php echo $ce_curr === $ce_c->term_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $ce_c ) ); ?>" <?php echo $ce_curr === $ce_c->term_id ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $ce_c->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
			<?php get_search_form(); ?>
		</div>

		<?php if ( have_posts() ) : ?>
			<div class="ce-cards ce-cards--3">
				<?php
				$ce_i = 0;
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/components/post-card', null, array( 'post' => get_post(), 'heading' => 'h2', 'index' => $ce_i % 3 ) );
					++$ce_i;
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'mid_size'           => 1,
					'prev_text'          => ce_icon( 'chevron-left', 18 ) . '<span class="screen-reader-text">Önceki sayfa</span>',
					'next_text'          => '<span class="screen-reader-text">Sonraki sayfa</span>' . ce_icon( 'chevron-right', 18 ),
					'screen_reader_text' => 'Sayfalar',
					'class'              => 'ce-pagination',
				)
			);
			?>
		<?php else : ?>
			<div class="ce-empty-state">
				<?php echo ce_icon( 'search', 28 ); // phpcs:ignore ?>
				<p><?php echo is_search() ? 'Aramanızla eşleşen içerik bulunamadı. Farklı bir kelime deneyin.' : 'Henüz yazı yayınlanmadı.'; ?></p>
			</div>
		<?php endif; ?>
	</div>
</section>
