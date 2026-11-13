<?php
/**
 * Ortam kitaplığında "Kullanıldı" işareti: bir ürünün ana görseli ya da galerisinde kullanılan görsellerin
 * köşesinde yeşil etiket çıkar, üzerine gelince hangi üründe kullanıldığı görünür. Liste görünümünde ayrı sütun.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Görsel → kullanıldığı ürünler haritası (önbellekli; ürün kaydında yenilenir).
 *
 * @return array<int,int[]>
 */
function df_mu_map() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$map = get_transient( 'df_media_used' );
	if ( is_array( $map ) ) {
		$cache = $map;
		return $map;
	}
	global $wpdb;
	$map  = array();
	$rows = $wpdb->get_results( "SELECT pm.post_id, pm.meta_key, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'product' AND p.post_status IN ('publish','draft','pending','private') AND pm.meta_key IN ('_thumbnail_id','_product_image_gallery') AND pm.meta_value <> ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( (array) $rows as $r ) {
		foreach ( array_filter( array_map( 'absint', explode( ',', (string) $r->meta_value ) ) ) as $aid ) {
			$map[ $aid ][] = (int) $r->post_id;
		}
	}
	foreach ( $map as $k => $v ) {
		$map[ $k ] = array_values( array_unique( $v ) );
	}
	set_transient( 'df_media_used', $map, DAY_IN_SECONDS );
	$cache = $map;
	return $map;
}

/**
 * Ürün kaydında haritayı yenile.
 *
 * @param int $post_id Yazı.
 */
function df_mu_flush( $post_id ) {
	if ( in_array( get_post_type( $post_id ), array( 'product', 'attachment' ), true ) ) {
		delete_transient( 'df_media_used' );
	}
}
add_action( 'save_post', 'df_mu_flush' );
add_action( 'deleted_post', 'df_mu_flush' );

/**
 * Kitaplık penceresi (ızgara) için görsele kullanım bilgisi ekle.
 *
 * @param array   $resp Yanıt.
 * @param WP_Post $att  Görsel.
 * @return array
 */
function df_mu_js( $resp, $att ) {
	$map = df_mu_map();
	if ( ! empty( $map[ $att->ID ] ) ) {
		$names = array();
		foreach ( array_slice( $map[ $att->ID ], 0, 3 ) as $pid ) {
			$names[] = get_the_title( $pid );
		}
		$resp['dfUsed'] = count( $map[ $att->ID ] );
		$resp['dfUsedIn'] = implode( ', ', $names ) . ( count( $map[ $att->ID ] ) > 3 ? ' …' : '' );
	}
	return $resp;
}
add_filter( 'wp_prepare_attachment_for_js', 'df_mu_js', 10, 2 );

/**
 * Izgara görünümünde rozet.
 */
function df_mu_assets() {
	if ( ! wp_script_is( 'media-views', 'enqueued' ) && ! wp_script_is( 'media-grid', 'enqueued' ) ) {
		return;
	}
	?>
	<style>
		.attachment .df-used{position:absolute;left:6px;top:6px;z-index:5;padding:2px 7px;border-radius:10px;background:#2e7d32;color:#fff;font-size:10px;font-weight:600;line-height:1.5;box-shadow:0 1px 3px rgba(0,0,0,.3);pointer-events:none}
		.attachment.df-is-used .thumbnail{opacity:.82}
	</style>
	<script>
	( function () {
		function patch() {
			if ( ! window.wp || ! wp.media || ! wp.media.view || ! wp.media.view.Attachment ) { return false; }
			var A = wp.media.view.Attachment, render = A.prototype.render;
			A.prototype.render = function () {
				render.apply( this, arguments );
				var n = this.model.get( 'dfUsed' );
				this.$el.toggleClass( 'df-is-used', !! n ).find( '.df-used' ).remove();
				if ( n ) {
					this.$el.find( '.attachment-preview' ).append( '<span class="df-used" title="' + String( this.model.get( 'dfUsedIn' ) || '' ).replace( /"/g, '&quot;' ) + '">Kullanıldı' + ( n > 1 ? ' ×' + n : '' ) + '</span>' );
					this.$el.attr( 'title', 'Kullanıldığı ürün: ' + ( this.model.get( 'dfUsedIn' ) || '' ) );
				}
				return this;
			};
			return true;
		}
		if ( ! patch() ) { document.addEventListener( 'DOMContentLoaded', patch ); }
	}() );
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'df_mu_assets', 5 );

/**
 * Liste görünümünde "Kullanım" sütunu.
 *
 * @param array $cols Sütunlar.
 * @return array
 */
function df_mu_col( $cols ) {
	$cols['df_used'] = 'Kullanım';
	return $cols;
}
add_filter( 'manage_media_columns', 'df_mu_col' );

/**
 * Sütun içeriği.
 *
 * @param string $col Sütun.
 * @param int    $id  Görsel.
 */
function df_mu_col_val( $col, $id ) {
	if ( 'df_used' !== $col ) {
		return;
	}
	$map = df_mu_map();
	if ( empty( $map[ $id ] ) ) {
		echo '<span style="color:#8c8f94">Kullanılmadı</span>';
		return;
	}
	$links = array();
	foreach ( array_slice( $map[ $id ], 0, 3 ) as $pid ) {
		$links[] = '<a href="' . esc_url( get_edit_post_link( $pid ) ) . '">' . esc_html( get_the_title( $pid ) ) . '</a>';
	}
	echo '<strong style="color:#2e7d32">Kullanıldı</strong><br>' . implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'manage_media_custom_column', 'df_mu_col_val', 10, 2 );
