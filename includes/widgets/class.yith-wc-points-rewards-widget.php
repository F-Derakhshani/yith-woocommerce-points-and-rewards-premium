<?php
if ( !defined( 'ABSPATH' ) || ! defined( 'YITH_YWPAR_VERSION' ) ) {
    exit; // Exit if accessed directly
}

/**
 * YITH_YWPAR_Points_Rewards_Widget add a widget to YITH WooCommerce Points and Rewards
 *
 * @class 	YITH_YWPAR_Points_Rewards_Widget
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */

if( !class_exists( 'YITH_YWPAR_Points_Rewards_Widget' ) ) {
    /**
     * YITH YWPAR Points Rewards Widget
     *
     * @since 1.0.0
     */
    class YITH_YWPAR_Points_Rewards_Widget extends WP_Widget {


        /**
         * constructor
         *
         * @access public
         */
        function __construct() {

            /* Widget variable settings. */
            $this->woo_widget_cssclass = 'woocommerce widget_ywpar_points_rewards';
            $this->woo_widget_description = __( 'Show points collected by the user so far', 'yith-woocommerce-points-and-rewards' );
            $this->woo_widget_idbase = 'yith_ywpar_points_rewards';
            $this->woo_widget_name = __( 'YITH WooCommerce Points And Rewards - Point Credit', 'yith-woocommerce-points-and-rewards' );


            /* Widget settings. */
            $widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

            /* Create the widget. */
            parent::__construct('widget_ywpar_points_rewards', $this->woo_widget_name, $widget_ops);

        }


        /**
         * widget function.
         *
         * @see WP_Widget
         * @access public
         * @param array $args
         * @param array $instance
         * @return void
         */
        function widget( $args, $instance ) {
            extract($args);

            if( !is_user_logged_in() ){
                return;
            }

            $this->istance = $instance;
            $title = isset( $instance['title'] ) ? $instance['title'] : '';
            $title = apply_filters('widget_title', $title, $instance, $this->id_base);

            $label = isset( $instance['label'] ) ? $instance['label'] : '';

            if( ! YITH_WC_Points_Rewards()->is_user_enabled()  ){
            	return;
			}

            echo $before_widget;

            if ($title) echo $before_title . $title . $after_title;

            $points   = get_user_meta( get_current_user_id(), '_ywpar_user_total_points', true );
            $points   = ( $points == '' ) ? 0 : $points;
            $singular = YITH_WC_Points_Rewards()->get_option( 'points_label_singular' );
            $plural   = YITH_WC_Points_Rewards()->get_option( 'points_label_plural' );

            echo '<p>'. $label .' ';
            printf( _n( '<strong>%s</strong> '.$singular, '<strong>%s</strong> '.$plural, $points,  'yith-woocommerce-points-and-rewards' ), $points );
            echo '</p>';

            echo $after_widget;

        }

        /**
         * update function.
         *
         * @see WP_Widget->update
         * @access public
         * @param array $new_instance
         * @param array $old_instance
         * @return array
         */
        function update( $new_instance, $old_instance ) {
            $instance['title'] = strip_tags( stripslashes( $new_instance['title'] ) );
            $instance['label'] = stripslashes( $new_instance['label'] );

            $this->istance = $instance;
            return $instance;
        }

        /**
         * form function.
         *
         * @see WP_Widget->form
         * @access public
         * @param array $instance
         * @return void
         */
        function form( $instance ) {
            $defaults = array(
                'title'           => __( 'My Points', 'yith-woocommerce-points-and-rewards' ),
                'label'  => __( 'Your credit is ', 'yith-woocommerce-points-and-rewards' ),
            );

            $instance = wp_parse_args( (array) $instance, $defaults ); ?>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'yith-woocommerce-points-and-rewards' ) ?></label>
                <input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" />
            </p>

            <p>
                <label for="<?php echo $this->get_field_id('label'); ?>"><?php _e('Label:', 'yith-woocommerce-points-and-rewards' ) ?></label>
                <input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('label') ); ?>" name="<?php echo esc_attr( $this->get_field_name('label') ); ?>" value="<?php if (isset ( $instance['label'])) {echo esc_attr( $instance['label'] );} ?>" />
            </p>

        <?php
        }


    }
}