<?php
/**
 * Yönetim Paneli → Siparişler: durum sekmeleri (sayılı), arama, tarih aralığı, ürün görselli liste,
 * satırdan hızlı durum değiştirme ve toplu işlemler (hazırlanıyor, kuryede, teslim, arşiv, yazdır, sil).
 * Teslim edildi yapılan sipariş anında arşive geçer (ayar kapatılabilir).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sekmeler: anahtar → (etiket, durumlar, renk).
 *
 * @return array<string,array{0:string,1:string[],2:string}>
 */
function df_ord_tabs() {
	return array(
		'yeni'         => array( 'Yeni Siparişler', array( 'processing' ), '#2e7d32' ),
		'havale'       => array( 'Havale Bekleyen', array( 'on-hold' ), '#558b2f' ),
		'hazirlaniyor' => array( 'Hazırlanıyor', array( 'df-preparing' ), '#1f2a44' ),
		'kuryede'      => array( 'Kuryede', array( 'df-on-the-way' ), '#1565c0' ),
		'teslim'       => array( 'Teslim Edilenler', array( 'completed' ), '#c62828' ),
		'odenmeyen'    => array( 'Ödeme Yapamayanlar / İptal', array( 'pending', 'failed', 'cancelled' ), '#6d4c41' ),
		'arsiv'        => array( 'Sipariş Arşivi', array( 'df-archived' ), '#455a64' ),
		'hepsi'        => array( 'Hepsini Listele', array( 'processing', 'on-hold', 'df-preparing', 'df-on-the-way', 'completed', 'pending', 'failed', 'cancelled', 'df-archived', 'refunded' ), '#283593' ),
	);
}

/**
 * Durum → sekme.
 *
 * @param string $status Durum (wc- öneksiz).
 * @return string
 */
function df_ord_tab_for( $status ) {
	foreach ( df_ord_tabs() as $k => $t ) {
		if ( 'hepsi' !== $k && in_array( $status, $t[1], true ) ) {
			return $k;
		}
	}
	return 'hepsi';
}

/**
 * Sayfa adresi.
 *
 * @param array $args Sorgu.
 * @return string
 */
function df_ord_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'admin.php?page=df-orders' ) );
}

/**
 * Sekmedeki sipariş sayısı.
 *
 * @param string[] $statuses Durumlar.
 * @return int
 */
function df_ord_count( $statuses ) {
	$n = 0;
	foreach ( $statuses as $s ) {
		$n += (int) wc_orders_count( $s );
	}
	return $n;
}

/**
 * Sayfa.
 */
function df_app_orders_page() {
	df_app_shell( 'df-orders', 'Siparişler', 'Durumu değiştirin, arşive gönderin, yazdırın.', 'df_ord_content' );
}

/**
 * İçerik.
 */
function df_ord_content() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- yalnızca listeleme filtreleri.
	$tabs = df_ord_tabs();
	$tab  = isset( $_GET['tab'], $tabs[ sanitize_key( $_GET['tab'] ) ] ) ? sanitize_key( $_GET['tab'] ) : 'yeni';
	$s    = isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '';
	$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
	$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
	$pg   = max( 1, isset( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1 );
	$msg  = isset( $_GET['df_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['df_msg'] ) ) : '';
	// phpcs:enable
	$per  = 30;
	$stat = $tabs[ $tab ][1];
	$date = '';
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
		$date = ( $from ? $from : '2000-01-01' ) . '...' . ( $to ? $to : wp_date( 'Y-m-d' ) );
	}
	if ( '' !== $s ) {
		// Arama: sipariş no, ad, telefon, e-posta.
		$ids    = array_map( 'absint', (array) wc_order_search( $s ) );
		$byno   = ctype_digit( $s ) ? wc_get_order( (int) $s ) : null;
		$ids    = $byno ? array_merge( array( $byno->get_id() ), $ids ) : $ids;
		$orders = array();
		foreach ( array_unique( array_slice( $ids, 0, 300 ) ) as $id ) {
			$o = wc_get_order( $id );
			if ( $o instanceof WC_Order && 'shop_order' === $o->get_type() && in_array( $o->get_status(), $stat, true ) ) {
				$orders[] = $o;
			}
		}
		$total  = count( $orders );
		$orders = array_slice( $orders, ( $pg - 1 ) * $per, $per );
	} else {
		$q = array(
			'status'   => $stat,
			'type'     => 'shop_order',
			'limit'    => $per,
			'paged'    => $pg,
			'paginate' => true,
			'orderby'  => 'date',
			'order'    => 'DESC',
		);
		if ( $date ) {
			$q['date_created'] = $date;
		}
		$r      = wc_get_orders( $q );
		$orders = $r->orders;
		$total  = (int) $r->total;
	}
	$pages = max( 1, (int) ceil( $total / $per ) );
	?>
	<div class="df-ord">
		<?php if ( $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>
		<form class="df-ord__filter" method="get">
			<input type="hidden" name="page" value="df-orders">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<input type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="Sipariş no, ad, telefon, e-posta">
			<label>Başlangıç <input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"></label>
			<label>Bitiş <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>"></label>
			<button class="button button-primary">Ara</button>
			<?php if ( $s || $from || $to ) : ?><a class="button" href="<?php echo esc_url( df_ord_url( array( 'tab' => $tab ) ) ); ?>">Temizle</a><?php endif; ?>
		</form>
		<nav class="df-ord__tabs">
			<?php foreach ( $tabs as $k => $t ) : ?>
				<a class="df-ord__tab<?php echo $k === $tab ? ' is-on' : ''; ?>" style="--c:<?php echo esc_attr( $t[2] ); ?>" href="<?php echo esc_url( df_ord_url( array( 'tab' => $k ) ) ); ?>"><?php echo esc_html( $t[0] ); ?> <b>(<?php echo (int) df_ord_count( $t[1] ); ?>)</b></a>
			<?php endforeach; ?>
		</nav>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="df-ord__form" data-ord-form>
			<input type="hidden" name="action" value="df_orders_bulk">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<?php wp_nonce_field( 'df_orders_bulk' ); ?>
			<?php
			$bar = function () {
				?>
				<div class="df-ord__bulk">
					<button class="button" name="do" value="df-preparing">✔ Hazırlanıyor olarak işaretle</button>
					<button class="button" name="do" value="df-on-the-way">🚚 Kuryede</button>
					<button class="button" name="do" value="completed">✅ Teslim edildi</button>
					<button class="button" name="do" value="df-archived">🗄 Arşive gönder</button>
					<button class="button" name="do" value="print">🖨 Seçilenleri yazdır</button>
					<button class="button df-ord__del" name="do" value="trash" data-ord-confirm="Seçilen siparişler çöp kutusuna taşınsın mı? (WooCommerce → Siparişler → Çöp'ten geri alınabilir)">✖ Seçilenleri sil</button>
				</div>
				<?php
			};
			$bar();
			?>
			<table class="widefat striped df-ord__table">
				<thead><tr>
					<th class="check-column"><input type="checkbox" data-ord-all></th>
					<th>Sipariş</th><th>Ürün</th><th>Gönderen</th><th>Alıcı / Teslimat</th><th>Tarih</th><th>Durum</th><th>Ödeme</th><th>Tutar</th><th>İşlem</th>
				</tr></thead>
				<tbody>
				<?php if ( ! $orders ) : ?>
					<tr><td colspan="10" class="df-ord__empty">Bu bölümde sipariş yok.</td></tr>
				<?php endif; ?>
				<?php foreach ( $orders as $o ) : ?>
					<?php
					$items = $o->get_items();
					$first = $items ? reset( $items ) : null;
					$prod  = $first ? $first->get_product() : null;
					$names = array();
					foreach ( $items as $it ) {
						$names[] = $it->get_name() . ( $it->get_quantity() > 1 ? ' ×' . $it->get_quantity() : '' );
					}
					$dd = (string) $o->get_meta( '_df_delivery_date' );
					?>
					<tr>
						<th class="check-column"><input type="checkbox" name="ids[]" value="<?php echo (int) $o->get_id(); ?>"></th>
						<td><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>"><strong>#<?php echo esc_html( $o->get_order_number() ); ?></strong></a></td>
						<td class="df-ord__prod"><?php echo $prod ? $prod->get_image( array( 48, 60 ) ) : ''; // phpcs:ignore ?><span><?php echo esc_html( implode( ', ', $names ) ); ?></span></td>
						<td><?php echo esc_html( trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ) ); ?><small><?php echo esc_html( $o->get_billing_phone() ); ?></small></td>
						<td><?php echo esc_html( (string) $o->get_meta( '_df_recipient_name' ) ); ?><small><?php echo esc_html( function_exists( 'df_app_district' ) ? df_app_district( $o ) : '' ); ?><?php echo $dd ? ' · ' . esc_html( wp_date( 'j M', strtotime( $dd . ' 12:00:00' ) ) . ' ' . $o->get_meta( '_df_delivery_slot' ) ) : ''; ?></small></td>
						<td><?php echo esc_html( $o->get_date_created() ? wp_date( 'j M Y H:i', $o->get_date_created()->getTimestamp() ) : '' ); ?></td>
						<td>
							<select class="df-ord__st" data-ord-status="<?php echo (int) $o->get_id(); ?>">
								<?php foreach ( wc_get_order_statuses() as $sk => $sl ) : ?>
									<option value="<?php echo esc_attr( substr( $sk, 3 ) ); ?>" <?php selected( 'wc-' . $o->get_status(), $sk ); ?>><?php echo esc_html( $sl ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><?php echo esc_html( $o->get_payment_method_title() ); ?></td>
						<td><strong><?php echo wp_kses_post( wc_price( $o->get_total(), array( 'currency' => $o->get_currency() ) ) ); ?></strong></td>
						<td class="df-ord__act">
							<a class="button button-small" href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">Düzenle</a>
							<a class="button button-small" target="_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=df_print_bulk&type=both&ids[]=' . $o->get_id() ), 'df_print_bulk' ) ); ?>">Yazdır</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php $bar(); ?>
		</form>
		<?php if ( $pages > 1 ) : ?>
			<div class="df-ord__pages">
				<?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
					<a class="button<?php echo $i === $pg ? ' button-primary' : ''; ?>" href="<?php echo esc_url( df_ord_url( array( 'tab' => $tab, 's' => $s, 'from' => $from, 'to' => $to, 'pg' => $i ) ) ); ?>"><?php echo (int) $i; ?></a>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
		<p class="description">Toplam <?php echo (int) $total; ?> sipariş. <?php echo df_opt( 'archive_instant', 1 ) ? '“Teslim edildi” yapılan siparişler otomatik olarak arşive taşınır.' : ''; ?></p>
	</div>
	<style>
		.df-ord__filter{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:0 0 14px}.df-ord__filter input[type=search]{min-width:260px}
		.df-ord__tabs{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 16px}
		.df-ord__tab{padding:9px 14px;border-radius:6px;background:var(--c);color:#fff;text-decoration:none;font-weight:600;opacity:.82}.df-ord__tab:hover{color:#fff;opacity:1}.df-ord__tab.is-on{opacity:1;box-shadow:0 0 0 3px rgba(0,0,0,.15)}
		.df-ord__bulk{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;margin:10px 0}.df-ord__del{background:#c62828!important;border-color:#c62828!important;color:#fff!important}
		.df-ord__table td{vertical-align:middle}.df-ord__table small{display:block;color:#8c8f94}.df-ord__prod{display:flex;align-items:center;gap:10px;min-width:220px}.df-ord__prod img{width:48px;height:60px;object-fit:cover;border-radius:4px}
		.df-ord__empty{text-align:center;padding:30px!important;color:#8c8f94}.df-ord__act{white-space:nowrap}.df-ord__pages{display:flex;gap:6px;margin:12px 0}
		.df-ord__st.is-saving{opacity:.5}.df-ord__st.is-ok{outline:2px solid #2e7d32}
	</style>
	<script>
	( function () {
		var f = document.querySelector( '[data-ord-form]' ); if ( ! f ) { return; }
		var all = f.querySelector( '[data-ord-all]' );
		if ( all ) { all.addEventListener( 'change', function () { f.querySelectorAll( 'input[name="ids[]"]' ).forEach( function ( c ) { c.checked = all.checked; } ); } ); }
		f.addEventListener( 'submit', function ( e ) {
			var b = e.submitter; if ( ! f.querySelector( 'input[name="ids[]"]:checked' ) ) { e.preventDefault(); alert( 'Önce sipariş seçin.' ); return; }
			if ( b && b.dataset.ordConfirm && ! confirm( b.dataset.ordConfirm ) ) { e.preventDefault(); }
			if ( b && 'print' === b.value ) { f.target = '_blank'; setTimeout( function () { f.target = ''; }, 500 ); }
		} );
		f.querySelectorAll( '[data-ord-status]' ).forEach( function ( sel ) {
			sel.addEventListener( 'change', function () {
				var d = new FormData(); d.append( 'action', 'df_orders_status' ); d.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( 'df_orders_status' ) ); ?> ); d.append( 'id', sel.dataset.ordStatus ); d.append( 'status', sel.value );
				sel.classList.add( 'is-saving' );
				fetch( ajaxurl, { method: 'POST', body: d, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
					sel.classList.remove( 'is-saving' ); if ( r && r.success ) { sel.classList.add( 'is-ok' ); if ( r.data && r.data.status ) { sel.value = r.data.status; } } else { alert( 'Durum değiştirilemedi.' ); }
				} ).catch( function () { sel.classList.remove( 'is-saving' ); alert( 'Durum değiştirilemedi.' ); } );
			} );
		} );
	}() );
	</script>
	<?php
}

/**
 * Toplu işlem.
 */
function df_orders_bulk() {
	if ( ! current_user_can( 'edit_shop_orders' ) || ! check_admin_referer( 'df_orders_bulk' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$ids = isset( $_POST['ids'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) ) : array();
	$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'yeni';
	if ( 'print' === $do ) {
		$url = admin_url( 'admin-post.php?action=df_print_bulk&type=both' );
		foreach ( $ids as $id ) {
			$url .= '&ids[]=' . $id;
		}
		wp_safe_redirect( wp_nonce_url( $url, 'df_print_bulk' ) );
		exit;
	}
	$n = 0;
	foreach ( $ids as $id ) {
		$o = wc_get_order( $id );
		if ( ! $o ) {
			continue;
		}
		if ( 'trash' === $do ) {
			$o->delete( false );
		} elseif ( in_array( $do, array( 'df-preparing', 'df-on-the-way', 'completed', 'df-archived' ), true ) ) {
			$o->update_status( $do, 'Yönetim Paneli → Siparişler (toplu işlem).' );
		} else {
			continue;
		}
		++$n;
	}
	$labels = array( 'df-preparing' => 'hazırlanıyor yapıldı', 'df-on-the-way' => 'kuryede yapıldı', 'completed' => 'teslim edildi yapıldı', 'df-archived' => 'arşive gönderildi', 'trash' => 'çöp kutusuna taşındı' );
	wp_safe_redirect( df_ord_url( array( 'tab' => $tab, 'df_msg' => $n . ' sipariş ' . ( $labels[ $do ] ?? 'güncellendi' ) . '.' ) ) );
	exit;
}
add_action( 'admin_post_df_orders_bulk', 'df_orders_bulk' );

/**
 * AJAX: satırdan durum değiştir.
 */
function df_orders_status() {
	check_ajax_referer( 'df_orders_status', 'nonce' );
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$st = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
	$o  = $id ? wc_get_order( $id ) : null;
	if ( ! $o || ! current_user_can( 'edit_shop_orders' ) || ! array_key_exists( 'wc-' . $st, wc_get_order_statuses() ) ) {
		wp_send_json_error( null, 403 );
	}
	$o->update_status( $st, 'Yönetim Paneli → Siparişler.' );
	wp_send_json_success( array( 'status' => $o->get_status() ) );
}
add_action( 'wp_ajax_df_orders_status', 'df_orders_status' );
