<?php
/**
 * Plugin Name: FriendsLink 友链管理
 * Description: 简单管理友情链接，通过短代码 [friendslink] 在前台展示。
 * Version:     1.1.0
 * Author:      FriendsLink
 * License:     GPL-2.0+
 * Text Domain: friendslink
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FRIENDSLINK_VERSION',    '1.1.0' );
define( 'FRIENDSLINK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRIENDSLINK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once FRIENDSLINK_PLUGIN_DIR . 'includes/class-fl-db.php';
require_once FRIENDSLINK_PLUGIN_DIR . 'includes/class-fl-admin.php';
require_once FRIENDSLINK_PLUGIN_DIR . 'includes/class-fl-shortcode.php';

register_activation_hook( __FILE__, [ 'FL_DB', 'create_table' ] );

add_action( 'plugins_loaded', 'friendslink_init' );

function friendslink_init() {
    $installed = get_option( 'friendslink_version', '0' );
    if ( version_compare( $installed, FRIENDSLINK_VERSION, '<' ) ) {
        FL_DB::create_table();
        update_option( 'friendslink_version', FRIENDSLINK_VERSION );
    }

    new FL_Admin();
    new FL_Shortcode();
}
