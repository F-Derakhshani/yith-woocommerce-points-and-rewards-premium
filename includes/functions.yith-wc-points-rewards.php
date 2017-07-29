<?php
if ( !defined( 'ABSPATH' ) || ! defined( 'YITH_YWPAR_VERSION' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements helper functions for YITH WooCommerce Points and Rewards
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */

global $yith_ywpar_db_version;

$yith_ywpar_db_version = '1.0.1';

if ( !function_exists( 'yith_ywpar_db_install' ) ) {
    /**
     * Install the table yith_ywpar_points_log
     *
     * @return void
     * @since 1.0.0
     */
    function yith_ywpar_db_install() {
        global $wpdb;
        global $yith_ywpar_db_version;

        $installed_ver = get_option( "yith_ywpar_db_version" );

        $table_name = $wpdb->prefix . 'yith_ywpar_points_log';

        $charset_collate = $wpdb->get_charset_collate();

        if( ! $installed_ver ){
                $sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `action` VARCHAR (255) NOT NULL,
            `order_id` int(11),
            `amount` int(11) NOT NULL,
            `date_earning` datetime NOT NULL,
            `cancelled` datetime,
            PRIMARY KEY (id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            add_option( 'yith_ywpar_db_version', $yith_ywpar_db_version );
        }

        if ( $installed_ver == '1.0.0') {
            $sql = "SELECT COLUMN_NAME from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME='$table_name'";
            $cols = $wpdb->get_col($sql);

            if( is_array($cols) && !in_array('cancelled', $cols)){
                $sql = "ALTER TABLE $table_name ADD `cancelled` datetime";
                $wpdb->query( $sql);
            }
            update_option( 'yith_ywpar_db_version', $yith_ywpar_db_version );
        }
    }
}



if ( !function_exists( 'yith_ywpar_update_db_check' ) ) {
    /**
     * check if the function yith_ywpar_db_install must be installed or updated
     *
     * @return void
     * @since 1.0.0
     */
    function yith_ywpar_update_db_check() {
        global $yith_ywpar_db_version;

        if ( get_site_option( 'yith_ywpar_db_version' ) != $yith_ywpar_db_version ) {

            yith_ywpar_db_install();
        }
    }
}


if ( !function_exists( 'yith_ywpar_locate_template' ) ) {
    /**
     * Locate the templates and return the path of the file found
     *
     * @param string $path
     * @param array  $var
     *
     * @return string
     * @since 1.0.0
     */
    function yith_ywpar_locate_template( $path, $var = NULL ) {

        global $woocommerce;

        if ( function_exists( 'WC' ) ) {
            $woocommerce_base = WC()->template_path();
        }
        elseif ( defined( 'WC_TEMPLATE_PATH' ) ) {
            $woocommerce_base = WC_TEMPLATE_PATH;
        }
        else {
            $woocommerce_base = $woocommerce->plugin_path() . '/templates/';
        }

        $template_woocommerce_path = $woocommerce_base . $path;
        $template_path             = '/' . $path;
        $plugin_path               = YITH_YWPAR_DIR . 'templates/' . $path;

        $located = locate_template( array(
            $template_woocommerce_path, // Search in <theme>/woocommerce/
            $template_path,             // Search in <theme>/
            $plugin_path                // Search in <plugin>/templates/
        ) );

        if ( !$located && file_exists( $plugin_path ) ) {
            return apply_filters( 'yith_ywpar_locate_template', $plugin_path, $path );
        }

        return apply_filters( 'yith_ywpar_locate_template', $located, $path );
    }
}


if ( !function_exists( 'yith_ywpar_get_roles' ) ) {
    /**
     * Return the roles of users
     *
     * @return array
     * @since 1.0.0
     */
    function yith_ywpar_get_roles(){
        global $wp_roles;
        return $wp_roles->get_names();
    }
}

if ( ! function_exists( 'yith_ywpar_calculate_user_total_orders_amount' ) ) {
	/**
	 * Calculate the amount of all order completed and processed of a user
	 *
	 * @param $user_id
	 * @param int $order_id
	 *
	 * @return float
	 * @since 1.1.3
	 */

	function yith_ywpar_calculate_user_total_orders_amount( $user_id, $order_id = 0 ) {

		$orders = wc_get_orders( array( 'customer' => $user_id, 'status' => array( 'wc-completed', 'wc-processed' ) ) );
		$o = wc_get_order( $order_id );
		$total_amount = 0;

		if ( $orders ) {
			foreach ( $orders as $order ) {
				if( $order_id && $order_id == $order->id ){
					continue;
				}
				$total_amount += $order->get_subtotal();
			}
		}

		if( $o ){
			$total_amount += $o->get_subtotal();
		}

		return $total_amount;

	}
}

if ( ! function_exists( 'ywpar_get_customer_order_count' ) ) {
	/**
	 * Calculate the amount of all order completed and processed of a user
	 *
	 * @param $user_id
	 *
	 * @return float
	 * @internal param int $order_id
	 *
	 * @since    1.1.3
	 */

	function ywpar_get_customer_order_count( $user_id ) {

		$orders = wc_get_orders( array( 'customer' => $user_id, 'status' => array( 'wc-completed', 'wc-processed' ) , 'limit' => -1 ) );

		return count( $orders );

	}
}

/**
 * Provides functionality for array_column() to projects using PHP earlier than
 * version 5.5.
 * @copyright (c) 2015 WinterSilence (http://github.com/WinterSilence)
 * @license MIT
 */
if (!function_exists('array_column')) {
    /**
     * Returns an array of values representing a single column from the input
     * array.
     * @param array $array A multi-dimensional array from which to pull a
     *     column of values.
     * @param mixed $columnKey The column of values to return. This value may
     *     be the integer key of the column you wish to retrieve, or it may be
     *     the string key name for an associative array. It may also be NULL to
     *     return complete arrays (useful together with index_key to reindex
     *     the array).
     * @param mixed $indexKey The column to use as the index/keys for the
     *     returned array. This value may be the integer key of the column, or
     *     it may be the string key name.
     * @return array
     */
    function array_column(array $array, $columnKey, $indexKey = null)
    {
        $result = array();
        foreach ($array as $subArray) {
            if (!is_array($subArray)) {
                continue;
            } elseif (is_null($indexKey) && array_key_exists($columnKey, $subArray)) {
                $result[] = $subArray[$columnKey];
            } elseif (array_key_exists($indexKey, $subArray)) {
                if (is_null($columnKey)) {
                    $result[$subArray[$indexKey]] = $subArray;
                } elseif (array_key_exists($columnKey, $subArray)) {
                    $result[$subArray[$indexKey]] = $subArray[$columnKey];
                }
            }
        }
        return $result;
    }
}
if ( ! function_exists( 'ywpar_get_price' ) ) {
	function ywpar_get_price( $product, $qty = 1, $price = '' ){

		if ( $price === '' ) {
			$price = $product->get_price();
		}

		$tax_display_mode = apply_filters('ywpar_get_price_tax_on_points', get_option( 'woocommerce_tax_display_shop' ) );
		$display_price = $tax_display_mode == 'incl' ? yit_get_price_including_tax( $product, $qty, $price ) : yit_get_price_excluding_tax( $product, $qty, $price );

		return $display_price;
	}
}

if ( function_exists( 'AW_Referrals' ) ) {
	add_filter( 'woocommerce_coupon_is_valid', 'validate_ywpar_coupon', 11, 2 );
	/**
	 * Compatibility with AutomateWoo - Referrals Add-on
	 * @param $valid
	 * @param $coupon
	 *
	 * @return bool
	 */
	function validate_ywpar_coupon( $valid, $coupon ) {
		if ( 'ywpar_discount' == $coupon->code ) {
			return true;
		}

		return $valid;
	}
}

