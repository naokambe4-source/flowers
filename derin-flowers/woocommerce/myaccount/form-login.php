<?php
/**
 * Giriş / Üye ol (sekmeli, görselli).
 *
 * @package DerinFlowers
 * @version 9.9.0
 */

defined( 'ABSPATH' ) || exit;

$df_reg     = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$df_image   = absint( df_opt( 'login_image' ) );
// phpcs:ignore WordPress.Security.NonceVerification.Missing
$df_tab     = ( $df_reg && ( isset( $_POST['register'] ) || isset( $_GET['kayit'] ) ) ) ? 'register' : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="df-auth<?php echo $df_image ? ' has-image' : ''; ?>" id="customer_login" data-df-auth>
	<?php if ( $df_image ) : ?>
		<div class="df-auth__media"><?php echo wp_get_attachment_image( $df_image, 'df-portrait', false, array( 'loading' => 'lazy' ) ); ?></div>
	<?php endif; ?>

	<div class="df-auth__panel">
		<div class="df-auth__intro">
			<h2<?php echo df_e( 'login_title' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'login_title', 'Hoş Geldiniz' ) ); ?></h2>
			<p<?php echo df_e( 'login_text' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'login_text' ) ); ?></p>
		</div>

		<?php if ( $df_reg ) : ?>
			<div class="df-auth__tabs" role="tablist">
				<button type="button" role="tab" class="df-auth__tab<?php echo 'login' === $df_tab ? ' is-active' : ''; ?>" data-df-auth-tab="login" aria-selected="<?php echo 'login' === $df_tab ? 'true' : 'false'; ?>">Giriş Yap</button>
				<button type="button" role="tab" class="df-auth__tab<?php echo 'register' === $df_tab ? ' is-active' : ''; ?>" data-df-auth-tab="register" aria-selected="<?php echo 'register' === $df_tab ? 'true' : 'false'; ?>">Üye Ol</button>
			</div>
		<?php endif; ?>

		<div class="df-auth__pane<?php echo 'login' === $df_tab ? ' is-active' : ''; ?>" data-df-auth-pane="login" role="tabpanel">
			<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
				<?php do_action( 'woocommerce_login_form_start' ); ?>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="username">E-posta adresi veya kullanıcı adı&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true" />
				</p>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="password">Şifre&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
				</p>
				<?php do_action( 'woocommerce_login_form' ); ?>
				<div class="df-auth__row">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme df-checkbox">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span>Beni hatırla</span>
					</label>
					<a class="df-auth__lost" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Şifremi unuttum</a>
				</div>
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit df-btn df-btn--solid df-btn--block" name="login" value="Giriş yap">Giriş Yap</button>
				<?php do_action( 'woocommerce_login_form_end' ); ?>
			</form>
		</div>

		<?php if ( $df_reg ) : ?>
			<div class="df-auth__pane<?php echo 'register' === $df_tab ? ' is-active' : ''; ?>" data-df-auth-pane="register" role="tabpanel">
				<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >
					<?php do_action( 'woocommerce_register_form_start' ); ?>
					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_username">Kullanıcı adı&nbsp;<span class="required" aria-hidden="true">*</span></label>
							<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true" />
						</p>
					<?php endif; ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_email">E-posta adresi&nbsp;<span class="required" aria-hidden="true">*</span></label>
						<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true" />
					</p>
					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_password">Şifre&nbsp;<span class="required" aria-hidden="true">*</span></label>
							<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
						</p>
					<?php else : ?>
						<p class="df-field__hint">Şifre oluşturma bağlantısı e-posta adresinize gönderilecektir.</p>
					<?php endif; ?>
					<?php do_action( 'woocommerce_register_form' ); ?>
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit df-btn df-btn--solid df-btn--block" name="register" value="Üye ol">Üye Ol</button>
					<?php do_action( 'woocommerce_register_form_end' ); ?>
				</form>
			</div>
		<?php endif; ?>

		<ul class="df-auth__perks">
			<li><?php df_the_icon( 'truck', array( 'size' => 18 ) ); ?>Siparişlerinizi anlık takip edin</li>
			<li><?php df_the_icon( 'heart', array( 'size' => 18 ) ); ?>Favorilerinizi kaydedin</li>
			<li><?php df_the_icon( 'gift', array( 'size' => 18 ) ); ?>Özel günlerde size özel fırsatlar</li>
		</ul>
	</div>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
