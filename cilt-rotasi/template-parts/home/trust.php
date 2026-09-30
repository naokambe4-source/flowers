<?php
/**
 * Yayın ilkeleri / güven (E-E-A-T sinyali).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$items = (array) cr_opt( 'trust_items' );
if ( ! $items ) {
	return;
}
?>
<section class="cr-section cr-trust"<?php echo cr_section_attr( 'trust' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<div class="cr-trust__grid">
			<div class="cr-trust__head cr-reveal">
				<h2 class="cr-h2"<?php echo cr_edit( 'trust_title' ); // phpcs:ignore ?>><?php cr_t( 'trust_title' ); ?></h2>
				<?php if ( cr_opt( 'trust_link_text' ) ) : ?>
					<a class="cr-link" href="<?php echo esc_url( cr_url( cr_opt( 'trust_link_url' ) ) ); ?>"><span<?php echo cr_edit( 'trust_link_text' ); // phpcs:ignore ?>><?php cr_t( 'trust_link_text' ); ?></span> <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
				<?php endif; ?>
			</div>
			<ul class="cr-trust__list" role="list">
				<?php foreach ( $items as $i => $it ) : ?>
					<li class="cr-trust__item cr-reveal">
						<span class="cr-trust__icon" aria-hidden="true"><?php echo cr_icon( $it['icon'] ? $it['icon'] : 'leaf', 24 ); // phpcs:ignore ?></span>
						<h3 class="cr-trust__title"<?php echo cr_edit( 'trust_items.' . $i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $it['title'] ); ?></h3>
						<p<?php echo cr_edit( 'trust_items.' . $i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $it['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
