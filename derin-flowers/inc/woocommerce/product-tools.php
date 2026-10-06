<?php
/**
 * Ürün yönetimi araçları:
 * - Otomatik ürün kodu (AYZ0001…), varyasyonlarda AYZ0001-21
 * - Hazır varyasyon şablonları (21 / 41 / 101 gül…) — ürün ekranından tek tıkla
 * - Ürün başlık havuzu ve aynı başlık uyarısı
 * - Ürün listesinde Aktif / Pasif anahtarı
 * - Görsel SEO: dosya adı, ALT metni, en büyük boyut, WebP
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Otomatik ürün kodu
 * ---------------------------------------------------------------------- */

/**
 * Kod öneki.
 *
 * @return string
 */
function df_sku_prefix() {
	$p = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', remove_accents( (string) df_opt( 'sku_prefix', 'AYZ' ) ) ) );
	return '' !== $p ? $p : 'AYZ';
}

/**
 * Sıradaki boş ürün kodu.
 *
 * @return string
 */
function df_sku_next() {
	global $wpdb;
	$prefix = df_sku_prefix();
	$digits = max( 3, min( 8, (int) df_opt( 'sku_digits', 4 ) ) );
	$n      = (int) get_option( 'df_sku_counter_' . $prefix, 0 );
	if ( ! $n ) {
		// İlk kullanımda mevcut en büyük kodu bul (elle girilmiş kodlar korunur).
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $rows as $v ) {
			if ( preg_match( '/^' . preg_quote( $prefix, '/' ) . '(\d+)$/', $v, $m ) ) {
				$n = max( $n, (int) $m[1] );
			}
		}
	}
	do {
		++$n;
		$sku = $prefix . str_pad( (string) $n, $digits, '0', STR_PAD_LEFT );
	} while ( wc_get_product_id_by_sku( $sku ) );
	update_option( 'df_sku_counter_' . $prefix, $n, false );
	return $sku;
}

/**
 * Varyasyon kodu: ANA-21 (seçenekteki sayı ya da kısa ad).
 *
 * @param WC_Product_Variation $variation Varyasyon.
 * @return string
 */
function df_sku_for_variation( $variation ) {
	$parent = wc_get_product( $variation->get_parent_id() );
	$base   = $parent ? $parent->get_sku() : '';
	if ( '' === $base ) {
		return '';
	}
	$parts = array();
	foreach ( $variation->get_attributes() as $value ) {
		if ( '' === (string) $value ) {
			continue;
		}
		$parts[] = preg_match( '/\d+/', (string) $value, $m ) ? $m[0] : strtoupper( substr( sanitize_title( $value ), 0, 4 ) );
	}
	$sku = $base . ( $parts ? '-' . implode( '-', $parts ) : '-' . $variation->get_menu_order() );
	$try = $sku;
	$i   = 2;
	while ( ( $id = wc_get_product_id_by_sku( $try ) ) && $id !== $variation->get_id() ) { // phpcs:ignore
		$try = $sku . '-' . $i++;
	}
	return $try;
}

/**
 * Kaydetmeden önce boş kodu doldur.
 *
 * @param WC_Product $product Ürün.
 */
function df_sku_before_save( $product ) {
	if ( ! df_opt( 'sku_auto', 1 ) || '' !== (string) $product->get_sku( 'edit' ) ) {
		return;
	}
	$status = $product->get_status( 'edit' );
	if ( in_array( $status, array( 'auto-draft', 'trash' ), true ) ) {
		return;
	}
	try {
		if ( $product->is_type( 'variation' ) ) {
			$sku = df_sku_for_variation( $product );
			if ( $sku ) {
				$product->set_sku( $sku );
			}
		} else {
			$product->set_sku( df_sku_next() );
		}
	} catch ( Exception $e ) {
		unset( $e ); // Benzersiz değilse boş bırak.
	}
}
add_action( 'woocommerce_before_product_object_save', 'df_sku_before_save', 5 );
add_action( 'woocommerce_before_product_variation_object_save', 'df_sku_before_save', 5 );

/**
 * Kodsuz tüm ürünlere kod ver (Ürünler ekranındaki düğme).
 */
function df_sku_fill_all() {
	if ( ! current_user_can( 'edit_products' ) || ! check_admin_referer( 'df_sku_fill' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				array( 'key' => '_sku', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_sku', 'value' => '' ),
			),
		)
	);
	$n = 0;
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( $p && '' === $p->get_sku( 'edit' ) ) {
			$p->save(); // df_sku_before_save kodu verir.
			++$n;
			if ( $p->is_type( 'variable' ) ) {
				foreach ( $p->get_children() as $vid ) {
					$v = wc_get_product( $vid );
					if ( $v && '' === $v->get_sku( 'edit' ) ) {
						$v->save();
					}
				}
			}
		}
	}
	wp_safe_redirect( add_query_arg( 'df_sku_done', $n, admin_url( 'edit.php?post_type=product' ) ) );
	exit;
}
add_action( 'admin_post_df_sku_fill', 'df_sku_fill_all' );

/**
 * Ürünler ekranı: araç çubuğu (kod ver, CSV içe aktar, sıralama).
 *
 * @param array $views Görünümler.
 * @return array
 */
function df_products_toolbar( $views ) {
	if ( df_opt( 'sku_auto', 1 ) ) {
		$views['df_sku'] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=df_sku_fill' ), 'df_sku_fill' ) ) . '">Kodsuz ürünlere ' . esc_html( df_sku_prefix() ) . ' kodu ver</a>';
	}
	$views['df_import'] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=product&page=product_importer' ) ) . '">CSV ile ürün yükle</a>';
	$views['df_sort']   = '<a href="' . esc_url( admin_url( 'edit.php?post_type=product&orderby=menu_order+title&order=ASC' ) ) . '">Sürükle-bırak sıralama</a>';
	return $views;
}
add_filter( 'views_edit-product', 'df_products_toolbar', 20 );

/**
 * Bilgi notu.
 */
function df_sku_notice() {
	if ( isset( $_GET['df_sku_done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . (int) $_GET['df_sku_done'] . ' ürüne kod verildi.</p></div>'; // phpcs:ignore
	}
}
add_action( 'admin_notices', 'df_sku_notice' );

/* -------------------------------------------------------------------------
 * Hazır varyasyon şablonları
 * ---------------------------------------------------------------------- */

/**
 * Şablonlar.
 *
 * @return array<int, array{name:string, attr:string, values:string[]}>
 */
function df_var_templates() {
	$out = array();
	foreach ( df_lines( df_opt( 'var_templates' ), true ) as $row ) {
		if ( count( $row ) < 3 ) {
			continue;
		}
		$values = array_values( array_filter( array_map( 'trim', explode( ',', $row[2] ) ), 'strlen' ) );
		if ( $values ) {
			$out[] = array(
				'name'   => $row[0],
				'attr'   => $row[1],
				'values' => $values,
			);
		}
	}
	return $out;
}

/**
 * Kutu kaydı.
 */
function df_var_box_register() {
	add_meta_box( 'df-var-template', 'Hazır Varyasyon (21 – 41 – 101 gül…)', 'df_var_box', 'product', 'normal', 'default' );
}
add_action( 'add_meta_boxes_product', 'df_var_box_register' );

/**
 * Kutu.
 *
 * @param WP_Post $post Ürün.
 */
function df_var_box( $post ) {
	$templates = df_var_templates();
	$product   = wc_get_product( $post->ID );
	wp_nonce_field( 'df_var_apply', 'df_var_nonce' );
	?>
	<div class="df-vt">
		<p class="df-vt__intro">Şablon seçin, fiyatları yazın, <strong>"Kaydederken uygula"</strong> kutusunu işaretleyip ürünü güncelleyin. Ürün otomatik olarak <em>değişken ürüne</em> çevrilir; mevcut varyasyonların fiyatı güncellenir, eksikler eklenir.</p>
		<?php if ( ! $templates ) : ?>
			<p>Şablon yok. <a href="<?php echo esc_url( admin_url( 'admin.php?page=derin-flowers#product' ) ); ?>">Derin Flowers → Ürün & Mağaza</a> bölümünden ekleyin.</p>
		<?php else : ?>
			<div class="df-vt__top">
				<select data-df-vt-select>
					<option value="">— Şablon seçin —</option>
					<?php foreach ( $templates as $i => $t ) : ?>
						<option value="<?php echo (int) $i; ?>" data-attr="<?php echo esc_attr( $t['attr'] ); ?>" data-values="<?php echo esc_attr( wp_json_encode( $t['values'] ) ); ?>"><?php echo esc_html( $t['name'] . ' (' . implode( ', ', $t['values'] ) . ')' ); ?></option>
					<?php endforeach; ?>
				</select>
				<label>Özellik adı <input type="text" name="df_vt_attr" data-df-vt-attr placeholder="Adet"></label>
			</div>
			<table class="df-vt__rows" data-df-vt-rows hidden>
				<thead><tr><th>Seçenek</th><th>Fiyat (₺)</th><th>İndirimli (₺)</th><th></th></tr></thead>
				<tbody></tbody>
			</table>
			<p class="df-vt__foot" data-df-vt-foot hidden>
				<button type="button" class="button" data-df-vt-add>+ Seçenek ekle</button>
				<label class="df-vt__apply"><input type="checkbox" name="df_vt_apply" value="1"> <strong>Kaydederken uygula</strong></label>
				<?php if ( $product && $product->get_regular_price() ) : ?>
					<span class="description">Mevcut fiyat: <?php echo wp_kses_post( wc_price( $product->get_regular_price() ) ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
	<style>
		.df-vt__intro{color:#555;margin-top:0}.df-vt__top{display:flex;gap:12px;flex-wrap:wrap;align-items:center}.df-vt__top select{min-width:280px}
		.df-vt__rows{width:100%;max-width:640px;border-collapse:collapse;margin:12px 0}.df-vt__rows th{text-align:left;font-weight:600;padding:6px 4px}
		.df-vt__rows td{padding:4px}.df-vt__rows input{width:100%}.df-vt__foot{display:flex;gap:16px;align-items:center;flex-wrap:wrap}
		.df-vt__apply{background:#fff7e6;border:1px solid #f0c36d;padding:6px 10px;border-radius:6px}
	</style>
	<script>
	( function () {
		var sel = document.querySelector( '[data-df-vt-select]' );
		if ( ! sel ) { return; }
		var table = document.querySelector( '[data-df-vt-rows]' ), body = table.querySelector( 'tbody' );
		var foot = document.querySelector( '[data-df-vt-foot]' ), attr = document.querySelector( '[data-df-vt-attr]' );
		function row( v ) {
			var tr = document.createElement( 'tr' );
			tr.innerHTML = '<td><input type="text" name="df_vt_values[]"></td><td><input type="text" inputmode="decimal" name="df_vt_prices[]" placeholder="Örn. 1490"></td><td><input type="text" inputmode="decimal" name="df_vt_sales[]" placeholder="Boş"></td><td><button type="button" class="button-link-delete">Sil</button></td>';
			tr.querySelector( 'input' ).value = v || '';
			tr.querySelector( 'button' ).addEventListener( 'click', function () { tr.remove(); } );
			body.appendChild( tr );
		}
		sel.addEventListener( 'change', function () {
			body.innerHTML = '';
			var o = sel.options[ sel.selectedIndex ];
			if ( ! sel.value ) { table.hidden = foot.hidden = true; return; }
			attr.value = o.getAttribute( 'data-attr' );
			JSON.parse( o.getAttribute( 'data-values' ) ).forEach( row );
			table.hidden = foot.hidden = false;
		} );
		document.querySelector( '[data-df-vt-add]' ).addEventListener( 'click', function () { row( '' ); } );
	}() );
	</script>
	<?php
}

/**
 * Şablonu uygula (WooCommerce ürünü kaydettikten sonra).
 *
 * @param int $post_id Ürün.
 */
function df_var_apply( $post_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['df_vt_apply'] ) || empty( $_POST['df_var_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['df_var_nonce'] ), 'df_var_apply' ) || ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}
	$name   = isset( $_POST['df_vt_attr'] ) ? sanitize_text_field( wp_unslash( $_POST['df_vt_attr'] ) ) : '';
	$values = isset( $_POST['df_vt_values'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['df_vt_values'] ) ) : array();
	$prices = isset( $_POST['df_vt_prices'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['df_vt_prices'] ) ) : array();
	$sales  = isset( $_POST['df_vt_sales'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['df_vt_sales'] ) ) : array();
	// phpcs:enable
	$rows = array();
	foreach ( $values as $i => $v ) {
		$v = trim( $v );
		if ( '' !== $v && ! isset( $rows[ $v ] ) ) {
			$rows[ $v ] = array(
				'price' => isset( $prices[ $i ] ) && '' !== trim( $prices[ $i ] ) ? df_parse_money( $prices[ $i ] ) : null,
				'sale'  => isset( $sales[ $i ] ) && '' !== trim( $sales[ $i ] ) ? df_parse_money( $sales[ $i ] ) : null,
			);
		}
	}
	if ( '' === $name || ! $rows ) {
		return;
	}
	df_var_build( $post_id, $name, $rows );
}
add_action( 'woocommerce_process_product_meta', 'df_var_apply', 99 );

/**
 * Ürünü değişken yapıp varyasyonları oluştur / güncelle.
 *
 * @param int    $post_id Ürün.
 * @param string $name    Özellik adı.
 * @param array  $rows    seçenek => [price, sale].
 * @return int Oluşturulan varyasyon sayısı.
 */
function df_var_build( $post_id, $name, $rows ) {
	$old = wc_get_product( $post_id );
	$base_price = $old ? $old->get_regular_price( 'edit' ) : '';
	wp_set_object_terms( $post_id, 'variable', 'product_type' );
	$product = new WC_Product_Variable( $post_id );
	$slug    = sanitize_title( $name );
	$attrs   = $product->get_attributes( 'edit' );
	$options = array_keys( $rows );
	if ( isset( $attrs[ $slug ] ) && ! $attrs[ $slug ]->is_taxonomy() ) {
		$options = array_values( array_unique( array_merge( $attrs[ $slug ]->get_options(), $options ) ) );
	}
	$attr = new WC_Product_Attribute();
	$attr->set_id( 0 );
	$attr->set_name( $name );
	$attr->set_options( $options );
	$attr->set_position( isset( $attrs[ $slug ] ) ? $attrs[ $slug ]->get_position() : count( $attrs ) );
	$attr->set_visible( true );
	$attr->set_variation( true );
	$attrs[ $slug ] = $attr;
	$product->set_attributes( $attrs );
	if ( ! $product->get_default_attributes() ) {
		$product->set_default_attributes( array( $slug => (string) key( $rows ) ) );
	}
	$product->save();

	$existing = array();
	foreach ( $product->get_children() as $vid ) {
		$v = wc_get_product( $vid );
		if ( $v ) {
			$a = $v->get_attributes( 'edit' );
			if ( isset( $a[ $slug ] ) ) {
				$existing[ strtolower( $a[ $slug ] ) ] = $v;
			}
		}
	}
	$made  = 0;
	$order = 0;
	foreach ( $rows as $value => $r ) {
		$key = strtolower( $value );
		$v   = isset( $existing[ $key ] ) ? $existing[ $key ] : new WC_Product_Variation();
		if ( ! $v->get_id() ) {
			$v->set_parent_id( $post_id );
			$v->set_attributes( array( $slug => $value ) );
			$v->set_status( 'publish' );
			++$made;
			if ( null === $r['price'] && '' !== $base_price && 0 === $order ) {
				$r['price'] = (float) $base_price; // İlk seçenek mevcut fiyatı alır.
			}
		}
		$v->set_menu_order( $order++ );
		if ( null !== $r['price'] ) {
			$v->set_regular_price( wc_format_decimal( $r['price'] ) );
		}
		$v->set_sale_price( null !== $r['sale'] && ( null === $r['price'] || $r['sale'] < $r['price'] ) ? wc_format_decimal( $r['sale'] ) : '' );
		$v->save();
	}
	WC_Product_Variable::sync( $post_id );
	wc_delete_product_transients( $post_id );
	return $made;
}

/* -------------------------------------------------------------------------
 * Başlık havuzu & aynı başlık uyarısı
 * ---------------------------------------------------------------------- */

/**
 * Ürün ekranına başlık havuzu ve uyarı.
 */
function df_title_tools() {
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->id ) {
		return;
	}
	$pool = df_lines( df_opt( 'title_pool' ) );
	$used = array();
	if ( $pool ) {
		global $wpdb;
		$titles = $wpdb->get_col( "SELECT LOWER(post_title) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status NOT IN ('trash','auto-draft')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$used   = array_flip( $titles );
	}
	?>
	<?php if ( $pool ) : ?>
		<datalist id="df-title-pool">
			<?php foreach ( $pool as $t ) : ?>
				<option value="<?php echo esc_attr( $t ); ?>"><?php echo isset( $used[ mb_strtolower( $t ) ] ) ? 'Kullanıldı' : 'Boşta'; ?></option>
			<?php endforeach; ?>
		</datalist>
	<?php endif; ?>
	<script>
	( function () {
		var t = document.getElementById( 'title' );
		if ( ! t ) { return; }
		<?php if ( $pool ) : ?>
		t.setAttribute( 'list', 'df-title-pool' );
		<?php endif; ?>
		<?php if ( df_opt( 'title_dupe_warn', 1 ) ) : ?>
		var box = document.createElement( 'p' );
		box.style.cssText = 'margin:6px 0 0;padding:8px 12px;border-radius:6px;background:#fdecea;color:#8a1f11;display:none';
		t.parentNode.appendChild( box );
		var timer;
		function check() {
			var v = t.value.trim();
			if ( v.length < 3 ) { box.style.display = 'none'; return; }
			var fd = new FormData();
			fd.append( 'action', 'df_title_check' );
			fd.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( 'df_title_check' ) ); ?> );
			fd.append( 'title', v );
			fd.append( 'id', <?php echo (int) get_the_ID(); ?> );
			fetch( ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
				if ( r && r.success && r.data.length ) {
					box.innerHTML = '⚠ Bu başlık daha önce kullanıldı: ' + r.data.map( function ( p ) { return '<a href="' + p.url + '" target="_blank">' + p.title + ' (' + p.sku + ')</a>'; } ).join( ', ' ) + '. Farklı bir isim seçmeniz önerilir.';
					box.style.display = 'block';
				} else {
					box.style.display = 'none';
				}
			} );
		}
		t.addEventListener( 'input', function () { clearTimeout( timer ); timer = setTimeout( check, 600 ); } );
		t.addEventListener( 'change', check );
		if ( t.value ) { check(); }
		<?php endif; ?>
	}() );
	</script>
	<?php
}
add_action( 'admin_footer-post.php', 'df_title_tools' );
add_action( 'admin_footer-post-new.php', 'df_title_tools' );

/**
 * AJAX: aynı başlıklı ürünler.
 */
function df_title_check() {
	check_ajax_referer( 'df_title_check', 'nonce' );
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
 * Ürün listesinde Aktif / Pasif
 * ---------------------------------------------------------------------- */

/**
 * Sütun.
 *
 * @param array $cols Sütunlar.
 * @return array
 */
function df_active_column( $cols ) {
	$out = array();
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( 'name' === $k ) {
			$out['df_active'] = 'Satışta';
		}
	}
	return $out;
}
add_filter( 'manage_edit-product_columns', 'df_active_column', 20 );

/**
 * Sütun içeriği.
 *
 * @param string $col Sütun.
 * @param int    $id  Ürün.
 */
function df_active_column_content( $col, $id ) {
	if ( 'df_active' !== $col ) {
		return;
	}
	$on = 'publish' === get_post_status( $id );
	echo '<button type="button" class="df-act' . ( $on ? ' is-on' : '' ) . '" data-df-act="' . (int) $id . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '" title="Aktif / Pasif"><span></span></button>';
}
add_action( 'manage_product_posts_custom_column', 'df_active_column_content', 10, 2 );

/**
 * Anahtar stili ve betiği.
 */
function df_active_assets() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-product' !== $screen->id ) {
		return;
	}
	?>
	<style>
		.column-df_active{width:64px}.df-act{width:40px;height:22px;border-radius:22px;border:0;background:#cfc8c3;position:relative;cursor:pointer;transition:background .2s}
		.df-act span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .2s}
		.df-act.is-on{background:#5e7b61}.df-act.is-on span{transform:translateX(18px)}.df-act[disabled]{opacity:.5}
	</style>
	<script>
	document.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( '[data-df-act]' );
		if ( ! b ) { return; }
		e.preventDefault();
		b.disabled = true;
		var fd = new FormData();
		fd.append( 'action', 'df_product_active' );
		fd.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( 'df_product_active' ) ); ?> );
		fd.append( 'id', b.getAttribute( 'data-df-act' ) );
		fetch( ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
			if ( r && r.success ) { b.classList.toggle( 'is-on', r.data.on ); b.setAttribute( 'aria-pressed', r.data.on ? 'true' : 'false' ); }
		} ).finally( function () { b.disabled = false; } );
	} );
	</script>
	<?php
}
add_action( 'admin_footer-edit.php', 'df_active_assets' );

/**
 * AJAX: aktif / pasif.
 */
function df_product_active() {
	check_ajax_referer( 'df_product_active', 'nonce' );
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	if ( ! $id || 'product' !== get_post_type( $id ) || ! current_user_can( 'edit_product', $id ) ) {
		wp_send_json_error( null, 403 );
	}
	$on = 'publish' !== get_post_status( $id );
	wp_update_post(
		array(
			'ID'          => $id,
			'post_status' => $on ? 'publish' : 'draft',
		)
	);
	wp_send_json_success( array( 'on' => $on ) );
}
add_action( 'wp_ajax_df_product_active', 'df_product_active' );

/* -------------------------------------------------------------------------
 * Görsel SEO
 * ---------------------------------------------------------------------- */

/**
 * Türkçe karakterleri sadeleştir, küçük harf (kirmizi-gul-buketi.jpg).
 *
 * @param string $name Dosya adı.
 * @return string
 */
function df_img_seo_filename( $name ) {
	if ( ! df_opt( 'img_seo_name', 1 ) ) {
		return $name;
	}
	$name = strtr( $name, array( 'İ' => 'i', 'I' => 'i', 'ı' => 'i', 'Ş' => 's', 'ş' => 's', 'Ğ' => 'g', 'ğ' => 'g', 'Ü' => 'u', 'ü' => 'u', 'Ö' => 'o', 'ö' => 'o', 'Ç' => 'c', 'ç' => 'c' ) );
	$name = strtolower( remove_accents( $name ) );
	$name = preg_replace( '/[\s_]+/', '-', $name );
	$name = preg_replace( '/-+/', '-', $name );
	return $name;
}
add_filter( 'sanitize_file_name', 'df_img_seo_filename', 5 );

/**
 * Ürün ekranından yüklenen görsele ürün adını ver (IMG_2041.jpg → kirmizi-gul-buketi.jpg).
 *
 * @param array $file Dosya.
 * @return array
 */
function df_img_seo_prefilter( $file ) {
	// phpcs:ignore WordPress.Security.NonceVerification
	$pid = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
	if ( ! df_opt( 'img_seo_name', 1 ) || ! $pid || 'product' !== get_post_type( $pid ) || 0 !== strpos( (string) $file['type'], 'image/' ) ) {
		return $file;
	}
	$title = get_the_title( $pid );
	if ( ! $title || 'Otomatik Taslak' === $title || 'Auto Draft' === $title ) {
		return $file;
	}
	$base = pathinfo( $file['name'], PATHINFO_FILENAME );
	// Yalnızca anlamsız (kamera / ekran görüntüsü) adları değiştir.
	if ( preg_match( '/^(img|dsc|image|photo|whatsapp|screenshot|ekran|pxl|dcim|\d)/i', $base ) ) {
		$file['name'] = df_img_seo_filename( $title ) . '.' . strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	}
	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'df_img_seo_prefilter' );

/**
 * Ürün kaydında ALT metni boş görsellere ürün adı.
 *
 * @param int $post_id Ürün.
 */
function df_img_seo_alt( $post_id ) {
	if ( ! df_opt( 'img_seo_alt', 1 ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return;
	}
	$ids = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
	$n   = 0;
	foreach ( $ids as $aid ) {
		if ( '' === trim( (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) ) ) {
			update_post_meta( $aid, '_wp_attachment_image_alt', $product->get_name() . ( $n ? ' - ' . ( $n + 1 ) : '' ) );
		}
		++$n;
	}
}
add_action( 'woocommerce_update_product', 'df_img_seo_alt', 20 );
add_action( 'woocommerce_new_product', 'df_img_seo_alt', 20 );

/**
 * En büyük görsel boyutu.
 *
 * @return int
 */
function df_img_max() {
	return max( 1200, min( 4000, (int) df_opt( 'img_max', 2000 ) ) );
}
add_filter( 'big_image_size_threshold', 'df_img_max' );

/**
 * Alt boyutları WebP üret.
 *
 * @param array $formats Biçimler.
 * @return array
 */
function df_img_webp( $formats ) {
	if ( df_opt( 'img_webp', 0 ) && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';
	}
	return $formats;
}
add_filter( 'image_editor_output_format', 'df_img_webp' );
