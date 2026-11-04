<?php
/**
 * Yapay zekâ metinlerinde çeşitlilik: ürün açıklaması ve kart notu her seferinde başka bir tarzda yazılsın.
 *
 * Ürün açıklaması için 5 boyut (anlatım sesi × açılış × yapı × odak × liste başlığı) birleşir: 300.000'i aşkın
 * tarz. Son kullanılan tarzlar ve son açılış cümleleri tekrar edilmez. Yapay zekâya ulaşılamazsa hazır şablon da
 * cümle havuzlarından her seferinde farklı kurulur. Kart notlarında da aynı mantık: not başına ayrı tarz, yapay
 * zekâ yoksa özel güne göre giriş × ana cümle × kapanış birleşimi (her özel gün için yüzlerce farklı not).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Listeden rastgele bir öğe.
 *
 * @param array $a Liste.
 * @return mixed
 */
function df_pick( $a ) {
	return $a ? $a[ array_rand( $a ) ] : '';
}

/**
 * Ürün açıklaması tarz boyutları.
 *
 * @return array<string,string[]>
 */
function df_style_dims() {
	return array(
		'voice'       => array(
			'zarif bir butik çiçekçinin sakin, rafine sesiyle', 'bir moda dergisinin editoryal, şık diliyle',
			'sıcak bir hikâye anlatıcısı gibi, okuru bir sahnenin içine alarak', 'minimalist bir lüks markanın az ve öz cümleleriyle',
			'çiçekleri iyi tanıyan bir botanik tutkununun bilgili ama samimi diliyle', 'sevdiğine yazılmış kısa bir mektubun içtenliğiyle',
			'modern, şehirli ve enerjik bir dille', 'bir sanat galerisi küratörünün tasarımı anlatır gibi özenli diliyle',
			'Ege\'nin ferah, güneşli ruhunu taşıyan bir dille', 'koku, doku ve renk gibi duyulara seslenen betimleyici bir dille',
			'hafif nostaljik, zamansız bir zarafetle', 'yalın, net ve güven veren bilgilendirici bir dille',
			'davetkâr ve sıcak, okuru hediye anını hayal etmeye çağıran bir dille', 'kutlama coşkusu taşıyan neşeli bir dille',
			'huzur veren, yumuşak ve dingin bir dille', 'deneyimli bir hediye danışmanının yol gösteren diliyle',
			'İzmir\'i ve şehrin hayatını tanıyan yerel bir çiçekçinin samimi diliyle', 'şiirsel ama abartısız, ölçülü bir dille',
			'özgüvenli, iddialı ve premium bir marka diliyle', 'kısa cümleli, ritmik ve akıcı bir dille',
			'bir iç mimarın dekorasyon önerisi gibi, mekânla kurduğu uyumu anlatan bir dille', 'bir fotoğrafçının kareyi anlatır gibi ışık ve kompozisyona odaklanan diliyle',
			'eski bir dostun tavsiyesi gibi rahat ve içten bir dille', 'bir parfüm tanıtımı gibi notalarla (ilk izlenim, kalp, kalıcı iz) anlatan bir dille',
			'bir düğün planlayıcısının incelikli, detaycı diliyle', 'genç ve dinamik, sosyal medyaya yakın ama özenli bir dille',
			'klasik, ağırbaşlı ve saygın bir dille', 'merak uyandıran, sırrını yavaş yavaş açan bir dille',
			'bir seyahat yazarının mekân ve an betimlemesi gibi', 'tatlı bir mizah dokunuşu taşıyan, gülümseten bir dille',
			'bir şefin tabak anlatır gibi katman katman tarif eden diliyle', 'mektup değil, kısa bir günlük sayfası gibi kişisel bir dille',
			'butik bir otel lobisinin sakin lüksünü çağrıştıran bir dille', 'romantik bir film sahnesi anlatır gibi sinematik bir dille',
			'bilgece, olgun ve yavaş akan bir dille', 'pratik, çözüm odaklı ve hızlı karar verdiren bir dille',
			'bir müzik parçası gibi ritim, ton ve uyumdan söz eden bir dille', 'bahar sabahı gibi taze, aydınlık bir dille',
			'kadife gibi yumuşak, sıcak ve sarmalayan bir dille', 'cesur, çarpıcı ve akılda kalan kısa vurgularla',
		),
		'opening'     => array(
			'okura doğrudan yöneltilen kısa bir soruyla', 'çiçeklerin teslim edildiği anı betimleyen bir sahneyle',
			'tasarımın uyandırdığı duyguyla', 'baskın rengin ve tonların etkisiyle',
			'kapı çaldığında karşıdakinin yüzünde oluşacak ifadeyle', 'tek cümlelik, güçlü ve kısa bir girişle',
			'bu çiçeği alacak kişiyi hayal ettirerek', 'çiçeklerin taşıdığı anlam ve simgeyle',
			'mevsimin ya da günün ruhuyla', 'tasarımdaki dikkat çeken bir ayrıntıyla (ambalaj, kurdele, form)',
			'çiçeklerin bir mekâna (masa, salon, ofis) kattığı havayla', 'bir zıtlıkla (sade ama etkileyici, küçük ama anlamlı gibi)',
			'ürün adının kendisinden yola çıkan bir cümleyle', 'sözcüklerin yetmediği anlara gönderme yaparak',
			'atölyede hazırlanma anına değinerek', 'bir kutlamanın ya da sürprizin heyecanıyla',
			'bir benzetmeyle (çiçeği bir şeye benzeterek)', 'günün bir saatine (sabah, akşam üstü) gönderme yaparak',
			'bir sessizliği ya da durgun bir anı anlatarak', 'okurun aklındaki bir soruyu yanıtlayarak',
			'çiçeğe bakan birinin ilk saniyesini anlatarak', 'bir dokunuşu (kurdeleyi çözmek, ambalajı açmak) anlatarak',
			'tek kelimelik bir cümleyle ve ardından açıklamayla', 'bir dileği dile getirerek',
			'bir ilişkiyi (anne, sevgili, arkadaş) anarak', 'renklerin birbirini tamamlayışını anlatarak',
			'bir hatıraya gönderme yaparak', 'kısa bir tanımla (Bu bir buket değil, bir … gibi)',
			'bir sürprizin planlanma anını anlatarak', 'çiçeklerin kokusunu ya da tazeliğini hissettirerek',
		),
		'structure'   => array(
			'2 paragraf ve sonunda 3-4 maddelik kısa bir liste', '3 kısa paragraf ve sonunda 3 maddelik bir liste',
			'1 akıcı paragraf, ardından 4 maddelik liste, ardından tek cümlelik kapanış paragrafı', '2 paragraf, ardından 3 maddelik liste; ilk paragraf duygu, ikincisi tasarım ve teslimat',
			'kısa bir giriş cümlesi paragrafı, bir betimleme paragrafı ve 4 maddelik liste', '2 orta uzunlukta paragraf ve sonda 5 kısa maddelik liste',
			'3 paragraf (duygu, tasarım, sipariş kolaylığı) ve 3 maddelik liste', '1 giriş paragrafı, 3 maddelik liste ve liste sonrası kısa bir davet paragrafı',
			'çok kısa bir giriş, 2 maddelik liste, bir betimleme paragrafı ve ikinci bir 3 maddelik liste (iki ayrı başlıkla)', '4 kısa paragraf (her biri 1-2 cümle) ve sonda 3 maddelik liste',
			'önce liste (4 madde), sonra 2 paragraf', 'bir paragraf, 3 maddelik liste, bir paragraf daha',
			'soru-cevap düzeni: 2 kısa soru başlığı ve altında birer paragraf, sonda 3 maddelik liste', 'tek uzun paragraf ve sonda 4 maddelik liste',
			'2 paragraf ve sonda madde yerine kısa bir kapanış cümlesi; liste ikinci paragraftan önce olsun', '3 paragraf; ikincisi tamamen tasarımın ayrıntılarına ayrılsın, sonda 3 maddelik liste',
		),
		'focus'       => array(
			'renk uyumu ve görsel etki', 'yarattığı duygu ve bıraktığı iz', 'hediye edilme anı ve sürpriz etkisi',
			'tasarım, ambalaj ve el işçiliği', 'tazelik ve günlük hazırlanma', 'uygun olduğu özel günler',
			'alıcının kim olabileceği ve ona ne hissettireceği', 'evde ya da ofiste yarattığı atmosfer', 'zarafet ve sadelik',
			'İzmir içinde aynı gün teslimatın kolaylığı', 'çiçeklerin anlamı ve sembolizmi', 'fotoğraftaki görünümün aynısının gelmesi (güven)',
			'not kartıyla kişiselleştirme', 'son dakika hediyesi olarak pratikliği', 'premium ve gösterişli duruşu',
			'romantik etkisi', 'aile ve büyüklere uygunluğu', 'iş dünyasında şık bir jest oluşu', 'uzun süre canlı kalması',
			'fotoğraf ve paylaşım anına yakışması',
		),
		'list'        => array(
			'Kimler için uygun', 'Neden seveceksiniz', 'Bu tasarımı öne çıkaranlar', 'Hangi anlar için', 'Öne çıkanlar',
			'Tasarım notları', 'Kime yakışır', 'Bir bakışta', 'Sizi neler bekliyor', 'Bu buketle söyleyebilecekleriniz',
			'En çok tercih edildiği anlar', 'Siparişinizle birlikte', 'Kısaca', 'Neden bu tasarım', 'Hediye ipuçları',
			'Tasarımın imzası', 'Hangi duygular için', 'Akılda kalanlar', 'Küçük ayrıntılar', 'Ne zaman gönderilir',
			'Size kazandırdıkları', 'Bu çiçekle', 'Tasarımın hikâyesi', 'Kimin gününü güzelleştirir', 'Neler içerir',
		),
		'perspective' => array(
			'okura "siz" diye seslen', 'okura hiç doğrudan seslenme, tasarımı üçüncü şahısla anlat',
			'okura "siz" diye seslen ve çiçeği alacak kişiden "o" diye söz et', 'çiçeğin kendisini anlatıcı yapmadan, ama ona canlı bir karakter gibi davranarak anlat',
			'atölye olarak "biz" dilini kullan',
		),
		'rhythm'      => array(
			'kısa ve uzun cümleleri dengeli karıştır', 'çoğunlukla kısa cümleler kullan', 'akıcı, orta uzunlukta cümleler kullan',
			'her paragrafı vurucu kısa bir cümleyle bitir', 'en az bir paragrafı tek cümleden oluştur', 'cümleleri farklı kelimelerle başlat, aynı başlangıcı iki kez kullanma',
		),
		'imagery'     => array(
			'ışık ve gün batımı', 'deniz ve Ege esintisi', 'müzik ve melodi', 'ipek ve kadife gibi dokular', 'bahçe ve doğa',
			'sanat ve tablo', 'mektup ve sözcükler', 'zaman ve anılar', 'kutlama ve ışıltı', 'sabah kahvesi ve sakinlik',
			'imza ve kalıcı iz', 'yolculuk ve kavuşma', 'hiçbir benzetme kullanma, somut ve net anlat',
		),
		'closing'     => array(
			'okuru siparişe nazikçe davet eden bir cümle', 'akılda kalan kısa bir slogan cümlesi', 'alıcının hissedeceği duyguyla biten bir cümle',
			'teslimat kolaylığını hatırlatan bir cümle', 'not kartına ne yazılabileceğine dair kısa bir öneri', 'kapanış cümlesi olmadan, doğrudan listeyle bitir',
			'bir soruyla biten cümle', 'zamansız bir dilekle biten cümle',
		),
	);
}

/**
 * Bu üretim için tarz seç (son 40 tarz tekrar edilmez).
 *
 * @return array<string,string>
 */
function df_style_pick() {
	$dims = df_style_dims();
	$last = (array) get_option( 'df_style_last', array() ); // Boyut başına son kullanılanlar.
	$pick = array();
	foreach ( $dims as $k => $list ) {
		$avoid = isset( $last[ $k ] ) ? (array) $last[ $k ] : array();
		$free  = array_values( array_diff( $list, $avoid ) );
		$pick[ $k ] = df_pick( $free ? $free : $list );
		// Listenin yarısı kadar (en fazla 12) son seçim tekrar edilmez: art arda ürünler hep farklı tarzda.
		$keep        = max( 1, min( 12, (int) floor( count( $list ) / 2 ) ) );
		$avoid[]     = $pick[ $k ];
		$last[ $k ]  = array_slice( $avoid, -$keep );
	}
	update_option( 'df_style_last', $last, false );
	return $pick;
}

/**
 * Tarz satırları (ürün açıklaması talimatına eklenir).
 *
 * @return string
 */
function df_style_prompt() {
	$s    = df_style_pick();
	$used = array_filter( (array) get_option( 'df_style_openers', array() ) );
	$txt  = "\nBu metnin tarzı (her ürün farklı tarzda yazılır, bu tarza sadık kal; önceki ürünlere benzemesin):\n"
		. '- Anlatım: ' . $s['voice'] . "\n"
		. '- Uzun açıklamaya ' . $s['opening'] . " başla.\n"
		. '- Yapı: ' . $s['structure'] . ".\n"
		. '- Ağırlık ver: ' . $s['focus'] . ".\n"
		. '- Bakış açısı: ' . $s['perspective'] . ".\n"
		. '- Cümle ritmi: ' . $s['rhythm'] . ".\n"
		. '- İmge dünyası: ' . $s['imagery'] . ".\n"
		. '- Kapanış: ' . $s['closing'] . ".\n"
		. "- Liste başlığı tam olarak '" . $s['list'] . ":' olsun (ayrı satırda, ardından maddeler); ikinci liste varsa başka bir başlık seç.\n"
		. "- Kısa açıklama, uzun açıklamadan farklı bir açıdan yazılsın; ürün adıyla başlamasın.\n"
		. "- Şu kalıpları kullanma: 'özenle hazırlanan', 'mükemmel bir seçim', 'sevdiklerinizi mutlu edin', 'eşsiz', 'büyüleyici', 'göz alıcı', 'en özel anlarınız', 'unutulmaz bir hediye', 'kalpleri fethedecek'.\n";
	if ( $used ) {
		$txt .= "- Son ürünlerde şu cümlelerle başlandı; bunlara benzeyen bir girişle BAŞLAMA:\n  * " . implode( "\n  * ", array_slice( $used, -8 ) ) . "\n";
	}
	return $txt;
}

/**
 * Üretilen açıklamanın ilk cümlesini sakla (sonraki ürünlerde tekrar edilmesin).
 *
 * @param string $long Uzun açıklama (HTML olabilir).
 */
function df_style_remember( $long ) {
	$t = trim( wp_strip_all_tags( (string) $long ) );
	if ( '' === $t ) {
		return;
	}
	$first = preg_split( '/(?<=[.!?])\s+/u', $t );
	$first = mb_substr( trim( (string) $first[0] ), 0, 90 );
	$list  = (array) get_option( 'df_style_openers', array() );
	$list[] = $first;
	update_option( 'df_style_openers', array_slice( $list, -12 ), false );
}

/**
 * Yapay zekâ yoksa: cümle havuzlarından her seferinde farklı açıklama.
 *
 * @param string $name Ürün adı.
 * @param string $sub  Alt başlık.
 * @param string $one  Ürün türü (gül, orkide, çiçek…).
 * @param string $info Çiçek / renk / gün bilgisi cümlesi (boş olabilir).
 * @return array{short:string,long:string,seo_desc:string}
 */
function df_style_template( $name, $sub, $one, $info ) {
	$n    = $name . ( $sub ? ' (' . $sub . ')' : '' );
	$open = array(
		"Bazı duygular en güzel çiçeklerle anlatılır; $n de tam böyle bir an için tasarlandı.",
		"Kapı çaldığında yüzlerde beliren o ilk gülümseme için: $n.",
		"$n, sade çizgileri ve dengeli formuyla bakanı ilk anda kendine çeken bir $one tasarımı.",
		"Söylemek istediklerinizi tek bir bakışta anlatan bir $one arıyorsanız $n tam size göre.",
		"Taze çiçeklerin canlılığı ile zarif bir dokunuş bir araya geldiğinde ortaya $n çıkıyor.",
		"Sevdiğiniz birinin gününe renk katmanın en kolay yollarından biri: $n.",
		"Atölyemizde her sabah taze çiçeklerle yeniden hazırlanan $n, sıcak ve içten bir hediye.",
		"$n; ister bir kutlama, ister sessiz bir teşekkür olsun, her anı biraz daha özel kılar.",
		"Bir buketin güzelliği ayrıntılarda saklıdır; $n de her ayrıntısı düşünülerek tasarlandı.",
		"Kelimelerin yetmediği yerde çiçekler konuşur. $n bunun için var.",
		"Zamansız bir zarafet arayanlar için $n, dengeli ve göz yormayan bir $one tasarımı sunuyor.",
		"Bir masanın, bir odanın, hatta bir günün havasını değiştirebilecek bir tasarım: $n.",
		"Hediye seçmek bazen zordur; $n bu seçimi kolaylaştıran, herkesin sevdiği bir $one.",
		"İzmir'in güneşli ruhunu taşıyan $n, ferah ve canlı bir $one tasarımı.",
		"Küçük bir sürpriz, büyük bir mutluluk: $n ile sevdiklerinize kendinizi hatırlatın.",
		"$n, ilk bakışta sade, yakından bakıldığında ise her ayrıntısıyla etkileyici.",
		"Ambalajı açıldığı an odaya yayılan tazelik: $n.",
		"Bir teşekkürü, bir özlemi ya da bir kutlamayı taşımaya hazır: $n.",
		"Renklerin uyumu, formun dengesi, tazeliğin canlılığı; $n üçünü bir araya getiriyor.",
		"Bugün birinin gününü güzelleştirmek istiyorsanız, $n iyi bir başlangıç.",
		"Akşam üstü kapıya gelen bir sürpriz düşünün: $n tam o an için.",
		"Sadeliği seven ama etkileyici olmaktan vazgeçmeyenler için: $n.",
		"Bazı hediyeler unutulur, bazıları ise o günün kendisi olur. $n ikincilerden.",
		"$n; ister bir masanın ortasında, ister bir başucunda, bulunduğu yeri aydınlatır.",
		"Söze gerek bırakmayan bir jest arıyorsanız: $n.",
		"$n, bir mektup kadar kişisel, bir kutlama kadar neşeli.",
		"İlk izlenim renkler, kalıcı iz ise duygu: $n ikisini birden bırakıyor.",
		"Planlı bir kutlama ya da son dakika sürprizi; $n her ikisine de yakışır.",
		"Bir sabah kahvesinin yanında bile anı güzelleştirecek bir tasarım: $n.",
		"Uzakta olsanız da yanında olduğunuzu hissettirmenin zarif yolu: $n.",
	);
	$body = array(
		'Çiçekler sipariş gününde seçilir, el işçiliğiyle bir araya getirilir ve özenli bir ambalajla yola çıkar.',
		'Renkler birbirini bastırmadan tamamlar; ortaya hem modern hem de sıcak bir görünüm çıkar.',
		'Ambalajdan kurdeleye kadar her parça, tasarımın bütün olarak zarif görünmesi için seçildi.',
		'Hem evde hem ofiste yakışan bu tasarım, bulunduğu yere ferah bir hava katar.',
		'Taze kesilmiş çiçeklerle hazırlandığı için günlerce canlılığını korur.',
		'Abartıdan uzak, dengeli formu sayesinde her yaştan ve her zevkten alıcıyı mutlu eder.',
		'Teslim edildiği an, karşınızdakine ne kadar düşünüldüğünü hissettirir.',
		'Fotoğraftaki tasarım, aynı özen ve aynı çiçek kalitesiyle sizin için yeniden hazırlanır.',
		'Doğal dokusu ve canlı tonlarıyla, bir hediyeden çok bir anı bırakır.',
		'Sade bir not kartıyla birlikte bile söylemek istediğiniz her şeyi anlatır.',
		'Formu dengeli, renkleri yumuşak; uzun süre bakmaktan yorulmayacağınız bir tasarım.',
		'Her çiçek tek tek seçilir, yerleştirilir ve son kontrolden geçtikten sonra paketlenir.',
		'Kurdelesinden ambalajına kadar, açıldığı an ilk izlenimi güçlü olsun diye düşünüldü.',
		'Hem klasik hem modern zevklere hitap eden çizgisiyle yaş ve tarz ayırt etmez.',
		'Fotoğraflarda olduğu kadar canlı görünür; çoğu zaman daha da güzel.',
		'Bir vazoya alındığında da formunu korur, günlerce evin havasını değiştirir.',
		'İçindeki her çiçek, renk ve boy dengesi gözetilerek bir araya getirildi.',
		'Gösterişli ama yorucu değil; tam kıvamında bir zarafet.',
	);
	$deliv = array(
		"İzmir içindeki teslimat bölgelerine aynı gün gönderilebilir; teslimat gününü ve saat aralığını sipariş sırasında siz seçersiniz.",
		"Siparişiniz İzmir'de aynı gün yola çıkabilir; dilediğiniz gün ve saat aralığını ödeme adımında belirlersiniz.",
		"İzmir içi aynı gün teslimat seçeneğiyle, son dakika sürprizleri bile mümkün.",
		"Ücretsiz not kartına mesajınızı yazın; gerisini biz düşünelim ve İzmir'de dilediğiniz adrese ulaştıralım.",
		"Not kartı ücretsizdir; mesajınızı yazmanız yeterli, tasarım özenle paketlenip zamanında teslim edilir.",
		"Gönderiyi dilediğiniz gün ve saat aralığına planlayabilir, kartınıza kişisel bir not ekleyebilirsiniz.",
	);
	$heads = df_style_dims()['list'];
	$items = array(
		'Doğum günü sürprizleri', 'Yıldönümü kutlamaları', 'Sevgiliye hediye', 'Teşekkür etmek için',
		'Yeni iş ve terfi tebriği', 'Geçmiş olsun dilekleri', 'Annelere ve büyüklere', 'Özür dilemenin zarif yolu',
		'Ofis ve iş yeri hediyesi', 'Sebepsiz bir mutluluk için', 'Söz, nişan ve özel davetler', 'Ev ziyaretleri',
		'Uzaktaki sevdiklere sürpriz', 'Mezuniyet ve başarı kutlaması', 'Yeni eve taşınanlara', 'Sadece hatırlatmak için',
	);
	shuffle( $items );
	$o = df_pick( $open );
	$b = array_rand( array_flip( $body ), 2 );
	$long = $o . ( $info ? ' ' . $info : '' ) . ' ' . $b[0] . "\n\n" . $b[1] . ' ' . df_pick( $deliv ) . "\n\n"
		. df_pick( $heads ) . ":\n- " . implode( "\n- ", array_slice( $items, 0, wp_rand( 3, 4 ) ) );
	$short = array(
		"$name; taze çiçeklerle hazırlanır, İzmir'e aynı gün teslim edilir.",
		"Zarif ve içten bir hediye: $name. İzmir içi aynı gün teslimat.",
		"Sevdiklerinize kendinizi hatırlatmanın en güzel yolu: $name.",
		"Günün çiçekleriyle hazırlanan $name, ücretsiz not kartıyla kapıda.",
		"$name ile küçük bir sürpriz, büyük bir gülümseme. İzmir'de aynı gün.",
		"Her ayrıntısı düşünülmüş bir tasarım: $name. Taze, özenli, zamanında.",
		"İzmir'de aynı gün teslim edilen $name, her özel güne yakışır.",
		"Söylemek istediklerinizi çiçeklerle anlatın: $name.",
	);
	$sd = array(
		"$name İzmir'e aynı gün teslimat ile kapınızda. Taze çiçekler, ücretsiz not kartı ve özenli paketleme. Hemen sipariş verin.",
		"$name ile sevdiklerinizi şaşırtın. İzmir içi aynı gün teslimat, ücretsiz not kartı ve taze çiçek garantisi.",
		"İzmir'de aynı gün çiçek mi arıyorsunuz? $name taze hazırlanır, özenle paketlenir, zamanında teslim edilir.",
		"$name: günün taze çiçekleriyle hazırlanan zarif bir tasarım. İzmir'e aynı gün gönderim, not kartı hediye.",
		"Özel günler için $name. İzmir içi aynı gün teslimat, dilediğiniz saat aralığı ve ücretsiz kart notu.",
	);
	return array(
		'short'    => df_pick( $short ),
		'long'     => $long,
		'seo_desc' => mb_substr( df_pick( $sd ), 0, 155 ),
	);
}

/* -------------------------------------------------------------------------
 * Kart notu
 * ---------------------------------------------------------------------- */

/**
 * Kart notu tarzları (her öneri için ayrı biri seçilir).
 *
 * @return string[]
 */
function df_note_styles() {
	return array(
		'tek cümlelik, vurucu ve kısa', 'iki cümlelik, sıcak ve içten', 'ortak bir anıya gönderme yapan',
		'hafif esprili ama kırıcı olmayan', 'şiirsel ama sade', 'teşekkür duygusunu öne çıkaran',
		'geleceğe dair güzel bir dilek içeren', 'karşıdakinin bir özelliğini öven', 'sürpriz hissini vurgulayan',
		'uzaktan özlemi anlatan', 'gurur ve takdir ifade eden', 'sevgiyi doğrudan söyleyen',
		'küçük bir itirafla başlayan', 'bir soruyla başlayan', 'mevsime ya da güne gönderme yapan',
		'çiçeklerin yerine konuşturduğu', 'yalın ve olgun', 'neşeli ve enerjik', 'sakin ve huzur veren',
		'birlikte geçirilen zamana değinen', 'söz veren', 'cesaret ve destek veren',
		'kutlama coşkusu taşıyan', 'nazik ve saygılı', 'kısa bir hatırlatma gibi', 'romantik ve tutkulu',
		'dostça ve rahat', 'gülümseten bir benzetme kullanan', 'teselli eden ve umut veren', 'minnettarlık dolu',
		'bir şarkı sözü gibi akan (alıntı yapmadan)', 'tek kelimeyle başlayıp açıklayan', 'iki zıt duyguyu buluşturan',
		'küçük bir sırrı paylaşır gibi', 'bir fotoğraf karesini anlatır gibi', 'bir gün planı öneren (bugün şunu yap gibi)',
		'gelecekteki bir buluşmaya gönderme yapan', 'karşıdakinin gülüşünü anlatan', 'bir alışkanlığına sevgiyle takılan',
		'hayatına kattıklarını sayan', 'bir "keşke"yi güzel bir dileğe çeviren', 'uzun bir cümleyle akan, tek nefeslik',
		'üç kısa cümlelik, ritmik', 'çok sade, süssüz ve samimi', 'biraz çocuksu ve tatlı', 'olgun ve bilge',
		'ilk tanışmaya gönderme yapan', 'bir rengi ona benzeten', 'bir kokuyu ona benzeten', 'hayaline gönderme yapan',
		'emeğini takdir eden', 'onu güldürmeyi amaçlayan', 'sessiz bir sarılma gibi', 'bir teşekkürle biten',
		'bir sözle (sözüm olsun gibi) biten', 'bir soruyla biten', 'bir dilekle biten', 'zamanla ilgili (her gün, her yıl) vurgulu',
		'şehre (İzmir, deniz, kordon) dokunan', 'mektup başlangıcı gibi', 'kartpostal gibi kısa ve neşeli',
	);
}

/**
 * Not talimatına eklenecek tarz satırı.
 *
 * @return string
 */
function df_note_style_prompt() {
	$s = df_note_styles();
	shuffle( $s );
	return "\nBu sefer 4 notun tarzları sırasıyla: " . implode( '; ', array_slice( $s, 0, 4 ) ) . ".\n"
		. "Her not farklı bir kelimeyle başlasın; 'Nice mutlu', 'İyi ki varsın' gibi en bilinen kalıplarla başlama.\n";
}

/**
 * Yapay zekâ yoksa: özel güne göre giriş × ana cümle × kapanış birleşimi.
 *
 * @param string $cat  Özel gün.
 * @param string $tone Ton.
 * @return string[] 8 farklı not.
 */
function df_note_combos( $cat, $tone ) {
	$core = array(
		'Doğum Günü'       => array(
			'Yeni yaşın sana en çok istediğin şeyleri getirsin.', 'Bugün senin günün; dünya biraz daha güzel, çünkü sen doğdun.',
			'Yeni yaşında kahkahan hiç eksik olmasın.', 'Pastadaki mumlar kadar dileğin, her birinin gerçek olması dileğiyle.',
			'Bir yaş daha büyüdün ama gülüşün hep aynı kalsın.', 'Bu yıl, şimdiye kadarki en güzel yılın olsun.',
			'Seninle geçen her yıl bir hediye; bugün sıra bende.', 'Yeni yaşın sağlık, huzur ve bol sürprizle dolsun.',
		),
		'Yıl Dönümü'       => array(
			'Birlikte geçen her yıl, bir öncekinden daha güzel.', 'Seninle başlayan hikâyemin en sevdiğim sayfası bugün.',
			'Nice yıllar değil, nice anılar diliyorum; hepsi seninle.', 'Aradan geçen yıllar sadece sevgimi büyüttü.',
			'İyi ki o gün yollarımız kesişti.', 'Her yıl dönümünde sana yeniden âşık oluyorum.',
			'Seninle her gün bir kutlama, bugün sadece adı konmuş hali.', 'Birlikte yürüdüğümüz yol, en sevdiğim yolculuk.',
		),
		'Sevgililer Günü'  => array(
			'Kalbimin en güzel köşesi hep sana ait.', 'Seninle her gün sevgililer günü.',
			'Bu çiçekler, sana söylemeye doyamadığım her şey için.', 'Gülüşün, günümün en güzel anı.',
			'Seni sevmek, yaptığım en doğru şey.', 'Yanımda olduğun her an, kalbim bayram ediyor.',
			'Dünyada en sevdiğim yer, senin yanın.', 'Sana her bakışımda yeniden seçiyorum seni.',
		),
		'Anneler Günü'     => array(
			'Bana verdiğin her şey için sonsuz teşekkürler anne.', 'Dünyanın en güzel kalbi sende, iyi ki annemsin.',
			'Bugün sadece bir gün; sana olan sevgim her gün.', 'Elinin sıcaklığı hâlâ en güvenli yerim.',
			'Ne olduysam senin sayende oldum.', 'Duaların hep yanımda, sevgim hep seninle.',
			'Gülüşün evimizin ışığı, iyi ki varsın anne.', 'Sana bir çiçek, kalbimden bir demet sevgi.',
		),
		'Geçmiş Olsun'     => array(
			'En kısa zamanda sağlığına kavuşmanı diliyorum.', 'Bu çiçekler odana biraz neşe, sana biraz güç getirsin.',
			'Kendine iyi bak; seni yeniden aramızda görmek için sabırsızlanıyoruz.', 'Geçmiş olsun, her şeyin en güzeli yakında.',
			'Güçlü olduğunu biliyorum; bu da geçecek.', 'Şifa dileklerimizle, aklımız ve kalbimiz seninle.',
			'Dinlen, iyileş ve bir an önce gülüşünle dön.', 'Yanında olamasak da dualarımız seninle.',
		),
		'Teşekkür'         => array(
			'Yaptıkların için minnettarım.', 'Desteğin benim için çok değerliydi, iyi ki varsın.',
			'Küçük bir teşekkür, büyük bir minnetle.', 'İyiliğin hiç unutulmayacak.',
			'Her şey için yürekten teşekkürler.', 'Bu çiçekler, söylemekle bitmeyecek bir teşekkür için.',
			'Zor anımda yanımda olduğun için sağ ol.', 'Emeğin ve inceliğin için kocaman bir teşekkür.',
		),
		'Özür'             => array(
			'Seni kırdığım için gerçekten özür dilerim.', 'Bazen yanlış kelimeleri seçiyorum, ama seni sevmekte hiç yanılmadım.',
			'Hatamı biliyorum; bir şans daha verir misin?', 'Bu çiçekler, söyleyemediğim özürler için.',
			'Küslük bize hiç yakışmıyor.', 'Seni üzdüğüm için çok üzgünüm, telafi etmek istiyorum.',
			'Gülüşünü özledim; barışalım mı?', 'Affedersen, bir daha seni üzmemek için elimden geleni yapacağım.',
		),
		'Tebrik & Yeni İş' => array(
			'Başarını yürekten kutlarım.', 'Yeni işinde bol şans ve nice başarılar.',
			'Emeğinin karşılığını almanı görmek harika.', 'Bu daha başlangıç; nice başarılara.',
			'Seninle gurur duyuyorum.', 'Yeni yolculuğun hayırlı ve keyifli olsun.',
			'Hak ettiğin yerdesin, tebrikler.', 'Yeni başlangıcın sana bol bereket getirsin.',
		),
		'Söz & Nişan'      => array(
			'Mutluluğunuz daim olsun.', 'Bir ömür boyu el ele, gönül gönüle.',
			'Bu güzel başlangıç, ömür boyu sürecek bir mutluluğa vesile olsun.', 'Sevginiz her geçen gün büyüsün.',
			'İkinize de yürekten mutluluklar.', 'Bir yastıkta mutlulukla, sağlıkla ve huzurla.',
			'Birlikte kuracağınız hayat, en güzel hikâye olsun.', 'Bugünün heyecanı hiç bitmesin.',
		),
		'Yeni Doğan'       => array(
			'Minik bebeğiniz sağlıkla, mutlulukla büyüsün.', 'Ailenin en yeni üyesine hoş geldin.',
			'Evinize gelen bu minik mutluluk daim olsun.', 'Analı babalı, uzun ve sağlıklı bir ömür diliyoruz.',
			'Gözünüz aydın, Allah bağışlasın.', 'Minik ellerin, büyük mutluluklar getirsin.',
			'Hayatınızın en güzel sayfası açıldı; hayırlı olsun.', 'Bebeğinizin gülüşü evinizi hep aydınlatsın.',
		),
		'Öğretmenler Günü' => array(
			'Emekleriniz ve sabrınız için teşekkürler öğretmenim.', 'Bize kattıklarınız için minnettarız.',
			'Öğrettiğiniz her şey ömür boyu bizimle.', 'Işık olduğunuz için teşekkürler.',
			'İyi ki öğretmenimsiniz.', 'Bilginiz kadar sevginiz de yol gösterdi.',
			'Öğretmenler gününüz kutlu olsun, emeğinize sağlık.', 'Bize inandığınız için teşekkür ederiz.',
		),
		''                 => array(
			'Seni düşündüm ve gülümsedim.', 'Sadece aklımdasın diye.', 'Günün güzel geçsin, kalbin hep hafif olsun.',
			'Bu çiçekler sana iyi ki varsın demek için.', 'Hayatında güzel şeyler hep çoğalsın.', 'Küçük bir sürpriz, kocaman bir sevgiyle.',
			'Uzakta olsam da kalbim yanında.', 'Bugün biraz daha mutlu ol diye.',
		),
	);
	$pre  = array( '', '', '', 'Biliyor musun,', 'Sadece şunu söylemek istedim:', 'Kalbimden:', 'Bir çiçek, bir not:', 'Küçük bir not:' );
	$post = 'resmi' === $tone
		? array( '', 'Saygılarımla.', 'En içten dileklerimle.', 'Sevgi ve saygıyla.' )
		: array( '', '', 'Seni seviyorum.', 'İyi ki varsın.', 'Kocaman sarılıyorum.', 'Hep yanındayım.', 'Sevgiyle.' );
	if ( in_array( $cat, array( 'Geçmiş Olsun', 'Teşekkür', 'Tebrik & Yeni İş', 'Söz & Nişan', 'Yeni Doğan', 'Öğretmenler Günü' ), true ) ) {
		$post = array_values( array_diff( $post, array( 'Seni seviyorum.' ) ) );
	}
	$formal = array(
		'Doğum Günü'       => array( 'Yeni yaşınızın sağlık, mutluluk ve başarıyla geçmesini dileriz.', 'Doğum gününüzü en içten dileklerimizle kutlarız.', 'Nice sağlıklı ve huzurlu yıllara.', 'Yeni yaşınız size güzellikler getirsin.' ),
		'Yıl Dönümü'       => array( 'Yıl dönümünüzü içtenlikle kutlarız.', 'Nice mutlu ve huzurlu yıllar dileriz.', 'Birlikteliğinizin daim olmasını dileriz.', 'Bu anlamlı gününüzü kutlarız.' ),
		'Geçmiş Olsun'     => array( 'Geçmiş olsun, en kısa sürede sağlığınıza kavuşmanızı dileriz.', 'Acil şifalar diler, sevgiyle selamlarız.', 'Sağlığınıza kısa sürede kavuşmanızı temenni ederiz.', 'Bu çiçekler size moral olsun; geçmiş olsun.' ),
		'Teşekkür'         => array( 'Desteğiniz için içtenlikle teşekkür ederiz.', 'Gösterdiğiniz ilgi ve emek için teşekkürler.', 'Nezaketiniz için minnettarız.', 'Değerli katkılarınız için teşekkür ederiz.' ),
		'Tebrik & Yeni İş' => array( 'Yeni görevinizde başarılarınızın devamını dileriz.', 'Başarınızı tebrik eder, nice güzel adımlar dileriz.', 'Yeni işinizin hayırlı olmasını dileriz.', 'Emeğinizin karşılığını aldığınız bu günü kutlarız.' ),
		'Söz & Nişan'      => array( 'Mutluluğunuzun daim olmasını dileriz.', 'Bu güzel başlangıcınızı içtenlikle kutlarız.', 'Bir ömür boyu mutluluklar dileriz.', 'Sevgi ve huzur dolu bir hayat dileriz.' ),
		'Yeni Doğan'       => array( 'Bebeğinizin sağlıkla büyümesini dileriz.', 'Aileniz için hayırlı uğurlu olsun.', 'Gözünüz aydın, minik bebeğinize sağlıklı bir ömür dileriz.', 'Evinize gelen mutluluk daim olsun.' ),
		'Öğretmenler Günü' => array( 'Öğretmenler Gününüzü kutlar, emekleriniz için teşekkür ederiz.', 'Değerli emekleriniz için minnettarız.', 'Işık olduğunuz için teşekkür ederiz.', 'Öğrettikleriniz için sonsuz teşekkürler.' ),
		''                 => array( 'En içten dileklerimizle.', 'Güzel bir gün geçirmenizi dileriz.', 'Sevgi ve saygılarımızla.', 'Bu küçük jestimizi kabul etmenizi dileriz.' ),
	);
	if ( 'resmi' === $tone ) {
		$core = $formal;
	}
	$list = isset( $core[ $cat ] ) ? $core[ $cat ] : $core[''];
	shuffle( $list );
	$out = array();
	foreach ( array_slice( $list, 0, 8 ) as $c ) {
		$p = 'kisa' === $tone || 'resmi' === $tone ? '' : df_pick( $pre );
		$q = 'kisa' === $tone ? '' : df_pick( $post );
		if ( ',' === mb_substr( $p, -1 ) ) {
			$c = mb_strtolower( strtr( mb_substr( $c, 0, 1 ), array( 'İ' => 'i', 'I' => 'ı' ) ), 'UTF-8' ) . mb_substr( $c, 1 );
		}
		$out[] = trim( preg_replace( '/\s+/u', ' ', $p . ' ' . $c . ' ' . $q ) );
	}
	return $out;
}
