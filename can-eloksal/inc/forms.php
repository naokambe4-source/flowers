<?php
/**
 * İletişim ve teklif formları.
 *
 * Güvenlik: nonce, bal küpü (honeypot) alanı, imzalı zaman damgası (bot hız kontrolü),
 * IP başına hız sınırı, sunucu tarafı doğrulama, dosya uzantısı + MIME + içerik imzası
 * kontrolü, rastgele dosya adı ve web'den erişilemeyen gizli yükleme klasörü.
 *
 * Kayıtlar ce_message / ce_quote içerik türlerine yazılır; SMTP ayarlıysa bildirim gönderilir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Teklif dosyalarında izin verilen türler: uzantı => izinli MIME'ler.
 *
 * @return array<string,string[]>
 */
function ce_allowed_quote_files() {
	return array(
		'pdf'  => array( 'application/pdf' ),
		'jpg'  => array( 'image/jpeg' ),
		'jpeg' => array( 'image/jpeg' ),
		'png'  => array( 'image/png' ),
		'webp' => array( 'image/webp' ),
	);
}

/**
 * Talep durumları.
 *
 * @param string $type message|quote.
 * @return array<string,string>
 */
function ce_inquiry_statuses( $type = 'quote' ) {
	if ( 'message' === $type ) {
		return array(
			'new'      => 'Yeni',
			'read'     => 'Okundu',
			'replied'  => 'Geri Dönüş Yapıldı',
			'archived' => 'Arşiv',
		);
	}
	return array(
		'new'      => 'Yeni',
		'review'   => 'İnceleniyor',
		'replied'  => 'Geri Dönüş Yapıldı',
		'done'     => 'Tamamlandı',
		'archived' => 'Arşiv',
	);
}

/**
 * Gizli yükleme klasörü (yoksa oluşturur ve korur).
 *
 * @return string Mutlak yol (sonunda / yok).
 */
function ce_private_upload_dir() {
	$uploads = wp_upload_dir( null, false );
	$dir     = trailingslashit( $uploads['basedir'] ) . 'ce-private';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	$files = array(
		'.htaccess'  => "# Can Eloksal: bu klasördeki dosyalar yalnızca yönetim panelinden indirilebilir.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\nOptions -Indexes -ExecCGI\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar\n",
		'index.php'  => "<?php\n// Sessizlik altındır.\n",
		'web.config' => '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>',
	);
	foreach ( $files as $name => $content ) {
		if ( ! file_exists( $dir . '/' . $name ) ) {
			file_put_contents( $dir . '/' . $name, $content ); // phpcs:ignore
		}
	}
	return $dir;
}

/**
 * Form için imzalı zaman damgası.
 *
 * @return string
 */
function ce_form_token() {
	$ts = time();
	return $ts . '.' . substr( hash_hmac( 'sha256', (string) $ts, wp_salt( 'nonce' ) ), 0, 20 );
}

/**
 * Formun gizli güvenlik alanları.
 *
 * @param string $form contact|quote.
 */
function ce_form_security_fields( $form ) {
	wp_nonce_field( 'ce_form_' . $form, 'ce_nonce', false );
	echo '<input type="hidden" name="action" value="ce_' . esc_attr( $form ) . '">';
	echo '<input type="hidden" name="ce_ts" value="' . esc_attr( ce_form_token() ) . '">';
	echo '<input type="hidden" name="ce_ref" value="' . esc_attr( get_permalink() ) . '">';
	// Bal küpü: insanlar görmez; dolduran botlar reddedilir.
	echo '<div class="ce-hp" aria-hidden="true"><label>Web sitesi <input type="text" name="ce_website" value="" tabindex="-1" autocomplete="off"></label></div>';
}

/**
 * Ortak güvenlik kontrolleri.
 *
 * @param string $form Form.
 * @return true|string Hata mesajı.
 */
function ce_form_guard( $form ) {
	$nonce = isset( $_POST['ce_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ce_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'ce_form_' . $form ) ) {
		return 'Oturum süreniz dolmuş olabilir. Lütfen sayfayı yenileyip tekrar deneyin.';
	}
	if ( ! empty( $_POST['ce_website'] ) ) {
		return 'Gönderim doğrulanamadı.';
	}
	$token = isset( $_POST['ce_ts'] ) ? sanitize_text_field( wp_unslash( $_POST['ce_ts'] ) ) : '';
	$parts = explode( '.', $token );
	if ( 2 !== count( $parts ) || ! hash_equals( substr( hash_hmac( 'sha256', $parts[0], wp_salt( 'nonce' ) ), 0, 20 ), $parts[1] ) ) {
		return 'Gönderim doğrulanamadı. Lütfen sayfayı yenileyin.';
	}
	$age = time() - (int) $parts[0];
	if ( $age < 3 ) {
		return 'Form çok hızlı gönderildi. Lütfen birkaç saniye sonra tekrar deneyin.';
	}
	if ( $age > 2 * DAY_IN_SECONDS ) {
		return 'Sayfa çok uzun süre açık kaldı. Lütfen sayfayı yenileyin.';
	}
	$key   = 'ce_rate_' . md5( ce_client_ip() );
	$count = (int) get_transient( $key );
	if ( $count >= (int) ce_opt( 'form_rate_limit', 5 ) ) {
		return 'Kısa sürede çok fazla gönderim yapıldı. Lütfen 10 dakika sonra tekrar deneyin.';
	}
	return true;
}

/**
 * Hız sınırı sayacını artırır.
 */
function ce_form_count() {
	$key = 'ce_rate_' . md5( ce_client_ip() );
	set_transient( $key, (int) get_transient( $key ) + 1, 10 * MINUTE_IN_SECONDS );
}

/**
 * POST değeri (temizlenmiş).
 *
 * @param string $key       Anahtar.
 * @param bool   $multiline Çok satırlı.
 * @param int    $max       En fazla karakter.
 * @return string
 */
function ce_post_value( $key, $multiline = false, $max = 200 ) {
	if ( ! isset( $_POST[ $key ] ) || is_array( $_POST[ $key ] ) ) { // phpcs:ignore
		return '';
	}
	$raw   = wp_unslash( $_POST[ $key ] ); // phpcs:ignore
	$value = $multiline ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
	return mb_substr( trim( $value ), 0, $max );
}

/**
 * Telefon doğrulama (Türkiye ve uluslararası).
 *
 * @param string $phone Telefon.
 * @return bool
 */
function ce_valid_phone( $phone ) {
	$digits = preg_replace( '/\D/', '', $phone );
	return strlen( $digits ) >= 10 && strlen( $digits ) <= 15;
}

/**
 * Yanıt gönderir (AJAX: JSON, klasik: yönlendirme).
 *
 * @param bool   $ok      Başarılı mı.
 * @param string $message Mesaj.
 * @param array  $errors  Alan hataları.
 */
function ce_form_respond( $ok, $message, $errors = array() ) {
	if ( wp_doing_ajax() ) {
		$payload = array( 'message' => $message, 'errors' => (object) $errors );
		if ( $ok ) {
			wp_send_json_success( $payload );
		}
		wp_send_json_error( $payload, 422 );
	}
	$ref = isset( $_POST['ce_ref'] ) ? esc_url_raw( wp_unslash( $_POST['ce_ref'] ) ) : home_url( '/' ); // phpcs:ignore
	$ref = wp_validate_redirect( $ref, home_url( '/' ) );
	$key = 'ce_fm_' . wp_generate_password( 12, false );
	set_transient( $key, array( 'ok' => $ok, 'message' => $message, 'errors' => $errors ), 5 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'ce_fm', substr( $key, 6 ), $ref ) . '#form' );
	exit;
}

/**
 * JavaScript kapalıyken yönlendirme sonrası gösterilen mesaj.
 *
 * @return array|null
 */
function ce_form_flash() {
	$key = isset( $_GET['ce_fm'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['ce_fm'] ) ) : ''; // phpcs:ignore
	if ( ! $key ) {
		return null;
	}
	$data = get_transient( 'ce_fm_' . $key );
	delete_transient( 'ce_fm_' . $key );
	return is_array( $data ) ? $data : null;
}

add_action( 'wp_ajax_ce_contact', 'ce_handle_contact' );
add_action( 'wp_ajax_nopriv_ce_contact', 'ce_handle_contact' );
add_action( 'admin_post_ce_contact', 'ce_handle_contact' );
add_action( 'admin_post_nopriv_ce_contact', 'ce_handle_contact' );
/**
 * İletişim formu.
 */
function ce_handle_contact() {
	$guard = ce_form_guard( 'contact' );
	if ( true !== $guard ) {
		ce_form_respond( false, $guard );
	}

	$data = array(
		'name'    => ce_post_value( 'name', false, 100 ),
		'company' => ce_post_value( 'company', false, 150 ),
		'phone'   => ce_post_value( 'phone', false, 30 ),
		'email'   => sanitize_email( ce_post_value( 'email', false, 150 ) ),
		'subject' => ce_post_value( 'subject', false, 150 ),
		'message' => ce_post_value( 'message', true, 5000 ),
	);

	$errors = array();
	if ( mb_strlen( $data['name'] ) < 2 ) {
		$errors['name'] = 'Lütfen adınızı ve soyadınızı yazın.';
	}
	if ( ! ce_valid_phone( $data['phone'] ) ) {
		$errors['phone'] = 'Geçerli bir telefon numarası girin.';
	}
	if ( ! is_email( $data['email'] ) ) {
		$errors['email'] = 'Geçerli bir e-posta adresi girin.';
	}
	if ( mb_strlen( $data['message'] ) < 10 ) {
		$errors['message'] = 'Mesajınız en az 10 karakter olmalıdır.';
	}
	if ( empty( $_POST['kvkk'] ) ) { // phpcs:ignore
		$errors['kvkk'] = 'Devam etmek için KVKK metnini onaylamanız gerekir.';
	}
	if ( $errors ) {
		ce_form_respond( false, 'Lütfen işaretli alanları kontrol edin.', $errors );
	}

	ce_form_count();
	$title   = $data['name'] . ( $data['subject'] ? ' — ' . $data['subject'] : '' );
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'ce_message',
			'post_status' => 'publish',
			'post_title'  => $title,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		ce_log( 'İletişim mesajı kaydedilemedi: ' . $post_id->get_error_message() );
		ce_form_respond( false, 'Mesajınız kaydedilemedi. Lütfen telefonla ulaşın.' );
	}
	ce_store_inquiry_meta( $post_id, $data );

	ce_notify_admin(
		'Yeni iletişim mesajı — ' . $data['name'],
		array(
			'Ad Soyad' => $data['name'],
			'Firma'    => $data['company'],
			'Telefon'  => $data['phone'],
			'E-posta'  => $data['email'],
			'Konu'     => $data['subject'],
			'Mesaj'    => $data['message'],
		),
		$post_id,
		$data['email']
	);

	ce_form_respond( true, ce_opt( 'contact_success' ) );
}

add_action( 'wp_ajax_ce_quote', 'ce_handle_quote' );
add_action( 'wp_ajax_nopriv_ce_quote', 'ce_handle_quote' );
add_action( 'admin_post_ce_quote', 'ce_handle_quote' );
add_action( 'admin_post_nopriv_ce_quote', 'ce_handle_quote' );
/**
 * Teklif formu.
 */
function ce_handle_quote() {
	$guard = ce_form_guard( 'quote' );
	if ( true !== $guard ) {
		ce_form_respond( false, $guard );
	}

	$data = array(
		'company'  => ce_post_value( 'company', false, 150 ),
		'name'     => ce_post_value( 'name', false, 100 ),
		'phone'    => ce_post_value( 'phone', false, 30 ),
		'email'    => sanitize_email( ce_post_value( 'email', false, 150 ) ),
		'service'  => ce_post_value( 'service', false, 150 ),
		'material' => ce_post_value( 'material', false, 150 ),
		'quantity' => ce_post_value( 'quantity', false, 60 ),
		'surface'  => ce_post_value( 'surface', false, 100 ),
		'color'    => ce_post_value( 'color', false, 80 ),
		'micron'   => ce_post_value( 'micron', false, 40 ),
		'message'  => ce_post_value( 'message', true, 5000 ),
	);

	$errors = array();
	if ( mb_strlen( $data['company'] ) < 2 ) {
		$errors['company'] = 'Firma adını yazın.';
	}
	if ( mb_strlen( $data['name'] ) < 2 ) {
		$errors['name'] = 'Yetkili adını yazın.';
	}
	if ( ! ce_valid_phone( $data['phone'] ) ) {
		$errors['phone'] = 'Geçerli bir telefon numarası girin.';
	}
	if ( ! is_email( $data['email'] ) ) {
		$errors['email'] = 'Geçerli bir e-posta adresi girin.';
	}
	if ( '' === $data['service'] ) {
		$errors['service'] = 'Bir hizmet seçin.';
	}
	if ( empty( $_POST['kvkk'] ) ) { // phpcs:ignore
		$errors['kvkk'] = 'Devam etmek için KVKK metnini onaylamanız gerekir.';
	}

	// Dosyaları kaydetmeden önce tamamen doğrula.
	$files = ce_collect_uploads( 'files' );
	if ( is_string( $files ) ) {
		$errors['files'] = $files;
	}
	if ( $errors ) {
		ce_form_respond( false, 'Lütfen işaretli alanları kontrol edin.', $errors );
	}

	ce_form_count();
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'ce_quote',
			'post_status' => 'publish',
			'post_title'  => $data['company'] . ' — ' . $data['service'],
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		ce_log( 'Teklif talebi kaydedilemedi: ' . $post_id->get_error_message() );
		ce_form_respond( false, 'Talebiniz kaydedilemedi. Lütfen telefonla ulaşın.' );
	}

	$stored = ce_store_uploads( $files );
	ce_store_inquiry_meta( $post_id, $data );
	update_post_meta( $post_id, '_ce_files', $stored );

	$lines = array(
		'Firma'             => $data['company'],
		'Yetkili'           => $data['name'],
		'Telefon'           => $data['phone'],
		'E-posta'           => $data['email'],
		'Hizmet'            => $data['service'],
		'Malzeme'           => $data['material'],
		'Parça adedi'       => $data['quantity'],
		'Talep edilen yüzey' => $data['surface'],
		'Renk'              => $data['color'],
		'Mikron'            => $data['micron'],
		'Açıklama'          => $data['message'],
		'Dosyalar'          => $stored ? count( $stored ) . ' dosya (yönetim panelinden indirilebilir)' : '',
	);
	ce_notify_admin( 'Yeni teklif talebi — ' . $data['company'], $lines, $post_id, $data['email'] );

	ce_form_respond( true, ce_opt( 'quote_success' ) );
}

/**
 * Talep meta verilerini kaydeder.
 *
 * @param int   $post_id Kayıt.
 * @param array $data    Veriler.
 */
function ce_store_inquiry_meta( $post_id, $data ) {
	foreach ( $data as $key => $value ) {
		update_post_meta( $post_id, '_ce_f_' . $key, $value );
	}
	update_post_meta( $post_id, '_ce_status', 'new' );
	update_post_meta( $post_id, '_ce_ip', ce_client_ip() );
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	update_post_meta( $post_id, '_ce_ua', mb_substr( $ua, 0, 250 ) );
	$ref = isset( $_POST['ce_ref'] ) ? esc_url_raw( wp_unslash( $_POST['ce_ref'] ) ) : ''; // phpcs:ignore
	update_post_meta( $post_id, '_ce_source', $ref );
	update_post_meta( $post_id, '_ce_kvkk', current_time( 'mysql' ) );
}

/**
 * Yüklenen dosyaları doğrular (henüz taşımaz).
 *
 * @param string $field Alan adı.
 * @return array|string Geçerli dosya listesi veya hata mesajı.
 */
function ce_collect_uploads( $field ) {
	if ( empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ]['name'] ) ) { // phpcs:ignore
		return array();
	}
	$raw     = $_FILES[ $field ]; // phpcs:ignore
	$allowed = ce_allowed_quote_files();
	$max     = (int) ce_opt( 'quote_max_mb', 10 ) * MB_IN_BYTES;
	$limit   = (int) ce_opt( 'quote_max_files', 5 );
	$out     = array();
	$finfo   = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : null;

	foreach ( $raw['name'] as $i => $name ) {
		$error = (int) $raw['error'][ $i ];
		if ( UPLOAD_ERR_NO_FILE === $error ) {
			continue;
		}
		if ( UPLOAD_ERR_OK !== $error ) {
			return in_array( $error, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ? 'Dosya boyutu sunucu sınırını aşıyor.' : 'Dosya yüklenemedi. Lütfen tekrar deneyin.';
		}
		$tmp = $raw['tmp_name'][ $i ];
		if ( ! is_uploaded_file( $tmp ) ) {
			return 'Geçersiz dosya yüklemesi.';
		}
		if ( count( $out ) >= $limit ) {
			return sprintf( 'En fazla %d dosya yükleyebilirsiniz.', $limit );
		}
		$size = (int) $raw['size'][ $i ];
		if ( $size <= 0 || $size > $max ) {
			return sprintf( '"%s" dosyası %d MB sınırını aşıyor.', sanitize_file_name( $name ), (int) ce_opt( 'quote_max_mb', 10 ) );
		}
		$ext = strtolower( pathinfo( (string) $name, PATHINFO_EXTENSION ) );
		if ( ! isset( $allowed[ $ext ] ) ) {
			return 'Yalnızca PDF, JPG, JPEG, PNG veya WEBP dosyaları yükleyebilirsiniz.';
		}
		$mime = $finfo ? finfo_file( $finfo, $tmp ) : '';
		if ( ! $finfo || ! in_array( $mime, $allowed[ $ext ], true ) ) {
			return sprintf( '"%s" dosyasının içeriği uzantısıyla uyuşmuyor.', sanitize_file_name( $name ) );
		}
		// İçerik imzası: PDF başlığı / gerçek görsel.
		if ( 'application/pdf' === $mime ) {
			$head = (string) file_get_contents( $tmp, false, null, 0, 5 ); // phpcs:ignore
			if ( '%PDF-' !== $head ) {
				return 'Geçersiz PDF dosyası.';
			}
		} elseif ( false === @getimagesize( $tmp ) ) { // phpcs:ignore
			return sprintf( '"%s" geçerli bir görsel değil.', sanitize_file_name( $name ) );
		}
		$out[] = array(
			'tmp'  => $tmp,
			'name' => sanitize_file_name( mb_substr( (string) $name, 0, 120 ) ),
			'ext'  => 'jpeg' === $ext ? 'jpg' : $ext,
			'mime' => $mime,
			'size' => $size,
		);
	}
	if ( $finfo ) {
		finfo_close( $finfo );
	}
	return $out;
}

/**
 * Doğrulanmış dosyaları gizli klasöre rastgele adla taşır.
 *
 * @param array $files Dosyalar.
 * @return array Kayıt bilgileri (göreli yol).
 */
function ce_store_uploads( $files ) {
	if ( ! $files ) {
		return array();
	}
	$base = ce_private_upload_dir();
	$sub  = 'quotes/' . gmdate( 'Y/m' );
	wp_mkdir_p( $base . '/' . $sub );
	$stored = array();
	foreach ( $files as $file ) {
		$random = bin2hex( random_bytes( 16 ) ) . '.' . $file['ext'];
		$target = $base . '/' . $sub . '/' . $random;
		if ( move_uploaded_file( $file['tmp'], $target ) ) {
			chmod( $target, 0640 ); // phpcs:ignore
			$stored[] = array(
				'name' => $file['name'],
				'path' => $sub . '/' . $random,
				'mime' => $file['mime'],
				'size' => $file['size'],
			);
		} else {
			ce_log( 'Teklif dosyası taşınamadı: ' . $file['name'] );
		}
	}
	return $stored;
}

/**
 * Yöneticiye e-posta bildirimi.
 *
 * @param string $subject  Konu.
 * @param array  $lines    Alan => değer.
 * @param int    $post_id  Kayıt.
 * @param string $reply_to Yanıt adresi.
 */
function ce_notify_admin( $subject, $lines, $post_id, $reply_to = '' ) {
	$to = ce_opt( 'notify_email' ) ? ce_opt( 'notify_email' ) : ce_opt( 'email' );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}
	$rows = '';
	foreach ( $lines as $label => $value ) {
		if ( '' === (string) $value ) {
			continue;
		}
		$rows .= '<tr><th style="text-align:left;padding:10px 14px;background:#F4F7F8;border-bottom:1px solid #DCE3E7;width:180px;font:600 13px Arial,sans-serif;color:#17212B;vertical-align:top">' . esc_html( $label ) . '</th><td style="padding:10px 14px;border-bottom:1px solid #DCE3E7;font:14px/1.6 Arial,sans-serif;color:#17212B">' . nl2br( esc_html( $value ) ) . '</td></tr>';
	}
	$link = admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' );
	$body = '<div style="background:#F4F7F8;padding:24px"><div style="max-width:640px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #DCE3E7">'
		. '<div style="background:#0A1118;color:#fff;padding:20px 24px;font:700 16px Arial,sans-serif;letter-spacing:.04em">' . esc_html( ce_opt( 'site_name' ) ) . ' <span style="color:#19C4D2">•</span> ' . esc_html( $subject ) . '</div>'
		. '<table style="width:100%;border-collapse:collapse">' . $rows . '</table>'
		. '<div style="padding:20px 24px"><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#00AFC1;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;font:600 14px Arial,sans-serif">Yönetim panelinde aç</a></div>'
		. '</div></div>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $reply_to && is_email( $reply_to ) ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	}
	$sent = wp_mail( $to, '[' . ce_opt( 'site_name' ) . '] ' . $subject, $body, $headers );
	update_post_meta( $post_id, '_ce_mail_sent', $sent ? 1 : 0 );
	if ( ! $sent ) {
		ce_log( 'Bildirim e-postası gönderilemedi (#' . $post_id . '). SMTP ayarlarını kontrol edin.' );
	}
}

/**
 * Tema hata günlüğü (uploads/ce-private/logs/ — web'den erişilemez).
 *
 * @param string $message Mesaj.
 */
function ce_log( $message ) {
	$dir = ce_private_upload_dir() . '/logs';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	$line = '[' . gmdate( 'Y-m-d H:i:s' ) . ' UTC] ' . $message . "\n";
	file_put_contents( $dir . '/ce-' . gmdate( 'Y-m' ) . '.log', $line, FILE_APPEND | LOCK_EX ); // phpcs:ignore
	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		error_log( 'Can Eloksal: ' . $message ); // phpcs:ignore
	}
}

add_action( 'before_delete_post', 'ce_delete_inquiry_files' );
/**
 * Teklif kalıcı silinince dosyalarını da siler.
 *
 * @param int $post_id Kayıt.
 */
function ce_delete_inquiry_files( $post_id ) {
	if ( 'ce_quote' !== get_post_type( $post_id ) ) {
		return;
	}
	$base = realpath( ce_private_upload_dir() );
	foreach ( array_filter( (array) get_post_meta( $post_id, '_ce_files', true ) ) as $file ) {
		$path = realpath( $base . '/' . $file['path'] );
		if ( $path && 0 === strpos( $path, $base . DIRECTORY_SEPARATOR ) && is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}
}
