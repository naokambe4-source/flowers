<?php
/**
 * Ürün kategorisi ek alanları: ikon, kapak görseli, kısa açıklama.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kategori ekleme formu.
 */
function df_cat_add_fields() {
	wp_nonce_field( 'df_term_meta', 'df_term_meta_nonce' );
	?>
	<div class="form-field">
		<label for="df_icon">Menü ikonu</label>
		<select name="df_icon" id="df_icon">
			<?php foreach ( df_icon_choices( true ) as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p>Header kategori menüsünde ve navigasyonda gösterilir.</p>
	</div>
	<div class="form-field">
		<label for="df_subtitle">Kısa alt başlık</label>
		<input type="text" name="df_subtitle" id="df_subtitle" value="">
		<p>Kategori kartlarında ve kategori sayfası başlığında gösterilir.</p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'df_cat_add_fields' );

/**
 * Kategori düzenleme formu.
 *
 * @param WP_Term $term Terim.
 */
function df_cat_edit_fields( $term ) {
	$icon     = get_term_meta( $term->term_id, 'df_icon', true );
	$subtitle = get_term_meta( $term->term_id, 'df_subtitle', true );
	$banner   = absint( get_term_meta( $term->term_id, 'df_banner', true ) );
	wp_nonce_field( 'df_term_meta', 'df_term_meta_nonce' );
	wp_enqueue_media();
	?>
	<tr class="form-field">
		<th scope="row"><label for="df_icon">Menü ikonu</label></th>
		<td>
			<select name="df_icon" id="df_icon">
				<?php foreach ( df_icon_choices( true ) as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="df-icon-preview" style="display:inline-block;vertical-align:middle;margin-left:8px"><?php echo $icon ? df_icon( $icon, array( 'size' => 26 ) ) : ''; // phpcs:ignore ?></span>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="df_subtitle">Kısa alt başlık</label></th>
		<td><input type="text" name="df_subtitle" id="df_subtitle" value="<?php echo esc_attr( $subtitle ); ?>"></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label>Kategori sayfası kapak görseli</label></th>
		<td>
			<div class="df-media" data-size="medium">
				<input type="hidden" name="df_banner" value="<?php echo esc_attr( $banner ); ?>" class="df-media__input">
				<div class="df-media__preview"><?php echo $banner ? wp_get_attachment_image( $banner, 'medium' ) : ''; ?></div>
				<button type="button" class="button df-media__select">Görsel seç</button>
				<button type="button" class="button-link-delete df-media__remove" <?php echo $banner ? '' : 'hidden'; ?>>Kaldır</button>
			</div>
			<p class="description">Geniş, yatay görsel (en az 2000px). Boş bırakılırsa sade başlık alanı kullanılır.</p>
		</td>
	</tr>
	<?php $banner_m = absint( get_term_meta( $term->term_id, 'df_banner_mobile', true ) ); ?>
	<tr class="form-field">
		<th scope="row"><label>Mobil kapak görseli (opsiyonel)</label></th>
		<td>
			<div class="df-media" data-size="medium">
				<input type="hidden" name="df_banner_mobile" value="<?php echo esc_attr( $banner_m ); ?>" class="df-media__input">
				<div class="df-media__preview"><?php echo $banner_m ? wp_get_attachment_image( $banner_m, 'medium' ) : ''; ?></div>
				<button type="button" class="button df-media__select">Görsel seç</button>
				<button type="button" class="button-link-delete df-media__remove" <?php echo $banner_m ? '' : 'hidden'; ?>>Kaldır</button>
			</div>
			<p class="description">Telefonda gösterilir (dikey / kare görsel önerilir). Boşsa masaüstü görseli kullanılır.</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="df_banner_pos">Kapak görseli</label></th>
		<td>
			<?php $pos = get_term_meta( $term->term_id, 'df_banner_pos', true ); ?>
			<input type="hidden" name="df_banner_text" value="below">
			<select name="df_banner_pos" id="df_banner_pos">
				<?php foreach ( array( 'right' => 'Odak: sağ (önerilen)', 'top' => 'Odak: sağ üst', 'bottom' => 'Odak: sağ alt', 'mid' => 'Odak: orta', 'left' => 'Odak: sol' ) as $k => $l ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( in_array( $pos, array( 'top', 'bottom', 'mid', 'left' ), true ) ? $pos : 'right', $k ); ?>><?php echo esc_html( $l ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description">Görsel başlığın <strong>sağ tarafında</strong> durur ve sol kenarı degradeye yumuşakça karışır. Sadece buket/ürün fotoğrafı yükleyin, <strong>üzerinde yazı olmasın</strong> (başlığı tema yazar). Önerilen ölçü: <strong>1600 × 1000 px</strong>, JPG. Kapak yüklemezseniz kategori görseli, o da yoksa kategorideki ilk ürünün görseli kullanılır.</p>
			<?php $hc = (string) get_term_meta( $term->term_id, 'df_hero_color', true ); ?>
			<p style="margin-top:12px"><label>Degrade rengi: <input type="color" name="df_hero_color" value="<?php echo esc_attr( $hc ? $hc : df_cat_hero_color( $term ) ); ?>"></label>
			<label style="margin-left:10px"><input type="checkbox" name="df_hero_color_reset" value="1"> Otomatik renge dön</label></p>
			<p class="description">Örnek: Güller pembe (#E6197E), Orkideler mor (#8E44AD), Söz &amp; Nişan altın (#C29A5B).</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="df_per_page">Sayfada kaç ürün</label></th>
		<td>
			<input type="number" min="0" max="100" name="df_per_page" id="df_per_page" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'df_per_page', true ) ); ?>" style="width:100px" placeholder="20">
			<?php $dp = get_term_meta( $term->term_id, 'df_desc_pos', true ); ?>
			<select name="df_desc_pos" style="margin-left:8px">
				<option value="bottom" <?php selected( $dp ? $dp : 'bottom', 'bottom' ); ?>>Kategori açıklaması ürünlerin altında</option>
				<option value="top" <?php selected( $dp, 'top' ); ?>>Kategori açıklaması ürünlerin üstünde</option>
			</select>
			<p class="description">Boş bırakılırsa genel ayardaki sayı (20) kullanılır. Açıklamada "## Başlık", "**kalın**" ve "- madde" yazabilirsiniz; düzgün başlık ve liste olarak görünür.</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="df_promo_text">Kampanya bandı</label></th>
		<td>
			<input type="text" name="df_promo_text" id="df_promo_text" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'df_promo_text', true ) ); ?>" placeholder="Örn. Anneler Günü'ne özel tüm buketlerde aynı gün teslimat">
			<p style="display:flex;gap:8px;margin-top:8px">
				<input type="text" name="df_promo_btn" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'df_promo_btn', true ) ); ?>" placeholder="Buton metni (opsiyonel)" style="max-width:240px">
				<input type="url" name="df_promo_url" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'df_promo_url', true ) ); ?>" placeholder="Buton bağlantısı (https://…)">
			</p>
			<p class="description">Kategori sayfasında başlığın altında renkli bant olarak gösterilir. Boşsa gösterilmez.</p>
			<script>
			( function ( $ ) {
				$( document ).on( 'click', '.df-media__select', function ( e ) {
					e.preventDefault();
					var box = $( this ).closest( '.df-media' );
					var frame = wp.media( { title: 'Görsel seç', multiple: false, library: { type: 'image' } } );
					frame.on( 'select', function () {
						var a = frame.state().get( 'selection' ).first().toJSON();
						box.find( '.df-media__input' ).val( a.id );
						var url = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
						box.find( '.df-media__preview' ).html( '<img src="' + url + '" style="max-width:300px;height:auto">' );
						box.find( '.df-media__remove' ).prop( 'hidden', false );
					} );
					frame.open();
				} );
				$( document ).on( 'click', '.df-media__remove', function ( e ) {
					e.preventDefault();
					var box = $( this ).closest( '.df-media' );
					box.find( '.df-media__input' ).val( '' );
					box.find( '.df-media__preview' ).empty();
					$( this ).prop( 'hidden', true );
				} );
			} )( jQuery );
			</script>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'df_cat_edit_fields', 20 );

/**
 * Kategori meta kaydı.
 *
 * @param int $term_id Terim.
 */
function df_cat_save_fields( $term_id ) {
	if ( ! isset( $_POST['df_term_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['df_term_meta_nonce'] ) ), 'df_term_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['df_icon'] ) ) {
		$icon = sanitize_key( wp_unslash( $_POST['df_icon'] ) );
		update_term_meta( $term_id, 'df_icon', array_key_exists( $icon, df_icon_library() ) ? $icon : '' );
	}
	if ( isset( $_POST['df_subtitle'] ) ) {
		update_term_meta( $term_id, 'df_subtitle', sanitize_text_field( wp_unslash( $_POST['df_subtitle'] ) ) );
	}
	if ( isset( $_POST['df_banner'] ) ) {
		update_term_meta( $term_id, 'df_banner', absint( $_POST['df_banner'] ) );
	}
	if ( isset( $_POST['df_banner_mobile'] ) ) {
		update_term_meta( $term_id, 'df_banner_mobile', absint( $_POST['df_banner_mobile'] ) );
	}
	foreach ( array( 'df_banner_h' => array( 'short', 'medium', 'tall' ), 'df_banner_text' => array( 'below', 'over' ), 'df_desc_pos' => array( 'bottom', 'top' ) ) as $k => $allowed ) {
		if ( isset( $_POST[ $k ] ) ) {
			$v = sanitize_key( wp_unslash( $_POST[ $k ] ) );
			update_term_meta( $term_id, $k, in_array( $v, $allowed, true ) ? $v : $allowed[0] );
		}
	}
	if ( isset( $_POST['df_per_page'] ) ) {
		update_term_meta( $term_id, 'df_per_page', min( 100, absint( $_POST['df_per_page'] ) ) );
	}
	if ( ! empty( $_POST['df_hero_color_reset'] ) ) {
		delete_term_meta( $term_id, 'df_hero_color' );
	} elseif ( isset( $_POST['df_hero_color'] ) ) {
		$hc = sanitize_hex_color( wp_unslash( $_POST['df_hero_color'] ) );
		$t  = get_term( $term_id );
		if ( $hc && ( ! $t instanceof WP_Term || strtolower( $hc ) !== strtolower( df_cat_hero_color( $t, false ) ) ) ) {
			update_term_meta( $term_id, 'df_hero_color', $hc );
		}
	}
	if ( isset( $_POST['df_banner_pos'] ) ) {
		$pos = sanitize_key( wp_unslash( $_POST['df_banner_pos'] ) );
		update_term_meta( $term_id, 'df_banner_pos', in_array( $pos, array( 'right', 'top', 'bottom', 'mid', 'left' ), true ) ? $pos : 'right' );
	}
	foreach ( array( 'df_promo_text', 'df_promo_btn' ) as $k ) {
		if ( isset( $_POST[ $k ] ) ) {
			update_term_meta( $term_id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) );
		}
	}
	if ( isset( $_POST['df_promo_url'] ) ) {
		update_term_meta( $term_id, 'df_promo_url', esc_url_raw( wp_unslash( $_POST['df_promo_url'] ) ) );
	}
}
add_action( 'created_product_cat', 'df_cat_save_fields' );
add_action( 'edited_product_cat', 'df_cat_save_fields' );

/**
 * Kategori listesine ikon sütunu.
 *
 * @param array $columns Sütunlar.
 * @return array
 */
function df_cat_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'thumb' === $key ) {
			$new['df_icon'] = 'İkon';
		}
	}
	return $new;
}
add_filter( 'manage_edit-product_cat_columns', 'df_cat_columns' );

/**
 * İkon sütunu içeriği.
 *
 * @param string $content Çıktı.
 * @param string $column  Sütun.
 * @param int    $term_id Terim.
 * @return string
 */
function df_cat_column_content( $content, $column, $term_id ) {
	if ( 'df_icon' === $column ) {
		$icon = get_term_meta( $term_id, 'df_icon', true );
		return $icon ? df_icon( $icon, array( 'size' => 24 ) ) : '—';
	}
	return $content;
}
add_filter( 'manage_product_cat_custom_column', 'df_cat_column_content', 10, 3 );
