<?php
/**
 * İletişim kartları (telefon, WhatsApp, e-posta, adres).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_cards = array();
if ( ce_opt( 'phone' ) ) {
	$ce_cards[] = array( 'phone', 'Telefon', ce_opt( 'phone' ), ce_tel(), false );
}
if ( ce_whatsapp_url() ) {
	$ce_cards[] = array( 'whatsapp', 'WhatsApp', 'Mesaj gönderin', ce_whatsapp_url(), true );
}
if ( ce_opt( 'email' ) ) {
	$ce_cards[] = array( 'mail', 'E-posta', ce_opt( 'email' ), 'mailto:' . ce_opt( 'email' ), false );
}
if ( ce_opt( 'address' ) ) {
	$ce_cards[] = array( 'map-pin', 'Adres', ce_opt( 'address' ), ce_opt( 'maps_link' ), true );
}
if ( ! $ce_cards ) {
	return;
}
?>
<ul class="ce-ccards">
	<?php foreach ( $ce_cards as $ce_i => $ce_c ) : ?>
		<li data-reveal style="--i:<?php echo (int) $ce_i; ?>">
			<?php if ( $ce_c[3] ) : ?>
				<a class="ce-ccard" href="<?php echo 0 === strpos( $ce_c[3], 'http' ) ? esc_url( $ce_c[3] ) : esc_attr( $ce_c[3] ); ?>" <?php echo $ce_c[4] ? 'target="_blank" rel="noopener"' : ''; ?>>
			<?php else : ?>
				<div class="ce-ccard">
			<?php endif; ?>
				<span class="ce-ccard__icon"><?php echo ce_icon( $ce_c[0], 22 ); // phpcs:ignore ?></span>
				<span class="ce-ccard__label"><?php echo esc_html( $ce_c[1] ); ?></span>
				<span class="ce-ccard__value"><?php echo ce_nl2br( $ce_c[2] ); // phpcs:ignore ?></span>
				<?php if ( $ce_c[3] ) : ?>
					<span class="ce-ccard__arrow" aria-hidden="true"><?php echo ce_icon( 'arrow-up-right', 18 ); // phpcs:ignore ?></span>
				<?php endif; ?>
			<?php echo $ce_c[3] ? '</a>' : '</div>'; ?>
		</li>
	<?php endforeach; ?>
</ul>
