<?php
/**
 * SMTP: wp_mail() gönderimlerini Tema Ayarları → SMTP bilgileriyle yapar.
 * Şifre öncelik sırası: wp-config.php CE_SMTP_PASSWORD sabiti → veritabanındaki şifreli değer.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

add_action( 'phpmailer_init', 'ce_configure_smtp' );
/**
 * PHPMailer ayarları.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mailer Posta nesnesi.
 */
function ce_configure_smtp( $mailer ) {
	if ( ! ce_opt( 'smtp_enabled' ) || ! ce_opt( 'smtp_host' ) ) {
		return;
	}
	$mailer->isSMTP();
	$mailer->Host     = (string) ce_opt( 'smtp_host' );
	$mailer->Port     = (int) ce_opt( 'smtp_port', 587 );
	$mailer->Timeout  = 15;
	$user             = (string) ce_opt( 'smtp_username' );
	$pass             = defined( 'CE_SMTP_PASSWORD' ) ? (string) CE_SMTP_PASSWORD : ce_decrypt( (string) ce_opt( 'smtp_password' ) );
	$mailer->SMTPAuth = '' !== $user;
	if ( $mailer->SMTPAuth ) {
		$mailer->Username = $user;
		$mailer->Password = $pass;
	}
	$enc = ce_opt( 'smtp_encryption', 'tls' );
	if ( 'none' === $enc ) {
		$mailer->SMTPSecure  = '';
		$mailer->SMTPAutoTLS = false;
	} else {
		$mailer->SMTPSecure = $enc;
	}
	$from = ce_opt( 'smtp_from_email' );
	if ( $from && is_email( $from ) ) {
		$mailer->setFrom( $from, (string) ce_opt( 'smtp_from_name', ce_opt( 'site_name' ) ), false );
	}
}

add_filter( 'wp_mail_from_name', 'ce_mail_from_name' );
/**
 * Varsayılan gönderen adı.
 *
 * @param string $name Ad.
 * @return string
 */
function ce_mail_from_name( $name ) {
	return 'WordPress' === $name ? (string) ce_opt( 'smtp_from_name', ce_opt( 'site_name' ) ) : $name;
}

add_action( 'wp_ajax_ce_smtp_test', 'ce_smtp_test' );
/**
 * Test e-postası.
 */
function ce_smtp_test() {
	check_ajax_referer( 'ce_admin', 'nonce' );
	if ( ! current_user_can( 'ce_manage_settings' ) ) {
		wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
	}
	$error = '';
	$catch = static function ( $wp_error ) use ( &$error ) {
		$error = $wp_error->get_error_message();
	};
	add_action( 'wp_mail_failed', $catch );
	$user = wp_get_current_user();
	$ok   = wp_mail(
		$user->user_email,
		'[' . ce_opt( 'site_name' ) . '] SMTP test e-postası',
		'Bu e-posta Can Eloksal teması SMTP ayarlarını doğrulamak için gönderildi. ' . wp_date( 'd.m.Y H:i' ),
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);
	remove_action( 'wp_mail_failed', $catch );
	if ( $ok ) {
		wp_send_json_success( array( 'message' => 'Test e-postası ' . $user->user_email . ' adresine gönderildi.' ) );
	}
	wp_send_json_error( array( 'message' => 'Gönderilemedi: ' . ( $error ? $error : 'bilinmeyen hata' ) ) );
}
