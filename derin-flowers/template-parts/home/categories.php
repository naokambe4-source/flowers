<?php
/**
 * Ana sayfa — büyük editorial kategori kartları (product_cat + kategori görseli).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

if ( ! df_wc() ) {
	return;
}

$cards = array();
foreach ( (array) df_opt( 'cats_items', array() ) as $item ) {
	$term = ! empty( $item['cat'] ) ? get_term( (int) $item['cat'], 'product_cat' ) : null;
	$term = ( $term && ! is_wp_error( $term ) ) ? $term : null;
	if ( ! $term && empty( $item['image'] ) ) {
		continue;
	}
	$image = ! empty( $item['image'] ) ? (int) $item['image'] : ( $term ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0 );
	$cards[] = array(
		'title' => ! empty( $item['title'] ) ? $item['title'] : ( $term ? $term->name : '' ),
		'text'  => ! empty( $item['text'] ) ? $item['text'] : ( $term ? get_term_meta( $term->term_id, 'df_subtitle', true ) : '' ),
		'url'   => $term ? get_term_link( $term ) : wc_get_page_permalink( 'shop' ),
		'image' => $image,
	);
}

// Panelde kategori seçilmemişse en çok ürünü olan 4 kategori.
if ( ! $cards ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 4,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$cards[] = array(
				'title' => $term->name,
				'text'  => get_term_meta( $term->term_id, 'df_subtitle', true ),
				'url'   => get_term_link( $term ),
				'image' => (int) get_term_meta( $term->term_id, 'thumbnail_id', true ),
			);
		}
	}
}

if ( ! $cards ) {
	return;
}
$style = df_opt( 'cats_style', 'below' );
?>
<section data-df-sec="categories" class="df-section df-cats df-cats--<?php echo esc_attr( $style ); ?>" aria-labelledby="df-cats-title">
	<div class="df-container">
		<?php
		df_section_head(
			array(
				'eyebrow' => df_opt( 'cats_eyebrow' ),
				'title'   => df_opt( 'cats_title' ),
				'sub'     => df_opt( 'cats_sub' ),
				'id'      => 'df-cats-title',
				'keys'    => array( 'eyebrow' => 'cats_eyebrow', 'title' => 'cats_title', 'sub' => 'cats_sub' ),
			)
		);
		?>
		<div class="df-cats__grid df-scroller" data-count="<?php echo count( $cards ); ?>">
			<?php foreach ( $cards as $card ) : ?>
				<a class="df-cat" href="<?php echo esc_url( $card['url'] ); ?>">
					<span class="df-cat__media">
						<?php
						echo df_image( // phpcs:ignore
							$card['image'],
							'df-portrait',
							array(
								'sizes' => '(max-width: 700px) 78vw, (max-width: 1100px) 45vw, 25vw',
								'alt'   => $card['title'],
							),
							'Kategori görseli (3:4)'
						);
						?>
					</span>
					<span class="df-cat__body">
						<span class="df-cat__title"><?php echo esc_html( $card['title'] ); ?></span>
						<?php if ( $card['text'] ) : ?>
							<span class="df-cat__text"><?php echo esc_html( $card['text'] ); ?></span>
						<?php endif; ?>
						<span class="df-cat__cta"><?php echo esc_html( df_opt( 'cats_cta', 'KEŞFET' ) ); ?><?php df_the_icon( 'arrow-right', array( 'size' => 16 ) ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
