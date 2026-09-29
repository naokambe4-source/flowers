<?php
/**
 * Canlı Editör kayıt motoru.
 *
 * Kaynak biçimleri:
 *   opt:anahtar[@i[.alan]]              Tema Ayarları (ce_manage_settings)
 *   meta:ID:anahtar[@i[.alan]]          İçerik alanı (edit_post)
 *   post:ID:title|excerpt               Başlık / özet (edit_post)
 *   thumb:ID                            Öne çıkan görsel (edit_post)
 *   battr:ID:KEY:öznitelik[@i[.alan]]   Can Eloksal bloğu özniteliği (edit_post)
 *   cblock:ID:KEY                       Paragraf / başlık / liste bloğu HTML'i (edit_post)
 *   cimg:ID:KEY                         Görsel bloğu (edit_post)
 *
 * Yapı işlemleri: section:anahtar (ana sayfa bölümü), block:ID:KEY (sayfa bloğu).
 * Stiller: edit_theme_options yetkisi.
 *
 * @package CanEloksalBuilder
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_ceb_live_save', 'ceb_live_ajax_save' );
/**
 * Tüm bekleyen değişiklikleri kaydeder.
 */
function ceb_live_ajax_save() {
	check_ajax_referer( 'ceb_live', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
	}
	$payload = json_decode( (string) wp_unslash( $_POST['payload'] ?? '' ), true ); // phpcs:ignore -- aşağıda her alan ayrı doğrulanır.
	if ( ! is_array( $payload ) ) {
		wp_send_json_error( array( 'message' => 'Geçersiz istek.' ), 400 );
	}

	$errors  = array();
	$applied = 0;
	$posts   = array(); // Bloklarına dokunulan yazılar: ID => keyed list.

	// 1) Yapı işlemleri (sırası önemli).
	foreach ( (array) ( $payload['ops'] ?? array() ) as $op ) {
		$r = ceb_live_apply_op( (array) $op, $posts );
		if ( is_wp_error( $r ) ) {
			$errors[] = array( 'source' => (string) ( $op['unit'] ?? '' ), 'message' => $r->get_error_message() );
		} else {
			++$applied;
		}
	}

	// 2) Metin / görsel / bağlantı değerleri.
	foreach ( (array) ( $payload['values'] ?? array() ) as $source => $value ) {
		$r = ceb_live_apply_value( (string) $source, $value, $posts );
		if ( is_wp_error( $r ) ) {
			$errors[] = array( 'source' => (string) $source, 'message' => $r->get_error_message() );
		} else {
			++$applied;
		}
	}

	// Blok değişikliklerini yazılara kaydet (her yazı için tek revizyon).
	foreach ( $posts as $post_id => $list ) {
		$content = serialize_blocks( array_values( $list ) );
		$result  = wp_update_post( array( 'ID' => $post_id, 'post_content' => wp_slash( $content ) ), true );
		if ( is_wp_error( $result ) ) {
			$errors[] = array( 'source' => 'post:' . $post_id, 'message' => $result->get_error_message() );
		}
	}

	// 3) Stiller.
	if ( ! empty( $payload['styles'] ) || ! empty( $payload['resets'] ) ) {
		$r = ceb_live_save_styles( (string) ( $payload['context'] ?? '' ), (array) ( $payload['styles'] ?? array() ), (array) ( $payload['resets'] ?? array() ) );
		if ( is_wp_error( $r ) ) {
			$errors[] = array( 'source' => 'styles', 'message' => $r->get_error_message() );
		} else {
			$applied += $r;
		}
	}

	if ( function_exists( 'ce_audit' ) ) {
		ce_audit( 'update', 'live', 0, 'Canlı editör: ' . $applied . ' değişiklik' . ( $errors ? ', ' . count( $errors ) . ' hata' : '' ) );
	}
	wp_send_json_success( array( 'applied' => $applied, 'errors' => $errors ) );
}

/* -------------------------------------------------------------------------
 * Bloklar
 * ---------------------------------------------------------------------- */

/**
 * Yazının üst düzey bloklarını anahtarlı listeye çevirir (k0, k1…; çerçevedeki numaralandırmayla aynı).
 *
 * @param int   $post_id Yazı.
 * @param array $posts   Önbellek.
 * @return array|WP_Error
 */
function &ceb_live_blocks( $post_id, &$posts ) {
	$error = null;
	if ( ! isset( $posts[ $post_id ] ) ) {
		$post = get_post( $post_id );
		if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
			$error = new WP_Error( 'ceb_perm', 'Bu içeriği düzenleme yetkiniz yok.' );
			return $error;
		}
		$list = array();
		$i    = 0;
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( null === $block['blockName'] && '' === trim( (string) $block['innerHTML'] ) ) {
				continue;
			}
			$list[ 'k' . $i ] = $block;
			++$i;
		}
		$posts[ $post_id ] = $list;
	}
	return $posts[ $post_id ];
}

/**
 * Yapı işlemi.
 *
 * @param array $op    İşlem (type, unit, dir, newKey).
 * @param array $posts Yazı blok listeleri.
 * @return true|WP_Error
 */
function ceb_live_apply_op( $op, &$posts ) {
	$type = sanitize_key( $op['type'] ?? '' );
	$unit = (string) ( $op['unit'] ?? '' );

	if ( 0 === strpos( $unit, 'section:' ) ) {
		if ( ! current_user_can( 'ce_manage_settings' ) ) {
			return new WP_Error( 'ceb_perm', 'Ana sayfa bölümlerini düzenleme yetkiniz yok.' );
		}
		$key      = sanitize_key( substr( $unit, 8 ) );
		$options  = get_option( CE_OPTION, array() );
		$options  = is_array( $options ) ? $options : array();
		$sections = ce_normalize_sections( $options['home_sections'] ?? array(), ce_home_section_labels() );
		$index    = array_search( $key, array_column( $sections, 'key' ), true );
		if ( false === $index ) {
			return new WP_Error( 'ceb_missing', 'Bölüm bulunamadı.' );
		}
		if ( in_array( $type, array( 'hide', 'delete' ), true ) ) {
			$sections[ $index ]['on'] = 0;
		} elseif ( 'move' === $type ) {
			$step = 'up' === ( $op['dir'] ?? '' ) ? -1 : 1;
			$j    = $index + $step;
			while ( isset( $sections[ $j ] ) && empty( $sections[ $j ]['on'] ) ) {
				$j += $step;
			}
			if ( isset( $sections[ $j ] ) ) {
				$tmp               = $sections[ $j ];
				$sections[ $j ]    = $sections[ $index ];
				$sections[ $index ] = $tmp;
			}
		} else {
			return new WP_Error( 'ceb_op', 'Bu bölüm için desteklenmeyen işlem.' );
		}
		$options['home_sections'] = $sections;
		update_option( CE_OPTION, $options );
		$GLOBALS['ce_opt_reset'] = true;
		return true;
	}

	if ( preg_match( '/^block:(\d+):(k[\w]+)$/', $unit, $m ) ) {
		$post_id = (int) $m[1];
		$key     = $m[2];
		$list    = &ceb_live_blocks( $post_id, $posts );
		if ( is_wp_error( $list ) ) {
			return $list;
		}
		if ( ! isset( $list[ $key ] ) ) {
			return new WP_Error( 'ceb_missing', 'Blok bulunamadı (sayfa başka yerden değişmiş olabilir).' );
		}
		$keys = array_keys( $list );
		$pos  = array_search( $key, $keys, true );
		switch ( $type ) {
			case 'delete':
			case 'hide':
				unset( $list[ $key ] );
				break;
			case 'duplicate':
				$new = preg_replace( '/[^\w]/', '', (string) ( $op['newKey'] ?? '' ) );
				if ( ! $new || isset( $list[ $new ] ) ) {
					return new WP_Error( 'ceb_op', 'Geçersiz kopya anahtarı.' );
				}
				$list = array_slice( $list, 0, $pos + 1, true ) + array( $new => $list[ $key ] ) + array_slice( $list, $pos + 1, null, true );
				break;
			case 'move':
				$target = 'up' === ( $op['dir'] ?? '' ) ? $pos - 1 : $pos + 1;
				if ( $target < 0 || $target >= count( $keys ) ) {
					return true;
				}
				$tmp             = $keys[ $target ];
				$keys[ $target ] = $key;
				$keys[ $pos ]    = $tmp;
				$reordered       = array();
				foreach ( $keys as $k ) {
					$reordered[ $k ] = $list[ $k ];
				}
				$list = $reordered;
				break;
			default:
				return new WP_Error( 'ceb_op', 'Desteklenmeyen işlem.' );
		}
		return true;
	}
	return new WP_Error( 'ceb_op', 'Bilinmeyen hedef.' );
}

/* -------------------------------------------------------------------------
 * Değerler
 * ---------------------------------------------------------------------- */

/**
 * "anahtar@2.alan" yolunu çözer.
 *
 * @param string $path Yol.
 * @return array{0:string,1:?int,2:?string}
 */
function ceb_live_path( $path ) {
	if ( preg_match( '/^([\w-]+)(?:@(\d+)(?:\.([\w-]+))?)?$/', $path, $m ) ) {
		return array( $m[1], isset( $m[2] ) && '' !== $m[2] ? (int) $m[2] : null, $m[3] ?? null );
	}
	return array( '', null, null );
}

/**
 * Alan tanımına göre tek değeri temizler.
 *
 * @param string $type  Alan tipi.
 * @param mixed  $value Değer.
 * @return mixed|WP_Error
 */
function ceb_live_clean( $type, $value ) {
	switch ( $type ) {
		case 'text':
			return sanitize_text_field( (string) $value );
		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( (string) $value );
		case 'email':
			return sanitize_email( (string) $value );
		case 'url':
			$value = trim( (string) $value );
			return '' === $value ? '' : esc_url_raw( $value, array( 'http', 'https', 'mailto', 'tel' ) );
		case 'image':
			$id = absint( $value );
			return ( 0 === $id || wp_attachment_is_image( $id ) ) ? $id : new WP_Error( 'ceb_img', 'Geçersiz görsel.' );
		case 'number':
			return is_numeric( $value ) ? 0 + $value : 0;
	}
	return new WP_Error( 'ceb_type', 'Bu alan canlı editörden düzenlenemez.' );
}

/**
 * Yol ile verilen alt değeri günceller (satır listesi veya tekrarlayıcı).
 *
 * @param array  $field   Alan tanımı (type, fields…).
 * @param mixed  $current Mevcut değer.
 * @param ?int   $index   Satır.
 * @param ?string $sub    Alt alan.
 * @param mixed  $value   Yeni değer.
 * @return mixed|WP_Error
 */
function ceb_live_set_path( $field, $current, $index, $sub, $value ) {
	if ( null === $index ) {
		if ( 'repeater' === $field['type'] ) {
			return new WP_Error( 'ceb_type', 'Liste tamamen değiştirilemez.' );
		}
		return ceb_live_clean( $field['type'], $value );
	}
	if ( 'lines' === $field['type'] || 'textarea' === $field['type'] ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $current );
		$lines = array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
		if ( ! isset( $lines[ $index ] ) ) {
			return new WP_Error( 'ceb_missing', 'Satır bulunamadı.' );
		}
		$lines[ $index ] = sanitize_text_field( (string) $value );
		return implode( "\n", array_filter( $lines, 'strlen' ) );
	}
	if ( 'repeater' === $field['type'] ) {
		$rows = is_array( $current ) ? array_values( $current ) : array();
		if ( ! isset( $rows[ $index ] ) || null === $sub ) {
			return new WP_Error( 'ceb_missing', 'Satır bulunamadı.' );
		}
		$sub_def = null;
		foreach ( $field['fields'] as $f ) {
			if ( $f['id'] === $sub ) {
				$sub_def = $f;
			}
		}
		if ( ! $sub_def ) {
			return new WP_Error( 'ceb_missing', 'Alan bulunamadı.' );
		}
		$clean = ceb_live_clean( $sub_def['type'], $value );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}
		$rows[ $index ][ $sub ] = $clean;
		return $rows;
	}
	return new WP_Error( 'ceb_type', 'Bu alan canlı editörden düzenlenemez.' );
}

/**
 * Tema Ayarları alan tanımı.
 *
 * @param string $key Anahtar.
 * @return array|null
 */
function ceb_live_option_field( $key ) {
	foreach ( ce_options_schema() as $tab ) {
		foreach ( $tab['sections'] as $section ) {
			foreach ( $section['fields'] as $field ) {
				if ( ( $field['id'] ?? '' ) === $key ) {
					return $field;
				}
			}
		}
	}
	return null;
}

/**
 * Meta alan tanımı.
 *
 * @param WP_Post $post Yazı.
 * @param string  $key  Anahtar.
 * @return array|null
 */
function ceb_live_meta_field( $post, $key ) {
	foreach ( ce_meta_boxes( $post ) as $box ) {
		if ( ! in_array( $post->post_type, $box['screens'], true ) ) {
			continue;
		}
		foreach ( $box['fields'] as $field ) {
			if ( $field['id'] === $key ) {
				return $field;
			}
		}
	}
	return null;
}

/**
 * Tek bir kaynağa değer yazar.
 *
 * @param string $source Kaynak.
 * @param mixed  $value  Değer.
 * @param array  $posts  Blok listeleri.
 * @return true|WP_Error
 */
function ceb_live_apply_value( $source, $value, &$posts ) {
	$parts = explode( ':', $source );
	$kind  = $parts[0] ?? '';

	switch ( $kind ) {
		case 'opt':
			if ( ! current_user_can( 'ce_manage_settings' ) ) {
				return new WP_Error( 'ceb_perm', 'Tema Ayarları\'nı düzenleme yetkiniz yok.' );
			}
			list( $key, $index, $sub ) = ceb_live_path( $parts[1] ?? '' );
			$field                     = ceb_live_option_field( $key );
			if ( ! $field ) {
				return new WP_Error( 'ceb_missing', 'Ayar bulunamadı.' );
			}
			$options = get_option( CE_OPTION, array() );
			$options = is_array( $options ) ? $options : array();
			$current = $options[ $key ] ?? ce_opt( $key );
			$clean   = ceb_live_set_path( $field, $current, $index, $sub, $value );
			if ( is_wp_error( $clean ) ) {
				return $clean;
			}
			$options[ $key ] = $clean;
			update_option( CE_OPTION, $options );
			$GLOBALS['ce_opt_reset'] = true;
			return true;

		case 'meta':
			$post = get_post( (int) ( $parts[1] ?? 0 ) );
			if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) {
				return new WP_Error( 'ceb_perm', 'Bu içeriği düzenleme yetkiniz yok.' );
			}
			list( $key, $index, $sub ) = ceb_live_path( $parts[2] ?? '' );
			$field                     = ceb_live_meta_field( $post, $key );
			if ( ! $field ) {
				return new WP_Error( 'ceb_missing', 'Alan bulunamadı.' );
			}
			$clean = ceb_live_set_path( $field, get_post_meta( $post->ID, '_ce_' . $key, true ), $index, $sub, $value );
			if ( is_wp_error( $clean ) ) {
				return $clean;
			}
			update_post_meta( $post->ID, '_ce_' . $key, $clean );
			return true;

		case 'post':
			$post  = get_post( (int) ( $parts[1] ?? 0 ) );
			$field = $parts[2] ?? '';
			if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) {
				return new WP_Error( 'ceb_perm', 'Bu içeriği düzenleme yetkiniz yok.' );
			}
			if ( 'title' === $field ) {
				$r = wp_update_post( array( 'ID' => $post->ID, 'post_title' => wp_slash( sanitize_text_field( (string) $value ) ) ), true );
			} elseif ( 'excerpt' === $field ) {
				$r = wp_update_post( array( 'ID' => $post->ID, 'post_excerpt' => wp_slash( sanitize_textarea_field( (string) $value ) ) ), true );
			} else {
				return new WP_Error( 'ceb_type', 'Desteklenmeyen alan.' );
			}
			return is_wp_error( $r ) ? $r : true;

		case 'thumb':
			$post_id = (int) ( $parts[1] ?? 0 );
			$att     = absint( $value );
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return new WP_Error( 'ceb_perm', 'Bu içeriği düzenleme yetkiniz yok.' );
			}
			if ( ! wp_attachment_is_image( $att ) ) {
				return new WP_Error( 'ceb_img', 'Geçersiz görsel.' );
			}
			set_post_thumbnail( $post_id, $att );
			return true;

		case 'battr':
			return ceb_live_apply_block_attr( (int) ( $parts[1] ?? 0 ), (string) ( $parts[2] ?? '' ), (string) ( $parts[3] ?? '' ), $value, $posts );

		case 'cblock':
			return ceb_live_apply_core_text( (int) ( $parts[1] ?? 0 ), (string) ( $parts[2] ?? '' ), (string) $value, $posts );

		case 'cimg':
			return ceb_live_apply_core_image( (int) ( $parts[1] ?? 0 ), (string) ( $parts[2] ?? '' ), absint( $value ), $posts );
	}
	return new WP_Error( 'ceb_source', 'Bilinmeyen kaynak.' );
}

/**
 * Can Eloksal bloğu özniteliği.
 *
 * @param int    $post_id Yazı.
 * @param string $key     Blok anahtarı.
 * @param string $path    Öznitelik yolu.
 * @param mixed  $value   Değer.
 * @param array  $posts   Blok listeleri.
 * @return true|WP_Error
 */
function ceb_live_apply_block_attr( $post_id, $key, $path, $value, &$posts ) {
	$list = &ceb_live_blocks( $post_id, $posts );
	if ( is_wp_error( $list ) ) {
		return $list;
	}
	if ( ! isset( $list[ $key ] ) || 0 !== strpos( (string) $list[ $key ]['blockName'], 'ce/' ) ) {
		return new WP_Error( 'ceb_missing', 'Blok bulunamadı.' );
	}
	$slug = substr( $list[ $key ]['blockName'], 3 );
	$defs = ceb_blocks();
	list( $attr, $index, $sub ) = ceb_live_path( $path );
	if ( ! isset( $defs[ $slug ]['fields'][ $attr ] ) ) {
		return new WP_Error( 'ceb_missing', 'Blok alanı bulunamadı.' );
	}
	$def     = $defs[ $slug ]['fields'][ $attr ];
	$current = $list[ $key ]['attrs'][ $attr ] ?? '';

	if ( null === $index ) {
		$clean = ceb_live_clean( 'lines' === $def[0] ? 'lines' : $def[0], $value );
	} else {
		// Satır listesi: boşsa Tema Ayarları'ndaki listeden başlar.
		$icons = 'process_steps' !== $attr;
		if ( '' === trim( (string) $current ) ) {
			$current = ceb_rows_to_lines( ce_opt( $attr ), $icons );
		}
		$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $current ) ), 'strlen' ) );
		if ( ! isset( $lines[ $index ] ) ) {
			return new WP_Error( 'ceb_missing', 'Satır bulunamadı.' );
		}
		$cols = array_map( 'trim', explode( '|', $lines[ $index ] ) );
		$map  = array( 'title' => 0, 'text' => 1, 'icon' => 2 );
		$col  = $map[ $sub ?? 'title' ] ?? 0;
		while ( count( $cols ) <= $col ) {
			$cols[] = '';
		}
		$cols[ $col ]    = str_replace( '|', '/', sanitize_text_field( (string) $value ) );
		$lines[ $index ] = rtrim( implode( ' | ', $cols ), ' |' );
		$clean           = implode( "\n", $lines );
	}
	if ( is_wp_error( $clean ) ) {
		return $clean;
	}
	$list[ $key ]['attrs'][ $attr ] = $clean;
	return true;
}

/**
 * Metin bloğu (paragraf, başlık, liste) HTML'i.
 *
 * @param int    $post_id Yazı.
 * @param string $key     Blok anahtarı.
 * @param string $html    Yeni HTML (öğenin kendisi).
 * @param array  $posts   Blok listeleri.
 * @return true|WP_Error
 */
function ceb_live_apply_core_text( $post_id, $key, $html, &$posts ) {
	$list = &ceb_live_blocks( $post_id, $posts );
	if ( is_wp_error( $list ) ) {
		return $list;
	}
	if ( ! isset( $list[ $key ] ) ) {
		return new WP_Error( 'ceb_missing', 'Blok bulunamadı.' );
	}
	$block = $list[ $key ];
	$name  = (string) $block['blockName'];
	$html  = preg_replace( '/\s(data-ce-[\w-]+|contenteditable|spellcheck)="[^"]*"/', '', $html );
	$html  = preg_replace( '/\sclass="\s*"/', '', $html );
	$html  = trim( wp_kses_post( $html ) );

	if ( 'core/paragraph' === $name || 'core/heading' === $name ) {
		$tag = 'core/paragraph' === $name ? 'p' : 'h' . (int) ( $block['attrs']['level'] ?? 2 );
		if ( ! preg_match( '#^<' . $tag . '[\s>].*</' . $tag . '>$#is', $html ) ) {
			return new WP_Error( 'ceb_html', 'Metin biçimi korunamadı.' );
		}
		$block['innerHTML']    = $html;
		$block['innerContent'] = array( $html );
	} elseif ( 'core/list' === $name ) {
		if ( ! preg_match_all( '#<li[^>]*>(.*?)</li>#is', $html, $m ) ) {
			return new WP_Error( 'ceb_html', 'Liste boş olamaz.' );
		}
		$tag   = ! empty( $block['attrs']['ordered'] ) ? 'ol' : 'ul';
		$items = array();
		$inner = array( '<' . $tag . ' class="wp-block-list">' );
		foreach ( $m[1] as $li ) {
			$li_html = '<li>' . trim( $li ) . '</li>';
			$items[] = array( 'blockName' => 'core/list-item', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => $li_html, 'innerContent' => array( $li_html ) );
			$inner[] = null;
		}
		$inner[]               = '</' . $tag . '>';
		$block['innerBlocks']  = $items;
		$block['innerHTML']    = '<' . $tag . ' class="wp-block-list"></' . $tag . '>';
		$block['innerContent'] = $inner;
	} else {
		return new WP_Error( 'ceb_type', 'Bu blok canlı editörden metin olarak düzenlenemez; blok editörünü kullanın.' );
	}
	$list[ $key ] = $block;
	return true;
}

/**
 * Görsel bloğu.
 *
 * @param int    $post_id Yazı.
 * @param string $key     Blok anahtarı.
 * @param int    $att     Ek.
 * @param array  $posts   Blok listeleri.
 * @return true|WP_Error
 */
function ceb_live_apply_core_image( $post_id, $key, $att, &$posts ) {
	$list = &ceb_live_blocks( $post_id, $posts );
	if ( is_wp_error( $list ) ) {
		return $list;
	}
	if ( ! isset( $list[ $key ] ) || 'core/image' !== $list[ $key ]['blockName'] || ! wp_attachment_is_image( $att ) ) {
		return new WP_Error( 'ceb_missing', 'Görsel bloğu bulunamadı.' );
	}
	$block = $list[ $key ];
	$size  = $block['attrs']['sizeSlug'] ?? 'large';
	$url   = wp_get_attachment_image_url( $att, $size );
	$alt   = (string) get_post_meta( $att, '_wp_attachment_image_alt', true );
	$html  = (string) $block['innerHTML'];
	$html  = preg_replace( '/src="[^"]*"/', 'src="' . esc_url( $url ) . '"', $html, 1 );
	$html  = preg_replace( '/wp-image-\d+/', 'wp-image-' . $att, $html, 1 );
	$html  = preg_replace( '/alt="[^"]*"/', 'alt="' . esc_attr( $alt ) . '"', $html, 1 );
	$block['attrs']['id']  = $att;
	$block['innerHTML']    = $html;
	$block['innerContent'] = array( $html );
	$list[ $key ]          = $block;
	return true;
}

/* -------------------------------------------------------------------------
 * Stiller
 * ---------------------------------------------------------------------- */

/**
 * İzin verilen CSS özellikleri.
 *
 * @return string[]
 */
function ceb_css_props() {
	return array(
		'color', 'background-color', 'background-image', 'background-size', 'background-position', 'background-repeat',
		'font-family', 'font-size', 'font-weight', 'font-style', 'line-height', 'letter-spacing', 'text-align', 'text-transform', 'text-decoration',
		'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
		'width', 'max-width', 'min-height', 'height', 'gap', 'display', 'flex-direction', 'align-items', 'justify-content', 'flex-wrap',
		'border-width', 'border-style', 'border-color', 'border-radius', 'box-shadow', 'opacity', 'z-index', 'object-fit', 'aspect-ratio',
	);
}

/**
 * Seçiciyi doğrular.
 *
 * @param string $selector Seçici.
 * @return string Boşsa geçersiz.
 */
function ceb_clean_selector( $selector ) {
	$selector = trim( preg_replace( '/\s+/', ' ', (string) $selector ) );
	if ( '' === $selector || strlen( $selector ) > 500 ) {
		return '';
	}
	if ( ! preg_match( '/^[a-zA-Z0-9_\-.#:\s>()+~,*\[\]="]+$/', $selector ) || preg_match( '/[{};<@]|\/\*|url|expression|import/i', $selector ) ) {
		return '';
	}
	return $selector;
}

/**
 * CSS değerini doğrular.
 *
 * @param string $prop  Özellik.
 * @param string $value Değer.
 * @return string|null Null: geçersiz, '': sil.
 */
function ceb_clean_css_value( $prop, $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( strlen( $value ) > 200 || preg_match( '/[{};<>\\\\]|\/\*|url\s*\(|expression|javascript|import|behavior/i', $value ) ) {
		return null;
	}
	if ( 'background-image' === $prop ) {
		return ( 'none' === $value || preg_match( '/^att:\d+$/', $value ) ) ? $value : null;
	}
	if ( 'font-family' === $prop ) {
		$allowed = function_exists( 'ce_font_choices' ) ? array_values( wp_list_pluck( ce_font_choices(), 1 ) ) : array();
		$allowed[] = 'inherit';
		return in_array( $value, $allowed, true ) ? $value : null;
	}
	return preg_match( '/^[#a-zA-Z0-9 .,%()\-+\'"]+$/', $value ) ? $value : null;
}

/**
 * Stilleri kaydeder.
 *
 * @param string $context Sayfa bağlamı.
 * @param array  $styles  [global|page][cihaz][seçici][özellik] = değer.
 * @param array  $resets  [{scope, selector}] sıfırlanacak seçiciler.
 * @return int|WP_Error Uygulanan sayı.
 */
function ceb_live_save_styles( $context, $styles, $resets ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'ceb_perm', 'Stil değiştirme yetkiniz yok (yalnızca metinler kaydedildi).' );
	}
	if ( ! preg_match( '/^(front|blog|search|404|other|post-\d+|term-\d+|archive-[a-z0-9_\-]+)$/', $context ) ) {
		return new WP_Error( 'ceb_ctx', 'Geçersiz sayfa bağlamı.' );
	}
	$all     = ceb_live_styles();
	$props   = ceb_css_props();
	$devices = array( 'desktop', 'tablet', 'mobile' );
	$count   = 0;

	ceb_live_push_history( $all );

	foreach ( $resets as $reset ) {
		$ctx = 'global' === ( $reset['scope'] ?? '' ) ? 'global' : $context;
		$sel = ceb_clean_selector( $reset['selector'] ?? '' );
		if ( ! $sel ) {
			continue;
		}
		foreach ( $devices as $d ) {
			unset( $all[ $ctx ][ $d ][ $sel ] );
		}
		++$count;
	}

	foreach ( array( 'global', 'page' ) as $scope ) {
		$ctx = 'global' === $scope ? 'global' : $context;
		foreach ( $devices as $d ) {
			foreach ( (array) ( $styles[ $scope ][ $d ] ?? array() ) as $selector => $declarations ) {
				$sel = ceb_clean_selector( $selector );
				if ( ! $sel ) {
					continue;
				}
				foreach ( (array) $declarations as $prop => $value ) {
					$prop = strtolower( (string) $prop );
					if ( ! in_array( $prop, $props, true ) ) {
						continue;
					}
					$clean = ceb_clean_css_value( $prop, $value );
					if ( null === $clean ) {
						continue;
					}
					if ( '' === $clean ) {
						unset( $all[ $ctx ][ $d ][ $sel ][ $prop ] );
					} else {
						$all[ $ctx ][ $d ][ $sel ][ $prop ] = $clean;
					}
					++$count;
				}
				if ( empty( $all[ $ctx ][ $d ][ $sel ] ) ) {
					unset( $all[ $ctx ][ $d ][ $sel ] );
				}
			}
		}
	}

	// Boş dalları temizle.
	foreach ( $all as $ctx => $devs ) {
		foreach ( (array) $devs as $d => $sels ) {
			if ( empty( $sels ) ) {
				unset( $all[ $ctx ][ $d ] );
			}
		}
		if ( empty( $all[ $ctx ] ) ) {
			unset( $all[ $ctx ] );
		}
	}
	update_option( CEB_LIVE_OPTION, $all, true );
	return $count;
}

/**
 * Stil geçmişine anlık görüntü ekler (son 15 kayıt).
 *
 * @param array $styles Stiller.
 */
function ceb_live_push_history( $styles ) {
	$history = get_option( 'ceb_live_styles_history', array() );
	$history = is_array( $history ) ? $history : array();
	array_unshift( $history, array( 'time' => time(), 'user' => wp_get_current_user()->display_name, 'styles' => $styles ) );
	update_option( 'ceb_live_styles_history', array_slice( $history, 0, 15 ), false );
}

add_action( 'admin_post_ceb_live_restore', 'ceb_live_restore' );
/**
 * Stil geçmişinden geri yükleme / tüm canlı stilleri sıfırlama.
 */
function ceb_live_restore() {
	check_admin_referer( 'ceb_live_restore' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$index   = isset( $_POST['index'] ) ? (int) $_POST['index'] : -2;
	$history = get_option( 'ceb_live_styles_history', array() );
	ceb_live_push_history( ceb_live_styles() );
	if ( -1 === $index ) {
		update_option( CEB_LIVE_OPTION, array(), true );
	} elseif ( isset( $history[ $index ]['styles'] ) ) {
		update_option( CEB_LIVE_OPTION, $history[ $index ]['styles'], true );
	}
	if ( function_exists( 'ce_audit' ) ) {
		ce_audit( 'restore', 'live', 0, -1 === $index ? 'Canlı stiller sıfırlandı' : 'Canlı stil geçmişi geri yüklendi' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=ceb-builder&ceb=styles' ) );
	exit;
}
