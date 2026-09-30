<?php
/**
 * İçerik sözlüğü tekil sayfası (DefinedTerm).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pid      = get_the_ID();
	$inci     = cr_meta( '_cr_inci' );
	$aka      = cr_meta( '_cr_aka' );
	$fn       = cr_meta( '_cr_function' );
	$benefits = cr_lines( cr_meta( '_cr_benefits' ) );
	$pairs    = cr_lines( cr_meta( '_cr_pairs' ) );
	$avoid    = cr_lines( cr_meta( '_cr_avoid' ) );
	$when_map = array( 'sabah' => 'Sabah', 'aksam' => 'Akşam', 'ikisi' => 'Sabah ve akşam' );
	$preg_map = array( 'uygun' => 'Genel olarak uygun', 'danis' => 'Hekime danışın', 'kacin' => 'Önerilmez' );
	$lvl      = array( '1' => 'Sınırlı', '2' => 'Orta', '3' => 'Güçlü' );
	$irr      = array( '1' => 'Düşük', '2' => 'Orta', '3' => 'Yüksek' );
	$content  = apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	$short    = cr_meta( '_cr_short_answer' ) ? cr_meta( '_cr_short_answer' ) : $fn;
	$group    = cr_primary_term( $pid );
	?>
	<article <?php post_class( 'cr-ing' ); ?>>
		<header class="cr-ing__hero">
			<div class="cr-container">
				<?php cr_breadcrumb_html(); ?>
				<div class="cr-ing__hero-grid">
					<div>
						<p class="cr-eyebrow"><?php echo cr_icon( 'flask', 14 ); // phpcs:ignore ?> <?php echo esc_html( $group ? $group->name : 'İçerik sözlüğü' ); ?></p>
						<h1 class="cr-article__title cr-ing__title"><?php the_title(); ?></h1>
						<?php if ( $inci || $aka ) : ?>
							<p class="cr-ing__inci">
								<?php if ( $inci ) : ?>
									<span><small>INCI</small><?php echo esc_html( $inci ); ?></span>
								<?php endif; ?>
								<?php if ( $aka ) : ?>
									<span><small>Diğer adları</small><?php echo esc_html( $aka ); ?></span>
								<?php endif; ?>
							</p>
						<?php endif; ?>
						<?php if ( $short ) : ?>
							<div class="cr-short-answer cr-short-answer--plain">
								<span class="cr-short-answer__label"><?php echo cr_icon( 'sparkle', 16 ); // phpcs:ignore ?>Ne işe yarar?</span>
								<p class="cr-short-answer__text"><?php echo esc_html( $short ); ?></p>
							</div>
						<?php endif; ?>
						<div class="cr-ing__actions">
							<?php echo cr_save_button( $pid, 'cr-share__btn', true ); // phpcs:ignore ?>
							<button type="button" class="cr-share__btn" data-share data-title="<?php echo esc_attr( get_the_title() ); ?>" data-url="<?php echo esc_url( get_permalink() ); ?>"><?php echo cr_icon( 'share', 18 ); // phpcs:ignore ?><span>Paylaş</span></button>
						</div>
					</div>
					<dl class="cr-ing__facts">
						<?php if ( cr_meta( '_cr_skin_types' ) ) : ?>
							<div class="cr-ing__fact cr-ing__fact--wide"><dt><?php echo cr_icon( 'face', 18 ); // phpcs:ignore ?>Uygun cilt tipleri</dt><dd><?php echo esc_html( cr_meta( '_cr_skin_types' ) ); ?></dd></div>
						<?php endif; ?>
						<?php if ( cr_meta( '_cr_conc' ) ) : ?>
							<div class="cr-ing__fact"><dt><?php echo cr_icon( 'drop', 18 ); // phpcs:ignore ?>Etkili oran</dt><dd><?php echo esc_html( cr_meta( '_cr_conc' ) ); ?></dd></div>
						<?php endif; ?>
						<?php if ( isset( $when_map[ cr_meta( '_cr_when' ) ] ) ) : ?>
							<div class="cr-ing__fact"><dt><?php echo cr_icon( 'aksam' === cr_meta( '_cr_when' ) ? 'moon' : 'sun', 18 ); // phpcs:ignore ?>Ne zaman?</dt><dd><?php echo esc_html( $when_map[ cr_meta( '_cr_when' ) ] ); ?></dd></div>
						<?php endif; ?>
						<?php if ( isset( $preg_map[ cr_meta( '_cr_pregnancy' ) ] ) ) : ?>
							<div class="cr-ing__fact"><dt><?php echo cr_icon( 'heart', 18 ); // phpcs:ignore ?>Hamilelik</dt><dd><?php echo esc_html( $preg_map[ cr_meta( '_cr_pregnancy' ) ] ); ?></dd></div>
						<?php endif; ?>
						<?php if ( isset( $lvl[ cr_meta( '_cr_evidence' ) ] ) ) : ?>
							<div class="cr-ing__fact"><dt><?php echo cr_icon( 'chart', 18 ); // phpcs:ignore ?>Kanıt düzeyi</dt><dd><span class="cr-meter" data-level="<?php echo esc_attr( cr_meta( '_cr_evidence' ) ); ?>"><i></i><i></i><i></i></span><?php echo esc_html( $lvl[ cr_meta( '_cr_evidence' ) ] ); ?></dd></div>
						<?php endif; ?>
						<?php if ( isset( $irr[ cr_meta( '_cr_irritation' ) ] ) ) : ?>
							<div class="cr-ing__fact"><dt><?php echo cr_icon( 'alert', 18 ); // phpcs:ignore ?>Tahriş potansiyeli</dt><dd><span class="cr-meter cr-meter--warn" data-level="<?php echo esc_attr( cr_meta( '_cr_irritation' ) ); ?>"><i></i><i></i><i></i></span><?php echo esc_html( $irr[ cr_meta( '_cr_irritation' ) ] ); ?></dd></div>
						<?php endif; ?>
					</dl>
				</div>
			</div>
		</header>

		<div class="cr-container cr-container--narrow cr-ing__body">
			<?php if ( $benefits ) : ?>
				<div class="cr-takeaways">
					<p class="cr-takeaways__title"><?php echo cr_icon( 'check', 18 ); // phpcs:ignore ?>Faydaları</p>
					<ul><?php foreach ( $benefits as $b ) : ?><li><?php echo esc_html( $b ); ?></li><?php endforeach; ?></ul>
				</div>
			<?php endif; ?>

			<?php if ( $pairs || $avoid ) : ?>
				<div class="cr-combos">
					<?php if ( $pairs ) : ?>
						<div class="cr-combos__col cr-combos__col--good">
							<p class="cr-combos__title"><?php echo cr_icon( 'check', 18 ); // phpcs:ignore ?> İyi anlaşır</p>
							<div class="cr-chips">
								<?php foreach ( $pairs as $p ) : ?>
									<?php $l = cr_ingredient_link( $p ); ?>
									<?php echo $l ? '<a class="cr-chip cr-chip--soft" href="' . esc_url( $l ) . '">' . esc_html( $p ) . '</a>' : '<span class="cr-chip">' . esc_html( $p ) . '</span>'; ?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( $avoid ) : ?>
						<div class="cr-combos__col cr-combos__col--warn">
							<p class="cr-combos__title"><?php echo cr_icon( 'alert', 18 ); // phpcs:ignore ?> Dikkatli kombinle</p>
							<div class="cr-chips">
								<?php foreach ( $avoid as $p ) : ?>
									<?php $l = cr_ingredient_link( $p ); ?>
									<?php echo $l ? '<a class="cr-chip" href="' . esc_url( $l ) . '">' . esc_html( $p ) . '</a>' : '<span class="cr-chip">' . esc_html( $p ) . '</span>'; ?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="cr-prose entry-content">
				<?php echo $content; // phpcs:ignore ?>
			</div>

			<?php get_template_part( 'template-parts/parts/article-extras' ); ?>

			<p class="cr-updated"><?php echo cr_icon( 'refresh', 16 ); // phpcs:ignore ?> Son güncelleme: <time datetime="<?php echo esc_attr( mysql2date( 'c', cr_updated( $pid ) ) ); ?>"><?php echo esc_html( cr_date( cr_updated( $pid ) ) ); ?></time></p>
		</div>

		<?php
		// Bu içerikten bahseden rehberler.
		$mentions = new WP_Query(
			array(
				'post_type'      => 'post',
				's'              => get_the_title(),
				'posts_per_page' => 3,
				'no_found_rows'  => true,
			)
		);
		$siblings = get_posts(
			array(
				'post_type'      => 'icerik',
				'posts_per_page' => 8,
				'post__not_in'   => array( $pid ),
				'orderby'        => 'rand',
				'no_found_rows'  => true,
				'tax_query'      => $group ? array( array( 'taxonomy' => 'icerik_grubu', 'terms' => array( $group->term_id ) ) ) : array(), // phpcs:ignore
			)
		);
		?>
		<div class="cr-after">
			<div class="cr-container">
				<?php if ( $mentions->have_posts() ) : ?>
					<div class="cr-related">
						<h2 class="cr-h3"><?php echo esc_html( get_the_title() ); ?> geçen rehberler</h2>
						<div class="cr-related__grid">
							<?php
							while ( $mentions->have_posts() ) {
								$mentions->the_post();
								get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'plain' ) );
							}
							wp_reset_postdata();
							?>
						</div>
					</div>
				<?php endif; ?>
				<?php if ( $siblings ) : ?>
					<div class="cr-related">
						<h2 class="cr-h3">Benzer içerikler</h2>
						<div class="cr-ing-grid cr-ing-grid--compact">
							<?php foreach ( $siblings as $s ) : ?>
								<a class="cr-ing-card" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
									<span class="cr-ing-card__name"><?php echo esc_html( get_the_title( $s ) ); ?></span>
									<span class="cr-ing-card__fn"><?php echo esc_html( get_post_meta( $s->ID, '_cr_function', true ) ); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
