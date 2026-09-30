<?php
/**
 * Panel alanlarının çıktısı ve temizleme (sanitize) işlemleri.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alan çıktısı.
 *
 * @param array  $field Alan.
 * @param mixed  $value Değer.
 * @param string $name  Form adı.
 * @param string $uid   Benzersiz id.
 */
function df_render_field( $field, $value, $name, $uid ) {
	$type  = $field['type'];
	$label = isset( $field['label'] ) ? $field['label'] : '';
	$desc  = isset( $field['desc'] ) ? $field['desc'] : '';
	$width = '';
	foreach ( array( 'half', 'third', 'quarter' ) as $w ) {
		if ( ! empty( $field[ $w ] ) ) {
			$width = ' df-field--' . $w;
		}
	}
	if ( 'sections' === $type ) {
		df_render_sections_field( $value, $name );
		return;
	}
	if ( 'repeater' === $type ) {
		df_render_repeater_field( $field, $value, $name, $uid );
		return;
	}
	if ( 'design' === $type ) {
		df_render_design_field( $field, $value, $name, $uid );
		return;
	}
	echo '<div class="df-field df-field--' . esc_attr( $type ) . esc_attr( $width ) . '">';
	if ( 'toggle' !== $type && $label ) {
		echo '<label class="df-field__label" for="' . esc_attr( $uid ) . '">' . esc_html( $label ) . '</label>';
	}

	switch ( $type ) {
		case 'text':
		case 'url':
			printf(
				'<input type="text" class="df-input" id="%1$s" name="%2$s" value="%3$s" %4$s>',
				esc_attr( $uid ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				'url' === $type ? 'placeholder="https://… veya /sayfa/"' : ''
			);
			break;

		case 'number':
			printf(
				'<input type="number" class="df-input df-input--num" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s">',
				esc_attr( $uid ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				esc_attr( isset( $field['min'] ) ? $field['min'] : '' ),
				esc_attr( isset( $field['max'] ) ? $field['max'] : '' ),
				esc_attr( isset( $field['step'] ) ? $field['step'] : '1' )
			);
			break;

		case 'textarea':
		case 'lines':
			printf(
				'<textarea class="df-input%5$s" id="%1$s" name="%2$s" rows="%3$d">%4$s</textarea>',
				esc_attr( $uid ),
				esc_attr( $name ),
				isset( $field['rows'] ) ? (int) $field['rows'] : 3,
				esc_textarea( (string) $value ),
				( 'lines' === $type ? ' df-input--code' : '' )
			);
			break;

		case 'color':
			printf(
				'<input type="text" class="df-color" id="%1$s" name="%2$s" value="%3$s" data-default-color="%4$s">',
				esc_attr( $uid ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				esc_attr( isset( $field['default'] ) ? $field['default'] : '' )
			);
			break;

		case 'toggle':
			printf(
				'<input type="hidden" name="%2$s" value="0"><label class="df-switch" for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s><span class="df-switch__track"></span><span class="df-switch__label">%4$s</span></label>',
				esc_attr( $uid ),
				esc_attr( $name ),
				checked( (int) $value, 1, false ),
				esc_html( $label )
			);
			break;

		case 'select':
			echo '<select class="df-input" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $field['options'] as $k => $l ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( (string) $value, (string) $k, false ), esc_html( $l ) );
			}
			echo '</select>';
			break;

		case 'icon':
			echo '<div class="df-iconpick">';
			echo '<span class="df-iconpick__preview">' . ( $value ? df_icon( $value, array( 'size' => 24 ) ) : '' ) . '</span>'; // phpcs:ignore
			echo '<select class="df-input df-iconpick__select" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( df_icon_choices( true ) as $k => $l ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( (string) $value, (string) $k, false ), esc_html( $l ) );
			}
			echo '</select></div>';
			break;

		case 'image':
			$id  = absint( $value );
			$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
			echo '<div class="df-media' . ( $src ? ' has-image' : '' ) . '">';
			echo '<input type="hidden" class="df-media__input" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $id ? $id : '' ) . '">';
			echo '<div class="df-media__preview">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="">' : '<span>Görsel seçilmedi</span>' ) . '</div>';
			echo '<div class="df-media__actions"><button type="button" class="button df-media__select">' . ( $src ? 'Değiştir' : 'Görsel seç' ) . '</button>';
			echo '<button type="button" class="button-link df-media__remove"' . ( $src ? '' : ' hidden' ) . '>Kaldır</button></div>';
			echo '</div>';
			break;

		case 'product_cat':
		case 'post_cat':
			$tax   = 'product_cat' === $type ? 'product_cat' : 'category';
			$terms = taxonomy_exists( $tax ) ? get_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => false,
				)
			) : array();
			echo '<select class="df-input" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '"><option value="">— Seçin —</option>';
			if ( ! is_wp_error( $terms ) ) {
				foreach ( df_sort_terms_hierarchically( $terms ) as $term ) {
					printf(
						'<option value="%d" %s>%s%s (%d)</option>',
						(int) $term->term_id,
						selected( (int) $value, (int) $term->term_id, false ),
						esc_html( str_repeat( '— ', (int) $term->df_depth ) ),
						esc_html( $term->name ),
						(int) $term->count
					);
				}
			}
			echo '</select>';
			break;

		case 'page':
			wp_dropdown_pages(
				array(
					'name'              => esc_attr( $name ),
					'id'                => esc_attr( $uid ),
					'selected'          => absint( $value ),
					'show_option_none'  => '— Seçin —',
					'option_none_value' => '',
					'class'             => 'df-input',
				)
			);
			break;

		case 'products':
			$ids = array_filter( array_map( 'absint', (array) $value ) );
			if ( df_wc() ) {
				echo '<select class="wc-product-search df-products" multiple="multiple" style="width:100%" id="' . esc_attr( $uid ) . '" name="' . esc_attr( $name ) . '[]" data-placeholder="Ürün arayın…" data-action="woocommerce_json_search_products">';
				foreach ( $ids as $pid ) {
					$p = wc_get_product( $pid );
					if ( $p ) {
						printf( '<option value="%d" selected="selected">%s</option>', (int) $pid, esc_html( wp_strip_all_tags( $p->get_formatted_name() ) ) );
					}
				}
				echo '</select>';
			} else {
				echo '<p class="description">Bu alan için WooCommerce etkin olmalıdır.</p>';
			}
			break;

		case 'checkboxes':
			$vals = array_map( 'strval', (array) $value );
			echo '<div class="df-checks">';
			foreach ( $field['options'] as $k => $l ) {
				printf(
					'<label><input type="checkbox" name="%1$s[]" value="%2$s" %3$s> %4$s</label>',
					esc_attr( $name ),
					esc_attr( $k ),
					checked( in_array( (string) $k, $vals, true ), true, false ),
					esc_html( $l )
				);
			}
			echo '</div>';
			break;
	}

	if ( $desc ) {
		echo '<p class="df-field__desc">' . wp_kses_post( $desc ) . '</p>';
	}
	echo '</div>';
}

/**
 * Terimleri hiyerarşik sıralar.
 *
 * @param WP_Term[] $terms  Terimler.
 * @param int       $parent Üst.
 * @param int       $depth  Derinlik.
 * @return WP_Term[]
 */
function df_sort_terms_hierarchically( $terms, $parent = 0, $depth = 0 ) {
	$out = array();
	foreach ( $terms as $term ) {
		if ( (int) $term->parent === (int) $parent ) {
			$term->df_depth = $depth;
			$out[]          = $term;
			$out            = array_merge( $out, df_sort_terms_hierarchically( $terms, $term->term_id, $depth + 1 ) );
		}
	}
	return $out;
}

/**
 * Bölüm sıralama/aç-kapa alanı.
 *
 * @param array  $value Değer.
 * @param string $name  Ad.
 */
function df_render_sections_field( $value, $name ) {
	$labels = df_home_section_labels();
	$list   = array();
	foreach ( df_all_sections_ordered() as $row ) {
		$list[ $row['id'] ] = $row['on'];
	}
	echo '<ol class="df-sections" data-name="' . esc_attr( $name ) . '">';
	$i = 0;
	foreach ( $list as $id => $on ) {
		printf(
			'<li class="df-sections__item%5$s" data-id="%2$s"><span class="df-sections__handle dashicons dashicons-menu" aria-hidden="true"></span><span class="df-sections__num">%6$02d</span><span class="df-sections__label">%3$s</span>
			<input type="hidden" class="df-sections__id" name="%1$s[%4$d][id]" value="%2$s">
			<input type="hidden" class="df-sections__off" name="%1$s[%4$d][on]" value="0">
			<label class="df-switch"><input type="checkbox" class="df-sections__on" name="%1$s[%4$d][on]" value="1" %7$s><span class="df-switch__track"></span><span class="df-switch__label">%8$s</span></label>
			<a href="#" class="df-sections__edit" data-target="%2$s">Düzenle</a></li>',
			esc_attr( $name ),
			esc_attr( $id ),
			esc_html( $labels[ $id ] ),
			(int) $i,
			$on ? '' : ' is-off',
			(int) $i + 1,
			checked( $on, true, false ),
			$on ? 'Açık' : 'Kapalı'
		);
		++$i;
	}
	echo '</ol>';
}

/**
 * Tekrarlayıcı alan.
 *
 * @param array  $field Alan.
 * @param array  $value Değer.
 * @param string $name  Ad.
 * @param string $uid   Id.
 */
function df_render_repeater_field( $field, $value, $name, $uid ) {
	$items = is_array( $value ) ? array_values( $value ) : array();
	echo '<div class="df-field df-field--repeater">';
	if ( ! empty( $field['label'] ) ) {
		echo '<div class="df-field__label">' . esc_html( $field['label'] ) . '</div>';
	}
	if ( ! empty( $field['desc'] ) ) {
		echo '<p class="df-field__desc">' . wp_kses_post( $field['desc'] ) . '</p>';
	}
	echo '<div class="df-repeater" data-name="' . esc_attr( $name ) . '" data-title-field="' . esc_attr( isset( $field['title_field'] ) ? $field['title_field'] : '' ) . '">';
	echo '<div class="df-repeater__items">';
	foreach ( $items as $i => $item ) {
		df_render_repeater_item( $field, $item, $name . '[' . $i . ']', $uid . '_' . $i, $i );
	}
	echo '</div>';
	echo '<script type="text/html" class="df-repeater__tpl">';
	ob_start();
	df_render_repeater_item( $field, array(), $name . '[__i__]', $uid . '___i__', '__i__' );
	echo str_replace( '</script>', '<\/script>', ob_get_clean() ); // phpcs:ignore
	echo '</script>';
	echo '<button type="button" class="button df-repeater__add"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html( isset( $field['add_label'] ) ? $field['add_label'] : 'Ekle' ) . '</button>';
	echo '</div></div>';
}

/**
 * Tekrarlayıcı öğe.
 *
 * @param array      $field Alan.
 * @param array      $item  Değerler.
 * @param string     $name  Ad.
 * @param string     $uid   Id.
 * @param int|string $index Sıra.
 */
function df_render_repeater_item( $field, $item, $name, $uid, $index ) {
	$title_field = isset( $field['title_field'] ) ? $field['title_field'] : '';
	$title       = ( $title_field && ! empty( $item[ $title_field ] ) ) ? wp_strip_all_tags( $item[ $title_field ] ) : '';
	$title       = $title ? mb_substr( str_replace( array( "\r", "\n" ), ' ', $title ), 0, 70 ) : 'Öğe';
	$thumb       = '';
	foreach ( $field['fields'] as $sub ) {
		if ( 'image' === $sub['type'] && ! empty( $item[ $sub['id'] ] ) ) {
			$thumb = wp_get_attachment_image_url( absint( $item[ $sub['id'] ] ), 'thumbnail' );
			break;
		}
	}
	echo '<div class="df-repeater__item is-collapsed">';
	echo '<div class="df-repeater__bar"><span class="df-repeater__handle dashicons dashicons-menu"></span>';
	echo '<span class="df-repeater__thumb">' . ( $thumb ? '<img src="' . esc_url( $thumb ) . '" alt="">' : '' ) . '</span>';
	echo '<span class="df-repeater__title">' . esc_html( $title ) . '</span>';
	echo '<span class="df-repeater__tools"><button type="button" class="button-link df-repeater__dup" title="Çoğalt"><span class="dashicons dashicons-admin-page"></span></button>';
	echo '<button type="button" class="button-link df-repeater__remove" title="Sil"><span class="dashicons dashicons-trash"></span></button>';
	echo '<button type="button" class="button-link df-repeater__toggle" title="Aç/kapat"><span class="dashicons dashicons-arrow-down-alt2"></span></button></span></div>';
	echo '<div class="df-repeater__body"><div class="df-fields">';
	foreach ( $field['fields'] as $sub ) {
		$val = isset( $item[ $sub['id'] ] ) ? $item[ $sub['id'] ] : ( isset( $sub['default'] ) ? $sub['default'] : '' );
		df_render_field( $sub, $val, $name . '[' . $sub['id'] . ']', $uid . '_' . $sub['id'] );
	}
	echo '</div></div></div>';
}

/**
 * Tek alanın değerini temizler.
 *
 * @param array $field Alan.
 * @param mixed $raw   Ham değer (null = formda yok).
 * @return mixed
 */
function df_sanitize_field( $field, $raw ) {
	$type    = $field['type'];
	$default = isset( $field['default'] ) ? $field['default'] : '';

	switch ( $type ) {
		case 'text':
			return null === $raw ? '' : sanitize_text_field( $raw );
		case 'textarea':
		case 'lines':
			return null === $raw ? '' : sanitize_textarea_field( $raw );
		case 'url':
			$raw = null === $raw ? '' : trim( (string) $raw );
			if ( '' === $raw ) {
				return '';
			}
			if ( 0 === strpos( $raw, '/' ) || 0 === strpos( $raw, '#' ) ) {
				return sanitize_text_field( $raw );
			}
			return esc_url_raw( $raw );
		case 'number':
			if ( null === $raw || '' === $raw ) {
				return $default;
			}
			$n = is_numeric( $raw ) ? $raw + 0 : $default;
			if ( isset( $field['min'] ) ) {
				$n = max( $field['min'], $n );
			}
			if ( isset( $field['max'] ) ) {
				$n = min( $field['max'], $n );
			}
			return $n;
		case 'color':
			$c = sanitize_hex_color( (string) $raw );
			return $c ? $c : $default;
		case 'toggle':
			return empty( $raw ) ? 0 : 1;
		case 'select':
			$raw = (string) (string) $raw;
			return array_key_exists( $raw, $field['options'] ) ? $raw : $default;
		case 'icon':
			$raw = sanitize_key( (string) $raw );
			return array_key_exists( $raw, df_icon_library() ) ? $raw : '';
		case 'image':
		case 'product_cat':
		case 'post_cat':
		case 'page':
			return absint( $raw ) ? absint( $raw ) : '';
		case 'products':
			return array_values( array_unique( array_filter( array_map( 'absint', (array) $raw ) ) ) );
		case 'checkboxes':
			$allowed = array_map( 'strval', array_keys( $field['options'] ) );
			return array_values( array_intersect( array_map( 'strval', (array) $raw ), $allowed ) );
		case 'sections':
			$labels = df_home_section_labels();
			$out    = array();
			foreach ( (array) $raw as $row ) {
				if ( empty( $row['id'] ) || ! isset( $labels[ $row['id'] ] ) ) {
					continue;
				}
				$out[] = array(
					'id' => sanitize_key( $row['id'] ),
					'on' => empty( $row['on'] ) ? 0 : 1,
				);
			}
			return $out ? $out : df_default_sections();
		case 'design':
			$raw   = is_array( $raw ) ? $raw : array();
			$opts  = df_design_choices();
			$clean = array();
			foreach ( array( 'bg', 'color' ) as $k ) {
				$c = isset( $raw[ $k ] ) ? sanitize_hex_color( (string) $raw[ $k ] ) : '';
				if ( $c ) {
					$clean[ $k ] = $c;
				}
			}
			foreach ( array( 'pt', 'pb', 'align', 'title' ) as $k ) {
				$v = isset( $raw[ $k ] ) ? (string) $raw[ $k ] : '';
				if ( '' !== $v && isset( $opts[ $k ][ $v ] ) ) {
					$clean[ $k ] = $v;
				}
			}
			foreach ( array( 'hide_m', 'hide_d' ) as $k ) {
				if ( ! empty( $raw[ $k ] ) ) {
					$clean[ $k ] = 1;
				}
			}
			return $clean;
		case 'repeater':
			$out = array();
			foreach ( (array) $raw as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$item = array();
				foreach ( $field['fields'] as $sub ) {
					$item[ $sub['id'] ] = df_sanitize_field( $sub, isset( $row[ $sub['id'] ] ) ? $row[ $sub['id'] ] : null );
				}
				$out[] = $item;
			}
			return $out;
	}
	return '';
}

/**
 * Tüm seçenekleri temizler.
 *
 * @param array $input Form verisi.
 * @return array
 */
function df_sanitize_options( $input ) {
	if ( ! is_array( $input ) ) {
		return get_option( DF_OPTION, array() );
	}
	// İçe aktarma gibi durumlarda zaten temizlenmiş veri gelir.
	if ( ! empty( $input['__df_clean'] ) ) {
		unset( $input['__df_clean'] );
		return $input;
	}
	$out = array();
	foreach ( df_options_schema() as $tab ) {
		foreach ( $tab['groups'] as $group ) {
			foreach ( $group['fields'] as $field ) {
				if ( empty( $field['id'] ) ) {
					continue;
				}
				$out[ $field['id'] ] = df_sanitize_field( $field, isset( $input[ $field['id'] ] ) ? $input[ $field['id'] ] : null );
			}
		}
	}
	return $out;
}

/**
 * Bölüm tasarımı seçenekleri.
 *
 * @return array
 */
function df_design_choices() {
	$space = array(
		''     => 'Varsayılan',
		'none' => 'Yok',
		's'    => 'Az',
		'm'    => 'Orta',
		'l'    => 'Geniş',
		'xl'   => 'Çok geniş',
	);
	$title = array( '' => 'Varsayılan' );
	foreach ( array( 24, 28, 32, 36, 40, 46, 52, 60, 72 ) as $px ) {
		$title[ (string) $px ] = $px . ' px';
	}
	return array(
		'pt'    => $space,
		'pb'    => $space,
		'align' => array(
			''       => 'Varsayılan',
			'left'   => 'Sola',
			'center' => 'Ortaya',
			'right'  => 'Sağa',
		),
		'title' => $title,
	);
}

/**
 * Bölüm tasarımı alanı.
 *
 * @param array  $field Alan.
 * @param mixed  $value Değer.
 * @param string $name  Ad.
 * @param string $uid   Id.
 */
function df_render_design_field( $field, $value, $name, $uid ) {
	$v      = is_array( $value ) ? $value : array();
	$opts   = df_design_choices();
	$labels = array(
		'pt'    => 'Üst boşluk',
		'pb'    => 'Alt boşluk',
		'align' => 'Başlık hizası',
		'title' => 'Başlık boyutu',
	);
	echo '<div class="df-field df-field--design"><div class="df-field__label">' . esc_html( $field['label'] ) . '</div><div class="df-design">';
	foreach ( array( 'bg' => 'Zemin rengi', 'color' => 'Yazı rengi' ) as $k => $l ) {
		printf(
			'<div class="df-design__cell"><label for="%1$s">%4$s</label><input type="text" class="df-color" id="%1$s" name="%2$s" value="%3$s" data-default-color=""></div>',
			esc_attr( $uid . '_' . $k ),
			esc_attr( $name . '[' . $k . ']' ),
			esc_attr( isset( $v[ $k ] ) ? $v[ $k ] : '' ),
			esc_html( $l )
		);
	}
	foreach ( $labels as $k => $l ) {
		echo '<div class="df-design__cell"><label for="' . esc_attr( $uid . '_' . $k ) . '">' . esc_html( $l ) . '</label><select class="df-input" id="' . esc_attr( $uid . '_' . $k ) . '" name="' . esc_attr( $name . '[' . $k . ']' ) . '">';
		foreach ( $opts[ $k ] as $ok => $ol ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $ok ), selected( isset( $v[ $k ] ) ? (string) $v[ $k ] : '', (string) $ok, false ), esc_html( $ol ) );
		}
		echo '</select></div>';
	}
	foreach ( array( 'hide_m' => 'Mobilde gizle', 'hide_d' => 'Masaüstünde gizle' ) as $k => $l ) {
		printf(
			'<div class="df-design__cell df-design__cell--check"><label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label></div>',
			esc_attr( $name . '[' . $k . ']' ),
			checked( ! empty( $v[ $k ] ), true, false ),
			esc_html( $l )
		);
	}
	echo '</div></div>';
}
