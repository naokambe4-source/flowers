<?php
/**
 * Ön yüz form alanı bileşenleri (etiket, hata alanı, erişilebilirlik nitelikleri).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form alanı.
 *
 * @param array $f name, label, type (text|email|tel|textarea|select|number), required, options, autocomplete, placeholder, half, value, hint.
 * @param array $errors Sunucu hataları (JS kapalıyken).
 * @param array $old    Önceki değerler.
 */
function ce_form_field( $f, $errors = array(), $old = array() ) {
	$name  = $f['name'];
	$id    = 'ce-f-' . $f['form'] . '-' . $name;
	$type  = $f['type'] ?? 'text';
	$req   = ! empty( $f['required'] );
	$err   = $errors[ $name ] ?? '';
	$value = $old[ $name ] ?? ( $f['value'] ?? '' );
	$attrs = sprintf(
		'id="%1$s" name="%2$s" %3$s %4$s aria-describedby="%1$s-err%5$s"%6$s',
		esc_attr( $id ),
		esc_attr( $name ),
		$req ? 'required aria-required="true"' : '',
		! empty( $f['autocomplete'] ) ? 'autocomplete="' . esc_attr( $f['autocomplete'] ) . '"' : '',
		! empty( $f['hint'] ) ? ' ' . esc_attr( $id ) . '-hint' : '',
		$err ? ' aria-invalid="true"' : ''
	);
	echo '<div class="ce-fg' . ( ! empty( $f['half'] ) ? ' ce-fg--half' : '' ) . ( $err ? ' has-error' : '' ) . '">';
	echo '<label class="ce-fg__label" for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . ( $req ? ' <span class="ce-req" aria-hidden="true">*</span>' : ' <span class="ce-fg__opt">(opsiyonel)</span>' ) . '</label>';

	switch ( $type ) {
		case 'textarea':
			echo '<textarea class="ce-input" rows="' . (int) ( $f['rows'] ?? 5 ) . '" ' . $attrs . ( ! empty( $f['maxlength'] ) ? ' maxlength="' . (int) $f['maxlength'] . '"' : '' ) . ' placeholder="' . esc_attr( $f['placeholder'] ?? '' ) . '">' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore
			break;
		case 'select':
			echo '<div class="ce-select"><select class="ce-input" ' . $attrs . '>'; // phpcs:ignore
			echo '<option value="">' . esc_html( $f['placeholder'] ?? 'Seçiniz' ) . '</option>';
			foreach ( $f['options'] as $opt ) {
				echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $value, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
			}
			echo '</select>' . ce_icon( 'chevron-down', 16, 'ce-select__icon' ) . '</div>'; // phpcs:ignore
			break;
		default:
			$input_type = in_array( $type, array( 'email', 'tel', 'number' ), true ) ? $type : 'text';
			echo '<input class="ce-input" type="' . esc_attr( $input_type ) . '" value="' . esc_attr( $value ) . '" ' . $attrs . ( ! empty( $f['maxlength'] ) ? ' maxlength="' . (int) $f['maxlength'] . '"' : '' ) . ( 'tel' === $type ? ' inputmode="tel"' : '' ) . ' placeholder="' . esc_attr( $f['placeholder'] ?? '' ) . '">'; // phpcs:ignore
	}
	if ( ! empty( $f['hint'] ) ) {
		echo '<p class="ce-fg__hint" id="' . esc_attr( $id ) . '-hint">' . esc_html( $f['hint'] ) . '</p>';
	}
	echo '<p class="ce-fg__error" id="' . esc_attr( $id ) . '-err" data-error-for="' . esc_attr( $name ) . '">' . esc_html( $err ) . '</p>';
	echo '</div>';
}

/**
 * KVKK onay kutusu.
 *
 * @param string $form   Form.
 * @param array  $errors Hatalar.
 */
function ce_form_kvkk( $form, $errors = array() ) {
	$id    = 'ce-f-' . $form . '-kvkk';
	$link  = ce_legal_link( 'kvkk_page', 'KVKK Aydınlatma Metni' );
	$label = str_replace( '{link}', $link, esc_html( ce_opt( 'kvkk_label' ) ) );
	$err   = $errors['kvkk'] ?? '';
	echo '<div class="ce-fg ce-fg--check' . ( $err ? ' has-error' : '' ) . '">';
	echo '<label class="ce-check" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="kvkk" value="1" required aria-required="true" aria-describedby="' . esc_attr( $id ) . '-err"><span class="ce-check__box" aria-hidden="true">' . ce_icon( 'check', 14 ) . '</span><span class="ce-check__text">' . wp_kses_post( $label ) . '</span></label>'; // phpcs:ignore
	echo '<p class="ce-fg__error" id="' . esc_attr( $id ) . '-err" data-error-for="kvkk">' . esc_html( $err ) . '</p>';
	echo '</div>';
}

/**
 * Form durum kutusu (JS kapalıyken sunucu mesajı gösterir).
 *
 * @param array|null $flash Flash veri.
 */
function ce_form_status( $flash ) {
	$class = $flash ? ( $flash['ok'] ? ' is-success' : ' is-error' ) : '';
	echo '<div class="ce-form__status' . esc_attr( $class ) . '" role="status" aria-live="polite" data-form-status' . ( $flash ? '' : ' hidden' ) . '>';
	if ( $flash ) {
		echo ce_icon( $flash['ok'] ? 'check' : 'alert', 20 ) . '<span>' . esc_html( $flash['message'] ) . '</span>'; // phpcs:ignore
	}
	echo '</div>';
}

/**
 * Gönder butonu.
 *
 * @param string $label Metin.
 */
function ce_form_submit( $label ) {
	echo '<button type="submit" class="ce-btn ce-btn--primary ce-btn--lg ce-form__submit" data-submit><span class="ce-btn__label">' . esc_html( $label ) . '</span>' . ce_icon( 'arrow-right', 18, 'ce-btn__icon' ) . '<span class="ce-spinner" aria-hidden="true"></span></button>'; // phpcs:ignore
}
