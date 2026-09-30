<?php
/**
 * Meta kutuları (SEO · AEO · GEO, İçerik kartı, Ürün kartı), terim alanları, yazar (E-E-A-T) alanları.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kutuları ekle.
 */
function cr_add_meta_boxes() {
	foreach ( cr_meta_schema() as $key => $box ) {
		foreach ( $box['types'] as $type ) {
			add_meta_box(
				'cr-box-' . $key,
				'seo' === $key ? '♥ Cilt Rotası — SEO · AEO · GEO' : '♥ ' . $box['title'],
				'cr_render_meta_box',
				$type,
				'normal',
				'seo' === $key ? 'high' : 'high',
				array( 'box' => $key )
			);
		}
	}
}
add_action( 'add_meta_boxes', 'cr_add_meta_boxes' );

/**
 * Meta alanı girdisi.
 *
 * @param string $key   Anahtar.
 * @param array  $f     Alan.
 * @param mixed  $value Değer.
 */
function cr_meta_input( $key, $f, $value ) {
	$name = 'cr_meta[' . $key . ']';
	$id   = 'cr-m' . $key;
	switch ( $f['type'] ) {
		case 'faq':
		case 'steps':
			$rows = is_array( $value ) ? $value : array();
			$a    = 'faq' === $f['type'] ? array( 'q', 'Soru', 'a', 'Yanıt' ) : array( 'name', 'Adım', 'text', 'Açıklama' );
			echo '<div class="cr-pairs" data-pairs data-name="' . esc_attr( $name ) . '" data-a="' . esc_attr( $a[0] ) . '" data-b="' . esc_attr( $a[2] ) . '" data-la="' . esc_attr( $a[1] ) . '" data-lb="' . esc_attr( $a[3] ) . '">';
			echo '<div data-rows>';
			foreach ( array_values( $rows ) as $i => $r ) {
				echo '<div class="cr-pair" data-row><span class="cr-row__drag">⋮⋮</span><div class="cr-pair__fields">';
				echo '<input type="text" class="cr-input" name="' . esc_attr( $name . '[' . $i . '][' . $a[0] . ']' ) . '" value="' . esc_attr( isset( $r[ $a[0] ] ) ? $r[ $a[0] ] : '' ) . '" placeholder="' . esc_attr( $a[1] ) . '">';
				echo '<textarea class="cr-input" rows="2" name="' . esc_attr( $name . '[' . $i . '][' . $a[2] . ']' ) . '" placeholder="' . esc_attr( $a[3] ) . '">' . esc_textarea( isset( $r[ $a[2] ] ) ? $r[ $a[2] ] : '' ) . '</textarea>';
				echo '</div><button type="button" class="cr-row__btn cr-danger" data-remove title="Sil">✕</button></div>';
			}
			echo '</div><button type="button" class="button" data-add-pair>+ ' . esc_html( 'faq' === $f['type'] ? 'Soru ekle' : 'Adım ekle' ) . '</button></div>';
			return;
		case 'textarea':
		case 'lines':
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . ( 'lines' === $f['type'] ? 4 : 3 ) . '" class="cr-input" data-count="' . esc_attr( $key ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
			return;
		case 'toggle':
			$on = '' === (string) $value && ! empty( $f['default'] ) ? true : ! empty( $value );
			echo '<label class="cr-switch"><input type="hidden" name="' . esc_attr( $name ) . '" value="0"><input id="' . esc_attr( $id ) . '" type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( $on, true, false ) . '><span></span></label>';
			return;
		case 'select':
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="cr-input">';
			foreach ( $f['choices'] as $k => $l ) {
				echo '<option value="' . esc_attr( $k ) . '"' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $l ) . '</option>';
			}
			echo '</select>';
			return;
		case 'date':
			echo '<input id="' . esc_attr( $id ) . '" type="date" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" class="cr-input cr-input--sm">';
			return;
		case 'number':
			echo '<input id="' . esc_attr( $id ) . '" type="number" min="0" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" class="cr-input cr-input--sm">';
			return;
		case 'image':
			cr_field_input( array( 'type' => 'image' ), $value, $name, $id );
			return;
		default:
			echo '<input id="' . esc_attr( $id ) . '" type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" class="cr-input" data-count="' . esc_attr( $key ) . '">';
	}
}

/**
 * Kutu çıktısı.
 *
 * @param WP_Post $post Yazı.
 * @param array   $args Argümanlar.
 */
function cr_render_meta_box( $post, $args ) {
	$key    = $args['args']['box'];
	$schema = cr_meta_schema();
	$box    = $schema[ $key ];
	wp_nonce_field( 'cr_meta_' . $post->ID, 'cr_meta_nonce' );
	echo '<div class="cr-admin cr-metabox" data-metabox="' . esc_attr( $key ) . '">';
	if ( 'seo' === $key ) {
		$audit = 'publish' === $post->post_status ? cr_seo_audit( $post->ID ) : null;
		if ( cr_seo_plugin() ) {
			echo '<div class="cr-notice">' . esc_html( cr_seo_plugin() ) . ' etkin: başlık/açıklama için eklentinin kutusunu kullanın. AEO ve GEO alanları bu temada çalışmaya devam eder.</div>';
		}
		?>
		<div class="cr-serp" data-serp>
			<p class="cr-serp__label">Google önizlemesi</p>
			<div class="cr-serp__box">
				<div class="cr-serp__site"><span class="cr-serp__fav">♥</span><span><strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong><small><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( get_permalink( $post ) ) ) ); ?></small></span></div>
				<p class="cr-serp__title" data-serp-title></p>
				<p class="cr-serp__desc" data-serp-desc></p>
			</div>
			<?php if ( $audit ) : ?>
				<div class="cr-serp__score"><span class="cr-score <?php echo esc_attr( cr_score_class( $audit['score'] ) ); ?>"><?php echo (int) $audit['score']; ?></span> kayıtlı sürümün SEO · AEO · GEO skoru</div>
			<?php endif; ?>
		</div>
		<div class="cr-mtabs" role="tablist">
			<button type="button" class="is-active" data-mtab="seo">SEO</button>
			<button type="button" data-mtab="aeo">AEO · Yanıt motorları</button>
			<button type="button" data-mtab="geo">GEO · Yapay zekâ & E-E-A-T</button>
			<?php if ( $audit ) : ?>
				<button type="button" data-mtab="audit">Denetim (<?php echo count( array_filter( $audit['checks'], function ( $c ) { return ! $c['ok']; } ) ); ?>)</button>
			<?php endif; ?>
		</div>
		<?php
		foreach ( array( 'seo', 'aeo', 'geo' ) as $tab ) {
			echo '<div class="cr-mpanel' . ( 'seo' === $tab ? ' is-active' : '' ) . '" data-mpanel="' . esc_attr( $tab ) . '">';
			if ( 'seo' === $tab ) {
				$tax = 'post' === $post->post_type ? 'category' : ( 'icerik' === $post->post_type ? 'icerik_grubu' : ( 'urun_rehberi' === $post->post_type ? 'urun_turu' : '' ) );
				if ( $tax ) {
					$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
					$cur   = (int) get_post_meta( $post->ID, '_cr_primary_term', true );
					echo '<div class="cr-mfield"><label for="cr-m_cr_primary_term">Ana kategori (breadcrumb ve rozet)</label><div><select id="cr-m_cr_primary_term" name="cr_meta[_cr_primary_term]" class="cr-input"><option value="0">Otomatik (ilk kategori)</option>';
					foreach ( (array) $terms as $t ) {
						echo '<option value="' . (int) $t->term_id . '"' . selected( $cur, $t->term_id, false ) . '>' . esc_html( $t->name ) . '</option>';
					}
					echo '</select></div></div>';
				}
			}
			foreach ( $box['fields'] as $k => $f ) {
				if ( ( isset( $f['tab'] ) ? $f['tab'] : 'seo' ) !== $tab ) {
					continue;
				}
				echo '<div class="cr-mfield cr-mfield--' . esc_attr( $f['type'] ) . '"><label for="cr-m' . esc_attr( $k ) . '">' . esc_html( $f['label'] ) . ' <span class="cr-counter" data-counter-for="' . esc_attr( $k ) . '"></span></label><div>';
				cr_meta_input( $k, $f, get_post_meta( $post->ID, $k, true ) );
				if ( ! empty( $f['desc'] ) ) {
					echo '<p class="cr-field__desc">' . esc_html( $f['desc'] ) . '</p>';
				}
				echo '</div></div>';
			}
			echo '</div>';
		}
		if ( $audit ) {
			echo '<div class="cr-mpanel" data-mpanel="audit"><ul class="cr-checks">';
			foreach ( $audit['checks'] as $c ) {
				echo '<li class="' . ( $c['ok'] ? 'is-ok' : 'is-bad' ) . '"><span>' . ( $c['ok'] ? '✓' : '✕' ) . '</span><em>' . esc_html( $c['group'] ) . '</em>' . esc_html( $c['label'] ) . ( ! $c['ok'] && $c['hint'] ? ' <small>— ' . esc_html( $c['hint'] ) . '</small>' : '' ) . '</li>';
			}
			echo '</ul><p class="cr-field__desc">Denetim, en son kaydedilen sürüme göre yapılır.</p></div>';
		}
		echo '<div class="cr-live-checks" data-live-checks></div>';
	} else {
		echo '<div class="cr-mgrid">';
		foreach ( $box['fields'] as $k => $f ) {
			$wide = in_array( $f['type'], array( 'lines', 'textarea' ), true );
			echo '<div class="cr-mfield' . ( $wide ? ' cr-mfield--wide' : '' ) . '"><label for="cr-m' . esc_attr( $k ) . '">' . esc_html( $f['label'] ) . '</label><div>';
			cr_meta_input( $k, $f, get_post_meta( $post->ID, $k, true ) );
			if ( ! empty( $f['desc'] ) ) {
				echo '<p class="cr-field__desc">' . esc_html( $f['desc'] ) . '</p>';
			}
			echo '</div></div>';
		}
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Kaydet.
 *
 * @param int $post_id Yazı.
 */
function cr_save_meta( $post_id ) {
	if ( ! isset( $_POST['cr_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cr_meta_nonce'] ) ), 'cr_meta_' . $post_id ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in   = isset( $_POST['cr_meta'] ) && is_array( $_POST['cr_meta'] ) ? wp_unslash( $_POST['cr_meta'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$type = get_post_type( $post_id );
	foreach ( cr_meta_fields_flat() as $key => $f ) {
		if ( ! in_array( $type, $f['types'], true ) ) {
			continue;
		}
		if ( ! array_key_exists( $key, $in ) ) {
			if ( in_array( $f['type'], array( 'faq', 'steps' ), true ) ) {
				delete_post_meta( $post_id, $key );
			}
			continue;
		}
		$v = $in[ $key ];
		switch ( $f['type'] ) {
			case 'faq':
			case 'steps':
				$a    = 'faq' === $f['type'] ? array( 'q', 'a' ) : array( 'name', 'text' );
				$rows = array();
				foreach ( (array) $v as $r ) {
					$x = isset( $r[ $a[0] ] ) ? sanitize_text_field( $r[ $a[0] ] ) : '';
					$y = isset( $r[ $a[1] ] ) ? wp_kses_post( $r[ $a[1] ] ) : '';
					if ( '' !== $x ) {
						$rows[] = array( $a[0] => $x, $a[1] => $y );
					}
				}
				$clean = $rows;
				break;
			case 'textarea':
			case 'lines':
				$clean = sanitize_textarea_field( $v );
				break;
			case 'url':
				$clean = esc_url_raw( $v );
				break;
			case 'image':
				$clean = is_numeric( $v ) ? absint( $v ) : esc_url_raw( $v );
				break;
			case 'number':
				$clean = (string) absint( $v );
				break;
			case 'toggle':
				$clean = empty( $v ) ? '0' : '1';
				break;
			case 'date':
				$clean = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? $v : '';
				break;
			case 'select':
				$clean = isset( $f['choices'] ) && array_key_exists( (string) $v, $f['choices'] ) ? (string) $v : '';
				break;
			default:
				$clean = sanitize_text_field( $v );
		}
		if ( '' === $clean || array() === $clean || ( 'number' === $f['type'] && '0' === $clean ) ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $clean );
		}
	}
}
add_action( 'save_post', 'cr_save_meta' );

/* ------------------------------------------------------------------------
 * Terim alanları.
 * --------------------------------------------------------------------- */

/**
 * Terim alanları tanımı.
 *
 * @return array
 */
function cr_term_fields() {
	return array(
		'cr_image'      => array( 'type' => 'image', 'label' => 'Görsel', 'desc' => 'Kategori sayfası, mega menü ve şeritlerde kullanılır.' ),
		'cr_hero_title' => array( 'type' => 'text', 'label' => 'Sayfa üst başlığı', 'desc' => 'Örn. “Cildin verdiği sinyalleri anlamaya başla.”' ),
		'cr_seo_title'  => array( 'type' => 'text', 'label' => 'SEO başlığı' ),
		'cr_seo_desc'   => array( 'type' => 'textarea', 'label' => 'Meta açıklama' ),
	);
}

/**
 * Yeni terim formu.
 */
function cr_term_add_fields() {
	wp_nonce_field( 'cr_term', 'cr_term_nonce' );
	foreach ( cr_term_fields() as $k => $f ) {
		echo '<div class="form-field cr-admin"><label>' . esc_html( $f['label'] ) . '</label>';
		cr_field_input( $f, '', 'cr_term[' . $k . ']', 'cr-t-' . $k );
		if ( ! empty( $f['desc'] ) ) {
			echo '<p>' . esc_html( $f['desc'] ) . '</p>';
		}
		echo '</div>';
	}
}

/**
 * Terim düzenleme formu.
 *
 * @param WP_Term $term Terim.
 */
function cr_term_edit_fields( $term ) {
	wp_nonce_field( 'cr_term', 'cr_term_nonce' );
	foreach ( cr_term_fields() as $k => $f ) {
		echo '<tr class="form-field cr-admin"><th scope="row"><label>' . esc_html( $f['label'] ) . '</label></th><td>';
		cr_field_input( $f, get_term_meta( $term->term_id, $k, true ), 'cr_term[' . $k . ']', 'cr-t-' . $k );
		if ( ! empty( $f['desc'] ) ) {
			echo '<p class="description">' . esc_html( $f['desc'] ) . '</p>';
		}
		echo '</td></tr>';
	}
}

/**
 * Terim kaydı.
 *
 * @param int $term_id Terim.
 */
function cr_term_save( $term_id ) {
	if ( ! isset( $_POST['cr_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cr_term_nonce'] ) ), 'cr_term' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$in = isset( $_POST['cr_term'] ) && is_array( $_POST['cr_term'] ) ? wp_unslash( $_POST['cr_term'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	foreach ( cr_term_fields() as $k => $f ) {
		$v = isset( $in[ $k ] ) ? $in[ $k ] : '';
		$v = 'image' === $f['type'] ? ( is_numeric( $v ) ? absint( $v ) : esc_url_raw( $v ) ) : ( 'textarea' === $f['type'] ? sanitize_textarea_field( $v ) : sanitize_text_field( $v ) );
		if ( '' === $v || 0 === $v ) {
			delete_term_meta( $term_id, $k );
		} else {
			update_term_meta( $term_id, $k, $v );
		}
	}
}

foreach ( cr_term_meta_taxonomies() as $cr_tax ) {
	add_action( $cr_tax . '_add_form_fields', 'cr_term_add_fields' );
	add_action( $cr_tax . '_edit_form_fields', 'cr_term_edit_fields' );
	add_action( 'created_' . $cr_tax, 'cr_term_save' );
	add_action( 'edited_' . $cr_tax, 'cr_term_save' );
}

/* ------------------------------------------------------------------------
 * Yazar alanları (E-E-A-T).
 * --------------------------------------------------------------------- */

/**
 * Profil alanları.
 *
 * @param WP_User $user Kullanıcı.
 */
function cr_user_fields( $user ) {
	$fields = array(
		'cr_job'         => array( 'Unvan / uzmanlık', 'Örn. Kozmetik kimyager, Dermatoloji hemşiresi, Editör' ),
		'cr_credentials' => array( 'Sertifika / eğitim', 'Örn. Ankara Üniversitesi Eczacılık Fakültesi' ),
		'cr_expertise'   => array( 'Uzmanlık konuları (her satıra bir)', '' ),
		'cr_sameas'      => array( 'Profil bağlantıları (her satıra bir URL)', 'LinkedIn, ORCID, Instagram… — Person şemasına sameAs olarak eklenir.' ),
	);
	echo '<h2 id="cr-author">♥ Cilt Rotası yazar profili (E-E-A-T)</h2><table class="form-table" role="presentation">';
	wp_nonce_field( 'cr_user', 'cr_user_nonce' );
	foreach ( $fields as $k => $f ) {
		$v     = get_user_meta( $user->ID, $k, true );
		$lines = in_array( $k, array( 'cr_expertise', 'cr_sameas' ), true );
		echo '<tr><th><label for="' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( $lines ) {
			echo '<textarea id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" rows="3" class="regular-text">' . esc_textarea( (string) $v ) . '</textarea>';
		} else {
			echo '<input id="' . esc_attr( $k ) . '" type="text" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '" class="regular-text">';
		}
		if ( $f[1] ) {
			echo '<p class="description">' . esc_html( $f[1] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}
add_action( 'show_user_profile', 'cr_user_fields' );
add_action( 'edit_user_profile', 'cr_user_fields' );

/**
 * Profil kaydı.
 *
 * @param int $user_id Kullanıcı.
 */
function cr_user_save( $user_id ) {
	if ( ! isset( $_POST['cr_user_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cr_user_nonce'] ) ), 'cr_user' ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	foreach ( array( 'cr_job', 'cr_credentials' ) as $k ) {
		update_user_meta( $user_id, $k, isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '' );
	}
	foreach ( array( 'cr_expertise', 'cr_sameas' ) as $k ) {
		update_user_meta( $user_id, $k, isset( $_POST[ $k ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $k ] ) ) : '' );
	}
}
add_action( 'personal_options_update', 'cr_user_save' );
add_action( 'edit_user_profile_update', 'cr_user_save' );
