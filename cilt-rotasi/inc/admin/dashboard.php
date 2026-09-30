<?php
/**
 * Kontrol Paneli (karşılama + istatistik + içerik sağlığı) ve SEO Denetimi.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tek içerik için SEO/AEO/GEO denetimi.
 *
 * @param int $id Yazı.
 * @return array{score:int,checks:array}
 */
function cr_seo_audit( $id ) {
	$post    = get_post( $id );
	$type    = $post->post_type;
	$content = (string) $post->post_content;
	$title   = get_post_meta( $id, '_cr_seo_title', true );
	$title   = $title ? $title : get_the_title( $id ) . ' ' . cr_opt( 'seo_sep', '—' ) . ' ' . get_bloginfo( 'name' );
	$desc    = get_post_meta( $id, '_cr_seo_desc', true );
	$desc    = $desc ? $desc : ( $post->post_excerpt ? $post->post_excerpt : get_post_meta( $id, '_cr_short_answer', true ) );
	$kw      = cr_lower( trim( (string) get_post_meta( $id, '_cr_focus_kw', true ) ) );
	$words   = cr_word_count( $content );
	$len     = function ( $s ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $s ) : strlen( (string) $s );
	};
	$plain   = cr_lower( wp_strip_all_tags( strip_shortcodes( $content ) ) );
	$first   = implode( ' ', array_slice( preg_split( '/\s+/', $plain ), 0, 110 ) );
	$min     = 'icerik' === $type ? 250 : ( 'page' === $type ? 150 : ( 'urun_rehberi' === $type ? 300 : 600 ) );
	$links   = preg_match_all( '#href="(' . preg_quote( home_url(), '#' ) . '|/)[^"]*"#i', $content );
	$h2      = preg_match_all( '#<h2[\s>]#i', $content ) + substr_count( $content, 'wp:heading {"level":2' );
	$h2      = max( $h2, preg_match_all( '#<!-- wp:heading -->#', $content ) );
	$noalt   = preg_match_all( '#<img(?![^>]*\balt="[^"]+")[^>]*>#i', $content );
	$faq     = count( cr_faq_items( $id ) );
	$slug    = str_replace( '-', ' ', $post->post_name );

	$c   = array();
	$add = function ( $key, $label, $ok, $group, $weight = 1, $hint = '' ) use ( &$c ) {
		$c[] = array( 'key' => $key, 'label' => $label, 'ok' => (bool) $ok, 'group' => $group, 'w' => $weight, 'hint' => $hint );
	};
	$tl = $len( $title );
	$dl = $len( $desc );
	$add( 'title', 'SEO başlığı 30–65 karakter (' . $tl . ')', $tl >= 30 && $tl <= 65, 'SEO', 2 );
	$add( 'desc', 'Meta açıklama 110–165 karakter (' . $dl . ')', $dl >= 110 && $dl <= 165, 'SEO', 2, 'SEO kutusundaki “Meta açıklama” alanını doldurun.' );
	$add( 'kw', 'Odak anahtar kelime belirlenmiş', '' !== $kw, 'SEO', 1 );
	if ( '' !== $kw ) {
		$add( 'kw_title', 'Anahtar kelime başlıkta', false !== strpos( cr_lower( $title ), $kw ), 'SEO', 2 );
		$add( 'kw_desc', 'Anahtar kelime açıklamada', false !== strpos( cr_lower( $desc ), $kw ), 'SEO', 1 );
		$add( 'kw_first', 'Anahtar kelime ilk paragrafta', false !== strpos( $first, $kw ), 'SEO', 1 );
		$add( 'kw_slug', 'Anahtar kelime adreste (slug)', false !== strpos( $slug, sanitize_title( $kw ) ) || false !== strpos( $post->post_name, sanitize_title( $kw ) ), 'SEO', 1 );
	}
	if ( 'page' !== $type ) {
		$add( 'image', 'Öne çıkan görsel', has_post_thumbnail( $id ) || get_post_meta( $id, '_cr_ext_image', true ), 'SEO', 1 );
	}
	$add( 'words', 'İçerik uzunluğu en az ' . $min . ' kelime (' . $words . ')', $words >= $min, 'SEO', 2 );
	if ( 'post' === $type ) {
		$add( 'h2', 'En az 2 ara başlık (H2)', $h2 >= 2, 'SEO', 1 );
		$add( 'links', 'En az 2 iç bağlantı (' . (int) $links . ')', $links >= 2, 'SEO', 1, 'İlgili rehberlere ve sözlük içeriklerine bağlantı verin.' );
	}
	$add( 'alt', 'İçerikteki tüm görsellerde alt metin', 0 === (int) $noalt, 'SEO', 1 );
	$add( 'short', 'Kısa cevap (40–60 kelime) — AEO', '' !== trim( (string) get_post_meta( $id, '_cr_short_answer', true ) ) || ( 'icerik' === $type && get_post_meta( $id, '_cr_function', true ) ), 'AEO', 2 );
	if ( 'page' !== $type ) {
		$add( 'faq', 'En az 2 soru-cevap (FAQ) — AEO', $faq >= 2, 'AEO', 2 );
		$add( 'takeaways', '“Akılda kalsın” maddeleri — AEO', '' !== trim( (string) get_post_meta( $id, '_cr_takeaways', true ) ) || 'icerik' === $type, 'AEO', 1 );
		$add( 'sources', 'Kaynak gösterilmiş — GEO', '' !== trim( (string) get_post_meta( $id, '_cr_sources', true ) ), 'GEO', 2, 'Hakemli çalışma veya kılavuz bağlantıları ekleyin.' );
		$add( 'reviewer', 'Uzman kontrolü bilgisi — E-E-A-T', '' !== trim( (string) get_post_meta( $id, '_cr_reviewer', true ) ) || '' !== cr_opt( 'art_reviewer_default' ), 'GEO', 1 );
		$add( 'author', 'Yazar biyografisi — E-E-A-T', '' !== trim( (string) get_the_author_meta( 'description', $post->post_author ) ), 'GEO', 1 );
	}
	$add( 'fresh', 'Son 12 ayda güncellenmiş', strtotime( cr_updated( $id ) ) > strtotime( '-12 months' ), 'GEO', 1 );

	$total = 0;
	$ok    = 0;
	foreach ( $c as $x ) {
		$total += $x['w'];
		$ok    += $x['ok'] ? $x['w'] : 0;
	}
	return array(
		'score'  => $total ? (int) round( $ok / $total * 100 ) : 0,
		'checks' => $c,
	);
}

/**
 * Denetim dışı tutulan sayfalar (ana sayfa, rehberler listesi, şablon sayfaları, noindex).
 *
 * @param int $id Yazı.
 * @return bool
 */
function cr_audit_skip( $id ) {
	if ( in_array( (int) $id, array( (int) get_option( 'page_on_front' ), (int) get_option( 'page_for_posts' ) ), true ) ) {
		return true;
	}
	if ( '1' === (string) get_post_meta( $id, '_cr_noindex', true ) ) {
		return true;
	}
	return 'page' === get_post_type( $id ) && '' !== (string) get_page_template_slug( $id );
}

/**
 * Skor rengi.
 *
 * @param int $s Skor.
 * @return string
 */
function cr_score_class( $s ) {
	return $s >= 80 ? 'is-good' : ( $s >= 55 ? 'is-mid' : 'is-bad' );
}

/**
 * Selamlama.
 *
 * @return string
 */
function cr_time_greeting() {
	$h = (int) current_time( 'G' );
	if ( $h < 5 ) {
		return 'İyi geceler';
	}
	if ( $h < 12 ) {
		return 'Günaydın';
	}
	if ( $h < 18 ) {
		return 'İyi günler';
	}
	return 'İyi akşamlar';
}

/**
 * Kontrol Paneli.
 */
function cr_dashboard_page() {
	$user  = wp_get_current_user();
	$notes = cr_lines( cr_opt( 'adm_notes' ) );
	$note  = $notes ? $notes[ array_rand( $notes ) ] : '';
	$types = array( 'post', 'icerik', 'urun_rehberi' );
	$ids   = get_posts(
		array(
			'post_type'      => $types,
			'posts_per_page' => 300,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	_prime_post_caches( $ids, false, true );
	$health = array(
		'desc'    => array( 'label' => 'Meta açıklaması eksik', 'ids' => array() ),
		'short'   => array( 'label' => 'Kısa cevabı (AEO) eksik', 'ids' => array() ),
		'faq'     => array( 'label' => 'SSS eklenmemiş', 'ids' => array() ),
		'sources' => array( 'label' => 'Kaynak gösterilmemiş', 'ids' => array() ),
		'image'   => array( 'label' => 'Görseli yok', 'ids' => array() ),
		'words'   => array( 'label' => 'İçeriği kısa', 'ids' => array() ),
		'fresh'   => array( 'label' => '12 aydan eski', 'ids' => array() ),
	);
	$sum   = 0;
	$views = 0;
	foreach ( $ids as $id ) {
		$a    = cr_seo_audit( $id );
		$sum += $a['score'];
		$views += cr_views( $id );
		foreach ( $a['checks'] as $ch ) {
			if ( ! $ch['ok'] && isset( $health[ $ch['key'] ] ) ) {
				$health[ $ch['key'] ][ 'ids' ][] = $id;
			}
		}
	}
	$avg  = $ids ? (int) round( $sum / count( $ids ) ) : 0;
	$subs = get_option( 'cr_subscribers', array() );
	$subs = is_array( $subs ) ? $subs : array();
	$new7 = 0;
	foreach ( $subs as $s ) {
		if ( isset( $s['t'] ) && $s['t'] > time() - WEEK_IN_SECONDS ) {
			$new7++;
		}
	}
	$log  = get_option( 'cr_search_log', array() );
	$log  = is_array( $log ) ? $log : array();
	$zero = array_filter(
		$log,
		function ( $x ) {
			return 0 === (int) $x['r'];
		}
	);
	uasort(
		$zero,
		function ( $a, $b ) {
			return $b['n'] <=> $a['n'];
		}
	);
	$zero = array_slice( $zero, 0, 8, true );

	$popular = get_posts(
		array(
			'post_type'      => $types,
			'posts_per_page' => 5,
			'meta_key'       => '_cr_views', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	$recent = get_posts(
		array(
			'post_type'      => $types,
			'posts_per_page' => 6,
			'orderby'        => 'modified',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
			'no_found_rows'  => true,
		)
	);
	$count = function ( $t, $s = 'publish' ) {
		$c = wp_count_posts( $t );
		return isset( $c->$s ) ? (int) $c->$s : 0;
	};
	$drafts = $count( 'post', 'draft' ) + $count( 'icerik', 'draft' ) + $count( 'urun_rehberi', 'draft' );
	?>
	<div class="wrap cr-admin cr-dash">
		<section class="cr-love" aria-label="Karşılama">
			<div class="cr-love__hearts" aria-hidden="true" data-hearts></div>
			<div class="cr-love__inner">
				<p class="cr-love__hello"><?php echo esc_html( cr_time_greeting() . ', ' . $user->display_name ); ?> · <?php echo esc_html( date_i18n( 'j F Y, l' ) ); ?></p>
				<h1 class="cr-love__title"><?php echo esc_html( cr_opt( 'adm_greeting' ) ); ?> <span class="cr-love__heart">♥</span></h1>
				<?php if ( $note ) : ?>
					<p class="cr-love__note"><?php echo esc_html( $note ); ?></p>
				<?php endif; ?>
				<div class="cr-love__actions">
					<a class="cr-abtn cr-abtn--light" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">+ Yeni rehber</a>
					<a class="cr-abtn cr-abtn--glass" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=icerik' ) ); ?>">+ Sözlüğe içerik</a>
					<a class="cr-abtn cr-abtn--glass" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=urun_rehberi' ) ); ?>">+ Ürün incelemesi</a>
					<a class="cr-abtn cr-abtn--glass" href="<?php echo esc_url( add_query_arg( 'cr_edit', '1', home_url( '/' ) ) ); ?>">✎ Canlı düzenle</a>
				</div>
			</div>
		</section>

		<div class="cr-stats">
			<?php
			$stats = array(
				array( 'Yayındaki rehber', $count( 'post' ), 'book', admin_url( 'edit.php' ) ),
				array( 'Sözlükteki içerik', $count( 'icerik' ), 'flask', admin_url( 'edit.php?post_type=icerik' ) ),
				array( 'Ürün incelemesi', $count( 'urun_rehberi' ), 'drop', admin_url( 'edit.php?post_type=urun_rehberi' ) ),
				array( 'Taslak', $drafts, 'edit', admin_url( 'edit.php?post_status=draft' ) ),
				array( 'Bülten abonesi', count( $subs ) . ( $new7 ? ' <small>+' . $new7 . ' bu hafta</small>' : '' ), 'mail', admin_url( 'admin.php?page=cilt-rotasi-aboneler' ) ),
				array( 'Toplam okunma', number_format_i18n( $views ), 'eye', '' ),
			);
			foreach ( $stats as $s ) :
				?>
				<a class="cr-stat" <?php echo $s[3] ? 'href="' . esc_url( $s[3] ) . '"' : ''; ?>>
					<span class="cr-stat__icon"><?php echo cr_icon( $s[2], 20 ); // phpcs:ignore ?></span>
					<span class="cr-stat__num"><?php echo wp_kses_post( (string) $s[1] ); ?></span>
					<span class="cr-stat__label"><?php echo esc_html( $s[0] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="cr-dash__grid">
			<section class="cr-card-a cr-dash__score">
				<header class="cr-card-a__head"><h2>SEO · AEO · GEO sağlığı</h2><p>Yayındaki <?php echo count( $ids ); ?> içeriğin ortalama skoru.</p></header>
				<div class="cr-card-a__body">
					<div class="cr-ring <?php echo esc_attr( cr_score_class( $avg ) ); ?>" style="--p:<?php echo (int) $avg; ?>">
						<span><?php echo (int) $avg; ?><small>/100</small></span>
					</div>
					<ul class="cr-health">
						<?php foreach ( $health as $key => $h ) : ?>
							<li class="<?php echo $h['ids'] ? 'has-issue' : 'is-ok'; ?>">
								<span><?php echo $h['ids'] ? '●' : '✓'; ?> <?php echo esc_html( $h['label'] ); ?></span>
								<?php if ( $h['ids'] ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=cilt-rotasi-seo&sorun=' . $key ) ); ?>"><?php echo count( $h['ids'] ); ?> içerik →</a>
								<?php else : ?>
									<em>Tamam</em>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<a class="cr-abtn cr-abtn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=cilt-rotasi-seo' ) ); ?>">Tam denetimi aç</a>
				</div>
			</section>

			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>Son düzenlenenler</h2></header>
				<div class="cr-card-a__body">
					<ul class="cr-list">
						<?php foreach ( $recent as $p ) : ?>
							<?php $a = cr_seo_audit( $p->ID ); ?>
							<li>
								<span class="cr-score <?php echo esc_attr( cr_score_class( $a['score'] ) ); ?>"><?php echo (int) $a['score']; ?></span>
								<a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ? get_the_title( $p ) : '(başlıksız)' ); ?></a>
								<em><?php echo 'publish' === $p->post_status ? esc_html( human_time_diff( strtotime( $p->post_modified_gmt . ' UTC' ) ) . ' önce' ) : 'Taslak'; ?></em>
							</li>
						<?php endforeach; ?>
						<?php if ( ! $recent ) : ?>
							<li class="cr-list__empty">Henüz içerik yok. <a href="<?php echo esc_url( admin_url( 'admin.php?page=cilt-rotasi-araclar' ) ); ?>">Kurulum sihirbazıyla</a> örnek içerikleri ekleyebilirsin.</li>
						<?php endif; ?>
					</ul>
				</div>
			</section>

			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>En çok okunanlar</h2></header>
				<div class="cr-card-a__body">
					<ol class="cr-list cr-list--num">
						<?php foreach ( $popular as $p ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $p ) ); ?></a><em><?php echo esc_html( number_format_i18n( cr_views( $p->ID ) ) ); ?> okunma</em></li>
						<?php endforeach; ?>
						<?php if ( ! $popular ) : ?>
							<li class="cr-list__empty">Okunma verisi biriktikçe burada görünecek.</li>
						<?php endif; ?>
					</ol>
				</div>
			</section>

			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>İçerik fırsatları</h2><p>Okurların aradığı ama sitede karşılığı olmayan konular.</p></header>
				<div class="cr-card-a__body">
					<ul class="cr-list">
						<?php foreach ( $zero as $q => $x ) : ?>
							<li><strong>“<?php echo esc_html( $q ); ?>”</strong><em><?php echo (int) $x['n']; ?> arama</em><a class="cr-mini" href="<?php echo esc_url( admin_url( 'post-new.php?post_title=' . rawurlencode( ucfirst( $q ) ) ) ); ?>">Yazı oluştur</a></li>
						<?php endforeach; ?>
						<?php if ( ! $zero ) : ?>
							<li class="cr-list__empty">Harika — şu an sonuçsuz kalan arama yok.</li>
						<?php endif; ?>
					</ul>
					<a class="cr-abtn cr-abtn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=cilt-rotasi-aramalar' ) ); ?>">Arama analitiği</a>
				</div>
			</section>

			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>Yapay zekâ görünürlüğü (GEO)</h2></header>
				<div class="cr-card-a__body">
					<ul class="cr-health">
						<li class="<?php echo cr_opt( 'geo_llms' ) ? 'is-ok' : 'has-issue'; ?>"><span>llms.txt</span><?php echo cr_opt( 'geo_llms' ) ? '<a href="' . esc_url( home_url( '/llms.txt' ) ) . '" target="_blank">Görüntüle ↗</a>' : '<em>Kapalı</em>'; ?></li>
						<li class="<?php echo cr_opt( 'geo_llms' ) ? 'is-ok' : 'has-issue'; ?>"><span>llms-full.txt</span><?php echo cr_opt( 'geo_llms' ) ? '<a href="' . esc_url( home_url( '/llms-full.txt' ) ) . '" target="_blank">Görüntüle ↗</a>' : '<em>Kapalı</em>'; ?></li>
						<li class="is-ok"><span>XML site haritası</span><a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>" target="_blank">Görüntüle ↗</a></li>
						<li class="is-ok"><span>robots.txt</span><a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank">Görüntüle ↗</a></li>
						<li class="<?php echo 'block' === cr_opt( 'geo_ai_bots' ) ? 'has-issue' : 'is-ok'; ?>"><span>Yapay zekâ botları</span><em><?php echo esc_html( array( 'allow' => 'İzinli', 'search' => 'Yalnızca arama botları', 'block' => 'Engelli' )[ cr_opt( 'geo_ai_bots', 'allow' ) ] ); ?></em></li>
						<li class="<?php echo get_option( 'blog_public' ) ? 'is-ok' : 'has-issue'; ?>"><span>Arama motorlarına açık</span><em><?php echo get_option( 'blog_public' ) ? 'Evet' : 'Hayır — Ayarlar › Okuma'; ?></em></li>
						<li class="<?php echo get_option( 'permalink_structure' ) ? 'is-ok' : 'has-issue'; ?>"><span>Okunaklı kalıcı bağlantılar</span><em><?php echo get_option( 'permalink_structure' ) ? 'Açık' : 'Kapalı'; ?></em></li>
						<li class="<?php echo cr_seo_plugin() ? 'is-ok' : ( cr_opt( 'seo_enable' ) ? 'is-ok' : 'has-issue' ); ?>"><span>SEO motoru</span><em><?php echo esc_html( cr_seo_plugin() ? cr_seo_plugin() . ' (tema uyumlu)' : ( cr_opt( 'seo_enable' ) ? 'Cilt Rotası SEO' : 'Kapalı' ) ); ?></em></li>
					</ul>
				</div>
			</section>

			<section class="cr-card-a">
				<header class="cr-card-a__head"><h2>Kurulum kontrol listesi</h2></header>
				<div class="cr-card-a__body">
					<?php
					$todo = array(
						array( 'Logo yüklendi', (bool) cr_opt( 'logo_image' ) || (bool) cr_opt( 'logo_text' ), admin_url( 'admin.php?page=cilt-rotasi-ayarlar&tab=brand' ) ),
						array( 'Site simgesi (favicon)', (bool) get_option( 'site_icon' ), admin_url( 'customize.php?autofocus[section]=title_tagline' ) ),
						array( 'Şema logosu (Organization)', (bool) cr_opt( 'org_logo' ) || (bool) cr_opt( 'logo_image' ), admin_url( 'admin.php?page=cilt-rotasi-ayarlar&tab=seo' ) ),
						array( 'Search Console doğrulaması', (bool) cr_opt( 'verify_google' ), admin_url( 'admin.php?page=cilt-rotasi-ayarlar&tab=seo' ) ),
						array( 'Sosyal medya hesapları', (bool) cr_social_links(), admin_url( 'admin.php?page=cilt-rotasi-ayarlar&tab=footer' ) ),
						array( 'Yazar biyografisi ve unvanı', (bool) get_the_author_meta( 'description', get_current_user_id() ), admin_url( 'profile.php#cr-author' ) ),
						array( 'Menüler atandı', has_nav_menu( 'primary' ), admin_url( 'nav-menus.php' ) ),
						array( 'Yayın ilkeleri sayfası', (bool) get_page_by_path( 'yayin-ilkeleri' ), admin_url( 'admin.php?page=cilt-rotasi-araclar' ) ),
					);
					?>
					<ul class="cr-todo">
						<?php foreach ( $todo as $t ) : ?>
							<li class="<?php echo $t[1] ? 'is-done' : ''; ?>"><span class="cr-todo__box"><?php echo $t[1] ? '✓' : ''; ?></span><a href="<?php echo esc_url( $t[2] ); ?>"><?php echo esc_html( $t[0] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		</div>
		<p class="cr-dash__foot">Cilt Rotası teması v<?php echo esc_html( CR_VERSION ); ?> · sevgiyle yapıldı ♥</p>
	</div>
	<?php
}

/**
 * SEO Denetimi sayfası.
 */
function cr_seo_audit_page() {
	$filter = isset( $_GET['sorun'] ) ? sanitize_key( wp_unslash( $_GET['sorun'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$ptype  = isset( $_GET['tur'] ) ? sanitize_key( wp_unslash( $_GET['tur'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$types  = $ptype && in_array( $ptype, array( 'post', 'icerik', 'urun_rehberi', 'page' ), true ) ? array( $ptype ) : array( 'post', 'icerik', 'urun_rehberi', 'page' );
	$ids    = get_posts(
		array(
			'post_type'      => $types,
			'posts_per_page' => 400,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	_prime_post_caches( $ids, false, true );
	$rows   = array();
	foreach ( $ids as $id ) {
		if ( cr_audit_skip( $id ) ) {
			continue;
		}
		$a      = cr_seo_audit( $id );
		$failed = array_filter(
			$a['checks'],
			function ( $c ) {
				return ! $c['ok'];
			}
		);
		if ( $filter ) {
			$has = false;
			foreach ( $failed as $f ) {
				if ( $f['key'] === $filter ) {
					$has = true;
				}
			}
			if ( ! $has ) {
				continue;
			}
		}
		$rows[] = array( 'id' => $id, 'score' => $a['score'], 'failed' => $failed );
	}
	usort(
		$rows,
		function ( $a, $b ) {
			return $a['score'] <=> $b['score'];
		}
	);
	$labels = array( 'post' => 'Rehber', 'icerik' => 'Sözlük', 'urun_rehberi' => 'Ürün', 'page' => 'Sayfa' );
	?>
	<div class="wrap cr-admin">
		<?php cr_admin_header( 'SEO Denetimi', 'Her içerik; klasik SEO, yanıt motorları (AEO) ve üretken yapay zekâ (GEO) açısından puanlanır.' ); ?>
		<div class="cr-filterbar">
			<a class="cr-pill<?php echo ! $ptype ? ' is-active' : ''; ?>" href="<?php echo esc_url( remove_query_arg( 'tur' ) ); ?>">Tümü</a>
			<?php foreach ( $labels as $k => $l ) : ?>
				<a class="cr-pill<?php echo $k === $ptype ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tur', $k ) ); ?>"><?php echo esc_html( $l ); ?></a>
			<?php endforeach; ?>
			<?php if ( $filter ) : ?>
				<a class="cr-pill is-warn" href="<?php echo esc_url( remove_query_arg( 'sorun' ) ); ?>">Filtre: <?php echo esc_html( $filter ); ?> ✕</a>
			<?php endif; ?>
		</div>
		<section class="cr-card-a">
			<div class="cr-card-a__body cr-card-a__body--flush">
				<table class="cr-table">
					<thead><tr><th>Skor</th><th>İçerik</th><th>Tür</th><th>Eksikler</th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $rows as $r ) : ?>
							<tr>
								<td><span class="cr-score <?php echo esc_attr( cr_score_class( $r['score'] ) ); ?>"><?php echo (int) $r['score']; ?></span></td>
								<td><a href="<?php echo esc_url( get_edit_post_link( $r['id'] ) ); ?>"><strong><?php echo esc_html( get_the_title( $r['id'] ) ); ?></strong></a></td>
								<td><?php echo esc_html( $labels[ get_post_type( $r['id'] ) ] ); ?></td>
								<td>
									<?php foreach ( array_slice( $r['failed'], 0, 5 ) as $f ) : ?>
										<span class="cr-tag cr-tag--<?php echo esc_attr( strtolower( $f['group'] ) ); ?>" title="<?php echo esc_attr( $f['hint'] ); ?>"><?php echo esc_html( $f['group'] . ': ' . $f['label'] ); ?></span>
									<?php endforeach; ?>
									<?php if ( count( $r['failed'] ) > 5 ) : ?>
										<span class="cr-tag">+<?php echo count( $r['failed'] ) - 5; ?></span>
									<?php endif; ?>
									<?php if ( ! $r['failed'] ) : ?>
										<span class="cr-tag cr-tag--ok">Kusursuz ✓</span>
									<?php endif; ?>
								</td>
								<td><a class="cr-mini" href="<?php echo esc_url( get_edit_post_link( $r['id'] ) ); ?>">Düzenle</a> <a class="cr-mini" href="<?php echo esc_url( get_permalink( $r['id'] ) ); ?>" target="_blank">Gör</a></td>
							</tr>
						<?php endforeach; ?>
						<?php if ( ! $rows ) : ?>
							<tr><td colspan="5" class="cr-list__empty">Bu filtrede içerik yok.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
	<?php
}

/**
 * WordPress başlangıç ekranına kısa yol kutusu.
 */
function cr_wp_dashboard_widget() {
	wp_add_dashboard_widget(
		'cr_love_widget',
		'♥ Cilt Rotası',
		function () {
			echo '<p style="font-size:18px;margin:0 0 10px"><strong>' . esc_html( cr_opt( 'adm_greeting' ) ) . ' ♥</strong></p>';
			echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=cilt-rotasi' ) ) . '">Cilt Rotası paneline git</a></p>';
		}
	);
}
add_action( 'wp_dashboard_setup', 'cr_wp_dashboard_widget' );
