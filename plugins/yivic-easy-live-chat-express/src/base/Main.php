<?php
/**
 * Created by PhpStorm.
 * User: phucnguyen
 * Date: 07/13/2021
 * Time: 11:06 PM
 */

namespace Yivic\WpPlugin\Elce\Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Yivic\WpPlugin\Elce\Elce;

class Main extends BaseObject {

    /**
     * Frontend constructor.
     * Initialize all hooks related to frontend.
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Init frontend hooks.
     */
    public function init() {
        // Add frontend markup to footer.
        add_action( 'wp_footer', array( $this, 'get_contact_number_box' ) );

        // Add styles to frontend.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Add style to frontend.
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'elce_style',
            Elce::plugin_dir_url() . 'assets/dist/css/elce-style.css',
            array(),
            null
        );
    }

    /**
     * Output Contact Number Plugin box content.
     */
    public function get_contact_number_box() {

        $options = Elce::instance()->options;
        $options = is_array( $options ) ? $options : array();

        $result = '';
        $result .= '<div class="wp-elce">';

        // Contact section.
        $elce_contact = ! empty( $options['contact_app_link'] ) ? esc_url( $options['contact_app_link'] ) : '';

        if ( ! empty( $elce_contact ) ) {
            $result .= '<div id="elce-contact" class="elce-contact">';
            $result .= '    <div class="elce-phone">';
            $result .= '        <div class="elce-phone-circle-fill"></div>';
            $result .= '        <div class="elce-phone-img-circle">';
            $result .= '            <a target="_blank" href="' . $elce_contact . '">';
            $result .= '                <img src="' . esc_url( Elce::plugin_dir_url() . 'assets/src/images/contact.png' ) . '" alt="Contact">';
            $result .= '            </a>';
            $result .= '        </div>';
            $result .= '    </div>';
            $result .= '</div>';
        }
        // End Contact section.

        // Messenger section.
        $elce_messenger = ! empty( $options['messenger_app_link'] ) ? sanitize_text_field( $options['messenger_app_link'] ) : '';

        if ( ! empty( $elce_messenger ) ) {
            $messenger_url = 'https://m.me/' . rawurlencode( $elce_messenger );

            $result .= '<div id="elce-messenger" class="elce-contact">';
            $result .= '    <div class="elce-phone">';
            $result .= '        <div class="elce-phone-circle-fill"></div>';
            $result .= '        <div class="elce-phone-img-circle">';
            $result .= '            <a target="_blank" href="' . esc_url( $messenger_url ) . '">';
            $result .= '                <img src="' . esc_url( Elce::plugin_dir_url() . 'assets/src/images/messenger.png' ) . '" alt="Messenger">';
            $result .= '            </a>';
            $result .= '        </div>';
            $result .= '    </div>';
            $result .= '</div>';
        }
        // End Messenger section.

        // Zalo section.
        $elce_zalo    = ! empty( $options['zalo_app_number'] ) ? sanitize_text_field( $options['zalo_app_number'] ) : '';
        $zalo_number  = preg_replace( '/\D/', '', $elce_zalo );

        if ( ! empty( $zalo_number ) ) {
            $zalo_url = 'https://zalo.me/' . $zalo_number;

            $result .= '<div id="elce-zalo" class="elce-contact">';
            $result .= '    <div class="elce-phone">';
            $result .= '        <div class="elce-phone-circle-fill"></div>';
            $result .= '        <div class="elce-phone-img-circle">';
            $result .= '            <a target="_blank" href="' . esc_url( $zalo_url ) . '">';
            $result .= '                <img src="' . esc_url( Elce::plugin_dir_url() . 'assets/src/images/zalo.png' ) . '" alt="Zalo">';
            $result .= '            </a>';
            $result .= '        </div>';
            $result .= '    </div>';
            $result .= '</div>';
        }
        // End Zalo section.

        // Phone section.
        $elce_phone_raw = ! empty( $options['phone_app_number'] ) ? $options['phone_app_number'] : '';
        $elce_phone     = sanitize_text_field( $elce_phone_raw );

        if ( ! empty( $elce_phone ) ) {

            $tel_href = 'tel:' . preg_replace( '/[^0-9+]/', '', $elce_phone );

            $result .= '<div id="elce-phone" class="elce-contact">';
            $result .= '    <div class="elce-phone">';
            $result .= '        <div class="elce-phone-circle-fill"></div>';
            $result .= '        <div class="elce-phone-img-circle">';
            $result .= '            <a href="' . esc_url( $tel_href ) . '">';
            $result .= '                <img src="' . esc_url( Elce::plugin_dir_url() . 'assets/src/images/phone.png' ) . '" alt="Phone">';
            $result .= '            </a>';
            $result .= '        </div>';
            $result .= '    </div>';
            $result .= '</div>';

            // Phone bar.
            if ( ! empty( $options['phone_app_bar'] ) ) {
                $result .= '<div class="phone-bar phone-bar-n">';
                $result .= '    <div class="phone-bar phone-bar-n">';
                $result .= '        <a href="' . esc_url( $tel_href ) . '">';
                $result .= '            <span class="text-phone">' . esc_html( $elce_phone ) . '</span>';
                $result .= '        </a>';
                $result .= '    </div>';
                $result .= '</div>';
            }

            $phone_color = ! empty( $options['phone_app_color'] ) ? sanitize_hex_color( $options['phone_app_color'] ) : '#dd382d';

            $result .= '
			<style>
				.phone-bar a,
				#elce-phone .elce-phone-circle-fill,
				#elce-phone .elce-phone-img-circle,
				#elce-phone .phone-bar a {
					background-color: ' . esc_attr( $phone_color ) . ';
				}
				#elce-phone .elce-phone-circle-fill {
					opacity: 0.7;
					box-shadow: 0 0 0 0 ' . esc_attr( $phone_color ) . ';
				}
			</style>
			';
        }
        // End phone section.

        // Check select location.
        if ( ! empty( $options['location_display'] ) && 'right' === $options['location_display'] ) {
            $result .= '
			<style>
				.wp-elce { right: 0; left: unset; }
				.phone-bar a { left: auto; right: 30px; padding: 8px 55px 7px 15px; }
			</style>
			';
        }

        // Check hide on mobile.
        if ( ! empty( $options['hide_app_mobile'] ) ) {
            $result .= '
			<style>
				@media (max-width: 736px) {
					.wp-elce { display: none; }
				}
			</style>
			';
        }

        // Check hide on desktop.
        if ( ! empty( $options['hide_app_desktop'] ) ) {
            $result .= '
			<style>
				@media (min-width: 736px) {
					.wp-elce { display: none; }
				}
			</style>
			';
        }

        $result .= '</div>';

        // Không dùng gettext cho HTML động – chỉ echo ra.
        echo $result;
    }
}