<?php
/**
 * Template Name: Hakkımızda (Derin Flowers)
 *
 * Kısa adı "hakkimizda" olan sayfada otomatik kullanılır. Diğer sayfalarla aynı
 * başlık alanını ve beyaz zemini kullanır.
 * Metin: sayfa düzenleyicisine kendi metninizi yazarsanız o gösterilir; boşsa kısa hazır metin.
 * Görsel: sayfanın öne çıkan görseli (opsiyonel).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'df-pages', DF_URI . '/assets/css/pages.css', array( 'df-main' ), DF_VERSION );

get_header();

while ( have_posts() ) :
	the_post();
	$df_has_text = '' !== trim( wp_strip_all_tags( get_the_content() ) ) && mb_strlen( trim( wp_strip_all_tags( get_the_content() ) ) ) > 120;

	get_template_part(
		'template-parts/content/page-header',
		null,
		array(
			'title'   => get_the_title(),
			'eyebrow' => 'Hikayemiz',
		)
	);
	?>
	<div class="df-container df-container--narrow df-page df-about">
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="df-about__image">
				<?php the_post_thumbnail( 'df-wide', array( 'sizes' => '(max-width: 900px) 100vw, 820px' ) ); ?>
			</figure>
		<?php endif; ?>

		<div class="df-prose df-page__content">
			<?php if ( $df_has_text ) : ?>
				<?php the_content(); ?>
			<?php else : ?>
				<p>Derin Flowers, İzmir Alsancak'taki atölyesinde her gün taze seçilen çiçeklerle özel anlarınız için tasarımlar hazırlar.</p>
				<p>Her siparişi floristlerimiz elde hazırlar; renk uyumuna, tazeliğe ve sunuma aynı özeni gösteririz. Çiçekleriniz, seçtiğiniz gün ve saatte sevdiklerinize ulaştırılır.</p>
				<p>Amacımız basit: Söylemek istediklerinizi en güzel haliyle çiçeklere emanet etmek.</p>
			<?php endif; ?>
		</div>

		<ul class="df-about__values">
			<li><?php df_the_icon( 'leaf', array( 'size' => 24 ) ); ?><strong>Taze Çiçek</strong><span>Her gün yeniden seçilir</span></li>
			<li><?php df_the_icon( 'hand', array( 'size' => 24 ) ); ?><strong>El Yapımı</strong><span>Her tasarım özel hazırlanır</span></li>
			<li><?php df_the_icon( 'truck', array( 'size' => 24 ) ); ?><strong>Zamanında Teslimat</strong><span>İzmir'in seçili bölgelerine</span></li>
		</ul>
	</div>
	<?php
endwhile;

get_footer();
