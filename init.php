<?php
/*
Plugin Name: YITH WooCommerce Points and Rewards Premium
Description: YITH WooCommerce Points and Rewards allows you to add a rewarding program to your site and encourage your customers collecting points.
Version: 1.2.6
Author: YITHEMES
Author URI: http://yithemes.com/
Text Domain: yith-woocommerce-points-and-rewards
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
 * @package YITH WooCommerce Points and Rewards Premium
 * @since   1.0.0
 * @author  YITHEMES
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! defined( 'YITH_YWPAR_DIR' ) ) {
    define( 'YITH_YWPAR_DIR', plugin_dir_path( __FILE__ ) );
}


if( ! function_exists( 'yit_deactive_free_version' ) ) {
    require_once 'plugin-fw/yit-deactive-plugin.php';
}
yit_deactive_free_version( 'YITH_YWPAR_FREE_INIT', plugin_basename( __FILE__ ) );

    /* Plugin Framework Version Check */
if( ! function_exists( 'yit_maybe_plugin_fw_loader' ) && file_exists( YITH_YWPAR_DIR . 'plugin-fw/init.php' ) ) {
    require_once( YITH_YWPAR_DIR . 'plugin-fw/init.php' );
}
yit_maybe_plugin_fw_loader( YITH_YWPAR_DIR  );

// Define constants ________________________________________
if ( defined( 'YITH_YWPAR_VERSION' ) ) {
    return;
}else{
    define( 'YITH_YWPAR_VERSION', '1.2.6' );
}

if ( ! defined( 'YITH_YWPAR_PREMIUM' ) ) {
    define( 'YITH_YWPAR_PREMIUM', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YITH_YWPAR_INIT' ) ) {
    define( 'YITH_YWPAR_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YITH_YWPAR_FILE' ) ) {
    define( 'YITH_YWPAR_FILE', __FILE__ );
}


if ( ! defined( 'YITH_YWPAR_URL' ) ) {
    define( 'YITH_YWPAR_URL', plugins_url( '/', __FILE__ ) );
}

if ( ! defined( 'YITH_YWPAR_ASSETS_URL' ) ) {
    define( 'YITH_YWPAR_ASSETS_URL', YITH_YWPAR_URL . 'assets' );
}

if ( ! defined( 'YITH_YWPAR_TEMPLATE_PATH' ) ) {
    define( 'YITH_YWPAR_TEMPLATE_PATH', YITH_YWPAR_DIR . 'templates' );
}

if ( ! defined( 'YITH_YWPAR_INC' ) ) {
    define( 'YITH_YWPAR_INC', YITH_YWPAR_DIR . '/includes/' );
}

if ( ! defined( 'YITH_YWPAR_SUFFIX' ) ) {
    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
    define( 'YITH_YWPAR_SUFFIX', $suffix );
}

if ( ! defined( 'YITH_YWPAR_SLUG' ) ) {
    define( 'YITH_YWPAR_SLUG', 'yith-woocommerce-points-and-rewards' );
}

if ( ! defined( 'YITH_YWPAR_SECRET_KEY' ) ) {
    define( 'YITH_YWPAR_SECRET_KEY', 'BtvfSnvcDK1ZB1lgvJbY' );
}

if ( !function_exists( 'yith_ywpar_install_woocommerce_admin_notice' ) ) {
    function yith_ywpar_install_woocommerce_premium_admin_notice() {
        ?>
        <div class="error">
            <p><?php _e( 'YITH WooCommerce Points and Rewards is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-points-and-rewards' ); ?></p>

        </div>
        <?php
    }
}

if ( ! function_exists( 'yith_ywpar_premium_install' ) ) {
    function yith_ywpar_premium_install() {

        if ( !function_exists( 'WC' ) ) {
            add_action( 'admin_notices', 'yith_ywpar_install_woocommerce_admin_notice' );
        } else {
            do_action( 'yith_ywpar_init' );
        }

        // check for update table
        if( function_exists( 'yith_ywpar_update_db_check' ) ) {
            yith_ywpar_update_db_check();
        }
    }

    add_action( 'plugins_loaded', 'yith_ywpar_premium_install', 11 );
}

register_activation_hook( __FILE__, 'yith_ywpar_reset_option_version' );
function yith_ywpar_reset_option_version(){
    delete_option( 'yit_ywpar_option_version' );
}

function yith_ywpar_premium_constructor() {

    // Woocommerce installation check _________________________
    if ( !function_exists( 'WC' ) ) {


        add_action( 'admin_notices', 'yith_ywpar_install_woocommerce_admin_notice' );
        return;
    }

    // Load ywpar text domain ___________________________________
    load_plugin_textdomain( 'yith-woocommerce-points-and-rewards', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    if( ! class_exists( 'WP_List_Table' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }

    require_once( YITH_YWPAR_INC . 'functions.yith-wc-points-rewards.php' );
    require_once( YITH_YWPAR_INC . 'class.yith-wc-points-rewards-admin.php' );
    require_once( YITH_YWPAR_INC . 'class.yith-wc-points-rewards-frontend.php' );
    require_once( YITH_YWPAR_INC . 'class.yith-wc-points-rewards.php' );
    require_once( YITH_YWPAR_INC . 'class.yith-wc-points-rewards-earning.php' );
    require_once( YITH_YWPAR_INC . 'class.yith-wc-points-rewards-redemption.php' );
    require_once( YITH_YWPAR_INC . 'class.yith-wc-points-rewards-porting.php' );

    require_once( YITH_YWPAR_INC . '/widgets/class.yith-wc-points-rewards-widget.php' );

    require_once( YITH_YWPAR_INC . 'admin/yith-wc-points-rewards-customers-view.php' );
    require_once( YITH_YWPAR_INC . 'admin/yith-wc-points-rewards-customer-history-view.php' );

    if ( class_exists( 'YITH_Vendors' ) ) {
        require_once( YITH_YWPAR_INC . 'compatibility/yith-woocommerce-product-vendors.php' );
    }
    
    if ( is_admin() ) {
        YITH_WC_Points_Rewards_Admin();
    }

    YITH_WC_Points_Rewards();
    YITH_WC_Points_Rewards_Earning();
    YITH_WC_Points_Rewards_Redemption();
    YITH_WC_Points_Rewards_Frontend();

    if ( YITH_WC_Points_Rewards()->get_option( 'enable_expiration_point', 'no' ) == 'yes' ) {
        add_action( 'ywpar_cron', array( YITH_WC_Points_Rewards(), 'set_expired_points' ) );
        add_action( 'ywpar_cron', array( YITH_WC_Points_Rewards(), 'send_email_before_expiration' ) );
    }

    add_action( 'ywpar_cron', array( YITH_WC_Points_Rewards(), 'clear_coupons'));
}
add_action( 'yith_ywpar_init', 'yith_ywpar_premium_constructor' );