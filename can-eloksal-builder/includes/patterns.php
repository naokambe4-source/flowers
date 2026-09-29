<?php
/**
 * Hazır sayfa düzenleri (blok desenleri). Editörde "+" → Desenler → Can Eloksal.
 *
 * @package CanEloksalBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Düzen tanımları.
 *
 * @return array<string,array{title:string,description:string,content:callable}>
 */
function ceb_layouts() {
	return array(
		'home'      => array(
			'title'       => 'Ana Sayfa (tüm bölümler)',
			'description' => 'Ana sayfanın tüm bölümleri; Tema Ayarları\'ndaki güncel metinlerle.',
			'content'     => 'ceb_home_markup',
		),
		'corporate' => array(
			'title'       => 'Kurumsal Açılış Sayfası',
			'description' => 'Hero, güven şeridi, hizmetler, görsel+metin, özellik kartları ve CTA.',
			'content'     => static fn() => ceb_block( 'page-hero', array( 'title' => 'Alüminyum yüzeylerde profesyonel çözümler', 'eyebrow' => 'Can Eloksal', 'subtitle' => 'Savunma ve makine sanayine yönelik eloksal ve kimyasal kaplama uygulamaları.', 'btn1_label' => 'Teklif Al', 'btn1_url' => '/teklif-al/', 'btn2_label' => 'Hizmetler', 'btn2_url' => '/hizmetler/' ) )
				. ceb_block( 'trust' ) . ceb_block( 'services', array( 'layout' => 'grid', 'services_count' => 6 ) ) . ceb_block( 'about' ) . ceb_block( 'features' ) . ceb_block( 'cta' ),
		),
		'service'   => array(
			'title'       => 'Hizmet / Ürün Tanıtım Sayfası',
			'description' => 'Hero, açıklama bölümü, özellik listesi, proses ve teklif formu.',
			'content'     => static fn() => ceb_block( 'page-hero', array( 'title' => 'Hizmet adı', 'eyebrow' => 'Hizmet', 'subtitle' => 'Kısa açıklama', 'btn1_label' => 'Teklif Al', 'btn1_url' => '#form' ) )
				. ceb_block( 'section', array( 'width' => 'narrow' ), ceb_block( 'heading', array( 'eyebrow' => 'Genel bakış', 'title' => 'Hizmet hakkında' ) ) . ceb_core( 'p', 'Hizmetin ne olduğunu, hangi parçalarda kullanıldığını ve sağladığı faydaları anlatın.' ) . ceb_core( 'h3', 'Avantajlar' ) . ceb_core( 'ul', array( 'Korozyon direnci', 'Dayanıklı yüzey', 'Estetik görünüm' ) ) )
				. ceb_block( 'process' ) . ceb_block( 'quote-form' ),
		),
		'contact'   => array(
			'title'       => 'İletişim Sayfası',
			'description' => 'Hero, iletişim kartları, form ve harita.',
			'content'     => static fn() => ceb_block( 'page-hero', array( 'title' => 'İletişim', 'subtitle' => 'Parçalarınız ve yüzey işlem ihtiyaçlarınız için bize ulaşın.', 'size' => 'md' ) )
				. ceb_block( 'contact' ) . ceb_block( 'contact-form' ) . ceb_block( 'map' ),
		),
		'campaign'  => array(
			'title'       => 'Duyuru / Kampanya Sayfası',
			'description' => 'Hero, serbest içerik bölümü, koyu vurgu bölümü ve CTA bandı.',
			'content'     => static fn() => ceb_block( 'page-hero', array( 'title' => 'Duyuru başlığı', 'eyebrow' => 'Duyuru', 'subtitle' => 'Kısa açıklama' ) )
				. ceb_block( 'section', array(), ceb_block( 'heading', array( 'title' => 'Detaylar', 'text' => 'Duyurunun ayrıntılarını buraya yazın.' ) ) . ceb_core( 'p', 'İçerik metni.' ) )
				. ceb_block( 'section', array( 'bg' => 'dark' ), ceb_block( 'heading', array( 'eyebrow' => 'Öne çıkan', 'title' => 'Vurgulamak istediğiniz mesaj', 'align' => 'center' ) ) )
				. ceb_block( 'band' ),
		),
		'blank'     => array(
			'title'       => 'Boş Bölümlü Sayfa',
			'description' => 'Hero + tek bir serbest bölüm.',
			'content'     => static fn() => ceb_block( 'page-hero', array( 'size' => 'md' ) ) . ceb_block( 'section', array(), ceb_core( 'p', 'İçeriğinizi buraya ekleyin.' ) ),
		),
	);
}

add_action( 'init', 'ceb_register_patterns', 30 );
/**
 * Desenleri kaydeder.
 */
function ceb_register_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}
	register_block_pattern_category( 'can-eloksal', array( 'label' => 'Can Eloksal' ) );
	foreach ( ceb_layouts() as $slug => $layout ) {
		register_block_pattern(
			'can-eloksal/' . $slug,
			array(
				'title'       => $layout['title'],
				'description' => $layout['description'],
				'categories'  => array( 'can-eloksal' ),
				'blockTypes'  => array( 'core/post-content' ),
				'content'     => call_user_func( $layout['content'] ),
			)
		);
	}
}
