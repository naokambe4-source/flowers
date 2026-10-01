<?php
/**
 * Ürün rehberi kataloğu (ürün türü arşivleri de bu şablonu kullanır).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();
global $wp_query;
$cr_term  = is_tax( 'urun_turu' ) ? get_queried_object() : null;
$cr_types = get_terms( array( 'taxonomy' => 'urun_turu', 'hide_empty' => false, 'parent' => 0 ) );
$cr_sort  = isset( $_GET['siralama'] ) ? sanitize_key( wp_unslash( $_GET['siralama'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$cr_base  = $cr_term ? get_term_link( $cr_term ) : get_post_type_archive_link( 'urun_rehberi' );
$cr_sorts = array(
	''      => 'En yeni',
	'puan'  => 'Editör puanı',
	'ad'    => 'A–Z',
);
?>
<section class="cr-pagehead cr-pagehead--catalog">
	<div class="cr-container">
		<?php cr_breadcrumb_html(); ?>
		<div class="cr-pagehead__row">
			<div>
				<p class="cr-eyebrow cr-eyebrow--line"><?php echo $cr_term ? 'Ürün türü' : 'Katalog'; ?></p>
				<h1 class="cr-pagehead__title">
					<?php if ( $cr_term ) : ?>
						<?php echo esc_html( $cr_term->name ); ?>
					<?php else : ?>
						Cilt Bakım Rafına<br><em class="cr-accent">Daha Bilinçli Bak.</em>
					<?php endif; ?>
				</h1>
			</div>
			<div class="cr-pagehead__side">
				<p class="cr-pagehead__lead"><?php echo esc_html( $cr_term && $cr_term->description ? $cr_term->description : cr_opt( 'products_archive_text' ) ); ?></p>
				<p class="cr-note"><?php echo cr_icon( 'shield', 16 ); // phpcs:ignore ?> Sponsorlu sıralama yok · Bağımsız editör değerlendirmesi</p>
			</div>
		</div>
		<?php if ( $cr_types && ! is_wp_error( $cr_types ) ) : ?>
			<nav class="cr-tabs-line cr-tabs-line--page" aria-label="Ürün türleri">
				<a class="<?php echo $cr_term ? '' : 'is-active'; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'urun_rehberi' ) ); ?>"<?php echo $cr_term ? '' : ' aria-current="page"'; ?>>Tümü</a>
				<?php foreach ( $cr_types as $t ) : ?>
					<?php $on = $cr_term && $cr_term->term_id === $t->term_id; ?>
					<a class="<?php echo $on ? 'is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $t ) ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $t->name ); ?><sup><?php echo (int) $t->count; ?></sup></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
</section>

<section class="cr-section cr-section--tight cr-catalog cr-catalog--page">
	<div class="cr-container">
		<div class="cr-toolbar-line">
			<p><strong><?php echo (int) $wp_query->found_posts; ?></strong> inceleme</p>
			<div class="cr-toolbar-line__sort" role="group" aria-label="Sıralama">
				<span>Sırala:</span>
				<?php foreach ( $cr_sorts as $k => $label ) : ?>
					<a class="<?php echo $k === $cr_sort ? 'is-active' : ''; ?>" href="<?php echo esc_url( $k ? add_query_arg( 'siralama', $k, $cr_base ) : $cr_base ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( have_posts() ) : ?>
			<div class="cr-catalog__grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/cards/product', null, array( 'post_id' => get_the_ID() ) );
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => cr_icon( 'arrow-left', 18 ) . '<span class="screen-reader-text">Önceki</span>',
					'next_text' => '<span class="screen-reader-text">Sonraki</span>' . cr_icon( 'arrow-right', 18 ),
					'class'     => 'cr-pagination',
				)
			);
			?>
		<?php else : ?>
			<div class="cr-empty-state">
				<?php echo cr_icon( 'drop', 40 ); // phpcs:ignore ?>
				<p>Bu türde henüz inceleme yok. Yakında burada olacak.</p>
				<a class="cr-btn-line" href="<?php echo esc_url( get_post_type_archive_link( 'urun_rehberi' ) ); ?>">Tüm ürünler</a>
			</div>
		<?php endif; ?>
	</div>
</section>

<section class="cr-section cr-method">
	<div class="cr-container">
		<header class="cr-head cr-head--center">
			<p class="cr-eyebrow">Yöntem</p>
			<h2 class="cr-h2">Bir ürünü nasıl değerlendiriyoruz?</h2>
		</header>
		<ol class="cr-method__grid">
			<li><span class="cr-method__num">01</span><h3>İçerik listesi</h3><p>INCI listesindeki aktiflerin oranını, sırasını ve kanıt düzeyini sözlüğümüzle karşılaştırırız.</p></li>
			<li><span class="cr-method__num">02</span><h3>Doku &amp; kullanım</h3><p>Hangi cilt tipine uyduğunu, rutinde nereye oturduğunu ve olası tahriş risklerini not ederiz.</p></li>
			<li><span class="cr-method__num">03</span><h3>Bağımsızlık</h3><p>Sponsorlu sıralama yapmayız; iş birliği varsa açıkça belirtir, değerlendirmeyi etkilemesine izin vermeyiz.</p></li>
		</ol>
	</div>
</section>
<?php
get_footer();
