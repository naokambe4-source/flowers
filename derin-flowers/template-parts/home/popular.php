<?php
/**
 * Ana sayfa — Popüler kategoriler (yuvarlak görsel + isim).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

if ( ! df_wc() ) {
	return;
}

$items = array();
foreach ( (array) df_opt( 'pop_items', array() ) as $i => $row ) {
	$term = ! empty( $row['cat'] ) ? get_term( (int) $row['cat'], 'product_cat' ) : null;
	$term = ( $term && ! is_wp_error( $term ) ) ? $term : null;
	if ( ! $term ) {
		continue;
	}
	$items[] = array(
		'title' => ! empty( $row['title'] ) ? $row['title'] : $term->name,
		'url'   => get_term_link( $term ),
		'image' => ! empty( $row['image'] ) ? (int) $row['image'] : (int) get_term_meta( $term->term_id, 'thumbnail_id', true ),
		'key'   => 'pop_items.' . $i,
	);
}

// Seçim yoksa: görseli olan kategoriler (alt kategoriler dahil), en fazla 8.
if ( ! $items ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 30,
			'orderby'    => 'menu_order',
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => 'thumbnail_id',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( array_slice( $terms, 0, 8 ) as $term ) {
			$items[] = array(
				'title' => $term->name,
				'url'   => get_term_link( $term ),
				'image' => (int) get_term_meta( $term->term_id, 'thumbnail_id', true ),
				'key'   => '',
			);
		}
	}
}

if ( ! $items ) {
	return;
}
$align = 'center' === df_opt( 'pop_align' ) ? 'center' : 'left';
?>
<section data-df-sec="popular" class="df-popular df-popular--<?php echo esc_attr( $align ); ?>" aria-labelledby="df-pop-title">
	<div class="df-container">
		<?php if ( df_opt( 'pop_title' ) ) : ?>
			<h2 class="df-popular__title" id="df-pop-title"<?php echo df_e( 'pop_title' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'pop_title' ) ); ?></h2>
		<?php endif; ?>
		<ul class="df-popular__list df-scroller">
			<?php foreach ( $items as $item ) : ?>
				<li>
					<a class="df-popular__item" href="<?php echo esc_url( $item['url'] ); ?>">
						<span class="df-popular__img"<?php echo $item['key'] ? df_i( $item['key'] . '.image' ) : ''; // phpcs:ignore ?>>
							<?php echo df_image( $item['image'], 'df-square', array( 'sizes' => '120px', 'alt' => '' ), 'Kare görsel' ); // phpcs:ignore ?>
						</span>
						<span class="df-popular__name"><?php echo esc_html( $item['title'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
