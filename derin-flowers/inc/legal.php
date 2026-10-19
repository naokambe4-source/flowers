<?php
/**
 * Yasal sayfalar: Mesafeli Satış Sözleşmesi, Ön Bilgilendirme Formu, İade ve İptal Koşulları,
 * KVKK Aydınlatma Metni, Gizlilik ve Çerez Politikası. Firma bilgileri Tema Ayarları'ndan
 * [derin_firma] kısa koduyla gelir; ayar değişince sayfalar kendiliğinden güncellenir.
 * Sayfalar bir kez otomatik oluşturulur (var olan aynı adresli sayfaya dokunulmaz), footer alt
 * satırında ve ödeme adımındaki onay kutusunda bağlantı olarak görünür.
 *
 * Not: Metinler genel şablondur; yayından önce bir hukukçu / mali müşavir tarafından firmanıza
 * göre gözden geçirilmelidir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Firma bilgisi.
 *
 * @param string $key Alan.
 * @return string
 */
function df_firma( $key ) {
	$site = wp_parse_url( home_url(), PHP_URL_HOST );
	$map  = array(
		'unvan'         => df_opt( 'legal_company', '' ) ? df_opt( 'legal_company', '' ) : get_bloginfo( 'name' ),
		'marka'         => get_bloginfo( 'name' ),
		'vergi_dairesi' => df_opt( 'legal_tax_office', '' ),
		'vergi_no'      => df_opt( 'legal_tax_no', '' ),
		'mersis'        => df_opt( 'legal_mersis', '' ),
		'kep'           => df_opt( 'legal_kep', '' ),
		'adres'         => trim( preg_replace( '/\s*\n\s*/', ', ', (string) df_opt( 'contact_address', '' ) ) ),
		'telefon'       => df_opt( 'contact_phone1', '' ),
		'eposta'        => df_opt( 'contact_email', '' ),
		'site'          => $site ? $site : home_url(),
		'sehir'         => df_opt( 'df_default_city', 'İzmir' ),
	);
	return isset( $map[ $key ] ) ? (string) $map[ $key ] : '';
}

/**
 * [derin_firma alan="unvan"] — boş alanlar "…" olarak görünür ki eksik bilgi fark edilsin.
 *
 * @param array $atts Nitelikler.
 * @return string
 */
function df_firma_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'alan' => 'unvan' ), $atts );
	$v    = df_firma( sanitize_key( $atts['alan'] ) );
	return '' !== $v ? esc_html( $v ) : '<span class="df-legal__missing">……</span>';
}
add_shortcode( 'derin_firma', 'df_firma_shortcode' );

/**
 * [derin_firma_bilgileri] — satıcı bilgileri listesi (isteğe bağlı boş alanlar gizlenir).
 *
 * @return string
 */
function df_firma_list_shortcode() {
	$rows = array(
		'Unvan'      => array( 'unvan', true ),
		'Adres'      => array( 'adres', true ),
		'Telefon'    => array( 'telefon', true ),
		'E-posta'    => array( 'eposta', true ),
		'Vergi dairesi' => array( 'vergi_dairesi', true ),
		'Vergi no'   => array( 'vergi_no', true ),
		'MERSİS no'  => array( 'mersis', false ),
		'KEP adresi' => array( 'kep', false ),
		'Web sitesi' => array( 'site', true ),
	);
	$out = '<ul class="df-legal__seller">';
	foreach ( $rows as $label => $r ) {
		$v = df_firma( $r[0] );
		if ( '' === $v && ! $r[1] ) {
			continue;
		}
		$out .= '<li><strong>' . esc_html( $label ) . ':</strong> ' . ( '' !== $v ? esc_html( $v ) : '<span class="df-legal__missing">……</span>' ) . '</li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'derin_firma_bilgileri', 'df_firma_list_shortcode' );

/**
 * Yasal sayfaların tanımı.
 *
 * @return array<string,array{0:string,1:string}>
 */
function df_legal_pages() {
	$f = function ( $k ) {
		return '[derin_firma alan="' . $k . '"]';
	};
	$seller = '[derin_firma_bilgileri]';

	return array(
		'mesafeli-satis-sozlesmesi'   => array(
			'Mesafeli Satış Sözleşmesi',
			'<h2>1. Taraflar</h2>
<p><strong>SATICI</strong></p>' . $seller . '
<p><strong>ALICI</strong></p>
<p>' . $f( 'site' ) . ' üzerinden sipariş veren ve sipariş formunda adı, adresi ve iletişim bilgileri yer alan kişidir. Alıcı ile çiçeğin teslim edileceği kişi (Alıcı/Teslim alan) farklı olabilir.</p>

<h2>2. Konu</h2>
<p>Bu sözleşme, Alıcının ' . $f( 'site' ) . ' internet sitesi üzerinden elektronik ortamda sipariş verdiği ürün ve hizmetlerin satışı ve teslimi ile ilgili olarak 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince tarafların hak ve yükümlülüklerini düzenler.</p>

<h2>3. Sözleşme konusu ürün ve bedel</h2>
<p>Ürünün adı, adedi, varsa seçenekleri (gül adedi, renk, ek hediyeler), satış fiyatı (vergiler dahil), teslimat ücreti, toplam tutar, ödeme şekli, teslimat adresi, teslimat tarihi ve saat aralığı, sipariş tamamlanmadan önce ödeme sayfasında gösterilir ve sipariş onay e-postasında Alıcıya iletilir. Bu bilgiler sözleşmenin ayrılmaz parçasıdır.</p>

<h2>4. Teslimat</h2>
<ul>
<li>Ürün, Alıcının sipariş sırasında seçtiği tarih ve saat aralığında, belirttiği adrese Satıcının kuryesi veya anlaşmalı kurye ile teslim edilir. Teslimat yapılan bölgeler ve ücretleri sitede “Teslimat Bölgeleri” sayfasında yer alır.</li>
<li>Aynı gün teslimat, sitede belirtilen son sipariş saatine kadar verilen siparişler için geçerlidir.</li>
<li>Adresin eksik veya yanlış olması, teslim alacak kişiye ulaşılamaması ya da adreste kimsenin bulunmaması durumunda Satıcı, Alıcıyı arayarak bilgilendirir; ürün mümkünse komşuya, site/apartman görevlisine bırakılır veya yeni bir teslimat zamanı planlanır. Alıcı kaynaklı nedenlerle teslim edilemeyen çiçeklerde ek teslimat ücreti talep edilebilir.</li>
<li>Hastane, yoğun bakım, askeri birlik gibi çiçek kabul etmeyen yerlere teslimat yapılamaması halinde Alıcı bilgilendirilir.</li>
<li>Çiçekler doğal ürün olduğundan mevsime ve tedarik durumuna göre renk, tür ve görünümde küçük farklılıklar olabilir. Satıcı, tasarımın genel görünümünü ve değerini koruyarak benzer çiçek kullanabilir.</li>
</ul>

<h2>5. Ödeme</h2>
<p>Ödeme; kredi kartı / banka kartı (3D Secure ile, lisanslı ödeme kuruluşu aracılığıyla) veya havale/EFT ile yapılır. Kart bilgileri Satıcı tarafından görülmez ve saklanmaz. Havale/EFT ile ödenen siparişler, ödeme Satıcının hesabına geçtikten sonra hazırlanır.</p>

<h2>6. Cayma hakkı</h2>
<p>Mesafeli Sözleşmeler Yönetmeliği’nin 15. maddesi uyarınca; <strong>çabuk bozulabilen veya son kullanma tarihi geçebilecek malların</strong> (kesme çiçek, buket, aranjman, canlı bitki, pasta ve benzeri gıda ürünleri) ve <strong>Alıcının istekleri veya kişisel ihtiyaçları doğrultusunda hazırlanan malların</strong> (kişiye özel not kartı, özel adet ve tasarım siparişleri) teslimine ilişkin sözleşmelerde cayma hakkı kullanılamaz.</p>
<p>Siparişin iptali ve iade koşulları “İade ve İptal Koşulları” sayfasında açıklanmıştır.</p>

<h2>7. Ayıplı ürün</h2>
<p>Teslim edilen ürünün hasarlı, eksik veya siparişe aykırı olması halinde Alıcı, teslimattan itibaren 24 saat içinde ürünün fotoğrafıyla birlikte Satıcıya başvurur. Satıcı, inceleme sonrasında ürünü yeniler veya bedelini iade eder.</p>

<h2>8. Uyuşmazlıkların çözümü</h2>
<p>Bu sözleşmeden doğan uyuşmazlıklarda, Ticaret Bakanlığınca her yıl ilan edilen parasal sınırlar dahilinde Alıcının veya Satıcının yerleşim yerindeki Tüketici Hakem Heyetleri, bu sınırları aşan durumlarda Tüketici Mahkemeleri yetkilidir.</p>

<h2>9. Yürürlük</h2>
<p>Alıcı, ödeme sayfasında Ön Bilgilendirme Formu’nu ve bu sözleşmeyi okuyup elektronik ortamda onayladığında sözleşme kurulmuş sayılır. Sözleşme ve sipariş bilgileri Alıcının e-posta adresine gönderilir.</p>',
		),
		'on-bilgilendirme-formu'      => array(
			'Ön Bilgilendirme Formu',
			'<h2>1. Satıcı bilgileri</h2>' . $seller . '

<h2>2. Ürün, fiyat ve teslimat</h2>
<p>Siparişe konu ürünlerin temel nitelikleri ürün sayfasında; ürün adedi, vergiler dahil satış fiyatı, teslimat ücreti, toplam tutar, teslimat adresi, tarihi ve saat aralığı ödeme sayfasındaki sipariş özetinde gösterilir. Fiyatlara KDV dahildir. Teslimat ücreti teslimat bölgesine göre hesaplanır ve ödemeden önce gösterilir.</p>

<h2>3. Ödeme ve teslimat şekli</h2>
<p>Ödeme kredi/banka kartı veya havale/EFT ile yapılır. Ürünler seçilen tarih ve saat aralığında, belirtilen adrese kurye ile teslim edilir. Mağazadan teslim seçeneğinde ürün atölyeden teslim alınır.</p>

<h2>4. Cayma hakkı</h2>
<p>Kesme çiçek, buket, aranjman, canlı bitki ve gıda ürünleri çabuk bozulabilen mallardır; kişiye özel hazırlanan siparişler de Alıcının isteğine göre üretilir. Bu nedenle Mesafeli Sözleşmeler Yönetmeliği’nin 15. maddesi uyarınca <strong>bu siparişlerde cayma hakkı bulunmamaktadır</strong>. Sipariş iptali ve iade koşulları “İade ve İptal Koşulları” sayfasındadır.</p>

<h2>5. Şikâyet ve başvuru</h2>
<p>Talep ve şikâyetlerinizi ' . $f( 'telefon' ) . ' numaralı telefondan veya ' . $f( 'eposta' ) . ' adresinden iletebilirsiniz. Uyuşmazlıklarda, Ticaret Bakanlığınca ilan edilen parasal sınırlar dahilinde Tüketici Hakem Heyetleri, bu sınırları aşan durumlarda Tüketici Mahkemeleri yetkilidir.</p>',
		),
		'iade-ve-iptal-kosullari'     => array(
			'İade ve İptal Koşulları',
			'<h2>Sipariş iptali</h2>
<ul>
<li>Siparişinizi, <strong>hazırlanmaya başlanmadan önce</strong> ' . $f( 'telefon' ) . ' numarasını arayarak veya WhatsApp’tan yazarak ücretsiz iptal edebilirsiniz. İleri tarihli siparişlerde teslimattan en az 24 saat önce yapılan iptaller ücretsizdir.</li>
<li>Hazırlanmaya başlanmış veya yola çıkmış siparişler, çiçekler bozulabilir ve tekrar satılamaz olduğundan iptal edilemez. Bu durumda teslimat tarihi veya adresi değişikliği için bizi arayın; mümkün olan her durumda yardımcı oluruz.</li>
<li>Satıcı; stok, hava koşulları, adrese ulaşılamaması gibi nedenlerle siparişi yerine getiremeyecekse Alıcıyı bilgilendirir ve dilerse ödemenin tamamını iade eder.</li>
</ul>

<h2>Cayma hakkı</h2>
<p>Kesme çiçek, buket, aranjman, canlı bitki ve pasta gibi gıda ürünleri çabuk bozulabilen mallardır. Not kartı ve özel tasarımlı siparişler kişiye özel hazırlanır. Mesafeli Sözleşmeler Yönetmeliği’nin 15. maddesi uyarınca bu ürünlerde cayma hakkı kullanılamaz.</p>

<h2>Hasarlı, eksik veya farklı ürün</h2>
<ul>
<li>Teslim alınan ürün hasarlı, eksik veya siparişinizden belirgin şekilde farklıysa teslimattan itibaren <strong>24 saat içinde</strong> ürünün fotoğrafıyla birlikte bize ulaşın.</li>
<li>İnceleme sonrası ürününüz <strong>ücretsiz yenilenir</strong> veya dilerseniz <strong>bedeli iade edilir</strong>.</li>
<li>Çiçekler doğal ürün olduğundan mevsime bağlı küçük renk ve tür farklılıkları ayıp sayılmaz.</li>
</ul>

<h2>İade süreci</h2>
<p>Onaylanan iadeler, kartla yapılan ödemelerde aynı karta, havale/EFT ödemelerinde bildireceğiniz IBAN’a en geç 14 gün içinde yapılır. Karta yapılan iadenin hesabınıza yansıma süresi bankanıza bağlıdır.</p>

<h2>İletişim</h2>
<p>' . $f( 'unvan' ) . ' · ' . $f( 'telefon' ) . ' · ' . $f( 'eposta' ) . '</p>',
		),
		'kvkk-aydinlatma-metni'       => array(
			'KVKK Aydınlatma Metni',
			'<p>' . $f( 'unvan' ) . ' (“Şirket”) olarak, 6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) kapsamında veri sorumlusu sıfatıyla kişisel verilerinizi aşağıda açıklanan şekilde işliyoruz.</p>

<h2>1. İşlenen kişisel veriler</h2>
<ul>
<li><strong>Kimlik ve iletişim:</strong> ad soyad, telefon, e-posta, fatura adresi.</li>
<li><strong>Teslimat bilgileri:</strong> alıcının adı, telefonu ve teslimat adresi, teslimat tarihi ve saati.</li>
<li><strong>Sipariş bilgileri:</strong> satın alınan ürünler, not kartı mesajı, sipariş ve ödeme kayıtları (kart bilgileriniz Şirketimizce görülmez, lisanslı ödeme kuruluşunca işlenir).</li>
<li><strong>İşlem güvenliği:</strong> IP adresi, tarayıcı ve çerez bilgileri.</li>
</ul>

<h2>2. İşleme amaçları ve hukuki sebepler</h2>
<ul>
<li>Siparişin alınması, hazırlanması, teslim edilmesi, fatura düzenlenmesi ve sizinle iletişim kurulması — <em>sözleşmenin kurulması ve ifası</em> (KVKK m.5/2-c).</li>
<li>Yasal saklama ve bildirim yükümlülüklerinin yerine getirilmesi (vergi ve ticaret mevzuatı) — <em>hukuki yükümlülük</em> (m.5/2-ç).</li>
<li>Dolandırıcılığın önlenmesi, adres–teslimat bölgesi doğrulaması, site güvenliği — <em>meşru menfaat</em> (m.5/2-f).</li>
<li>Teslim sonrası memnuniyet ve değerlendirme mesajı gönderilmesi — <em>meşru menfaat</em>; kampanya ve tanıtım iletileri yalnızca <em>açık rızanız</em> ile (m.5/1).</li>
</ul>

<h2>3. Aktarım</h2>
<p>Kişisel verileriniz yalnızca yukarıdaki amaçlar için ve gerektiği ölçüde; teslimatı yapan kuryelere, ödeme kuruluşuna, bankalara, barındırma (hosting), e-posta ve mesajlaşma hizmeti sağlayıcılarına, mali müşavirimize ve talep halinde yetkili kamu kurumlarına aktarılabilir. Bazı hizmet sağlayıcıların (e-posta, mesajlaşma, adres doğrulama, bulut hizmetleri) sunucuları yurt dışında bulunabilir; bu aktarımlar KVKK m.9’daki şartlara uygun olarak yapılır.</p>

<h2>4. Toplama yöntemi</h2>
<p>Verileriniz internet sitemizdeki sipariş, üyelik ve iletişim formları, telefon, WhatsApp ve e-posta yoluyla elektronik ortamda toplanır.</p>

<h2>5. Haklarınız</h2>
<p>KVKK m.11 uyarınca; verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, amacına uygun kullanılıp kullanılmadığını öğrenme, aktarıldığı üçüncü kişileri bilme, eksik veya yanlış işlenmişse düzeltilmesini, şartları oluştuğunda silinmesini veya yok edilmesini isteme, itiraz etme ve zararın giderilmesini talep etme haklarına sahipsiniz.</p>
<p>Başvurularınızı ' . $f( 'adres' ) . ' adresine yazılı olarak veya ' . $f( 'eposta' ) . ' adresine sistemimizde kayıtlı e-posta adresinizden iletebilirsiniz. Başvurular en geç 30 gün içinde ücretsiz sonuçlandırılır.</p>',
		),
		'gizlilik-ve-cerez-politikasi' => array(
			'Gizlilik ve Çerez Politikası',
			'<h2>Gizlilik</h2>
<p>' . $f( 'unvan' ) . ' olarak ' . $f( 'site' ) . ' ziyaretçilerinin ve müşterilerimizin gizliliğine önem veriyoruz. Kişisel verilerinizin nasıl işlendiği “KVKK Aydınlatma Metni” sayfasında ayrıntılı olarak açıklanmıştır.</p>
<ul>
<li>Ödeme sırasında kart bilgileriniz 3D Secure ile doğrudan lisanslı ödeme kuruluşuna iletilir; sitemizde saklanmaz.</li>
<li>Siteye bağlantılar SSL (https) ile şifrelenir.</li>
<li>Bilgileriniz satılmaz, kiralanmaz; yalnızca siparişinizin yerine getirilmesi için gereken taraflarla paylaşılır.</li>
</ul>

<h2>Çerezler</h2>
<p>Çerezler, siteyi ziyaret ettiğinizde tarayıcınıza kaydedilen küçük dosyalardır. Sitemizde şu çerezler kullanılır:</p>
<ul>
<li><strong>Zorunlu çerezler:</strong> sepet, oturum, ödeme adımları ve güvenlik için gereklidir; kapatılırsa sipariş verilemez.</li>
<li><strong>Tercih çerezleri:</strong> favori ürünleriniz ve dil tercihiniz gibi seçimlerinizi hatırlar.</li>
<li><strong>Analiz çerezleri:</strong> (kullanılıyorsa) siteyi nasıl kullandığınızı anonim olarak ölçerek hizmetimizi geliştirmemize yardımcı olur.</li>
</ul>
<p>Tarayıcınızın ayarlarından çerezleri silebilir veya engelleyebilirsiniz; ancak zorunlu çerezler engellenirse sipariş işlemleri çalışmayabilir.</p>

<h2>İletişim</h2>
<p>Sorularınız için: ' . $f( 'eposta' ) . ' · ' . $f( 'telefon' ) . '</p>',
		),
	);
}

/**
 * Eksik yasal sayfaları oluştur; WooCommerce şartlar ve WordPress gizlilik sayfasını ata.
 *
 * @return int Oluşturulan sayfa sayısı.
 */
function df_legal_create() {
	$made = 0;
	$ids  = array();
	foreach ( df_legal_pages() as $slug => $p ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$ids[ $slug ] = (int) $page->ID;
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $p[0],
				'post_name'    => $slug,
				'post_content' => $p[1],
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			$ids[ $slug ] = (int) $id;
			++$made;
		}
	}
	if ( ! get_page_by_path( 'iletisim' ) ) {
		wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'İletişim', 'post_name' => 'iletisim', 'post_content' => '[derin_iletisim]' ) );
		++$made;
	}
	if ( ! empty( $ids['mesafeli-satis-sozlesmesi'] ) && ! (int) get_option( 'woocommerce_terms_page_id' ) ) {
		update_option( 'woocommerce_terms_page_id', $ids['mesafeli-satis-sozlesmesi'] );
	}
	$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( ! empty( $ids['kvkk-aydinlatma-metni'] ) && ( ! $privacy || 'publish' !== get_post_status( $privacy ) ) ) {
		update_option( 'wp_page_for_privacy_policy', $ids['kvkk-aydinlatma-metni'] );
	}
	return $made;
}

/**
 * Tema güncellenince bir kez oluştur.
 */
function df_legal_autocreate() {
	if ( get_option( 'df_legal_seeded' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	update_option( 'df_legal_seeded', 1, false );
	df_legal_create();
}
add_action( 'admin_init', 'df_legal_autocreate' );

/**
 * "Yasal sayfaları oluştur" düğmesi (silinen sayfayı geri getirmek için).
 */
function df_legal_action() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'df_legal' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$n = df_legal_create();
	wp_safe_redirect( add_query_arg( 'df_legal', $n, admin_url( 'edit.php?post_type=page' ) ) );
	exit;
}
add_action( 'admin_post_df_legal', 'df_legal_action' );

/**
 * Footer alt satırı için yasal bağlantılar (Yasal menü atanmamışsa).
 *
 * @return array<int,array{title:string,url:string}>
 */
function df_legal_links() {
	$out = array();
	foreach ( array_merge( array_keys( df_legal_pages() ), array( 'iletisim' ) ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$out[] = array(
				'title' => get_the_title( $page ),
				'url'   => get_permalink( $page ),
			);
		}
	}
	return $out;
}

/**
 * Ödeme adımındaki onay kutusu metni.
 *
 * @param string $text Metin.
 * @return string
 */
function df_legal_terms_text( $text ) {
	$pre = get_page_by_path( 'on-bilgilendirme-formu' );
	$mss = (int) get_option( 'woocommerce_terms_page_id' );
	if ( ! $mss ) {
		return $text;
	}
	$a = $pre ? '<a href="' . esc_url( get_permalink( $pre ) ) . '" target="_blank" rel="noopener">Ön Bilgilendirme Formu</a>’nu ve ' : '';
	return $a . '<a href="' . esc_url( get_permalink( $mss ) ) . '" class="woocommerce-terms-and-conditions-link" target="_blank" rel="noopener">Mesafeli Satış Sözleşmesi</a>’ni okudum, onaylıyorum.';
}
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'df_legal_terms_text' );

/**
 * Ödeme sayfasındaki gizlilik notu.
 *
 * @return string
 */
function df_legal_privacy_text() {
	return 'Kişisel verileriniz siparişinizin hazırlanması ve teslimi amacıyla [privacy_policy] kapsamında işlenir.';
}
add_filter( 'woocommerce_checkout_privacy_policy_text', 'df_legal_privacy_text' );
