<?php
/**
 * Ürün ek alanları: alt başlık, ürün videosu, içerik listesi, ölçü, bakım, ürüne özel SSS.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta kutusu.
 */
function df_product_meta_box() {
	add_meta_box( 'df-product-extra', 'Derin Flowers — Ürün Detayları, Video & SSS', 'df_product_meta_box_html', 'product', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'df_product_meta_box' );

/**
 * Meta kutusu içeriği.
 *
 * @param WP_Post $post Yazı.
 */
function df_product_meta_box_html( $post ) {
	$product   = wc_get_product( $post->ID );
	$get       = function ( $key ) use ( $product, $post ) {
		return $product ? $product->get_meta( $key ) : get_post_meta( $post->ID, $key, true );
	};
	$video_id  = absint( $get( '_df_video_id' ) );
	$video_url = $get( '_df_video_url' );
	$poster    = absint( $get( '_df_video_poster' ) );
	$pos       = $get( '_df_video_pos' ) ? $get( '_df_video_pos' ) : 'second';
	$auto      = $get( '_df_video_autoplay' );
	$faq       = $get( '_df_faq' );
	$faq       = is_array( $faq ) ? $faq : array();
	wp_nonce_field( 'df_product_meta', 'df_product_meta_nonce' );
	?>
	<div class="df-meta">
		<div class="df-meta-grid">
			<p class="df-meta-row">
				<label for="df_subtitle">Kısa alt başlık</label>
				<input type="text" class="widefat" id="df_subtitle" name="df_subtitle" value="<?php echo esc_attr( $get( '_df_subtitle' ) ); ?>" placeholder="Örn. Premium Buket">
				<span class="description">Ürün kartında ve başlık üstünde gösterilir.</span>
			</p>
			<p class="df-meta-row">
				<label for="df_size">Ölçü / boyut</label>
				<input type="text" class="widefat" id="df_size" name="df_size" value="<?php echo esc_attr( $get( '_df_size' ) ); ?>" placeholder="Örn. Yükseklik 55 cm · Genişlik 35 cm">
			</p>
		</div>

		<h4>Ürün videosu</h4>
		<div class="df-meta-grid">
			<div class="df-meta-row">
				<label>Video dosyası (MP4 / WebM — medya kütüphanesinden yükleyin)</label>
				<input type="hidden" name="df_video_id" id="df_video_id" value="<?php echo esc_attr( $video_id ? $video_id : '' ); ?>">
				<div class="df-video-preview" id="df_video_preview">
					<?php if ( $video_id ) : ?>
						<video src="<?php echo esc_url( wp_get_attachment_url( $video_id ) ); ?>" muted controls preload="metadata"></video>
					<?php endif; ?>
				</div>
				<p>
					<button type="button" class="button" id="df_video_select">Video yükle / seç</button>
					<button type="button" class="button-link-delete" id="df_video_remove" <?php echo $video_id ? '' : 'hidden'; ?>>Kaldır</button>
				</p>
			</div>
			<div class="df-meta-row">
				<label for="df_video_url">veya video bağlantısı (YouTube, Vimeo ya da MP4 URL)</label>
				<input type="url" class="widefat" id="df_video_url" name="df_video_url" value="<?php echo esc_attr( $video_url ); ?>" placeholder="https://www.youtube.com/watch?v=…">
				<label style="margin-top:12px">Video kapak görseli (opsiyonel)</label>
				<input type="hidden" name="df_video_poster" id="df_video_poster" value="<?php echo esc_attr( $poster ? $poster : '' ); ?>">
				<span id="df_poster_preview"><?php echo $poster ? wp_get_attachment_image( $poster, 'thumbnail' ) : ''; ?></span>
				<p><button type="button" class="button" id="df_poster_select">Kapak seç</button> <button type="button" class="button-link-delete" id="df_poster_remove" <?php echo $poster ? '' : 'hidden'; ?>>Kaldır</button></p>
			</div>
		</div>
		<div class="df-meta-grid">
			<p class="df-meta-row">
				<label for="df_video_pos">Galerideki konumu</label>
				<select id="df_video_pos" name="df_video_pos">
					<option value="second" <?php selected( $pos, 'second' ); ?>>Ana görselden sonra</option>
					<option value="first" <?php selected( $pos, 'first' ); ?>>İlk sırada (ana medya)</option>
					<option value="last" <?php selected( $pos, 'last' ); ?>>En sonda</option>
				</select>
			</p>
			<p class="df-meta-row">
				<label><input type="checkbox" name="df_video_autoplay" value="1" <?php checked( $auto, '1' ); ?>> Sessiz ve döngüde otomatik oynat (yalnızca MP4)</label>
			</p>
		</div>

		<h4>İçerik ve bakım</h4>
		<div class="df-meta-grid">
			<p class="df-meta-row">
				<label for="df_contents">Ürün içeriği (her satır bir madde)</label>
				<textarea class="widefat" rows="4" id="df_contents" name="df_contents" placeholder="15 adet pembe bahçe gülü&#10;Mevsim yeşillikleri&#10;Özel kraft ambalaj"><?php echo esc_textarea( $get( '_df_contents' ) ); ?></textarea>
			</p>
			<p class="df-meta-row">
				<label for="df_care">Bakım bilgisi (boşsa panel varsayılanı)</label>
				<textarea class="widefat" rows="4" id="df_care" name="df_care"><?php echo esc_textarea( $get( '_df_care' ) ); ?></textarea>
			</p>
		</div>

		<h4>Ürüne özel sıkça sorulan sorular</h4>
		<div class="df-faq-rows" id="df_faq_rows">
			<?php foreach ( $faq as $i => $row ) : ?>
				<div class="df-faq-row">
					<span class="dashicons dashicons-menu df-faq-handle"></span>
					<a href="#" class="df-faq-remove">Sil</a>
					<input type="text" name="df_faq[<?php echo (int) $i; ?>][q]" value="<?php echo esc_attr( $row['q'] ); ?>" placeholder="Soru">
					<textarea name="df_faq[<?php echo (int) $i; ?>][a]" rows="2" placeholder="Cevap"><?php echo esc_textarea( $row['a'] ); ?></textarea>
				</div>
			<?php endforeach; ?>
		</div>
		<script type="text/html" id="df_faq_tpl">
			<div class="df-faq-row">
				<span class="dashicons dashicons-menu df-faq-handle"></span>
				<a href="#" class="df-faq-remove">Sil</a>
				<input type="text" name="df_faq[__i__][q]" value="" placeholder="Soru">
				<textarea name="df_faq[__i__][a]" rows="2" placeholder="Cevap"></textarea>
			</div>
		</script>
		<p><button type="button" class="button" id="df_faq_add">+ Soru ekle</button> <span class="description">Genel SSS, Derin Flowers → Ürün & Mağaza sekmesinden yönetilir.</span></p>
	</div>
	<?php
}

/**
 * Kaydet.
 *
 * @param WC_Product $product Ürün.
 */
function df_product_meta_save( $product ) {
	if ( ! isset( $_POST['df_product_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['df_product_meta_nonce'] ) ), 'df_product_meta' ) ) {
		return;
	}
	$text = array(
		'df_subtitle' => '_df_subtitle',
		'df_size'     => '_df_size',
	);
	foreach ( $text as $field => $key ) {
		$product->update_meta_data( $key, isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '' );
	}
	foreach ( array( 'df_contents' => '_df_contents', 'df_care' => '_df_care' ) as $field => $key ) {
		$product->update_meta_data( $key, isset( $_POST[ $field ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) : '' );
	}
	$product->update_meta_data( '_df_video_id', isset( $_POST['df_video_id'] ) ? absint( $_POST['df_video_id'] ) : '' );
	$product->update_meta_data( '_df_video_poster', isset( $_POST['df_video_poster'] ) ? absint( $_POST['df_video_poster'] ) : '' );
	$product->update_meta_data( '_df_video_url', isset( $_POST['df_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['df_video_url'] ) ) : '' );
	$pos = isset( $_POST['df_video_pos'] ) ? sanitize_key( wp_unslash( $_POST['df_video_pos'] ) ) : 'second';
	$product->update_meta_data( '_df_video_pos', in_array( $pos, array( 'first', 'second', 'last' ), true ) ? $pos : 'second' );
	$product->update_meta_data( '_df_video_autoplay', empty( $_POST['df_video_autoplay'] ) ? '' : '1' );

	$faq = array();
	if ( ! empty( $_POST['df_faq'] ) && is_array( $_POST['df_faq'] ) ) {
		foreach ( wp_unslash( $_POST['df_faq'] ) as $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- satır satır temizlenir.
			$q = isset( $row['q'] ) ? sanitize_text_field( $row['q'] ) : '';
			$a = isset( $row['a'] ) ? sanitize_textarea_field( $row['a'] ) : '';
			if ( $q && $a ) {
				$faq[] = array(
					'q' => $q,
					'a' => $a,
				);
			}
		}
	}
	$product->update_meta_data( '_df_faq', $faq );
}
add_action( 'woocommerce_admin_process_product_object', 'df_product_meta_save' );

/**
 * Ürün videosu verisi.
 *
 * @param WC_Product $product Ürün.
 * @return array|null type(file|youtube|vimeo), src, poster, pos, autoplay.
 */
function df_product_video( $product ) {
	$id  = absint( $product->get_meta( '_df_video_id' ) );
	$url = (string) $product->get_meta( '_df_video_url' );
	$out = array(
		'type'     => '',
		'src'      => '',
		'embed'    => '',
		'poster'   => '',
		'pos'      => $product->get_meta( '_df_video_pos' ) ? $product->get_meta( '_df_video_pos' ) : 'second',
		'autoplay' => '1' === (string) $product->get_meta( '_df_video_autoplay' ),
	);
	$poster = absint( $product->get_meta( '_df_video_poster' ) );
	$out['poster'] = $poster ? wp_get_attachment_image_url( $poster, 'woocommerce_single' ) : '';

	if ( $id && wp_get_attachment_url( $id ) ) {
		$out['type'] = 'file';
		$out['src']  = wp_get_attachment_url( $id );
	} elseif ( $url ) {
		if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m ) ) {
			$out['type']  = 'youtube';
			$out['embed'] = 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1&playsinline=1';
			if ( ! $out['poster'] ) {
				$out['poster'] = 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
			}
		} elseif ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
			$out['type']  = 'vimeo';
			$out['embed'] = 'https://player.vimeo.com/video/' . $m[1] . '?dnt=1&title=0&byline=0';
		} elseif ( preg_match( '~\.(mp4|webm|mov)(\?.*)?$~i', $url ) ) {
			$out['type'] = 'file';
			$out['src']  = $url;
		}
	}
	if ( ! $out['type'] ) {
		return null;
	}
	if ( ! $out['poster'] && $product->get_image_id() ) {
		$out['poster'] = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_single' );
	}
	return $out;
}
