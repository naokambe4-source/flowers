<?php
/**
 * Ürün rehberi bilgi kartı (satış yok).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$pid    = get_the_ID();
$brand  = cr_meta( '_cr_brand' );
$rating = cr_meta( '_cr_rating' );
$price  = cr_meta( '_cr_price' );
$ingr   = cr_lines( cr_meta( '_cr_key_ingr' ) );
$pros   = cr_lines( cr_meta( '_cr_pros' ) );
$cons   = cr_lines( cr_meta( '_cr_cons' ) );
$facts  = array_filter(
	array(
		'Marka'       => $brand,
		'Hacim'       => cr_meta( '_cr_size' ),
		'Doku'        => cr_meta( '_cr_texture' ),
		'Kimler için' => cr_meta( '_cr_for' ),
		'Fiyat'       => $price ? str_repeat( '₺', (int) $price ) : '',
	)
);
?>
<div class="cr-pcard">
	<div class="cr-pcard__top">
		<?php if ( '0' !== (string) get_post_meta( $pid, '_cr_independent', true ) ) : ?>
			<span class="cr-pill-note cr-pill-note--sm"><?php echo cr_icon( 'shield', 16 ); // phpcs:ignore ?> Sponsorlu değil · bağımsız inceleme</span>
		<?php endif; ?>
		<?php if ( $rating ) : ?>
			<span class="cr-rating" aria-label="<?php echo esc_attr( 'Editör puanı ' . $rating . ' / 5' ); ?>">
				<?php
				for ( $s = 1; $s <= 5; $s++ ) {
					echo '<span class="cr-rating__star' . ( $s <= floor( (float) $rating ) ? ' is-on' : ( $s - 0.5 <= (float) $rating ? ' is-half' : '' ) ) . '">' . cr_icon( 'star', 16 ) . '</span>'; // phpcs:ignore
				}
				?>
				<strong><?php echo esc_html( str_replace( '.', ',', $rating ) ); ?></strong>
			</span>
		<?php endif; ?>
	</div>
	<?php if ( $facts ) : ?>
		<dl class="cr-facts">
			<?php foreach ( $facts as $k => $v ) : ?>
				<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
	<?php if ( $ingr ) : ?>
		<div class="cr-pcard__ingr">
			<p class="cr-pcard__label">Öne çıkan içerikler</p>
			<div class="cr-chips">
				<?php foreach ( $ingr as $n ) : ?>
					<?php $l = cr_ingredient_link( $n ); ?>
					<?php if ( $l ) : ?>
						<a class="cr-chip cr-chip--soft" href="<?php echo esc_url( $l ); ?>"><?php echo cr_icon( 'flask', 14 ); // phpcs:ignore ?><?php echo esc_html( $n ); ?></a>
					<?php else : ?>
						<span class="cr-chip"><?php echo esc_html( $n ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
	<?php if ( $pros || $cons ) : ?>
		<div class="cr-proscons">
			<?php if ( $pros ) : ?>
				<div class="cr-proscons__col cr-proscons__col--pro">
					<p class="cr-pcard__label">Artıları</p>
					<ul><?php foreach ( $pros as $p ) : ?><li><?php echo cr_icon( 'check', 16 ); // phpcs:ignore ?><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
				</div>
			<?php endif; ?>
			<?php if ( $cons ) : ?>
				<div class="cr-proscons__col cr-proscons__col--con">
					<p class="cr-pcard__label">Dikkat</p>
					<ul><?php foreach ( $cons as $p ) : ?><li><?php echo cr_icon( 'minus', 16 ); // phpcs:ignore ?><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( cr_meta( '_cr_usage' ) ) : ?>
		<div class="cr-pcard__usage"><p class="cr-pcard__label">Nasıl kullanılır?</p><p><?php echo esc_html( cr_meta( '_cr_usage' ) ); ?></p></div>
	<?php endif; ?>
	<?php if ( cr_meta( '_cr_verdict' ) ) : ?>
		<blockquote class="cr-verdict"><p class="cr-pcard__label">Editör özeti</p><p><?php echo esc_html( cr_meta( '_cr_verdict' ) ); ?></p></blockquote>
	<?php endif; ?>
	<?php if ( cr_meta( '_cr_info_link' ) ) : ?>
		<a class="cr-link" href="<?php echo esc_url( cr_meta( '_cr_info_link' ) ); ?>" target="_blank" rel="nofollow noopener">Resmî ürün sayfası <?php echo cr_icon( 'arrow-up-right', 14 ); // phpcs:ignore ?></a>
	<?php endif; ?>
</div>
