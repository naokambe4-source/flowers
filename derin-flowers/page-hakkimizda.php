<?php
/**
 * Template Name: Hakkımızda (Derin Flowers)
 *
 * Kısa adı "hakkimizda" olan sayfada otomatik kullanılır. İçerik Derin Flowers →
 * Hakkımızda & İletişim sekmesinden (ya da ön yüzde "Canlı Düzenle") gelir.
 * Sayfa editörü içeriği bilerek kullanılmaz: başka eklentilerin sayfaya kaydettiği
 * HTML (kendi header/footer'ıyla) tema düzenini bozamaz.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'df-pages', DF_URI . '/assets/css/pages.css', array( 'df-main' ), DF_VERSION );

get_header();

$df_image  = absint( df_opt( 'about_image' ) );
$df_values = array_filter(
	(array) df_opt( 'about_values', array() ),
	function ( $v ) {
		return ! empty( $v['title'] );
	}
);
$df_paras  = preg_split( '/\n\s*\n/', trim( (string) df_opt( 'about_text' ) ) );
?>
<header class="df-pagehead">
	<div class="df-container">
		<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) : ?>
			<?php woocommerce_breadcrumb(); ?>
		<?php endif; ?>
		<?php if ( df_opt( 'about_eyebrow' ) ) : ?>
			<p class="df-eyebrow"<?php echo df_e( 'about_eyebrow' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'about_eyebrow' ) ); ?></p>
		<?php endif; ?>
		<h1 class="df-pagehead__title"<?php echo df_e( 'about_title' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'about_title', get_the_title() ) ); ?></h1>
	</div>
</header>

<div class="df-container df-container--narrow df-page df-about">
	<?php if ( $df_image || df_live() ) : ?>
		<figure class="df-about__image"<?php echo df_i( 'about_image' ); // phpcs:ignore ?>>
			<?php echo df_image( $df_image, 'df-wide', array( 'loading' => 'eager', 'sizes' => '(max-width: 900px) 100vw, 820px', 'alt' => df_opt( 'about_title' ) ), 'Hakkımızda görseli' ); // phpcs:ignore ?>
		</figure>
	<?php endif; ?>

	<div class="df-prose df-page__content"<?php echo df_e( 'about_text' ); // phpcs:ignore ?>>
		<?php
		if ( df_live() ) {
			// Canlı düzenlemede tek blok olarak düzenlenir (paragraflar boş satırla ayrılır).
			echo esc_html( trim( (string) df_opt( 'about_text' ) ) );
		} else {
			foreach ( $df_paras as $df_p ) {
				if ( trim( $df_p ) ) {
					echo '<p>' . df_nl2br( $df_p ) . '</p>'; // phpcs:ignore
				}
			}
		}
		?>
	</div>

	<?php if ( $df_values ) : ?>
		<ul class="df-about__values">
			<?php foreach ( $df_values as $df_i => $df_v ) : ?>
				<li>
					<?php if ( ! empty( $df_v['icon'] ) ) : ?>
						<?php df_the_icon( $df_v['icon'], array( 'size' => 24 ) ); ?>
					<?php endif; ?>
					<strong<?php echo df_e( 'about_values.' . $df_i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $df_v['title'] ); ?></strong>
					<span<?php echo df_e( 'about_values.' . $df_i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( isset( $df_v['text'] ) ? $df_v['text'] : '' ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
<?php
get_footer();
