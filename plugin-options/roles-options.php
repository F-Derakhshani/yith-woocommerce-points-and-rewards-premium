<?php

$r = yith_ywpar_get_roles();

$rewards_method = YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) ? YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) : 'fixed';
$roles = array(

    'roles' => array(

        'header'    => array(

            array(
                'name' => __( 'Role Settings', 'yith-woocommerce-points-and-rewards' ),
                'type' => 'title'
            ),

            array( 'type' => 'close' )
        )

    )
);

$roles['roles']['roles'][] = array( 'type' => 'open' );
//@since 1.1.3
$roles['roles']['roles'] [] =  array(
    'id'       => 'user_role_enabled',
    'name'     => __( 'User Role enabled to earn points', 'yith-woocommerce-points-and-rewards' ),
    'desc'     => '',
    'type'     => 'chosen',
    'multiple' => true,
    'class'    => 'yith-ywpar-chosen',
    'css'      => 'min-width:300px',
    'std'      => 'all',
    'options'  => array_merge( array( 'all' => __( 'All', 'yith-woocommerce-points-and-rewards' ) ), yith_ywpar_get_roles() ),
);

$roles['roles']['roles'][] = array(
    'id'   => 'enable_conversion_rate_for_role',
    'name' => __( 'Enable Conversion Rate for Points by Roles', 'yith-woocommerce-points-and-rewards' ),
    'desc' => '',
    'type' => 'on-off',
    'std'  => 'no'
);
$roles['roles']['roles'][] = array(
    'id'   => 'conversion_rate_level',
    'name' => __( 'Priority Level Conversion', 'yith-woocommerce-points-and-rewards' ),
    'desc' => '',
    'type' => 'select',
    'options' => array(
        'low'  => __( 'Use the rule with Lowest Conversion Rate', 'yith-woocommerce-points-and-rewards' ),
        'high' => __( 'Use the rule with Highest Conversion Rate', 'yith-woocommerce-points-and-rewards' ),
    ),
    'std'  => 'high'
);
foreach ( $r as $key => $role ) {

    $roles['roles']['roles'][] = array(
        'id'   => 'earn_points_role_' . $key,
        'name' => sprintf( __( 'Conversion Rate for Points earned by %s', 'yith-woocommerce-points-and-rewards' ), $role ),
        'desc' => '',
        'type' => 'options-conversion',
        'std'  => ''
    );
}
$roles['roles']['roles'][] = array( 'type' => 'close' );
$roles['roles']['roles'][] = array( 'type' => 'open' );

$roles['roles']['roles'] [] =  array(
    'id'       => 'user_role_redeem_enabled',
    'name'     => __( 'User Role enabled to redeem points', 'yith-woocommerce-points-and-rewards' ),
    'desc'     => '',
    'type'     => 'chosen',
    'multiple' => true,
    'class'    => 'yith-ywpar-chosen',
    'css'      => 'min-width:300px',
    'std'      => 'all',
    'options'  => array_merge( array( 'all' => __( 'All', 'yith-woocommerce-points-and-rewards' ) ), yith_ywpar_get_roles() ),
);

$roles['roles']['roles'][] = array(
    'id'   => 'rewards_points_for_role',
    'name' => __( 'Enable Reward Points by Roles', 'yith-woocommerce-points-and-rewards' ),
    'desc' => '',
    'type' => 'on-off',
    'std'  => 'no'
);
$roles['roles']['roles'][] = array(
    'id'   => 'rewards_points_level',
    'name' => __( 'Priority Level for Reward Points', 'yith-woocommerce-points-and-rewards' ),
    'desc' => '',
    'type' => 'select',
    'options' => array(
        'low'  => __( 'Use the rule with Lowest Reward Points', 'yith-woocommerce-points-and-rewards' ),
        'high' => __( 'Use the rule with Highest Reward Points', 'yith-woocommerce-points-and-rewards' ),
    ),
    'std'  => 'high'
);
foreach ( $r as $key => $role ) {
    if(  $rewards_method == 'fixed'){
        $roles['roles']['roles'][] = array(
            'id'   => 'rewards_points_role_' . $key,
            'name' => sprintf( __( 'Reward Points for %s', 'yith-woocommerce-points-and-rewards' ), $role ),
            'desc' => '',
            'type' => 'options-conversion',
            'std'     => array(
                'points' => 100,
                'money'  => 1
            )
        );
    }else{
        $roles['roles']['roles'][] = array(
            'id'   => 'rewards_points_percentual_role_' . $key,
            'name' => sprintf( __( 'Reward Points for %s', 'yith-woocommerce-points-and-rewards' ), $role ),
            'desc' => '',
            'type' => 'options-percentual-conversion',
            'std'     => array(
                'points' => 20,
                'discount'  => 5
            )
        );
    }

}
$roles['roles']['roles'][] = array( 'type' => 'close' );
return apply_filters( 'yith_ywpar_panel_settings_options', $roles );