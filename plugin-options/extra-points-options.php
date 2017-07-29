<?php

$extra_points_options = array(

    'extra-points' => array(

        'header'    => array(

            array(
                'name' => __( 'Settings for Extra Points', 'yith-woocommerce-points-and-rewards' ),
                'type' => 'title'
            ),

            array( 'type' => 'close' )
        ),


        'settings' => array(

            array( 'type' => 'open' ),

            array(
                'id'      => 'extra_points',
                'name'    => '',
                'desc'    => '',
                'type'    => 'options-extra-points'
            ),

            array( 'type' => 'close' ),
        )
    )
);

return apply_filters( 'yith_ywpar_panel_extra_points_options', $extra_points_options );