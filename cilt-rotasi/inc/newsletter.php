<?php
/**
 * Bülten aboneliği (AJAX). Aboneler yönetim panelinde listelenir ve CSV olarak indirilir.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abone kaydı.
 */
function cr_news_subscribe() {
	check_ajax_referer( 'cr_news', 'nonce' );
	if ( ! empty( $_POST['website'] ) ) { // Bal küpü.
		wp_send_json_success( array( 'message' => cr_opt( 'news_success' ) ) );
	}
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Geçerli bir e-posta adresi yaz.' ) );
	}
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rate = 'cr_news_' . md5( $ip );
	$n    = (int) get_transient( $rate );
	if ( $n > 5 ) {
		wp_send_json_error( array( 'message' => 'Çok fazla deneme. Biraz sonra tekrar dene.' ) );
	}
	set_transient( $rate, $n + 1, HOUR_IN_SECONDS );

	$subs = get_option( 'cr_subscribers', array() );
	if ( ! is_array( $subs ) ) {
		$subs = array();
	}
	$key = strtolower( $email );
	if ( isset( $subs[ $key ] ) ) {
		wp_send_json_success( array( 'message' => 'Zaten listedesin, teşekkürler!' ) );
	}
	$subs[ $key ] = array(
		't'   => time(),
		'src' => isset( $_POST['src'] ) ? sanitize_text_field( wp_unslash( $_POST['src'] ) ) : '',
	);
	update_option( 'cr_subscribers', $subs, false );
	do_action( 'cr_new_subscriber', $email );
	wp_send_json_success( array( 'message' => cr_opt( 'news_success' ) ) );
}
add_action( 'wp_ajax_cr_subscribe', 'cr_news_subscribe' );
add_action( 'wp_ajax_nopriv_cr_subscribe', 'cr_news_subscribe' );

/**
 * Bülten formu HTML.
 *
 * @param string $src   Kaynak (form yeri).
 * @param string $class Sınıf.
 */
function cr_news_form( $src = 'home', $class = '' ) {
	?>
	<form class="cr-news-form <?php echo esc_attr( $class ); ?>" data-news novalidate>
		<label class="screen-reader-text" for="cr-news-<?php echo esc_attr( $src ); ?>">E-posta adresi</label>
		<input id="cr-news-<?php echo esc_attr( $src ); ?>" type="email" name="email" autocomplete="email" required placeholder="<?php echo esc_attr( cr_opt( 'news_placeholder' ) ); ?>">
		<input type="text" name="website" tabindex="-1" autocomplete="off" class="cr-hp" aria-hidden="true">
		<input type="hidden" name="src" value="<?php echo esc_attr( $src ); ?>">
		<button type="submit" class="cr-btn cr-btn--primary"><span<?php echo cr_edit( 'news_button' ); // phpcs:ignore ?>><?php cr_t( 'news_button' ); ?></span> <?php echo cr_icon( 'arrow-right', 18 ); // phpcs:ignore ?></button>
		<p class="cr-news-form__msg" role="status" aria-live="polite"></p>
	</form>
	<?php
}
