<?php

$update = array(

    'update' => array(

        'header'    => array(

            array(
                'name' => __( 'Update Point Options', 'yith-woocommerce-points-and-rewards' ),
                'type' => 'title',
                'hide_sidebar' => true
            ),

            array( 'type' => 'close' )
        ),


        'update' => array(

            array( 'type' => 'open' ),


            array(
                'id'      => 'enable_update_point_email',
                'name'    => __( 'Enable email notification each time points are updated', 'yith-woocommerce-points-and-rewards' ),
                'desc'    => '',
                'type'    => 'on-off',
                'std'     => 'no'
            ),


            array(
                'id'   => 'update_point_email_content',
                'name' => __( 'Email content', 'yith-woocommerce-points-and-rewards' ),
                'desc' => sprintf( '%s {username} = %s {latest_updates} = %s {total_points} = %s %s ',
                    __( 'You can use the following placeholders', 'yith-woocommerce-points-and-rewards' ),
                    __( "customer's username", 'yith-woocommerce-points-and-rewards' ),
                    __( 'latest point updates', 'yith-woocommerce-points-and-rewards' ),
                    __( 'label for points', 'yith-woocommerce-points-and-rewards' ),
                    __( 'current point credit', 'yith-woocommerce-points-and-rewards' ) ),

                'type' => 'textarea',
                'std'  => __('Hi {username}, you can find below latest updates about your {label_points}. {latest_updates} Total is &eacute; {total_points}.', 'yith-woocommerce-points-and-rewards')
            ),

            array( 'type' => 'close' ),
        )
    )
);

return apply_filters( 'yith_ywpar_panel_update_options', $update );