<?php
/**
 * Ana sayfa — Instagram · Blog · Sosyal medya şeridi (footer üstü üç kart).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$links = df_social_links();
$blog  = absint( get_option( 'page_for_posts' ) ) ? get_permalink( absint( get_option( 'page_for_posts' ) ) ) : '';
if ( ! $links && ! $blog ) {
	return;
}
$bg = sanitize_hex_color( df_opt( 'soc_bg', '#F6E7E4' ) );
?>
<section data-df-sec="social" class="df-sstrip" aria-label="Sosyal medya ve blog"<?php echo $bg ? ' style="--soc-bg:' . esc_attr( $bg ) . '"' : ''; ?>>
	<div class="df-container df-sstrip__grid">
		<?php if ( ! empty( $links['instagram'] ) ) : ?>
			<a class="df-sstrip__card" href="<?php echo esc_url( $links['instagram'] ); ?>" target="_blank" rel="noopener">
				<span class="df-sstrip__icon df-sstrip__icon--ig"><?php df_the_icon( 'instagram', array( 'size' => 26 ) ); ?></span>
				<span class="df-sstrip__body">
					<strong<?php echo df_e( 'soc_ig_title' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'soc_ig_title', 'Instagram' ) ); ?></strong>
					<span<?php echo df_e( 'soc_ig_text' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'soc_ig_text' ) ); ?></span>
				</span>
				<?php df_the_icon( 'arrow-right', array( 'size' => 18, 'class' => 'df-sstrip__arrow' ) ); ?>
			</a>
		<?php endif; ?>
		<?php if ( $blog ) : ?>
			<a class="df-sstrip__card" href="<?php echo esc_url( $blog ); ?>">
				<span class="df-sstrip__icon df-sstrip__icon--blog"><?php df_the_icon( 'note', array( 'size' => 26 ) ); ?></span>
				<span class="df-sstrip__body">
					<strong<?php echo df_e( 'soc_blog_title' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'soc_blog_title', 'Blog' ) ); ?></strong>
					<span<?php echo df_e( 'soc_blog_text' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'soc_blog_text' ) ); ?></span>
				</span>
				<?php df_the_icon( 'arrow-right', array( 'size' => 18, 'class' => 'df-sstrip__arrow' ) ); ?>
			</a>
		<?php endif; ?>
		<?php if ( $links ) : ?>
			<div class="df-sstrip__card df-sstrip__card--follow">
				<strong<?php echo df_e( 'soc_follow' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'soc_follow', 'Bizi takip edin' ) ); ?></strong>
				<ul class="df-sstrip__nets">
					<?php foreach ( $links as $net => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $net ) ); ?>"><?php df_the_icon( $net, array( 'size' => 20 ) ); ?><span><?php echo esc_html( ucfirst( $net ) ); ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>
