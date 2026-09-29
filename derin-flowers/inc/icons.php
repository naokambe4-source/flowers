<?php
/**
 * İnce çizgili SVG ikon seti. Kategori, menü, güven şeridi ve header ikonları buradan gelir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * İkon yolları (24x24 viewBox, stroke tabanlı).
 *
 * @return array<string, array{label:string, path:string}>
 */
function df_icon_library() {
	static $icons = null;
	if ( null !== $icons ) {
		return $icons;
	}
	$icons = array(
		// Arayüz.
		'search'      => array( 'Arama', '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>' ),
		'user'        => array( 'Hesap', '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c1.2-3.8 4-5.5 7.5-5.5s6.3 1.7 7.5 5.5"/>' ),
		'heart'       => array( 'Kalp / Favori', '<path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.3a4.3 4.3 0 0 1 7.5 2.5C19.5 15.4 12 20 12 20Z"/>' ),
		'bag'         => array( 'Sepet', '<path d="M5 8h14l-1 12.5H6L5 8Z"/><path d="M9 10V6.5a3 3 0 0 1 6 0V10"/>' ),
		'menu'        => array( 'Menü', '<path d="M3.5 7h17M3.5 12h17M3.5 17h11"/>' ),
		'grid'        => array( 'Kategoriler', '<rect x="4" y="4" width="6.5" height="6.5"/><rect x="13.5" y="4" width="6.5" height="6.5"/><rect x="4" y="13.5" width="6.5" height="6.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5"/>' ),
		'close'       => array( 'Kapat', '<path d="M6 6l12 12M18 6 6 18"/>' ),
		'arrow-right' => array( 'Ok sağ', '<path d="M4 12h15.5M14 6.5l5.5 5.5-5.5 5.5"/>' ),
		'arrow-left'  => array( 'Ok sol', '<path d="M20 12H4.5M10 6.5 4.5 12l5.5 5.5"/>' ),
		'chevron-down'=> array( 'Aşağı', '<path d="m6 9.5 6 6 6-6"/>' ),
		'chevron-right'=> array( 'Sağ', '<path d="m9.5 6 6 6-6 6"/>' ),
		'chevron-left'=> array( 'Sol', '<path d="m14.5 6-6 6 6 6"/>' ),
		'plus'        => array( 'Artı', '<path d="M12 5v14M5 12h14"/>' ),
		'minus'       => array( 'Eksi', '<path d="M5 12h14"/>' ),
		'check'       => array( 'Onay', '<path d="m5 12.5 4.5 4.5L19 7.5"/>' ),
		'play'        => array( 'Oynat', '<path d="M8 5.5v13l10.5-6.5L8 5.5Z"/>' ),
		'eye'         => array( 'Göz', '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>' ),
		'filter'      => array( 'Filtre', '<path d="M4 6h16M7 12h10M10 18h4"/>' ),
		'zoom'        => array( 'Büyüt', '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2M11 8v6M8 11h6"/>' ),
		'info'        => array( 'Bilgi', '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.6v.1"/>' ),
		'lock'        => array( 'Kilit', '<rect x="5" y="10.5" width="14" height="10" rx="1"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>' ),
		'edit'        => array( 'Düzenle', '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>' ),
		// Hizmet / güven.
		'truck'       => array( 'Teslimat aracı', '<path d="M2.5 6.5h11v10h-11z"/><path d="M13.5 10h4l3 3.2v3.3h-7"/><circle cx="6.5" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>' ),
		'clock'       => array( 'Saat', '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>' ),
		'calendar'    => array( 'Takvim', '<rect x="3.5" y="5" width="17" height="15.5" rx="1"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>' ),
		'shield'      => array( 'Güvenli', '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8 7.5 9.5 4.3-1.5 7.5-4.9 7.5-9.5V6L12 3Z"/><path d="m8.8 12 2.2 2.2 4.3-4.4"/>' ),
		'card'        => array( 'Kredi kartı', '<rect x="3" y="5.5" width="18" height="13" rx="1"/><path d="M3 10h18M7 15h4"/>' ),
		'leaf'        => array( 'Yaprak / Tazelik', '<path d="M5 19c0-8 5-13.5 14.5-14-.3 9.6-5.9 14.5-14 14Z"/><path d="M5 19 13 11"/>' ),
		'sparkle'     => array( 'Işıltı / Özen', '<path d="M12 3.5c.6 4.4 2.1 5.9 6.5 6.5-4.4.6-5.9 2.1-6.5 6.5-.6-4.4-2.1-5.9-6.5-6.5 4.4-.6 5.9-2.1 6.5-6.5Z"/><path d="M18.5 15.5c.2 1.6.9 2.3 2.5 2.5-1.6.2-2.3.9-2.5 2.5-.2-1.6-.9-2.3-2.5-2.5 1.6-.2 2.3-.9 2.5-2.5Z"/>' ),
		'hand'        => array( 'El yapımı', '<path d="M8 12V5.5a1.5 1.5 0 0 1 3 0V11"/><path d="M11 10V4.5a1.5 1.5 0 0 1 3 0V11"/><path d="M14 10V6a1.5 1.5 0 0 1 3 0v7c0 4.2-2.6 7.5-6.5 7.5-2.6 0-4-1.2-5.4-3.4L3 13.4a1.5 1.5 0 0 1 2.4-1.8L8 14"/>' ),
		'store'       => array( 'Mağaza', '<path d="M4 9.5V20h16V9.5"/><path d="M3 9.5 5 4h14l2 5.5c0 1.4-1.1 2.3-2.3 2.3S16.4 10.9 16.4 9.5c0 1.4-1.1 2.3-2.2 2.3S12 10.9 12 9.5c0 1.4-1.1 2.3-2.2 2.3S7.6 10.9 7.6 9.5c0 1.4-1.1 2.3-2.3 2.3S3 10.9 3 9.5Z"/><path d="M10 20v-5h4v5"/>' ),
		'pin'         => array( 'Konum', '<path d="M12 21s-6.5-5.8-6.5-11a6.5 6.5 0 0 1 13 0c0 5.2-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/>' ),
		'phone'       => array( 'Telefon', '<path d="M5 4h3.5l1.5 4.2-2 1.3a10.5 10.5 0 0 0 6.5 6.5l1.3-2L20 15.5V19a1 1 0 0 1-1 1C11 20 4 13 4 5a1 1 0 0 1 1-1Z"/>' ),
		'mail'        => array( 'E-posta', '<rect x="3" y="5.5" width="18" height="13" rx="1"/><path d="m3.5 6.5 8.5 7 8.5-7"/>' ),
		'package'     => array( 'Paket', '<path d="M3.5 7.5 12 3.5l8.5 4v9L12 20.5l-8.5-4v-9Z"/><path d="M3.5 7.5 12 11.5l8.5-4M12 11.5v9"/>' ),
		'note'        => array( 'Not kartı', '<rect x="4" y="4" width="16" height="16" rx="1"/><path d="M8 9h8M8 12.5h8M8 16h5"/>' ),
		'gift'        => array( 'Hediye', '<rect x="4" y="9" width="16" height="11.5"/><path d="M3 9h18M12 9v11.5"/><path d="M12 9c-1.5-3.8-5.5-4.2-5.5-1.8S9.5 9 12 9Zm0 0c1.5-3.8 5.5-4.2 5.5-1.8S14.5 9 12 9Z"/>' ),
		'star'        => array( 'Yıldız', '<path d="m12 3.8 2.5 5.1 5.6.8-4 3.9 1 5.6-5.1-2.7-5 2.7 1-5.6-4.1-3.9 5.6-.8L12 3.8Z"/>' ),
		'home'        => array( 'Ev', '<path d="M4 10.5 12 4l8 6.5V20h-5.5v-5.5h-5V20H4v-9.5Z"/>' ),
		// Çiçek / kategori ikonları.
		'bouquet'     => array( 'Buket', '<circle cx="8.5" cy="7" r="2.6"/><circle cx="15.5" cy="7" r="2.6"/><circle cx="12" cy="4.8" r="2.3"/><path d="M8 11.5h8l-2.6 3.1h-2.8L8 11.5Z"/><path d="M10.6 14.6 9.5 21h5l-1.1-6.4"/><path d="M12 9.6v4.8"/>' ),
		'rose'        => array( 'Gül', '<path d="M12 11.5c-3 0-4.6-2-4.6-4.6C7.4 4.9 9.5 3.5 12 3.5s4.6 1.4 4.6 3.4c0 2.6-1.6 4.6-4.6 4.6Z"/><path d="M12 11.5c-1.6-1.2-1.8-3.6 0-5 1.1 1 2.3.6 2.6-.6"/><path d="M12 11.5V21"/><path d="M12 16c-1.2-2.3-3.5-2.7-5-2.2.7 2 2.8 3 5 2.2ZM12 18c1.2-2.1 3.3-2.4 4.7-1.9-.7 1.8-2.6 2.6-4.7 1.9Z"/>' ),
		'orchid'      => array( 'Orkide', '<path d="M6 21h6"/><path d="M7 21v-3h4v3"/><path d="M9 18c0-6 2-10 7-12.5"/><path d="M16 5.5c1.3-1.9 3.6-1.4 3.8.3.2 1.7-2 2.4-3.8-.3Z"/><path d="M12.4 9.2c-1.8-.9-3.7.1-3.4 1.6.3 1.5 2.7 1.3 3.4-1.6Z"/><path d="M16.4 10c.7-1.9 3-2 3.4-.5.4 1.6-1.7 2.5-3.4.5Z"/>' ),
		'box'         => array( 'Kutuda çiçek', '<path d="M5 11h14v9.5H5z"/><path d="M4 9h16v2H4z"/><circle cx="8.6" cy="6.3" r="2.1"/><circle cx="12" cy="5.2" r="2.1"/><circle cx="15.4" cy="6.3" r="2.1"/>' ),
		'vase'        => array( 'Vazoda çiçek', '<path d="M9 12h6c.2 3.3 2 4.6 2 6.5 0 1.3-.8 2-2 2H9c-1.2 0-2-.7-2-2 0-1.9 1.8-3.2 2-6.5Z"/><path d="M10 12V9.5M14 12V9.5M12 12V7"/><circle cx="12" cy="5.2" r="1.9"/><circle cx="9" cy="8" r="1.7"/><circle cx="15" cy="8" r="1.7"/>' ),
		'ring'        => array( 'Söz & Nişan', '<circle cx="12" cy="15" r="5.5"/><path d="m9.5 6.5 1.3-2.5h2.4l1.3 2.5L12 9.5 9.5 6.5Z"/>' ),
		'cake'        => array( 'Doğum günü', '<path d="M4.5 20.5h15v-7h-15v7Z"/><path d="M4.5 16c1.8 1.2 3.7 1.2 5 0 1.4 1.2 3.3 1.2 5 0 1.4 1.2 3.3 1.2 5 0"/><path d="M8 13.5v-3M12 13.5v-3M16 13.5v-3"/><path d="M8 8.5c-.8-.8-.5-1.8 0-2.7.5.9.8 1.9 0 2.7ZM12 8.5c-.8-.8-.5-1.8 0-2.7.5.9.8 1.9 0 2.7ZM16 8.5c-.8-.8-.5-1.8 0-2.7.5.9.8 1.9 0 2.7Z"/>' ),
		'tulip'       => array( 'Lale / Mevsim', '<path d="M8 5.5c1.2 1 2.2 1.6 4 1.6s2.8-.6 4-1.6c.5 4-1 6.5-4 6.5s-4.5-2.5-4-6.5Z"/><path d="M12 7.1 12 4"/><path d="M12 12v9"/><path d="M12 17.5c-1.5-2.4-3.6-3-5.3-2.6.9 2.2 3 3.2 5.3 2.6Z"/>' ),
		'succulent'   => array( 'Saksı bitkisi', '<path d="M6.5 14h11l-1.5 6.5h-8L6.5 14Z"/><path d="M12 14c0-3.5 0-6 0-9.5M12 14c-1.2-2.8-3.5-4.4-6-4.6.5 2.6 2.6 4.4 6 4.6ZM12 14c1.2-2.8 3.5-4.4 6-4.6-.5 2.6-2.6 4.4-6 4.6Z"/>' ),
		'balloon'     => array( 'Balon', '<path d="M12 15.5c-3.2 0-5.5-3-5.5-6.3A5.5 5.5 0 0 1 12 3.5a5.5 5.5 0 0 1 5.5 5.7c0 3.3-2.3 6.3-5.5 6.3Z"/><path d="m11 15.5.5 1.2h1l.5-1.2M12 16.7c-1 1.5 1 2.5 0 4.3"/>' ),
		'chocolate'   => array( 'Çikolata', '<rect x="5.5" y="3.5" width="13" height="17" rx="1"/><path d="M5.5 9.2h13M5.5 14.8h13M12 3.5v17"/>' ),
		'wreath'      => array( 'Çelenk / Taziye', '<circle cx="12" cy="12" r="7.5"/><circle cx="12" cy="12" r="4"/><path d="M12 4.5v3M4.5 12h3M16.5 12h3M12 16.5v3"/>' ),
		'crown'       => array( 'Koleksiyon', '<path d="M4 17.5 3 7.5l5 4 4-6.5 4 6.5 5-4-1 10H4Z"/><path d="M4 20.5h16"/>' ),
		'baby'        => array( 'Yeni doğan', '<circle cx="12" cy="8.5" r="4.5"/><path d="M10.3 8.3h.1M13.7 8.3h.1M10.5 10.5c.9.7 2.1.7 3 0"/><path d="M6 21c.5-3.7 3-5.5 6-5.5s5.5 1.8 6 5.5"/>' ),
		'briefcase'   => array( 'Kurumsal / Tebrik', '<rect x="3.5" y="7.5" width="17" height="12" rx="1"/><path d="M9 7.5V5h6v2.5M3.5 12.5h17"/>' ),
		// Sosyal.
		'instagram'   => array( 'Instagram', '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="4"/><path d="M17 7v.1"/>' ),
		'facebook'    => array( 'Facebook', '<path d="M14 8.5h2.5V5H14c-2.2 0-3.5 1.5-3.5 3.7V11H8v3.5h2.5V21H14v-6.5h2.5l.5-3.5h-3V9.2c0-.4.3-.7.7-.7Z"/>' ),
		'pinterest'   => array( 'Pinterest', '<circle cx="12" cy="12" r="9"/><path d="M10.5 20.5 12.6 11M9.3 13.8c-.6-3.6 1.5-6.3 4.4-6.3 2.3 0 3.8 1.6 3.8 3.8 0 2.7-1.5 4.8-3.6 4.8-1 0-1.9-.6-1.9-1.6"/>' ),
		'youtube'     => array( 'YouTube', '<rect x="2.5" y="5.5" width="19" height="13" rx="3.5"/><path d="m10 9.2 5 2.8-5 2.8V9.2Z"/>' ),
		'tiktok'      => array( 'TikTok', '<path d="M14 3.5v11.2a3.3 3.3 0 1 1-3.3-3.3"/><path d="M14 3.5c.4 2.6 2.2 4.4 5 4.6"/>' ),
		'whatsapp'    => array( 'WhatsApp', '<path d="M4 20l1.2-4A8.5 8.5 0 1 1 8.4 19L4 20Z"/><path d="M9 8.5c0 3.6 2.9 6.5 6.5 6.5l1-1.6-2-1-1 .9a4.4 4.4 0 0 1-2.4-2.4l.9-1-1-2L9 8.5Z"/>' ),
	);
	return $icons;
}

/**
 * İkon seçici için etiket listesi.
 *
 * @param bool $only_decor Sadece kategori/dekor ikonlarını döndür.
 * @return array<string,string>
 */
function df_icon_choices( $only_decor = false ) {
	$skip = array( 'menu', 'close', 'arrow-right', 'arrow-left', 'chevron-down', 'chevron-right', 'chevron-left', 'plus', 'minus', 'play', 'filter', 'zoom', 'edit', 'grid' );
	$out  = array( '' => '— İkon yok —' );
	foreach ( df_icon_library() as $key => $icon ) {
		if ( $only_decor && in_array( $key, $skip, true ) ) {
			continue;
		}
		$out[ $key ] = $icon[0];
	}
	return $out;
}

/**
 * SVG ikon döndürür.
 *
 * @param string $name  İkon adı.
 * @param array  $args  size, class, label.
 * @return string
 */
function df_icon( $name, $args = array() ) {
	$lib = df_icon_library();
	if ( empty( $lib[ $name ] ) ) {
		return '';
	}
	$args  = wp_parse_args(
		$args,
		array(
			'size'  => 22,
			'class' => '',
			'label' => '',
		)
	);
	$aria  = $args['label'] ? 'role="img" aria-label="' . esc_attr( $args['label'] ) . '"' : 'aria-hidden="true" focusable="false"';
	$class = trim( 'df-icon df-icon--' . $name . ' ' . $args['class'] );
	return sprintf(
		'<svg class="%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" %3$s>%4$s</svg>',
		esc_attr( $class ),
		(int) $args['size'],
		$aria,
		$lib[ $name ][1]
	);
}

/**
 * İkonu doğrudan yazdırır.
 *
 * @param string $name İkon.
 * @param array  $args Argümanlar.
 */
function df_the_icon( $name, $args = array() ) {
	echo df_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- statik SVG.
}
