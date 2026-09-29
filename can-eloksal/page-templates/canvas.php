<?php
/**
 * Template Name: Boş Tuval (Builder / Elementor)
 *
 * Tema hero'su ve kenar boşlukları olmadan yalnızca header + içerik + footer.
 * Can Eloksal Builder blokları veya Elementor ile serbest sayfa tasarlamak için.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
ce_render_canvas();
get_footer();
