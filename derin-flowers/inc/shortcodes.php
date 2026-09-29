<?php
/**
 * Genel kısa kodlar: iletişim kartı, SSS listesi.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * SSS listesi + yapılandırılmış veri.
 *
 * @param array $items q/a.
 */
function df_faq_list( $items ) {
	$schema = array();
	echo '<div class="df-faq">';
	foreach ( $items as $i => $row ) {
		if ( empty( $row['q'] ) || empty( $row['a'] ) ) {
			continue;
		}
		printf(
			'<details class="df-faq__item"%s><summary><span>%s</span>%s</summary><div class="df-faq__a">%s</div></details>',
			0 === $i ? ' open' : '',
			esc_html( $row['q'] ),
			df_icon( 'plus', array( 'size' => 18, 'class' => 'df-faq__sign' ) ), // phpcs:ignore
			wp_kses_post( wpautop( esc_html( $row['a'] ) ) )
		);
		$schema[] = array(
			'@type'          => 'Question',
			'name'           => $row['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $row['a'],
			),
		);
	}
	echo '</div>';
	if ( $schema ) {
		echo '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $schema,
			),
			JSON_UNESCAPED_UNICODE
		) . '</script>';
	}
}

/**
 * [derin_iletisim] — iletişim kartı.
 *
 * @return string
 */
function df_contact_shortcode() {
	ob_start();
	?>
	<div class="df-contact">
		<div class="df-contact__col">
			<h3>Bize ulaşın</h3>
			<?php foreach ( array( 'contact_phone1', 'contact_phone2' ) as $k ) : ?>
				<?php if ( df_opt( $k ) ) : ?>
					<p><?php df_the_icon( 'phone', array( 'size' => 18 ) ); ?><a href="<?php echo esc_url( df_tel( df_opt( $k ) ) ); ?>"><?php echo esc_html( df_opt( $k ) ); ?></a></p>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ( df_opt( 'contact_email' ) ) : ?>
				<p><?php df_the_icon( 'mail', array( 'size' => 18 ) ); ?><a href="mailto:<?php echo esc_attr( antispambot( df_opt( 'contact_email' ) ) ); ?>"><?php echo esc_html( antispambot( df_opt( 'contact_email' ) ) ); ?></a></p>
			<?php endif; ?>
			<?php if ( df_whatsapp_url() ) : ?>
				<p><?php df_the_icon( 'whatsapp', array( 'size' => 18 ) ); ?><a href="<?php echo esc_url( df_whatsapp_url() ); ?>" target="_blank" rel="noopener">WhatsApp ile yazın</a></p>
			<?php endif; ?>
		</div>
		<div class="df-contact__col">
			<h3>Atölye & Mağaza</h3>
			<p><?php df_the_icon( 'pin', array( 'size' => 18 ) ); ?><span><?php echo df_nl2br( df_opt( 'contact_address' ) ); // phpcs:ignore ?></span></p>
			<p><?php df_the_icon( 'clock', array( 'size' => 18 ) ); ?><span><?php echo df_nl2br( df_opt( 'contact_hours' ) ); // phpcs:ignore ?></span></p>
			<?php if ( df_opt( 'contact_map' ) ) : ?>
				<p><a class="df-link-arrow" href="<?php echo esc_url( df_opt( 'contact_map' ) ); ?>" target="_blank" rel="noopener">Yol tarifi al<?php df_the_icon( 'arrow-right', array( 'size' => 18 ) ); ?></a></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'derin_iletisim', 'df_contact_shortcode' );

/**
 * [derin_sss] — genel SSS.
 *
 * @return string
 */
function df_faq_shortcode() {
	ob_start();
	df_faq_list( (array) df_opt( 'faq_items', array() ) );
	return ob_get_clean();
}
add_shortcode( 'derin_sss', 'df_faq_shortcode' );
