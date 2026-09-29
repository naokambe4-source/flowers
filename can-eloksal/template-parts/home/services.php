<?php
/**
 * Hizmetler bölümü: editoryal ızgara (2 büyük, 3 orta, sonra küçük kartlar).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_extra = array();
if ( ! empty( $args['category'] ) ) {
	$ce_extra['tax_query'] = array( array( 'taxonomy' => 'ce_service_cat', 'field' => 'slug', 'terms' => $args['category'] ) ); // phpcs:ignore
}
$ce_services = ce_get_items( 'ce_service', (int) ce_hopt( $args, 'services_count', 9 ), $ce_extra );
$ce_layout   = ce_hopt( $args, 'layout', 'editorial' );
if ( ! $ce_services ) {
	return;
}
?>
<section class="ce-section ce-services" aria-labelledby="<?php echo esc_attr( ce_uid( $args, 'ce-services-title' ) ); ?>">
	<div class="ce-container">
		<?php
		ce_section_head(
			array(
				'eyebrow'    => ce_hopt( $args, 'services_eyebrow' ),
				'title'      => ce_hopt( $args, 'services_title' ),
				'text'       => ce_hopt( $args, 'services_text' ),
				'link'       => ce_services_url(),
				'link_label' => 'Tüm hizmetler',
				'id'         => ce_uid( $args, 'ce-services-title' ),
			)
		);
		?>
		<div class="ce-services__grid<?php echo 'grid' === $ce_layout ? ' ce-services__grid--archive' : ''; ?>">
			<?php
			foreach ( $ce_services as $ce_i => $ce_service ) {
				$ce_size = 'grid' === $ce_layout ? 'md' : ( $ce_i < 2 ? 'lg' : ( $ce_i < 5 ? 'md' : 'sm' ) );
				get_template_part( 'template-parts/components/service-card', null, array( 'post' => $ce_service, 'size' => $ce_size, 'index' => $ce_i ) );
			}
			?>
		</div>
	</div>
</section>
