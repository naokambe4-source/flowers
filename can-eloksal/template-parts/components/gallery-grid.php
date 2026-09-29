<?php
/**
 * Masonry galeri ızgarası + kategori filtresi + lightbox.
 *
 * @package CanEloksal
 *
 * @var array $args items (WP_Post[] ce_gallery veya ek ID dizisi), filter (bool), group (string), limit (int).
 */

defined( 'ABSPATH' ) || exit;

$ce_items  = $args['items'] ?? array();
$ce_filter = ! empty( $args['filter'] );
$ce_group  = $args['group'] ?? 'gallery';
if ( ! $ce_items ) {
	return;
}

// Kayıtları ortak biçime getir.
$ce_rows = array();
$ce_cats = array();
foreach ( $ce_items as $ce_item ) {
	if ( $ce_item instanceof WP_Post ) {
		$ce_id = (int) get_post_thumbnail_id( $ce_item );
		if ( ! $ce_id ) {
			continue;
		}
		$ce_terms = get_the_terms( $ce_item, 'ce_gallery_cat' );
		$ce_slugs = array();
		if ( $ce_terms && ! is_wp_error( $ce_terms ) ) {
			foreach ( $ce_terms as $ce_t ) {
				$ce_slugs[]              = $ce_t->slug;
				$ce_cats[ $ce_t->slug ] = $ce_t->name;
			}
		}
		$ce_caption = $ce_item->post_excerpt ? $ce_item->post_excerpt : $ce_item->post_title;
	} else {
		$ce_id      = absint( $ce_item );
		$ce_slugs   = array();
		$ce_caption = wp_get_attachment_caption( $ce_id );
		if ( ! $ce_caption ) {
			$ce_caption = get_the_title( $ce_id );
		}
	}
	if ( ! wp_attachment_is_image( $ce_id ) ) {
		continue;
	}
	$ce_rows[] = array( 'id' => $ce_id, 'cats' => $ce_slugs, 'caption' => $ce_caption );
}
if ( ! $ce_rows ) {
	return;
}
?>
<div class="ce-gallery" data-gallery="<?php echo esc_attr( $ce_group ); ?>">
	<?php if ( $ce_filter && count( $ce_cats ) > 1 ) : ?>
		<div class="ce-filter" role="group" aria-label="Galeri kategorileri" data-filter>
			<button type="button" class="ce-filter__btn is-active" aria-pressed="true" data-filter-value="*">Tümü</button>
			<?php foreach ( $ce_cats as $ce_slug => $ce_name ) : ?>
				<button type="button" class="ce-filter__btn" aria-pressed="false" data-filter-value="<?php echo esc_attr( $ce_slug ); ?>"><?php echo esc_html( $ce_name ); ?></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<ul class="ce-masonry" data-filter-target>
		<?php foreach ( $ce_rows as $ce_i => $ce_row ) : ?>
			<?php $ce_full = wp_get_attachment_image_src( $ce_row['id'], 'ce-hero' ); ?>
			<li class="ce-masonry__item" data-cats="<?php echo esc_attr( implode( ' ', $ce_row['cats'] ) ); ?>" data-reveal style="--i:<?php echo (int) ( $ce_i % 6 ); ?>">
				<a class="ce-masonry__link" href="<?php echo esc_url( $ce_full ? $ce_full[0] : wp_get_attachment_url( $ce_row['id'] ) ); ?>" data-lightbox="<?php echo esc_attr( $ce_group ); ?>" data-caption="<?php echo esc_attr( $ce_row['caption'] ); ?>">
					<?php echo ce_img( $ce_row['id'], 'large', array( 'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw', 'fallback_alt' => $ce_row['caption'] ) ); // phpcs:ignore ?>
					<span class="ce-masonry__zoom" aria-hidden="true"><?php echo ce_icon( 'zoom', 20 ); // phpcs:ignore ?></span>
					<?php if ( $ce_row['caption'] ) : ?>
						<span class="ce-masonry__caption"><?php echo esc_html( $ce_row['caption'] ); ?></span>
					<?php endif; ?>
					<span class="screen-reader-text">Büyüt</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="ce-empty-state" hidden data-filter-empty><?php echo ce_icon( 'image', 22 ); // phpcs:ignore ?> Bu kategoride henüz görsel bulunmuyor.</p>
</div>
