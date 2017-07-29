<?php

$additional_options = array();
if( class_exists('WC_Points_Rewards')){
    $additional_options = array(
        'id'        => 'apply_points_from_wc_points_rewards',
        'name'      => __( 'Apply Points from WooCommerce Points and Rewards', 'yith-woocommerce-points-and-rewards' ),
        'desc'      => __( 'You can do this action only one time', 'yith-woocommerce-points-and-rewards' ),
        'type'      => 'points-previous-order',
	    'label'     => __( 'Import points', 'yith-woocommerce-points-and-rewards' ),
        'show_data' => false,
        'std'       => ''
    );
}


$settings = array(

    'general' => array(

        'header'    => array(

            array(
                'name' => __( 'General Settings', 'yith-woocommerce-points-and-rewards' ),
                'type' => 'title'
            ),

            array( 'type' => 'close' )
        ),


        'settings' => array(

            array( 'type' => 'open' ),

            array(
                'id'      => 'enabled',
                'name'    => __( 'Enable Points and Rewards', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'yes'
            ),

			//from 1.1.3
	        array(
		        'id'      => 'enabled_shop_manager',
		        'name'    => __( 'Enable Shop Manager to edit customers\' points', 'yith-woocommerce-points-and-rewards' ),
		        'desc'    => '',
		        'type'    => 'on-off',
		        'std'     => 'no'
	        ),

	        array(
		        'id'      => 'enable_points_upon_sales',
		        'name'    => __( 'Enable points upon sales', 'yith-woocommerce-points-and-rewards' ),
		        'desc'    => '',
		        'type'    => 'on-off',
		        'std'     => 'yes'
	        ),


	        array(
                'id'      => 'earn_points_conversion_rate',
                'name'    => __( 'Conversion Rate for Points earned', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'options-conversion',
                'std'     => array(
                    'points' => 1,
                    'money'  => 1
                )
            ),

            //from 1.1.0
            array(
                'id'      => 'conversion_rate_method',
                'name'    => __( 'Reward Conversion Method', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'select',
                'std'     => 'fixed',
                'options' => array(
                    'none'       => __( 'None', 'yith-woocommerce-points-and-rewards' ),
                    'fixed'      => __( 'Fixed Price Discount', 'yith-woocommerce-points-and-rewards' ),
                    'percentage' => __( 'Percentage Discount', 'yith-woocommerce-points-and-rewards' ),
                )
            ),

            //showed if conversion_rate_method == percentage
            array(
                'id'      => 'rewards_percentual_conversion_rate',
                'name'    => __( 'Reward Percentual Conversion Rate', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'options-percentual-conversion',
                'class'   => 'percentual_method',
                'std'     => array(
                    'points' => 20,
                    'discount'  => 5
                )
            ),

            //showed if conversion_rate_method == percentage
            array(
                'id'      => 'max_percentual_discount',
                'name'    => __( 'Maximum discount', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => __('( in %) Set maximum discount percentuage allowed in cart when redeeming points.','yith-woocommerce-points-and-rewards'),
                'type'    => 'text',
                'custom_attributes'  => array( 'data-hide' => 'percentage_method' ),
                'std'     => '50'
            ),


            //showed if conversion_rate_method == fixed
            array(
                'id'      => 'rewards_conversion_rate',
                'name'    => __( 'Reward Conversion Rate', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'options-conversion',
                'class'  => 'fixed_method',
                'std'     => array(
                    'points' => 100,
                    'money'  => 1
                )
            ),

            //showed if conversion_rate_method == fixed
            array(
                'id'      => 'max_points_discount',
                'name'    => __( 'Maximum discount', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => __('Set maximum product discount allowed in cart when redeeming points. Leave blank to disable.','yith-woocommerce-points-and-rewards'),
                'custom_attributes'  => array( 'data-hide' => 'fixed_method' ),
                'type'    => 'text',
                'std'     => ''
            ),


            //from version 1.0.9
            //showed if conversion_rate_method == fixed
            array(
                'id'      => 'minimum_amount_discount_to_redeem',
                'name'    => __( 'Minimum Discount Required to Redeem', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => __('Set minimum amount of discount to redeem points. Leave blank to disable.','yith-woocommerce-points-and-rewards'),
                'custom_attributes'  => array( 'data-hide' => 'fixed_method' ),
                'type'    => 'text',
                'std'     => ''
            ),

            array(
                'id'      => 'max_points_product_discount',
                'name'    => __( 'Maximum discount for single product', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => __('Set maximum product discount allowed when redeeming points per-product. Leave blank to disable.','yith-woocommerce-points-and-rewards'),
                'custom_attributes'  => array( 'data-hide' => 'fixed_method' ),
                'type'    => 'text',
                'std'     => ''
            ),

            //from version 1.0.1
            array(
                'id'      => 'minimum_amount_to_redeem',
                'name'    => __( 'Minimum Amount to Redeem', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => __('Set minimum amount in the cart to redeem points. Leave blank to disable.','yith-woocommerce-points-and-rewards'),
                'type'    => 'text',
                'std'     => ''
            ),

	        array(
		        'id'      => 'allow_free_shipping_to_redeem',
		        'name'    => __( 'Allow free shipping to Redeem', 'yith-woocommerce-points-and-rewards' ),
		        'desc'    => __( 'Check this box if the coupon grants free shipping. A free shipping method must be enabled in your shipping zone and be set to require "a valid free shipping coupon".', 'yith-woocommerce-points-and-rewards' ),
		        'type'    => 'on-off',
		        'std'     => 'no'
	        ),

            array(
                'id'      => 'remove_point_order_deleted',
                'name'    => __( 'Enable point removal for cancelled orders', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'yes'
            ),

	        array(
		        'id'      => 'remove_point_refund_order',
		        'name'    => __( 'Enable point removal for total or partial refund', 'yith-woocommerce-points-and-rewards' ),
		        'desc'    => '',
		        'type'    => 'on-off',
		        'std'     => 'yes'
	        ),


	        array(
		        'id'      => 'reassing_redeemed_points_refund_order',
		        'name'    => __( 'Reassign redeemed points for total refund', 'yith-woocommerce-points-and-rewards' ),
		        'desc'    => '',
		        'type'    => 'on-off',
		        'std'     => 'no'
	        ),

            //1.0.5
            array(
                'id'      => 'remove_points_coupon',
                'name'    => __( 'Remove points when coupons are used', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'yes'
            ),


            //1.0.5
            array(
                'id'      => 'remove_points_coupon',
                'name'    => __( 'Remove points when coupons are used', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => __( 'If you use coupons, their value will be removed from cart total and consequently points gained will be reduced as well.', 'yith-woocommerce-points-and-rewards' ),
                'type'    => 'on-off',
                'std'     => 'yes'
            ),


	        array(
		        'id'      => 'hide_point_system_to_guest',
		        'name'    => __( 'Hide points message for guest', 'yith-woocommerce-points-and-rewards' ),
		        'desc'    => __( 'If checked hide points messages in single products, cart and checkout', 'yith-woocommerce-points-and-rewards' ),
		        'type'    => 'on-off',
		        'std'     => 'yes'
	        ),

            array(
                'id'      => 'show_point_list_my_account_page',
                'name'    => __( 'Show points in "My Account" page', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => __( 'If checked show the points list "My Account" page', 'yith-woocommerce-points-and-rewards' ),
                'type'    => 'on-off',
                'std'     => 'yes'
            ),

            array(
                'id'        => 'apply_points_previous_order',
                'name'      => __( 'Apply Points to Previous Orders', 'yith-woocommerce-points-and-rewards' ),
                'desc'      => __( 'Starting from - Optional: Leave blank to apply to all orders', 'yith-woocommerce-points-and-rewards' ),
                'type'      => 'points-previous-order',
                'label'     => __( 'Apply Points', 'yith-woocommerce-points-and-rewards' ),
                'show_data' => true,
                'std'       => ''
            ),

            //from 1.1.1
            array(
                'id'           => 'reset_points',
                'name'         => __( 'Reset Points', 'yith-woocommerce-points-and-rewards' ),
                'desc'         => '',
                'type'         => 'text-button',
                'button-class' => 'ywrac_reset_points',
                'button-name'  => __( 'Reset Points', 'yith-woocommerce-points-and-rewards' ),
                'show_data'    => true,
                'std'          => ''
            ),

	        //1.2.0
	        array(
		        'id'      => 'coupon_delete_after_use',
		        'name'    => __( 'Delete the coupon once used', 'yith-woocommerce-points-and-rewards' ),
		        'desc'    => '',
		        'type'    => 'on-off',
		        'std'     => 'yes'
	        ),



        )
    )
);

if ( ! empty( $additional_options ) ) {
    $settings['general']['settings'][] = $additional_options;
}
$settings['general']['settings'][] = array( 'type' => 'close' );
return apply_filters( 'yith_ywpar_panel_settings_options', $settings );