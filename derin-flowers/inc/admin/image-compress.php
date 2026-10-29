<?php
/**
 * Toplu görsel sıkıştırma: mevcut görsellerin boyutlarını güncel kalite ve WebP ayarıyla yeniden
 * üretir; en büyük kenarı sınırı aşan ana dosyaları küçültür. Araçlar & Kurulum'dan çalıştırılır,
 * 5'er görsel işler (zaman aşımı olmaz).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bir parti işle.
 */
function df_imgc_ajax() {
	check_ajax_referer( 'df_imgc', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Yetkiniz yok.' );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$ids    = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp' ), 'post_status' => 'inherit', 'numberposts' => 5, 'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids' ) );
	$cnt    = (array) wp_count_attachments( 'image' );
	$total  = (int) ( $cnt['image/jpeg'] ?? 0 ) + (int) ( $cnt['image/png'] ?? 0 ) + (int) ( $cnt['image/webp'] ?? 0 );
	$saved  = 0;
	$max    = function_exists( 'df_img_max' ) ? df_img_max() : 2400;
	$q      = function_exists( 'df_img_quality' ) ? df_img_quality() : 82;
	foreach ( $ids as $id ) {
		$file = get_attached_file( $id );
		if ( ! $file || ! file_exists( $file ) ) {
			continue;
		}
		$meta   = wp_get_attachment_metadata( $id );
		$before = df_imgc_size( $file, $meta );
		$size   = wp_getimagesize( $file );
		if ( $size && max( $size[0], $size[1] ) > $max ) {
			$ed = wp_get_image_editor( $file );
			if ( ! is_wp_error( $ed ) ) {
				$ed->resize( $max, $max, false );
				$ed->set_quality( $q );
				$ed->save( $file );
			}
		}
		$new = wp_generate_attachment_metadata( $id, $file );
		if ( $new && ! is_wp_error( $new ) ) {
			wp_update_attachment_metadata( $id, $new );
			$saved += max( 0, $before - df_imgc_size( $file, $new ) );
		}
	}
	wp_send_json_success(
		array(
			'done'  => $offset + count( $ids ),
			'total' => $total,
			'saved' => $saved,
			'end'   => count( $ids ) < 5,
		)
	);
}
add_action( 'wp_ajax_df_imgc', 'df_imgc_ajax' );

/**
 * Ana dosya + boyutların toplam baytı.
 *
 * @param string $file Dosya.
 * @param array  $meta Meta.
 * @return int
 */
function df_imgc_size( $file, $meta ) {
	$sum = (int) @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$dir = trailingslashit( dirname( $file ) );
	foreach ( (array) ( $meta['sizes'] ?? array() ) as $s ) {
		if ( ! empty( $s['file'] ) ) {
			$sum += (int) @filesize( $dir . $s['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		foreach ( (array) ( $s['sources'] ?? array() ) as $src ) {
			if ( ! empty( $src['file'] ) && $src['file'] !== ( $s['file'] ?? '' ) ) {
				$sum += (int) @filesize( $dir . $src['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}
	}
	return $sum;
}

/**
 * Araçlar sayfasındaki kutu.
 */
function df_imgc_box() {
	?>
	<div class="df-group">
		<div class="df-group__head"><h3>Görselleri sıkıştır (hız)</h3></div>
		<div class="df-group__body">
			<p class="df-group__desc">Yüklü tüm görsellerin küçük boyutları güncel kalite (%<?php echo (int) ( function_exists( 'df_img_quality' ) ? df_img_quality() : 82 ); ?>) ve <?php echo df_opt( 'img_webp', 1 ) ? 'WebP' : 'JPEG'; ?> ayarıyla yeniden üretilir; <?php echo (int) ( function_exists( 'df_img_max' ) ? df_img_max() : 2400 ); ?> px’ten büyük ana dosyalar küçültülür. Görünüm bozulmaz, sayfalar daha hızlı açılır. İşlem bitene kadar bu sayfayı kapatmayın. Yeni yüklenen görseller zaten otomatik sıkıştırılır. Ayarlar: Tema Ayarları → Ürün & Mağaza → görsel kalitesi / WebP.</p>
			<p><button type="button" class="button button-primary" id="df-imgc-run">Görselleri sıkıştır</button> <span id="df-imgc-out" style="margin-left:10px"></span></p>
			<script>
			( function () {
				var btn = document.getElementById( 'df-imgc-run' ), out = document.getElementById( 'df-imgc-out' ), saved = 0;
				btn.addEventListener( 'click', function () {
					if ( ! confirm( 'Tüm görseller yeniden sıkıştırılacak. Başlasın mı?' ) ) { return; }
					btn.disabled = true; saved = 0; step( 0 );
				} );
				function step( off ) {
					var fd = new FormData(); fd.append( 'action', 'df_imgc' ); fd.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( 'df_imgc' ) ); ?> ); fd.append( 'offset', off );
					fetch( ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
						if ( ! r.success ) { out.textContent = 'Hata: ' + ( r.data || '' ); btn.disabled = false; return; }
						saved += r.data.saved;
						out.textContent = r.data.done + ' / ' + r.data.total + ' görsel · ' + ( saved / 1048576 ).toFixed( 1 ) + ' MB küçüldü';
						if ( r.data.end ) { out.textContent += ' — tamamlandı ✓'; btn.disabled = false; } else { step( r.data.done ); }
					} ).catch( function () { out.textContent += ' — bağlantı koptu, tekrar basıp devam edin.'; btn.disabled = false; } );
				}
			}() );
			</script>
		</div>
	</div>
	<?php
}
