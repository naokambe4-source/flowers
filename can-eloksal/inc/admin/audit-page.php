<?php
/**
 * Audit log ekranı: filtre, arama, sayfalama.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Eylem etiketleri.
 *
 * @return array<string,string>
 */
function ce_audit_action_labels() {
	return array(
		'login'         => 'Giriş',
		'logout'        => 'Çıkış',
		'login_failed'  => 'Başarısız giriş',
		'login_blocked' => 'Engellenen giriş',
		'create'        => 'Oluşturma',
		'update'        => 'Güncelleme',
		'publish'       => 'Yayınlama',
		'trash'         => 'Çöpe taşıma',
		'restore'       => 'Geri yükleme',
		'delete'        => 'Kalıcı silme',
		'settings'      => 'Ayar güncelleme',
		'status'        => 'Durum değişikliği',
		'download'      => 'Dosya indirme',
		'export'        => 'Dışa aktarma',
		'import'        => 'İçe aktarma',
		'setup'         => 'Kurulum',
		'role'          => 'Rol değişikliği',
		'switch'        => 'Tema değişikliği',
		'activate'      => 'Eklenti etkinleştirme',
		'deactivate'    => 'Eklenti devre dışı',
	);
}

/**
 * Ekran.
 */
function ce_render_audit_page() {
	global $wpdb;
	if ( ! current_user_can( 'ce_view_audit' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$table   = ce_audit_table();
	$action  = isset( $_GET['a'] ) ? sanitize_key( wp_unslash( $_GET['a'] ) ) : ''; // phpcs:ignore
	$module  = isset( $_GET['m'] ) ? sanitize_key( wp_unslash( $_GET['m'] ) ) : ''; // phpcs:ignore
	$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore
	$paged   = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore
	$per     = 40;
	$where   = array( '1=1' );
	$params  = array();
	if ( $action ) {
		$where[]  = 'action = %s';
		$params[] = $action;
	}
	if ( $module ) {
		$where[]  = 'module = %s';
		$params[] = $module;
	}
	if ( $search ) {
		$like     = '%' . $wpdb->esc_like( $search ) . '%';
		$where[]  = '(object_title LIKE %s OR user_login LIKE %s OR ip LIKE %s)';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}
	$sql_where = implode( ' AND ', $where );
	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$sql_where}";
	$rows_sql  = "SELECT * FROM {$table} WHERE {$sql_where} ORDER BY id DESC LIMIT %d OFFSET %d";
	// phpcs:disable WordPress.DB
	$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) );
	$rows  = $wpdb->get_results( $wpdb->prepare( $rows_sql, array_merge( $params, array( $per, ( $paged - 1 ) * $per ) ) ) );
	$mods  = $wpdb->get_col( "SELECT DISTINCT module FROM {$table} ORDER BY module" );
	// phpcs:enable

	$labels = ce_audit_action_labels();
	echo '<div class="wrap ce-admin">';
	ce_admin_header( 'Audit Log', 'Yönetim panelindeki önemli işlemlerin kaydı (365 gün saklanır).' );

	echo '<form class="ce-filterbar" method="get"><input type="hidden" name="page" value="ce-audit">';
	echo '<label class="screen-reader-text" for="ce-a">Eylem</label><select id="ce-a" name="a"><option value="">Tüm eylemler</option>';
	foreach ( $labels as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $action, $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select><label class="screen-reader-text" for="ce-m">Modül</label><select id="ce-m" name="m"><option value="">Tüm modüller</option>';
	foreach ( (array) $mods as $m ) {
		echo '<option value="' . esc_attr( $m ) . '" ' . selected( $module, $m, false ) . '>' . esc_html( $m ) . '</option>';
	}
	echo '</select><label class="screen-reader-text" for="ce-s">Ara</label><input id="ce-s" type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Kayıt, kullanıcı veya IP"><button class="button">Filtrele</button></form>';

	echo '<div class="ce-card ce-card--flush"><table class="widefat striped ce-table"><thead><tr><th>Tarih</th><th>Kullanıcı</th><th>Eylem</th><th>Modül</th><th>Kayıt</th><th>IP</th><th>Tarayıcı</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="7"><div class="ce-empty"><span class="dashicons dashicons-list-view" aria-hidden="true"></span><p>Kayıt bulunamadı.</p></div></td></tr>';
	}
	foreach ( (array) $rows as $r ) {
		$date = get_date_from_gmt( $r->created_at, 'd.m.Y H:i:s' );
		$obj  = $r->object_title;
		if ( $r->object_id && ! in_array( $r->module, array( 'auth', 'user', 'settings', 'menu', 'theme', 'plugin' ), true ) && get_post( (int) $r->object_id ) ) {
			$obj = '<a href="' . esc_url( get_edit_post_link( (int) $r->object_id ) ) . '">' . esc_html( $r->object_title ) . '</a>';
		} else {
			$obj = esc_html( $obj );
		}
		printf(
			'<tr><td>%1$s</td><td>%2$s</td><td><span class="ce-status ce-status--%8$s">%3$s</span></td><td>%4$s</td><td>%5$s</td><td><code>%6$s</code></td><td class="ce-ua" title="%7$s">%7$s</td></tr>',
			esc_html( $date ),
			esc_html( $r->user_login ? $r->user_login : '—' ),
			esc_html( $labels[ $r->action ] ?? $r->action ),
			esc_html( $r->module ),
			$obj, // phpcs:ignore -- yukarıda kaçırıldı.
			esc_html( $r->ip ),
			esc_attr( $r->user_agent ),
			esc_attr( in_array( $r->action, array( 'delete', 'login_failed', 'login_blocked', 'trash' ), true ) ? 'danger' : ( 'login' === $r->action ? 'done' : 'review' ) )
		);
	}
	echo '</tbody></table></div>';

	$pages = (int) ceil( $total / $per );
	if ( $pages > 1 ) {
		echo '<div class="tablenav"><div class="tablenav-pages">';
		echo paginate_links( // phpcs:ignore
			array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $paged,
				'total'   => $pages,
			)
		);
		echo '</div></div>';
	}
	echo '</div>';
}
