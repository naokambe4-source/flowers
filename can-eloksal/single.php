<?php
/**
 * Blog yazısı detayı.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id   = get_the_ID();
	$ce_cats = get_the_category();
	$ce_meta = '<span>' . ce_icon( 'calendar', 16 ) . '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( ce_date() ) . '</time></span>'
		. '<span>' . ce_icon( 'clock', 16 ) . esc_html( ce_reading_time( $ce_id ) ) . ' dk okuma</span>'
		. '<span>' . ce_icon( 'users', 16 ) . esc_html( get_the_author() ) . '</span>';
	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'   => get_the_title(),
			'src'     => array( 'title' => 'post:' . $ce_id . ':title' ),
			'eyebrow' => $ce_cats ? $ce_cats[0]->name : 'Blog',
			'tone'    => ce_post_tone( $ce_id ),
			'meta'    => $ce_meta,
		)
	);
	?>
	<section class="ce-section ce-section--article">
		<div class="ce-container ce-article">
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="ce-article__cover">
					<?php echo ce_img( get_post_thumbnail_id(), 'ce-wide', array( 'sizes' => '(min-width: 1200px) 1100px, 100vw', 'loading' => 'eager', 'fallback_alt' => get_the_title() ) ); // phpcs:ignore ?>
					<?php if ( wp_get_attachment_caption( get_post_thumbnail_id() ) ) : ?>
						<figcaption><?php echo esc_html( wp_get_attachment_caption( get_post_thumbnail_id() ) ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="ce-article__layout">
				<article <?php post_class( 'ce-prose ce-entry ce-article__body' ); ?>>
					<?php if ( has_excerpt() ) : ?>
						<p class="ce-lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<?php
					the_content();
					wp_link_pages( array( 'before' => '<nav class="ce-pages">', 'after' => '</nav>' ) );
					?>
					<?php $ce_tags = get_the_tags(); ?>
					<?php if ( $ce_tags ) : ?>
						<ul class="ce-tags" aria-label="Etiketler">
							<?php foreach ( $ce_tags as $ce_t ) : ?>
								<li><a href="<?php echo esc_url( get_tag_link( $ce_t ) ); ?>">#<?php echo esc_html( $ce_t->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</article>

				<aside class="ce-article__aside">
					<div class="ce-aside-card ce-aside-card--sticky">
						<p class="ce-eyebrow">Paylaş</p>
						<?php
						$ce_u     = rawurlencode( get_permalink() );
						$ce_t_enc = rawurlencode( get_the_title() );
						?>
						<ul class="ce-share">
							<li><a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $ce_u ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'linkedin', 18 ); // phpcs:ignore ?><span class="screen-reader-text">LinkedIn'de paylaş</span></a></li>
							<li><a href="<?php echo esc_url( 'https://wa.me/?text=' . $ce_t_enc . '%20' . $ce_u ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'whatsapp', 18 ); // phpcs:ignore ?><span class="screen-reader-text">WhatsApp'ta paylaş</span></a></li>
							<li><a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . $ce_u ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'facebook', 18 ); // phpcs:ignore ?><span class="screen-reader-text">Facebook'ta paylaş</span></a></li>
							<li><button type="button" data-copy="<?php echo esc_url( get_permalink() ); ?>" data-copy-msg="Bağlantı kopyalandı."><?php echo ce_icon( 'copy', 18 ); // phpcs:ignore ?><span class="screen-reader-text">Bağlantıyı kopyala</span></button></li>
						</ul>
						<hr>
						<p class="ce-eyebrow">Teknik destek</p>
						<p>Parçanız için doğru yüzey işlemini birlikte belirleyelim.</p>
						<?php echo ce_button( 'Teklif Al', ce_quote_url(), 'primary', 'arrow-right' ); // phpcs:ignore ?>
					</div>
				</aside>
			</div>

			<nav class="ce-postnav" aria-label="Diğer yazılar">
				<?php
				$ce_prev = get_previous_post();
				$ce_next = get_next_post();
				if ( $ce_prev ) {
					echo '<a class="ce-postnav__link" href="' . esc_url( get_permalink( $ce_prev ) ) . '"><small>' . ce_icon( 'arrow-left', 14 ) . ' Önceki yazı</small><strong>' . esc_html( get_the_title( $ce_prev ) ) . '</strong></a>'; // phpcs:ignore
				} else {
					echo '<span></span>';
				}
				if ( $ce_next ) {
					echo '<a class="ce-postnav__link ce-postnav__link--next" href="' . esc_url( get_permalink( $ce_next ) ) . '"><small>Sonraki yazı ' . ce_icon( 'arrow-right', 14 ) . '</small><strong>' . esc_html( get_the_title( $ce_next ) ) . '</strong></a>'; // phpcs:ignore
				}
				?>
			</nav>
		</div>
	</section>

	<?php
	$ce_related = get_posts(
		array(
			'numberposts'  => 3,
			'post__not_in' => array( $ce_id ),
			'category__in' => wp_list_pluck( $ce_cats, 'term_id' ),
		)
	);
	if ( $ce_related ) :
		?>
		<section class="ce-section ce-section--tint">
			<div class="ce-container">
				<?php ce_section_head( array( 'eyebrow' => 'Teknik Bilgi Merkezi', 'title' => 'İlgili yazılar', 'align' => 'left' ) ); ?>
				<div class="ce-cards ce-cards--3">
					<?php
					foreach ( $ce_related as $ce_i => $ce_r ) {
						get_template_part( 'template-parts/components/post-card', null, array( 'post' => $ce_r, 'index' => $ce_i ) );
					}
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
