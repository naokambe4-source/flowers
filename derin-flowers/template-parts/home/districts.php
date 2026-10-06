<?php
/**
 * Ana sayfa — "İzmir'de hangi ilçeye çiçek göndermek istersiniz?" ilçe kısayolları.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$names = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n|,/', (string) df_opt( 'dist_items' ) ) ) );
if ( ! $names ) {
	return;
}
$base = df_opt( 'dist_url' ) ? df_url( df_opt( 'dist_url' ) ) : ( df_wc() ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
?>
<section data-df-sec="districts" class="df-districts" aria-labelledby="df-dist-title">
	<div class="df-container">
		<div class="df-districts__box">
			<h2 class="df-districts__title" id="df-dist-title"<?php echo df_e( 'dist_title' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'dist_title' ) ); ?></h2>
			<ul class="df-districts__list">
				<?php foreach ( $names as $n ) : ?>
					<?php $df_l = function_exists( 'df_loc_link_for' ) ? df_loc_link_for( $n ) : ''; ?>
					<li><a href="<?php echo esc_url( $df_l ? $df_l : $base ); ?>"><?php echo esc_html( $n ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( df_opt( 'dist_note' ) ) : ?>
				<p class="df-districts__note"<?php echo df_e( 'dist_note' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'dist_note' ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>
