<?php

$messages = array(

    'messages' => array(

        'header'    => array(

            array(
                'name' => __( 'Messages', 'yith-woocommerce-points-and-rewards' ),
                'type' => 'title'
            ),

            array( 'type' => 'close' )
        ),


        'messages' => array(

            array( 'type' => 'open' ),


            array(
                'id'      => 'enabled_single_product_message',
                'name'    => __( 'Enable Single Product Message', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'yes'
            ),

            array(
                'id'      => 'single_product_message_position',
                'name'    => __( 'Message position', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'select',
                'options' => array(
                    'before_add_to_cart' => __( 'Before "Add to cart" button', 'yith-woocommerce-points-and-rewards' ),
                    'after_add_to_cart'  => __( 'After "Add to cart" button', 'yith-woocommerce-points-and-rewards' ),
                    'before_excerpt'     => __( 'Before excerpt', 'yith-woocommerce-points-and-rewards' ),
                    'after_excerpt'      => __( 'After excerpt', 'yith-woocommerce-points-and-rewards' ),
                    'after_meta'         => __( 'After product meta', 'yith-woocommerce-points-and-rewards' ),

                ),
                'std'     => 'before_add_to_cart',
            ),

            //@since 1.1.3
            array(
                'id'   => 'single_product_message',
                'name' => __( 'Single Product Page Message', 'yith-woocommerce-points-and-rewards' ),
                'desc' => __( '{points} number of points earned;<br>{points_label} label of points;<br>{price_discount_fixed_conversion} the value corresponding to points ', 'yith-woocommerce-points-and-rewards' ),
                'type' => 'textarea',
                'std'  => __('If you purchase this product you will earn <strong>{points}</strong> {points_label}! Worth {price_discount_fixed_conversion}!', 'yith-woocommerce-points-and-rewards')
            ),

            //@since 1.1.3
            array(
                'id'      => 'enabled_loop_message',
                'name'    => __( 'Show Message in Loop', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'no'
            ),

            array(
                'id'   => 'loop_message',
                'name' => __( 'Loop Message', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '{points} number of points earned;<br>{points_label} label of points;<br>{price_discount_fixed_conversion} the value corresponding to points',
                'type' => 'textarea',
                'std'  => __('<strong>{points}</strong> {points_label}', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'      => 'enabled_cart_message',
                'name'    => __( 'Show Message in Cart', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'yes'
            ),

            array(
                'id'   => 'cart_message',
                'name' => __( 'Cart Message', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'textarea',
                'std'  => __('If you proceed to checkout, you will earn <strong>{points}</strong> {points_label}!', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'      => 'enabled_checkout_message',
                'name'    => __( 'Show Message in Checkout', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'yes'
            ),

            array(
                'id'   => 'checkout_message',
                'name' => __( 'Checkout Message', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'textarea',
                'std'  => __('If you proceed to checkout, you will earn <strong>{points}</strong> {points_label}!', 'yith-woocommerce-points-and-rewards')
            ),

            array(
                'id'      => 'enabled_rewards_cart_message',
                'name'    => __( 'Show Reward Message in Cart/Checkout', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'yes'
            ),

            array(
                'id'   => 'rewards_cart_message',
                'name' => __( 'Rewards Message in Cart/Checkout Page', 'yith-woocommerce-points-and-rewards' ),
                'desc' => '',
                'type' => 'textarea',
                'std'  => __('Use <strong>{points}</strong> {points_label} for a <strong>{max_discount}</strong> discount on this order!', 'yith-woocommerce-points-and-rewards')
            ),



            array( 'type' => 'close' ),
        )
    )
);

return apply_filters( 'yith_ywpar_panel_messages_options', $messages );