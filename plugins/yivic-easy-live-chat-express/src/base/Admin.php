<?php
/**
 * Created by PhpStorm.
 * Author: manhphucofficial@yahoo.com
 * Date time: 07/13/2021 11:20 AM
 */

namespace Yivic\WpPlugin\Elce\Base;

use Yivic\WpPlugin\Elce\Elce;

class Admin extends BaseObject {

    /**
     * Admin constructor.
     * Initialize all hook related to admin
     *
     * @param null $config
     */
    public function __construct( $config = null ) {
        $this->init();
    }

    /**
     * Hook to attach to admin_init action
     * Register settings for the plugin options.
     */
    public function admin_init() {

        register_setting(
            Elce::OPTION_GROUP_NAME,
            Elce::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
            )
        );
    }

    /**
     * Sanitize all plugin options before saving.
     *
     * @param array $input Raw input.
     *
     * @return array Sanitized options.
     */
    public static function sanitize_options( $input ) {

        $input  = is_array( $input ) ? $input : array();
        $output = array();

        // Phone settings.
        $output['phone_app_number'] = isset( $input['phone_app_number'] )
            ? sanitize_text_field( $input['phone_app_number'] )
            : '';

        $output['phone_app_color'] = isset( $input['phone_app_color'] )
            ? sanitize_hex_color( $input['phone_app_color'] )
            : '';

        $output['phone_app_bar'] = ! empty( $input['phone_app_bar'] ) ? 1 : 0;

        // Zalo & Messenger.
        $output['zalo_app_number'] = isset( $input['zalo_app_number'] )
            ? sanitize_text_field( $input['zalo_app_number'] )
            : '';

        $output['messenger_app_link'] = isset( $input['messenger_app_link'] )
            ? sanitize_text_field( $input['messenger_app_link'] )
            : '';

        // Contact link.
        $output['contact_app_link'] = isset( $input['contact_app_link'] )
            ? sanitize_text_field( $input['contact_app_link'] )
            : '';

        // Display settings.
        $location = isset( $input['location_display'] )
            ? sanitize_text_field( $input['location_display'] )
            : 'left';

        // Only allow left/right.
        $output['location_display'] = in_array( $location, array( 'left', 'right' ), true )
            ? $location
            : 'left';

        $output['hide_app_desktop'] = ! empty( $input['hide_app_desktop'] ) ? 1 : 0;
        $output['hide_app_mobile']  = ! empty( $input['hide_app_mobile'] ) ? 1 : 0;

        return $output;
    }

    /**
     * Initialize admin hooks.
     */
    public function init() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_color_picker' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    /**
     * Enqueue admin styles/scripts.
     */
    public function enqueue_color_picker() {
        wp_enqueue_style( 'wp-color-picker' );
    }

    /**
     * Hook to attach to admin_menu action
     * Add more menu item to admin menu
     */
    public function admin_menu() {
        add_submenu_page(
            'options-general.php',
            'Yivic Easy Live Chat',
            'Yivic Easy Live Chat',
            'manage_options',
            'elce',
            array( $this, 'display_options_page' )
        );
    }

    /**
     * Display options page in Admin Panel
     */
    public function display_options_page() {

        $options = get_option( Elce::OPTION_KEY );

        if ( empty( $options ) ) {
            $options = Elce::default_options();
        }

        include Elce::plugin_dir_path() . '/views/admin/options-page.php';
    }

    /**
     * (Legacy) Hook to attach to admin_menu action
     * Add more menu item to admin menu
     */
    public function display_options() {
        add_menu_page(
            'Yivic Easy Live Chat Options',
            'Yivic Easy Live Chat',
            'manage_options',
            'elce',
            array( $this, 'display_options_page' )
        );
    }
}