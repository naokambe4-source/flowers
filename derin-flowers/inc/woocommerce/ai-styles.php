<?php
/**
 * Yapay zekâ metinlerinde çeşitlilik: ürün açıklaması ve kart notu her seferinde başka bir tarzda yazılsın.
 *
 * Ürün açıklaması satış odaklıdır: önce ürünün ne olduğu, sonra fayda, güvence ve siparişe çağrı. Bu çerçevede
 * 7 boyut (satış yöntemi × ton × açılış × yapı × odak × liste başlığı × çağrı) birleşir: 2,8 milyondan fazla tarz. Son kullanılan tarzlar ve son açılış cümleleri tekrar edilmez. Yapay zekâya ulaşılamazsa hazır şablon da
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
		// Satış metni çerçevesi: her biri kanıtlanmış bir e-ticaret yazım yöntemi.
		'frame'     => array(
			'AIDA: dikkat çeken net bir giriş, ilgi uyandıran ürün özellikleri, isteği artıran fayda, siparişe çağrı',
			'Fayda önce: ürünün alıcıya ne kazandırdığını önce söyle, sonra içeriği ve teslimatı anlat',
			'Özellik → Avantaj → Fayda: her özelliği alıcıya sağladığı somut faydaya bağla',
			'Problem → Çözüm: "doğru hediyeyi bulamamak" ya da "son dakika" derdini, bu ürünün nasıl çözdüğünü anlat',
			'Hediye rehberi: kime, hangi durumda, neden bu ürün; karar vermeyi kolaylaştır',
			'Premium konumlandırma: kaliteyi, el işçiliğini ve sunumu öne çıkar; fiyatın karşılığını hissettir',
			'Güven odaklı: taze çiçek, fotoğraftakiyle aynı tasarım, zamanında teslimat ve kart notu gibi güvenceleri öne çıkar',
			'Duygu + mantık: önce hissettireceği duyguyu bir cümleyle ver, sonra net bilgilerle ikna et',
			'Anı odaklı: teslim edildiği anı kısa bir sahneyle anlat, sonra ürünü ve sipariş kolaylığını yaz',
			'Kısa ve net satış: gereksiz süs yok, kısa paragraflar, güçlü fiiller, net bilgi',
		),
		'tone'      => array(
			'samimi ve sıcak', 'zarif ve premium', 'net ve güven veren', 'enerjik ve neşeli', 'romantik ama ölçülü',
			'profesyonel ve kurumsal', 'içten ve yalın', 'davetkâr ve ikna edici', 'modern ve şık', 'nazik ve özenli',
		),
		'opening'   => array(
			'ürünün ne olduğunu (adet, çiçek, renk, vazo/kutu/buket) net söyleyen bir cümleyle',
			'alıcının yaşayacağı duyguyu söyleyen bir cümleyle; ikinci cümlede ürünün ne olduğunu net yaz',
			'hangi anlar için ideal olduğunu söyleyen bir cümleyle; hemen ardından ürünün içeriğini yaz',
			'kısa bir soruyla (ör. "Ona ne kadar önemli olduğunu göstermek mi istiyorsunuz?"); ardından ürünün içeriğini yaz',
			'ürünün en güçlü özelliğiyle (renk, adet, sunum) başlayan bir cümleyle',
			'hediye almayı kolaylaştıran bir cümleyle (ör. "Doğru hediyeyi düşünmenize gerek yok")',
			'teslim anını anlatan kısa bir cümleyle; ardından ürünün ne olduğunu net yaz',
			'ürünün simgelediği anlamla (kırmızı gül = aşk gibi) başlayan kısa bir cümleyle',
		),
		'structure' => array(
			"1 giriş paragrafı (2-3 cümle), 'Ürün içeriği:' listesi, 1 fayda/kullanım paragrafı, kısa siparişe çağrı cümlesi",
			"2 kısa paragraf (ürün + fayda), ardından 3-4 maddelik özel gün listesi, son paragrafta teslimat ve çağrı",
			"1 giriş paragrafı, 'Ürün içeriği:' listesi, 'Kimler için uygun:' listesi, tek cümlelik çağrı",
			"3 kısa paragraf: ne olduğu, kime/ne zaman, teslimat ve kart notu; sonda 3 maddelik liste",
			"1 güçlü giriş paragrafı, 4 maddelik 'Neden bu ürün:' listesi, kısa teslimat ve çağrı paragrafı",
			"1 giriş paragrafı, 'Ürün içeriği:' listesi, 'Teslimat:' paragrafı (aynı gün, saat aralığı, not kartı)",
		),
		'focus'     => array(
			'romantik etki ve sevgiyi gösterme', 'özel günler (doğum günü, yıl dönümü, sevgililer günü)', 'premium sunum ve kalite',
			'son dakika hediyesinde aynı gün teslimatın rahatlığı', 'tazelik ve uzun ömür', 'fotoğraftakiyle aynı tasarımın gelmesi',
			'özür, barışma ve gönül alma', 'ev, ofis veya masaya yakışan şık görünüm', 'kişisel not kartıyla mesaj iletmek',
			'iş dünyasında ve kurumsal hediyede prestij',
		),
		'list'      => array(
			'Kimler için uygun', 'Neden bu ürün', 'Hangi anlar için', 'Öne çıkanlar', 'Bir bakışta', 'Siparişinizle birlikte',
			'Kime yakışır', 'Neden tercih ediliyor', 'Sizi neler bekliyor', 'Hediye ipuçları',
		),
		'cta'       => array(
			'İzmir içi aynı gün teslimat için şimdi sipariş verin', 'Teslimat gününü ve saatini seçin, gerisini bize bırakın',
			'Not kartınıza mesajınızı yazın, siparişinizi hemen oluşturun', 'Sevdiğinizi bugün şaşırtın: hemen sepete ekleyin',
			'Özel günü kaçırmamak için teslimat tarihini şimdiden seçin', 'Tek tıkla sipariş verin, kapısına biz götürelim',
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
	$txt  = "\nAMAÇ: SATIŞ. Bu bir e-ticaret ürün açıklaması; okuyan 5 saniyede ürünün ne olduğunu anlamalı ve sipariş vermek istemeli.\n"
		. "Satış kuralları:\n"
		. "- Ürün adındaki bilgileri (adet, çiçek türü, renk, vazo / kutu / buket gibi sunum) ilk iki cümlede açıkça yaz. Örn. 'Vazoda 19 Kırmızı Gül' → vazoda sunulan 19 adet kırmızı gül.\n"
		. "- Sadece bilinen bilgiyi yaz; vazonun malzemesi, çiçeğin boyu, ek süs gibi verilmeyen ayrıntıları uydurma.\n"
		. "- Somut faydalar yaz: aynı gün İzmir içi teslimat, teslimat gün ve saatini seçebilme, ücretsiz kişisel not kartı, günlük taze çiçek, fotoğraftaki tasarım.\n"
		. "- Şiirsel ve soyut cümle kurma; benzetme en fazla bir tane olsun. Her cümle ya bilgi versin ya da satın almaya ikna etsin.\n"
		. "- Kısa paragraflar (en fazla 3 cümle). Okunması kolay olsun.\n"
		. "- 'Ürün içeriği:' listesi varsa yalnızca ürün adından ve verilen bilgiden çıkan gerçek kalemleri yaz.\n"
		. "- Kısa açıklama (short): ürünün ne olduğu + en güçlü fayda + teslimat, 1-2 cümle; ürün adını aynen tekrar etmekle başlamasın.\n"
		. "\nBu ürünün yazım tarzı (her ürün farklı yazılır):\n"
		. '- Satış çerçevesi: ' . $s['frame'] . "\n"
		. '- Ton: ' . $s['tone'] . "\n"
		. '- Uzun açıklamaya ' . $s['opening'] . " başla.\n"
		. '- Yapı: ' . $s['structure'] . ".\n"
		. '- Ağırlık ver: ' . $s['focus'] . ".\n"
		. "- Yapıda adı verilen liste başlıklarını aynen kullan; başlığı belirtilmemiş bir liste varsa başlığı '" . $s['list'] . ":' olsun.\n"
		. "- Son cümle şu fikirde bir siparişe çağrı olsun (kelimesi kelimesine değil): " . $s['cta'] . ".\n"
		. "- Şu kalıpları kullanma: 'özenle hazırlanan', 'mükemmel bir seçim', 'eşsiz', 'büyüleyici', 'göz alıcı', 'unutulmaz bir hediye', 'kalpleri fethedecek', 'sihirli', 'masalsı'.\n";
	if ( $used ) {
		$txt .= "- Son ürünlerde şu cümlelerle başlandı; aynı kalıpla BAŞLAMA:\n  * " . implode( "\n  * ", array_slice( $used, -6 ) ) . "\n";
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
