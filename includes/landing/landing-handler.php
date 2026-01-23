<?php

// Hook to enqueue scripts and styles for landing pages
add_action('wp_enqueue_scripts', 'cf7_landing_assets');

function cf7_landing_assets() {
    // Enqueue Landing Page CSS
    wp_enqueue_style(
        'cf7-landing-style', 
        plugins_url('../../assets/css/landing.css', __FILE__), 
        [], 
        '1.0.0'
    );

    // Enqueue Landing Page JS
    wp_enqueue_script(
        'cf7-landing-script', 
        plugins_url('../../assets/js/landing.js', __FILE__), 
        [], 
        '1.0.0', 
        true // Load in footer
    );
    
    // Pass AJAX URL to script
    wp_localize_script('cf7-landing-script', 'cf7_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
