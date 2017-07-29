<?php

$labels = array(

    'labels' => array(

        'header'    => array(

            array(
                'name' => __( 'Labels', 'yith-woocommerce-points-and-rewards' ),
                'type' => 'title'
            ),

            array( 'type' => 'close' )
        ),


        'labels' => array(

            array( 'type' => 'open' ),

            array(
                'id'   => 'points_label_singular',
                'name' => __( 'Singular label replacing "point"', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Point', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'points_label_plural',
                'name' => __( 'Plural label replacing "points"', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Points', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_order_completed',
                'name' => __( 'Order Completed', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Order Completed', 'yith-woocommerce-points-and-rewards')
            ),


	        array(
		        'id'   => 'label_order_processing',
		        'name' => __( 'Order Processing', 'yith-woocommerce-points-and-rewards' ),
		        'desc' => '',
		        'type' => 'text',
		        'std'  => __('Order Processing', 'yith-woocommerce-points-and-rewards')
	        ),

            array(
                'id'   => 'label_order_cancelled',
                'name' => __( 'Order Cancelled', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Order Cancelled', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_admin_action',
                'name' => __( 'Admin Action', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Admin Action', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_reviews_exp',
                'name' => __( 'Reviews', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Reviews', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_registration_exp',
                'name' => __( 'Registration', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Registration', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_points_exp',
                'name' => __( 'Target - Points', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Target achieved - Points collected', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_amount_spent_exp',
                'name' => __( 'Target - Total Amount', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Target achieved - Total spend amount', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_num_of_orders_exp',
                'name' => __( 'Target - Total Orders', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Target achieved - Total Orders', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_expired_points',
                'name' => __( 'Expired Points', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Expired Points', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_order_refund',
                'name' => __( 'Order Refund', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Order Refund', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_refund_deleted',
                'name' => __( 'Order Refund Deleted', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Order Refund Deleted', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'   => 'label_redeemed_points',
                'name' => __( 'Redeemed Points', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Redeemed Points for order', 'yith-woocommerce-points-and-rewards')
            ),


            array(
                'id'   => 'label_apply_discounts',
                'name' => __( 'Apply Discount Button', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'text',
                'std'  => __('Apply Discount', 'yith-woocommerce-points-and-rewards')
            ),


	        array( 'type' => 'close' ),
        )
    )
);

return apply_filters( 'yith_ywpar_panel_labels_options', $labels );