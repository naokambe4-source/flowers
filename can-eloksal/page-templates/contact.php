<?php
/**
 * Template Name: İletişim
 *
 * Firma bilgileri, harita ve veritabanına kaydedilen iletişim formu.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_flash  = ce_form_flash();
$ce_errors = $ce_flash && ! $ce_flash['ok'] ? (array) $ce_flash['errors'] : array();

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id       = get_the_ID();
	$ce_subjects = ce_lines( ce_meta( $ce_id, 'subjects' ) );
	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => ce_page_title( $ce_id ),
			'subtitle' => ce_page_subtitle( $ce_id ),
			'eyebrow'  => ce_meta( $ce_id, 'hero_eyebrow' ),
			'image'    => ce_meta( $ce_id, 'hero_image' ),
		)
	);
	?>
	<section class="ce-section">
		<div class="ce-container ce-contact">
			<div class="ce-contact__info">
				<p class="ce-eyebrow"><?php echo esc_html( ce_opt( 'company_name' ) ); ?></p>
				<h2 class="ce-display ce-display--sm">Bize ulaşın</h2>
				<?php if ( get_the_content() ) : ?>
					<div class="ce-prose"><?php the_content(); ?></div>
				<?php endif; ?>
				<?php get_template_part( 'template-parts/components/contact-cards' ); ?>
			</div>

			<div class="ce-card-form" id="form">
				<h2 class="ce-card-form__title"><?php echo esc_html( ce_meta( $ce_id, 'form_title', 'Bize yazın' ) ); ?></h2>
				<?php if ( ce_meta( $ce_id, 'form_text' ) ) : ?>
					<p class="ce-card-form__text"><?php echo esc_html( ce_meta( $ce_id, 'form_text' ) ); ?></p>
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
		</div>
	</section>

	<?php $ce_map = ce_map_embed(); ?>
	<?php if ( $ce_map ) : ?>
		<section class="ce-map" aria-label="Konum haritası">
			<div class="ce-map__frame"><?php echo $ce_map; // phpcs:ignore -- ce_map_embed() yalnızca doğrulanmış Google Maps iframe döndürür. ?></div>
			<?php if ( ce_opt( 'maps_link' ) ) : ?>
				<a class="ce-btn ce-btn--dark ce-map__btn" href="<?php echo esc_url( ce_opt( 'maps_link' ) ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'map-pin', 18, 'ce-btn__icon' ); // phpcs:ignore ?><span>Yol tarifi al</span></a>
			<?php endif; ?>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
