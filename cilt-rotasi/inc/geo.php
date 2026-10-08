<?php
/**
 * GEO (Generative Engine Optimization): llms.txt, llms-full.txt, Markdown sürümleri, yapay zekâ botları için robots.txt.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Eğitim amaçlı ve arama/yanıt amaçlı bot listeleri.
 *
 * @return array
 */
function cr_ai_bots() {
	return array(
		'training' => array( 'GPTBot', 'Google-Extended', 'CCBot', 'ClaudeBot', 'anthropic-ai', 'Applebot-Extended', 'Bytespider', 'meta-externalagent', 'cohere-training-data-crawler' ),
		'search'   => array( 'OAI-SearchBot', 'ChatGPT-User', 'PerplexityBot', 'Perplexity-User', 'Claude-SearchBot', 'Claude-User', 'Applebot', 'DuckAssistBot', 'MistralAI-User' ),
	);
}

/**
 * robots.txt eklemeleri.
 *
 * @param string $output Çıktı.
 * @param bool   $public Site herkese açık mı.
 * @return string
 */
function cr_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	$bots = cr_ai_bots();
	$mode = cr_opt( 'geo_ai_bots', 'allow' );
	$out  = "\n# Cilt Rotası — yapay zekâ tarayıcıları\n";
	if ( 'allow' === $mode ) {
		foreach ( array_merge( $bots['search'], $bots['training'] ) as $b ) {
			$out .= "User-agent: {$b}\n";
		}
		$out .= "Allow: /\nDisallow: /wp-admin/\n";
	} elseif ( 'search' === $mode ) {
		foreach ( $bots['search'] as $b ) {
			$out .= "User-agent: {$b}\n";
		}
		$out .= "Allow: /\nDisallow: /wp-admin/\n\n";
		foreach ( $bots['training'] as $b ) {
			$out .= "User-agent: {$b}\n";
		}
		$out .= "Disallow: /\n";
	} else {
		foreach ( array_merge( $bots['search'], $bots['training'] ) as $b ) {
			$out .= "User-agent: {$b}\n";
		}
		$out .= "Disallow: /\n";
	}
	$out .= "\n# Arama sonuçları ve dahili sayfalar\nUser-agent: *\nDisallow: /?s=\nDisallow: /search/\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n";
	$extra = trim( (string) cr_opt( 'geo_robots_extra' ) );
	if ( $extra ) {
		$out .= "\n" . $extra . "\n";
	}
	if ( cr_opt( 'geo_llms' ) ) {
		$out .= '# LLM rehberi: ' . home_url( '/llms.txt' ) . "\n";
	}
	return $output . $out;
}
add_filter( 'robots_txt', 'cr_robots_txt', 20, 2 );

/**
 * /llms.txt, /llms-full.txt ve ?format=md isteklerini yakalar.
 *
 * @param WP $wp İstek.
 */
function cr_geo_request( $wp ) {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$base = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$rel  = ltrim( substr( (string) $path, strlen( (string) $base ) ), '/' );
	if ( ! cr_opt( 'geo_llms' ) ) {
		return;
	}
	if ( 'llms.txt' === $rel ) {
		cr_send_text( cr_llms_txt() );
	}
	if ( 'llms-full.txt' === $rel ) {
		cr_send_text( cr_llms_full_txt() );
	}
}
add_action( 'parse_request', 'cr_geo_request', 1 );

/**
 * Düz metin yanıtı.
 *
 * @param string $body İçerik.
 */
function cr_send_text( $body ) {
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	header( 'Cache-Control: public, max-age=3600' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- düz metin.
	exit;
}

/**
 * Markdown sürümü (?format=md) — tekil içeriklerde.
 */
function cr_markdown_view() {
	if ( ! cr_opt( 'geo_markdown' ) || ! is_singular( array( 'post', 'icerik', 'urun_rehberi', 'page' ) ) ) {
		return;
	}
	if ( ! isset( $_GET['format'] ) || 'md' !== $_GET['format'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$post = get_queried_object();
	if ( ! $post || 'publish' !== $post->post_status || post_password_required( $post ) ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/markdown; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	header( 'Link: <' . get_permalink( $post ) . '>; rel="canonical"' );
	echo cr_post_markdown( $post->ID, true ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}
add_action( 'template_redirect', 'cr_markdown_view', 1 );

/**
 * Markdown alternatif bağlantısı.
 */
function cr_markdown_link() {
	if ( cr_opt( 'geo_markdown' ) && is_singular( array( 'post', 'icerik', 'urun_rehberi' ) ) ) {
		echo '<link rel="alternate" type="text/markdown" href="' . esc_url( add_query_arg( 'format', 'md', get_permalink() ) ) . '" title="Markdown">' . "\n";
	}
	if ( cr_opt( 'geo_llms' ) && is_front_page() ) {
		echo '<link rel="alternate" type="text/plain" href="' . esc_url( home_url( '/llms.txt' ) ) . '" title="llms.txt">' . "\n";
	}
}
add_action( 'wp_head', 'cr_markdown_link', 4 );

/**
 * Basit HTML → Markdown dönüştürücü.
 *
 * @param string $html HTML.
 * @return string
 */
function cr_html_to_md( $html ) {
	$html = preg_replace( '#<(script|style|figure|svg|iframe|noscript)[^>]*>.*?</\1>#is', '', $html );
	$html = preg_replace_callback(
		'#<h([1-6])[^>]*>(.*?)</h\1>#is',
		function ( $m ) {
			return "\n\n" . str_repeat( '#', (int) $m[1] ) . ' ' . trim( wp_strip_all_tags( $m[2] ) ) . "\n\n";
		},
		$html
	);
	$html = preg_replace_callback(
		'#<a[^>]+href="([^"]+)"[^>]*>(.*?)</a>#is',
		function ( $m ) {
			$t = trim( wp_strip_all_tags( $m[2] ) );
			return $t ? '[' . $t . '](' . $m[1] . ')' : '';
		},
		$html
	);
	$html = preg_replace( '#<(strong|b)>(.*?)</\1>#is', '**$2**', $html );
	$html = preg_replace( '#<(em|i)>(.*?)</\1>#is', '*$2*', $html );
	$html = preg_replace( '#<li[^>]*>#i', "\n- ", $html );
	$html = preg_replace( '#<blockquote[^>]*>(.*?)</blockquote>#is', "\n> $1\n", $html );
	$html = preg_replace( '#<br\s*/?>#i', "\n", $html );
	$html = preg_replace( '#</(p|div|ul|ol|table|tr|section)>#i', "\n\n", $html );
	$html = preg_replace( '#</t[dh]>#i', ' | ', $html );
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$text = preg_replace( "/[ \t]+/", ' ', $text );
	$text = preg_replace( "/\n\s*\n\s*\n+/", "\n\n", $text );
	return trim( $text );
}

/**
 * Bir yazının Markdown sürümü (kısa cevap, öne çıkanlar, SSS, kaynaklar dahil).
 *
 * @param int  $post_id Yazı.
 * @param bool $full    Tam içerik.
 * @return string
 */
function cr_post_markdown( $post_id, $full = true ) {
	$post = get_post( $post_id );
	$md   = '# ' . wp_strip_all_tags( get_the_title( $post ) ) . "\n\n";
	$md  .= '> Kaynak: ' . get_permalink( $post ) . "\n";
	$md  .= '> Yayın: ' . get_the_date( 'Y-m-d', $post ) . ' · Güncelleme: ' . mysql2date( 'Y-m-d', cr_updated( $post_id ) ) . "\n";
	$md  .= '> Yazar: ' . get_the_author_meta( 'display_name', $post->post_author );
	$rev  = get_post_meta( $post_id, '_cr_reviewer', true );
	if ( $rev ) {
		$md .= ' · Uzman kontrolü: ' . $rev;
	}
	$md .= "\n\n";
	$short = get_post_meta( $post_id, '_cr_short_answer', true );
	if ( $short ) {
		$md .= '**Kısa cevap:** ' . wp_strip_all_tags( $short ) . "\n\n";
	}
	if ( 'icerik' === $post->post_type ) {
		$rows = array(
			'INCI'              => get_post_meta( $post_id, '_cr_inci', true ),
			'Ne işe yarar'      => get_post_meta( $post_id, '_cr_function', true ),
			'Uygun cilt tipleri'=> get_post_meta( $post_id, '_cr_skin_types', true ),
			'Konsantrasyon'     => get_post_meta( $post_id, '_cr_conc', true ),
		);
		foreach ( $rows as $k => $v ) {
			if ( $v ) {
				$md .= '- **' . $k . ':** ' . $v . "\n";
			}
		}
		$md .= "\n";
	}
	$take = cr_lines( get_post_meta( $post_id, '_cr_takeaways', true ) );
	if ( $take ) {
		$md .= "## Akılda kalsın\n\n";
		foreach ( $take as $t ) {
			$md .= '- ' . $t . "\n";
		}
		$md .= "\n";
	}
	if ( $full ) {
		$html = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$md  .= cr_html_to_md( $html ) . "\n\n";
	}
	$steps = cr_steps( $post_id );
	if ( $steps ) {
		$md .= '## ' . ( get_post_meta( $post_id, '_cr_steps_title', true ) ? get_post_meta( $post_id, '_cr_steps_title', true ) : 'Adım adım' ) . "\n\n";
		foreach ( $steps as $i => $s ) {
			$md .= ( $i + 1 ) . '. **' . $s['name'] . '** ' . $s['text'] . "\n";
		}
		$md .= "\n";
	}
	$faq = cr_faq_items( $post_id );
	if ( $faq ) {
		$md .= "## Sık sorulan sorular\n\n";
		foreach ( $faq as $f ) {
			$md .= '### ' . $f['q'] . "\n\n" . wp_strip_all_tags( $f['a'] ) . "\n\n";
		}
	}
	$src = cr_sources( $post_id );
	if ( $src ) {
		$md .= "## Kaynaklar\n\n";
		foreach ( $src as $s ) {
			$md .= '- ' . ( $s['url'] ? '[' . $s['title'] . '](' . $s['url'] . ')' : $s['title'] ) . "\n";
		}
		$md .= "\n";
	}
	return $md;
}

/**
 * llms.txt içeriği.
 *
 * @return string
 */
function cr_llms_txt() {
	$cached = get_transient( 'cr_llms_txt' );
	if ( $cached ) {
		return $cached;
	}
	$out  = '# ' . get_bloginfo( 'name' ) . "\n\n";
	$out .= '> ' . wp_strip_all_tags( cr_opt( 'seo_home_desc' ) ) . "\n\n";
	$out .= wp_strip_all_tags( cr_opt( 'geo_llms_intro' ) ) . "\n\n";
	$out .= '- Dil: Türkçe (tr-TR)' . "\n";
	$out .= '- Site haritası: ' . home_url( '/wp-sitemap.xml' ) . "\n";
	$out .= '- Tüm içeriğin metin sürümü: ' . home_url( '/llms-full.txt' ) . "\n";
	if ( cr_opt( 'geo_markdown' ) ) {
		$out .= '- Her makalenin Markdown sürümü: makale adresine `?format=md` ekleyin' . "\n";
	}
	$out .= "\n";

	$pages = array();
	foreach ( array( 'hakkimizda', 'yayin-ilkeleri', 'iletisim' ) as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status ) {
			$pages[] = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . ')';
		}
	}
	if ( $pages ) {
		$out .= "## Kurumsal\n\n" . implode( "\n", $pages ) . "\n\n";
	}

	$cats = get_categories( array( 'hide_empty' => true ) );
	foreach ( $cats as $cat ) {
		$posts = get_posts(
			array(
				'category'       => $cat->term_id,
				'posts_per_page' => 25,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'OR',
					array( 'key' => '_cr_noindex', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_cr_noindex', 'value' => '1', 'compare' => '!=' ),
				),
			)
		);
		if ( ! $posts ) {
			continue;
		}
		$out .= '## ' . $cat->name . "\n\n";
		foreach ( $posts as $p ) {
			$out .= '- [' . wp_strip_all_tags( get_the_title( $p ) ) . '](' . get_permalink( $p ) . '): ' . cr_excerpt( $p->ID, 26 ) . "\n";
		}
		$out .= "\n";
	}

	$ing = get_posts(
		array(
			'post_type'      => 'icerik',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	if ( $ing ) {
		$out .= '## ' . cr_opt( 'glossary_archive_title' ) . "\n\n";
		foreach ( $ing as $p ) {
			$fn   = get_post_meta( $p->ID, '_cr_function', true );
			$out .= '- [' . wp_strip_all_tags( get_the_title( $p ) ) . '](' . get_permalink( $p ) . ')' . ( $fn ? ': ' . $fn : '' ) . "\n";
		}
		$out .= "\n";
	}

	$prod = ! cr_products_on() ? array() : get_posts(
		array(
			'post_type'      => 'urun_rehberi',
			'posts_per_page' => 100,
			'no_found_rows'  => true,
		)
	);
	if ( $prod ) {
		$out .= '## ' . cr_opt( 'products_archive_title' ) . "\n\n";
		foreach ( $prod as $p ) {
			$out .= '- [' . wp_strip_all_tags( get_the_title( $p ) ) . '](' . get_permalink( $p ) . '): ' . cr_excerpt( $p->ID, 20 ) . "\n";
		}
		$out .= "\n";
	}
	$out .= "## Optional\n\n- [Kısa cevaplar (SSS)](" . home_url( '/#cr-faq' ) . ")\n";
	set_transient( 'cr_llms_txt', $out, 6 * HOUR_IN_SECONDS );
	return $out;
}

/**
 * llms-full.txt içeriği.
 *
 * @return string
 */
function cr_llms_full_txt() {
	$cached = get_transient( 'cr_llms_full' );
	if ( $cached ) {
		return $cached;
	}
	$out   = '# ' . get_bloginfo( 'name' ) . " — tam içerik\n\n" . wp_strip_all_tags( cr_opt( 'geo_llms_intro' ) ) . "\n\n---\n\n";
	$posts = get_posts(
		array(
			'post_type'      => array( 'post', 'icerik', 'urun_rehberi' ),
			'posts_per_page' => (int) cr_opt( 'geo_full_limit', 40 ),
			'orderby'        => 'modified',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				array( 'key' => '_cr_noindex', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_cr_noindex', 'value' => '1', 'compare' => '!=' ),
			),
		)
	);
	foreach ( $posts as $p ) {
		$out .= cr_post_markdown( $p->ID, true ) . "\n\n---\n\n";
	}
	set_transient( 'cr_llms_full', $out, 6 * HOUR_IN_SECONDS );
	return $out;
}

/**
 * İçerik değişince önbelleği temizle.
 */
function cr_geo_flush() {
	delete_transient( 'cr_llms_txt' );
	delete_transient( 'cr_llms_full' );
}
add_action( 'save_post', 'cr_geo_flush' );
add_action( 'deleted_post', 'cr_geo_flush' );
add_action( 'update_option_' . CR_OPTION, 'cr_geo_flush' );
