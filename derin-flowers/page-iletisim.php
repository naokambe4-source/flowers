<?php
/**
 * Template Name: İletişim (Derin Flowers)
 *
 * Kısa adı "iletisim" olan sayfada otomatik kullanılır; başka bir sayfaya
 * Sayfa Özellikleri → Şablon menüsünden de atanabilir.
 * Telefon, e-posta, adres, saat, WhatsApp ve harita: Derin Flowers → Footer & İletişim.
 * İletişim formundan gelen mesajlar panelde yazan e-posta adresine gönderilir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * İletişim formu gönderimi (yalnızca bu sayfada çalışır).
 * ---------------------------------------------------------------------- */
$df_form_state = '';
$df_form_error = '';
$df_form       = array(
	'name'    => '',
	'phone'   => '',
	'email'   => '',
	'subject' => '',
	'message' => '',
);
$df_subjects   = array( 'Sipariş hakkında', 'Özel tasarım talebi', 'Söz & nişan organizasyonu', 'Kurumsal sipariş', 'Diğer' );

if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) && isset( $_POST['df_contact_nonce'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	foreach ( array( 'name', 'phone', 'email', 'subject' ) as $df_k ) {
		$df_form[ $df_k ] = isset( $_POST[ 'df_c_' . $df_k ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'df_c_' . $df_k ] ) ) : '';
	}
	$df_form['message'] = isset( $_POST['df_c_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['df_c_message'] ) ) : '';
	$df_ip              = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x';
	$df_rate_key        = 'df_contact_' . md5( $df_ip );

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['df_contact_nonce'] ) ), 'df_contact_form' ) ) {
		$df_form_error = 'Oturum süresi doldu, lütfen formu tekrar gönderin.';
	} elseif ( ! empty( $_POST['df_c_website'] ) ) {
		$df_form_state = 'sent'; // Bot tuzağı: sessizce yok say.
	} elseif ( (int) get_transient( $df_rate_key ) >= 5 ) {
		$df_form_error = 'Kısa sürede çok fazla mesaj gönderildi. Lütfen biraz sonra tekrar deneyin ya da bizi arayın.';
	} elseif ( mb_strlen( $df_form['name'] ) < 2 || mb_strlen( $df_form['message'] ) < 10 ) {
		$df_form_error = 'Lütfen adınızı ve en az birkaç kelimelik mesajınızı yazın.';
	} elseif ( ! is_email( $df_form['email'] ) && strlen( preg_replace( '/\D/', '', $df_form['phone'] ) ) < 10 ) {
		$df_form_error = 'Size dönebilmemiz için geçerli bir e-posta adresi ya da telefon numarası yazın.';
	} elseif ( empty( $_POST['df_c_kvkk'] ) ) {
		$df_form_error = 'Lütfen KVKK aydınlatma metnini onaylayın.';
	} else {
		$df_to   = is_email( df_opt( 'contact_email' ) ) ? df_opt( 'contact_email' ) : get_option( 'admin_email' );
		$df_body = "Ad Soyad: {$df_form['name']}\nTelefon: {$df_form['phone']}\nE-posta: {$df_form['email']}\nKonu: {$df_form['subject']}\n\n{$df_form['message']}\n\n— " . home_url( '/' );
		$df_head = is_email( $df_form['email'] ) ? array( 'Reply-To: ' . $df_form['name'] . ' <' . $df_form['email'] . '>' ) : array();
		if ( wp_mail( $df_to, '[İletişim] ' . ( $df_form['subject'] ? $df_form['subject'] : 'Yeni mesaj' ) . ' — ' . $df_form['name'], $df_body, $df_head ) ) {
			set_transient( $df_rate_key, (int) get_transient( $df_rate_key ) + 1, HOUR_IN_SECONDS );
			$df_form_state = 'sent';
			$df_form       = array_fill_keys( array_keys( $df_form ), '' );
		} else {
			$df_form_error = 'Mesajınız şu anda gönderilemedi. Lütfen bizi telefon ya da WhatsApp ile arayın.';
		}
	}
}

wp_enqueue_style( 'df-pages', DF_URI . '/assets/css/pages.css', array( 'df-main' ), DF_VERSION );

get_header();

$df_phone1  = df_opt( 'contact_phone1' );
$df_phone2  = df_opt( 'contact_phone2' );
$df_email   = df_opt( 'contact_email' );
$df_wa      = df_whatsapp_url( 'Merhaba, bilgi almak istiyorum.' );
$df_address = df_opt( 'contact_address' );
$df_map_q   = trim( preg_replace( '/\s+/', ' ', (string) $df_address ) );
$df_track   = df_page_url( 'track_page', 'siparis-takip' );

while ( have_posts() ) :
	the_post();
	?>

	<section class="df-contact-hero">
		<div class="df-container">
			<p class="df-eyebrow">İletişim</p>
			<h1 class="df-contact-hero__title">Size Nasıl Yardımcı<br>Olabiliriz?</h1>
			<p class="df-contact-hero__lead">Sipariş, özel tasarım ya da teslimatla ilgili tüm sorularınız için buradayız. En hızlı yanıt için bizi arayabilir veya WhatsApp'tan yazabilirsiniz.</p>
			<?php if ( trim( wp_strip_all_tags( get_the_content() ) ) && ! has_shortcode( get_the_content(), 'derin_iletisim' ) ) : ?>
				<div class="df-contact-hero__extra df-prose"><?php the_content(); ?></div>
			<?php endif; ?>
		</div>
	</section>

	<section class="df-contact-cards">
		<div class="df-container df-contact-cards__grid">
			<?php if ( $df_phone1 ) : ?>
				<a class="df-contact-card" href="<?php echo esc_url( df_tel( $df_phone1 ) ); ?>">
					<span class="df-contact-card__icon"><?php df_the_icon( 'phone', array( 'size' => 26 ) ); ?></span>
					<span class="df-contact-card__label">Bizi Arayın</span>
					<strong><?php echo esc_html( $df_phone1 ); ?></strong>
					<?php if ( $df_phone2 ) : ?>
						<span class="df-contact-card__sub"><?php echo esc_html( $df_phone2 ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>
			<?php if ( $df_wa ) : ?>
				<a class="df-contact-card" href="<?php echo esc_url( $df_wa ); ?>" target="_blank" rel="noopener">
					<span class="df-contact-card__icon df-contact-card__icon--wa"><?php df_the_icon( 'whatsapp', array( 'size' => 26 ) ); ?></span>
					<span class="df-contact-card__label">WhatsApp</span>
					<strong>Hemen Yazın</strong>
					<span class="df-contact-card__sub">Görsel gönderip fikir alabilirsiniz</span>
				</a>
			<?php endif; ?>
			<?php if ( $df_email ) : ?>
				<a class="df-contact-card" href="mailto:<?php echo esc_attr( antispambot( $df_email ) ); ?>">
					<span class="df-contact-card__icon"><?php df_the_icon( 'mail', array( 'size' => 26 ) ); ?></span>
					<span class="df-contact-card__label">E-posta</span>
					<strong><?php echo esc_html( antispambot( $df_email ) ); ?></strong>
					<span class="df-contact-card__sub">Kurumsal ve özel talepler için</span>
				</a>
			<?php endif; ?>
			<?php if ( df_wc() ) : ?>
				<a class="df-contact-card" href="<?php echo esc_url( $df_track ); ?>">
					<span class="df-contact-card__icon"><?php df_the_icon( 'package', array( 'size' => 26 ) ); ?></span>
					<span class="df-contact-card__label">Sipariş Takip</span>
					<strong>Siparişim Nerede?</strong>
					<span class="df-contact-card__sub">Durumu anlık görüntüleyin</span>
				</a>
			<?php endif; ?>
		</div>
	</section>

	<section class="df-section df-contact-main">
		<div class="df-container df-contact-main__grid">
			<div class="df-contact-form-wrap">
				<p class="df-eyebrow">Mesaj Gönderin</p>
				<h2>Size Dönüş Yapalım</h2>
				<p class="df-contact-form-wrap__lead">Formu doldurun, çalışma saatlerimiz içinde en kısa sürede size dönüş yapalım.</p>

				<?php if ( 'sent' === $df_form_state ) : ?>
					<div class="df-contact-alert df-contact-alert--ok" role="status"><?php df_the_icon( 'check', array( 'size' => 20 ) ); ?><span>Teşekkürler! Mesajınız bize ulaştı, en kısa sürede size dönüş yapacağız.</span></div>
				<?php elseif ( $df_form_error ) : ?>
					<div class="df-contact-alert df-contact-alert--error" role="alert"><?php df_the_icon( 'info', array( 'size' => 20 ) ); ?><span><?php echo esc_html( $df_form_error ); ?></span></div>
				<?php endif; ?>

				<form class="df-contact-form" method="post" action="<?php echo esc_url( get_permalink() ); ?>#df-contact-form" id="df-contact-form">
					<?php wp_nonce_field( 'df_contact_form', 'df_contact_nonce' ); ?>
					<p class="df-contact-form__hp" aria-hidden="true"><label>Web sitesi <input type="text" name="df_c_website" tabindex="-1" autocomplete="off"></label></p>
					<div class="df-contact-form__row">
						<p class="df-contact-field">
							<label for="df_c_name">Ad Soyad <span class="required">*</span></label>
							<input type="text" id="df_c_name" name="df_c_name" value="<?php echo esc_attr( $df_form['name'] ); ?>" autocomplete="name" required>
						</p>
						<p class="df-contact-field">
							<label for="df_c_phone">Telefon</label>
							<input type="tel" id="df_c_phone" name="df_c_phone" value="<?php echo esc_attr( $df_form['phone'] ); ?>" autocomplete="tel" inputmode="tel" placeholder="05XX XXX XX XX">
						</p>
					</div>
					<div class="df-contact-form__row">
						<p class="df-contact-field">
							<label for="df_c_email">E-posta</label>
							<input type="email" id="df_c_email" name="df_c_email" value="<?php echo esc_attr( $df_form['email'] ); ?>" autocomplete="email">
						</p>
						<p class="df-contact-field">
							<label for="df_c_subject">Konu</label>
							<select id="df_c_subject" name="df_c_subject">
								<?php foreach ( $df_subjects as $df_sub ) : ?>
									<option value="<?php echo esc_attr( $df_sub ); ?>" <?php selected( $df_form['subject'], $df_sub ); ?>><?php echo esc_html( $df_sub ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
					</div>
					<p class="df-contact-field">
						<label for="df_c_message">Mesajınız <span class="required">*</span></label>
						<textarea id="df_c_message" name="df_c_message" rows="6" required placeholder="Sipariş numaranız varsa yazmanız işimizi hızlandırır."><?php echo esc_textarea( $df_form['message'] ); ?></textarea>
					</p>
					<label class="df-contact-check"><input type="checkbox" name="df_c_kvkk" value="1" required><span>Kişisel verilerimin talebime yanıt verilmesi amacıyla işlenmesine ilişkin KVKK aydınlatma metnini okudum.</span></label>
					<button type="submit" class="df-btn df-btn--solid"><span>Mesajı Gönder</span></button>
				</form>
			</div>

			<aside class="df-contact-info">
				<div class="df-contact-info__card">
					<p class="df-eyebrow">Atölye & Mağaza</p>
					<h3><?php echo esc_html( df_opt( 'logo_text', get_bloginfo( 'name' ) ) ); ?></h3>
					<?php if ( $df_address ) : ?>
						<p class="df-contact-info__row"><?php df_the_icon( 'pin', array( 'size' => 20 ) ); ?><span><?php echo df_nl2br( $df_address ); // phpcs:ignore ?></span></p>
					<?php endif; ?>
					<?php if ( df_opt( 'contact_hours' ) ) : ?>
						<p class="df-contact-info__row"><?php df_the_icon( 'clock', array( 'size' => 20 ) ); ?><span><?php echo df_nl2br( df_opt( 'contact_hours' ) ); // phpcs:ignore ?></span></p>
					<?php endif; ?>
					<?php if ( df_opt( 'contact_map' ) ) : ?>
						<a class="df-link-arrow" href="<?php echo esc_url( df_opt( 'contact_map' ) ); ?>" target="_blank" rel="noopener">Yol Tarifi Al<?php df_the_icon( 'arrow-right', array( 'size' => 18 ) ); ?></a>
					<?php endif; ?>
					<?php $df_social = df_social_links(); ?>
					<?php if ( $df_social ) : ?>
						<ul class="df-social df-contact-info__social">
							<?php foreach ( $df_social as $df_net => $df_url ) : ?>
								<li><a href="<?php echo esc_url( $df_url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $df_net ) ); ?>"><?php df_the_icon( $df_net, array( 'size' => 20 ) ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<?php if ( $df_map_q ) : ?>
					<div class="df-contact-map">
						<iframe title="Derin Flowers konumu" src="<?php echo esc_url( 'https://maps.google.com/maps?q=' . rawurlencode( $df_map_q ) . '&z=16&output=embed' ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
					</div>
				<?php endif; ?>
			</aside>
		</div>
	</section>

	<?php $df_faq_items = (array) df_opt( 'faq_items', array() ); ?>
	<?php if ( $df_faq_items ) : ?>
		<section class="df-section df-contact-faq">
			<div class="df-container df-contact-faq__grid">
				<div>
					<p class="df-eyebrow">Yardım</p>
					<h2>Sıkça Sorulan Sorular</h2>
					<p class="df-contact-faq__lead">Aradığınız cevap burada olabilir.</p>
				</div>
				<?php df_faq_list( $df_faq_items ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
endwhile;

get_footer();
