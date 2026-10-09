<?php
/**
 * Teslimat / gönderici / alıcı / not alanları. Ödeme sayfasında ve ürün sayfasındaki
 * hızlı siparişte ortak kullanılır.
 *
 * @package DerinFlowers
 *
 * @var array $args context: checkout|product.
 */

defined( 'ABSPATH' ) || exit;

$df_ctx = isset( $args['context'] ) ? $args['context'] : 'checkout';
$df_pickup_on = (bool) df_opt( 'df_pickup_on' );
$df_type      = df_checkout_value( 'type' ) === 'pickup' && $df_pickup_on ? 'pickup' : 'address';
$df_stores    = df_delivery_stores();
$df_groups    = df_delivery_districts_grouped();
$df_codes     = df_phone_codes();
$df_notes     = df_note_templates();
$df_note_max  = absint( df_opt( 'note_max', 300 ) );
$df_district  = df_checkout_value( 'district' );
$df_note      = df_checkout_value( 'note' );
$df_phone     = function ( $name, $label, $value, $cc_value ) use ( $df_codes ) {
	?>
	<p class="form-row form-row-wide validate-required df-field" id="<?php echo esc_attr( $name ); ?>_field">
		<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?>&nbsp;<abbr class="required" title="zorunlu">*</abbr></label>
		<span class="df-phone">
			<select name="<?php echo esc_attr( $name ); ?>_cc" class="df-phone__cc" aria-label="Ülke kodu">
				<?php foreach ( $df_codes as $code => $country ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $cc_value ? $cc_value : '+90', $code ); ?>><?php echo esc_html( $country ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="tel" class="input-text" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="5XX XXX XX XX" autocomplete="tel-national" inputmode="tel" required>
		</span>
	</p>
	<?php
};
?>
				<?php /* 1 — Teslimat */ ?>
				<section class="df-step" id="df-step-delivery">
					<h2 class="df-step__title"><span class="df-step__num">1</span>Teslimat bilgileri</h2>
					<p class="df-step__lead">Çiçeklerinizin ne zaman ve nasıl ulaşacağını seçin.</p>

					<?php if ( $df_pickup_on ) : ?>
						<fieldset class="df-types">
							<legend class="df-label">Teslimat türü</legend>
							<label class="df-type">
								<input type="radio" name="df_type" value="address" <?php checked( $df_type, 'address' ); ?>>
								<span class="df-type__box">
									<?php df_the_icon( 'truck', array( 'size' => 26 ) ); ?>
									<span><strong>Adrese teslim</strong><small><?php echo esc_html( df_opt( 'df_default_city', 'İzmir' ) ); ?>'in seçili bölgelerine</small></span>
									<i class="df-radio" aria-hidden="true"></i>
								</span>
							</label>
							<label class="df-type">
								<input type="radio" name="df_type" value="pickup" <?php checked( $df_type, 'pickup' ); ?>>
								<span class="df-type__box">
									<?php df_the_icon( 'store', array( 'size' => 26 ) ); ?>
									<span><strong>Mağazadan teslim</strong><small><?php echo esc_html( $df_stores ? $df_stores[0]['name'] : 'Ücretsiz' ); ?></small></span>
									<i class="df-radio" aria-hidden="true"></i>
								</span>
							</label>
						</fieldset>
					<?php else : ?>
						<input type="hidden" name="df_type" value="address">
					<?php endif; ?>

					<div class="df-zone" data-df-for="address"<?php echo 'pickup' === $df_type ? ' hidden' : ''; ?>>
						<p class="form-row form-row-wide validate-required df-field" id="df_district_field">
							<label for="df_district">Teslimat bölgesi&nbsp;<abbr class="required" title="zorunlu">*</abbr></label>
							<span class="df-zone__pin" aria-hidden="true"><?php df_the_icon( 'pin', array( 'size' => 20 ) ); ?></span>
							<select name="df_district" id="df_district" class="df-district" data-placeholder="Gönderim adresi seçin">
								<option value="">Gönderim adresi seçin</option>
								<?php foreach ( $df_groups as $df_city => $df_items ) : ?>
									<optgroup label="<?php echo esc_attr( $df_city ); ?>">
										<?php foreach ( $df_items as $df_d ) : ?>
											<option value="<?php echo esc_attr( $df_d['key'] ); ?>" data-fee="<?php echo esc_attr( $df_d['fee'] ); ?>" <?php selected( $df_district, $df_d['key'] ); ?>>
												<?php echo esc_html( $df_d['name'] . ' — ' . ( $df_d['fee'] > 0 ? wp_strip_all_tags( wc_price( $df_d['fee'] ) ) : 'Ücretsiz' ) ); ?>
											</option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
							<span class="df-field__hint" data-df-fee-hint></span>
						</p>
					</div>

					<div class="df-when">
						<div class="df-when__date" id="df_date_field">
							<span class="df-label" id="df-date-label">Teslimat tarihi <abbr class="required" title="zorunlu">*</abbr></span>
							<span class="df-when__sub">Teslim etmek istediğiniz günü seçin.</span>
							<div class="df-cal" data-df-calendar aria-labelledby="df-date-label">
								<div class="df-cal__quick" data-df-cal-quick></div>
								<div class="df-cal__box">
									<div class="df-cal__head">
										<button type="button" class="df-cal__nav" data-df-cal-prev aria-label="Önceki ay"><?php df_the_icon( 'chevron-left', array( 'size' => 18 ) ); ?></button>
										<strong class="df-cal__month" data-df-cal-month aria-live="polite"></strong>
										<button type="button" class="df-cal__nav" data-df-cal-next aria-label="Sonraki ay"><?php df_the_icon( 'chevron-right', array( 'size' => 18 ) ); ?></button>
									</div>
									<div class="df-cal__week" aria-hidden="true"></div>
									<div class="df-cal__grid" data-df-cal-grid role="grid"></div>
									<p class="df-cal__legend"><span class="is-available"></span>Teslimat yapılabilir <span class="is-closed"></span>Kapalı</p>
								</div>
								<input type="hidden" name="df_date" id="df_date" value="<?php echo esc_attr( df_checkout_value( 'date' ) ); ?>">
								<noscript><p class="df-alert">Takvimi kullanmak için tarayıcınızda JavaScript açık olmalıdır.</p></noscript>
							</div>
						</div>
						<div class="df-when__slot" id="df_slot_field">
							<span class="df-label">Teslimat saati <abbr class="required" title="zorunlu">*</abbr></span>
							<p class="df-when__selected" data-df-date-text>Önce takvimden bir gün seçin.</p>
							<div class="df-slots" data-df-slots role="radiogroup" aria-label="Saat aralığı"></div>
							<input type="hidden" id="df_slot_saved" value="<?php echo esc_attr( df_checkout_value( 'slot' ) ); ?>">
							<p class="df-when__note"><?php df_the_icon( 'info', array( 'size' => 16 ) ); ?><span><?php echo esc_html( df_vars( 'Aynı gün veya ileri tarihli teslimat seçebilirsiniz. Aynı gün için son sipariş saati {cutoff}.' ) ); ?></span></p>
						</div>
					</div>
				</section>

				<?php /* 2 — Gönderici */ ?>
				<section class="df-step" id="df-step-sender">
					<h2 class="df-step__title"><span class="df-step__num">2</span>Gönderici Bilgileri</h2>
					<div class="df-row-2">
						<p class="form-row form-row-wide validate-required df-field" id="df_sender_name_field">
							<label for="df_sender_name">Gönderici Adı Soyadı&nbsp;<abbr class="required" title="zorunlu">*</abbr></label>
							<input type="text" class="input-text" name="df_sender_name" id="df_sender_name" value="<?php echo esc_attr( df_checkout_value( 'sender_name' ) ); ?>" autocomplete="name" required>
						</p>
						<?php $df_phone( 'df_sender_phone', 'Gönderici Telefon Numarası', df_checkout_value( 'sender_phone' ), df_checkout_value( 'sender_cc' ) ); ?>
					</div>
					<?php if ( 'checkout' === $df_ctx ) : ?>
						<div class="df-billing">
							<?php do_action( 'woocommerce_checkout_billing' ); ?>
						</div>
					<?php else : ?>
						<p class="form-row form-row-wide validate-required df-field" id="billing_email_field">
							<label for="billing_email">E-posta adresiniz&nbsp;<abbr class="required" title="zorunlu">*</abbr></label>
							<input type="email" class="input-text" name="billing_email" id="billing_email" value="<?php echo esc_attr( df_checkout_value( 'email' ) ); ?>" placeholder="Sipariş onayı bu adrese gönderilir" autocomplete="email" required>
						</p>
					<?php endif; ?>
				</section>

				<?php /* 3 — Alıcı */ ?>
				<section class="df-step" id="df-step-recipient">
					<h2 class="df-step__title"><span class="df-step__num">3</span>Alıcı Bilgileri</h2>
					<div class="df-row-2">
						<p class="form-row form-row-wide validate-required df-field" id="df_recipient_name_field">
							<label for="df_recipient_name">Alıcı Adı Soyadı&nbsp;<abbr class="required" title="zorunlu">*</abbr></label>
							<input type="text" class="input-text" name="df_recipient_name" id="df_recipient_name" value="<?php echo esc_attr( df_checkout_value( 'recipient_name' ) ); ?>" autocomplete="off" required>
						</p>
						<?php $df_phone( 'df_recipient_phone', 'Alıcı Telefon Numarası', df_checkout_value( 'recipient_phone' ), df_checkout_value( 'recipient_cc' ) ); ?>
					</div>

					<div class="df-addr" data-df-for="address"<?php echo 'pickup' === $df_type ? ' hidden' : ''; ?>>
						<p class="form-row form-row-wide validate-required df-field" id="df_address_field">
							<label for="df_address">Alıcı Adresi&nbsp;<abbr class="required" title="zorunlu">*</abbr></label>
							<textarea class="input-text" name="df_address" id="df_address" rows="3" placeholder="Mahalle, cadde/sokak, bina no, daire no" autocomplete="street-address"><?php echo esc_textarea( df_checkout_value( 'address' ) ); ?></textarea>
						</p>
						<p class="form-row form-row-wide df-field" id="df_address_hint_field">
							<label for="df_address_hint">Adres tarifi <span class="optional">(opsiyonel)</span></label>
							<input type="text" class="input-text" name="df_address_hint" id="df_address_hint" value="<?php echo esc_attr( df_checkout_value( 'address_hint' ) ); ?>" placeholder="Ör. Hastane B blok, 3. kat, 312 nolu oda">
						</p>
					</div>

					<?php if ( $df_pickup_on ) : ?>
						<div class="df-stores" data-df-for="pickup" id="df_store_field"<?php echo 'pickup' === $df_type ? '' : ' hidden'; ?>>
							<span class="df-label">Teslim alınacak mağaza</span>
							<?php foreach ( $df_stores as $df_i => $df_store ) : ?>
								<label class="df-store">
									<input type="radio" name="df_store" value="<?php echo (int) $df_i; ?>" <?php checked( 1 === count( $df_stores ) || (string) df_checkout_value( 'store' ) === (string) $df_i ); ?>>
									<span class="df-store__box">
										<?php df_the_icon( 'pin', array( 'size' => 22 ) ); ?>
										<span>
											<strong><?php echo esc_html( $df_store['name'] ); ?></strong>
											<?php if ( $df_store['address'] ) : ?>
												<small><?php echo esc_html( $df_store['address'] ); ?></small>
											<?php endif; ?>
											<?php if ( $df_store['hours'] ) : ?>
												<small class="df-store__hours"><?php echo esc_html( $df_store['hours'] ); ?></small>
											<?php endif; ?>
										</span>
									</span>
								</label>
							<?php endforeach; ?>
							<p class="df-field__hint">Siparişinizi seçtiğiniz tarih ve saat aralığında mağazamızdan teslim alabilirsiniz. Alıcı bilgileri teslimde kontrol edilir.</p>
						</div>
					<?php endif; ?>

					<?php if ( 'checkout' === $df_ctx ) : ?>
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					<?php endif; ?>
				</section>

				<?php /* 4 — Çiçek notu */ ?>
				<section class="df-step" id="df-step-note">
					<h2 class="df-step__title"><span class="df-step__num">4</span>Çiçek Notu</h2>
					<p class="df-step__desc"><?php echo esc_html( df_opt( 'note_intro' ) ); ?></p>
					<div class="df-note">
						<div class="df-note__form">
							<p class="form-row form-row-wide df-field">
								<label for="df_note_cat">Mesaj kategorisi</label>
								<select name="df_note_cat" id="df_note_cat" class="df-note__cat">
									<option value="">Kategori seçin</option>
									<?php foreach ( array_keys( $df_notes ) as $df_cat ) : ?>
										<option value="<?php echo esc_attr( $df_cat ); ?>" <?php selected( df_checkout_value( 'note_cat' ), $df_cat ); ?>><?php echo esc_html( $df_cat ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
							<div class="df-note__templates" data-df-note-templates aria-live="polite"></div>
							<?php if ( function_exists( 'df_ai_box' ) ) { df_ai_box(); } ?>
							<p class="form-row form-row-wide df-field" id="df_note_field">
								<label for="df_note">Notunuz</label>
								<textarea class="input-text" name="df_note" id="df_note" rows="4" maxlength="<?php echo esc_attr( $df_note_max ); ?>" placeholder="Kalbinizden geçenleri yazın…"><?php echo esc_textarea( $df_note ); ?></textarea>
								<span class="df-note__count"><span data-df-note-count><?php echo (int) mb_strlen( $df_note ); ?></span> / <?php echo (int) $df_note_max; ?></span>
							</p>
							<div class="df-row-2 df-row-2--center">
								<p class="form-row form-row-wide df-field">
									<label for="df_note_from">Gönderen Adı <span class="optional">(kartta görünecek)</span></label>
									<input type="text" class="input-text" name="df_note_from" id="df_note_from" value="<?php echo esc_attr( df_checkout_value( 'note_from' ) ); ?>" placeholder="Ör. Seni seven eşin">
								</p>
								<div class="df-note__checks">
									<label class="df-checkbox"><input type="checkbox" name="df_anon" value="1" <?php checked( df_checkout_value( 'anon' ), '1' ); ?>><span>İsimsiz gönder</span></label>
									<label class="df-checkbox"><input type="checkbox" name="df_no_note" value="1" <?php checked( df_checkout_value( 'no_note' ), '1' ); ?>><span>Not kartı istemiyorum</span></label>
								</div>
							</div>
						</div>
						<div class="df-note__preview" aria-hidden="true">
							<div class="df-notecard">
								<span class="df-notecard__brand"><?php echo esc_html( df_opt( 'logo_text' ) ); ?></span>
								<p class="df-notecard__text" data-df-note-preview><?php echo $df_note ? esc_html( $df_note ) : 'Notunuz burada görünecek…'; ?></p>
								<p class="df-notecard__from" data-df-note-from-preview></p>
							</div>
						</div>
					</div>
				</section>
