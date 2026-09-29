<?php
/**
 * Favoriler: üyeler için kullanıcı meta, ziyaretçiler için çerez. Girişte birleştirilir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

const DF_WISHLIST_COOKIE = 'df_wishlist';

/**
 * Favori ürün ID'leri.
 *
 * @return int[]
 */
function df_wishlist_ids() {
	$ids = array();
	if ( is_user_logged_in() ) {
		$ids = (array) get_user_meta( get_current_user_id(), '_df_wishlist', true );
	} elseif ( ! empty( $_COOKIE[ DF_WISHLIST_COOKIE ] ) ) {
		$ids = explode( ',', sanitize_text_field( wp_unslash( $_COOKIE[ DF_WISHLIST_COOKIE ] ) ) );
	}
	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

/**
 * Favorileri kaydeder.
 *
 * @param int[] $ids ID'ler.
 */
function df_wishlist_save( $ids ) {
	$ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ), 0, 100 );
	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), '_df_wishlist', $ids );
	} else {
		setcookie( DF_WISHLIST_COOKIE, implode( ',', $ids ), time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
	}
	return $ids;
}

/**
 * AJAX: favori ekle/çıkar.
 */
function df_ajax_wishlist_toggle() {
	check_ajax_referer( 'df_nonce', 'nonce' );
	$pid = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	if ( ! $pid || 'product' !== get_post_type( $pid ) ) {
		wp_send_json_error();
	}
	$ids = df_wishlist_ids();
	$key = array_search( $pid, $ids, true );
	if ( false === $key ) {
		$ids[]  = $pid;
		$status = 'added';
	} else {
		unset( $ids[ $key ] );
		$status = 'removed';
	}
	$ids = df_wishlist_save( $ids );
	wp_send_json_success(
		array(
			'status' => $status,
			'ids'    => $ids,
			'count'  => count( $ids ),
		)
	);
}
add_action( 'wp_ajax_df_wishlist_toggle', 'df_ajax_wishlist_toggle' );
add_action( 'wp_ajax_nopriv_df_wishlist_toggle', 'df_ajax_wishlist_toggle' );

/**
 * Girişte çerezdeki favorileri hesaba taşır.
 *
 * @param string  $login Kullanıcı adı.
 * @param WP_User $user  Kullanıcı.
 */
function df_wishlist_merge_on_login( $login, $user ) {
	if ( empty( $_COOKIE[ DF_WISHLIST_COOKIE ] ) ) {
		return;
	}
	$cookie = array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_COOKIE[ DF_WISHLIST_COOKIE ] ) ) ) );
	$saved  = (array) get_user_meta( $user->ID, '_df_wishlist', true );
	update_user_meta( $user->ID, '_df_wishlist', array_values( array_unique( array_filter( array_map( 'absint', array_merge( $saved, $cookie ) ) ) ) ) );
	setcookie( DF_WISHLIST_COOKIE, '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
}
add_action( 'wp_login', 'df_wishlist_merge_on_login', 10, 2 );

/**
 * Favori butonu.
 *
 * @param int    $product_id Ürün.
 * @param string $class      Ek sınıf.
 * @return string
 */
function df_wishlist_button( $product_id, $class = '' ) {
	$active = in_array( (int) $product_id, df_wishlist_ids(), true );
	return sprintf(
		'<button type="button" class="df-fav %1$s%2$s" data-product="%3$d" aria-pressed="%4$s" aria-label="%5$s">%6$s</button>',
		esc_attr( $class ),
		$active ? ' is-active' : '',
		(int) $product_id,
		$active ? 'true' : 'false',
		esc_attr( $active ? 'Favorilerden çıkar' : 'Favorilere ekle' ),
		df_icon( 'heart', array( 'size' => 20 ) )
	);
}

/**
 * [derin_favoriler] kısa kodu.
 *
 * @return string
 */
function df_wishlist_shortcode() {
	if ( ! df_wc() ) {
		return '';
	}
	$ids = df_wishlist_ids();
	ob_start();
	echo '<div class="df-wishlist" data-df-wishlist-page>';
	if ( ! $ids ) {
		echo '<div class="df-empty">';
		df_the_icon( 'heart', array( 'size' => 40 ) );
		echo '<h2>Favori listeniz boş</h2><p>Beğendiğiniz tasarımları kalp ikonuna dokunarak buraya ekleyebilirsiniz.</p>';
		echo df_button( 'Koleksiyonu keşfet', wc_get_page_permalink( 'shop' ) ); // phpcs:ignore
		echo '</div>';
	} else {
		$products = wc_get_products(
			array(
				'include' => $ids,
				'limit'   => count( $ids ),
				'orderby' => 'post__in',
				'status'  => 'publish',
			)
		);
		echo '<div class="df-grid df-grid--4">';
		foreach ( $products as $product ) {
			df_product_card( $product );
		}
		echo '</div>';
	}
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'derin_favoriler', 'df_wishlist_shortcode' );
