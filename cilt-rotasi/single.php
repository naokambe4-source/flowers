<?php
/**
 * Makale sayfası (yazılar ve ürün rehberi için ortak iskelet).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pid      = get_the_ID();
	$content  = apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	$toc      = cr_opt( 'art_toc' ) ? cr_toc_from_html( $content ) : array();
	$reviewer = cr_meta( '_cr_reviewer' ) ? cr_meta( '_cr_reviewer' ) : cr_opt( 'art_reviewer_default' );
	$updated  = cr_updated( $pid );
	$short    = cr_meta( '_cr_short_answer' );
	$take     = cr_lines( cr_meta( '_cr_takeaways' ) );
	$is_prod  = 'urun_rehberi' === get_post_type();
	?>
	<article <?php post_class( 'cr-article' ); ?>>
		<header class="cr-article__head">
			<div class="cr-container cr-container--narrow">
				<?php cr_breadcrumb_html(); ?>
				<div class="cr-article__cat"><?php echo cr_term_badge( $pid, 'cr-badge--solid' ); // phpcs:ignore ?></div>
				<h1 class="cr-article__title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="cr-article__intro"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<div class="cr-article__meta">
					<span class="cr-byline">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 40, '', '', array( 'class' => 'cr-byline__avatar' ) ); ?>
						<span>
							<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author"><?php the_author(); ?></a>
							<?php if ( $reviewer ) : ?>
								<small><?php echo cr_icon( 'shield', 14 ); // phpcs:ignore ?> Uzman kontrolü: <?php echo esc_html( $reviewer ); ?></small>
							<?php endif; ?>
						</span>
					</span>
					<span class="cr-article__facts">
						<span><?php echo cr_icon( 'clock', 16 ); // phpcs:ignore ?><?php echo esc_html( cr_reading_time( $pid ) ); ?> dk okuma</span>
						<span><?php echo cr_icon( 'refresh', 16 ); // phpcs:ignore ?>Güncellendi: <time datetime="<?php echo esc_attr( mysql2date( 'c', $updated ) ); ?>"><?php echo esc_html( cr_date( $updated ) ); ?></time></span>
					</span>
				</div>
			</div>
		</header>

		<?php if ( has_post_thumbnail() || cr_meta( '_cr_ext_image' ) ) : ?>
			<figure class="cr-article__hero cr-container">
				<?php
				echo cr_post_image( // phpcs:ignore
					$pid,
					'cr-hero',
					array(
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'sizes'         => '(max-width: 1360px) 100vw, 1360px',
						'alt'           => get_the_title(),
					)
				);
				$cap = has_post_thumbnail() ? get_the_post_thumbnail_caption() : '';
				if ( $cap ) :
					?>
					<figcaption><?php echo esc_html( $cap ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="cr-container cr-article__layout<?php echo $toc ? ' has-toc' : ''; ?>">
			<div class="cr-article__side">
				<?php if ( cr_opt( 'art_share' ) ) : ?>
					<?php get_template_part( 'template-parts/parts/share', null, array( 'vertical' => true ) ); ?>
				<?php endif; ?>
			</div>

			<div class="cr-article__main">
				<?php if ( $short ) : ?>
					<div class="cr-short-answer">
						<span class="cr-short-answer__label"><?php echo cr_icon( 'sparkle', 16 ); // phpcs:ignore ?><?php cr_t( 'art_short_label' ); ?></span>
						<p class="cr-short-answer__text"><?php echo esc_html( $short ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( $toc ) : ?>
					<details class="cr-toc-mobile">
						<summary><?php echo cr_icon( 'list', 18 ); // phpcs:ignore ?> İçindekiler <span class="cr-toc-mobile__count"><?php echo count( $toc ); ?> başlık</span></summary>
						<?php get_template_part( 'template-parts/parts/toc', null, array( 'toc' => $toc ) ); ?>
					</details>
				<?php endif; ?>

				<?php if ( $is_prod ) : ?>
					<?php get_template_part( 'template-parts/parts/product-card' ); ?>
				<?php endif; ?>

				<?php if ( $take ) : ?>
					<div class="cr-takeaways">
						<p class="cr-takeaways__title"><?php echo cr_icon( 'bulb', 18 ); // phpcs:ignore ?><?php cr_t( 'art_takeaways_label' ); ?></p>
						<ul>
							<?php foreach ( $take as $t ) : ?>
								<li><?php echo esc_html( $t ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<div class="cr-prose entry-content">
					<?php
					echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content çıktısı.
					wp_link_pages(
						array(
							'before' => '<nav class="cr-pages">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>

				<?php get_template_part( 'template-parts/parts/article-extras' ); ?>

				<?php
				$tags = get_the_tags();
				if ( $tags ) :
					?>
					<div class="cr-tags">
						<?php foreach ( $tags as $t ) : ?>
							<a class="cr-chip" href="<?php echo esc_url( get_tag_link( $t ) ); ?>">#<?php echo esc_html( $t->name ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( cr_opt( 'art_share' ) ) : ?>
					<div class="cr-article__actions">
						<p>Bu rehber işine yaradı mı?</p>
						<?php get_template_part( 'template-parts/parts/share' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( cr_opt( 'art_author' ) ) : ?>
					<?php get_template_part( 'template-parts/parts/author-box' ); ?>
				<?php endif; ?>
			</div>

			<?php if ( $toc ) : ?>
				<aside class="cr-article__toc" aria-label="İçindekiler">
					<div class="cr-toc-sticky">
						<p class="cr-toc__title"><?php echo cr_icon( 'list', 18 ); // phpcs:ignore ?> Bu rehberde</p>
						<?php get_template_part( 'template-parts/parts/toc', null, array( 'toc' => $toc ) ); ?>
						<a class="cr-toc__quiz" href="<?php echo esc_url( cr_url( cr_opt( 'quiz_url' ) ) ); ?>">
							<?php echo cr_icon( 'target', 20 ); // phpcs:ignore ?>
							<span><strong>Cildini tanı</strong><small><?php cr_t( 'quiz_note' ); ?>lık test</small></span>
						</a>
					</div>
				</aside>
			<?php endif; ?>
		</div>

		<?php get_template_part( 'template-parts/parts/after-article' ); ?>

		<?php if ( cr_opt( 'art_comments' ) && ( comments_open() || get_comments_number() ) ) : ?>
			<div class="cr-container cr-container--narrow cr-comments-wrap"><?php comments_template(); ?></div>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
