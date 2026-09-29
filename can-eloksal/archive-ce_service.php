<?php
/**
 * Hizmetler (/hizmetler) ve hizmet kategorisi arşivi. Kategori filtresi istemci tarafında çalışır;
 * kategori bağlantıları ayrıca SEO için kendi URL'lerine sahiptir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ce_is_tax = is_tax( 'ce_service_cat' );
$ce_term   = $ce_is_tax ? get_queried_object() : null;
$ce_terms  = get_terms( array( 'taxonomy' => 'ce_service_cat', 'hide_empty' => true, 'orderby' => 'term_order' ) );

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title'    => $ce_is_tax ? $ce_term->name : ce_opt( 'services_page_title', 'HİZMETLERİMİZ' ),
		'subtitle' => $ce_is_tax && $ce_term->description ? $ce_term->description : ce_opt( 'services_page_sub' ),
		'eyebrow'  => $ce_is_tax ? 'Hizmet Kategorisi' : 'Yüzey İşlem Çözümleri',
		'image'    => ce_opt( 'services_page_image' ),
		'src'      => $ce_is_tax ? array() : array( 'title' => 'opt:services_page_title', 'subtitle' => 'opt:services_page_sub', 'image' => 'opt:services_page_image' ),
		'size'     => 'lg',
	)
);
?>
<section class="ce-section">
	<div class="ce-container">
		<?php if ( ! is_wp_error( $ce_terms ) && count( $ce_terms ) > 1 ) : ?>
			<nav class="ce-filter" aria-label="Hizmet kategorileri" <?php echo $ce_is_tax ? '' : 'data-filter'; ?>>
				<a class="ce-filter__btn<?php echo $ce_is_tax ? '' : ' is-active'; ?>" href="<?php echo esc_url( ce_services_url() ); ?>" data-filter-value="*" <?php echo $ce_is_tax ? '' : 'aria-current="page"'; ?>>Tümü</a>
				<?php foreach ( $ce_terms as $ce_t ) : ?>
					<?php $ce_cur = $ce_is_tax && $ce_term->term_id === $ce_t->term_id; ?>
					<a class="ce-filter__btn<?php echo $ce_cur ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $ce_t ) ); ?>" data-filter-value="<?php echo esc_attr( $ce_t->slug ); ?>" <?php echo $ce_cur ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $ce_t->name ); ?> <small><?php echo (int) $ce_t->count; ?></small></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="ce-services__grid ce-services__grid--archive" data-filter-target>
				<?php
				$ce_i = 0;
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/components/service-card', null, array( 'post' => get_post(), 'size' => 'md', 'heading' => 'h2', 'index' => $ce_i % 6 ) );
					++$ce_i;
				endwhile;
				?>
			</div>
			<p class="ce-empty-state" hidden data-filter-empty><?php echo ce_icon( 'search', 22 ); // phpcs:ignore ?> Bu kategoride henüz hizmet bulunmuyor.</p>
		<?php else : ?>
			<div class="ce-empty-state"><?php echo ce_icon( 'layers', 28 ); // phpcs:ignore ?><p>Henüz hizmet eklenmedi.</p></div>
		<?php endif; ?>
	</div>
</section>

<?php get_template_part( 'template-parts/home/process' ); ?>

<?php
get_footer();
