<?php
/**
 * İlçe sayfası (/cicek-siparisi/bornova/) ve ilçe listesi (/cicek-siparisi/).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$df_cur   = df_loc_current();
$df_items = df_loc_items();
$df_city  = df_opt( 'df_default_city', 'İzmir' );
$df_meta  = df_loc_meta();
$df_wa    = df_whatsapp_url();
$df_shop  = df_wc() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

get_header();
?>
<div class="df-loc">
	<header class="df-loc__hero">
		<div class="df-container">
			<nav class="df-loc__crumbs" aria-label="Sayfa yolu">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Ana Sayfa</a><span aria-hidden="true">/</span>
				<?php if ( '__index' === $df_cur ) : ?>
					<span><?php echo esc_html( $df_city ); ?> Çiçek Siparişi</span>
				<?php else : ?>
					<a href="<?php echo esc_url( df_loc_url() ); ?>"><?php echo esc_html( $df_city ); ?> Çiçek Siparişi</a><span aria-hidden="true">/</span><span><?php echo esc_html( $df_items[ $df_cur ]['name'] ); ?></span>
				<?php endif; ?>
			</nav>
			<h1 class="df-loc__title"><?php echo esc_html( $df_meta['title'] ); ?></h1>
			<p class="df-loc__lead"><?php echo esc_html( $df_meta['desc'] ); ?></p>
			<div class="df-loc__cta">
				<a class="df-btn df-btn--solid" href="<?php echo esc_url( '__index' === $df_cur ? $df_shop : '#df-loc-products' ); ?>">Çiçekleri İncele</a>
				<?php if ( $df_wa ) : ?>
					<a class="df-btn df-btn--outline" href="<?php echo esc_url( $df_wa ); ?>" target="_blank" rel="noopener">WhatsApp ile Sipariş</a>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<?php if ( '__index' === $df_cur ) : ?>
		<section class="df-loc__section">
			<div class="df-container">
				<h2 class="df-loc__h2">Çiçek gönderilecek ilçeyi seçin</h2>
				<ul class="df-loc__list">
					<?php foreach ( $df_items as $df_slug => $df_loc ) : ?>
						<li><a href="<?php echo esc_url( df_loc_url( $df_slug ) ); ?>"><strong><?php echo esc_html( $df_loc['name'] ); ?></strong><span><?php echo esc_html( df_loc_vars( df_opt( 'loc_title', '{ilce} Çiçek Siparişi' ), $df_loc ) ); ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php else : ?>
		<?php
		$df_loc   = $df_items[ $df_cur ];
		$df_zones = df_loc_zones( $df_loc );
		$df_slots = function_exists( 'df_delivery_slots' ) ? df_delivery_slots() : array();
		?>
		<section class="df-loc__facts">
			<div class="df-container df-loc__facts-grid">
				<div class="df-loc__fact"><?php df_the_icon( 'clock', array( 'size' => 24 ) ); ?><strong>Aynı gün teslimat</strong><span>Saat <?php echo esc_html( df_opt( 'df_cutoff', '16:00' ) ); ?>'a kadar verilen siparişler</span></div>
				<?php if ( null !== $df_zones['min'] ) : ?>
					<div class="df-loc__fact"><?php df_the_icon( 'truck', array( 'size' => 24 ) ); ?><strong><?php echo esc_html( $df_zones['min'] > 0 ? html_entity_decode( wp_strip_all_tags( wc_price( $df_zones['min'] ) ), ENT_QUOTES, 'UTF-8' ) . "'den başlayan" : 'Ücretsiz' ); ?></strong><span><?php echo esc_html( $df_loc['name'] ); ?> teslimat ücreti</span></div>
				<?php endif; ?>
				<?php if ( $df_slots ) : ?>
					<div class="df-loc__fact"><?php df_the_icon( 'calendar', array( 'size' => 24 ) ); ?><strong><?php echo count( $df_slots ); ?> saat aralığı</strong><span><?php echo esc_html( implode( ' · ', wp_list_pluck( $df_slots, 'label' ) ) ); ?></span></div>
				<?php endif; ?>
				<div class="df-loc__fact"><?php df_the_icon( 'note', array( 'size' => 24 ) ); ?><strong>Ücretsiz not kartı</strong><span>Kişiye özel mesajınızla</span></div>
			</div>
		</section>

		<?php
		$df_cat      = absint( df_opt( 'loc_cat' ) );
		$df_products = df_wc() ? df_query_products( $df_cat ? 'category' : 'bestsellers', array( 'cat' => $df_cat, 'limit' => max( 4, min( 24, (int) df_opt( 'loc_count', 8 ) ) ) ) ) : array();
		?>
		<?php if ( $df_products ) : ?>
			<section class="df-loc__section df-vitrin" id="df-loc-products">
				<div class="df-container">
					<header class="df-vitrin__head">
						<h2><?php echo esc_html( $df_loc['name'] ); ?>'ye gönderebileceğiniz çiçekler</h2>
						<a class="df-link" href="<?php echo esc_url( $df_shop ); ?>"><span>Tüm çiçekler</span><?php df_the_icon( 'arrow-right', array( 'size' => 14 ) ); ?></a>
					</header>
					<div class="df-vitrin__grid">
						<?php foreach ( $df_products as $df_p ) : ?>
							<?php df_product_card( $df_p, array( 'variant' => 'vitrin' ) ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="df-loc__section df-loc__text">
			<div class="df-container df-container--narrow">
				<h2 class="df-loc__h2"><?php echo esc_html( $df_loc['name'] ); ?>'de çiçek göndermek</h2>
				<div class="df-prose"><?php echo wp_kses_post( wpautop( esc_html( df_loc_vars( '' !== $df_loc['text'] ? $df_loc['text'] : df_opt( 'loc_intro' ), $df_loc ) ) ) ); ?></div>
				<?php if ( count( $df_zones['zones'] ) > 1 ) : ?>
					<p class="df-loc__zones"><strong>Teslimat yaptığımız bölgeler:</strong> <?php echo esc_html( implode( ', ', array_map( 'df_loc_title_case', $df_zones['zones'] ) ) ); ?></p>
				<?php endif; ?>
			</div>
		</section>

		<section class="df-loc__section df-loc__faq">
			<div class="df-container df-container--narrow">
				<h2 class="df-loc__h2">Sıkça sorulanlar</h2>
				<?php foreach ( df_loc_faq( $df_loc ) as $df_qa ) : ?>
					<details class="df-loc__qa">
						<summary><?php echo esc_html( $df_qa[0] ); ?></summary>
						<p><?php echo esc_html( $df_qa[1] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</section>

		<?php $df_others = array_diff_key( $df_items, array( $df_cur => 1 ) ); ?>
		<?php if ( $df_others ) : ?>
			<section class="df-loc__section df-loc__others">
				<div class="df-container">
					<h2 class="df-loc__h2">Diğer ilçeler</h2>
					<ul class="df-loc__chips">
						<?php foreach ( $df_others as $df_slug => $df_o ) : ?>
							<li><a href="<?php echo esc_url( df_loc_url( $df_slug ) ); ?>"><?php echo esc_html( df_loc_vars( df_opt( 'loc_title', '{ilce} Çiçek Siparişi' ), $df_o ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
get_footer();
