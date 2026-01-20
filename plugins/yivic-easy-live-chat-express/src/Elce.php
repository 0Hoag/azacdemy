<?php
/**
 * Created by PhpStorm.
 * User: phucnguyen
 * Date: 07/13/2021
 * Time: 9:59 PM
 */

namespace Yivic\WpPlugin\Elce;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Yivic\WpPlugin\Elce\Base\Admin;
use Yivic\WpPlugin\Elce\Base\Main;

class Elce {

    const OPTION_KEY        = 'elce';
    const OPTION_GROUP_NAME = 'elce_options';

    /**
     * @var null|Elce
     */
    protected static $_instance = null;

    /**
     * @var null|Admin
     */
    public $admin = null;

    /**
     * @var null|Main
     */
    public $main = null;

    public $wp_cli_command   = null;
    public $text_domain      = null;
    public $plugin_dir_path  = null;
    public $plugin_dir_url   = null;

    /**
     * @var mixed|null options of the plugin from database
     */
    public $options = null;

    /**
     * Elce constructor.
     * Only invoked for singleton object
     *
     * @param null|array $config
     */
    private function __construct( $config = null ) {

        if ( is_array( $config ) ) {
            foreach ( $config as $config_key => $config_val ) {
                if ( property_exists( $this, $config_key ) ) {
                    $this->$config_key = $config_val;
                }
            }
        }

        $this->options = get_option( static::OPTION_KEY );
    }

    /**
     * Get singleton instance
     *
     * @param null|array $config Config params for the instance.
     *
     * @return static
     */
    public static function instance( $config = null ) {

        if ( null === static::$_instance ) {
            static::$_instance = new static( $config );
        }

        return static::$_instance;
    }

    /**
     * Get the text domain of the plugin
     *
     * @return null|string
     */
    public static function text_domain() {
        return static::instance()->text_domain;
    }

    /**
     * Get the plugin directory
     *
     * @return null|string
     */
    public static function plugin_dir_path() {
        return static::instance()->plugin_dir_path;
    }

    /**
     * Get the plugin url
     *
     * @return null|string
     */
    public static function plugin_dir_url() {
        return static::instance()->plugin_dir_url;
    }

    /**
     * Add more links to plugin links in Admin Plugin screen
     *
     * @param array $links Existing links.
     *
     * @return array
     */
    public static function plugin_action_links( $links ) {

        $file_name = 'options-general.php';

        // Text domain phải là chuỗi cố định, không dùng method.
        $settings_link = '<a href="' . $file_name . '?page=elce">' .
            esc_html__( 'Settings', 'yivic-easy-live-chat-express' ) .
            '</a>';

        array_unshift( $links, $settings_link );

        return $links;
    }

    /**
     * Get default option values for the plugin
     *
     * @return array
     */
    public static function default_options() {

        return array(
            'title_text'  => __( 'Yivic Easy Live Chat', 'yivic-easy-live-chat-express' ),
            'title_class' => null,
            'title_id'    => null,
        );
    }

    /**
     * Initialize plugin (admin + frontend).
     */
    public function init() {

        if ( is_admin() ) {
            if ( null === static::$_instance->admin ) {
                static::$_instance->admin = new Admin();
            }
        }

        if ( null === static::$_instance->main ) {
            static::$_instance->main = new Main();
        }
    }
}