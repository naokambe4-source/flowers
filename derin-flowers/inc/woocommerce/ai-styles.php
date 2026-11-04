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
		'voice'     => array(
			'zarif bir butik çiçekçinin sakin, rafine sesiyle',
			'bir moda dergisinin editoryal, şık diliyle',
			'sıcak bir hikâye anlatıcısı gibi, okuru bir sahnenin içine alarak',
			'minimalist bir lüks markanın az ve öz cümleleriyle',
			'çiçekleri iyi tanıyan bir botanik tutkununun bilgili ama samimi diliyle',
			'sevdiğine yazılmış kısa bir mektubun içtenliğiyle',
			'modern, şehirli ve enerjik bir dille',
			'bir sanat galerisi küratörünün tasarımı anlatır gibi özenli diliyle',
			'Ege\'nin ferah, güneşli ruhunu taşıyan bir dille',
			'koku, doku ve renk gibi duyulara seslenen betimleyici bir dille',
			'hafif nostaljik, zamansız bir zarafetle',
			'yalın, net ve güven veren bilgilendirici bir dille',
			'davetkâr ve sıcak, okuru hediye anını hayal etmeye çağıran bir dille',
			'kutlama coşkusu taşıyan neşeli bir dille',
			'huzur veren, yumuşak ve dingin bir dille',
			'deneyimli bir hediye danışmanının yol gösteren diliyle',
			'İzmir\'i ve şehrin hayatını tanıyan yerel bir çiçekçinin samimi diliyle',
			'şiirsel ama abartısız, ölçülü bir dille',
			'özgüvenli, iddialı ve premium bir marka diliyle',
			'kısa cümleli, ritmik ve akıcı bir dille',
		),
		'opening'   => array(
			'okura doğrudan yöneltilen kısa bir soruyla',
			'çiçeklerin teslim edildiği anı betimleyen bir sahneyle',
			'tasarımın uyandırdığı duyguyla',
			'baskın rengin ve tonların etkisiyle',
			'kapı çaldığında karşıdakinin yüzünde oluşacak ifadeyle',
			'tek cümlelik, güçlü ve kısa bir girişle',
			'bu çiçeği alacak kişiyi hayal ettirerek',
			'çiçeklerin taşıdığı anlam ve simgeyle',
			'mevsimin ya da günün ruhuyla',
			'tasarımdaki dikkat çeken bir ayrıntıyla (ambalaj, kurdele, form)',
			'çiçeklerin bir mekâna (masa, salon, ofis) kattığı havayla',
			'bir zıtlıkla (sade ama etkileyici, küçük ama anlamlı gibi)',
			'ürün adının kendisinden yola çıkan bir cümleyle',
			'sözcüklerin yetmediği anlara gönderme yaparak',
			'özenle hazırlanma sürecine (atölye, el işçiliği) değinerek',
			'bir kutlamanın ya da sürprizin heyecanıyla',
		),
		'structure' => array(
			'2 paragraf ve sonunda 3-4 maddelik kısa bir liste',
			'3 kısa paragraf ve sonunda 3 maddelik bir liste',
			'1 akıcı paragraf, ardından 4 maddelik liste, ardından tek cümlelik kapanış paragrafı',
			'2 paragraf, ardından 3 maddelik liste; ilk paragraf duygu, ikincisi tasarım ve teslimat',
			'kısa bir giriş cümlesi paragrafı, bir betimleme paragrafı ve 4 maddelik liste',
			'2 orta uzunlukta paragraf ve sonda 5 kısa maddelik liste',
			'3 paragraf (duygu, tasarım, sipariş kolaylığı) ve 3 maddelik liste',
			'1 giriş paragrafı, 3 maddelik liste ve liste sonrası kısa bir davet paragrafı',
		),
		'focus'     => array(
			'renk uyumu ve görsel etki',
			'yarattığı duygu ve bıraktığı iz',
			'hediye edilme anı ve sürpriz etkisi',
			'tasarım, ambalaj ve el işçiliği',
			'tazelik ve günlük hazırlanma',
			'uygun olduğu özel günler',
			'alıcının kim olabileceği ve ona ne hissettireceği',
			'evde ya da ofiste yarattığı atmosfer',
			'zarafet ve sadelik',
			'İzmir içinde aynı gün teslimatın kolaylığı',
		),
		'list'      => array(
			'Kimler için uygun',
			'Neden seveceksiniz',
			'Bu tasarımı öne çıkaranlar',
			'Hangi anlar için',
			'Öne çıkanlar',
			'Tasarım notları',
			'Kime yakışır',
			'Bir bakışta',
			'Sizi neler bekliyor',
			'Bu buketle söyleyebilecekleriniz',
			'En çok tercih edildiği anlar',
			'Siparişinizle birlikte',
		),
	);
}

/**
 * Bu üretim için tarz seç (son 40 tarz tekrar edilmez).
 *
 * @return array<string,string>
 */
function df_style_pick() {
	$dims   = df_style_dims();
	$recent = (array) get_option( 'df_style_recent', array() );
	$pick   = array();
	for ( $try = 0; $try < 12; $try++ ) {
		$pick = array();
		foreach ( $dims as $k => $list ) {
			$pick[ $k ] = df_pick( $list );
		}
		if ( ! in_array( md5( implode( '|', $pick ) ), $recent, true ) ) {
			break;
		}
	}
	$recent[] = md5( implode( '|', $pick ) );
	update_option( 'df_style_recent', array_slice( $recent, -40 ), false );
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
	$txt  = "\nBu metnin tarzı (her ürün farklı tarzda yazılır, bu tarza sadık kal):\n"
		. '- Anlatım: ' . $s['voice'] . "\n"
		. '- Uzun açıklamaya ' . $s['opening'] . " başla.\n"
		. '- Yapı: ' . $s['structure'] . ".\n"
		. '- Ağırlık ver: ' . $s['focus'] . ".\n"
		. "- Listenin başlığı tam olarak '" . $s['list'] . ":' olsun (ayrı satırda, ardından maddeler).\n"
		. "- Kısa açıklamanın ilk kelimesi ürün adı olmasın; uzun açıklamayla aynı cümleyle başlamasın.\n"
		. "- Şu kalıpları kullanma: 'özenle hazırlanan', 'mükemmel bir seçim', 'sevdiklerinizi mutlu edin', 'eşsiz', 'büyüleyici', 'göz alıcı'.\n";
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
		'birlikte geçirilen zamana değinen', 'sözünü tutan, söz veren', 'cesaret ve destek veren',
		'kutlama coşkusu taşıyan', 'nazik ve saygılı', 'kısa bir hatırlatma gibi', 'romantik ve tutkulu',
		'dostça ve rahat', 'gülümseten bir benzetme kullanan', 'teselli eden ve umut veren', 'minnettarlık dolu',
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
