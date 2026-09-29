<?php
/**
 * Yönetim paneli alan çıktıları ve temizleme (sanitize) işlemleri.
 * Tema Ayarları ekranı ve tüm meta kutuları bu dosyayı kullanır.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alan sınıfları (genişlik).
 *
 * @param array $field Alan.
 * @return string
 */
function ce_field_width( $field ) {
	foreach ( array( 'half', 'third', 'quarter' ) as $w ) {
		if ( ! empty( $field[ $w ] ) ) {
			return ' ce-field--' . $w;
		}
	}
	return '';
}

/**
 * Alan çıktısı.
 *
 * @param array  $field Alan tanımı.
 * @param mixed  $value Değer.
 * @param string $name  Form adı.
 * @param string $uid   Benzersiz id.
 */
function ce_render_field( $field, $value, $name, $uid ) {
	$type  = $field['type'];
	$label = $field['label'] ?? '';
	$desc  = $field['desc'] ?? '';

	if ( 'sections' === $type ) {
		ce_render_sections_field( $field, $value, $name );
		return;
	}
	if ( 'repeater' === $type ) {
		ce_render_repeater_field( $field, $value, $name, $uid );
		return;
	}

	echo '<div class="ce-field ce-field--' . esc_attr( $type ) . esc_attr( ce_field_width( $field ) ) . '">';
	if ( 'toggle' !== $type && $label ) {
		echo '<label class="ce-field__label" for="' . esc_attr( $uid ) . '">' . esc_html( $label ) . ( ! empty( $field['required'] ) ? ' <span class="ce-req">*</span>' : '' ) . '</label>';
	}

	switch ( $type ) {
		case 'text':
		case 'email':
		case 'url':
			printf(
				'<input type="%5$s" class="ce-input" id="%1$s" name="%2$s" value="%3$s" %4$s>',
				esc_attr( $uid ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				'url' === $type ? 'placeholder="https://… veya /sayfa/"' : ( ! empty( $field['placeholder'] ) ? 'placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '' ),
				'email' === $type ? 'email' : 'text'
			);
			break;

		case 'number':
			printf(
				'<input type="number" class="ce-input ce-input--num" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s">',
				esc_attr( $uid ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				esc_attr( (string) ( $field['min'] ?? '' ) ),
				esc_attr( (string) ( $field['max'] ?? '' ) ),
				esc_attr( (string) ( $field['step'] ?? 1 ) )
			);
			break;

		case 'password':
			printf(
				'<input type="password" class="ce-input" id="%1$s" name="%2$s" value="" autocomplete="new-password" placeholder="%3$s">',
				esc_attr( $uid ),
				esc_attr( $name ),
				$value ? esc_attr__( '•••••••• (kayıtlı — değiştirmek için yazın)', 'can-eloksal' ) : ''
			);
			break;

		case 'textarea':
		case 'lines':
		case 'code':
			printf(
				'<textarea class="ce-input%5$s" id="%1$s" name="%2$s" rows="%3$d">%4$s</textarea>',
				esc_attr( $uid ),
				esc_attr( $name ),
				absint( $field['rows'] ?? 3 ),
				esc_textarea( is_array( $value ) ? implode( "\n", $value ) : (string) $value ),
				'code' === $type ? ' ce-input--code' : ''
			);
			break;

		case 'editor':
			wp_editor(
				(string) $value,
				$uid,
				array(
					'textarea_name' => $name,
					'textarea_rows' => $field['rows'] ?? 8,
					'media_buttons' => false,
					'teeny'         => false,
					'quicktags'     => true,
				)
			);
			break;

		case 'toggle':
			printf(
				'<label class="ce-toggle"><input type="hidden" name="%2$s" value="0"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s><span class="ce-toggle__ui" aria-hidden="true"></span><span class="ce-toggle__label">%4$s</span></label>',
				esc_attr( $uid ),
				esc_attr( $name ),
				checked( (int) $value, 1, false ),
				esc_html( $label )
			);
			break;

		case 'select':
			echo '<select class="ce-input" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $field['options'] as $k => $v ) {
				echo '<option value="' . esc_attr( $k ) . '" ' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
			}
			echo '</select>';
			break;

		case 'icon':
			echo '<div class="ce-icon-select"><span class="ce-icon-select__preview" aria-hidden="true">' . ce_icon( $value ? $value : 'check', 20 ) . '</span>'; // phpcs:ignore
			echo '<select class="ce-input" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( ce_icon_choices() as $k => $v ) {
				echo '<option value="' . esc_attr( $k ) . '" ' . selected( (string) $value, $k, false ) . '>' . esc_html( $v ) . '</option>';
			}
			echo '</select></div>';
			break;

		case 'page':
			wp_dropdown_pages(
				array(
					'name'              => $name, // phpcs:ignore
					'id'                => $uid, // phpcs:ignore
					'selected'          => absint( $value ),
					'show_option_none'  => '— Seçin —',
					'option_none_value' => 0,
					'class'             => 'ce-input',
				)
			);
			break;

		case 'image':
			$id  = absint( $value );
			$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
			echo '<div class="ce-media' . ( $src ? ' has-image' : '' ) . '" data-type="image">';
			echo '<input type="hidden" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $id ? (string) $id : '' ) . '" class="ce-media__input">';
			echo '<button type="button" class="ce-media__preview ce-media__select" aria-label="Görsel seç">';
			if ( $src ) {
				echo '<img src="' . esc_url( $src ) . '" alt="">';
			} else {
				echo '<span class="dashicons dashicons-format-image" aria-hidden="true"></span><span>Görsel seç</span>';
			}
			echo '</button>';
			echo '<div class="ce-media__actions"><button type="button" class="button ce-media__select">Değiştir</button> <button type="button" class="button-link-delete ce-media__remove">Kaldır</button></div>';
			echo '</div>';
			break;

		case 'gallery':
			$ids = array_filter( array_map( 'absint', is_array( $value ) ? $value : explode( ',', (string) $value ) ) );
			echo '<div class="ce-gallery-field" data-type="gallery">';
			echo '<input type="hidden" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '" class="ce-gallery-field__input">';
			echo '<ul class="ce-gallery-field__list">';
			foreach ( $ids as $id ) {
				$src = wp_get_attachment_image_url( $id, 'thumbnail' );
				if ( $src ) {
					echo '<li data-id="' . esc_attr( (string) $id ) . '"><img src="' . esc_url( $src ) . '" alt=""><button type="button" class="ce-gallery-field__remove" aria-label="Kaldır">×</button></li>';
				}
			}
			echo '</ul>';
			echo '<button type="button" class="button ce-gallery-field__add">Görsel ekle</button> <span class="description">Sürükleyerek sıralayabilirsiniz.</span>';
			echo '</div>';
			break;

		case 'heading':
			echo wp_kses_post( $field['html'] ?? '' );
			break;
	}

	if ( $desc ) {
		echo '<p class="ce-field__desc">' . wp_kses_post( $desc ) . '</p>';
	}
	echo '</div>';
}

/**
 * Tekrarlayıcı (repeater) alan.
 *
 * @param array  $field Alan.
 * @param mixed  $value Değer.
 * @param string $name  Ad.
 * @param string $uid   ID.
 */
function ce_render_repeater_field( $field, $value, $name, $uid ) {
	$rows = is_array( $value ) ? array_values( $value ) : array();
	echo '<div class="ce-field ce-field--repeater" data-name="' . esc_attr( $name ) . '">';
	if ( ! empty( $field['label'] ) ) {
		echo '<p class="ce-field__label">' . esc_html( $field['label'] ) . '</p>';
	}
	if ( ! empty( $field['desc'] ) ) {
		echo '<p class="ce-field__desc">' . wp_kses_post( $field['desc'] ) . '</p>';
	}
	echo '<div class="ce-repeater__rows">';
	foreach ( $rows as $i => $row ) {
		ce_render_repeater_row( $field, $row, $name, $uid, (string) $i );
	}
	echo '</div>';
	echo '<script type="text/template" class="ce-repeater__template">';
	ob_start();
	ce_render_repeater_row( $field, array(), $name, $uid, '__INDEX__' );
	echo str_replace( '</script', '<\/script', ob_get_clean() ); // phpcs:ignore
	echo '</script>';
	echo '<button type="button" class="button ce-repeater__add"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> ' . esc_html( $field['add'] ?? 'Satır ekle' ) . '</button>';
	echo '</div>';
}

/**
 * Tekrarlayıcı satırı.
 *
 * @param array  $field Alan.
 * @param array  $row   Satır.
 * @param string $name  Ad.
 * @param string $uid   ID.
 * @param string $index Sıra.
 */
function ce_render_repeater_row( $field, $row, $name, $uid, $index ) {
	echo '<div class="ce-repeater__row">';
	echo '<div class="ce-repeater__bar"><span class="ce-repeater__handle dashicons dashicons-menu" title="Sürükleyin" aria-hidden="true"></span><span class="ce-repeater__title">' . esc_html( $row['title'] ?? ( $row['label'] ?? 'Yeni satır' ) ) . '</span><button type="button" class="ce-repeater__remove button-link-delete">Sil</button></div>';
	echo '<div class="ce-repeater__fields">';
	foreach ( $field['fields'] as $sub ) {
		$val = $row[ $sub['id'] ] ?? ( $sub['default'] ?? '' );
		ce_render_field( $sub, $val, $name . '[' . $index . '][' . $sub['id'] . ']', $uid . '-' . $index . '-' . $sub['id'] );
	}
	echo '</div></div>';
}

/**
 * Bölüm sıralama alanı.
 *
 * @param array  $field Alan.
 * @param mixed  $value Değer.
 * @param string $name  Ad.
 */
function ce_render_sections_field( $field, $value, $name ) {
	$items = ce_normalize_sections( $value, $field['choices'] );
	echo '<ul class="ce-sections">';
	foreach ( $items as $i => $item ) {
		printf(
			'<li class="ce-sections__item"><span class="dashicons dashicons-menu ce-sections__handle" aria-hidden="true"></span><input type="hidden" name="%1$s[%2$d][key]" value="%3$s"><label><input type="hidden" name="%1$s[%2$d][on]" value="0"><input type="checkbox" name="%1$s[%2$d][on]" value="1" %4$s> %5$s</label></li>',
			esc_attr( $name ),
			(int) $i,
			esc_attr( $item['key'] ),
			checked( (int) $item['on'], 1, false ),
			esc_html( $field['choices'][ $item['key'] ] )
		);
	}
	echo '</ul>';
}

/**
 * Bölüm listesini doğrular, eksik bölümleri sona ekler.
 *
 * @param mixed $value   Değer.
 * @param array $choices Seçenekler.
 * @return array
 */
function ce_normalize_sections( $value, $choices ) {
	$out  = array();
	$seen = array();
	if ( is_array( $value ) ) {
		foreach ( $value as $item ) {
			$key = isset( $item['key'] ) ? sanitize_key( $item['key'] ) : '';
			if ( $key && isset( $choices[ $key ] ) && ! isset( $seen[ $key ] ) ) {
				$out[]        = array( 'key' => $key, 'on' => empty( $item['on'] ) ? 0 : 1 );
				$seen[ $key ] = true;
			}
		}
	}
	foreach ( array_keys( $choices ) as $key ) {
		if ( ! isset( $seen[ $key ] ) ) {
			$out[] = array( 'key' => $key, 'on' => 1 );
		}
	}
	return $out;
}

/**
 * Alan değerini temizler.
 *
 * @param array $field Alan.
 * @param mixed $raw   Ham değer.
 * @param mixed $old   Önceki değer (şifre alanları için).
 * @return mixed
 */
function ce_sanitize_field( $field, $raw, $old = '' ) {
	$type = $field['type'];
	switch ( $type ) {
		case 'text':
			return sanitize_text_field( (string) $raw );
		case 'email':
			return sanitize_email( (string) $raw );
		case 'url':
			$raw = trim( (string) $raw );
			return '' === $raw ? '' : esc_url_raw( $raw, array( 'http', 'https', 'mailto', 'tel' ) );
		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( (string) $raw );
		case 'editor':
			return wp_kses_post( (string) $raw );
		case 'code':
			return ce_sanitize_embed( (string) $raw );
		case 'number':
			$num = is_numeric( $raw ) ? 0 + $raw : ( $field['default'] ?? 0 );
			if ( isset( $field['min'] ) ) {
				$num = max( $field['min'], $num );
			}
			if ( isset( $field['max'] ) ) {
				$num = min( $field['max'], $num );
			}
			return $num;
		case 'toggle':
			return empty( $raw ) ? 0 : 1;
		case 'select':
			return array_key_exists( (string) $raw, $field['options'] ) ? (string) $raw : ( $field['default'] ?? '' );
		case 'icon':
			return array_key_exists( (string) $raw, ce_icon_choices() ) ? (string) $raw : 'check';
		case 'page':
		case 'image':
			return absint( $raw );
		case 'gallery':
			$ids = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
			return array_values( array_filter( array_map( 'absint', $ids ) ) );
		case 'password':
			$raw = (string) $raw;
			if ( '' === $raw ) {
				return $old;
			}
			return ce_encrypt( $raw );
		case 'repeater':
			$rows = array();
			if ( is_array( $raw ) ) {
				foreach ( $raw as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$clean = array();
					$has   = false;
					foreach ( $field['fields'] as $sub ) {
						$clean[ $sub['id'] ] = ce_sanitize_field( $sub, $row[ $sub['id'] ] ?? '' );
						if ( 'icon' !== $sub['type'] && '' !== $clean[ $sub['id'] ] && 0 !== $clean[ $sub['id'] ] ) {
							$has = true;
						}
					}
					if ( $has ) {
						$rows[] = $clean;
					}
				}
			}
			return $rows;
		case 'sections':
			return ce_normalize_sections( $raw, $field['choices'] );
	}
	return '';
}

/**
 * Harita gömme kodunu temizler (yalnızca Google Maps iframe).
 *
 * @param string $code Kod.
 * @return string
 */
function ce_sanitize_embed( $code ) {
	$code = trim( $code );
	if ( '' === $code ) {
		return '';
	}
	if ( ! preg_match( '/src=["\']([^"\']+)["\']/i', $code, $m ) ) {
		return '';
	}
	$src = html_entity_decode( $m[1] );
	if ( ! preg_match( '#^https://(www\.)?google\.[a-z.]+/maps/embed#i', $src ) ) {
		return '';
	}
	return '<iframe src="' . esc_url( $src ) . '" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>';
}

/**
 * Hassas değerleri şifreler (AES-256-CBC, anahtar WordPress tuzlarından türetilir).
 *
 * @param string $plain Düz metin.
 * @return string
 */
function ce_encrypt( $plain ) {
	if ( ! function_exists( 'openssl_encrypt' ) ) {
		return 'b64:' . base64_encode( $plain ); // phpcs:ignore
	}
	$key = hash( 'sha256', wp_salt( 'auth' ) . 'ce-smtp', true );
	$iv  = random_bytes( 16 );
	$enc = openssl_encrypt( $plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
	$mac = hash_hmac( 'sha256', $iv . $enc, $key, true );
	return 'enc:' . base64_encode( $iv . $mac . $enc ); // phpcs:ignore
}

/**
 * Şifreli değeri çözer.
 *
 * @param string $stored Kayıtlı değer.
 * @return string
 */
function ce_decrypt( $stored ) {
	$stored = (string) $stored;
	if ( 0 === strpos( $stored, 'b64:' ) ) {
		return (string) base64_decode( substr( $stored, 4 ) ); // phpcs:ignore
	}
	if ( 0 !== strpos( $stored, 'enc:' ) || ! function_exists( 'openssl_decrypt' ) ) {
		return '';
	}
	$raw = base64_decode( substr( $stored, 4 ), true ); // phpcs:ignore
	if ( false === $raw || strlen( $raw ) < 49 ) {
		return '';
	}
	$key = hash( 'sha256', wp_salt( 'auth' ) . 'ce-smtp', true );
	$iv  = substr( $raw, 0, 16 );
	$mac = substr( $raw, 16, 32 );
	$enc = substr( $raw, 48 );
	if ( ! hash_equals( hash_hmac( 'sha256', $iv . $enc, $key, true ), $mac ) ) {
		return '';
	}
	$plain = openssl_decrypt( $enc, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
	return false === $plain ? '' : $plain;
}
