<?php
/**
 * Admin options page
 */

use \Yivic\WpPlugin\Elce\Elce;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Load assets
wp_enqueue_style( 'elce_admin', Elce::plugin_dir_url() . 'assets/dist/css/admin.css' );
wp_enqueue_script( 'elce_admin_script', Elce::plugin_dir_url() . 'assets/dist/js/admin.js', [ 'wp-color-picker' ], false, true );

// Prepare vars
$text_domain = 'yivic-easy-live-chat-express';
?>
<div class="options">
    <div class="options_header">
        <h1><?php echo esc_html__( 'Yivic Easy Live Chat', $text_domain ); ?></h1>
    </div>

    <div class="options">
        <div class="options_left">
            <h3><?php echo esc_html__( 'Options Left', $text_domain ); ?></h3>

            <div class="inside">
                <form method="post" action="options.php" id="options">
                    <?php settings_fields( Elce::OPTION_GROUP_NAME ); ?>

                    <h3 class="title"><?php echo esc_html__( 'Contact App Settings', $text_domain ); ?></h3>

                    <table class="form-table">

                        <!-- PHONE -->
                        <tr valign="top">
                            <th scope="row">
                                <label for="phone_app_number"><?php echo esc_html__( 'Phone', $text_domain ); ?></label>
                            </th>
                            <td>
                                <input
                                        placeholder="0123 456 789"
                                        id="phone_app_number"
                                        class="standard-input"
                                        type="text"
                                        name="elce[phone_app_number]"
                                        value="<?php echo esc_attr( $options['phone_app_number'] ?? '' ); ?>"
                                />
                            </td>
                        </tr>

                        <!-- COLOR -->
                        <tr valign="top">
                            <th scope="row">
                                <label for="phone_app_color"><?php echo esc_html__( 'Color', $text_domain ); ?></label>
                            </th>
                            <td>
                                <input
                                        id="phone_app_color"
                                        class="my-color-field"
                                        type="text"
                                        name="elce[phone_app_color]"
                                        value="<?php echo esc_attr( $options['phone_app_color'] ?? '' ); ?>"
                                />
                            </td>
                        </tr>

                        <!-- HOTLINE BAR -->
                        <tr valign="top" style="border-bottom: 1px dashed #bfbfbf;">
                            <th scope="row">
                                <label for="phone_app_bar"><?php echo esc_html__( 'Hotline bar (show/hide)', $text_domain ); ?></label>
                            </th>
                            <td>
                                <?php $options['phone_app_bar'] = $options['phone_app_bar'] ?? 0; ?>

                                <input id="phone_app_bar" name="elce[phone_app_bar]" type="checkbox"
                                       value="1" <?php checked( $options['phone_app_bar'], 1 ); ?> />

                                <small><?php echo esc_html__( 'Show phone number next to button or hide.', $text_domain ); ?></small>
                            </td>
                        </tr>

                        <!-- ZALO -->
                        <tr valign="top">
                            <th scope="row">
                                <label for="zalo_app_number"><?php echo esc_html__( 'Zalo', $text_domain ); ?></label>
                            </th>
                            <td>
                                <input
                                        placeholder="0123 456 789"
                                        id="zalo_app_number"
                                        class="standard-input"
                                        type="text"
                                        name="elce[zalo_app_number]"
                                        value="<?php echo esc_attr( $options['zalo_app_number'] ?? '' ); ?>"
                                />
                            </td>
                        </tr>

                        <!-- MESSENGER -->
                        <tr valign="top">
                            <th scope="row">
                                <label for="messenger_app_link"><?php echo esc_html__( 'Messenger', $text_domain ); ?></label>
                            </th>
                            <td>
                                <input
                                        placeholder="fb_id"
                                        id="messenger_app_link"
                                        class="standard-input"
                                        type="text"
                                        name="elce[messenger_app_link]"
                                        value="<?php echo esc_attr( $options['messenger_app_link'] ?? '' ); ?>"
                                />
                            </td>
                        </tr>

                        <!-- CONTACT LINK -->
                        <tr valign="top" style="border-bottom: 1px dashed #bfbfbf;">
                            <th scope="row">
                                <label for="contact_app_link"><?php echo esc_html__( 'Contact link', $text_domain ); ?></label>
                            </th>
                            <td>
                                <input
                                        placeholder="/contact/"
                                        id="contact_app_link"
                                        class="standard-input"
                                        type="text"
                                        name="elce[contact_app_link]"
                                        value="<?php echo esc_attr( $options['contact_app_link'] ?? '' ); ?>"
                                />
                            </td>
                        </tr>
                    </table>

                    <!-- DISPLAY SETTINGS -->
                    <h3 class="title"><?php echo esc_html__( 'Display Settings', $text_domain ); ?></h3>

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label for="location_display"><?php echo esc_html__( 'Location', $text_domain ); ?></label>
                            </th>
                            <td>
                                <select id="location_display" name="elce[location_display]">
                                    <option value="left"  <?php selected( $options['location_display'] ?? '', 'left' );  ?>>
                                        <?php echo esc_html__( 'Left', $text_domain ); ?>
                                    </option>
                                    <option value="right" <?php selected( $options['location_display'] ?? '', 'right' ); ?>>
                                        <?php echo esc_html__( 'Right', $text_domain ); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>

                        <!-- HIDE SETTINGS -->
                        <tr valign="top">
                            <th scope="row"><label for="hide_app_desktop"><?php echo esc_html__( 'Hide on', $text_domain ); ?></label></th>

                            <td>
                                <?php $options['hide_app_desktop'] = $options['hide_app_desktop'] ?? 0; ?>
                                <input id="hide_app_desktop" name="elce[hide_app_desktop]" type="checkbox"
                                       value="1" <?php checked( $options['hide_app_desktop'], 1 ); ?> />

                                <small><?php echo esc_html__( 'Button will not be displayed on desktop sized devices.', $text_domain ); ?></small>
                            </td>

                            <td>
                                <?php $options['hide_app_mobile'] = $options['hide_app_mobile'] ?? 0; ?>
                                <input id="hide_app_mobile" name="elce[hide_app_mobile]" type="checkbox"
                                       value="1" <?php checked( $options['hide_app_mobile'], 1 ); ?> />

                                <small><?php echo esc_html__( 'Button will not be displayed on small devices like on mobile.', $text_domain ); ?></small>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" class="button-primary"
                               value="<?php echo esc_attr__( 'Save Changes', $text_domain ); ?>" />
                    </p>
                </form>
            </div>
        </div>

        <div class="options_right">
            <h3><?php echo esc_html__( 'Options Right', $text_domain ); ?></h3>
        </div>
    </div>
</div>