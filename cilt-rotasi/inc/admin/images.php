<?php
/**
 * Harici görselleri (ör. i.hizliresim.com) medya kütüphanesine aktarma aracı.
 *
 * Harici görseller tam boyutlu (2–3 MB PNG) yüklenir; srcset, küçük boyut ve WebP üretilmez. Bu araç
 * her harici adresi bir kez indirir ve “adres → ek kimliği” eşlemesini (cr_img_map) saklar; cr_img()
 * eşlemeyi okuyup yerel, küçük boyutlu sürümü kullanır. Ayarlar ve yazılar değiştirilmez (geri alınabilir).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adres harici bir görsel mi?
 *
 * @param mixed $url Değer.
 * @return bool
 */
function cr_is_ext_image( $url ) {
	if ( ! is_string( $url ) || ! preg_match( '#^https?://#i', $url ) ) {
		return false;
	}
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$site = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! $host || strtolower( $host ) === strtolower( (string) $site ) ) {
		return false;
	}
	return (bool) preg_match( '#\.(png|jpe?g|webp|gif)(\?.*)?$#i', $url );
}

/**
 * Sitede kullanılan tüm harici görsel adreslerini toplar (ayar değerleri ve varsayılanları, yazı ve terim görselleri).
 *
 * @return array<string,string> adres => alternatif metin
 */
function cr_ext_images_collect() {
	$found = array();
	$add   = function ( $url, $alt = '' ) use ( &$found ) {
		if ( cr_is_ext_image( $url ) && ! isset( $found[ $url ] ) ) {
			$found[ $url ] = (string) $alt;
		}
	};
	foreach ( cr_schema_fields() as $id => $f ) {
		if ( 'image' === $f['type'] ) {
			$add( cr_opt( $id ), isset( $f['label'] ) ? $f['label'] : '' );
		} elseif ( 'repeater' === $f['type'] && ! empty( $f['fields'] ) ) {
			foreach ( (array) cr_opt( $id ) as $row ) {
				foreach ( $f['fields'] as $sub => $sf ) {
					if ( 'image' === $sf['type'] && ! empty( $row[ $sub ] ) ) {
						$add( $row[ $sub ], isset( $row['title'] ) ? $row['title'] : '' );
					}
				}
			}
		}
	}
	foreach ( array( 'hero', 'structure', 'problems', 'actives', 'routine', 'derm', 'serum', 'cleanser', 'cream', 'spf', 'calm', 'sensitive', 'dry', 'acne', 'spot', 'barrier', 'aging', 'blog1', 'blog2', 'blog3', 'blog4', 'blog5', 'blog6' ) as $k ) {
		$add( cr_default_img( $k ) );
	}
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_cr_ext_image' AND meta_value <> ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( (array) $rows as $r ) {
		$add( $r->meta_value, get_the_title( $r->post_id ) );
	}
	$terms = $wpdb->get_results( "SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = 'cr_image' AND meta_value <> ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( (array) $terms as $r ) {
		$t = get_term( (int) $r->term_id );
		$add( $r->meta_value, $t && ! is_wp_error( $t ) ? $t->name : '' );
	}
	return $found;
}

/**
 * Henüz aktarılmamış harici görseller.
 *
 * @return array<string,string>
 */
function cr_ext_images_pending() {
	$map = get_option( 'cr_img_map', array() );
	$map = is_array( $map ) ? $map : array();
	$out = array();
	foreach ( cr_ext_images_collect() as $url => $alt ) {
		if ( empty( $map[ $url ] ) || ! get_post( (int) $map[ $url ] ) ) {
			$out[ $url ] = $alt;
		}
	}
	return $out;
}

/**
 * Bir parti görseli aktarır.
 *
 * @param int $limit Bu çağrıdaki en fazla görsel.
 * @return array{done:int,left:int,errors:array}
 */
function cr_ext_images_run( $limit = 3 ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$map    = get_option( 'cr_img_map', array() );
	$map    = is_array( $map ) ? $map : array();
	$failed = get_transient( 'cr_img_failed' );
	$failed = is_array( $failed ) ? $failed : array();
	$done   = 0;
	$errors = array();
	foreach ( cr_ext_images_pending() as $url => $alt ) {
		if ( isset( $failed[ $url ] ) ) {
			continue;
		}
		if ( $done + count( $errors ) >= $limit ) {
			break;
		}
		$id = media_sideload_image( $url, 0, $alt ? $alt : null, 'id' );
		if ( is_wp_error( $id ) ) {
			$failed[ $url ] = 1;
			$errors[]       = $url . ' — ' . $id->get_error_message();
			continue;
		}
		if ( $alt ) {
			update_post_meta( (int) $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}
		$map[ $url ] = (int) $id;
		update_option( 'cr_img_map', $map, true );
		$done++;
	}
	set_transient( 'cr_img_failed', $failed, HOUR_IN_SECONDS );
	$left = 0;
	foreach ( cr_ext_images_pending() as $url => $alt ) {
		if ( ! isset( $failed[ $url ] ) ) {
			$left++;
		}
	}
	if ( function_exists( 'cr_purge_caches' ) ) {
		cr_purge_caches();
	}
	return array(
		'done'   => $done,
		'left'   => $left,
		'errors' => $errors,
	);
}

/**
 * AJAX: bir parti aktar.
 */
function cr_ajax_import_images() {
	check_ajax_referer( 'cr_import_images', 'nonce' );
	if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 180 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	wp_send_json_success( cr_ext_images_run( 2 ) );
}
add_action( 'wp_ajax_cr_import_images', 'cr_ajax_import_images' );

/**
 * Araçlar sayfasındaki kart.
 */
function cr_images_card() {
	$pending = cr_ext_images_pending();
	$map     = get_option( 'cr_img_map', array() );
	$count   = count( $pending );
	?>
	<section class="cr-card-a cr-card-a--hero">
		<header class="cr-card-a__head">
			<h2>🖼 Görselleri siteye aktar (hız + SEO)</h2>
			<p>Harici adreslerden (ör. i.hizliresim.com) gelen görseller tam boyutta, sıkıştırılmadan yüklenir; sayfayı ağırlaştırır ve Google Görseller’de senin siten adına görünmez. Bu araç onları medya kütüphanesine indirir; site içinde otomatik olarak küçük, WebP ve ekrana uygun boyutlar kullanılır. Ayarların değişmez.</p>
		</header>
		<div class="cr-card-a__body">
			<p><strong data-cr-img-left><?php echo (int) $count; ?></strong> harici görsel aktarılmayı bekliyor · <strong><?php echo is_array( $map ) ? count( $map ) : 0; ?></strong> görsel aktarıldı.</p>
			<?php if ( $count ) : ?>
				<button type="button" class="cr-abtn cr-abtn--lg" data-cr-img-run>Görselleri aktar</button>
				<p class="description" data-cr-img-status>Her adımda 2 görsel indirilir; sayfayı kapatma.</p>
				<script>
				(function () {
					var btn = document.querySelector('[data-cr-img-run]');
					var st = document.querySelector('[data-cr-img-status]');
					var left = document.querySelector('[data-cr-img-left]');
					var total = 0, errs = [];
					function step() {
						var fd = new FormData();
						fd.append('action', 'cr_import_images');
						fd.append('nonce', '<?php echo esc_js( wp_create_nonce( 'cr_import_images' ) ); ?>');
						fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
							if (!r || !r.success) { st.textContent = 'Hata: ' + ((r && r.data && r.data.message) || 'bilinmeyen'); btn.disabled = false; return; }
							total += r.data.done; errs = errs.concat(r.data.errors || []);
							left.textContent = r.data.left;
							st.textContent = total + ' görsel aktarıldı, ' + r.data.left + ' kaldı…';
							if (r.data.left > 0 && (r.data.done > 0 || (r.data.errors || []).length)) { step(); }
							else { st.textContent = '✓ Bitti: ' + total + ' görsel aktarıldı.' + (errs.length ? ' İndirilemeyen: ' + errs.length + ' (sonra tekrar dene).' : ''); btn.textContent = 'Tamamlandı'; }
						}).catch(function () { st.textContent = 'Bağlantı hatası, butona tekrar bas.'; btn.disabled = false; });
					}
					btn.addEventListener('click', function () { btn.disabled = true; st.textContent = 'Başladı…'; step(); });
				})();
				</script>
			<?php else : ?>
				<p>✓ Tüm görseller sitede barındırılıyor.</p>
			<?php endif; ?>
			<p class="description">Not: Yazıların içine (editörde) eklenmiş harici görseller bu araca dahil değildir; onları editörde “Medya kütüphanesine yükle” ile değiştir.</p>
		</div>
	</section>
	<?php
}
