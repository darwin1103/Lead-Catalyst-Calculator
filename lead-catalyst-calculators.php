<?php
/**
 * Plugin Name: Lead Catalyst Estimators & ROI Calculators
 * Description: Registers a custom Elementor Widget for ROI and Missed Opportunity Calculators.
 * Version: 1.0.0
 * Author: Antigravity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Register frontend assets
add_action( 'wp_enqueue_scripts', function() {
    wp_register_script(
        'lead-catalyst-calculator-script',
        plugins_url( 'assets/calculator.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );
    wp_register_style(
        'lead-catalyst-calculator-style',
        plugins_url( 'assets/calculator.css', __FILE__ ),
        array(),
        '1.0.0'
    );
} );

// Register the Elementor Widget
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    $widget_file = __DIR__ . '/widgets/calculator-widget.php';
    if ( file_exists( $widget_file ) ) {
        require_once $widget_file;
        $widgets_manager->register( new \Lead_Catalyst_Calculator_Widget() );
    }
} );
