<?php
/**
 * Template Name: Teklif Al
 *
 * Dosya yüklemeli teklif formu. Dosyalar sunucuda doğrulanır (uzantı + MIME + içerik imzası)
 * ve web'den erişilemeyen gizli klasöre rastgele adla kaydedilir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_flash  = ce_form_flash();
$ce_errors = $ce_flash && ! $ce_flash['ok'] ? (array) $ce_flash['errors'] : array();

// Hizmet seçenekleri: aktif hizmetler + "Diğer / Emin değilim".
$ce_services = array_map( 'get_the_title', ce_get_items( 'ce_service', 50 ) );
$ce_services = array_map( 'html_entity_decode', $ce_services );
$ce_services[] = 'Diğer / Emin değilim';
$ce_selected   = '';
if ( isset( $_GET['hizmet'] ) ) { // phpcs:ignore
	$ce_slug = sanitize_title( wp_unslash( $_GET['hizmet'] ) ); // phpcs:ignore
	$ce_sp   = get_page_by_path( $ce_slug, OBJECT, 'ce_service' );
	if ( $ce_sp ) {
		$ce_selected = html_entity_decode( get_the_title( $ce_sp ) );
	}
}
$ce_max_mb    = (int) ce_opt( 'quote_max_mb', 10 );
$ce_max_files = (int) ce_opt( 'quote_max_files', 5 );

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id = get_the_ID();
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
		<div class="ce-container ce-quote">
			<div class="ce-card-form ce-quote__form" id="form">
				<form class="ce-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" data-ajax-form novalidate>
					<?php ce_form_status( $ce_flash ); ?>
					<?php ce_form_security_fields( 'quote' ); ?>

					<fieldset class="ce-form__set">
						<legend>Firma ve iletişim bilgileri</legend>
						<div class="ce-form__grid">
							<?php
							ce_form_field( array( 'form' => 'q', 'name' => 'company', 'label' => 'Firma Adı', 'required' => true, 'autocomplete' => 'organization', 'half' => true, 'maxlength' => 150 ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'name', 'label' => 'Yetkili Adı', 'required' => true, 'autocomplete' => 'name', 'half' => true, 'maxlength' => 100 ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'phone', 'label' => 'Telefon', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'half' => true, 'placeholder' => '05xx xxx xx xx', 'maxlength' => 30 ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'email', 'label' => 'E-posta', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'half' => true, 'maxlength' => 150 ), $ce_errors );
							?>
						</div>
					</fieldset>

					<fieldset class="ce-form__set">
						<legend>Parça ve yüzey bilgileri</legend>
						<div class="ce-form__grid">
							<?php
							ce_form_field( array( 'form' => 'q', 'name' => 'service', 'label' => 'Hizmet', 'type' => 'select', 'required' => true, 'options' => $ce_services, 'placeholder' => 'Hizmet seçin', 'half' => true, 'value' => $ce_selected ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'material', 'label' => 'Malzeme Türü', 'type' => 'select', 'options' => ce_lines( ce_opt( 'quote_materials' ) ), 'placeholder' => 'Malzeme seçin', 'half' => true ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'quantity', 'label' => 'Parça Adedi', 'half' => true, 'placeholder' => 'Ör. 250', 'maxlength' => 60 ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'surface', 'label' => 'Talep Edilen Yüzey', 'type' => 'select', 'options' => ce_lines( ce_opt( 'quote_surfaces' ) ), 'placeholder' => 'Yüzey seçin', 'half' => true ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'color', 'label' => 'Renk', 'half' => true, 'placeholder' => 'Ör. Siyah, naturel', 'maxlength' => 80 ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'micron', 'label' => 'Mikron', 'half' => true, 'placeholder' => 'Ör. 15–20 µm', 'maxlength' => 40 ), $ce_errors );
							ce_form_field( array( 'form' => 'q', 'name' => 'message', 'label' => 'Açıklama', 'type' => 'textarea', 'rows' => 5, 'maxlength' => 5000, 'placeholder' => 'Parça ölçüleri, alaşım, şartname, teslim beklentisi vb.' ), $ce_errors );
							?>
							<div class="ce-fg<?php echo isset( $ce_errors['files'] ) ? ' has-error' : ''; ?>">
								<span class="ce-fg__label" id="ce-files-label">Dosya <span class="ce-fg__opt">(opsiyonel)</span></span>
								<label class="ce-drop" for="ce-f-q-files" data-drop>
									<input type="file" id="ce-f-q-files" name="files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" aria-describedby="ce-files-hint ce-f-q-files-err" data-max-mb="<?php echo (int) $ce_max_mb; ?>" data-max-files="<?php echo (int) $ce_max_files; ?>">
									<span class="ce-drop__icon"><?php echo ce_icon( 'upload', 24 ); // phpcs:ignore ?></span>
									<span class="ce-drop__text"><strong>Dosya seçin</strong> veya buraya sürükleyin</span>
									<span class="ce-drop__hint" id="ce-files-hint">PDF, JPG, JPEG, PNG, WEBP · en fazla <?php echo (int) $ce_max_files; ?> dosya · dosya başına <?php echo (int) $ce_max_mb; ?> MB</span>
								</label>
								<ul class="ce-drop__list" data-file-list></ul>
								<p class="ce-fg__error" id="ce-f-q-files-err" data-error-for="files"><?php echo esc_html( $ce_errors['files'] ?? '' ); ?></p>
							</div>
							<?php ce_form_kvkk( 'q', $ce_errors ); ?>
						</div>
					</fieldset>
					<?php ce_form_submit( 'Teklif Talebini Gönder' ); ?>
				</form>
			</div>

			<aside class="ce-quote__aside">
				<div class="ce-aside-card">
					<h2 class="ce-aside-card__title"><?php echo esc_html( ce_meta( $ce_id, 'aside_title', 'Teklif süreci' ) ); ?></h2>
					<?php $ce_steps = ce_lines( ce_meta( $ce_id, 'aside_steps' ) ); ?>
					<?php if ( $ce_steps ) : ?>
						<ol class="ce-mini-steps">
							<?php foreach ( $ce_steps as $ce_i => $ce_step ) : ?>
								<li><span><?php echo esc_html( sprintf( '%02d', $ce_i + 1 ) ); ?></span><?php echo esc_html( $ce_step ); ?></li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
					<?php if ( ce_meta( $ce_id, 'aside_note' ) ) : ?>
						<p class="ce-aside-card__note"><?php echo ce_icon( 'info', 18 ); // phpcs:ignore ?><span><?php echo esc_html( ce_meta( $ce_id, 'aside_note' ) ); ?></span></p>
					<?php endif; ?>
					<?php if ( get_the_content() ) : ?>
						<div class="ce-prose"><?php the_content(); ?></div>
					<?php endif; ?>
				</div>
				<div class="ce-aside-card ce-aside-card--dark">
					<p class="ce-eyebrow ce-eyebrow--light">Hızlı iletişim</p>
					<?php if ( ce_opt( 'phone' ) ) : ?>
						<a class="ce-aside-card__phone" href="<?php echo esc_attr( ce_tel() ); ?>"><?php echo ce_icon( 'phone', 20 ); // phpcs:ignore ?><?php echo esc_html( ce_opt( 'phone' ) ); ?></a>
					<?php endif; ?>
					<?php if ( ce_whatsapp_url() ) : ?>
						<a class="ce-btn ce-btn--ghost-light ce-btn--block" href="<?php echo esc_url( ce_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'whatsapp', 18, 'ce-btn__icon' ); // phpcs:ignore ?><span>WhatsApp ile yazın</span></a>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</section>
	<?php
endwhile;
get_footer();
