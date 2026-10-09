<?php
/**
 * Ürün Asistanı:
 * - Başlık Havuzu (Ürünler → Başlık Havuzu): Excel'den gelen başlıklar, koleksiyon / çiçek / renk / duygu bilgisi,
 *   kullanıldı / boşta durumu, yeni başlık ekleme, CSV içe aktarma, havuzdan taslak ürün oluşturma.
 * - Ürün ekranında havuzdan seçim, aynı başlık uyarısı.
 * - Yapay zekâ ile içerik (ChatGPT / Gemini / Claude): kısa ve uzun açıklama, SEO başlığı ve açıklaması,
 *   odak anahtar kelime, görsel ALT metni, etiket. Taslak olarak gelir, yönetici onaylayıp kaydeder.
 * - SEO alanları Rank Math / Yoast alanlarına yazılır; eklenti yoksa tema basar.
 * - Etiketi olmayan ürünlere otomatik etiket.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Başlık havuzu verisi
 * ---------------------------------------------------------------------- */

/**
 * Havuz alanları.
 *
 * @return array<string,string>
 */
function df_pool_fields() {
	return array(
		'en'     => 'Başlık (EN)',
		'tr'     => 'Türkçe anlamı',
		'col'    => 'Koleksiyon',
		'cat'    => 'Kategori',
		'flower' => 'Çiçek türü',
		'color'  => 'Renk',
		'occ'    => 'Duygu / özel gün',
		'level'  => 'Seviye',
		'note'   => 'Not',
	);
}

/**
 * Temayla gelen başlıklar (Excel: Ürün Başlık Havuzu).
 *
 * @return array
 */
function df_pool_bundled() {
	$file = DF_DIR . '/data/title-pool.json';
	$rows = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return is_array( $rows ) ? $rows : array();
}

/**
 * Bir satırı temizle.
 *
 * @param array $r Satır.
 * @return array|null
 */
function df_pool_clean_row( $r ) {
	$out = array();
	foreach ( array_keys( df_pool_fields() ) as $k ) {
		$out[ $k ] = isset( $r[ $k ] ) ? sanitize_text_field( (string) $r[ $k ] ) : '';
	}
	if ( '' === $out['en'] ) {
		return null;
	}
	$out['id'] = ! empty( $r['id'] ) ? sanitize_key( $r['id'] ) : substr( md5( strtolower( $out['en'] ) ), 0, 10 );
	return $out;
}

/**
 * Tüm havuz (ilk kullanımda Excel başlıklarıyla dolar; ayarlardaki ek başlıklar da eklenir).
 *
 * @return array<string, array>
 */
function df_pool_all() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$saved = get_option( 'df_title_pool', null );
	if ( ! is_array( $saved ) ) {
		$saved = array();
		foreach ( df_pool_bundled() as $r ) {
			$r = df_pool_clean_row( $r );
			if ( $r ) {
				$saved[ $r['id'] ] = $r;
			}
		}
		update_option( 'df_title_pool', $saved, false );
	}
	foreach ( df_lines( df_opt( 'title_pool' ) ) as $line ) {
		$r = df_pool_clean_row( array( 'en' => $line ) );
		if ( $r && ! isset( $saved[ $r['id'] ] ) ) {
			$r['note'] = 'Ayarlardaki ek başlık';
			$saved[ $r['id'] ] = $r;
		}
	}
	$cache = $saved;
	return $cache;
}

/**
 * Havuzu kaydet.
 *
 * @param array $rows Satırlar.
 */
function df_pool_save( $rows ) {
	update_option( 'df_title_pool', $rows, false );
}

/**
 * Kullanılmış başlıklar: havuz kimliği => ürün kimliği.
 *
 * @return array<string,int>
 */
function df_pool_used() {
	static $used = null;
	if ( null !== $used ) {
		return $used;
	}
	global $wpdb;
	$used     = array();
	$products = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status NOT IN ('trash','auto-draft')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$titles   = array();
	foreach ( $products as $p ) {
		$titles[ (int) $p->ID ] = df_pool_norm( $p->post_title );
	}
	$linked = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_df_pool_id'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( $linked as $l ) {
		if ( isset( $titles[ (int) $l->post_id ] ) ) {
			$used[ $l->meta_value ] = (int) $l->post_id;
		}
	}
	foreach ( df_pool_all() as $id => $r ) {
		if ( isset( $used[ $id ] ) ) {
			continue;
		}
		$n = df_pool_norm( $r['en'] );
		foreach ( $titles as $pid => $t ) {
			if ( $t === $n || ( strlen( $n ) > 6 && false !== strpos( $t, $n ) ) ) {
				$used[ $id ] = $pid;
				break;
			}
		}
	}
	return $used;
}

/**
 * Karşılaştırma için sadeleştir.
 *
 * @param string $s Metin.
 * @return string
 */
function df_pool_norm( $s ) {
	$s = function_exists( 'df_loc_ascii' ) ? df_loc_ascii( $s ) : strtolower( remove_accents( $s ) );
	return trim( preg_replace( '/[^a-z0-9]+/', ' ', $s ) );
}

/* -------------------------------------------------------------------------
 * Başlık Havuzu yönetim sayfası
 * ---------------------------------------------------------------------- */

/**
 * Menü.
 */
function df_pool_menu() {
	add_submenu_page( 'edit.php?post_type=product', 'Başlık Havuzu', 'Başlık Havuzu', 'edit_products', 'df-title-pool', 'df_pool_page' );
}
add_action( 'admin_menu', 'df_pool_menu', 30 );

/**
 * İşlemler (ekle, sil, içe aktar, taslak ürün).
 */
function df_pool_actions() {
	if ( ! current_user_can( 'edit_products' ) || ! check_admin_referer( 'df_pool' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$do   = isset( $_REQUEST['do'] ) ? sanitize_key( wp_unslash( $_REQUEST['do'] ) ) : '';
	$rows = df_pool_all();
	$msg  = '';
	if ( 'add' === $do ) {
		$r = df_pool_clean_row( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( $r ) {
			if ( isset( $rows[ $r['id'] ] ) ) {
				$msg = 'Bu başlık havuzda zaten var.';
			} else {
				$rows[ $r['id'] ] = $r;
				df_pool_save( $rows );
				$msg = '"' . $r['en'] . '" havuza eklendi.';
			}
		}
	} elseif ( 'delete' === $do && isset( $_GET['id'] ) ) {
		unset( $rows[ sanitize_key( wp_unslash( $_GET['id'] ) ) ] );
		df_pool_save( $rows );
		$msg = 'Başlık havuzdan silindi.';
	} elseif ( 'reload' === $do ) {
		$n = 0;
		foreach ( df_pool_bundled() as $r ) {
			$r = df_pool_clean_row( $r );
			if ( $r && ! isset( $rows[ $r['id'] ] ) ) {
				$rows[ $r['id'] ] = $r;
				++$n;
			}
		}
		df_pool_save( $rows );
		$msg = $n . ' başlık Excel havuzundan eklendi.';
	} elseif ( 'import' === $do && ! empty( $_FILES['csv']['tmp_name'] ) ) {
		$n  = 0;
		$fh = fopen( $_FILES['csv']['tmp_name'], 'r' ); // phpcs:ignore
		if ( $fh ) {
			$first = fgets( $fh );
			$delim = substr_count( (string) $first, ';' ) > substr_count( (string) $first, ',' ) ? ';' : ',';
			rewind( $fh );
			$head = true;
			while ( ( $c = fgetcsv( $fh, 0, $delim ) ) !== false ) { // phpcs:ignore
				if ( $head ) {
					$head = false;
					if ( isset( $c[1] ) && false !== stripos( (string) $c[1], 'başlık' ) ) {
						continue; // Başlık satırı.
					}
				}
				$c = array_map(
					function ( $v ) {
						return trim( (string) preg_replace( '/^\xEF\xBB\xBF/', '', (string) $v ) );
					},
					$c
				);
				// Excel sırası: No, EN, TR, Koleksiyon, Kategori, Çiçek, Renk, Duygu, Seviye, Durum, Kullanım, Not.
				$offset = is_numeric( $c[0] ) ? 1 : 0;
				$r      = df_pool_clean_row(
					array(
						'en'     => isset( $c[ $offset ] ) ? $c[ $offset ] : '',
						'tr'     => isset( $c[ $offset + 1 ] ) ? $c[ $offset + 1 ] : '',
						'col'    => isset( $c[ $offset + 2 ] ) ? $c[ $offset + 2 ] : '',
						'cat'    => isset( $c[ $offset + 3 ] ) ? $c[ $offset + 3 ] : '',
						'flower' => isset( $c[ $offset + 4 ] ) ? $c[ $offset + 4 ] : '',
						'color'  => isset( $c[ $offset + 5 ] ) ? $c[ $offset + 5 ] : '',
						'occ'    => isset( $c[ $offset + 6 ] ) ? $c[ $offset + 6 ] : '',
						'level'  => isset( $c[ $offset + 7 ] ) ? $c[ $offset + 7 ] : '',
						'note'   => isset( $c[ $offset + 10 ] ) ? $c[ $offset + 10 ] : '',
					)
				);
				if ( $r && ! isset( $rows[ $r['id'] ] ) ) {
					$rows[ $r['id'] ] = $r;
					++$n;
				}
			}
			fclose( $fh ); // phpcs:ignore
		}
		df_pool_save( $rows );
		$msg = $n . ' yeni başlık içe aktarıldı.';
	} elseif ( 'draft' === $do && isset( $_GET['id'] ) ) {
		$id = sanitize_key( wp_unslash( $_GET['id'] ) );
		if ( isset( $rows[ $id ] ) ) {
			$pid = df_pool_create_draft( $rows[ $id ] );
			if ( $pid ) {
				wp_safe_redirect( add_query_arg( 'df_pai', 1, get_edit_post_link( $pid, 'raw' ) ) );
				exit;
			}
		}
	}
	wp_safe_redirect( add_query_arg( 'df_msg', rawurlencode( $msg ), admin_url( 'edit.php?post_type=product&page=df-title-pool' ) ) );
	exit;
}
add_action( 'admin_post_df_pool', 'df_pool_actions' );

/**
 * Havuz başlığından taslak ürün oluştur.
 *
 * @param array $r Satır.
 * @return int Ürün.
 */
function df_pool_create_draft( $r ) {
	$p = new WC_Product_Simple();
	$p->set_name( $r['en'] );
	$p->set_status( 'draft' );
	$cats = df_pool_match_cats( $r );
	if ( $cats ) {
		$p->set_category_ids( $cats );
	}
	$id = $p->save();
	if ( $id ) {
		update_post_meta( $id, '_df_pool_id', $r['id'] );
		if ( '' !== $r['tr'] ) {
			update_post_meta( $id, '_df_subtitle', $r['tr'] );
		}
	}
	return $id;
}

/**
 * Havuz satırındaki kategori / çiçek adlarını mağaza kategorileriyle eşle (Gül → Güller).
 *
 * @param array $r Satır.
 * @return int[]
 */
function df_pool_match_cats( $r ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$want = array();
	foreach ( preg_split( '/[\/,]+/', $r['cat'] . '/' . $r['flower'] ) as $w ) {
		$w = df_pool_norm( $w );
		if ( strlen( $w ) > 2 && ! in_array( $w, array( 'genel', 'karma', 'premium' ), true ) ) {
			$want[] = $w;
		}
	}
	$ids = array();
	foreach ( $terms as $t ) {
		$n = df_pool_norm( $t->name );
		foreach ( $want as $w ) {
			if ( $n === $w || $n === $w . 'ler' || $n === $w . 'lar' || $n === $w . 'leri' || $n === $w . 'lari' ) {
				$ids[] = (int) $t->term_id;
			}
		}
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Sayfa.
 */
function df_pool_page() {
	$rows  = df_pool_all();
	$used  = df_pool_used();
	$cols  = array_unique( array_filter( wp_list_pluck( $rows, 'col' ) ) );
	$nonce = wp_create_nonce( 'df_pool' );
	$base  = admin_url( 'admin-post.php?action=df_pool&_wpnonce=' . $nonce );
	sort( $cols );
	?>
	<div class="wrap df-pool">
		<h1 class="wp-heading-inline">Ürün Başlık Havuzu</h1>
		<p class="description">Yeni ürün eklerken başlığı buradan seçin. Kullanılmış başlıklar işaretlenir; aynı isim iki üründe kullanılmaz. "Taslak ürün oluştur" başlığı, Türkçe alt başlığı ve uygun kategoriyi doldurup ürünü açar; Ürün Asistanı ile içeriği hazırlayıp yayınlarsınız.</p>
		<?php if ( isset( $_GET['df_msg'] ) && '' !== $_GET['df_msg'] ) : // phpcs:ignore ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['df_msg'] ) ) ); // phpcs:ignore ?></p></div>
		<?php endif; ?>
		<div class="df-pool__stats">
			<span><strong><?php echo count( $rows ); ?></strong> başlık</span>
			<span class="is-free"><strong><?php echo count( $rows ) - count( $used ); ?></strong> boşta</span>
			<span class="is-used"><strong><?php echo count( $used ); ?></strong> kullanıldı</span>
		</div>
		<div class="df-pool__bar">
			<input type="search" placeholder="Başlık, Türkçe anlam, renk, duygu ara…" data-pool-q>
			<select data-pool-col><option value="">Tüm koleksiyonlar</option><?php foreach ( $cols as $c ) : ?><option><?php echo esc_html( $c ); ?></option><?php endforeach; ?></select>
			<select data-pool-st><option value="">Hepsi</option><option value="free">Boşta</option><option value="used">Kullanıldı</option></select>
		</div>
		<table class="widefat striped df-pool__table">
			<thead><tr><th>Başlık</th><th>Türkçe</th><th>Koleksiyon</th><th>Çiçek · renk</th><th>Duygu / özel gün</th><th>Seviye</th><th>Durum</th><th></th></tr></thead>
			<tbody>
			<?php foreach ( $rows as $id => $r ) : ?>
				<?php $u = isset( $used[ $id ] ) ? $used[ $id ] : 0; ?>
				<tr data-st="<?php echo $u ? 'used' : 'free'; ?>" data-col="<?php echo esc_attr( $r['col'] ); ?>" data-q="<?php echo esc_attr( mb_strtolower( implode( ' ', $r ) ) ); ?>">
					<td><strong><?php echo esc_html( $r['en'] ); ?></strong></td>
					<td><?php echo esc_html( $r['tr'] ); ?></td>
					<td><?php echo esc_html( $r['col'] ); ?></td>
					<td><?php echo esc_html( trim( $r['flower'] . ' · ' . $r['color'], ' ·' ) ); ?></td>
					<td><?php echo esc_html( $r['occ'] ); ?></td>
					<td><?php echo esc_html( $r['level'] ); ?></td>
					<td><?php echo $u ? '<a class="df-pool__pill is-used" href="' . esc_url( get_edit_post_link( $u ) ) . '">Kullanıldı</a>' : '<span class="df-pool__pill">Boşta</span>'; ?></td>
					<td class="df-pool__acts">
						<?php if ( ! $u ) : ?>
							<a class="button button-small button-primary" href="<?php echo esc_url( $base . '&do=draft&id=' . $id ); ?>">Taslak ürün oluştur</a>
						<?php endif; ?>
						<a class="button-link-delete" href="<?php echo esc_url( $base . '&do=delete&id=' . $id ); ?>" onclick="return confirm('Başlık havuzdan silinsin mi?')">Sil</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div class="df-pool__grid">
			<form class="df-pool__card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<h2>Yeni başlık ekle</h2>
				<input type="hidden" name="action" value="df_pool"><input type="hidden" name="do" value="add"><?php wp_nonce_field( 'df_pool' ); ?>
				<?php foreach ( df_pool_fields() as $k => $label ) : ?>
					<label><span><?php echo esc_html( $label ); ?></span><input type="text" name="<?php echo esc_attr( $k ); ?>" <?php echo 'en' === $k ? 'required' : ''; ?>></label>
				<?php endforeach; ?>
				<button class="button button-primary">Havuza ekle</button>
			</form>
			<div class="df-pool__card">
				<h2>Toplu yükleme</h2>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="df_pool"><input type="hidden" name="do" value="import"><?php wp_nonce_field( 'df_pool' ); ?>
					<p>Excel'de "Ürün Başlık Havuzu" sayfasını <strong>CSV olarak kaydedin</strong> ve yükleyin (sütun sırası: No, Başlık, Türkçe, Koleksiyon, Kategori, Çiçek, Renk, Duygu, Seviye…). Havuzda olan başlıklar tekrar eklenmez.</p>
					<input type="file" name="csv" accept=".csv,text/csv" required> <button class="button">CSV yükle</button>
				</form>
				<hr>
				<p>Temayla gelen Excel havuzu (<?php echo count( df_pool_bundled() ); ?> başlık). Silinenleri geri getirmek için:</p>
				<a class="button" href="<?php echo esc_url( $base . '&do=reload' ); ?>">Excel havuzunu tekrar yükle</a>
			</div>
		</div>
	</div>
	<style>
		.df-pool__stats{display:flex;gap:10px;margin:14px 0}.df-pool__stats span{background:#fff;border:1px solid #e3ddd6;border-radius:8px;padding:8px 14px}
		.df-pool__stats .is-free strong{color:#3f5e45}.df-pool__stats .is-used strong{color:#8e5e52}
		.df-pool__bar{display:flex;gap:8px;margin:0 0 10px;flex-wrap:wrap}.df-pool__bar input{min-width:320px}
		.df-pool__pill{display:inline-block;padding:2px 10px;border-radius:20px;background:#e7efe8;color:#3f5e45;font-size:12px;text-decoration:none}
		.df-pool__pill.is-used{background:#f3e7e1;color:#8e5e52}.df-pool__acts{white-space:nowrap}.df-pool__acts .button{margin-right:8px}
		.df-pool__grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px}.df-pool__card{background:#fff;border:1px solid #e3ddd6;border-radius:10px;padding:16px 20px}
		.df-pool__card label{display:grid;grid-template-columns:150px 1fr;align-items:center;gap:8px;margin-bottom:6px}.df-pool__card h2{margin-top:0}
		@media(max-width:1000px){.df-pool__grid{grid-template-columns:1fr}}
	</style>
	<script>
	( function () {
		var q = document.querySelector( '[data-pool-q]' ), c = document.querySelector( '[data-pool-col]' ), st = document.querySelector( '[data-pool-st]' );
		function run() {
			var v = q.value.toLocaleLowerCase( 'tr' ).trim();
			document.querySelectorAll( '.df-pool__table tbody tr' ).forEach( function ( tr ) {
				var ok = ( ! v || tr.getAttribute( 'data-q' ).indexOf( v ) > -1 ) && ( ! c.value || tr.getAttribute( 'data-col' ) === c.value ) && ( ! st.value || tr.getAttribute( 'data-st' ) === st.value );
				tr.style.display = ok ? '' : 'none';
			} );
		}
		[ q, c, st ].forEach( function ( el ) { el.addEventListener( 'input', run ); el.addEventListener( 'change', run ); } );
	}() );
	</script>
	<?php
}

/* -------------------------------------------------------------------------
 * Ürün ekranı: Ürün Asistanı kutusu
 * ---------------------------------------------------------------------- */

/**
 * Kutu kaydı.
 */
function df_pai_box_register() {
	add_meta_box( 'df-pai', 'Ürün Asistanı — başlık havuzu, yapay zekâ içerik & SEO', 'df_pai_box', 'product', 'normal', 'high' );
}
add_action( 'add_meta_boxes_product', 'df_pai_box_register', 5 );

/**
 * SEO meta anahtarları (Rank Math, Yoast ve tema).
 *
 * @param string $field title|desc|kw.
 * @return string[]
 */
function df_seo_keys( $field ) {
	$map = array(
		'title' => array( '_df_seo_title', 'rank_math_title', '_yoast_wpseo_title' ),
		'desc'  => array( '_df_seo_desc', 'rank_math_description', '_yoast_wpseo_metadesc' ),
		'kw'    => array( '_df_seo_kw', 'rank_math_focus_keyword', '_yoast_wpseo_focuskw' ),
	);
	return $map[ $field ];
}

/**
 * Kayıtlı SEO değeri.
 *
 * @param int    $id    Ürün.
 * @param string $field Alan.
 * @return string
 */
function df_seo_get( $id, $field ) {
	foreach ( df_seo_keys( $field ) as $k ) {
		$v = (string) get_post_meta( $id, $k, true );
		if ( '' !== $v ) {
			return $v;
		}
	}
	return '';
}

/**
 * Kutu.
 *
 * @param WP_Post $post Ürün.
 */
function df_pai_box( $post ) {
	$rows    = df_pool_all();
	$used    = df_pool_used();
	$pool_id = (string) get_post_meta( $post->ID, '_df_pool_id', true );
	$cur     = isset( $rows[ $pool_id ] ) ? $rows[ $pool_id ] : null;
	$prov    = df_opt( 'pai_provider', 'openai' );
	$has_key = '' !== df_pai_key( $prov );
	$names   = array(
		'openai' => 'ChatGPT',
		'gemini' => 'Gemini',
		'claude' => 'Claude',
	);
	$thumb   = get_post_thumbnail_id( $post->ID );
	wp_nonce_field( 'df_pai_save', 'df_pai_nonce' );
	?>
	<div class="df-pai" data-df-pai data-auto="<?php echo isset( $_GET['df_pai'] ) ? '1' : '0'; // phpcs:ignore ?>">
		<div class="df-pai__row">
			<label class="df-pai__lbl" for="df-pai-pool">1 · Başlık havuzundan seç</label>
			<div class="df-pai__pool">
				<input type="text" id="df-pai-pool" list="df-pai-pool-list" placeholder="Yazmaya başlayın: Whispers, Kalbimin, kırmızı, yıldönümü…" autocomplete="off">
				<datalist id="df-pai-pool-list">
					<?php foreach ( $rows as $id => $r ) : ?>
						<option value="<?php echo esc_attr( $r['en'] ); ?>" data-id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( ( $r['tr'] ? $r['tr'] . ' · ' : '' ) . $r['col'] . ( isset( $used[ $id ] ) && (int) $used[ $id ] !== (int) $post->ID ? ' · KULLANILDI' : '' ) ); ?></option>
					<?php endforeach; ?>
				</datalist>
				<input type="hidden" name="df_pool_id" value="<?php echo esc_attr( $pool_id ); ?>" data-pai-pool-id>
			</div>
			<p class="df-pai__meta" data-pai-meta><?php echo $cur ? esc_html( df_pai_meta_line( $cur ) ) : 'Seçince ürün adı, Türkçe alt başlık ve çiçek / renk / duygu bilgileri doldurulur.'; ?></p>
			<p class="df-pai__warn" data-pai-warn hidden></p>
		</div>

		<div class="df-pai__row">
			<label class="df-pai__lbl" for="df-pai-notes">2 · Ürün bilgileri (yapay zekâya verilir)</label>
			<textarea id="df-pai-notes" rows="2" data-pai-notes placeholder="Örn. 21 adet kırmızı gül, okaliptüs, siyah kutu, sevgiliye ve yıldönümü için"><?php echo $cur ? esc_textarea( df_pai_meta_line( $cur ) ) : ''; ?></textarea>
			<div class="df-pai__go">
				<button type="button" class="button button-primary button-large" data-pai-go<?php disabled( ! df_opt( 'pai_on', 1 ) ); ?>>✨ <?php echo $has_key ? esc_html( $names[ $prov ] . ' ile içerik oluştur' ) : 'Şablonla içerik oluştur'; ?></button>
				<span class="df-pai__hint"><?php echo $has_key ? 'Taslak gelir; kontrol edip "Ürüne uygula" deyin, sonra ürünü güncelleyin.' : 'Yapay zekâ anahtarı girilmemiş: içerik hazır şablonla üretilir. Anahtar için Site Ayarları → Ürün & Mağaza → Yapay zekâ ürün içeriği.'; ?></span>
				<span class="spinner" data-pai-spin></span>
			</div>
			<p class="df-pai__err" data-pai-err hidden></p>
		</div>

		<div class="df-pai__result" data-pai-result hidden>
			<label class="df-pai__lbl">3 · Taslak (düzenleyebilirsiniz)</label>
			<label>Kısa açıklama<textarea rows="2" data-pai-f="short"></textarea></label>
			<label>Uzun açıklama<textarea rows="7" data-pai-f="long"></textarea></label>
			<button type="button" class="button button-primary" data-pai-apply>Ürüne uygula</button>
			<span class="df-pai__ok" data-pai-ok hidden>Uygulandı ✓ Kontrol edip sağdaki "Güncelle / Yayınla" düğmesine basın.</span>
		</div>

		<div class="df-pai__row df-pai__seo">
			<label class="df-pai__lbl">SEO (Google'da görünecek)</label>
			<label>SEO başlığı <small data-pai-count="seo_title"></small><input type="text" name="df_seo_title" data-pai-f="seo_title" maxlength="80" value="<?php echo esc_attr( df_seo_get( $post->ID, 'title' ) ); ?>" placeholder="Örn. Kırmızı Gül Buketi | İzmir Aynı Gün Teslimat"></label>
			<label>SEO açıklaması <small data-pai-count="seo_desc"></small><textarea name="df_seo_desc" rows="2" data-pai-f="seo_desc" maxlength="200"><?php echo esc_textarea( df_seo_get( $post->ID, 'desc' ) ); ?></textarea></label>
			<div class="df-pai__two">
				<label>Odak anahtar kelime<input type="text" name="df_seo_kw" data-pai-f="keyword" value="<?php echo esc_attr( df_seo_get( $post->ID, 'kw' ) ); ?>"></label>
				<label>Görsel ALT metni<input type="text" name="df_img_alt" data-pai-f="alt" value="<?php echo esc_attr( $thumb ? (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) : '' ); ?>"></label>
			</div>
			<label>Etiketler (virgülle — kaydedince ürüne eklenir)<input type="text" name="df_tags_add" data-pai-f="tags" placeholder="kırmızı gül, sevgiliye çiçek, izmir çiçek siparişi"></label>
		</div>
	</div>
	<style>
		.df-pai__row{margin-bottom:16px}.df-pai__lbl{display:block;font-weight:600;font-size:13px;margin-bottom:6px;color:#2b2522}
		.df-pai input[type=text],.df-pai textarea{width:100%}.df-pai label{display:block;margin-bottom:10px}
		.df-pai__meta{margin:6px 0 0;color:#6b625c;font-size:12px}.df-pai__warn,.df-pai__err{margin:6px 0 0;padding:8px 12px;border-radius:6px;background:#fdecea;color:#8a1f11}
		.df-pai__go{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:8px}.df-pai__hint{color:#6b625c;font-size:12px;max-width:520px}
		.df-pai__result{background:#faf7f2;border:1px solid #eadfd5;border-radius:10px;padding:14px;margin-bottom:16px}
		.df-pai__ok{margin-left:10px;color:#3f5e45;font-weight:600}.df-pai__two{display:grid;grid-template-columns:1fr 1fr;gap:12px}
		.df-pai small{font-weight:400;color:#6b625c}.df-pai small.is-bad{color:#b32d2e}.df-pai .spinner{float:none;margin:0}
		.df-pai__seo{border-top:1px solid #eee;padding-top:14px}
	</style>
	<script>
	( function () {
		var box = document.querySelector( '[data-df-pai]' );
		if ( ! box ) { return; }
		var rows = <?php echo wp_json_encode( df_pai_pool_js( $rows, $used, $post->ID ) ); ?>;
		var nonce = <?php echo wp_json_encode( wp_create_nonce( 'df_pai' ) ); ?>;
		var $ = function ( s ) { return box.querySelector( s ); };
		var title = document.getElementById( 'title' );
		var pool = $( '#df-pai-pool' ), warn = $( '[data-pai-warn]' ), notes = $( '[data-pai-notes]' );
		function f( k ) { return box.querySelector( '[data-pai-f="' + k + '"]' ); }
		function count() {
			[ [ 'seo_title', 60 ], [ 'seo_desc', 155 ] ].forEach( function ( p ) {
				var n = f( p[0] ).value.length, el = box.querySelector( '[data-pai-count="' + p[0] + '"]' );
				el.textContent = '(' + n + ' / ' + p[1] + ')';
				el.classList.toggle( 'is-bad', n > p[1] || ( n > 0 && n < p[1] * 0.5 ) );
			} );
		}
		[ 'seo_title', 'seo_desc' ].forEach( function ( k ) { f( k ).addEventListener( 'input', count ); } );
		count();
		function setWarn( t ) { warn.hidden = ! t; warn.innerHTML = t || ''; }
		function check() {
			var v = ( title && title.value || '' ).trim();
			if ( v.length < 3 ) { setWarn( '' ); return; }
			var fd = new FormData();
			fd.append( 'action', 'df_title_check' ); fd.append( 'nonce', nonce ); fd.append( 'title', v ); fd.append( 'id', <?php echo (int) $post->ID; ?> );
			fetch( ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
				setWarn( r && r.success && r.data.length ? '⚠ Bu başlık daha önce kullanıldı: ' + r.data.map( function ( p ) { return '<a href="' + p.url + '" target="_blank">' + p.title + ' (' + p.sku + ')</a>'; } ).join( ', ' ) + '. Havuzdan farklı bir başlık seçin.' : '' );
			} );
		}
		var t;
		if ( title ) {
			title.addEventListener( 'input', function () { clearTimeout( t ); t = setTimeout( check, 600 ); } );
			if ( title.value ) { check(); }
		}
		pool.addEventListener( 'change', function () {
			var r = rows.filter( function ( x ) { return x.en === pool.value; } )[0];
			if ( ! r ) { return; }
			if ( r.used ) { setWarn( '⚠ "' + r.en + '" başka bir üründe kullanıldı. Yine de seçebilirsiniz ama farklı başlık önerilir.' ); }
			if ( title ) { title.value = r.en; title.dispatchEvent( new Event( 'input' ) ); var lab = document.getElementById( 'title-prompt-text' ); if ( lab ) { lab.classList.add( 'screen-reader-text' ); } }
			var sub = document.getElementById( 'df_subtitle' );
			if ( sub && r.tr ) { sub.value = r.tr; }
			$( '[data-pai-pool-id]' ).value = r.id;
			$( '[data-pai-meta]' ).textContent = r.meta;
			notes.value = r.meta;
			check();
		} );
		function editorSet( id, html ) {
			if ( window.tinymce && tinymce.get( id ) && ! tinymce.get( id ).isHidden() ) {
				tinymce.get( id ).setContent( html );
			}
			var ta = document.getElementById( id );
			if ( ta ) { ta.value = html; }
		}
		function cats() {
			return Array.prototype.map.call( document.querySelectorAll( '#product_catchecklist input:checked' ), function ( i ) {
				return i.parentNode.textContent.trim();
			} ).join( ', ' );
		}
		function go() {
			var btn = $( '[data-pai-go]' ), spin = $( '[data-pai-spin]' ), err = $( '[data-pai-err]' );
			if ( ! title || ! title.value.trim() ) { err.hidden = false; err.textContent = 'Önce ürün başlığını yazın ya da havuzdan seçin.'; return; }
			err.hidden = true; btn.disabled = true; spin.classList.add( 'is-active' );
			var fd = new FormData();
			fd.append( 'action', 'df_pai_generate' ); fd.append( 'nonce', nonce );
			fd.append( 'title', title.value ); fd.append( 'notes', notes.value ); fd.append( 'cats', cats() );
			fd.append( 'subtitle', ( document.getElementById( 'df_subtitle' ) || {} ).value || '' );
			fd.append( 'price', ( document.getElementById( '_regular_price' ) || {} ).value || '' );
			fd.append( 'pool', $( '[data-pai-pool-id]' ).value );
			fetch( ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
				if ( ! r || ! r.success ) { throw new Error( r && r.data ? r.data : 'Yanıt alınamadı.' ); }
				var d = r.data;
				f( 'short' ).value = d.short || ''; f( 'long' ).value = d.long || '';
				f( 'seo_title' ).value = d.seo_title || ''; f( 'seo_desc' ).value = d.seo_desc || '';
				f( 'keyword' ).value = d.keyword || ''; f( 'alt' ).value = d.alt || ''; f( 'tags' ).value = ( d.tags || [] ).join( ', ' );
				$( '[data-pai-result]' ).hidden = false; $( '[data-pai-ok]' ).hidden = true;
				if ( d.note ) { err.hidden = false; err.style.background = '#fff7e6'; err.style.color = '#7a5200'; err.textContent = d.note; }
				count();
				$( '[data-pai-result]' ).scrollIntoView( { behavior: 'smooth', block: 'center' } );
			} ).catch( function ( e ) {
				err.hidden = false; err.style.background = ''; err.style.color = ''; err.textContent = 'İçerik oluşturulamadı: ' + e.message;
			} ).finally( function () { btn.disabled = false; spin.classList.remove( 'is-active' ); } );
		}
		$( '[data-pai-go]' ).addEventListener( 'click', go );
		$( '[data-pai-apply]' ).addEventListener( 'click', function () {
			editorSet( 'excerpt', f( 'short' ).value.replace( /\n/g, '<br>' ) );
			editorSet( 'content', f( 'long' ).value );
			$( '[data-pai-ok]' ).hidden = false;
		} );
		if ( '1' === box.getAttribute( 'data-auto' ) ) {
			box.scrollIntoView( { block: 'start' } );
		}
	}() );
	</script>
	<?php
}

/**
 * Havuz satırının bilgi satırı.
 *
 * @param array $r Satır.
 * @return string
 */
function df_pai_meta_line( $r ) {
	$parts = array();
	foreach ( array( 'flower' => 'Çiçek', 'color' => 'Renk', 'occ' => 'Özel gün', 'col' => 'Koleksiyon', 'level' => 'Seviye' ) as $k => $l ) {
		if ( '' !== $r[ $k ] && 'Çeşitli' !== $r[ $k ] ) {
			$parts[] = $l . ': ' . $r[ $k ];
		}
	}
	return implode( ' · ', $parts );
}

/**
 * İstemci için havuz.
 *
 * @param array $rows Havuz.
 * @param array $used Kullanılanlar.
 * @param int   $pid  Bu ürün.
 * @return array
 */
function df_pai_pool_js( $rows, $used, $pid ) {
	$out = array();
	foreach ( $rows as $id => $r ) {
		$out[] = array(
			'id'   => $id,
			'en'   => $r['en'],
			'tr'   => $r['tr'],
			'meta' => df_pai_meta_line( $r ),
			'used' => isset( $used[ $id ] ) && (int) $used[ $id ] !== (int) $pid,
		);
	}
	return $out;
}

/**
 * AJAX: aynı başlıklı ürünler.
 */
function df_title_check() {
	check_ajax_referer( 'df_pai', 'nonce' );
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( null, 403 );
	}
	global $wpdb;
	$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status NOT IN ('trash','auto-draft') AND LOWER(post_title) = LOWER(%s) AND ID <> %d LIMIT 5", $title, $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$out   = array();
	foreach ( $ids as $pid ) {
		$p     = wc_get_product( $pid );
		$out[] = array(
			'title' => esc_html( get_the_title( $pid ) ),
			'sku'   => esc_html( $p && $p->get_sku() ? $p->get_sku() : '#' . $pid ),
			'url'   => esc_url( get_edit_post_link( $pid, 'raw' ) ),
		);
	}
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_df_title_check', 'df_title_check' );

/* -------------------------------------------------------------------------
 * Yapay zekâ
 * ---------------------------------------------------------------------- */

/**
 * Sağlayıcı anahtarı.
 *
 * @param string $prov Sağlayıcı.
 * @return string
 */
function df_pai_key( $prov ) {
	$map = array(
		'openai' => 'pai_openai_key',
		'gemini' => 'pai_gemini_key',
		'claude' => 'ai_key',
	);
	return isset( $map[ $prov ] ) ? trim( (string) df_opt( $map[ $prov ] ) ) : '';
}

/**
 * Talimat metni.
 *
 * @param array $in Girdiler.
 * @return string
 */
function df_pai_prompt( $in ) {
	return "Bir Türk çiçekçinin e-ticaret sitesi için ürün içeriği yazan, Google SEO kurallarını bilen bir metin yazarısın.\n"
		. 'Marka: ' . df_opt( 'pai_brand' ) . "\n\n"
		. 'Ürün adı: ' . $in['title'] . "\n"
		. ( $in['subtitle'] ? 'Türkçe anlamı / alt başlık: ' . $in['subtitle'] . "\n" : '' )
		. ( $in['cats'] ? 'Kategoriler: ' . $in['cats'] . "\n" : '' )
		. ( $in['price'] ? 'Fiyat: ' . $in['price'] . " TL\n" : '' )
		. ( $in['notes'] ? 'Ürün bilgileri: ' . $in['notes'] . "\n" : '' )
		. "\nKurallar:\n"
		. "- Türkçe yaz. Doğal, sıcak ve zarif; abartı ve klişe yok, emoji yok.\n"
		. "- Verilmeyen bilgi uydurma (çiçek adedi, boy, içerik verilmediyse yazma).\n"
		. "- Anahtar kelimeleri doğal kullan, aynı kelimeyi tekrar tekrar yığma.\n"
		. "- short: 1-2 cümle, en fazla 220 karakter.\n"
		. "- long: 120-200 kelime; 2-3 kısa paragraf (paragraflar arasında boş satır) ve en sonda 'Kimler için uygun' gibi kısa bir madde listesi (satır başı '- ').\n"
		. "- seo_title: en fazla 60 karakter, ürün adını ve doğal biçimde 'İzmir' içersin.\n"
		. "- seo_desc: 140-155 karakter, aynı gün teslimat gibi gerçek bir fayda ve tıklamaya davet içersin.\n"
		. "- keyword: tek odak anahtar kelime (örn. 'izmir kırmızı gül buketi').\n"
		. "- alt: görseli anlatan en fazla 120 karakterlik ALT metni.\n"
		. "- tags: 5-8 küçük harfli Türkçe etiket (çiçek türü, renk, özel gün, kullanım).\n"
		. 'Yanıtı yalnızca JSON olarak ver: {"short":"","long":"","seo_title":"","seo_desc":"","keyword":"","alt":"","tags":[""]}';
}

/**
 * Sağlayıcıya sor, metin yanıtı döndür.
 *
 * @param string $prov   Sağlayıcı.
 * @param string $prompt Talimat.
 * @return string|WP_Error
 */
function df_pai_call( $prov, $prompt ) {
	$key = df_pai_key( $prov );
	if ( 'openai' === $prov ) {
		$res = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $key,
				),
				'body'    => wp_json_encode(
					array(
						'model'           => df_opt( 'pai_openai_model', 'gpt-4.1-mini' ),
						'response_format' => array( 'type' => 'json_object' ),
						'messages'        => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
					)
				),
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$b = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			return new WP_Error( 'df_pai', isset( $b['error']['message'] ) ? 'OpenAI: ' . $b['error']['message'] : 'OpenAI yanıt vermedi.' );
		}
		return isset( $b['choices'][0]['message']['content'] ) ? (string) $b['choices'][0]['message']['content'] : '';
	}
	if ( 'gemini' === $prov ) {
		$model = (string) df_opt( 'pai_gemini_model', 'gemini-3.8-flash' );
		for ( $try = 0; $try < 2; $try++ ) {
			$res = wp_remote_post(
				'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent',
				array(
					'timeout' => 60,
					'headers' => array(
						'Content-Type'   => 'application/json',
						'x-goog-api-key' => $key,
					),
					'body'    => wp_json_encode(
						array(
							'contents'         => array(
								array(
									'role'  => 'user',
									'parts' => array( array( 'text' => $prompt ) ),
								),
							),
							'generationConfig' => array( 'responseMimeType' => 'application/json' ),
						)
					),
				)
			);
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			$b = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( 200 === (int) wp_remote_retrieve_response_code( $res ) ) {
				break;
			}
			$msg = isset( $b['error']['message'] ) ? (string) $b['error']['message'] : '';
			// Google eski modeli kapatıp yenisini önerirse önerilen modelle bir kez daha dene ve ayara kaydet.
			if ( 0 === $try && preg_match( '#use (?:models/)?(gemini-[a-z0-9.\-]+)#i', $msg, $m ) && strtolower( $m[1] ) !== strtolower( $model ) ) {
				$model = strtolower( $m[1] );
				$opts  = get_option( DF_OPTION );
				if ( is_array( $opts ) ) {
					$opts['pai_gemini_model'] = $model;
					$opts['__df_clean']       = 1;
					update_option( DF_OPTION, $opts );
				}
				continue;
			}
			return new WP_Error( 'df_pai', '' !== $msg ? 'Gemini: ' . $msg : 'Gemini yanıt vermedi.' );
		}
		$text = '';
		foreach ( (array) ( isset( $b['candidates'][0]['content']['parts'] ) ? $b['candidates'][0]['content']['parts'] : array() ) as $part ) {
			$text .= isset( $part['text'] ) ? $part['text'] : '';
		}
		return $text;
	}
	$res = wp_remote_post(
		'https://api.anthropic.com/v1/messages',
		array(
			'timeout' => 90,
			'headers' => array(
				'content-type'      => 'application/json',
				'x-api-key'         => $key,
				'anthropic-version' => '2023-06-01',
				'anthropic-beta'    => 'server-side-fallback-2026-07-01',
			),
			'body'    => wp_json_encode(
				array(
					'model'         => df_opt( 'ai_model', 'claude-opus-5-5' ) ? df_opt( 'ai_model', 'claude-opus-5-5' ) : 'claude-opus-5-5',
					'max_tokens'    => 6000,
					'fallbacks'     => 'default',
					'output_config' => array( 'effort' => 'low' ),
					'messages'      => array(
						array(
							'role'    => 'user',
							'content' => $prompt,
						),
					),
				)
			),
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$b = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) || ! is_array( $b ) || 'refusal' === ( isset( $b['stop_reason'] ) ? $b['stop_reason'] : '' ) ) {
		return new WP_Error( 'df_pai', isset( $b['error']['message'] ) ? 'Claude: ' . $b['error']['message'] : 'Claude yanıt vermedi.' );
	}
	$text = '';
	foreach ( (array) $b['content'] as $block ) {
		if ( isset( $block['type'] ) && 'text' === $block['type'] ) {
			$text .= $block['text'];
		}
	}
	return $text;
}

/**
 * Şablonla içerik (yapay zekâ anahtarı yoksa ya da hata olursa).
 *
 * @param array $in Girdiler.
 * @return array
 */
function df_pai_template( $in ) {
	$name = $in['title'];
	$sub  = $in['subtitle'];
	$cat  = $in['cats'] ? trim( strtok( $in['cats'], ',' ) ) : 'Çiçek';
	$one  = preg_replace( '/(ler|lar|leri|ları)$/u', '', df_pai_lower( $cat ) );
	$rows = df_pool_all();
	$r    = isset( $rows[ $in['pool'] ] ) ? $rows[ $in['pool'] ] : null;
	$bits = array();
	if ( $r ) {
		$flower = df_pai_lower( str_replace( array( ' / ', '/' ), ' ve ', $r['flower'] ) );
		$color  = 'Çeşitli' !== $r['color'] && '' !== $r['color'] ? df_pai_lower( str_replace( ' / ', ' ve ', $r['color'] ) ) . ' tonlarda ' : '';
		$occ    = '' !== $r['occ'] ? df_pai_lower( str_replace( ' / ', ', ', $r['occ'] ) ) : '';
		$line   = $color . ( $flower ? $flower . ' ağırlıklı ' : '' ) . 'bu tasarım' . ( $occ ? ' özellikle ' . $occ . ' anlar için' : '' ) . ' hazırlandı.';
		$first  = mb_substr( $line, 0, 1 );
		$bits[] = ( 'i' === $first ? 'İ' : mb_strtoupper( $first ) ) . mb_substr( $line, 1 );
	} elseif ( '' !== trim( $in['notes'] ) && false === strpos( $in['notes'], ':' ) ) {
		$bits[] = 'İçerik: ' . trim( $in['notes'] ) . '.';
	}
	$long = $name . ( $sub ? ' (' . $sub . ')' : '' ) . ', atölyemizde günlük taze çiçeklerle özenle hazırlanan bir ' . $one . " tasarımıdır. " . implode( ' ', $bits ) . "\n\n"
		. "İzmir içindeki teslimat bölgelerine aynı gün gönderilebilir; teslimat tarihini ve saat aralığını sipariş sırasında siz seçersiniz. Siparişinize ücretsiz, kişiye özel bir not kartı ekleyebilirsiniz.\n\n"
		. "- Sevdiklerinizi mutlu etmek için\n- Doğum günü, yıldönümü ve özel günler için\n- Teşekkür ve tebrik için";
	$st   = $name . ' | İzmir Çiçek Siparişi';
	$tags = array( $one );
	if ( $r ) {
		$tags = array_merge( $tags, df_auto_tags_from( $r['flower'] . ',' . $r['color'] . ',' . $r['occ'] ) );
	}
	$tags[] = 'izmir çiçek siparişi';
	return array(
		'short'     => $name . ( $sub ? ' — ' . $sub . '.' : '.' ) . ' Taze çiçeklerle hazırlanır, İzmir\'e aynı gün teslim edilir.',
		'long'      => df_pai_long_html( $long ),
		'seo_title' => mb_strlen( $st ) > 60 ? $name . ' | İzmir' : $st,
		'seo_desc'  => mb_substr( $name . ' İzmir\'e aynı gün teslimat ile kapınızda. Taze çiçekler, ücretsiz not kartı ve özenli paketleme. Hemen sipariş verin.', 0, 155 ),
		'keyword'   => 'izmir ' . $one,
		'alt'       => $name . ( $sub ? ' - ' . $sub : '' ) . ' - Derin Flowers İzmir',
		'tags'      => array_slice( array_values( array_unique( array_filter( $tags ) ) ), 0, 8 ),
	);
}

/**
 * AJAX: içerik üret.
 */
function df_pai_generate() {
	check_ajax_referer( 'df_pai', 'nonce' );
	if ( ! current_user_can( 'edit_products' ) || ! df_opt( 'pai_on', 1 ) ) {
		wp_send_json_error( 'Yetkiniz yok.', 403 );
	}
	$in = array();
	foreach ( array( 'title', 'subtitle', 'cats', 'price', 'notes', 'pool' ) as $k ) {
		$in[ $k ] = isset( $_POST[ $k ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $k ] ) ) : '';
	}
	if ( '' === trim( $in['title'] ) ) {
		wp_send_json_error( 'Ürün başlığı boş.' );
	}
	$prov = df_opt( 'pai_provider', 'openai' );
	if ( '' === df_pai_key( $prov ) ) {
		wp_send_json_success( df_pai_template( $in ) + array( 'note' => 'Yapay zekâ anahtarı girilmediği için içerik hazır şablonla oluşturuldu. Gerçek yapay zekâ metni için ayarlardan anahtar girin.' ) );
	}
	$text = df_pai_call( $prov, df_pai_prompt( $in ) );
	if ( is_wp_error( $text ) ) {
		wp_send_json_error( $text->get_error_message() );
	}
	$json = preg_match( '/\{.*\}/s', (string) $text, $m ) ? json_decode( $m[0], true ) : null;
	if ( ! is_array( $json ) ) {
		wp_send_json_error( 'Yapay zekâ yanıtı okunamadı, tekrar deneyin.' );
	}
	$out = array();
	foreach ( array( 'short', 'long', 'seo_title', 'seo_desc', 'keyword', 'alt' ) as $k ) {
		$out[ $k ] = isset( $json[ $k ] ) ? sanitize_textarea_field( (string) $json[ $k ] ) : '';
	}
	$out['long'] = df_pai_long_html( $out['long'] );
	$out['tags'] = isset( $json['tags'] ) ? array_slice( array_values( array_filter( array_map( 'sanitize_text_field', (array) $json['tags'] ) ) ), 0, 8 ) : array();
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_df_pai_generate', 'df_pai_generate' );

/**
 * Düz metni paragraf + liste HTML'ine çevir.
 *
 * @param string $text Metin.
 * @return string
 */
function df_pai_long_html( $text ) {
	$html  = '';
	$list  = array();
	$flush = function () use ( &$list, &$html ) {
		if ( $list ) {
			$html .= "<ul>\n<li>" . implode( "</li>\n<li>", $list ) . "</li>\n</ul>\n";
			$list  = array();
		}
	};
	foreach ( preg_split( '/\n\s*\n|\n(?=\s*[-•*] )/', (string) $text ) as $block ) {
		foreach ( preg_split( '/\n/', trim( $block ) ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( preg_match( '/^[-•*]\s+(.*)$/u', $line, $m ) ) {
				$list[] = esc_html( $m[1] );
			} else {
				$flush();
				$html .= '<p>' . esc_html( $line ) . "</p>\n";
			}
		}
	}
	$flush();
	return trim( $html );
}

/* -------------------------------------------------------------------------
 * Kaydetme: SEO alanları, ALT, etiketler, havuz bağlantısı, otomatik etiket
 * ---------------------------------------------------------------------- */

/**
 * Ürün kaydında.
 *
 * @param int $post_id Ürün.
 */
function df_pai_save( $post_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- aşağıda doğrulanıyor.
	if ( empty( $_POST['df_pai_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['df_pai_nonce'] ), 'df_pai_save' ) || ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}
	$map = array(
		'title' => 'df_seo_title',
		'desc'  => 'df_seo_desc',
		'kw'    => 'df_seo_kw',
	);
	foreach ( $map as $field => $name ) {
		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}
		$v = sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
		foreach ( df_seo_keys( $field ) as $k ) {
			if ( '' === $v ) {
				delete_post_meta( $post_id, $k );
			} else {
				update_post_meta( $post_id, $k, $v );
			}
		}
	}
	if ( isset( $_POST['df_pool_id'] ) ) {
		$pid = sanitize_key( wp_unslash( $_POST['df_pool_id'] ) );
		if ( '' !== $pid ) {
			update_post_meta( $post_id, '_df_pool_id', $pid );
		}
	}
	$thumb = get_post_thumbnail_id( $post_id );
	if ( $thumb && isset( $_POST['df_img_alt'] ) && '' !== trim( (string) wp_unslash( $_POST['df_img_alt'] ) ) ) {
		update_post_meta( $thumb, '_wp_attachment_image_alt', sanitize_text_field( wp_unslash( $_POST['df_img_alt'] ) ) );
	}
	if ( ! empty( $_POST['df_tags_add'] ) ) {
		$tags = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['df_tags_add'] ) ) ) ) );
		if ( $tags ) {
			wp_set_object_terms( $post_id, array_slice( $tags, 0, 12 ), 'product_tag', true );
		}
	}
	// phpcs:enable
	df_auto_tags( $post_id );
}
add_action( 'woocommerce_process_product_meta', 'df_pai_save', 60 );

/**
 * Türkçe küçük harf.
 *
 * @param string $s Metin.
 * @return string
 */
function df_pai_lower( $s ) {
	return mb_strtolower( strtr( (string) $s, array( 'I' => 'ı', 'İ' => 'i' ) ), 'UTF-8' );
}

/**
 * Metinden etiketler (virgül / eğik çizgi ile ayrılmış parçalar).
 *
 * @param string $text Metin.
 * @return string[]
 */
function df_auto_tags_from( $text ) {
	$skip = array( 'genel', 'çeşitli', 'karma', 'premium', 'signature', 'luxury', 'ultra luxury', 'aktif', 'uncategorized', 'kategorilenmemiş' );
	$out  = array();
	foreach ( preg_split( '/[,\/·|;]+/u', (string) $text ) as $p ) {
		$p = trim( preg_replace( '/^[^:]*:\s*/u', '', $p ) );
		$p = df_pai_lower( $p );
		if ( mb_strlen( $p ) < 3 || mb_strlen( $p ) > 30 || in_array( $p, $skip, true ) ) {
			continue;
		}
		$out[ $p ] = $p;
	}
	return array_slice( array_values( $out ), 0, 8 );
}

/**
 * Etiketi olmayan ürüne otomatik etiket: kategori, havuz bilgisi (çiçek, renk, özel gün), seçenekler.
 *
 * @param int $post_id Ürün.
 */
function df_auto_tags( $post_id ) {
	if ( ! df_opt( 'auto_tags', 1 ) || 'product' !== get_post_type( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$has = wp_get_object_terms( $post_id, 'product_tag', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $has ) && $has ) {
		return;
	}
	$src  = array();
	$cats = wp_get_object_terms( $post_id, 'product_cat', array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $cats ) ) {
		$src = array_merge( $src, $cats );
	}
	$rows = df_pool_all();
	$pool = (string) get_post_meta( $post_id, '_df_pool_id', true );
	if ( isset( $rows[ $pool ] ) ) {
		$src[] = $rows[ $pool ]['flower'];
		$src[] = $rows[ $pool ]['color'];
		$src[] = $rows[ $pool ]['occ'];
	}
	$p = wc_get_product( $post_id );
	if ( $p ) {
		foreach ( $p->get_attributes() as $a ) {
			if ( $a instanceof WC_Product_Attribute && ! $a->is_taxonomy() ) {
				$src = array_merge( $src, $a->get_options() );
			}
		}
	}
	$tags = df_auto_tags_from( implode( ',', $src ) );
	if ( $tags ) {
		wp_set_object_terms( $post_id, $tags, 'product_tag', false );
	}
}

/**
 * İçe aktarma / REST ile gelen ürünler.
 *
 * @param int $id Ürün.
 */
function df_auto_tags_new( $id ) {
	if ( ! is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		df_auto_tags( $id );
	}
}
add_action( 'woocommerce_new_product', 'df_auto_tags_new', 30 );

/* -------------------------------------------------------------------------
 * SEO eklentisi yoksa ürün sayfasında başlık ve açıklama
 * ---------------------------------------------------------------------- */

/**
 * SEO eklentisi var mı?
 *
 * @return bool
 */
function df_seo_plugin() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Ürün sayfası başlığı.
 *
 * @param string $title Başlık.
 * @return string
 */
function df_seo_doc_title( $title ) {
	if ( ! df_seo_plugin() && function_exists( 'is_product' ) && is_product() ) {
		$t = (string) get_post_meta( get_queried_object_id(), '_df_seo_title', true );
		if ( '' !== $t ) {
			return $t;
		}
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'df_seo_doc_title', 20 );

/**
 * Ürün sayfası açıklaması.
 */
function df_seo_meta_desc() {
	if ( df_seo_plugin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$d = (string) get_post_meta( get_queried_object_id(), '_df_seo_desc', true );
	if ( '' === $d ) {
		$d = wp_strip_all_tags( (string) get_post_field( 'post_excerpt', get_queried_object_id() ) );
	}
	if ( '' !== $d ) {
		echo '<meta name="description" content="' . esc_attr( mb_substr( $d, 0, 160 ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'df_seo_meta_desc', 2 );
