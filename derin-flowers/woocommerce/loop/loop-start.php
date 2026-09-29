<?php
/**
 * Ürün listesi başlangıcı.
 *
 * @package DerinFlowers
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;
?>
<ul class="products df-grid df-grid--<?php echo esc_attr( wc_get_loop_prop( 'columns' ) ); ?>">
