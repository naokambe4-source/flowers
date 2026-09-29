<?php
/**
 * Hizmetler bölümü: editoryal ızgara (2 büyük, 3 orta, sonra küçük kartlar).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_services = ce_get_items( 'ce_service', (int) ce_opt( 'services_count', 9 ) );
if ( ! $ce_services ) {
	return;
}
?>
<section class="ce-section ce-services" aria-labelledby="ce-services-title">
	<div class="ce-container">
		<?php
		ce_section_head(
			array(
				'eyebrow'    => ce_opt( 'services_eyebrow' ),
				'title'      => ce_opt( 'services_title' ),
				'text'       => ce_opt( 'services_text' ),
				'link'       => ce_services_url(),
				'link_label' => 'Tüm hizmetler',
				'id'         => 'ce-services-title',
			)
		);
		?>
		<div class="ce-services__grid">
			<?php
			foreach ( $ce_services as $ce_i => $ce_service ) {
				$ce_size = $ce_i < 2 ? 'lg' : ( $ce_i < 5 ? 'md' : 'sm' );
				get_template_part( 'template-parts/components/service-card', null, array( 'post' => $ce_service, 'size' => $ce_size, 'index' => $ce_i ) );
			}
			?>
		</div>
	</div>
</section>
