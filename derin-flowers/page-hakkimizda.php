<?php
/**
 * Template Name: Hakkımızda (Derin Flowers)
 *
 * Kısa adı "hakkimizda" olan sayfada otomatik kullanılır; başka bir sayfaya
 * Sayfa Özellikleri → Şablon menüsünden de atanabilir.
 *
 * Metinler: sayfa düzenleyicisine 300 karakterden uzun kendi metninizi yazarsanız
 * "Hikayemiz" bölümünde o gösterilir; yazmazsanız aşağıdaki hazır metin kullanılır.
 * Görseller: sayfanın öne çıkan görseli (yoksa panel → Marka hikayesi görselleri).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'df-pages', DF_URI . '/assets/css/pages.css', array( 'df-main' ), DF_VERSION );

get_header();

while ( have_posts() ) :
	the_post();

	$df_hero_img  = has_post_thumbnail() ? get_post_thumbnail_id() : absint( df_opt( 'story_image' ) );
	$df_side_img  = absint( df_opt( 'story_image2' ) );
	$df_own_text  = trim( wp_strip_all_tags( get_the_content() ) );
	$df_use_own   = mb_strlen( $df_own_text ) > 300;
	$df_shop_url  = df_wc() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$df_contact   = get_page_by_path( 'iletisim' ) ? get_permalink( get_page_by_path( 'iletisim' ) ) : home_url( '/' );
	?>

	<section class="df-about-hero">
		<div class="df-container df-about-hero__grid">
			<div class="df-about-hero__text">
				<p class="df-eyebrow">Hikayemiz</p>
				<h1 class="df-about-hero__title">Çiçekten<br>Daha Fazlası</h1>
				<p class="df-about-hero__lead">Derin Flowers, İzmir Alsancak'taki atölyesinde her gün yeniden başlayan bir hikaye. Bir buketi hazırlarken yalnızca çiçekleri değil; bir özrü, bir kutlamayı, bir özlemi ya da hiç söylenememiş bir teşekkürü de özenle bir araya getiriyoruz.</p>
			</div>
			<figure class="df-about-hero__media">
				<?php echo df_image( $df_hero_img, 'df-portrait', array( 'loading' => 'eager', 'sizes' => '(max-width: 900px) 100vw, 45vw', 'alt' => get_the_title() ), 'Sayfaya öne çıkan görsel ekleyin' ); // phpcs:ignore ?>
			</figure>
		</div>
	</section>

	<section class="df-about-story">
		<div class="df-container df-about-story__grid">
			<aside class="df-about-story__side">
				<p class="df-eyebrow">Derin Flowers</p>
				<h2 class="df-about-story__title">Her çiçeğin söyleyecek bir sözü var.</h2>
			</aside>
			<div class="df-about-story__body df-prose">
				<?php if ( $df_use_own ) : ?>
					<?php the_content(); ?>
				<?php else : ?>
					<p class="df-about-dropcap">Derin Flowers'ın hikayesi, çiçeğe duyulan sade bir sevgiyle başladı. Bir çiçekçinin işinin yalnızca çiçek satmak olmadığına, insanların hayatlarındaki en özel anlara eşlik etmek olduğuna inandık. Bu yüzden atölyemize giren her sipariş, bizim için bir ürün değil; birinin hatırlanmak, sevildiğini hissetmek ya da mutluluğunu paylaşmak istediği bir andır.</p>
					<p>Her sabah güne, çiçeklerimizi tek tek seçerek başlıyoruz. Güllerin taç yapraklarının sıkılığını, orkidelerin dallarının sağlamlığını, mevsim çiçeklerinin tazeliğini kendi gözümüzle kontrol ediyoruz. Sizin için hazırladığımız tasarımda kullanılan her dal, o gün atölyemize gelen en güzel çiçekler arasından seçilir. Tazelik bizim için bir vaat değil, çalışma biçimimizdir.</p>
					<p>Tasarımlarımızda gösterişten çok zarafeti, kalabalıktan çok uyumu önemsiyoruz. Soft tonların, doğal yeşilliklerin ve mevsimin sunduğu dokuların bir araya geldiği; ilk bakışta sade, yakından bakıldıkça derinleşen buketler hazırlamayı seviyoruz. Her tasarım, floristlerimizin elinde tek tek şekillenir; iki buketimiz birbirinin tıpatıp aynısı olmaz.</p>
					<p>Doğum günlerinden yıl dönümlerine, söz ve nişan törenlerinden yeni iş tebriklerine, geçmiş olsun dileklerinden sessiz bir özre kadar pek çok anı sizinle birlikte paylaştık. Her birinde aynı soruyu sorduk: "Bu çiçek, alan kişiye ne hissettirmeli?" Tasarımı, rengi, ambalajı ve kartına yazılacak notu bu sorunun cevabına göre hazırlıyoruz.</p>
					<p>Siparişinizin hazırlanmasından teslimine kadar her adımı takip ediyoruz. Çiçekleriniz, seçtiğiniz gün ve saat aralığında, özenle paketlenmiş olarak sevdiklerinize ulaştırılır. Aklınıza takılan her konuda, telefonla ya da WhatsApp üzerinden bize doğrudan ulaşabilir, siparişinizin durumunu dilediğiniz an sorgulayabilirsiniz.</p>
					<p>Derin Flowers'ta amacımız basit: Sevdiklerinize söylemek istediklerinizi, en güzel haliyle çiçeklere emanet etmek. Bu yolculukta bizi tercih ettiğiniz için teşekkür ederiz.</p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="df-about-quote">
		<div class="df-container df-container--narrow">
			<blockquote>
				<p>"Bir buket, söylenemeyenleri en zarif haliyle anlatır. Biz o cümleyi çiçeklerle kuruyoruz."</p>
				<cite>Derin Flowers Atölyesi</cite>
			</blockquote>
		</div>
	</section>

	<section class="df-section df-about-values">
		<div class="df-container">
			<?php
			df_section_head(
				array(
					'eyebrow' => 'Değerlerimiz',
					'title'   => 'Bizi Farklı Kılanlar',
				)
			);
			?>
			<div class="df-about-values__grid">
				<?php
				$df_values = array(
					array( 'leaf', 'Mevsimin En Tazesi', 'Çiçeklerimizi her gün yeniden seçer, yalnızca o gün en taze olanlarla çalışırız.' ),
					array( 'hand', 'El İşçiliği', 'Her tasarım floristlerimizin elinde tek tek şekillenir; seri üretim buket hazırlamayız.' ),
					array( 'truck', 'Zamanında Teslimat', 'Seçtiğiniz gün ve saat aralığında, İzmir\'in seçili bölgelerine özenle teslim ederiz.' ),
					array( 'note', 'Kişiye Özel Dokunuş', 'Her siparişe ücretsiz not kartı ekler, sözlerinizi en zarif haliyle iletiriz.' ),
				);
				foreach ( $df_values as $df_v ) :
					?>
					<div class="df-about-value">
						<span class="df-about-value__icon"><?php df_the_icon( $df_v[0], array( 'size' => 30 ) ); ?></span>
						<h3><?php echo esc_html( $df_v[1] ); ?></h3>
						<p><?php echo esc_html( $df_v[2] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="df-section df-about-process">
		<div class="df-container">
			<?php
			df_section_head(
				array(
					'eyebrow' => 'Nasıl Çalışıyoruz',
					'title'   => 'Bir Siparişin Yolculuğu',
				)
			);
			?>
			<ol class="df-about-steps">
				<?php
				$df_steps = array(
					array( 'Seçim', 'Siparişiniz geldiğinde, tasarıma uygun en taze çiçekleri o günün seçkisinden ayırırız.' ),
					array( 'Tasarım', 'Floristimiz buketi renk ve doku uyumunu gözeterek elde hazırlar.' ),
					array( 'Not & Paket', 'Kartınıza notunuzu yazar, tasarımı özel ambalajıyla tamamlarız.' ),
					array( 'Teslimat', 'Çiçekleriniz seçtiğiniz saat aralığında sevdiklerinize ulaştırılır.' ),
				);
				foreach ( $df_steps as $df_i => $df_s ) :
					?>
					<li>
						<span class="df-about-steps__num"><?php echo esc_html( sprintf( '%02d', $df_i + 1 ) ); ?></span>
						<h3><?php echo esc_html( $df_s[0] ); ?></h3>
						<p><?php echo esc_html( $df_s[1] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<section class="df-section df-about-studio">
		<div class="df-container df-about-studio__grid<?php echo $df_side_img ? '' : ' no-image'; ?>">
			<?php if ( $df_side_img ) : ?>
				<figure class="df-about-studio__media">
					<?php echo df_image( $df_side_img, 'df-portrait', array( 'sizes' => '(max-width: 900px) 100vw, 40vw', 'alt' => 'Derin Flowers atölyesi' ) ); // phpcs:ignore ?>
				</figure>
			<?php endif; ?>
			<div class="df-about-studio__text">
				<p class="df-eyebrow">Atölyemiz</p>
				<h2>Alsancak'ta, Çiçeklerin Arasında</h2>
				<p>Atölyemize uğrayarak tasarımlarımızı yakından görebilir, özel günleriniz için floristlerimizle birlikte size özel bir buket planlayabilirsiniz. Siparişinizi mağazadan teslim almayı da seçebilirsiniz.</p>
				<?php if ( df_opt( 'contact_address' ) ) : ?>
					<p class="df-about-studio__info"><?php df_the_icon( 'pin', array( 'size' => 18 ) ); ?><span><?php echo df_nl2br( df_opt( 'contact_address' ) ); // phpcs:ignore ?></span></p>
				<?php endif; ?>
				<?php if ( df_opt( 'contact_hours' ) ) : ?>
					<p class="df-about-studio__info"><?php df_the_icon( 'clock', array( 'size' => 18 ) ); ?><span><?php echo df_nl2br( df_opt( 'contact_hours' ) ); // phpcs:ignore ?></span></p>
				<?php endif; ?>
				<a class="df-link-arrow df-link-arrow--lg" href="<?php echo esc_url( $df_contact ); ?>">Bize Ulaşın<?php df_the_icon( 'arrow-right', array( 'size' => 20 ) ); ?></a>
			</div>
		</div>
	</section>

	<section class="df-about-cta">
		<div class="df-container df-about-cta__inner">
			<h2>Sevdiklerinize söylemek istediklerinizi çiçeklerle anlatın.</h2>
			<div class="df-about-cta__actions">
				<?php echo df_button( 'Koleksiyonu Keşfet', $df_shop_url, 'solid' ); // phpcs:ignore ?>
				<?php echo df_button( 'İletişime Geç', $df_contact, 'outline' ); // phpcs:ignore ?>
			</div>
		</div>
	</section>

	<?php
endwhile;

get_footer();
