<?php
/**
 * Kategori içi ürün sıralaması: her kategorinin ürünleri admin panelde sürükle-bırak ile dizilir,
 * kategori sayfasında bu sırayla görünür. Sıraya henüz eklenmemiş (yeni) ürünler en üstte çıkar.
 * Ziyaretçi "Sırala" menüsünden fiyat vb. seçerse onun seçimi geçerlidir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menü: Ürünler → Kategori Sıralama.
 */
function df_co_menu() {
	add_submenu_page( 'edit.php?post_type=product', 'Kategori Sıralama', 'Kategori Sıralama', 'edit_products', 'df-cat-order', 'df_co_page' );
}
add_action( 'admin_menu', 'df_co_menu', 40 );

/**
 * Kategorinin kayıtlı sırası.
 *
 * @param int $term_id Kategori.
 * @return int[]
 */
function df_co_get( $term_id ) {
	return array_values( array_filter( array_map( 'absint', (array) get_term_meta( $term_id, 'df_order', true ) ) ) );
}

/**
 * Kategorideki ürünler, kayıtlı sıraya göre (yeniler en üstte).
 *
 * @param WP_Term $term Kategori.
 * @return int[]
 */
function df_co_products( $term ) {
	$ids   = wc_get_products(
		array(
			'status'   => array( 'publish', 'private', 'draft', 'pending' ),
			'limit'    => -1,
			'category' => array( $term->slug ),
			'orderby'  => 'date',
			'order'    => 'DESC',
			'return'   => 'ids',
		)
	);
	$saved = array_values( array_intersect( df_co_get( $term->term_id ), $ids ) );
	return array_merge( array_values( array_diff( $ids, $saved ) ), $saved );
}

/**
 * Sayfa.
 */
function df_co_page() {
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) );
	$terms = is_wp_error( $terms ) ? array() : $terms;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$cur   = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : ( $terms ? (int) $terms[0]->term_id : 0 );
	$term  = $cur ? get_term( $cur, 'product_cat' ) : null;
	wp_enqueue_script( 'jquery-ui-sortable' );
	?>
	<div class="wrap df-co">
		<h1>Kategori Sıralama</h1>
		<p class="description">Kategoriyi seçin, ürünleri sürükleyip istediğiniz sıraya dizin ve <b>Sırayı kaydet</b>'e basın. Sağdaki <b>Aktif / Pasif</b> düğmesiyle ürünü sitede anında gösterip gizleyebilirsiniz (pasif ürün silinmez, taslağa alınır). Kategori sayfasında ürünler bu sırayla görünür. Sonradan eklenen ürünler, siz yerini belirleyene kadar en üstte çıkar.</p>
		<form method="get" class="df-co__pick">
			<input type="hidden" name="post_type" value="product">
			<input type="hidden" name="page" value="df-cat-order">
			<select name="cat" onchange="this.form.submit()">
				<?php foreach ( $terms as $t ) : ?>
					<option value="<?php echo (int) $t->term_id; ?>" <?php selected( $cur, (int) $t->term_id ); ?>><?php echo esc_html( ( $t->parent ? '— ' : '' ) . html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) . ' (' . (int) $t->count . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
		</form>
		<?php if ( $term && ! is_wp_error( $term ) ) : ?>
			<?php $ids = df_co_products( $term ); ?>
			<?php if ( ! $ids ) : ?>
				<p>Bu kategoride ürün yok.</p>
			<?php else : ?>
				<div class="df-co__bar">
					<button type="button" class="button button-primary" data-co-save>Sırayı kaydet</button>
					<button type="button" class="button" data-co-sort="price">Fiyata göre diz (ucuz → pahalı)</button>
					<button type="button" class="button" data-co-sort="reverse">Ters çevir</button>
					<span class="df-co__msg" data-co-msg></span>
					<a class="button-link" href="<?php echo esc_url( get_term_link( $term ) ); ?>" target="_blank" rel="noopener">Kategori sayfasını aç ↗</a>
				</div>
				<ol class="df-co__list" data-co-list data-term="<?php echo (int) $term->term_id; ?>">
					<?php foreach ( $ids as $id ) : ?>
						<?php $p = wc_get_product( $id ); ?>
						<?php if ( ! $p ) { continue; } ?>
						<?php $on = 'publish' === $p->get_status(); ?>
						<li data-id="<?php echo (int) $id; ?>" data-price="<?php echo esc_attr( (string) (float) $p->get_price() ); ?>" class="<?php echo $on ? '' : 'is-off'; ?>">
							<span class="df-co__h" aria-hidden="true">⠿</span>
							<span class="df-co__img"><?php echo $p->get_image( 'thumbnail' ); // phpcs:ignore ?></span>
							<span class="df-co__t"><strong><?php echo esc_html( $p->get_name() ); ?></strong><small><?php echo esc_html( $p->get_sku() ); ?> · <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>" target="_blank" rel="noopener">Düzenle</a></small></span>
							<span class="df-co__p"><?php echo wp_kses_post( $p->get_price_html() ); ?></span>
							<span class="df-co__mv"><button type="button" class="button-link" data-co-top title="En üste taşı">⇡ En üste</button></span>
							<button type="button" class="df-co__sw" data-co-status role="switch" aria-checked="<?php echo $on ? 'true' : 'false'; ?>"><i></i><span><?php echo $on ? 'Aktif' : 'Pasif'; ?></span></button>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<style>
		.df-co__pick select{min-width:280px}.df-co__bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:16px 0}
		.df-co__list{margin:0;max-width:820px;counter-reset:co}.df-co__list li{display:flex;align-items:center;gap:12px;margin:0 0 6px;padding:8px 12px;background:#fff;border:1px solid #dcdcde;border-radius:8px;cursor:grab}
		.df-co__list li::before{counter-increment:co;content:counter(co);min-width:24px;color:#8c8f94;font-weight:600}
		.df-co__list li.ui-sortable-helper{box-shadow:0 8px 24px rgba(0,0,0,.15);cursor:grabbing}.df-co__list .ui-sortable-placeholder{visibility:visible!important;background:#f0f6f1;border:2px dashed #5e7b61}
		.df-co__h{color:#a7aaad;font-size:18px}.df-co__img img{width:48px;height:60px;object-fit:cover;border-radius:4px;display:block}
		.df-co__t{flex:1;display:flex;flex-direction:column}
		.df-co__list li.is-off{background:#f6f7f7}.df-co__list li.is-off .df-co__img,.df-co__list li.is-off .df-co__t strong{opacity:.45}
		.df-co__sw{display:inline-flex;align-items:center;gap:8px;min-width:92px;padding:6px 10px;border:1px solid #c3c4c7;border-radius:20px;background:#fff;cursor:pointer;font-weight:600;color:#50575e}
		.df-co__sw i{position:relative;width:30px;height:16px;border-radius:10px;background:#c3c4c7;transition:background .2s}.df-co__sw i::after{content:"";position:absolute;top:2px;left:2px;width:12px;height:12px;border-radius:50%;background:#fff;transition:left .2s}
		.df-co__sw[aria-checked=true]{border-color:#5e7b61;color:#3f5e45}.df-co__sw[aria-checked=true] i{background:#5e7b61}.df-co__sw[aria-checked=true] i::after{left:16px}.df-co__sw:disabled{opacity:.5;cursor:wait}.df-co__t small{color:#8c8f94}.df-co__p{min-width:110px;text-align:right}.df-co__msg{font-weight:600}
	</style>
	<script>
	jQuery( function ( $ ) {
		var $l = $( '[data-co-list]' ), $m = $( '[data-co-msg]' ), dirty = false;
		if ( ! $l.length ) { return; }
		$l.sortable( { axis: 'y', placeholder: 'ui-sortable-placeholder', update: function () { dirty = true; $m.text( 'Kaydedilmedi' ).css( 'color', '#b32d2e' ); } } );
		$l.on( 'click', '[data-co-top]', function () { $( this ).closest( 'li' ).prependTo( $l ); $l.sortable( 'option', 'update' )(); } );
		$( '[data-co-sort]' ).on( 'click', function () {
			var items = $l.children( 'li' ).get();
			if ( 'price' === $( this ).data( 'co-sort' ) ) { items.sort( function ( a, b ) { return +a.dataset.price - +b.dataset.price; } ); } else { items.reverse(); }
			$l.append( items ); dirty = true; $m.text( 'Kaydedilmedi' ).css( 'color', '#b32d2e' );
		} );
		$( '[data-co-save]' ).on( 'click', function () {
			var b = this; b.disabled = true; $m.text( 'Kaydediliyor…' ).css( 'color', '' );
			$.post( ajaxurl, { action: 'df_co_save', nonce: <?php echo wp_json_encode( wp_create_nonce( 'df_co' ) ); ?>, term: $l.data( 'term' ), ids: $l.children( 'li' ).map( function () { return this.dataset.id; } ).get().join( ',' ) } )
				.done( function ( r ) { dirty = false; $m.text( r && r.success ? '✓ Kaydedildi' : 'Kaydedilemedi' ).css( 'color', r && r.success ? '#1e5b2c' : '#b32d2e' ); } )
				.fail( function () { $m.text( 'Kaydedilemedi' ).css( 'color', '#b32d2e' ); } )
				.always( function () { b.disabled = false; } );
		} );
		$l.on( 'click', '[data-co-status]', function () {
			var b = this, li = $( b ).closest( 'li' ), on = 'true' !== b.getAttribute( 'aria-checked' );
			b.disabled = true;
			$.post( ajaxurl, { action: 'df_co_status', nonce: <?php echo wp_json_encode( wp_create_nonce( 'df_co' ) ); ?>, id: li.data( 'id' ), on: on ? 1 : 0 } )
				.done( function ( r ) {
					if ( ! r || ! r.success ) { $m.text( r && r.data ? r.data : 'Değiştirilemedi' ).css( 'color', '#b32d2e' ); return; }
					b.setAttribute( 'aria-checked', on ? 'true' : 'false' ); $( b ).find( 'span' ).text( on ? 'Aktif' : 'Pasif' ); li.toggleClass( 'is-off', ! on );
					$m.text( on ? '✓ Ürün sitede gösteriliyor' : '✓ Ürün gizlendi (taslağa alındı)' ).css( 'color', '#1e5b2c' );
				} )
				.fail( function () { $m.text( 'Değiştirilemedi' ).css( 'color', '#b32d2e' ); } )
				.always( function () { b.disabled = false; } );
		} );
		$( window ).on( 'beforeunload', function () { if ( dirty ) { return 'Sıra kaydedilmedi.'; } } );
	} );
	</script>
	<?php
}

/**
 * AJAX: sırayı kaydet.
 */
function df_co_save() {
	check_ajax_referer( 'df_co', 'nonce' );
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( null, 403 );
	}
	$term = isset( $_POST['term'] ) ? absint( $_POST['term'] ) : 0;
	$ids  = isset( $_POST['ids'] ) ? array_values( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['ids'] ) ) ) ) ) ) : array();
	if ( ! $term || ! get_term( $term, 'product_cat' ) ) {
		wp_send_json_error();
	}
	update_term_meta( $term, 'df_order', $ids );
	if ( function_exists( 'df_pc_flush' ) ) {
		df_pc_flush();
	}
	do_action( 'litespeed_purge_all' ); // LiteSpeed önbelleği varsa kategori sayfası hemen yenilensin.
	wp_send_json_success();
}
add_action( 'wp_ajax_df_co_save', 'df_co_save' );

/**
 * AJAX: ürünü aktif (yayında) / pasif (taslak) yap.
 */
function df_co_status() {
	check_ajax_referer( 'df_co', 'nonce' );
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$on = ! empty( $_POST['on'] );
	if ( ! $id || 'product' !== get_post_type( $id ) || ! current_user_can( $on ? 'publish_post' : 'edit_post', $id ) ) {
		wp_send_json_error( 'Bu ürünü değiştirme yetkiniz yok.', 403 );
	}
	$res = wp_update_post( array( 'ID' => $id, 'post_status' => $on ? 'publish' : 'draft' ), true );
	if ( is_wp_error( $res ) ) {
		wp_send_json_error( $res->get_error_message() );
	}
	if ( function_exists( 'df_pc_flush' ) ) {
		df_pc_flush();
	}
	do_action( 'litespeed_purge_all' );
	wp_send_json_success();
}
add_action( 'wp_ajax_df_co_status', 'df_co_status' );

/**
 * Kategori sayfasında kayıtlı sırayı uygula (ziyaretçi başka bir sıralama seçmediyse).
 * Sırada olmayan (yeni) ürünler en üstte, kendi aralarında en yeniden eskiye.
 *
 * @param array    $c Sorgu parçaları.
 * @param WP_Query $q Sorgu.
 * @return array
 */
function df_co_clauses( $c, $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_tax( 'product_cat' ) ) {
		return $c;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$by = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	if ( '' !== $by && 'menu_order' !== $by ) {
		return $c;
	}
	$term = get_queried_object();
	$ids  = $term instanceof WP_Term ? df_co_get( $term->term_id ) : array();
	if ( ! $ids ) {
		return $c;
	}
	global $wpdb;
	$c['orderby'] = 'FIELD(' . $wpdb->posts . '.ID, ' . implode( ',', array_map( 'absint', $ids ) ) . ') ASC, ' . $wpdb->posts . '.post_date DESC';
	return $c;
}
add_filter( 'posts_clauses', 'df_co_clauses', 999, 2 );

/**
 * Kategori düzenleme ekranında sıralama sayfasına kısayol.
 *
 * @param WP_Term $term Kategori.
 */
function df_co_term_link( $term ) {
	echo '<tr class="form-field"><th>Ürün sırası</th><td><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=product&page=df-cat-order&cat=' . (int) $term->term_id ) ) . '">Bu kategorinin ürünlerini sırala</a></td></tr>';
}
add_action( 'product_cat_edit_form_fields', 'df_co_term_link', 99 );
