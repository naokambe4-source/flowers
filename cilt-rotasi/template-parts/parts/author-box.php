<?php
/**
 * Yazar kutusu (E-E-A-T).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$uid  = (int) get_the_author_meta( 'ID' );
$job  = get_user_meta( $uid, 'cr_job', true );
$cred = get_user_meta( $uid, 'cr_credentials', true );
$bio  = get_the_author_meta( 'description', $uid );
?>
<section class="cr-author" aria-label="Yazar hakkında">
	<?php echo get_avatar( $uid, 88, '', '', array( 'class' => 'cr-author__avatar' ) ); ?>
	<div class="cr-author__body">
		<p class="cr-author__label">Yazar</p>
		<p class="cr-author__name"><a href="<?php echo esc_url( get_author_posts_url( $uid ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', $uid ) ); ?></a><?php echo $job ? ' <span>· ' . esc_html( $job ) . '</span>' : ''; ?></p>
		<?php if ( $cred ) : ?>
			<p class="cr-author__cred"><?php echo cr_icon( 'shield', 14 ); // phpcs:ignore ?><?php echo esc_html( $cred ); ?></p>
		<?php endif; ?>
		<?php if ( $bio ) : ?>
			<p class="cr-author__bio"><?php echo esc_html( $bio ); ?></p>
		<?php endif; ?>
		<?php
		$pub = get_page_by_path( 'yayin-ilkeleri' );
		if ( $pub ) :
			?>
			<a class="cr-link" href="<?php echo esc_url( get_permalink( $pub ) ); ?>">Yayın ilkelerimiz <?php echo cr_icon( 'arrow-right', 14 ); // phpcs:ignore ?></a>
		<?php endif; ?>
	</div>
</section>
