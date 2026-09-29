<?php
/**
 * İletişim formu (sayfa şablonu ve Builder bloğu ortak kullanır).
 *
 * @package CanEloksal
 *
 * @var array $args title, text, subjects (string[]).
 */

defined( 'ABSPATH' ) || exit;

$ce_flash    = ce_form_flash();
$ce_errors   = $ce_flash && ! $ce_flash['ok'] ? (array) $ce_flash['errors'] : array();
$ce_subjects = array_filter( (array) ( $args['subjects'] ?? array() ) );
?>
<div class="ce-card-form" id="form">
	<h2 class="ce-card-form__title"><?php echo esc_html( $args['title'] ?? 'Bize yazın' ); ?></h2>
	<?php if ( ! empty( $args['text'] ) ) : ?>
		<p class="ce-card-form__text"><?php echo esc_html( $args['text'] ); ?></p>
	<?php endif; ?>
	<form class="ce-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-ajax-form novalidate>
		<?php ce_form_status( $ce_flash ); ?>
		<?php ce_form_security_fields( 'contact' ); ?>
		<div class="ce-form__grid">
			<?php
			ce_form_field( array( 'form' => 'c', 'name' => 'name', 'label' => 'Ad Soyad', 'required' => true, 'autocomplete' => 'name', 'half' => true, 'maxlength' => 100 ), $ce_errors );
			ce_form_field( array( 'form' => 'c', 'name' => 'company', 'label' => 'Firma', 'autocomplete' => 'organization', 'half' => true, 'maxlength' => 150 ), $ce_errors );
			ce_form_field( array( 'form' => 'c', 'name' => 'phone', 'label' => 'Telefon', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'half' => true, 'placeholder' => '05xx xxx xx xx', 'maxlength' => 30 ), $ce_errors );
			ce_form_field( array( 'form' => 'c', 'name' => 'email', 'label' => 'E-posta', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'half' => true, 'maxlength' => 150 ), $ce_errors );
			if ( $ce_subjects ) {
				ce_form_field( array( 'form' => 'c', 'name' => 'subject', 'label' => 'Konu', 'type' => 'select', 'options' => $ce_subjects, 'placeholder' => 'Konu seçin' ), $ce_errors );
			} else {
				ce_form_field( array( 'form' => 'c', 'name' => 'subject', 'label' => 'Konu', 'maxlength' => 150 ), $ce_errors );
			}
			ce_form_field( array( 'form' => 'c', 'name' => 'message', 'label' => 'Mesaj', 'type' => 'textarea', 'required' => true, 'rows' => 6, 'maxlength' => 5000 ), $ce_errors );
			ce_form_kvkk( 'c', $ce_errors );
			?>
		</div>
		<?php ce_form_submit( 'Mesajı Gönder' ); ?>
	</form>
</div>
