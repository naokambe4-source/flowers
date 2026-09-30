<?php
/**
 * Panel alan bileşenleri ve temizleme.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seçim listesi (özel "icons" anahtarı ikon listesidir).
 *
 * @param mixed $choices Seçenekler.
 * @return array
 */
function cr_field_choices( $choices ) {
	if ( 'icons' === $choices ) {
		return cr_icon_choices();
	}
	return is_array( $choices ) ? $choices : array();
}

/**
 * Tek alan girdisi.
 *
 * @param array  $f     Alan.
 * @param mixed  $value Değer.
 * @param string $name  Girdi adı.
 * @param string $id    Kimlik.
 */
function cr_field_input( $f, $value, $name, $id ) {
	$type = $f['type'];
	switch ( $type ) {
		case 'textarea':
		case 'lines':
			printf( '<textarea id="%1$s" name="%2$s" rows="%3$d" class="cr-input">%4$s</textarea>', esc_attr( $id ), esc_attr( $name ), 'lines' === $type ? 5 : 3, esc_textarea( (string) $value ) );
			break;
		case 'code':
			printf( '<textarea id="%1$s" name="%2$s" rows="8" class="cr-input cr-input--code" spellcheck="false">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
			break;
		case 'color':
			printf( '<input id="%1$s" type="text" name="%2$s" value="%3$s" class="cr-color" data-default-color="%4$s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( isset( $f['default'] ) ? $f['default'] : '' ) );
			break;
		case 'number':
			printf(
				'<input id="%1$s" type="number" name="%2$s" value="%3$s" class="cr-input cr-input--sm"%4$s%5$s>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				isset( $f['min'] ) ? ' min="' . esc_attr( $f['min'] ) . '"' : '',
				isset( $f['max'] ) ? ' max="' . esc_attr( $f['max'] ) . '"' : ''
			);
			break;
		case 'toggle':
			printf( '<label class="cr-switch"><input type="hidden" name="%2$s" value="0"><input id="%1$s" type="checkbox" name="%2$s" value="1"%3$s><span></span></label>', esc_attr( $id ), esc_attr( $name ), checked( ! empty( $value ), true, false ) );
			break;
		case 'select':
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="cr-input">';
			foreach ( cr_field_choices( isset( $f['choices'] ) ? $f['choices'] : array() ) as $k => $label ) {
				echo '<option value="' . esc_attr( $k ) . '"' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
			break;
		case 'image':
			$url = cr_img_url( $value, 'medium' );
			echo '<div class="cr-media" data-media>';
			echo '<div class="cr-media__preview" data-media-preview>' . ( $url ? '<img src="' . esc_url( $url ) . '" alt="">' : '<span>Görsel yok</span>' ) . '</div>';
			echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" data-media-input>';
			echo '<div class="cr-media__actions"><button type="button" class="button" data-media-pick>Seç / yükle</button> <button type="button" class="button-link cr-danger" data-media-clear>Kaldır</button></div>';
			echo '<input type="url" class="cr-input cr-media__url" placeholder="ya da görsel adresi yapıştırın" value="' . esc_attr( is_numeric( $value ) ? '' : (string) $value ) . '" data-media-url>';
			echo '</div>';
			break;
		case 'url':
			printf( '<input id="%1$s" type="text" name="%2$s" value="%3$s" class="cr-input" placeholder="/ornek-sayfa/ veya https://…">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
			break;
		default:
			printf( '<input id="%1$s" type="text" name="%2$s" value="%3$s" class="cr-input">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
	}
}

/**
 * Alan satırı.
 *
 * @param array $f     Alan.
 * @param mixed $value Değer.
 */
function cr_field_row( $f, $value ) {
	$id   = 'cr-f-' . $f['id'];
	$name = 'cr[' . $f['id'] . ']';
	$wide = in_array( $f['type'], array( 'repeater', 'sections', 'code' ), true );
	echo '<div class="cr-field cr-field--' . esc_attr( $f['type'] ) . ( $wide ? ' cr-field--wide' : '' ) . '" data-search="' . esc_attr( cr_lower( wp_strip_all_tags( $f['label'] . ' ' . ( isset( $f['desc'] ) ? $f['desc'] : '' ) ) ) ) . '">';
	echo '<div class="cr-field__label"><label for="' . esc_attr( $id ) . '">' . wp_kses_post( $f['label'] ) . '</label>';
	if ( ! empty( $f['desc'] ) ) {
		echo '<p class="cr-field__desc">' . wp_kses_post( $f['desc'] ) . '</p>';
	}
	echo '</div><div class="cr-field__control">';
	if ( 'repeater' === $f['type'] ) {
		cr_field_repeater( $f, is_array( $value ) ? $value : array() );
	} elseif ( 'sections' === $f['type'] ) {
		cr_field_sections();
	} else {
		cr_field_input( $f, $value, $name, $id );
	}
	echo '</div></div>';
}

/**
 * Tekrarlayıcı alan.
 *
 * @param array $f    Alan.
 * @param array $rows Satırlar.
 */
function cr_field_repeater( $f, $rows ) {
	$max = isset( $f['max'] ) ? (int) $f['max'] : 0;
	echo '<div class="cr-repeater" data-repeater data-name="cr[' . esc_attr( $f['id'] ) . ']" data-max="' . (int) $max . '">';
	echo '<div class="cr-repeater__rows" data-rows>';
	foreach ( array_values( $rows ) as $i => $row ) {
		cr_field_repeater_row( $f, $row, $i );
	}
	echo '</div>';
	echo '<script type="text/template" data-row-template>';
	ob_start();
	cr_field_repeater_row( $f, array(), '__i__' );
	echo str_replace( '</script', '<\/script', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</script>';
	echo '<button type="button" class="button cr-repeater__add" data-add>+ Yeni ekle</button>';
	echo '</div>';
}

/**
 * Tekrarlayıcı satırı.
 *
 * @param array      $f   Alan.
 * @param array      $row Değerler.
 * @param int|string $i   Sıra.
 */
function cr_field_repeater_row( $f, $row, $i ) {
	$first = reset( $f['fields'] );
	$fk    = key( $f['fields'] );
	$title = isset( $row[ $fk ] ) && '' !== $row[ $fk ] ? $row[ $fk ] : 'Yeni öğe';
	echo '<div class="cr-row" data-row>';
	echo '<div class="cr-row__head"><span class="cr-row__drag" title="Sürükle">⋮⋮</span><button type="button" class="cr-row__title" data-toggle>' . esc_html( wp_trim_words( wp_strip_all_tags( (string) $title ), 8, '…' ) ) . '</button><button type="button" class="cr-row__btn" data-dup title="Çoğalt">⧉</button><button type="button" class="cr-row__btn cr-danger" data-remove title="Sil">✕</button></div>';
	echo '<div class="cr-row__body">';
	foreach ( $f['fields'] as $sub => $sf ) {
		$sf['id'] = $f['id'] . '_' . $sub;
		$val      = isset( $row[ $sub ] ) ? $row[ $sub ] : '';
		echo '<div class="cr-row__field"><label>' . esc_html( $sf['label'] ) . '</label>';
		cr_field_input( $sf, $val, 'cr[' . $f['id'] . '][' . $i . '][' . $sub . ']', 'cr-f-' . $f['id'] . '-' . $i . '-' . $sub );
		echo '</div>';
	}
	echo '</div></div>';
}

/**
 * Bölüm sıralama alanı.
 */
function cr_field_sections() {
	$labels = cr_home_section_labels();
	echo '<ul class="cr-sections" data-sortable>';
	foreach ( cr_sections() as $i => $s ) {
		echo '<li class="cr-sections__item' . ( $s['on'] ? '' : ' is-off' ) . '">';
		echo '<span class="cr-row__drag">⋮⋮</span>';
		echo '<input type="hidden" name="cr[sections][' . (int) $i . '][id]" value="' . esc_attr( $s['id'] ) . '">';
		echo '<span class="cr-sections__label">' . esc_html( $labels[ $s['id'] ] ) . '</span>';
		echo '<label class="cr-switch"><input type="hidden" name="cr[sections][' . (int) $i . '][on]" value="0"><input type="checkbox" name="cr[sections][' . (int) $i . '][on]" value="1"' . checked( $s['on'], 1, false ) . ' data-section-toggle><span></span></label>';
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Tek değeri temizler.
 *
 * @param array $f Alan.
 * @param mixed $v Değer.
 * @return mixed
 */
function cr_sanitize_value( $f, $v ) {
	switch ( $f['type'] ) {
		case 'toggle':
			return empty( $v ) ? 0 : 1;
		case 'number':
			$n = (int) $v;
			if ( isset( $f['min'] ) ) {
				$n = max( (int) $f['min'], $n );
			}
			if ( isset( $f['max'] ) ) {
				$n = min( (int) $f['max'], $n );
			}
			return $n;
		case 'color':
			$c = sanitize_hex_color( $v );
			return $c ? $c : ( isset( $f['default'] ) ? $f['default'] : '' );
		case 'url':
			$v = trim( (string) $v );
			return ( '' !== $v && '/' === $v[0] ) ? sanitize_text_field( $v ) : esc_url_raw( $v );
		case 'image':
			return is_numeric( $v ) ? absint( $v ) : esc_url_raw( (string) $v );
		case 'select':
			$choices = cr_field_choices( isset( $f['choices'] ) ? $f['choices'] : array() );
			return array_key_exists( (string) $v, $choices ) ? (string) $v : ( isset( $f['default'] ) ? $f['default'] : '' );
		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( (string) $v );
		case 'code':
			return current_user_can( 'unfiltered_html' ) ? (string) $v : wp_kses_post( (string) $v );
		default:
			return sanitize_text_field( (string) $v );
	}
}

/**
 * Bir sekmenin gönderilen değerlerini temizler.
 *
 * @param string $tab   Sekme.
 * @param array  $input Gönderilen.
 * @return array
 */
function cr_sanitize_tab( $tab, $input ) {
	$schema = cr_options_schema();
	$out    = array();
	if ( ! isset( $schema[ $tab ] ) ) {
		return $out;
	}
	foreach ( $schema[ $tab ]['groups'] as $group ) {
		foreach ( $group['fields'] as $f ) {
			if ( empty( $f['id'] ) ) {
				continue;
			}
			$id = $f['id'];
			$v  = isset( $input[ $id ] ) ? $input[ $id ] : null;
			if ( 'repeater' === $f['type'] ) {
				$rows = array();
				if ( is_array( $v ) ) {
					foreach ( $v as $row ) {
						if ( ! is_array( $row ) ) {
							continue;
						}
						$clean = array();
						$empty = true;
						foreach ( $f['fields'] as $sub => $sf ) {
							$clean[ $sub ] = cr_sanitize_value( $sf, isset( $row[ $sub ] ) ? $row[ $sub ] : '' );
							if ( '' !== $clean[ $sub ] && 0 !== $clean[ $sub ] ) {
								$empty = false;
							}
						}
						if ( ! $empty ) {
							$rows[] = $clean;
						}
					}
				}
				if ( ! empty( $f['max'] ) ) {
					$rows = array_slice( $rows, 0, (int) $f['max'] );
				}
				$out[ $id ] = $rows;
				continue;
			}
			if ( 'sections' === $f['type'] ) {
				$labels = cr_home_section_labels();
				$secs   = array();
				if ( is_array( $v ) ) {
					foreach ( $v as $s ) {
						if ( isset( $s['id'], $labels[ $s['id'] ] ) ) {
							$secs[] = array( 'id' => $s['id'], 'on' => empty( $s['on'] ) ? 0 : 1 );
						}
					}
				}
				$out[ $id ] = $secs ? $secs : cr_default_sections();
				continue;
			}
			if ( null === $v && 'toggle' !== $f['type'] ) {
				continue;
			}
			$out[ $id ] = cr_sanitize_value( $f, $v );
		}
	}
	return $out;
}
