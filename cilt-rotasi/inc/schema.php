<?php
/**
 * Yapılandırılmış veri (JSON-LD @graph): Organization, WebSite + SearchAction, WebPage/MedicalWebPage,
 * Article, BreadcrumbList, FAQPage, HowTo, DefinedTerm, Product/Review, ItemList, Person.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kurum düğümü.
 *
 * @return array
 */
function cr_schema_org() {
	$home  = home_url( '/' );
	$logo  = cr_img_url( cr_opt( 'org_logo' ), 'medium' );
	$logo  = $logo ? $logo : cr_img_url( cr_opt( 'logo_image' ), 'medium' );
	$logo  = $logo ? $logo : get_site_icon_url( 512 );
	$same  = array_merge( cr_lines( cr_opt( 'org_same_as' ) ), array_values( cr_social_links() ) );
	$node  = array(
		'@type'       => 'Organization',
		'@id'         => $home . '#organization',
		'name'        => cr_opt( 'org_name', get_bloginfo( 'name' ) ),
		'url'         => $home,
		'description' => cr_opt( 'org_desc' ),
	);
	if ( cr_opt( 'org_alt' ) ) {
		$node['alternateName'] = cr_opt( 'org_alt' );
	}
	if ( $logo ) {
		$node['logo']  = array(
			'@type' => 'ImageObject',
			'@id'   => $home . '#logo',
			'url'   => $logo,
		);
		$node['image'] = array( '@id' => $home . '#logo' );
	}
	if ( $same ) {
		$node['sameAs'] = array_values( array_unique( $same ) );
	}
	$knows = cr_lines( cr_opt( 'org_knows' ) );
	if ( $knows ) {
		$node['knowsAbout'] = $knows;
	}
	if ( cr_opt( 'org_founded' ) ) {
		$node['foundingDate'] = cr_opt( 'org_founded' );
	}
	if ( cr_opt( 'org_email' ) ) {
		$node['email'] = cr_opt( 'org_email' );
	}
	$pub = get_page_by_path( 'yayin-ilkeleri' );
	if ( $pub ) {
		$node['publishingPrinciples'] = get_permalink( $pub );
	}
	return $node;
}

/**
 * Yazar (Person) düğümü.
 *
 * @param int $user_id Kullanıcı.
 * @return array
 */
function cr_schema_person( $user_id ) {
	$url  = get_author_posts_url( $user_id );
	$node = array(
		'@type' => 'Person',
		'@id'   => $url . '#person',
		'name'  => get_the_author_meta( 'display_name', $user_id ),
		'url'   => $url,
	);
	$bio = get_the_author_meta( 'description', $user_id );
	if ( $bio ) {
		$node['description'] = wp_strip_all_tags( $bio );
	}
	$job = get_user_meta( $user_id, 'cr_job', true );
	if ( $job ) {
		$node['jobTitle'] = $job;
	}
	$cred = get_user_meta( $user_id, 'cr_credentials', true );
	if ( $cred ) {
		$node['hasCredential'] = array(
			'@type' => 'EducationalOccupationalCredential',
			'name'  => $cred,
		);
	}
	$same = cr_lines( get_user_meta( $user_id, 'cr_sameas', true ) );
	if ( $same ) {
		$node['sameAs'] = $same;
	}
	$exp = cr_lines( get_user_meta( $user_id, 'cr_expertise', true ) );
	if ( $exp ) {
		$node['knowsAbout'] = $exp;
	}
	$avatar = get_avatar_url( $user_id, array( 'size' => 256 ) );
	if ( $avatar ) {
		$node['image'] = $avatar;
	}
	$node['worksFor'] = array( '@id' => home_url( '/' ) . '#organization' );
	return $node;
}

/**
 * Breadcrumb düğümü.
 *
 * @param string $page_url Sayfa adresi.
 * @return array|null
 */
function cr_schema_breadcrumb( $page_url ) {
	$items = cr_breadcrumbs();
	if ( count( $items ) < 2 ) {
		return null;
	}
	$list = array();
	foreach ( $items as $i => $it ) {
		$el = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $it['name'],
		);
		if ( $it['url'] ) {
			$el['item'] = $it['url'];
		}
		$list[] = $el;
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $page_url . '#breadcrumb',
		'itemListElement' => $list,
	);
}

/**
 * SSS düğümü.
 *
 * @param array  $items    Sorular.
 * @param string $page_url Sayfa.
 * @return array
 */
function cr_schema_faq( $items, $page_url ) {
	$qs = array();
	foreach ( $items as $it ) {
		$qs[] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $it['q'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $it['a'] ),
			),
		);
	}
	return array(
		'@type'      => 'FAQPage',
		'@id'        => $page_url . '#faq',
		'mainEntity' => $qs,
	);
}

/**
 * Yapılandırılmış veriyi üretir.
 *
 * @return array
 */
function cr_schema_graph() {
	$full  = cr_seo_active();
	$home  = home_url( '/' );
	$graph = array();

	if ( $full ) {
		$graph[] = cr_schema_org();
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => $home . '#website',
			'url'             => $home,
			'name'            => get_bloginfo( 'name' ),
			'description'     => get_bloginfo( 'description' ),
			'inLanguage'      => 'tr-TR',
			'publisher'       => array( '@id' => $home . '#organization' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => $home . '?s={search_term_string}',
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	$page_url = $full ? cr_canonical() : '';
	if ( ! $page_url ) {
		$page_url = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );
	}

	/* --- Ana sayfa --- */
	if ( is_front_page() ) {
		if ( $full ) {
			$graph[] = array(
				'@type'      => 'WebPage',
				'@id'        => $home . '#webpage',
				'url'        => $home,
				'name'       => wp_get_document_title(),
				'description'=> cr_seo_description(),
				'isPartOf'   => array( '@id' => $home . '#website' ),
				'about'      => array( '@id' => $home . '#organization' ),
				'inLanguage' => 'tr-TR',
			);
		}
		$faq = array();
		foreach ( (array) cr_opt( 'faq_items' ) as $it ) {
			if ( ! empty( $it['q'] ) && ! empty( $it['a'] ) ) {
				$faq[] = $it;
			}
		}
		$on = false;
		foreach ( cr_sections() as $s ) {
			if ( 'faq' === $s['id'] && $s['on'] ) {
				$on = true;
			}
		}
		if ( $faq && $on ) {
			$graph[] = cr_schema_faq( $faq, $home );
		}
		return $graph;
	}

	/* --- Tekil içerik --- */
	if ( is_singular() ) {
		$id      = get_queried_object_id();
		$pt      = get_post_type( $id );
		$author  = (int) get_post_field( 'post_author', $id );
		$img     = cr_post_image_url( $id, 'large' );
		$desc    = cr_seo_description();
		$crumb   = cr_schema_breadcrumb( $page_url );
		$is_art  = in_array( $pt, array( 'post', 'icerik', 'urun_rehberi' ), true );
		$medical = $is_art && cr_opt( 'seo_medical' );

		if ( $full ) {
			$wp = array(
				'@type'         => $medical ? array( 'WebPage', 'MedicalWebPage' ) : 'WebPage',
				'@id'           => $page_url . '#webpage',
				'url'           => $page_url,
				'name'          => wp_get_document_title(),
				'description'   => $desc,
				'isPartOf'      => array( '@id' => $home . '#website' ),
				'inLanguage'    => 'tr-TR',
				'datePublished' => get_post_time( 'c', true, $id ),
				'dateModified'  => mysql2date( 'c', cr_updated( $id ) ),
			);
			if ( $crumb ) {
				$wp['breadcrumb'] = array( '@id' => $page_url . '#breadcrumb' );
			}
			if ( $img ) {
				$wp['primaryImageOfPage'] = array(
					'@type' => 'ImageObject',
					'url'   => $img,
				);
			}
			if ( $is_art ) {
				$wp['speakable'] = array(
					'@type'       => 'SpeakableSpecification',
					'cssSelector' => array( '.cr-article__title', '.cr-short-answer__text', '.cr-takeaways' ),
				);
			}
			$reviewer = cr_meta( '_cr_reviewer', $id );
			if ( ! $reviewer && $is_art ) {
				$reviewer = cr_opt( 'art_reviewer_default' );
			}
			if ( $medical && $reviewer ) {
				$rv = array(
					'@type' => 'Person',
					'name'  => $reviewer,
				);
				if ( cr_meta( '_cr_reviewer_url', $id ) ) {
					$rv['url'] = cr_meta( '_cr_reviewer_url', $id );
				}
				$wp['reviewedBy'] = $rv;
				$rd               = cr_meta( '_cr_reviewed_date', $id );
				$wp['lastReviewed'] = $rd ? $rd : mysql2date( 'Y-m-d', cr_updated( $id ) );
			}
			if ( 'page' === $pt ) {
				$tpl = get_page_template_slug( $id );
				if ( false !== strpos( (string) $tpl, 'iletisim' ) || 'iletisim' === get_post_field( 'post_name', $id ) ) {
					$wp['@type'] = 'ContactPage';
				} elseif ( 'hakkimizda' === get_post_field( 'post_name', $id ) ) {
					$wp['@type'] = 'AboutPage';
				}
			}
			$graph[] = $wp;
			if ( $crumb ) {
				$graph[] = $crumb;
			}
		}

		if ( $full && $is_art ) {
			$art = array(
				'@type'            => 'post' === $pt ? 'BlogPosting' : 'Article',
				'@id'              => $page_url . '#article',
				'headline'         => wp_strip_all_tags( get_the_title( $id ) ),
				'description'      => $desc,
				'mainEntityOfPage' => array( '@id' => $page_url . '#webpage' ),
				'datePublished'    => get_post_time( 'c', true, $id ),
				'dateModified'     => mysql2date( 'c', cr_updated( $id ) ),
				'author'           => array( '@id' => get_author_posts_url( $author ) . '#person' ),
				'publisher'        => array( '@id' => $home . '#organization' ),
				'inLanguage'       => 'tr-TR',
				'wordCount'        => cr_word_count( get_post_field( 'post_content', $id ) ),
				'timeRequired'     => 'PT' . cr_reading_time( $id ) . 'M',
			);
			if ( $img ) {
				$art['image'] = array( $img );
			}
			$term = cr_primary_term( $id );
			if ( $term ) {
				$art['articleSection'] = $term->name;
			}
			$kw = array();
			if ( cr_meta( '_cr_focus_kw', $id ) ) {
				$kw[] = cr_meta( '_cr_focus_kw', $id );
			}
			$tags = get_the_tags( $id );
			if ( $tags ) {
				foreach ( $tags as $t ) {
					$kw[] = $t->name;
				}
			}
			if ( $kw ) {
				$art['keywords'] = implode( ', ', array_unique( $kw ) );
			}
			$about = get_the_terms( $id, 'cilt_sorunu' );
			if ( $about && ! is_wp_error( $about ) ) {
				$art['about'] = array();
				foreach ( $about as $a ) {
					$art['about'][] = array(
						'@type' => 'Thing',
						'name'  => $a->name,
						'url'   => get_term_link( $a ),
					);
				}
			}
			$sources = cr_sources( $id );
			if ( $sources ) {
				$art['citation'] = array();
				foreach ( $sources as $s ) {
					$c = array(
						'@type' => 'CreativeWork',
						'name'  => $s['title'],
					);
					if ( $s['url'] ) {
						$c['url'] = $s['url'];
					}
					$art['citation'][] = $c;
				}
			}
			$graph[] = $art;
			$graph[] = cr_schema_person( $author );
		}

		// İçerik sözlüğü: DefinedTerm.
		if ( 'icerik' === $pt ) {
			$term_node = array(
				'@type'            => 'DefinedTerm',
				'@id'              => $page_url . '#term',
				'name'             => get_the_title( $id ),
				'description'      => cr_meta( '_cr_function', $id ) ? cr_meta( '_cr_function', $id ) : $desc,
				'url'              => $page_url,
				'inDefinedTermSet' => array(
					'@type' => 'DefinedTermSet',
					'@id'   => get_post_type_archive_link( 'icerik' ) . '#set',
					'name'  => cr_opt( 'glossary_archive_title' ),
					'url'   => get_post_type_archive_link( 'icerik' ),
				),
			);
			$inci = cr_meta( '_cr_inci', $id );
			if ( $inci ) {
				$term_node['termCode'] = $inci;
			}
			$aka = array_filter( array_map( 'trim', explode( ',', (string) cr_meta( '_cr_aka', $id ) ) ) );
			if ( $inci ) {
				array_unshift( $aka, $inci );
			}
			if ( $aka ) {
				$term_node['alternateName'] = array_values( array_unique( $aka ) );
			}
			$graph[] = $term_node;
		}

		// Ürün rehberi: Product (+ editör incelemesi).
		if ( 'urun_rehberi' === $pt ) {
			$prod = array(
				'@type'       => 'Product',
				'@id'         => $page_url . '#product',
				'name'        => get_the_title( $id ),
				'description' => $desc,
			);
			if ( $img ) {
				$prod['image'] = $img;
			}
			if ( cr_meta( '_cr_brand', $id ) ) {
				$prod['brand'] = array(
					'@type' => 'Brand',
					'name'  => cr_meta( '_cr_brand', $id ),
				);
			}
			$rating = cr_meta( '_cr_rating', $id );
			if ( $rating ) {
				$prod['review'] = array(
					'@type'        => 'Review',
					'author'       => array(
						'@type' => 'Person',
						'name'  => get_the_author_meta( 'display_name', $author ),
					),
					'datePublished' => get_post_time( 'Y-m-d', true, $id ),
					'reviewRating' => array(
						'@type'       => 'Rating',
						'ratingValue' => $rating,
						'bestRating'  => '5',
						'worstRating' => '1',
					),
					'reviewBody'   => wp_strip_all_tags( (string) cr_meta( '_cr_verdict', $id ) ),
				);
				$pros = cr_lines( cr_meta( '_cr_pros', $id ) );
				$cons = cr_lines( cr_meta( '_cr_cons', $id ) );
				if ( $pros ) {
					$prod['review']['positiveNotes'] = array(
						'@type'           => 'ItemList',
						'itemListElement' => array_map(
							function ( $p, $i ) {
								return array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $p );
							},
							$pros,
							array_keys( $pros )
						),
					);
				}
				if ( $cons ) {
					$prod['review']['negativeNotes'] = array(
						'@type'           => 'ItemList',
						'itemListElement' => array_map(
							function ( $p, $i ) {
								return array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $p );
							},
							$cons,
							array_keys( $cons )
						),
					);
				}
			}
			$graph[] = $prod;
		}

		$faq = cr_faq_items( $id );
		if ( $faq ) {
			$graph[] = cr_schema_faq( $faq, $page_url );
		}

		$steps = cr_steps( $id );
		if ( $steps ) {
			$how = array(
				'@type' => 'HowTo',
				'@id'   => $page_url . '#howto',
				'name'  => cr_meta( '_cr_steps_title', $id ) ? cr_meta( '_cr_steps_title', $id ) : get_the_title( $id ),
				'step'  => array(),
			);
			if ( $img ) {
				$how['image'] = $img;
			}
			$mins = (int) cr_meta( '_cr_steps_time', $id );
			if ( $mins ) {
				$how['totalTime'] = 'PT' . $mins . 'M';
			}
			foreach ( $steps as $i => $s ) {
				$how['step'][] = array(
					'@type'    => 'HowToStep',
					'position' => $i + 1,
					'name'     => $s['name'],
					'text'     => $s['text'] ? $s['text'] : $s['name'],
					'url'      => $page_url . '#adim-' . ( $i + 1 ),
				);
			}
			$graph[] = $how;
		}
		return $graph;
	}

	/* --- Arşivler --- */
	if ( $full && ( is_home() || is_archive() || is_search() ) ) {
		global $wp_query;
		$type = is_author() ? 'ProfilePage' : ( is_search() ? 'SearchResultsPage' : 'CollectionPage' );
		$node = array(
			'@type'      => $type,
			'@id'        => $page_url . '#webpage',
			'url'        => $page_url,
			'name'       => wp_get_document_title(),
			'description'=> cr_seo_description(),
			'isPartOf'   => array( '@id' => $home . '#website' ),
			'inLanguage' => 'tr-TR',
		);
		$crumb = cr_schema_breadcrumb( $page_url );
		if ( $crumb ) {
			$node['breadcrumb'] = array( '@id' => $page_url . '#breadcrumb' );
		}
		if ( ! empty( $wp_query->posts ) ) {
			$list = array();
			foreach ( array_slice( $wp_query->posts, 0, 30 ) as $i => $p ) {
				$list[] = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'url'      => get_permalink( $p ),
					'name'     => wp_strip_all_tags( get_the_title( $p ) ),
				);
			}
			$node['mainEntity'] = array(
				'@type'           => 'ItemList',
				'itemListElement' => $list,
			);
		}
		if ( is_author() ) {
			$node['mainEntity'] = cr_schema_person( get_queried_object_id() );
		}
		if ( is_post_type_archive( 'icerik' ) ) {
			$graph[] = array(
				'@type' => 'DefinedTermSet',
				'@id'   => get_post_type_archive_link( 'icerik' ) . '#set',
				'name'  => cr_opt( 'glossary_archive_title' ),
				'url'   => get_post_type_archive_link( 'icerik' ),
			);
		}
		$graph[] = $node;
		if ( $crumb ) {
			$graph[] = $crumb;
		}
	}
	return $graph;
}

/**
 * JSON-LD çıktısı.
 */
function cr_schema_output() {
	if ( ! cr_opt( 'seo_enable' ) ) {
		return;
	}
	$graph = cr_schema_graph();
	if ( ! $graph ) {
		return;
	}
	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( array_filter( $graph ) ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'cr_schema_output', 5 );
