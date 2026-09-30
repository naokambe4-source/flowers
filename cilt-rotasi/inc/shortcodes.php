<?php
/**
 * Kısa kodlar, blok desenleri, blok stilleri ve cilt testi.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bilgi kutusu kısa kodları: [cr_bilgi], [cr_not], [cr_uyari], [cr_ipucu].
 *
 * @param array  $atts    Nitelikler.
 * @param string $content İçerik.
 * @param string $tag     Etiket.
 * @return string
 */
function cr_sc_box( $atts, $content, $tag ) {
	$map  = array(
		'cr_bilgi' => array( 'info', 'info', 'Bilgi' ),
		'cr_not'   => array( 'note', 'bulb', 'Not' ),
		'cr_uyari' => array( 'warn', 'alert', 'Dikkat' ),
		'cr_ipucu' => array( 'tip', 'sparkle', 'İpucu' ),
	);
	$conf = $map[ $tag ];
	$atts = shortcode_atts( array( 'baslik' => $conf[2] ), $atts, $tag );
	return '<aside class="cr-box cr-box--' . esc_attr( $conf[0] ) . '"><div class="cr-box__icon">' . cr_icon( $conf[1], 22 ) . '</div><div class="cr-box__body"><p class="cr-box__title">' . esc_html( $atts['baslik'] ) . '</p>' . wpautop( do_shortcode( $content ) ) . '</div></aside>';
}
foreach ( array( 'cr_bilgi', 'cr_not', 'cr_uyari', 'cr_ipucu' ) as $cr_sc ) {
	add_shortcode( $cr_sc, 'cr_sc_box' );
}

/**
 * [cr_kisa_cevap]…[/cr_kisa_cevap]
 *
 * @param array  $atts    Nitelikler.
 * @param string $content İçerik.
 * @return string
 */
function cr_sc_short( $atts, $content ) {
	$atts = shortcode_atts( array( 'baslik' => cr_opt( 'art_short_label' ) ), $atts );
	return '<div class="cr-short-answer"><span class="cr-short-answer__label">' . cr_icon( 'sparkle', 16 ) . esc_html( $atts['baslik'] ) . '</span><p class="cr-short-answer__text">' . wp_kses_post( $content ) . '</p></div>';
}
add_shortcode( 'cr_kisa_cevap', 'cr_sc_short' );

/**
 * [cr_icerik ad="niasinamid"] — sözlükten mini kart.
 *
 * @param array $atts Nitelikler.
 * @return string
 */
function cr_sc_ingredient( $atts ) {
	$atts = shortcode_atts( array( 'ad' => '' ), $atts );
	$p    = get_page_by_path( sanitize_title( $atts['ad'] ), OBJECT, 'icerik' );
	if ( ! $p ) {
		return '';
	}
	return '<a class="cr-ing-mini" href="' . esc_url( get_permalink( $p ) ) . '"><span class="cr-ing-mini__icon">' . cr_icon( 'flask', 20 ) . '</span><span><strong>' . esc_html( get_the_title( $p ) ) . '</strong><small>' . esc_html( get_post_meta( $p->ID, '_cr_function', true ) ) . '</small></span>' . cr_icon( 'arrow-up-right', 18 ) . '</a>';
}
add_shortcode( 'cr_icerik', 'cr_sc_ingredient' );

/**
 * [cr_bulten]
 *
 * @return string
 */
function cr_sc_news() {
	ob_start();
	echo '<div class="cr-inline-news"><p class="cr-inline-news__title">' . esc_html( cr_opt( 'news_title' ) ) . '</p><p>' . esc_html( cr_opt( 'news_text' ) ) . '</p>';
	cr_news_form( 'shortcode' );
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'cr_bulten', 'cr_sc_news' );

/**
 * Cilt testi verisi (yalnızca testin olduğu sayfada JS'e aktarılır).
 *
 * @return array|null
 */
function cr_quiz_data() {
	global $post;
	$is_quiz = is_page_template( 'page-templates/template-cilt-testi.php' ) || ( is_singular() && $post && has_shortcode( (string) $post->post_content, 'cilt_testi' ) );
	if ( ! $is_quiz ) {
		return null;
	}
	$qs = array();
	foreach ( (array) cr_opt( 'qz_questions' ) as $q ) {
		if ( empty( $q['q'] ) ) {
			continue;
		}
		$ans = array();
		foreach ( cr_pairs( isset( $q['answers'] ) ? $q['answers'] : '' ) as $a ) {
			$ans[] = array(
				'text' => $a[0],
				'type' => isset( $a[1] ) ? sanitize_key( $a[1] ) : 'normal',
			);
		}
		if ( $ans ) {
			$qs[] = array(
				'q'       => $q['q'],
				'hint'    => isset( $q['hint'] ) ? $q['hint'] : '',
				'answers' => $ans,
			);
		}
	}
	$res = array();
	foreach ( array( 'kuru', 'yagli', 'karma', 'normal', 'hassas' ) as $t ) {
		$res[ $t ] = array(
			'title' => cr_opt( 'qz_' . $t . '_title' ),
			'text'  => cr_opt( 'qz_' . $t . '_text' ),
			'url'   => cr_url( cr_opt( 'qz_' . $t . '_url' ) ),
		);
	}
	return array(
		'questions'  => $qs,
		'results'    => $res,
		'disclaimer' => cr_opt( 'qz_disclaimer' ),
	);
}

/**
 * Cilt testi arayüzü.
 *
 * @return string
 */
function cr_quiz_html() {
	ob_start();
	?>
	<div class="cr-quiz" data-quiz>
		<div class="cr-quiz__intro" data-quiz-step="intro">
			<span class="cr-eyebrow"><?php echo esc_html( cr_opt( 'quiz_note' ) ); ?></span>
			<h2 class="cr-quiz__title"<?php echo cr_edit( 'qz_intro_title' ); // phpcs:ignore ?>><?php cr_t( 'qz_intro_title' ); ?></h2>
			<p<?php echo cr_edit( 'qz_intro_text' ); // phpcs:ignore ?>><?php cr_t( 'qz_intro_text' ); ?></p>
			<button type="button" class="cr-btn cr-btn--primary" data-quiz-start><?php echo esc_html( cr_opt( 'quiz_cta' ) ); ?> <?php echo cr_icon( 'arrow-right', 18 ); // phpcs:ignore ?></button>
		</div>
		<div class="cr-quiz__run" data-quiz-step="run" hidden>
			<div class="cr-quiz__bar" aria-hidden="true"><span data-quiz-progress></span></div>
			<p class="cr-quiz__count" data-quiz-count aria-live="polite"></p>
			<fieldset class="cr-quiz__q">
				<legend class="cr-quiz__question" data-quiz-question></legend>
				<p class="cr-quiz__hint" data-quiz-hint></p>
				<div class="cr-quiz__answers" data-quiz-answers></div>
			</fieldset>
			<button type="button" class="cr-link cr-quiz__back" data-quiz-back><?php echo cr_icon( 'arrow-left', 16 ); // phpcs:ignore ?> Geri</button>
		</div>
		<div class="cr-quiz__result" data-quiz-step="result" hidden tabindex="-1">
			<span class="cr-eyebrow">Sonucun</span>
			<h2 class="cr-quiz__title" data-quiz-rtitle></h2>
			<p data-quiz-rtext></p>
			<div class="cr-quiz__meter" data-quiz-meter></div>
			<div class="cr-quiz__actions">
				<a class="cr-btn cr-btn--primary" data-quiz-rurl href="#">Rotamı gör <?php echo cr_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
				<button type="button" class="cr-btn cr-btn--ghost" data-quiz-restart>Testi yeniden çöz</button>
			</div>
			<p class="cr-quiz__disc" data-quiz-disc></p>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cilt_testi', 'cr_quiz_html' );

/**
 * Blok desenleri ve stilleri.
 */
function cr_block_patterns() {
	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category( 'cilt-rotasi', array( 'label' => 'Cilt Rotası' ) );
	}
	if ( function_exists( 'register_block_style' ) ) {
		register_block_style( 'core/group', array( 'name' => 'cr-green', 'label' => 'Yeşil bilgi kutusu' ) );
		register_block_style( 'core/group', array( 'name' => 'cr-cream', 'label' => 'Krem not kutusu' ) );
		register_block_style( 'core/group', array( 'name' => 'cr-warn', 'label' => 'Uyarı kutusu' ) );
		register_block_style( 'core/table', array( 'name' => 'cr-table', 'label' => 'Cilt Rotası tablosu' ) );
		register_block_style( 'core/list', array( 'name' => 'cr-check', 'label' => 'Onay işaretli liste' ) );
		register_block_style( 'core/quote', array( 'name' => 'cr-expert', 'label' => 'Uzman görüşü' ) );
	}
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}
	register_block_pattern(
		'cilt-rotasi/bilgi-kutusu',
		array(
			'title'      => 'Yeşil bilgi kutusu',
			'categories' => array( 'cilt-rotasi' ),
			'content'    => '<!-- wp:group {"className":"is-style-cr-green"} --><div class="wp-block-group is-style-cr-green"><!-- wp:paragraph {"className":"cr-box-title"} --><p class="cr-box-title"><strong>Bilmekte fayda var</strong></p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Buraya önemli bilgiyi yaz.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
		)
	);
	register_block_pattern(
		'cilt-rotasi/karsilastirma',
		array(
			'title'      => 'Karşılaştırma tablosu (AEO)',
			'categories' => array( 'cilt-rotasi' ),
			'content'    => '<!-- wp:table {"className":"is-style-cr-table"} --><figure class="wp-block-table is-style-cr-table"><table><thead><tr><th>Özellik</th><th>Seçenek A</th><th>Seçenek B</th></tr></thead><tbody><tr><td>Ne işe yarar?</td><td></td><td></td></tr><tr><td>Kimler için?</td><td></td><td></td></tr><tr><td>Ne zaman?</td><td></td><td></td></tr></tbody></table></figure><!-- /wp:table -->',
		)
	);
	register_block_pattern(
		'cilt-rotasi/uzman-gorusu',
		array(
			'title'      => 'Uzman görüşü',
			'categories' => array( 'cilt-rotasi' ),
			'content'    => '<!-- wp:quote {"className":"is-style-cr-expert"} --><blockquote class="wp-block-quote is-style-cr-expert"><!-- wp:paragraph --><p>Uzman görüşünü buraya yaz.</p><!-- /wp:paragraph --><cite>Uzm. Dr. Ad Soyad, Dermatolog</cite></blockquote><!-- /wp:quote -->',
		)
	);
	register_block_pattern(
		'cilt-rotasi/kontrol-listesi',
		array(
			'title'      => 'Kontrol listesi',
			'categories' => array( 'cilt-rotasi' ),
			'content'    => '<!-- wp:list {"className":"is-style-cr-check"} --><ul class="is-style-cr-check"><!-- wp:list-item --><li>Birinci madde</li><!-- /wp:list-item --><!-- wp:list-item --><li>İkinci madde</li><!-- /wp:list-item --><!-- wp:list-item --><li>Üçüncü madde</li><!-- /wp:list-item --></ul><!-- /wp:list -->',
		)
	);
	register_block_pattern(
		'cilt-rotasi/kisa-cevap',
		array(
			'title'      => 'Kısa cevap kutusu',
			'categories' => array( 'cilt-rotasi' ),
			'content'    => '<!-- wp:shortcode -->[cr_kisa_cevap]Sorunun 40–60 kelimelik doğrudan yanıtı.[/cr_kisa_cevap]<!-- /wp:shortcode -->',
		)
	);
}
add_action( 'init', 'cr_block_patterns' );
