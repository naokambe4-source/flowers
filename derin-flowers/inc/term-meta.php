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
