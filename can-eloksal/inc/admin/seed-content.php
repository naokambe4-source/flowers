<?php
/**
 * Başlangıç içerikleri: hizmetler, kategoriler, sektörler, hero slaytı, banka hesapları,
 * sayfalar, teknik blog yazıları ve menüler. Kurulum aracı tarafından çağrılır; tekrar
 * çalıştırıldığında var olan kayıtları değiştirmez (yalnızca eksikleri ekler).
 *
 * Firma hakkında sertifika, referans, kapasite, kuruluş yılı veya rakam üretilmez.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Basit işaretlemeyi Gutenberg bloklarına çevirir.
 * "## Başlık", "- madde" ve boş satırla ayrılmış paragraflar desteklenir.
 *
 * @param string $text Metin.
 * @return string
 */
function ce_md_blocks( $text ) {
	$out    = '';
	$chunks = preg_split( '/\n\s*\n/', trim( str_replace( "\r", '', $text ) ) );
	foreach ( $chunks as $chunk ) {
		$chunk = trim( $chunk );
		if ( 0 === strpos( $chunk, '## ' ) ) {
			$out .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( substr( $chunk, 3 ) ) . "</h2>\n<!-- /wp:heading -->\n\n";
		} elseif ( 0 === strpos( $chunk, '- ' ) ) {
			$out .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
			foreach ( explode( "\n", $chunk ) as $li ) {
				$out .= '<!-- wp:list-item --><li>' . esc_html( ltrim( substr( trim( $li ), 1 ) ) ) . '</li><!-- /wp:list-item -->';
			}
			$out .= "</ul>\n<!-- /wp:list -->\n\n";
		} else {
			$out .= "<!-- wp:paragraph -->\n<p>" . esc_html( $chunk ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
	}
	return trim( $out );
}

/**
 * Terim oluşturur veya var olanı döndürür.
 *
 * @param string $name     Ad.
 * @param string $taxonomy Taksonomi.
 * @param string $slug     Kısa ad.
 * @param string $desc     Açıklama.
 * @return int
 */
function ce_seed_term( $name, $taxonomy, $slug, $desc = '' ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug, 'description' => $desc ) );
	return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
}

/**
 * Kayıt oluşturur (aynı slug varsa dokunmaz).
 *
 * @param array $args wp_insert_post argümanları + meta + terms.
 * @param array $log  Günlük.
 * @return int
 */
function ce_seed_post( $args, &$log ) {
	$existing = get_posts(
		array(
			'post_type'   => $args['post_type'],
			'name'        => $args['post_name'],
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	$meta  = $args['meta'] ?? array();
	$terms = $args['terms'] ?? array();
	unset( $args['meta'], $args['terms'] );
	$args  = wp_parse_args( $args, array( 'post_status' => 'publish', 'comment_status' => 'closed', 'ping_status' => 'closed' ) );
	$id    = wp_insert_post( wp_slash( $args ), true );
	if ( is_wp_error( $id ) ) {
		$log[] = '✗ ' . $args['post_title'] . ': ' . $id->get_error_message();
		return 0;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, 0 === strpos( $k, '_' ) ? $k : '_ce_' . $k, $v );
	}
	foreach ( $terms as $tax => $ids ) {
		wp_set_object_terms( $id, $ids, $tax );
	}
	$log[] = '✓ ' . $args['post_title'];
	return (int) $id;
}

/**
 * Hizmet verileri.
 *
 * @param array $cats Kategori ID'leri.
 * @return array
 */
function ce_seed_services( $cats ) {
	$common_specs = static function ( $color, $surface = 'Mat / Parlak / Saten (ön işleme göre)' ) {
		return array(
			array( 'label' => 'Uygulanan malzeme', 'value' => 'Alüminyum ve alüminyum alaşımları' ),
			array( 'label' => 'Renk', 'value' => $color ),
			array( 'label' => 'Yüzey görünümü', 'value' => $surface ),
			array( 'label' => 'Kaplama kalınlığı', 'value' => 'Talep edilen mikron değerine göre' ),
			array( 'label' => 'Son işlem', 'value' => 'Sızdırmazlık (sealing)' ),
		);
	};
	$color_process = "## Renkli eloksal nasıl yapılır?\n\nParça önce yağ alma ve dağlama gibi ön işlemlerden geçirilir, ardından anodik oksidasyonla gözenekli bir oksit tabakası oluşturulur. Bu gözenekler renk banyosunda boyar maddeyi emer; son adımda yapılan sızdırmazlık işlemi gözenekleri kapatarak rengi tabakanın içine hapseder.\n\nRengin tonu; alaşım türüne, yüzeyin mat ya da parlak hazırlanmasına ve oksit tabakasının kalınlığına bağlı olarak değişebilir. Seri üretim öncesinde numune parça üzerinde renk onayı alınması önerilir.";

	return array(
		array(
			'title'   => 'Naturel Eloksal',
			'slug'    => 'naturel-eloksal',
			'cat'     => $cats['eloksal'],
			'tone'    => 'natural',
			'summary' => 'Alüminyumun doğal metalik görünümünü koruyan; korozyon ve aşınma direncini artıran kontrollü oksit tabakası.',
			'content' => "Naturel eloksal, alüminyum yüzeyinde elektrokimyasal yöntemle kontrollü bir alüminyum oksit tabakası oluşturulması işlemidir. Renklendirme yapılmadığı için malzemenin kendi metalik tonu korunur; yüzey, ön işleme bağlı olarak mat, saten ya da parlak görünüm alabilir.\n\nOluşan oksit tabakası alüminyumun kendisinden büyür ve yüzeye kimyasal olarak bağlıdır; boya gibi pul pul kalkmaz. Bu sayede parçalar hem daha dayanıklı hem de uzun süre temiz görünen bir yüzeye sahip olur.\n\n## Süreç\n\nYağ alma ve yüzey temizliği, dağlama veya parlatma, sülfürik asit banyosunda anodik oksidasyon ve sızdırmazlık adımları kontrollü şekilde uygulanır. Tabaka kalınlığı, talep edilen mikron değerine göre proses süresi ve akım yoğunluğu ile ayarlanır.",
			'adv'     => "Korozyona karşı artırılmış direnç\nAşınma ve çizilmeye karşı daha sert yüzey\nAlüminyumun doğal metalik görünümü\nElektriksel yalıtım sağlayan oksit tabakası\nKolay temizlenebilir, bakım gerektirmeyen yüzey",
			'app'     => "Savunma sanayi komponentleri\nMakine parçaları ve gövdeler\nElektronik kutular ve soğutucular\nHassas işlenmiş CNC parçaları\nProfil ve plaka uygulamaları",
			'specs'   => $common_specs( 'Naturel (gümüş)' ),
		),
		array(
			'title'   => 'Kırmızı Eloksal',
			'slug'    => 'kirmizi-eloksal',
			'cat'     => $cats['eloksal'],
			'tone'    => 'red',
			'summary' => 'Gözenekli oksit tabakasının kırmızı renkle boyanıp sızdırmazlıkla sabitlendiği dayanıklı ve canlı yüzey.',
			'content' => "Kırmızı eloksal, anodik oksidasyonla oluşturulan gözenekli oksit tabakasının kırmızı boyar maddeyle renklendirilmesi ve ardından sızdırmazlık işlemiyle rengin tabakaya hapsedilmesidir. Renk, yüzeyin üzerinde ayrı bir katman olarak değil, oksit tabakasının içinde yer alır.\n\nKırmızı renk; parça tanımlama, renk kodlama ve tasarım ürünlerinde dikkat çekici bir yüzey elde etmek için sıkça tercih edilir.\n\n" . $color_process,
			'adv'     => "Canlı ve homojen renk görünümü\nRengin oksit tabakası içinde sabitlenmesi\nKorozyon ve aşınmaya karşı koruma\nParça tanımlama ve renk kodlamaya uygunluk\nMat veya parlak yüzey seçeneği",
			'app'     => "Renk kodlu savunma sanayi parçaları\nMakine kontrol ve ayar elemanları\nTasarım ve aksesuar ürünleri\nHassas işlenmiş dekoratif komponentler",
			'specs'   => $common_specs( 'Kırmızı (ton numune ile onaylanır)' ),
		),
		array(
			'title'   => 'Mavi Eloksal',
			'slug'    => 'mavi-eloksal',
			'cat'     => $cats['eloksal'],
			'tone'    => 'blue',
			'summary' => 'Teknik ve dekoratif parçalarda tercih edilen, oksit tabakası içine sabitlenmiş mavi renkli eloksal yüzey.',
			'content' => "Mavi eloksal, gözenekli oksit tabakasının mavi boyar maddeyle renklendirildiği ve sızdırmazlıkla sabitlendiği eloksal uygulamasıdır. Teknik görünümü nedeniyle makine parçaları, fikstürler ve ekipman bileşenlerinde yaygın olarak kullanılır.\n\nRenk tonu, ön işlemle elde edilen yüzeye göre açık, koyu, mat veya parlak görünebilir.\n\n" . $color_process,
			'adv'     => "Teknik ve modern görünüm\nKorozyona ve aşınmaya karşı dayanıklılık\nRenk kodlama ile hızlı parça ayrımı\nParlak ve mat yüzey alternatifleri",
			'app'     => "Makine ve otomasyon parçaları\nFikstür ve aparatlar\nEkipman ve cihaz bileşenleri\nDekoratif alüminyum ürünler",
			'specs'   => $common_specs( 'Mavi (ton numune ile onaylanır)' ),
		),
		array(
			'title'   => 'Sarı Eloksal',
			'slug'    => 'sari-eloksal',
			'cat'     => $cats['eloksal'],
			'tone'    => 'gold',
			'summary' => 'Altın ve sarı tonlarında, metalik parlaklığı koruyan dekoratif ve koruyucu eloksal yüzey.',
			'content' => "Sarı eloksal, oksit tabakasının sarı ya da altın tonlarında renklendirilmesiyle elde edilir. Parlatılmış yüzeylerde metalik altın görünümü, mat yüzeylerde ise daha sade bir sarı ton elde edilebilir.\n\nDekoratif parçaların yanı sıra renk kodlaması gereken teknik uygulamalarda da kullanılır.\n\n" . $color_process,
			'adv'     => "Altın / sarı metalik görünüm\nRengin oksit tabakası içinde korunması\nKorozyon ve aşınma direnci\nDekoratif ve teknik kullanıma uygunluk",
			'app'     => "Dekoratif alüminyum parçalar\nRenk kodlu teknik komponentler\nAksesuar ve tasarım ürünleri",
			'specs'   => $common_specs( 'Sarı / altın (ton numune ile onaylanır)' ),
		),
		array(
			'title'   => 'Siyah Eloksal',
			'slug'    => 'siyah-eloksal',
			'cat'     => $cats['eloksal'],
			'tone'    => 'black',
			'summary' => 'Düşük yansıma, homojen mat görünüm ve yüksek dayanıklılık gerektiren parçalar için siyah eloksal.',
			'content' => "Siyah eloksal, oksit tabakasının siyah boyar maddeyle renklendirilip sızdırmazlık işlemiyle sabitlenmesiyle elde edilir. Işık yansımasının istenmediği, profesyonel ve homojen görünüm beklenen parçalarda en çok tercih edilen eloksal türlerinden biridir.\n\nSavunma sanayi, optik ve elektronik ekipmanlar ile makine sanayinde yaygın olarak kullanılır. Derin ve tutarlı bir siyah için yeterli oksit tabakası kalınlığı ve kontrollü renklendirme önemlidir.\n\n" . $color_process,
			'adv'     => "Düşük ışık yansıması\nProfesyonel, homojen mat görünüm\nKorozyon ve aşınma direnci\nParmak izi ve lekeleri daha az gösteren yüzey",
			'app'     => "Savunma sanayi ekipman parçaları\nOptik ve elektronik gövdeler\nMakine ve otomasyon komponentleri\nHassas işlenmiş CNC parçaları",
			'specs'   => $common_specs( 'Siyah', 'Mat / Saten / Parlak (ön işleme göre)' ),
		),
		array(
			'title'   => 'Diğer Eloksal',
			'slug'    => 'diger-eloksal',
			'cat'     => $cats['eloksal'],
			'tone'    => 'multi',
			'summary' => 'Standart renklerin dışında; projeye özel renk ve yüzey kombinasyonlarıyla eloksal uygulamaları.',
			'content' => "Standart renklerin dışında farklı renk ve yüzey kombinasyonlarına ihtiyaç duyan projeler için, talep edilen renge uygun eloksal uygulamaları yapılabilir. Yeşil, turuncu, mor, bronz ve benzeri tonlar; malzemenin alaşımına ve yüzey hazırlığına bağlı olarak değerlendirilir.\n\nÖzel renk taleplerinde, seri üretime geçmeden önce numune parça üzerinde renk ve yüzey onayı alınması önerilir. Böylece beklenen ton ve görünüm baştan netleştirilir.\n\n## Nasıl çalışıyoruz?\n\nParçanın malzeme bilgisi, teknik resmi veya fotoğrafı ile hedeflenen renk referansını paylaşmanız yeterlidir. Uygulanabilirlik ve yöntem birlikte değerlendirilir.",
			'adv'     => "Projeye özel renk seçenekleri\nNumune ile renk onayı\nEloksalın koruyucu özellikleri\nMat, saten ve parlak yüzey alternatifleri",
			'app'     => "Tasarım ve ürün geliştirme projeleri\nMarka rengine uygun parçalar\nRenk kodlaması gereken ekipmanlar",
			'specs'   => $common_specs( 'Talebe göre (numune ile onaylanır)' ),
		),
		array(
			'title'   => 'Alodin Kaplama',
			'slug'    => 'alodin-kaplama',
			'cat'     => $cats['kimyasal'],
			'tone'    => 'champagne',
			'summary' => 'Alüminyumda elektrik iletkenliğini koruyan, korozyona karşı koruma ve boya altı astar görevi gören kimyasal dönüşüm kaplaması.',
			'content' => "Alodin kaplama, alüminyum yüzeyinde kimyasal reaksiyonla çok ince bir dönüşüm tabakası oluşturan kaplama işlemidir. Eloksalden farklı olarak elektrik akımı kullanılmaz ve oluşan tabaka çok incedir; bu nedenle parçanın ölçülerinde kayda değer bir değişiklik oluşmaz.\n\nTabaka, yüzeyin elektrik iletkenliğini büyük ölçüde korur. Bu özellik; topraklama ve elektromanyetik uyumluluk gerektiren elektronik kutularda ve bağlantı yüzeylerinde alodin kaplamayı öne çıkarır. Ayrıca boya ve yapıştırıcılar için iyi bir tutunma yüzeyi sağlar.\n\n## Görünüm\n\nKullanılan kimyasala göre şeffaf/renksiz, şampanya veya açık sarı tonlarında bir görünüm elde edilir.",
			'adv'     => "Elektrik iletkenliğinin korunması\nKorozyona karşı koruma\nBoya ve yapıştırıcılar için astar yüzeyi\nÖlçü değişikliği oluşturmayan ince tabaka\nKarmaşık geometrilerde homojen uygulama",
			'app'     => "Elektronik kutu ve şaseler\nTopraklama ve temas yüzeyleri\nBoyanacak alüminyum parçalar\nSavunma sanayi komponentleri",
			'specs'   => array(
				array( 'label' => 'Uygulanan malzeme', 'value' => 'Alüminyum ve alüminyum alaşımları' ),
				array( 'label' => 'Yöntem', 'value' => 'Kimyasal dönüşüm (akımsız)' ),
				array( 'label' => 'Görünüm', 'value' => 'Şeffaf / şampanya / açık sarı' ),
				array( 'label' => 'Elektrik iletkenliği', 'value' => 'Korunur' ),
				array( 'label' => 'Ölçü etkisi', 'value' => 'İhmal edilebilir düzeyde' ),
			),
		),
		array(
			'title'   => 'Kromat Kaplama',
			'slug'    => 'kromat-kaplama',
			'cat'     => $cats['kimyasal'],
			'tone'    => 'chromate',
			'summary' => 'Sarı yanardöner görünümlü, korozyon koruması ve boya tutuculuğu sağlayan kromat dönüşüm kaplaması.',
			'content' => "Kromat kaplama, metal yüzeyinde krom bileşikleri içeren çözeltilerle ince bir dönüşüm tabakası oluşturan kimyasal bir yüzey işlemidir. Alüminyum parçalarda tipik olarak sarı ve yanardöner bir görünüm oluşturur.\n\nTabaka korozyon direncini artırır, boya ve yapıştırıcılar için tutunma yüzeyi sağlar ve yüzeyin iletkenliğini büyük ölçüde korur.\n\n## Doğru kromat seçimi\n\nKromat türü ve renk tonu; parçanın kullanım yerine, bağlı olduğu teknik şartnameye ve çevresel gereksinimlere göre belirlenir. Teklif aşamasında şartname bilgisini paylaşmanız, doğru yöntemin seçilmesini kolaylaştırır.",
			'adv'     => "Korozyona karşı ek koruma\nBoya ve yapıştırıcı tutunmasını artırma\nİnce tabaka, düşük ölçü etkisi\nİletkenliğin büyük ölçüde korunması",
			'app'     => "Boya altı yüzey hazırlığı\nElektronik ve elektrik bileşenleri\nMakine ve savunma sanayi parçaları",
			'specs'   => array(
				array( 'label' => 'Uygulanan malzeme', 'value' => 'Alüminyum ve alüminyum alaşımları' ),
				array( 'label' => 'Yöntem', 'value' => 'Kimyasal dönüşüm' ),
				array( 'label' => 'Görünüm', 'value' => 'Sarı yanardöner (şartnameye göre)' ),
				array( 'label' => 'Ölçü etkisi', 'value' => 'İhmal edilebilir düzeyde' ),
			),
		),
		array(
			'title'   => 'Kuru Film Yağlama Kaplaması',
			'slug'    => 'kuru-film-yaglama-kaplamasi',
			'cat'     => $cats['yuzey'],
			'tone'    => 'graphite',
			'summary' => 'Sürtünmeyi azaltan, sıvı yağlamanın uygun olmadığı yerlerde kalıcı kayganlık sağlayan kuru film kaplama.',
			'content' => "Kuru film yağlama kaplaması, parçanın yüzeyine bağlayıcı içinde katı yağlayıcılar (ör. molibden disülfür, grafit veya PTFE) içeren ince bir film uygulanmasıdır. Kürlenen film, yüzeyde kalıcı ve kuru bir kayganlık sağlar.\n\nSıvı yağ ve greslerin toz tuttuğu, akabileceği ya da kullanılamadığı durumlarda; hareketli ve kayan parçalarda sürtünmeyi ve aşınmayı azaltmak için tercih edilir. Dişli bağlantılarda sıkışma ve yapışmanın (galling) önlenmesine yardımcı olur.\n\n## Eloksal ile birlikte kullanım\n\nEloksallı yüzeyler üzerine uygulanabildiği için, eloksalın koruyucu özellikleriyle kuru film kaplamanın düşük sürtünme avantajı aynı parçada birleştirilebilir.",
			'adv'     => "Düşük sürtünme katsayısı\nAşınma ve sıkışmaya (galling) karşı koruma\nToz tutmayan kuru yüzey\nSıvı yağlamanın uygun olmadığı yerlerde çözüm\nEloksallı yüzeylerle birlikte kullanılabilme",
			'app'     => "Kayar ve hareketli mekanizmalar\nDişli bağlantılar ve cıvatalar\nSavunma sanayi mekanik komponentleri\nMakine kızak ve yatak parçaları",
			'specs'   => array(
				array( 'label' => 'Uygulanan malzeme', 'value' => 'Alüminyum ve metal yüzeyler' ),
				array( 'label' => 'Kaplama tipi', 'value' => 'Katı yağlayıcılı bağlayıcı film' ),
				array( 'label' => 'Görünüm', 'value' => 'Koyu gri / grafit' ),
				array( 'label' => 'Uygulama', 'value' => 'Teknik resim ve şartnameye göre' ),
			),
		),
	);
}

/**
 * Teknik blog yazıları.
 *
 * @return array
 */
function ce_seed_articles() {
	return array(
		array(
			'title'   => 'Eloksal Nedir?',
			'slug'    => 'eloksal-nedir',
			'cat'     => 'eloksal',
			'excerpt' => 'Eloksal (anodizasyon), alüminyum yüzeyinde elektrokimyasal yöntemle koruyucu bir oksit tabakası oluşturma işlemidir. Nasıl yapıldığını ve neden tercih edildiğini anlatıyoruz.',
			'content' => "Eloksal, İngilizce literatürde anodizing olarak bilinen, alüminyum yüzeyinde kontrollü bir alüminyum oksit tabakası oluşturma işlemidir. Türkçedeki adı, “elektrolitik oksidasyon” ifadesinden gelir.\n\nAlüminyum doğal olarak havayla temas ettiğinde çok ince bir oksit tabakası oluşturur. Eloksal işlemi bu doğal süreci kontrollü hale getirerek çok daha kalın, sert ve dayanıklı bir tabaka elde edilmesini sağlar.\n\n## Eloksal nasıl yapılır?\n\n- Yağ alma ve temizleme: Yüzeydeki yağ ve kirler uzaklaştırılır.\n- Dağlama veya parlatma: İstenen mat ya da parlak görünüm için yüzey hazırlanır.\n- Anodik oksidasyon: Parça, asidik bir elektrolit banyosunda anot olarak bağlanır ve akım uygulanır.\n- Renklendirme (isteğe bağlı): Gözenekli tabaka boyar madde ile renklendirilir.\n- Sızdırmazlık: Gözenekler kapatılarak tabaka ve renk sabitlenir.\n\n## Eloksal tabakası neden dayanıklıdır?\n\nOksit tabakası alüminyumun kendisinden büyür; yüzeye yapıştırılmış bir boya katmanı değildir. Bu nedenle kabarma ya da pul pul dökülme yapmaz. Tabaka kalınlığı mikron (µm) cinsinden ifade edilir ve kullanım amacına göre belirlenir.\n\n## Hangi parçalarda kullanılır?\n\nSavunma sanayi, makine sanayi, elektronik, otomotiv ve mimari uygulamalar başta olmak üzere, alüminyumun korunması ve estetik bir görünüm kazanması gereken hemen her alanda eloksal kullanılır.",
		),
		array(
			'title'   => 'Naturel Eloksal Nedir?',
			'slug'    => 'naturel-eloksal-nedir',
			'cat'     => 'eloksal',
			'excerpt' => 'Naturel eloksal, alüminyumun doğal metalik rengini koruyan renksiz eloksal uygulamasıdır. Özellikleri ve kullanım alanlarını inceledik.',
			'content' => "Naturel eloksal, renklendirme adımı uygulanmadan yapılan eloksal işlemidir. Parça, alüminyumun doğal gümüş-gri tonunu korur; yüzey ise oksit tabakası sayesinde daha sert ve korozyona karşı daha dayanıklı hale gelir.\n\n## Görünümü neye bağlıdır?\n\nNaturel eloksalda son görünümü belirleyen en önemli etkenler, alüminyumun alaşım türü ve ön işlemdir. Dağlama ile mat, parlatma ile parlak, kumlama ile saten bir görünüm elde edilebilir. Aynı işlem farklı alaşımlarda hafif ton farklılıkları oluşturabilir.\n\n## Avantajları\n\n- Alüminyumun doğal metalik görünümü korunur.\n- Yüzey sertliği ve aşınma direnci artar.\n- Korozyona karşı koruma sağlanır.\n- Oksit tabakası elektriksel yalıtım sağlar.\n\n## Nerelerde tercih edilir?\n\nMakine parçaları, elektronik kutular, soğutucular, profil uygulamaları ve savunma sanayi komponentleri naturel eloksalın en yaygın kullanım alanlarıdır.",
		),
		array(
			'title'   => 'Renkli Eloksal Nedir?',
			'slug'    => 'renkli-eloksal-nedir',
			'cat'     => 'eloksal',
			'excerpt' => 'Renkli eloksalda renk, oksit tabakasının içine hapsedilir. Kırmızı, mavi, sarı ve diğer renklerin nasıl elde edildiğini anlatıyoruz.',
			'content' => "Renkli eloksal, anodik oksidasyonla oluşturulan gözenekli oksit tabakasının boyar maddelerle renklendirilmesiyle elde edilir. Renk, yüzeye sürülen bir boya gibi üstte durmaz; oksit tabakasının gözeneklerine yerleşir ve sızdırmazlık işlemiyle sabitlenir.\n\n## Renk tonunu etkileyen faktörler\n\n- Alüminyum alaşımı: Alaşım elementleri rengin tonunu etkileyebilir.\n- Oksit tabakası kalınlığı: Daha kalın tabaka genellikle daha doygun bir renk sağlar.\n- Yüzey hazırlığı: Mat veya parlak ön işlem, rengin algısını değiştirir.\n- Renklendirme süresi ve banyo koşulları.\n\n## Numune neden önemlidir?\n\nAynı renk adı farklı parçalarda farklı görünebilir. Bu nedenle seri üretim öncesinde numune parça üzerinde renk onayı alınması, beklenen sonucun garanti altına alınması için en doğru yöntemdir.\n\n## Dış ortam kullanımı\n\nOrganik boyar maddelerle yapılan renklendirmelerde, uzun süre doğrudan güneş ışığına maruz kalacak parçalar için renk seçimi ayrıca değerlendirilmelidir.",
		),
		array(
			'title'   => 'Siyah Eloksal Nedir?',
			'slug'    => 'siyah-eloksal-nedir',
			'cat'     => 'eloksal',
			'excerpt' => 'Siyah eloksal; düşük yansıma, homojen mat görünüm ve dayanıklılık gerektiren parçalarda en çok tercih edilen yüzeylerden biridir.',
			'content' => "Siyah eloksal, oksit tabakasının siyah boyar maddeyle renklendirilmesi ve sızdırmazlıkla sabitlenmesiyle elde edilen eloksal türüdür. Hem teknik hem estetik nedenlerle en çok talep edilen renklerden biridir.\n\n## Neden siyah eloksal?\n\n- Düşük ışık yansıması sağlar; optik ve görüntüleme sistemlerinde tercih edilir.\n- Profesyonel ve homojen bir görünüm sunar.\n- Parmak izi ve küçük lekeleri daha az gösterir.\n- Eloksalın korozyon ve aşınma direncini taşır.\n\n## İyi bir siyah için nelere dikkat edilir?\n\nDerin ve tutarlı bir siyah elde etmek için yeterli oksit tabakası kalınlığı, doğru renklendirme süresi ve kontrollü sızdırmazlık önemlidir. Alaşım farklılıkları, siyahın kahverengi ya da mavimsi alt tonlara kaymasına yol açabileceğinden, kritik parçalarda numune onayı önerilir.",
		),
		array(
			'title'   => 'Alodin Kaplama Nedir?',
			'slug'    => 'alodin-kaplama-nedir',
			'cat'     => 'kaplama',
			'excerpt' => 'Alodin kaplama, alüminyumda iletkenliği koruyan ince bir kimyasal dönüşüm tabakasıdır. Eloksaldan farkını ve kullanım alanlarını açıklıyoruz.',
			'content' => "Alodin kaplama, alüminyum yüzeyinde kimyasal reaksiyonla ince bir dönüşüm tabakası oluşturan işlemdir. Uygulamada elektrik akımı kullanılmaz; parça uygun kimyasal çözeltiyle işlem görür.\n\n## Eloksal ile farkı nedir?\n\n- Eloksal, akımla oluşturulan daha kalın ve yalıtkan bir oksit tabakasıdır.\n- Alodin, çok ince ve iletkenliği büyük ölçüde koruyan bir dönüşüm tabakasıdır.\n- Alodin parçanın ölçülerini pratikte değiştirmez.\n\n## Nerelerde kullanılır?\n\nElektronik kutular, topraklama yüzeyleri ve elektromanyetik uyumluluk gerektiren gövdeler; iletkenliğin korunması gerektiği için alodin kaplamanın tipik kullanım alanlarıdır. Ayrıca boyanacak alüminyum parçalarda astar yüzeyi olarak da tercih edilir.\n\n## Görünüm\n\nKullanılan kimyasala bağlı olarak şeffaf, şampanya veya açık sarı tonlarda bir yüzey elde edilir.",
		),
		array(
			'title'   => 'Kromat Kaplama Nedir?',
			'slug'    => 'kromat-kaplama-nedir',
			'cat'     => 'kaplama',
			'excerpt' => 'Kromat kaplama; korozyon koruması ve boya tutuculuğu sağlayan, sarı yanardöner görünümlü bir kimyasal dönüşüm kaplamasıdır.',
			'content' => "Kromat kaplama, metal yüzeyinde krom bileşikleri içeren çözeltilerle ince bir dönüşüm tabakası oluşturulmasıdır. Alüminyumda genellikle sarı ve yanardöner bir görünüm elde edilir.\n\n## Ne işe yarar?\n\n- Korozyon direncini artırır.\n- Boya ve yapıştırıcıların yüzeye tutunmasını kolaylaştırır.\n- İnce yapısı sayesinde ölçü değişikliği oluşturmaz.\n- Yüzeyin iletkenliğini büyük ölçüde korur.\n\n## Doğru yöntemin seçimi\n\nKromat kaplamalar farklı kimyasal türlerle yapılabilir. Hangi tipin uygulanacağı; parçanın kullanım yeri, bağlı olduğu teknik şartname ve çevresel gereksinimler dikkate alınarak belirlenir. Teklif talebinde şartname bilgisini paylaşmak süreci hızlandırır.",
		),
		array(
			'title'   => 'Kuru Film Yağlama Kaplaması Nedir?',
			'slug'    => 'kuru-film-yaglama-kaplamasi-nedir',
			'cat'     => 'kaplama',
			'excerpt' => 'Kuru film yağlama kaplamaları, sıvı yağlamanın uygun olmadığı yerlerde sürtünmeyi ve aşınmayı azaltan kalıcı ince filmlerdir.',
			'content' => "Kuru film yağlama kaplaması (dry film lubricant), bağlayıcı bir reçine içinde katı yağlayıcılar barındıran ince bir kaplamadır. Molibden disülfür, grafit ve PTFE en bilinen katı yağlayıcılardır. Uygulanan film kürlendikten sonra yüzeyde kuru ve kaygan bir tabaka oluşur.\n\n## Hangi sorunları çözer?\n\n- Kayan ve hareketli parçalarda sürtünmeyi azaltır.\n- Dişli bağlantılarda sıkışma ve yapışmayı (galling) önlemeye yardımcı olur.\n- Toz ve kir tutmaz; sıvı yağların akma sorunu yoktur.\n- Bakım aralıklarının uzamasına katkı sağlar.\n\n## Nerelerde kullanılır?\n\nSavunma sanayi mekanizmaları, makine kızakları, cıvata ve dişli bağlantılar ile sıvı yağlamanın mümkün olmadığı veya istenmediği uygulamalar başlıca kullanım alanlarıdır. Eloksallı yüzeyler üzerine de uygulanabilir.",
		),
		array(
			'title'   => 'Alüminyum Eloksal Kaplamanın Avantajları',
			'slug'    => 'aluminyum-eloksal-kaplamanin-avantajlari',
			'cat'     => 'teknik-rehber',
			'excerpt' => 'Korozyon direncinden yüzey sertliğine, renk seçeneklerinden bakım kolaylığına: alüminyum parçalarda eloksal kaplamanın başlıca avantajları.',
			'content' => "Alüminyum hafifliği ve işlenebilirliği sayesinde endüstrinin en çok kullanılan metallerinden biridir. Eloksal kaplama ise alüminyumun bu avantajlarını yüzey performansıyla tamamlar.\n\n## 1. Korozyon direnci\n\nKontrollü oksit tabakası, alüminyumu nem ve çevresel etkilere karşı korur. Sızdırmazlık işlemi bu korumayı daha da güçlendirir.\n\n## 2. Sert ve aşınmaya dayanıklı yüzey\n\nAlüminyum oksit, alüminyumun kendisinden çok daha serttir. Bu da parçaların çizilme ve aşınmaya karşı dayanıklılığını artırır.\n\n## 3. Kalıcı renk seçenekleri\n\nRenk, oksit tabakasının içine yerleştiği için boya gibi kabarmaz ya da pul pul dökülmez. Naturelden siyaha geniş bir renk yelpazesi mümkündür.\n\n## 4. Elektriksel yalıtım\n\nEloksal tabakası elektriği iletmez; bu özellik elektronik uygulamalarda avantaj sağlar.\n\n## 5. Kolay bakım\n\nEloksallı yüzeyler kolay temizlenir ve uzun süre ilk günkü görünümünü korur.\n\n## 6. Ölçü kontrolü\n\nTabaka kalınlığı mikron düzeyinde kontrol edilebildiği için, hassas toleranslı parçalarda da planlı şekilde uygulanabilir.",
		),
	);
}

/**
 * Tüm başlangıç içeriğini oluşturur.
 *
 * @return string[] Günlük.
 */
function ce_seed_all() {
	$log = array();

	// Kategoriler.
	$cats = array(
		'eloksal'  => ce_seed_term( 'Eloksal', 'ce_service_cat', 'eloksal', 'Alüminyum yüzeylerde naturel ve renkli eloksal uygulamaları.' ),
		'kimyasal' => ce_seed_term( 'Kimyasal Kaplama', 'ce_service_cat', 'kimyasal-kaplama', 'Alodin ve kromat gibi kimyasal dönüşüm kaplamaları.' ),
		'yuzey'    => ce_seed_term( 'Yüzey İşlem', 'ce_service_cat', 'yuzey-islem', 'Kuru film yağlama ve diğer fonksiyonel yüzey işlemleri.' ),
	);
	foreach ( array( 'Eloksal' => 'eloksal', 'Kaplama' => 'kaplama', 'Üretim' => 'uretim', 'Tesis' => 'tesis', 'Ürünler' => 'urunler' ) as $name => $slug ) {
		ce_seed_term( $name, 'ce_gallery_cat', $slug );
	}
	$blog_cats = array(
		'eloksal'       => ce_seed_term( 'Eloksal', 'category', 'eloksal' ),
		'kaplama'       => ce_seed_term( 'Kaplama', 'category', 'kaplama' ),
		'teknik-rehber' => ce_seed_term( 'Teknik Rehber', 'category', 'teknik-rehber' ),
	);
	$log[] = '— Kategoriler hazır.';

	// Hizmetler.
	foreach ( ce_seed_services( $cats ) as $i => $s ) {
		ce_seed_post(
			array(
				'post_type'    => 'ce_service',
				'post_title'   => $s['title'],
				'post_name'    => $s['slug'],
				'post_content' => ce_md_blocks( $s['content'] ),
				'menu_order'   => $i + 1,
				'meta'         => array(
					'summary'      => $s['summary'],
					'tone'         => $s['tone'],
					'advantages'   => $s['adv'],
					'applications' => $s['app'],
					'specs'        => $s['specs'],
				),
				'terms'        => array( 'ce_service_cat' => array( $s['cat'] ) ),
			),
			$log
		);
	}

	// Sektörler.
	$sectors = array(
		array( 'Savunma Sanayi', 'savunma-sanayi', '01', 'Hassas CNC işlenmiş, kompleks alüminyum komponentler için şartnameye uygun eloksal ve kaplama.', 'black' ),
		array( 'Makine Sanayi', 'makine-sanayi', '02', 'Makine ve otomasyon parçalarında korozyon, aşınma ve görünüm odaklı yüzey işlemleri.', 'blue' ),
		array( 'Endüstriyel Üretim', 'endustriyel-uretim', '03', 'Seri üretim parçaları için tutarlı, tekrarlanabilir eloksal ve kaplama prosesleri.', 'natural' ),
		array( 'Metal İşleme', 'metal-isleme', '04', 'Hassas işlenmiş metal parçalar için fonksiyonel ve koruyucu yüzey çözümleri.', 'graphite' ),
	);
	foreach ( $sectors as $i => $sec ) {
		ce_seed_post(
			array(
				'post_type'    => 'ce_sector',
				'post_title'   => $sec[0],
				'post_name'    => $sec[1],
				'post_excerpt' => $sec[3],
				'menu_order'   => $i + 1,
				'meta'         => array( 'number' => $sec[2], 'tone' => $sec[4] ),
			),
			$log
		);
	}

	// Hero slaytı.
	ce_seed_post(
		array(
			'post_type'  => 'ce_slide',
			'post_title' => 'ALÜMİNYUM YÜZEYLERDE | PROFESYONEL ELOKSAL ÇÖZÜMLERİ',
			'post_name'  => 'ana-slayt',
			'menu_order' => 1,
			'meta'       => array(
				'eyebrow'     => 'Alüminyum Yüzey Mühendisliği',
				'description' => 'İstenilen mikron, yüzey kalitesi ve renk seçenekleriyle endüstriyel alüminyum parçalarınız için profesyonel eloksal ve yüzey kaplama çözümleri.',
				'tags'        => "Savunma Sanayi\nMakine Sanayi\nEndüstriyel Üretim",
				'btn1_label'  => 'Hizmetleri İncele',
				'btn1_url'    => '/hizmetler/',
				'btn2_label'  => 'Teklif Al',
				'btn2_url'    => '/teklif-al/',
				'overlay'     => 55,
				'align'       => 'left',
			),
		),
		$log
	);

	// Banka hesapları.
	$banks = array(
		array( 'HALK BANKASI', 'halk-bankasi', 'YİĞİT CAN', 'BOSNA CADDESİ', '1532', '01112753', 'TR02 0001 2001 5320 0001 1127 53', 'dark' ),
		array( 'GARANTİ BANKASI', 'garanti-bankasi', 'YİĞİT CAN', 'ADAPAZARI', '333', '6851730', 'TR64 0006 2000 3330 0006 8517 30', 'cyan' ),
	);
	foreach ( $banks as $i => $b ) {
		ce_seed_post(
			array(
				'post_type'  => 'ce_bank',
				'post_title' => $b[0],
				'post_name'  => $b[1],
				'menu_order' => $i + 1,
				'meta'       => array( 'holder' => $b[2], 'branch' => $b[3], 'branch_code' => $b[4], 'account_no' => $b[5], 'iban' => $b[6], 'currency' => 'TRY', 'color' => $b[7] ),
			),
			$log
		);
	}

	// Sayfalar.
	$pages = ce_seed_pages();
	$ids   = array();
	foreach ( $pages as $key => $p ) {
		$meta = $p['meta'] ?? array();
		if ( ! empty( $p['template'] ) ) {
			$meta['_wp_page_template'] = 'page-templates/' . $p['template'] . '.php';
		}
		$ids[ $key ] = ce_seed_post(
			array(
				'post_type'    => 'page',
				'post_title'   => $p['title'],
				'post_name'    => $p['slug'],
				'post_content' => isset( $p['content'] ) ? ce_md_blocks( $p['content'] ) : '',
				'menu_order'   => $p['order'] ?? 0,
				'meta'         => $meta,
			),
			$log
		);
	}

	// Blog yazıları.
	foreach ( ce_seed_articles() as $i => $a ) {
		ce_seed_post(
			array(
				'post_type'     => 'post',
				'post_title'    => $a['title'],
				'post_name'     => $a['slug'],
				'post_excerpt'  => $a['excerpt'],
				'post_content'  => ce_md_blocks( $a['content'] ),
				'post_date'     => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( 8 - $i ) * DAY_IN_SECONDS ), // phpcs:ignore
				'comment_status' => 'closed',
				'terms'         => array( 'category' => array( $blog_cats[ $a['cat'] ] ) ),
			),
			$log
		);
	}

	// WordPress'in örnek içerikleri (düzenlenmemişse) kaldırılır.
	foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ), array( 'ornek-sayfa', 'page' ), array( 'merhaba-dunya', 'post' ) ) as $sample ) {
		$sp = get_page_by_path( $sample[0], OBJECT, $sample[1] );
		if ( $sp && $sp->post_date === $sp->post_modified ) {
			wp_trash_post( $sp->ID );
		}
	}

	// Okuma ayarları, kalıcı bağlantılar.
	if ( $ids['home'] ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}
	if ( $ids['blog'] ) {
		update_option( 'page_for_posts', $ids['blog'] );
	}
	update_option( 'posts_per_page', 9 );
	update_option( 'blogname', ce_opt( 'site_name', 'Can Eloksal' ) );
	update_option( 'blogdescription', 'Alüminyum Eloksal ve Yüzey Kaplama' );
	update_option( 'timezone_string', 'Europe/Istanbul' );
	update_option( 'date_format', 'j F Y' );
	update_option( 'default_comment_status', 'closed' );
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/blog/%postname%/' );
	update_option( 'category_base', 'blog/kategori' );
	update_option( 'tag_base', 'blog/etiket' );
	$uncat = get_term_by( 'slug', 'uncategorized', 'category' );
	if ( $uncat && 0 === (int) $uncat->count ) {
		wp_update_term( $uncat->term_id, 'category', array( 'name' => 'Genel', 'slug' => 'genel' ) );
	}

	// Yasal sayfalar tema ayarlarına bağlanır.
	$options = get_option( CE_OPTION, array() );
	$options = is_array( $options ) ? $options : array();
	foreach ( array( 'kvkk_page' => 'kvkk', 'privacy_page' => 'privacy', 'cookie_page' => 'cookie' ) as $opt => $key ) {
		if ( empty( $options[ $opt ] ) && ! empty( $ids[ $key ] ) ) {
			$options[ $opt ] = $ids[ $key ];
		}
	}
	update_option( CE_OPTION, $options );
	$GLOBALS['ce_opt_reset'] = true;

	ce_seed_menus( $ids, $log );

	ce_register_post_types();
	ce_sitemap_rewrite();
	flush_rewrite_rules();
	update_option( 'ce_setup_done', time() );
	delete_transient( 'ce_setup_notice' );
	ce_audit( 'setup', 'settings', 0, 'Başlangıç içerikleri' );
	$log[] = '— Okuma ayarları, kalıcı bağlantılar (/blog/%postname%/) ve menüler ayarlandı.';
	return $log;
}

/**
 * Sayfa verileri.
 *
 * @return array
 */
function ce_seed_pages() {
	$company = 'Can Eloksal (' . ce_opt( 'address' ) . ')';
	$company = str_replace( "\n", ' ', $company );
	return array(
		'home'    => array( 'title' => 'Ana Sayfa', 'slug' => 'ana-sayfa', 'order' => 0 ),
		'about'   => array(
			'title'    => 'Hakkımızda',
			'slug'     => 'hakkimizda',
			'template' => 'about',
			'order'    => 1,
			'content'  => "Tesisimizde günümüz teknolojisinin getirdiği tüm imkanlardan yararlanılarak istenilen mikron, kalite ve renkte alüminyum malzemeler için eloksal işçiliği yapılır.\n\nCan Eloksal firması olarak savunma sanayi, makine sanayi alanlarında ağırlıklı hizmet vererek bu sektörlerdeki her türlü alüminyum malzeme, parça veya ürünlere alüminyum mat veya parlak olmak üzere eloksal kaplama yapmaktayız.\n\nEloksal kaplanacak malzemenin kaygan olması ve oksitlenmeye daha dirençli olabilmesi amacıyla teknolojimizi sürekli geliştirerek müşterilerimize yüksek kaliteli hizmet sunmayı hedefliyoruz.",
			'meta'     => array(
				'hero_title'     => 'CAN ELOKSAL HAKKINDA',
				'hero_eyebrow'   => 'Kurumsal',
				'hero_subtitle'  => 'Alüminyum yüzeylerde hassasiyet, süreç disiplini ve özenli işçilik.',
				'intro_title'    => "HER YÜZEYDE\nÖZENLİ İŞÇİLİK.",
				'intro_badge'    => 'Savunma & Makine Sanayi',
				'blocks'         => array(
					array( 'eyebrow' => 'Firma', 'title' => 'Alüminyum yüzey işlemlerinde uzmanlaşmış bir ekip', 'text' => 'Can Eloksal; alüminyum parçaların eloksal ve kimyasal kaplama işlemlerine odaklanan, savunma ve makine sanayine yönelik çalışan bir yüzey işlem firmasıdır.', 'icon' => 'building', 'image' => 0 ),
					array( 'eyebrow' => 'Üretim Yaklaşımımız', 'title' => 'Her parça için doğru proses', 'text' => 'Parçanın alaşımını, geometrisini ve kullanım amacını değerlendirerek ön işlemden sızdırmazlığa kadar her adımı talebe uygun şekilde planlıyoruz.', 'icon' => 'settings', 'image' => 0 ),
					array( 'eyebrow' => 'Teknoloji', 'title' => 'Kontrollü ve tekrarlanabilir süreçler', 'text' => 'Banyo koşullarını, süreyi ve akım değerlerini kontrol altında tutarak istenilen mikron ve renkte tutarlı sonuçlar elde etmeyi hedefliyoruz.', 'icon' => 'cpu', 'image' => 0 ),
					array( 'eyebrow' => 'Kalite', 'title' => 'Teslimattan önce kontrol', 'text' => 'Yüzey görünümü, renk tutarlılığı ve kaplama bütünlüğü teslimat öncesinde kontrol edilir; parçalar özenle paketlenir.', 'icon' => 'badge-check', 'image' => 0 ),
					array( 'eyebrow' => 'Tesis', 'title' => 'Sakarya / Erenler\'deki tesisimiz', 'text' => 'Eloksal ve kaplama işlemlerini Sakarya Erenler\'deki tesisimizde gerçekleştiriyoruz. Parçalarınızı ve teknik taleplerinizi yerinde değerlendirmek için bizimle iletişime geçebilirsiniz.', 'icon' => 'factory', 'image' => 0 ),
				),
				'show_sectors'   => 1,
				'facility_title' => 'Tesisimizden',
				'cta_title'      => 'Parçalarınız için doğru yüzey çözümünü konuşalım.',
				'cta_text'       => 'Teknik resminizi veya parça fotoğraflarınızı paylaşın, size uygun yöntemi birlikte belirleyelim.',
			),
		),
		'quality' => array(
			'title'    => 'Kalite Politikamız',
			'slug'     => 'kalite-politikamiz',
			'template' => 'quality',
			'order'    => 2,
			'content'  => "Can Eloksal olarak kaliteyi yalnızca işlemin sonunda yapılan bir kontrol olarak değil, üretim sürecinin tamamına yayılan bir çalışma disiplini olarak görüyoruz.\n\nAlüminyum yüzey işlem ve eloksal uygulamalarında; müşterilerimizin teknik beklentilerini doğru anlamayı, prosesleri kontrollü şekilde yürütmeyi ve her projede tutarlı sonuçlar elde etmeyi hedefliyoruz.\n\nSavunma sanayi, makine sanayi, endüstriyel üretim ve metal işleme alanlarında hizmet verirken kullanılan malzemenin yapısına, talep edilen yüzey özelliğine, renk seçimine ve mikron değerine uygun çözümler geliştirmeye önem veriyoruz.",
			'meta'     => array(
				'hero_title'       => 'KALİTE POLİTİKAMIZ',
				'hero_eyebrow'     => 'Kurumsal',
				'hero_subtitle'    => 'Her aşamada kalite, her yüzeyde özen.',
				'principles_title' => 'Kalite ilkelerimiz',
				'principles'       => array(
					array( 'icon' => 'target', 'title' => 'Doğru Proses', 'text' => 'Malzemeye, alaşıma ve beklentiye en uygun yöntemi seçmek kalitenin ilk adımıdır.' ),
					array( 'icon' => 'settings', 'title' => 'Kontrollü Üretim', 'text' => 'Proses parametrelerini her partide kontrol altında tutarak tekrarlanabilir sonuçlar hedefleriz.' ),
					array( 'icon' => 'sparkle', 'title' => 'Yüzey Kalitesi', 'text' => 'Renk tutarlılığı, yüzey görünümü ve kaplama bütünlüğü üzerinde titizlikle dururuz.' ),
					array( 'icon' => 'ruler', 'title' => 'Ölçülebilir Sonuç', 'text' => 'Talep edilen mikron ve yüzey özelliklerini ölçülebilir kriterlerle değerlendiririz.' ),
					array( 'icon' => 'refresh', 'title' => 'Sürekli Gelişim', 'text' => 'Teknolojimizi ve süreçlerimizi geri bildirimlerle sürekli iyileştiririz.' ),
					array( 'icon' => 'users', 'title' => 'Müşteri Odaklı Çalışma', 'text' => 'Teknik beklentileri baştan netleştirir, süreç boyunca açık iletişim kurarız.' ),
				),
				'steps_title'      => 'Kalite kontrol adımlarımız',
				'steps'            => array(
					array( 'title' => 'Yüzey Hazırlığı', 'text' => 'Yağ alma, dağlama ve parlatma adımlarının parçaya uygun şekilde uygulanması.' ),
					array( 'title' => 'Proses Kontrolü', 'text' => 'Banyo koşulları, süre ve akım değerlerinin talep edilen sonuca göre izlenmesi.' ),
					array( 'title' => 'Yüzey Kontrolü', 'text' => 'Renk, görünüm ve kaplama bütünlüğünün teslimat öncesi kontrolü.' ),
					array( 'title' => 'Sürekli İyileştirme', 'text' => 'Her projeden edinilen deneyimin süreçlere geri beslenmesi.' ),
				),
			),
		),
		'gallery' => array(
			'title'    => 'Galeri',
			'slug'     => 'galeri',
			'template' => 'gallery',
			'order'    => 3,
			'meta'     => array( 'hero_subtitle' => 'Eloksal, kaplama ve üretim süreçlerimizden kareler.' ),
		),
		'blog'    => array( 'title' => 'Blog', 'slug' => 'blog', 'order' => 4, 'meta' => array( 'hero_eyebrow' => 'Teknik Bilgi Merkezi', 'hero_subtitle' => 'Eloksal ve yüzey kaplama hakkında teknik rehberler.' ) ),
		'banks'   => array(
			'title'    => 'Banka Hesapları',
			'slug'     => 'banka-hesaplari',
			'template' => 'banks',
			'order'    => 5,
			'content'  => 'Ödemelerinizde açıklama kısmına firma adınızı ve teklif / fatura numaranızı yazmanızı rica ederiz.',
			'meta'     => array( 'hero_subtitle' => 'Ödemeleriniz için güncel banka hesap bilgilerimiz.' ),
		),
		'contact' => array(
			'title'    => 'İletişim',
			'slug'     => 'iletisim',
			'template' => 'contact',
			'order'    => 6,
			'meta'     => array(
				'hero_subtitle' => 'Parçalarınız ve yüzey işlem ihtiyaçlarınız için bize ulaşın.',
				'form_title'    => 'Bize yazın',
				'form_text'     => 'Mesajınızı bırakın, en kısa sürede dönüş yapalım.',
				'subjects'      => "Genel bilgi\nEloksal hizmeti\nKimyasal kaplama\nNumune çalışması\nDiğer",
			),
		),
		'quote'   => array(
			'title'    => 'Teklif Al',
			'slug'     => 'teklif-al',
			'template' => 'quote',
			'order'    => 7,
			'meta'     => array(
				'hero_subtitle' => 'Parça bilgilerinizi paylaşın; malzemenize uygun eloksal veya kaplama çözümü için size dönüş yapalım.',
				'aside_title'   => 'Teklif süreci',
				'aside_steps'   => "Formu doldurun, varsa teknik resim veya fotoğraf ekleyin.\nTalebiniz teknik ekibimiz tarafından incelenir.\nGerekirse ek bilgi için sizinle iletişime geçilir.\nSize uygun yöntem ve teklif iletilir.",
				'aside_note'    => 'Teknik resim, parça fotoğrafı veya şartname eklemeniz teklif sürecini hızlandırır.',
			),
		),
		'kvkk'    => array(
			'title'   => 'KVKK Aydınlatma Metni',
			'slug'    => 'kvkk',
			'order'   => 20,
			'content' => "Bu aydınlatma metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) uyarınca, veri sorumlusu sıfatıyla {$company} tarafından, internet sitemiz üzerinden paylaştığınız kişisel verilerin işlenmesine ilişkin olarak sizi bilgilendirmek amacıyla hazırlanmıştır.\n\n## İşlenen kişisel veriler\n\nİletişim ve teklif formları aracılığıyla ad soyad, firma adı, telefon numarası, e-posta adresi, mesaj içeriği ile teklif talebine eklediğiniz dosyalar; ayrıca güvenlik amacıyla IP adresi ve tarayıcı bilgisi işlenmektedir.\n\n## İşleme amaçları\n\n- Talep, soru ve teklif başvurularınızın değerlendirilmesi ve size dönüş yapılması,\n- Teklif hazırlanması ve ticari ilişkinin kurulması,\n- Site güvenliğinin sağlanması ve kötüye kullanımın önlenmesi,\n- Yasal yükümlülüklerin yerine getirilmesi.\n\n## Hukuki sebepler\n\nKişisel verileriniz; KVKK madde 5/2 (c) kapsamında bir sözleşmenin kurulması veya ifasıyla doğrudan ilgili olması, (ç) veri sorumlusunun hukuki yükümlülüğünü yerine getirmesi, (f) meşru menfaat ve açık rızanızın bulunduğu hallerde açık rıza hukuki sebeplerine dayanılarak işlenmektedir.\n\n## Aktarım\n\nKişisel verileriniz, yalnızca yukarıdaki amaçlarla sınırlı olarak ve mevzuata uygun şekilde; hizmet aldığımız barındırma ve e-posta altyapı sağlayıcıları ile talep halinde yetkili kamu kurumlarıyla paylaşılabilir.\n\n## Saklama süresi\n\nVeriler, işleme amacının gerektirdiği süre ve ilgili mevzuatta öngörülen süreler boyunca saklanır; sürenin sonunda silinir, yok edilir veya anonim hale getirilir.\n\n## Haklarınız\n\nKVKK madde 11 uyarınca; kişisel verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltilmesini veya silinmesini isteme, itiraz etme ve zararın giderilmesini talep etme haklarına sahipsiniz. Başvurularınızı " . ce_opt( 'email' ) . ' adresine iletebilirsiniz.',
		),
		'privacy' => array(
			'title'   => 'Gizlilik Politikası',
			'slug'    => 'gizlilik-politikasi',
			'order'   => 21,
			'content' => "Can Eloksal olarak internet sitemizi ziyaret eden kullanıcılarımızın gizliliğine önem veriyoruz. Bu politika, sitemizi kullanırken hangi bilgilerin toplandığını ve nasıl kullanıldığını açıklar.\n\n## Toplanan bilgiler\n\nSitemizde üyelik sistemi bulunmamaktadır. Yalnızca iletişim ve teklif formlarında kendi isteğinizle paylaştığınız bilgiler ile güvenlik amacıyla IP adresi ve tarayıcı bilgisi kaydedilir.\n\n## Bilgilerin kullanımı\n\nPaylaştığınız bilgiler yalnızca talebinizi yanıtlamak ve teklif hazırlamak amacıyla kullanılır; üçüncü kişilere satılmaz veya pazarlama amacıyla paylaşılmaz.\n\n## Dosyalar\n\nTeklif formu ile gönderdiğiniz dosyalar herkese açık olmayan, korumalı bir alanda saklanır ve yalnızca yetkili personel tarafından görüntülenebilir.\n\n## Çerezler\n\nÇerez kullanımıyla ilgili ayrıntılar Çerez Politikası sayfamızda yer almaktadır.\n\n## İletişim\n\nGizlilikle ilgili sorularınız için " . ce_opt( 'email' ) . ' adresinden bize ulaşabilirsiniz.',
		),
		'cookie'  => array(
			'title'   => 'Çerez Politikası',
			'slug'    => 'cerez-politikasi',
			'order'   => 22,
			'content' => "Çerezler, ziyaret ettiğiniz internet siteleri tarafından tarayıcınıza kaydedilen küçük metin dosyalarıdır.\n\n## Kullandığımız çerezler\n\n- Zorunlu çerezler: Sitenin güvenli ve doğru çalışması için gereklidir (ör. form güvenliği, çerez tercihinizin hatırlanması).\n- Analitik çerezler: Yalnızca çerez bandında onay vermeniz halinde, siteyi nasıl kullandığınızı anonim olarak ölçmek için etkinleşir.\n\nSitemizde reklam veya pazarlama amaçlı çerez kullanılmamaktadır.\n\n## Tercihlerinizi yönetme\n\nÇerez tercihinizi sayfanın altındaki “Çerez tercihleri” bağlantısından istediğiniz zaman değiştirebilirsiniz. Ayrıca tarayıcı ayarlarınızdan çerezleri silebilir veya engelleyebilirsiniz.",
		),
	);
}

/**
 * Menüleri oluşturur ve konumlara atar (var olan atamalara dokunmaz).
 *
 * @param array $ids Sayfa ID'leri.
 * @param array $log Günlük.
 */
function ce_seed_menus( $ids, &$log ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );

	$make = static function ( $name, $location, $items ) use ( &$locations, &$log ) {
		if ( ! empty( $locations[ $location ] ) && wp_get_nav_menu_object( $locations[ $location ] ) ) {
			return;
		}
		$menu = wp_get_nav_menu_object( $name );
		$id   = $menu ? $menu->term_id : wp_create_nav_menu( $name );
		if ( is_wp_error( $id ) ) {
			return;
		}
		if ( ! $menu ) {
			$parents = array();
			foreach ( $items as $pos => $item ) {
				$args = array(
					'menu-item-title'     => $item['title'],
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $pos + 1,
					'menu-item-parent-id' => isset( $item['parent'] ) ? ( $parents[ $item['parent'] ] ?? 0 ) : 0,
					'menu-item-classes'   => $item['classes'] ?? '',
				);
				if ( ! empty( $item['page'] ) ) {
					$args['menu-item-type']      = 'post_type';
					$args['menu-item-object']    = 'page';
					$args['menu-item-object-id'] = $item['page'];
				} elseif ( ! empty( $item['archive'] ) ) {
					$args['menu-item-type']   = 'post_type_archive';
					$args['menu-item-object'] = $item['archive'];
				} elseif ( ! empty( $item['post'] ) ) {
					$args['menu-item-type']      = 'post_type';
					$args['menu-item-object']    = get_post_type( $item['post'] );
					$args['menu-item-object-id'] = $item['post'];
				} else {
					$args['menu-item-type'] = 'custom';
					$args['menu-item-url']  = $item['url'];
				}
				$item_id = wp_update_nav_menu_item( $id, 0, $args );
				if ( isset( $item['key'] ) && ! is_wp_error( $item_id ) ) {
					$parents[ $item['key'] ] = $item_id;
				}
			}
		}
		$locations[ $location ] = $id;
		$log[]                  = '✓ Menü: ' . $name;
	};

	$service = static function ( $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'ce_service' );
		return $p ? $p->ID : 0;
	};

	$make(
		'Ana Menü',
		'primary',
		array(
			array( 'title' => 'Ana Sayfa', 'url' => home_url( '/' ) ),
			array( 'title' => 'Kurumsal', 'page' => $ids['about'], 'key' => 'corp' ),
			array( 'title' => 'Hakkımızda', 'page' => $ids['about'], 'parent' => 'corp' ),
			array( 'title' => 'Kalite Politikamız', 'page' => $ids['quality'], 'parent' => 'corp' ),
			array( 'title' => 'Hizmetler', 'archive' => 'ce_service', 'classes' => 'ce-auto-services' ),
			array( 'title' => 'Galeri', 'page' => $ids['gallery'] ),
			array( 'title' => 'Blog', 'page' => $ids['blog'] ),
			array( 'title' => 'Banka Hesapları', 'page' => $ids['banks'] ),
			array( 'title' => 'İletişim', 'page' => $ids['contact'] ),
		)
	);
	$make(
		'Kurumsal',
		'footer_corporate',
		array(
			array( 'title' => 'Hakkımızda', 'page' => $ids['about'] ),
			array( 'title' => 'Kalite Politikamız', 'page' => $ids['quality'] ),
			array( 'title' => 'Teklif Al', 'page' => $ids['quote'] ),
		)
	);
	$eloksal = get_term_by( 'slug', 'eloksal', 'ce_service_cat' );
	$make(
		'Hizmetler',
		'footer_services',
		array_values(
			array_filter(
				array(
					array( 'title' => 'Naturel Eloksal', 'post' => $service( 'naturel-eloksal' ) ),
					array( 'title' => 'Renkli Eloksal', 'url' => $eloksal ? get_term_link( $eloksal ) : ce_services_url() ),
					array( 'title' => 'Alodin', 'post' => $service( 'alodin-kaplama' ) ),
					array( 'title' => 'Kromat', 'post' => $service( 'kromat-kaplama' ) ),
					array( 'title' => 'Kuru Film', 'post' => $service( 'kuru-film-yaglama-kaplamasi' ) ),
				),
				static fn( $i ) => ! array_key_exists( 'post', $i ) || $i['post']
			)
		)
	);
	$make(
		'Hızlı Linkler',
		'footer_quick',
		array(
			array( 'title' => 'Blog', 'page' => $ids['blog'] ),
			array( 'title' => 'Galeri', 'page' => $ids['gallery'] ),
			array( 'title' => 'Banka Hesapları', 'page' => $ids['banks'] ),
			array( 'title' => 'İletişim', 'page' => $ids['contact'] ),
		)
	);
	$make(
		'Yasal',
		'footer_legal',
		array(
			array( 'title' => 'KVKK', 'page' => $ids['kvkk'] ),
			array( 'title' => 'Gizlilik Politikası', 'page' => $ids['privacy'] ),
			array( 'title' => 'Çerez Politikası', 'page' => $ids['cookie'] ),
		)
	);
	set_theme_mod( 'nav_menu_locations', $locations );
}
