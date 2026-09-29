<?php
/**
 * Üyelik: kayıt formu ek alanları (ad, soyad, telefon, KVKK), Hesabım menüsü.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Üye kaydı: tek belirleyici Derin Flowers → Üyelik & Takip → "Hesabım sayfasında üye kaydı açık".
 * WooCommerce ayarı kapalı kalsa bile panel açıksa kayıt formu gösterilir.
 *
 * @param mixed $value WooCommerce ayar değeri.
 * @return string
 */
function df_force_registration_setting( $value ) {
	return df_opt( 'reg_on', 1 ) ? 'yes' : 'no';
}
add_filter( 'option_woocommerce_enable_myaccount_registration', 'df_force_registration_setting' );
add_filter( 'default_option_woocommerce_enable_myaccount_registration', 'df_force_registration_setting' );

/**
 * Kayıt formuna alanlar.
 */
function df_register_fields() {
	if ( df_opt( 'reg_phone' ) ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- form tekrar gösterimi.
		$first = isset( $_POST['df_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['df_first_name'] ) ) : '';
		$last  = isset( $_POST['df_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['df_last_name'] ) ) : '';
		$phone = isset( $_POST['df_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['df_phone'] ) ) : '';
		// phpcs:enable
		?>
		<div class="df-form-2col">
			<p class="woocommerce-form-row form-row">
				<label for="df_first_name">Ad&nbsp;<span class="required">*</span></label>
				<input type="text" class="input-text" name="df_first_name" id="df_first_name" autocomplete="given-name" value="<?php echo esc_attr( $first ); ?>" required>
			</p>
			<p class="woocommerce-form-row form-row">
				<label for="df_last_name">Soyad&nbsp;<span class="required">*</span></label>
				<input type="text" class="input-text" name="df_last_name" id="df_last_name" autocomplete="family-name" value="<?php echo esc_attr( $last ); ?>" required>
			</p>
		</div>
		<p class="woocommerce-form-row form-row">
			<label for="df_phone">Cep telefonu&nbsp;<span class="required">*</span></label>
			<span class="df-phone df-phone--static"><span class="df-phone__cc">+90</span><input type="tel" class="input-text" name="df_phone" id="df_phone" autocomplete="tel-national" inputmode="tel" placeholder="5XX XXX XX XX" value="<?php echo esc_attr( $phone ); ?>" required></span>
		</p>
		<?php
	}
}
add_action( 'woocommerce_register_form_start', 'df_register_fields' );

/**
 * KVKK onayı.
 */
function df_register_consent() {
	$text = df_opt( 'reg_kvkk' );
	if ( ! $text ) {
		return;
	}
	?>
	<p class="form-row df-consent">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox">
			<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="df_kvkk" value="1" required>
			<span><?php echo esc_html( $text ); ?></span>
		</label>
	</p>
	<p class="form-row df-consent">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox">
			<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="df_marketing" value="1">
			<span>Kampanya ve yeni koleksiyonlardan e-posta / SMS ile haberdar olmak istiyorum.</span>
		</label>
	</p>
	<?php
}
add_action( 'woocommerce_register_form', 'df_register_consent', 20 );

/**
 * Kayıt doğrulama.
 *
 * @param string   $username Kullanıcı adı.
 * @param string   $email    E-posta.
 * @param WP_Error $errors   Hatalar.
 */
function df_register_validate( $username, $email, $errors ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce kayıt nonce'unu doğrular.
	if ( df_opt( 'reg_phone' ) ) {
		if ( empty( $_POST['df_first_name'] ) || empty( $_POST['df_last_name'] ) ) {
			$errors->add( 'df_name', 'Lütfen adınızı ve soyadınızı girin.' );
		}
		$phone = isset( $_POST['df_phone'] ) ? preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['df_phone'] ) ) ) : '';
		if ( ! preg_match( '/^(0|90)?5\d{9}$/', $phone ) ) {
			$errors->add( 'df_phone', 'Lütfen geçerli bir cep telefonu numarası girin (5XX XXX XX XX).' );
		}
	}
	if ( df_opt( 'reg_kvkk' ) && empty( $_POST['df_kvkk'] ) ) {
		$errors->add( 'df_kvkk', 'Üyelik için sözleşme ve KVKK metnini onaylamanız gerekir.' );
	}
	// phpcs:enable
	return $errors;
}
add_action( 'woocommerce_register_post', 'df_register_validate', 10, 3 );

/**
 * Kayıt sonrası verileri kaydet.
 *
 * @param int $customer_id Müşteri.
 */
function df_register_save( $customer_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( isset( $_POST['df_first_name'] ) ) {
		$first = sanitize_text_field( wp_unslash( $_POST['df_first_name'] ) );
		update_user_meta( $customer_id, 'first_name', $first );
		update_user_meta( $customer_id, 'billing_first_name', $first );
	}
	if ( isset( $_POST['df_last_name'] ) ) {
		$last = sanitize_text_field( wp_unslash( $_POST['df_last_name'] ) );
		update_user_meta( $customer_id, 'last_name', $last );
		update_user_meta( $customer_id, 'billing_last_name', $last );
	}
	if ( isset( $_POST['df_phone'] ) ) {
		$phone = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['df_phone'] ) ) );
		$phone = preg_replace( '/^(90|0)/', '', $phone );
		update_user_meta( $customer_id, 'billing_phone', '+90' . $phone );
	}
	update_user_meta( $customer_id, 'df_kvkk_accepted', empty( $_POST['df_kvkk'] ) ? '' : current_time( 'mysql' ) );
	update_user_meta( $customer_id, 'df_marketing', empty( $_POST['df_marketing'] ) ? 'no' : 'yes' );
	// phpcs:enable
}
add_action( 'woocommerce_created_customer', 'df_register_save' );

/**
 * Hesabım menüsü: Favorilerim ve Sipariş Takip bağlantıları.
 *
 * @param array $items Öğeler.
 * @return array
 */
function df_account_menu( $items ) {
	$new = array();
	foreach ( $items as $key => $label ) {
		if ( 'downloads' === $key ) {
			continue; // Çiçekçide indirilebilir ürün yok.
		}
		$new[ $key ] = $label;
		if ( 'orders' === $key ) {
			$new['df-favorites'] = 'Favorilerim';
		}
	}
	if ( isset( $new['edit-address'] ) ) {
		$new['edit-address'] = 'Adreslerim';
	}
	return $new;
}
add_filter( 'woocommerce_account_menu_items', 'df_account_menu' );

/**
 * Hesabım menüsü öğe bağlantısı.
 *
 * @param string $url      URL.
 * @param string $endpoint Uç nokta.
 * @return string
 */
function df_account_menu_url( $url, $endpoint ) {
	if ( 'df-favorites' === $endpoint ) {
		return df_page_url( 'wishlist_page', 'favorilerim' );
	}
	return $url;
}
add_filter( 'woocommerce_get_endpoint_url', 'df_account_menu_url', 10, 2 );

/**
 * Hesabım ana sayfası: hızlı kartlar.
 */
function df_account_dashboard_cards() {
	$cards = array(
		array( 'package', 'Siparişlerim', wc_get_account_endpoint_url( 'orders' ) ),
		array( 'heart', 'Favorilerim', df_page_url( 'wishlist_page', 'favorilerim' ) ),
		array( 'truck', 'Sipariş Takip', df_page_url( 'track_page', 'siparis-takip' ) ),
		array( 'user', 'Hesap Bilgilerim', wc_get_account_endpoint_url( 'edit-account' ) ),
	);
	echo '<div class="df-account-cards">';
	foreach ( $cards as $c ) {
		printf( '<a class="df-account-card" href="%s">%s<span>%s</span></a>', esc_url( $c[2] ), df_icon( $c[0], array( 'size' => 26 ) ), esc_html( $c[1] ) ); // phpcs:ignore
	}
	echo '</div>';
}
add_action( 'woocommerce_account_dashboard', 'df_account_dashboard_cards' );
