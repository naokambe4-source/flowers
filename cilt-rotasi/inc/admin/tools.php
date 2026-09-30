<?php
/**
 * Araçlar & Kurulum, Bülten aboneleri, Arama analitiği sayfaları.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Araç işlemleri (admin-post).
 */
function cr_tools_action() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	check_admin_referer( 'cr_tools' );
	$do  = isset( $_POST['cr_do'] ) ? sanitize_key( wp_unslash( $_POST['cr_do'] ) ) : '';
	$msg = '';
	switch ( $do ) {
		case 'setup':
			$r   = cr_run_setup( ! empty( $_POST['samples'] ), ! empty( $_POST['sideload'] ) );
			$msg = 'Kurulum tamamlandı: ' . implode( ', ', $r );
			break;
		case 'export':
			nocache_headers();
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=cilt-rotasi-ayarlar-' . gmdate( 'Y-m-d' ) . '.json' );
			echo wp_json_encode( get_option( CR_OPTION, array() ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			exit;
		case 'import':
			if ( ! empty( $_FILES['cr_file']['tmp_name'] ) ) {
				$data = json_decode( (string) file_get_contents( sanitize_text_field( $_FILES['cr_file']['tmp_name'] ) ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.Security.ValidatedSanitizedInput
				if ( is_array( $data ) ) {
					cr_snapshot( 'İçe aktarma öncesi' );
					$clean = array();
					foreach ( array_keys( cr_options_schema() ) as $tab ) {
						$clean = array_merge( $clean, cr_sanitize_tab( $tab, $data ) );
					}
					update_option( CR_OPTION, $clean );
					$msg = 'Ayarlar içe aktarıldı.';
				} else {
					$msg = 'Dosya okunamadı (geçerli bir JSON değil).';
				}
			}
			break;
		case 'reset':
			cr_snapshot( 'Sıfırlama öncesi' );
			delete_option( CR_OPTION );
			$msg = 'Tüm tema ayarları varsayılana döndü (öncesi anlık görüntü olarak saklandı).';
			break;
		case 'restore':
			$i     = isset( $_POST['snap'] ) ? absint( $_POST['snap'] ) : -1;
			$snaps = get_option( 'cr_snapshots', array() );
			if ( isset( $snaps[ $i ]['data'] ) ) {
				$data = $snaps[ $i ]['data'];
				cr_snapshot( 'Geri yükleme öncesi' );
				update_option( CR_OPTION, $data );
				$msg = 'Anlık görüntü geri yüklendi.';
			}
			break;
		case 'flush':
			flush_rewrite_rules();
			cr_purge_caches();
			$msg = 'Kalıcı bağlantılar ve önbellekler yenilendi.';
			break;
		case 'subs_csv':
			$subs = get_option( 'cr_subscribers', array() );
			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=cilt-rotasi-aboneler-' . gmdate( 'Y-m-d' ) . '.csv' );
			$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			fputcsv( $out, array( 'E-posta', 'Tarih', 'Kaynak' ) );
			foreach ( (array) $subs as $email => $s ) {
				fputcsv( $out, array( $email, wp_date( 'Y-m-d H:i', isset( $s['t'] ) ? $s['t'] : 0 ), isset( $s['src'] ) ? $s['src'] : '' ) );
			}
			fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			exit;
		case 'subs_delete':
			$email = isset( $_POST['email'] ) ? strtolower( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : '';
			$subs  = get_option( 'cr_subscribers', array() );
			unset( $subs[ $email ] );
			update_option( 'cr_subscribers', $subs, false );
			$msg = 'Abone silindi.';
			wp_safe_redirect( add_query_arg( array( 'page' => 'cilt-rotasi-aboneler', 'msg' => rawurlencode( $msg ) ), admin_url( 'admin.php' ) ) );
			exit;
		case 'search_clear':
			delete_option( 'cr_search_log' );
			wp_safe_redirect( add_query_arg( array( 'page' => 'cilt-rotasi-aramalar', 'msg' => rawurlencode( 'Arama kayıtları temizlendi.' ) ), admin_url( 'admin.php' ) ) );
			exit;
	}
	cr_flush_options_cache();
	cr_purge_caches();
	wp_safe_redirect( add_query_arg( array( 'page' => 'cilt-rotasi-araclar', 'msg' => rawurlencode( $msg ) ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_cr_tools', 'cr_tools_action' );

/**
 * Bildirim.
 */
function cr_tools_notice() {
	if ( ! empty( $_GET['msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="cr-notice cr-notice--ok">✓ ' . esc_html( sanitize_text_field( wp_unslash( $_GET['msg'] ) ) ) . '</div>'; // phpcs:ignore WordPress.Security.NonceVerification
	}
}

/**
 * Form başı.
 *
 * @param string $do    İşlem.
 * @param string $extra Ek nitelik.
 */
function cr_tools_form( $do, $extra = '' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" ' . $extra . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<input type="hidden" name="action" value="cr_tools"><input type="hidden" name="cr_do" value="' . esc_attr( $do ) . '">';
	wp_nonce_field( 'cr_tools' );
}

/**
 * Araçlar sayfası.
 */
function cr_tools_page() {
	$snaps = get_option( 'cr_snapshots', array() );
	?>
	<div class="wrap cr-admin">
		<?php cr_admin_header( 'Araçlar & Kurulum', 'Tek tıkla kurulum, yedekleme ve geri yükleme.' ); ?>
		<?php cr_tools_notice(); ?>
		<div class="cr-dash__grid">
			<section class="cr-card-a cr-card-a--hero">
				<header class="cr-card-a__head"><h2>✨ Kurulum sihirbazı</h2><p>Sitenin iskeletini saniyeler içinde kurar. Var olan içerikleri silmez; aynı adla olanları atlar.</p></header>
				<div class="cr-card-a__body">
					<ul class="cr-bullets">
						<li>Kategoriler: Cilt Yapısı, Cilt Problemleri, Cilt Bakım Rutini, İçerikler, Ürünler (görsel ve üst başlıklarıyla)</li>
						<li>Cilt sorunları (Akne, Siyah Nokta, Leke, Kızarıklık, Hassasiyet, Kuruluk, İnce Çizgiler), cilt tipleri, içerik grupları, ürün türleri</li>
						<li>Sayfalar: Ana Sayfa, Rehberler, Cilt Testi, Kaydedilenler, Hakkımızda, Yayın İlkeleri, İletişim, Gizlilik, Çerez Politikası, Kullanım Koşulları</li>
						<li>Ana menü (Cilt Problemleri mega menüsüyle) ve 3 footer menüsü; okunaklı kalıcı bağlantılar (/%postname%/, /kategori/)</li>
					</ul>
					<?php cr_tools_form( 'setup' ); ?>
						<label class="cr-check"><input type="checkbox" name="samples" value="1" checked> Örnek içerikleri de ekle (9 rehber, 8 sözlük içeriği, 4 ürün incelemesi — kısa cevap, SSS, kaynak ve adımlarıyla)</label>
						<label class="cr-check"><input type="checkbox" name="sideload" value="1"> Örnek görselleri medya kütüphanesine indir (daha yavaş; kapalıysa görseller harici adresten gösterilir)</label>
						<button class="cr-abtn cr-abtn--lg" type="submit">Kurulumu çalıştır</button>
					</form>
				</div>
			</section>

			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>Yedekle / taşı</h2><p>Tüm tema ayarlarını JSON olarak indir veya başka bir siteye aktar.</p></header>
				<div class="cr-card-a__body cr-stack">
					<?php cr_tools_form( 'export' ); ?><button class="cr-abtn cr-abtn--ghost" type="submit">⤓ Ayarları dışa aktar</button></form>
					<?php cr_tools_form( 'import', 'enctype="multipart/form-data"' ); ?>
						<input type="file" name="cr_file" accept="application/json,.json" required>
						<button class="cr-abtn cr-abtn--ghost" type="submit">⤒ İçe aktar</button>
					</form>
					<?php cr_tools_form( 'flush' ); ?><button class="cr-abtn cr-abtn--ghost" type="submit">↻ Kalıcı bağlantıları ve önbelleği yenile</button></form>
					<?php cr_tools_form( 'reset', 'onsubmit="return confirm(\'Tüm tema ayarları varsayılana dönsün mü?\')"' ); ?><button class="cr-abtn cr-abtn--danger" type="submit">Ayarları sıfırla</button></form>
				</div>
			</section>

			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>Geçmiş (anlık görüntüler)</h2><p>Her kayıttan önce ayarların bir kopyası alınır. Son 15 kayıt saklanır.</p></header>
				<div class="cr-card-a__body">
					<ul class="cr-list">
						<?php foreach ( (array) $snaps as $i => $s ) : ?>
							<?php $u = get_userdata( isset( $s['u'] ) ? $s['u'] : 0 ); ?>
							<li>
								<strong><?php echo esc_html( $s['l'] ); ?></strong>
								<em><?php echo esc_html( wp_date( 'j F, H:i', $s['t'] ) . ( $u ? ' · ' . $u->display_name : '' ) ); ?></em>
								<?php cr_tools_form( 'restore', 'class="cr-inline" onsubmit="return confirm(\'Bu sürüme dönülsün mü?\')"' ); ?><input type="hidden" name="snap" value="<?php echo (int) $i; ?>"><button class="cr-mini" type="submit">Geri yükle</button></form>
							</li>
						<?php endforeach; ?>
						<?php if ( ! $snaps ) : ?>
							<li class="cr-list__empty">Henüz kayıt yok.</li>
						<?php endif; ?>
					</ul>
				</div>
			</section>

			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>Kısa kodlar ve bloklar</h2></header>
				<div class="cr-card-a__body">
					<ul class="cr-codes">
						<li><code>[cr_kisa_cevap]…[/cr_kisa_cevap]</code> Kısa cevap kutusu (AEO)</li>
						<li><code>[cr_bilgi baslik="…"]…[/cr_bilgi]</code> Yeşil bilgi kutusu</li>
						<li><code>[cr_not]</code> · <code>[cr_ipucu]</code> · <code>[cr_uyari]</code> Not, ipucu, uyarı kutuları</li>
						<li><code>[cr_icerik ad="niasinamid"]</code> Sözlükten içerik kartı</li>
						<li><code>[cilt_testi]</code> Cilt testi</li>
						<li><code>[cr_bulten]</code> Bülten formu</li>
						<li>Blok ekleyici › <b>Desenler › Cilt Rotası</b>: bilgi kutusu, karşılaştırma tablosu, uzman görüşü, kontrol listesi</li>
					</ul>
				</div>
			</section>
		</div>
	</div>
	<?php
}

/**
 * Bülten aboneleri.
 */
function cr_subscribers_page() {
	$subs = get_option( 'cr_subscribers', array() );
	$subs = is_array( $subs ) ? $subs : array();
	uasort(
		$subs,
		function ( $a, $b ) {
			return ( isset( $b['t'] ) ? $b['t'] : 0 ) <=> ( isset( $a['t'] ) ? $a['t'] : 0 );
		}
	);
	?>
	<div class="wrap cr-admin">
		<?php cr_admin_header( 'Bülten Aboneleri', count( $subs ) . ' abone' ); ?>
		<?php cr_tools_notice(); ?>
		<div class="cr-filterbar">
			<?php cr_tools_form( 'subs_csv' ); ?><button class="cr-abtn" type="submit">⤓ CSV indir</button></form>
			<span class="cr-field__desc">CSV dosyasını Mailchimp, Brevo, Substack gibi servislere içe aktarabilirsin.</span>
		</div>
		<section class="cr-card-a">
			<div class="cr-card-a__body cr-card-a__body--flush">
				<table class="cr-table">
					<thead><tr><th>E-posta</th><th>Tarih</th><th>Form</th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $subs as $email => $s ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $email ); ?></strong></td>
								<td><?php echo esc_html( wp_date( 'j F Y, H:i', isset( $s['t'] ) ? $s['t'] : 0 ) ); ?></td>
								<td><?php echo esc_html( isset( $s['src'] ) ? $s['src'] : '' ); ?></td>
								<td><?php cr_tools_form( 'subs_delete', 'class="cr-inline" onsubmit="return confirm(\'Silinsin mi?\')"' ); ?><input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>"><button class="cr-mini cr-danger" type="submit">Sil</button></form></td>
							</tr>
						<?php endforeach; ?>
						<?php if ( ! $subs ) : ?>
							<tr><td colspan="4" class="cr-list__empty">Henüz abone yok. İlk abone çok yakında ♥</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
	<?php
}

/**
 * Arama analitiği.
 */
function cr_searches_page() {
	$log = get_option( 'cr_search_log', array() );
	$log = is_array( $log ) ? $log : array();
	uasort(
		$log,
		function ( $a, $b ) {
			return $b['n'] <=> $a['n'];
		}
	);
	$zero = array_filter(
		$log,
		function ( $x ) {
			return 0 === (int) $x['r'];
		}
	);
	?>
	<div class="wrap cr-admin">
		<?php cr_admin_header( 'Arama Analitiği', 'Okurların ne aradığını gör, içerik boşluklarını yakala.' ); ?>
		<?php cr_tools_notice(); ?>
		<div class="cr-dash__grid">
			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>Sonuçsuz aramalar</h2><p>Bu konularda içerik yazmak doğrudan trafik getirir.</p></header>
				<div class="cr-card-a__body cr-card-a__body--flush">
					<table class="cr-table">
						<thead><tr><th>Arama</th><th>Kez</th><th>Son</th><th></th></tr></thead>
						<tbody>
							<?php foreach ( array_slice( $zero, 0, 50, true ) as $q => $x ) : ?>
								<tr><td><strong><?php echo esc_html( $q ); ?></strong></td><td><?php echo (int) $x['n']; ?></td><td><?php echo esc_html( human_time_diff( $x['t'] ) ); ?> önce</td><td><a class="cr-mini" href="<?php echo esc_url( admin_url( 'post-new.php?post_title=' . rawurlencode( ucfirst( $q ) ) ) ); ?>">Yazı oluştur</a></td></tr>
							<?php endforeach; ?>
							<?php if ( ! $zero ) : ?>
								<tr><td colspan="4" class="cr-list__empty">Sonuçsuz arama yok.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</section>
			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>En çok arananlar</h2></header>
				<div class="cr-card-a__body cr-card-a__body--flush">
					<table class="cr-table">
						<thead><tr><th>Arama</th><th>Kez</th><th>Sonuç</th></tr></thead>
						<tbody>
							<?php foreach ( array_slice( $log, 0, 50, true ) as $q => $x ) : ?>
								<tr><td><a href="<?php echo esc_url( add_query_arg( 's', rawurlencode( $q ), home_url( '/' ) ) ); ?>" target="_blank"><?php echo esc_html( $q ); ?></a></td><td><?php echo (int) $x['n']; ?></td><td><?php echo (int) $x['r']; ?></td></tr>
							<?php endforeach; ?>
							<?php if ( ! $log ) : ?>
								<tr><td colspan="3" class="cr-list__empty">Henüz arama kaydı yok.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</section>
		</div>
		<?php if ( $log ) : ?>
			<?php cr_tools_form( 'search_clear', 'onsubmit="return confirm(\'Tüm arama kayıtları silinsin mi?\')"' ); ?><button class="cr-abtn cr-abtn--danger" type="submit">Kayıtları temizle</button></form>
		<?php endif; ?>
	</div>
	<?php
}
