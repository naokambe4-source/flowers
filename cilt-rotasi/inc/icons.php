<?php
/**
 * Satır içi SVG ikon seti (ikon fontu yüklemeden, performans için).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * İkon yolları (24x24, stroke tabanlı).
 *
 * @return array<string,string>
 */
function cr_icon_paths() {
	return array(
		'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'bookmark'    => '<path d="M6 4h12v17l-6-4-6 4z"/>',
		'bookmarked'  => '<path d="M6 4h12v17l-6-4-6 4z" fill="currentColor"/>',
		'menu'        => '<path d="M4 7h16M4 12h16M4 17h10"/>',
		'close'       => '<path d="M6 6l12 12M18 6 6 18"/>',
		'home'        => '<path d="M4 11 12 4l8 7"/><path d="M6 10v10h12V10"/>',
		'compass'     => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
		'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
		'grid'        => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
		'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-left'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
		'chevron-down'   => '<path d="m6 9 6 6 6-6"/>',
		'chevron-right'  => '<path d="m9 6 6 6-6 6"/>',
		'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'calendar'    => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>',
		'refresh'     => '<path d="M20 11a8 8 0 0 0-14.5-4.5L4 8"/><path d="M4 4v4h4"/><path d="M4 13a8 8 0 0 0 14.5 4.5L20 16"/><path d="M20 20v-4h-4"/>',
		'share'       => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.4M8.2 13.2l7.6 4.4"/>',
		'link'        => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'check'       => '<path d="m5 12 5 5 9-10"/>',
		'heart'       => '<path d="M12 20s-7-4.5-8.5-9A4.8 4.8 0 0 1 12 6.5 4.8 4.8 0 0 1 20.5 11C19 15.5 12 20 12 20z"/>',
		'leaf'        => '<path d="M5 19c0-8 5-13 15-14-1 10-6 15-14 15"/><path d="M5 19 13 11"/>',
		'drop'        => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>',
		'sun'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'        => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
		'sparkle'     => '<path d="M12 3c.6 4.2 2.8 6.4 7 7-4.2.6-6.4 2.8-7 7-.6-4.2-2.8-6.4-7-7 4.2-.6 6.4-2.8 7-7z"/><path d="M19 17c.2 1.2.8 1.8 2 2-1.2.2-1.8.8-2 2-.2-1.2-.8-1.8-2-2 1.2-.2 1.8-.8 2-2z"/>',
		'shield'      => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'flask'       => '<path d="M9 3h6M10 3v6L4.5 18.5A1.7 1.7 0 0 0 6 21h12a1.7 1.7 0 0 0 1.5-2.5L14 9V3"/><path d="M7 15h10"/>',
		'book'        => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/>',
		'list'        => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
		'filter'      => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'info'        => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
		'alert'       => '<path d="M12 4 2.8 19.5h18.4z"/><path d="M12 10v4.5M12 17v.5"/>',
		'bulb'        => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/>',
		'star'        => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
		'edit'        => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
		'eye'         => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'layers'      => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
		'face'        => '<path d="M12 3c4.4 0 7 3.4 7 8.5S16 21 12 21s-7-4.4-7-9.5S7.6 3 12 3z"/><path d="M9 10.5v.5M15 10.5v.5M10 15.5c1.2.8 2.8.8 4 0"/>',
		'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
		'quote'       => '<path d="M7 17c-2 0-3-1.5-3-3.5C4 10 6 7.5 9 6.5M17 17c-2 0-3-1.5-3-3.5 0-3.5 2-6 5-7"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'minus'       => '<path d="M5 12h14"/>',
		'arrow-up'    => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'instagram'   => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor"/>',
		'pinterest'   => '<circle cx="12" cy="12" r="9"/><path d="M10.5 20.5 12.5 12M10 13.8c.4 1.2 1.3 1.7 2.4 1.7 2.3 0 3.6-2.2 3.6-4.6C16 8.4 14.2 7 12 7c-2.7 0-4.3 2-4.3 4 0 .9.3 1.7.9 2.1"/>',
		'tiktok'      => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.8 2.2 4.3 5 4.5"/>',
		'youtube'     => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="m10 9.5 5 2.5-5 2.5z" fill="currentColor"/>',
		'x'           => '<path d="M4 4l16 16M20 4 4 20"/>',
		'whatsapp'    => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1c-1-.5-1.8-1.3-2.3-2.3l1-1-1-2z"/>',
		'facebook'    => '<path d="M14 21v-8h3l.5-3.5H14V7.8c0-1 .3-1.8 1.8-1.8H18V3a24 24 0 0 0-2.8-.2c-2.8 0-4.7 1.7-4.7 4.8v2.9H7.5V13h3v8"/>',
		'copy'        => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
		'command'     => '<path d="M9 6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3z"/>',
		'target'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
		'chart'       => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
		'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
	);
}

/**
 * SVG ikon döndürür.
 *
 * @param string $name  İkon adı.
 * @param int    $size  Boyut (px).
 * @param string $class Ek sınıf.
 * @return string
 */
function cr_icon( $name, $size = 22, $class = '' ) {
	$paths = cr_icon_paths();
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="cr-icon %1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $class ),
		(int) $size,
		$paths[ $name ]
	);
}

/**
 * İkon seçimi için liste (yönetim paneli).
 *
 * @return array<string,string>
 */
function cr_icon_choices() {
	$out = array( '' => '— Yok —' );
	foreach ( array_keys( cr_icon_paths() ) as $k ) {
		$out[ $k ] = $k;
	}
	return $out;
}
