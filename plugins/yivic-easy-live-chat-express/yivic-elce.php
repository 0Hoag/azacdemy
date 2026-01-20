<?php
/*
Plugin Name:       Yivic Easy Live Chat
Plugin URI:        https://wordpress.org/plugins/yivic-easy-live-chat-express/
Description:       Add floating chat buttons for Zalo, Messenger and phone to your WordPress site.
Version:           1.0.4
Author:            Yivic
Author URI:        https://www.yivic.com/
License:           GPLv3
License URI:       https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:       yivic-easy-live-chat-express
Domain Path:       /languages
*/

/*
=====GNU General Public License V3 (GPL v3)=====

Copyright(C) 2021, Phuc Nguyen - manhphucofficial@yahoo.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

=====The MIT License (MIT)=====

Copyright (c) 2021, Phuc Nguyen - manhphucofficial@yahoo.com

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

All rights reserved.
*/

use \Yivic\WpPlugin\Elce\Elce;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

defined( 'YIVIC_ELCE_VER' ) or define( 'YIVIC_ELCE_VER', '1.0.4' );
// Text domain constant chỉ dùng nội bộ config, KHÔNG dùng cho các hàm gettext.
defined( 'YIVIC_TEXT_DOMAIN' ) or define( 'YIVIC_TEXT_DOMAIN', 'yivic-easy-live-chat-express' );

require dirname( __FILE__ ) . '/vendor/autoload.php';

$plugin = plugin_basename( __FILE__ );

$config = [
    'text_domain'     => YIVIC_TEXT_DOMAIN,
    'plugin_dir_path' => plugin_dir_path( __FILE__ ),
    'plugin_dir_url'  => plugin_dir_url( __FILE__ ),
];

Elce::instance( $config );

add_filter( "plugin_action_links_$plugin", [ Elce::instance(), 'plugin_action_links' ] );
add_action( 'init', [ Elce::instance(), 'init' ] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    try {
        WP_CLI::add_command( 'elce', '\\Sb\\Elce\\WpCliCommand' );
    } catch ( Exception $e ) {
        // Không dùng gettext ở đây để tránh lỗi i18n, chỉ log warning cho WP-CLI.
        WP_CLI::warning( 'WP_CLI error: ' . $e->getMessage() );
    }
}