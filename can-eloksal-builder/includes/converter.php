<?php
/**
 * Blok işaretlemesi üretimi: ana sayfayı bloklara aktarma ve hazır düzenler için yardımcılar.
 *
 * @package CanEloksalBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tek bir Can Eloksal bloğunun işaretlemesi.
 *
 * @param string $name  Blok adı (ce/ olmadan).
 * @param array  $attrs Öznitelikler.
 * @param string $inner İç içerik (kapsayıcı bloklar için).
 * @return string
 */
function ceb_block( $name, $attrs = array(), $inner = '' ) {
	$attrs = array_filter( $attrs, static fn( $v ) => '' !== $v && null !== $v );
	if ( '' === $inner ) {
		return serialize_block( array( 'blockName' => 'ce/' . $name, 'attrs' => $attrs, 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) ) . "\n\n";
	}
	$open = '<!-- wp:ce/' . $name . ( $attrs ? ' ' . serialize_block_attributes( $attrs ) : '' ) . ' -->';
	return $open . "\n" . $inner . "\n<!-- /wp:ce/" . $name . " -->\n\n";
}

/**
 * Çekirdek paragraf/başlık/liste blokları.
 *
 * @param string $type p|h2|h3|ul.
 * @param mixed  $text Metin veya liste dizisi.
 * @return string
 */
function ceb_core( $type, $text ) {
	switch ( $type ) {
		case 'h2':
		case 'h3':
			$level = (int) substr( $type, 1 );
			return '<!-- wp:heading' . ( 3 === $level ? ' {"level":3}' : '' ) . ' -->' . "\n<h{$level} class=\"wp-block-heading\">" . esc_html( $text ) . "</h{$level}>\n<!-- /wp:heading -->\n\n";
		case 'ul':
			$items = '';
			foreach ( (array) $text as $li ) {
				$items .= '<!-- wp:list-item --><li>' . esc_html( $li ) . '</li><!-- /wp:list-item -->';
			}
			return "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . $items . "</ul>\n<!-- /wp:list -->\n\n";
		case 'buttons':
			$btns = '';
			foreach ( (array) $text as $b ) {
				$btns .= '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $b[1] ) . '">' . esc_html( $b[0] ) . '</a></div><!-- /wp:button -->';
			}
			return "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\">" . $btns . "</div>\n<!-- /wp:buttons -->\n\n";
		default:
			return "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->\n\n";
	}
}

/**
 * Tema dizisini "Başlık | Açıklama | ikon" satırlarına çevirir.
 *
 * @param array $rows  Satırlar.
 * @param bool  $icons İkon sütunu.
 * @return string
 */
function ceb_rows_to_lines( $rows, $icons = true ) {
	$lines = array();
	foreach ( (array) $rows as $r ) {
		$parts = array( $r['title'] ?? '', $r['text'] ?? '' );
		if ( $icons ) {
			$parts[] = $r['icon'] ?? 'check';
		}
		$lines[] = implode( ' | ', $parts );
	}
	return implode( "\n", $lines );
}

/**
 * Ana sayfanın şu anki görünümünü (sıra, açık/kapalı bölümler ve tüm metinler) bloklara çevirir.
 *
 * @return string
 */
function ceb_home_markup() {
	$o   = static fn( $k ) => ce_opt( $k );
	$out = '';
	foreach ( ce_normalize_sections( ce_opt( 'home_sections' ), ce_home_section_labels() ) as $s ) {
		if ( empty( $s['on'] ) ) {
			continue;
		}
		switch ( $s['key'] ) {
			case 'hero':
				$out .= ceb_block( 'slider' );
				break;
			case 'trust':
				$out .= ceb_block( 'trust', array( 'trust_items' => ceb_rows_to_lines( $o( 'trust_items' ) ) ) );
				break;
			case 'services':
				$out .= ceb_block( 'services', array( 'services_eyebrow' => $o( 'services_eyebrow' ), 'services_title' => $o( 'services_title' ), 'services_text' => $o( 'services_text' ), 'services_count' => (int) $o( 'services_count' ) ) );
				break;
			case 'about':
				$out .= ceb_block(
					'about',
					array(
						'about_eyebrow' => $o( 'about_eyebrow' ),
						'about_title'   => $o( 'about_title' ),
						'about_text'    => $o( 'about_text' ),
						'about_image'   => absint( $o( 'about_image' ) ),
						'about_image_2' => absint( $o( 'about_image_2' ) ),
						'about_badge'   => $o( 'about_badge' ),
						'about_button'  => $o( 'about_button' ),
						'about_link'    => $o( 'about_link' ),
					)
				);
				break;
			case 'why':
				$out .= ceb_block( 'features', array( 'why_eyebrow' => $o( 'why_eyebrow' ), 'why_title' => $o( 'why_title' ), 'why_items' => ceb_rows_to_lines( $o( 'why_items' ) ) ) );
				break;
			case 'sectors':
				$out .= ceb_block( 'sectors', array( 'sectors_eyebrow' => $o( 'sectors_eyebrow' ), 'sectors_title' => $o( 'sectors_title' ) ) );
				break;
			case 'process':
				$out .= ceb_block( 'process', array( 'process_eyebrow' => $o( 'process_eyebrow' ), 'process_title' => $o( 'process_title' ), 'process_steps' => ceb_rows_to_lines( $o( 'process_steps' ), false ) ) );
				break;
			case 'gallery':
				$out .= ceb_block( 'gallery', array( 'gallery_eyebrow' => $o( 'gallery_eyebrow' ), 'gallery_title' => $o( 'gallery_title' ), 'gallery_count' => (int) $o( 'gallery_count' ) ) );
				break;
			case 'cta':
				$out .= ceb_block( 'cta', array( 'cta_title' => $o( 'cta_title' ), 'cta_text' => $o( 'cta_text' ), 'cta_image' => absint( $o( 'cta_image' ) ), 'cta_button' => $o( 'cta_button' ), 'cta_link' => $o( 'cta_link' ) ) );
				break;
			case 'blog':
				$out .= ceb_block( 'posts', array( 'blog_eyebrow' => $o( 'blog_eyebrow' ), 'blog_title' => $o( 'blog_title' ) ) );
				break;
			case 'references':
				$out .= ceb_block( 'references', array( 'refs_title' => $o( 'refs_title' ) ) );
				break;
			case 'contact':
				$out .= ceb_block( 'contact', array( 'contact_title' => $o( 'contact_title' ), 'contact_text' => $o( 'contact_text' ) ) );
				break;
		}
	}
	return trim( $out );
}
