<?php
/**
 * Kategorileri toplu aç / kapat (özel günler için: ör. Sevgililer Günü'nde yalnızca o kategori açık).
 *
 * Kapalı kategori: menülerden, kategori listelerinden ve sitemap'ten kalkar; sayfası açılırsa mağazaya yönlenir.
 * Yalnızca kapalı kategorilerde bulunan ürünler sitede görünmez ve satın alınamaz; açık bir kategoride de
 * bulunan ürün (ör. hem Kırmızı Güller hem Sevgililer Günü) görünmeye devam eder. Ürünler silinmez, değişmez.
 * Yönetim: Ürünler → Kategori Aç / Kapat.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kapalı kategori kimlikleri.
 *
 * @return int[]
 */
function df_ct_closed() {
	return array_values( array_filter( array_map( 'absint', (array) get_option( 'df_closed_cats', array() ) ) ) );
}

/**
 * Görünmeyecek ürünler: tüm kategorileri kapalı olanlar (önbellekli).
 *
 * @return int[]
 */
function df_ct_hidden_products() {
	$closed = df_ct_closed();
	if ( ! $closed ) {
		return array();
	}
	$key = 'df_ct_hidden_' . md5( implode( ',', $closed ) );
	$ids = get_transient( $key );
	if ( is_array( $ids ) ) {
		return $ids;
	}
	global $wpdb;
	$in   = implode( ',', array_map( 'absint', $closed ) );
	// Kapalı kategorilerdeki ürünler ve bu ürünlerin açık bir kategorisi olup olmadığı.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows = $wpdb->get_results(
		"SELECT tr.object_id, SUM( tt.term_id NOT IN ($in) ) AS open_cnt
		 FROM {$wpdb->term_relationships} tr
		 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
		 WHERE tr.object_id IN ( SELECT tr2.object_id FROM {$wpdb->term_relationships} tr2 INNER JOIN {$wpdb->term_taxonomy} tt2 ON tt2.term_taxonomy_id = tr2.term_taxonomy_id WHERE tt2.taxonomy = 'product_cat' AND tt2.term_id IN ($in) )
		 GROUP BY tr.object_id"
	);
	// phpcs:enable
	$ids = array();
	foreach ( (array) $rows as $r ) {
		if ( 0 === (int) $r->open_cnt ) {
			$ids[] = (int) $r->object_id;
		}
	}
	set_transient( $key, $ids, HOUR_IN_SECONDS );
	return $ids;
}

/**
 * Önbellekleri temizle.
 */
function df_ct_flush() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_df\\_ct\\_hidden\\_%' OR option_name LIKE '\\_transient\\_timeout\\_df\\_ct\\_hidden\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( function_exists( 'df_pc_flush' ) ) {
		df_pc_flush();
	}
	if ( function_exists( 'df_loc_products_flush' ) ) {
		update_option( 'df_loc_p_ver', (int) get_option( 'df_loc_p_ver', 1 ) + 1, false );
	}
	do_action( 'litespeed_purge_all' );
}

/**
 * Ürün kaydında gizli ürün listesini yenile.
 *
 * @param int $post_id Yazı.
 */
function df_ct_flush_on_save( $post_id ) {
	if ( 'product' === get_post_type( $post_id ) && df_ct_closed() ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_df\\_ct\\_hidden\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
add_action( 'save_post', 'df_ct_flush_on_save' );

/**
 * Ziyaretçi tarafında mı (yönetici panelini ve yöneticiyi etkilemesin)?
 *
 * @return bool
 */
function df_ct_front() {
	return ! is_admin() || wp_doing_ajax();
}

/**
 * Ürün sorgularından gizli ürünleri çıkar.
 *
 * @param WP_Query $q Sorgu.
 */
function df_ct_hide_products( $q ) {
	if ( ! df_ct_front() || ! df_ct_closed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_products' ) ) ) {
		return; // WooCommerce uygulaması (REST) tüm ürünleri görmeye devam eder.
	}
	if ( wp_doing_ajax() && current_user_can( 'edit_products' ) && isset( $_REQUEST['action'] ) && 0 === strpos( sanitize_key( wp_unslash( $_REQUEST['action'] ) ), 'woocommerce_' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return; // Yönetici ekranındaki WooCommerce aramaları (ör. siparişe ürün ekleme) etkilenmesin.
	}
	$pt = $q->get( 'post_type' );
	if ( 'product' === $pt || ( is_array( $pt ) && in_array( 'product', $pt, true ) ) || $q->is_tax( array( 'product_cat', 'product_tag' ) ) || $q->is_post_type_archive( 'product' ) || $q->is_search() ) {
		$ids = df_ct_hidden_products();
		if ( $ids && ! $q->is_singular() ) {
			$q->set( 'post__not_in', array_merge( (array) $q->get( 'post__not_in' ), $ids ) );
		}
	}
}
add_action( 'pre_get_posts', 'df_ct_hide_products', 20 );

/**
 * Kategori listelerinden (menü dışı: kategori şeritleri, filtreler, alt kategoriler) kapalıları çıkar.
 *
 * @param array $args       Sorgu.
 * @param array $taxonomies Sınıflandırmalar.
 * @return array
 */
function df_ct_terms_args( $args, $taxonomies ) {
	if ( is_admin() || ! in_array( 'product_cat', (array) $taxonomies, true ) || ! df_ct_closed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $args;
	}
	$args['exclude'] = array_merge( wp_parse_id_list( $args['exclude'] ?? array() ), df_ct_closed() );
	return $args;
}
add_filter( 'get_terms_args', 'df_ct_terms_args', 10, 2 );

/**
 * Menüden kapalı kategori bağlantılarını kaldır (alt öğeleriyle birlikte).
 *
 * @param array $items Menü öğeleri.
 * @return array
 */
function df_ct_menu( $items ) {
	$closed = df_ct_closed();
	if ( ! $closed || is_admin() ) {
		return $items;
	}
	$drop = array();
	foreach ( $items as $i => $it ) {
		if ( 'taxonomy' === $it->type && 'product_cat' === $it->object && in_array( (int) $it->object_id, $closed, true ) ) {
			// Kapalı kategorinin altında açık bir kategori varsa (ör. Özel Günler → Sevgililer Günü) üst öğe kalsın.
			$has_open_child = false;
			foreach ( $items as $c ) {
				if ( (int) $c->menu_item_parent === (int) $it->ID && ! ( 'taxonomy' === $c->type && 'product_cat' === $c->object && in_array( (int) $c->object_id, $closed, true ) ) ) {
					$has_open_child = true;
					break;
				}
			}
			if ( ! $has_open_child ) {
				$drop[] = (int) $it->ID;
				unset( $items[ $i ] );
			}
		}
	}
	// Kaldırılan öğelerin alt öğeleri de gitsin.
	do {
		$n = count( $drop );
		foreach ( $items as $i => $it ) {
			if ( in_array( (int) $it->menu_item_parent, $drop, true ) ) {
				$drop[] = (int) $it->ID;
				unset( $items[ $i ] );
			}
		}
	} while ( count( $drop ) !== $n );
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'df_ct_menu', 20 );

/**
 * Kapalı kategori sayfası ve gizli ürün sayfası: mağazaya / açık kategoriye yönlendir.
 */
function df_ct_redirect() {
	if ( is_admin() || current_user_can( 'edit_products' ) || ! df_ct_closed() ) {
		return;
	}
	$to   = '';
	$slug = (string) get_query_var( 'product_cat' );
	remove_filter( 'get_terms_args', 'df_ct_terms_args', 10 );
	$term = '' !== $slug ? get_term_by( 'slug', sanitize_title( basename( $slug ) ), 'product_cat' ) : null;
	add_filter( 'get_terms_args', 'df_ct_terms_args', 10, 2 );
	if ( is_404() && $term && in_array( (int) $term->term_id, df_ct_closed(), true ) ) {
		$to = df_ct_open_url(); // Kapalı kategori adresi (listeden çıkarıldığı için 404 olurdu).
	} elseif ( is_tax( 'product_cat' ) && in_array( (int) get_queried_object_id(), df_ct_closed(), true ) ) {
		$to = df_ct_open_url();
	} elseif ( is_singular( 'product' ) && in_array( (int) get_queried_object_id(), df_ct_hidden_products(), true ) ) {
		$to = df_ct_open_url();
	}
	if ( $to ) {
		wp_safe_redirect( $to, 302 );
		exit;
	}
}
add_action( 'template_redirect', 'df_ct_redirect', 2 );

/**
 * Yönlendirme hedefi: tek açık kategori varsa o, yoksa mağaza.
 *
 * @return string
 */
function df_ct_open_url() {
	$open = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => df_ct_closed(), 'fields' => 'ids' ) );
	$open = is_wp_error( $open ) ? array() : array_values( array_diff( $open, array( (int) get_option( 'default_product_cat' ) ) ) );
	if ( 1 === count( $open ) ) {
		$l = get_term_link( (int) $open[0], 'product_cat' );
		if ( ! is_wp_error( $l ) ) {
			return $l;
		}
	}
	return wc_get_page_permalink( 'shop' );
}

/**
 * Gizli ürün satın alınamasın (sepette kalmış olsa bile).
 *
 * @param bool       $ok      Satılabilir mi.
 * @param WC_Product $product Ürün.
 * @return bool
 */
function df_ct_purchasable( $ok, $product ) {
	if ( ! $ok || ! df_ct_closed() || is_admin() ) {
		return $ok;
	}
	$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	return ! in_array( (int) $id, df_ct_hidden_products(), true );
}
add_filter( 'woocommerce_is_purchasable', 'df_ct_purchasable', 20, 2 );

/* -------------------------------------------------------------------------
 * Yönetim sayfası
 * ---------------------------------------------------------------------- */

/**
 * Menü: Ürünler → Kategori Aç / Kapat.
 */
function df_ct_menu_page() {
	add_submenu_page( 'edit.php?post_type=product', 'Kategori Aç / Kapat', 'Kategori Aç / Kapat', 'manage_product_terms', 'df-cat-toggle', 'df_ct_page' );
}
add_action( 'admin_menu', 'df_ct_menu_page', 41 );

/**
 * Kategorileri ağaç sırasıyla (üst → alt) getir.
 *
 * @return array<int,array{0:WP_Term,1:int}>
 */
function df_ct_tree() {
	$all = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) );
	$all = is_wp_error( $all ) ? array() : $all;
	$by  = array();
	foreach ( $all as $t ) {
		$by[ (int) $t->parent ][] = $t;
	}
	$out  = array();
	$walk = function ( $parent, $depth ) use ( &$walk, &$out, $by ) {
		foreach ( $by[ $parent ] ?? array() as $t ) {
			$out[] = array( $t, $depth );
			$walk( (int) $t->term_id, $depth + 1 );
		}
	};
	$walk( 0, 0 );
	return $out;
}

/**
 * Sayfa.
 */
function df_ct_page() {
	$closed = df_ct_closed();
	$def    = (int) get_option( 'default_product_cat' );
	$tree   = df_ct_tree();
	$open_n = 0;
	foreach ( $tree as $row ) {
		if ( $def !== (int) $row[0]->term_id && ! in_array( (int) $row[0]->term_id, $closed, true ) ) {
			++$open_n;
		}
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$msg = isset( $_GET['df_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['df_msg'] ) ) : '';
	?>
	<div class="wrap df-ct">
		<h1>Kategori Aç / Kapat</h1>
		<p class="description">Özel günlerde (ör. Sevgililer Günü) diğer kategorileri tek tıkla kapatın: kapalı kategoriler menüden ve sitede kalkar, yalnızca kapalı kategorilerde olan ürünler gösterilmez ve satılmaz. Açık bir kategoride de bulunan ürünler görünmeye devam eder. Hiçbir ürün silinmez; özel gün bitince <b>Hepsini aç</b> demeniz yeterli.</p>
		<?php if ( $msg ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div><?php endif; ?>
		<div class="df-ct__state<?php echo $closed ? ' is-on' : ''; ?>">
			<?php if ( $closed ) : ?>
				<strong><?php echo count( $closed ); ?> kategori kapalı</strong>, <?php echo (int) $open_n; ?> kategori açık. Sitede gizlenen ürün: <strong><?php echo count( df_ct_hidden_products() ); ?></strong>.
			<?php else : ?>
				<strong>Tüm kategoriler açık.</strong>
			<?php endif; ?>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="df_cat_toggle">
			<?php wp_nonce_field( 'df_cat_toggle' ); ?>
			<div class="df-ct__bar">
				<button class="button button-primary" name="do" value="only">Sadece seçilenleri açık bırak (diğerlerini kapat)</button>
				<button class="button" name="do" value="open">Seçilenleri aç</button>
				<button class="button" name="do" value="close">Seçilenleri kapat</button>
				<button class="button df-ct__all" name="do" value="all" data-ct-noselect>Hepsini aç</button>
			</div>
			<table class="widefat striped df-ct__table">
				<thead><tr><th class="check-column"><input type="checkbox" data-ct-all></th><th>Kategori</th><th>Ürün</th><th>Durum</th></tr></thead>
				<tbody>
				<?php foreach ( $tree as $row ) : ?>
					<?php
					list( $t, $d ) = $row;
					if ( $def === (int) $t->term_id ) {
						continue;
					}
					$is_closed = in_array( (int) $t->term_id, $closed, true );
					?>
					<tr class="<?php echo $is_closed ? 'is-closed' : ''; ?>">
						<th class="check-column"><input type="checkbox" name="ids[]" value="<?php echo (int) $t->term_id; ?>"></th>
						<td style="padding-left:<?php echo 10 + 22 * (int) $d; ?>px"><?php echo $d ? '<span class="df-ct__tree">└</span> ' : ''; ?><strong><?php echo esc_html( html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) ); ?></strong> <a class="df-ct__view" href="<?php echo esc_url( get_term_link( $t ) ); ?>" target="_blank" rel="noopener">görüntüle ↗</a></td>
						<td><?php echo (int) $t->count; ?></td>
						<td>
							<button type="submit" class="df-ct__sw" name="toggle" value="<?php echo (int) $t->term_id; ?>" aria-checked="<?php echo $is_closed ? 'false' : 'true'; ?>"><i></i><span><?php echo $is_closed ? 'Kapalı' : 'Açık'; ?></span></button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</form>
	</div>
	<style>
		.df-ct__state{margin:14px 0;padding:12px 16px;border-radius:8px;background:#eaf6ec;color:#1e5b2c;border:1px solid #c9e3cd;max-width:900px}.df-ct__state.is-on{background:#fff4e5;color:#7a4a00;border-color:#f1d3a1}
		.df-ct__bar{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0}.df-ct__all{margin-left:auto!important}
		.df-ct__table{max-width:900px}.df-ct__table td{vertical-align:middle}.df-ct__table tr.is-closed td{color:#8c8f94}.df-ct__tree{color:#a7aaad}.df-ct__view{font-size:12px;margin-left:6px}
		.df-ct__sw{display:inline-flex;align-items:center;gap:8px;min-width:88px;padding:5px 10px;border:1px solid #c3c4c7;border-radius:20px;background:#fff;cursor:pointer;font-weight:600;color:#b32d2e}
		.df-ct__sw i{position:relative;width:30px;height:16px;border-radius:10px;background:#c3c4c7}.df-ct__sw i::after{content:"";position:absolute;top:2px;left:2px;width:12px;height:12px;border-radius:50%;background:#fff;transition:left .2s}
		.df-ct__sw[aria-checked=true]{border-color:#5e7b61;color:#3f5e45}.df-ct__sw[aria-checked=true] i{background:#5e7b61}.df-ct__sw[aria-checked=true] i::after{left:16px}
	</style>
	<script>
	( function () {
		var all = document.querySelector( '[data-ct-all]' ), f = all && all.closest( 'form' );
		if ( ! f ) { return; }
		all.addEventListener( 'change', function () { f.querySelectorAll( 'input[name="ids[]"]' ).forEach( function ( c ) { c.checked = all.checked; } ); } );
		f.addEventListener( 'submit', function ( e ) {
			var b = e.submitter;
			if ( ! b || b.name === 'toggle' || b.hasAttribute( 'data-ct-noselect' ) ) { return; }
			if ( ! f.querySelector( 'input[name="ids[]"]:checked' ) ) { e.preventDefault(); alert( 'Önce kategori seçin.' ); return; }
			if ( 'only' === b.value && ! confirm( 'Seçilen kategoriler dışındaki tüm kategoriler kapatılacak. Devam edilsin mi?' ) ) { e.preventDefault(); }
		} );
	}() );
	</script>
	<?php
}

/**
 * Kaydet.
 */
function df_ct_save() {
	if ( ! current_user_can( 'manage_product_terms' ) || ! check_admin_referer( 'df_cat_toggle' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$closed = df_ct_closed();
	$ids    = isset( $_POST['ids'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) ) : array();
	$do     = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	$tog    = isset( $_POST['toggle'] ) ? absint( $_POST['toggle'] ) : 0;
	$all    = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids' ) );
	$all    = is_wp_error( $all ) ? array() : array_map( 'intval', $all );
	$def    = (int) get_option( 'default_product_cat' );
	if ( $tog ) {
		$closed = in_array( $tog, $closed, true ) ? array_diff( $closed, array( $tog ) ) : array_merge( $closed, array( $tog ) );
		$msg    = in_array( $tog, $closed, true ) ? 'Kategori kapatıldı.' : 'Kategori açıldı.';
	} elseif ( 'all' === $do ) {
		$closed = array();
		$msg    = 'Tüm kategoriler açıldı.';
	} elseif ( 'only' === $do && $ids ) {
		$closed = array_values( array_diff( $all, $ids, array( $def ) ) );
		$msg    = count( $ids ) . ' kategori açık bırakıldı, diğer ' . count( $closed ) . ' kategori kapatıldı.';
	} elseif ( 'open' === $do && $ids ) {
		$closed = array_diff( $closed, $ids );
		$msg    = count( $ids ) . ' kategori açıldı.';
	} elseif ( 'close' === $do && $ids ) {
		$closed = array_merge( $closed, array_diff( $ids, array( $def ) ) );
		$msg    = count( $ids ) . ' kategori kapatıldı.';
	} else {
		$msg = 'Değişiklik yapılmadı.';
	}
	update_option( 'df_closed_cats', array_values( array_unique( array_intersect( array_map( 'intval', $closed ), $all ) ) ), true );
	df_ct_flush();
	wp_safe_redirect( add_query_arg( 'df_msg', rawurlencode( $msg ), admin_url( 'edit.php?post_type=product&page=df-cat-toggle' ) ) );
	exit;
}
add_action( 'admin_post_df_cat_toggle', 'df_ct_save' );

/**
 * Kategoriler kapalıyken panelde hatırlatma.
 */
function df_ct_notice() {
	$closed = df_ct_closed();
	if ( ! $closed || ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>' . count( $closed ) . ' ürün kategorisi kapalı</strong> (sitede görünmüyor). <a href="' . esc_url( admin_url( 'edit.php?post_type=product&page=df-cat-toggle' ) ) . '">Kategori Aç / Kapat</a></p></div>';
}
add_action( 'admin_notices', 'df_ct_notice' );

/**
 * Kategoriler listesinde durum sütunu.
 *
 * @param array $cols Sütunlar.
 * @return array
 */
function df_ct_col( $cols ) {
	$cols['df_ct'] = 'Sitede';
	return $cols;
}
add_filter( 'manage_edit-product_cat_columns', 'df_ct_col' );

/**
 * Sütun içeriği.
 *
 * @param string $out    Çıktı.
 * @param string $column Sütun.
 * @param int    $id     Kategori.
 * @return string
 */
function df_ct_col_val( $out, $column, $id ) {
	if ( 'df_ct' !== $column ) {
		return $out;
	}
	return in_array( (int) $id, df_ct_closed(), true ) ? '<span style="color:#b32d2e;font-weight:600">Kapalı</span>' : '<span style="color:#2e7d32">Açık</span>';
}
add_filter( 'manage_product_cat_custom_column', 'df_ct_col_val', 10, 3 );
