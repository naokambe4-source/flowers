<?php
/**
 * Template Name: İletişim (Derin Flowers)
 *
 * Kısa adı "iletisim" olan sayfada otomatik kullanılır. Diğer sayfalarla aynı
 * başlık alanını ve beyaz zemini kullanır.
 * Bilgiler: Derin Flowers → Footer & İletişim. Form mesajları oradaki e-postaya gider.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/* Form gönderimi (yalnızca bu sayfada). */
$df_sent  = false;
$df_error = '';
$df_val   = array(
	'name'    => '',
	'phone'   => '',
	'message' => '',
);

if ( isset( $_POST['df_contact_nonce'] ) ) {
	$df_val['name']    = isset( $_POST['df_c_name'] ) ? sanitize_text_field( wp_unslash( $_POST['df_c_name'] ) ) : '';
	$df_val['phone']   = isset( $_POST['df_c_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['df_c_phone'] ) ) : '';
	$df_val['message'] = isset( $_POST['df_c_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['df_c_message'] ) ) : '';
	$df_ip             = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x';
	$df_rate           = 'df_contact_' . md5( $df_ip );

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['df_contact_nonce'] ) ), 'df_contact_form' ) ) {
		$df_error = 'Oturum süresi doldu, lütfen tekrar gönderin.';
	} elseif ( ! empty( $_POST['df_c_website'] ) ) {
		$df_sent = true; // Bot tuzağı.
	} elseif ( (int) get_transient( $df_rate ) >= 5 ) {
		$df_error = 'Çok fazla mesaj gönderildi. Lütfen biraz sonra tekrar deneyin ya da bizi arayın.';
	} elseif ( mb_strlen( $df_val['name'] ) < 2 || strlen( preg_replace( '/\D/', '', $df_val['phone'] ) ) < 10 || mb_strlen( $df_val['message'] ) < 5 ) {
		$df_error = 'Lütfen adınızı, telefon numaranızı ve mesajınızı yazın.';
	} else {
		$df_to = is_email( df_opt( 'contact_email' ) ) ? df_opt( 'contact_email' ) : get_option( 'admin_email' );
		$df_ok = wp_mail( $df_to, '[İletişim] ' . $df_val['name'], "Ad Soyad: {$df_val['name']}\nTelefon: {$df_val['phone']}\n\n{$df_val['message']}" );
		if ( $df_ok ) {
			set_transient( $df_rate, (int) get_transient( $df_rate ) + 1, HOUR_IN_SECONDS );
			$df_sent = true;
			$df_val  = array_fill_keys( array_keys( $df_val ), '' );
		} else {
			$df_error = 'Mesajınız gönderilemedi. Lütfen bizi telefonla ya da WhatsApp üzerinden arayın.';
		}
	}
}

wp_enqueue_style( 'df-pages', DF_URI . '/assets/css/pages.css', array( 'df-main' ), DF_VERSION );

get_header();

while ( have_posts() ) :
	the_post();
	$df_content = get_the_content();
	$df_extra   = trim( wp_strip_all_tags( $df_content ) ) && ! has_shortcode( $df_content, 'derin_iletisim' );

	get_template_part(
		'template-parts/content/page-header',
		null,
		array(
			'title'   => get_the_title(),
			'eyebrow' => 'Bize Ulaşın',
		)
	);
	?>
	<div class="df-container df-page df-contactp">
		<?php if ( $df_extra ) : ?>
			<div class="df-prose df-contactp__intro"><?php the_content(); ?></div>
		<?php endif; ?>

		<div class="df-contactp__grid">
			<ul class="df-contactp__info">
				<?php foreach ( array( 'contact_phone1', 'contact_phone2' ) as $df_k ) : ?>
					<?php if ( df_opt( $df_k ) ) : ?>
						<li><?php df_the_icon( 'phone', array( 'size' => 20 ) ); ?><a href="<?php echo esc_url( df_tel( df_opt( $df_k ) ) ); ?>"><?php echo esc_html( df_opt( $df_k ) ); ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if ( df_whatsapp_url() ) : ?>
					<li><?php df_the_icon( 'whatsapp', array( 'size' => 20 ) ); ?><a href="<?php echo esc_url( df_whatsapp_url( 'Merhaba, bilgi almak istiyorum.' ) ); ?>" target="_blank" rel="noopener">WhatsApp ile yazın</a></li>
				<?php endif; ?>
				<?php if ( df_opt( 'contact_email' ) ) : ?>
					<li><?php df_the_icon( 'mail', array( 'size' => 20 ) ); ?><a href="mailto:<?php echo esc_attr( antispambot( df_opt( 'contact_email' ) ) ); ?>"><?php echo esc_html( antispambot( df_opt( 'contact_email' ) ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( df_opt( 'contact_address' ) ) : ?>
					<li><?php df_the_icon( 'pin', array( 'size' => 20 ) ); ?>
						<span><?php echo df_nl2br( df_opt( 'contact_address' ) ); // phpcs:ignore ?>
						<?php if ( df_opt( 'contact_map' ) ) : ?>
							<br><a class="df-contactp__map" href="<?php echo esc_url( df_opt( 'contact_map' ) ); ?>" target="_blank" rel="noopener">Yol tarifi al</a>
						<?php endif; ?>
						</span>
					</li>
				<?php endif; ?>
				<?php if ( df_opt( 'contact_hours' ) ) : ?>
					<li><?php df_the_icon( 'clock', array( 'size' => 20 ) ); ?><span><?php echo df_nl2br( df_opt( 'contact_hours' ) ); // phpcs:ignore ?></span></li>
				<?php endif; ?>
			</ul>

			<form class="df-contactp__form" method="post" action="<?php echo esc_url( get_permalink() ); ?>#df-contact-form" id="df-contact-form">
				<h2>Mesaj Gönderin</h2>
				<?php if ( $df_sent ) : ?>
					<p class="df-contactp__alert is-ok" role="status">Teşekkürler! Mesajınız bize ulaştı, en kısa sürede dönüş yapacağız.</p>
				<?php elseif ( $df_error ) : ?>
					<p class="df-contactp__alert is-error" role="alert"><?php echo esc_html( $df_error ); ?></p>
				<?php endif; ?>
				<?php wp_nonce_field( 'df_contact_form', 'df_contact_nonce' ); ?>
				<p class="df-contactp__hp" aria-hidden="true"><input type="text" name="df_c_website" tabindex="-1" autocomplete="off"></p>
				<p><label for="df_c_name">Ad Soyad</label><input type="text" id="df_c_name" name="df_c_name" value="<?php echo esc_attr( $df_val['name'] ); ?>" autocomplete="name" required></p>
				<p><label for="df_c_phone">Telefon</label><input type="tel" id="df_c_phone" name="df_c_phone" value="<?php echo esc_attr( $df_val['phone'] ); ?>" autocomplete="tel" inputmode="tel" placeholder="05XX XXX XX XX" required></p>
				<p><label for="df_c_message">Mesajınız</label><textarea id="df_c_message" name="df_c_message" rows="4" required><?php echo esc_textarea( $df_val['message'] ); ?></textarea></p>
				<button type="submit" class="df-btn df-btn--solid"><span>Gönder</span></button>
			</form>
		</div>
	</div>
	<?php
endwhile;

get_footer();
