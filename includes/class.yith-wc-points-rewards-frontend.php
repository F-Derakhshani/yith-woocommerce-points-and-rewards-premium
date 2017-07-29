<?php

if ( ! defined ( 'ABSPATH' ) || ! defined ( 'YITH_YWPAR_VERSION' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements features of YITH WooCommerce Points and Rewards Frontend
 *
 * @class   YITH_WC_Points_Rewards_Frontend
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */
if ( ! class_exists ( 'YITH_WC_Points_Rewards_Frontend' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Frontend
	 */
	class YITH_WC_Points_Rewards_Frontend {

        /**
         * Single instance of the class
         *
         * @var \YITH_WC_Points_Rewards_Frontend
         */
        protected static $instance;

        /**
         * Returns single instance of the class
         *
         * @return \YITH_WC_Points_Rewards_Frontend
         * @since 1.0.0
         */
        public static function get_instance () {
            if ( is_null ( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor
         *
         * Initialize plugin and registers actions and filters to be used
         *
         * @since  1.0.0
         * @author Emanuela Castorina
         */
        public function __construct () {

	        add_shortcode( 'ywpar_my_account_points', array( $this, 'shortcode_my_account_points' ) );

	        if ( ! YITH_WC_Points_Rewards()->is_enabled() ) {
		        return;
	        }

	        if ( YITH_WC_Points_Rewards()->get_option( 'hide_point_system_to_guest' ) == 'yes' && ! is_user_logged_in() ) {
		        return;
	        }

	        add_shortcode( 'yith_points_product_message', array( $this, 'show_single_product_message' ) );

	        add_action( 'template_redirect', array( $this, 'show_messages' ), 30 );

	        if (  ! YITH_WC_Points_Rewards()->is_user_enabled()  ) {
		        return;
	        }

	        add_action( 'init', array( $this, 'init' ) );

	        if ( YITH_WC_Points_Rewards()->get_option( 'enabled_cart_message' ) == 'yes' ) {
		        add_action( 'wc_ajax_ywpar_update_cart_messages', array( $this, 'print_cart_message' ) );
	        }


        }


		/**
		 * Show messages on
		 */
		public function show_messages() {

		    if ( apply_filters( 'ywpar_enable_points_upon_sales', YITH_WC_Points_Rewards()->get_option( 'enable_points_upon_sales', 'yes' ) == 'yes' ) ) {


			    if ( YITH_WC_Points_Rewards()->get_option( 'enabled_single_product_message' ) == 'yes' ) {
				    $this->show_single_product_message_position();
			    }

			    if ( YITH_WC_Points_Rewards()->get_option( 'enabled_loop_message' ) == 'yes' ) {
				    $this->show_single_loop_position();
			    }


			    if ( YITH_WC_Points_Rewards()->is_user_enabled() ) {
				    if ( YITH_WC_Points_Rewards()->get_option( 'enabled_cart_message' ) == 'yes' ) {
					    add_action( 'woocommerce_before_cart', array( $this, 'print_messages_in_cart' ) );
					    add_action( 'wc_ajax_ywpar_update_cart_messages', array( $this, 'print_cart_message' ) );
				    }

				    if ( YITH_WC_Points_Rewards()->get_option( 'enabled_checkout_message' ) == 'yes' ) {
					    add_action( 'woocommerce_before_checkout_form', array( $this, 'print_messages_in_cart' ) );
					    add_action( 'before_woocommerce_pay', array( $this, 'print_messages_in_order_pay' ) );
				    }
			    }

		    }
	    }


		/**
		 *
		 */
		public function init(){

			add_filter( 'woocommerce_available_variation', array( $this, 'add_params_to_available_variation' ), 10, 3 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

			if (  ! YITH_WC_Points_Rewards()->is_user_enabled()  ) {
				return;
			}

	        if ( YITH_WC_Points_Rewards()->get_option( 'show_point_list_my_account_page' ) == 'yes' ) {
		        add_action( 'woocommerce_before_my_account', array( $this, 'my_account_points' ) );
	        }

            /** REDEEM  */
            if ( YITH_WC_Points_Rewards()->get_option( 'enabled_rewards_cart_message' ) == 'yes' && YITH_WC_Points_Rewards()->is_user_enabled('redeem') ) {
                add_action( 'woocommerce_before_cart', array( $this, 'print_rewards_message_in_cart' ) );
                add_action( 'wc_ajax_ywpar_update_cart_rewards_messages', array( $this, 'print_rewards_message' ) );
                add_action( 'woocommerce_before_checkout_form', array( $this, 'print_rewards_message_in_cart' ) );
            }

        }

        /**
         * Enqueue Scripts and Styles
         *
         * @return void
         * @since  1.0.0
         * @author Emanuela Castorina
         */
        public function enqueue_styles_scripts () {

            wp_enqueue_script ( 'ywpar_frontend', YITH_YWPAR_ASSETS_URL . '/js/frontend' . YITH_YWPAR_SUFFIX . '.js', array ( 'jquery', 'wc-add-to-cart-variation' ), YITH_YWPAR_VERSION, true );
            wp_enqueue_style ( 'ywpar_frontend', YITH_YWPAR_ASSETS_URL . '/css/frontend.css' );

            $script_params = array(
                'ajax_url'                                 => admin_url( 'admin-ajax' ).'.php',
                'wc_ajax_url'                              => WC_AJAX::get_endpoint( "%%endpoint%%" ),
            ) ;

            wp_localize_script( 'ywpar_frontend', 'yith_wpar_general', $script_params );
            
        }

	    /**
	     * Add message in single product page
	     *
	     * @since   1.0.0
	     * @author  Emanuela Castorina
	     *
	     * @param $atts
	     *
	     * @return string
	     */
        public function show_single_product_message( $atts ) {

            $atts = shortcode_atts(array(
                'product_id' => 0,
            ), $atts );

            extract( $atts );

            if( ! intval( $product_id ) ) {
                global $product;
            }else {
                $product = wc_get_product( intval( $product_id ) );
            }

            if( ! $product ){
            	return '';
            }

	        $message    = YITH_WC_Points_Rewards()->get_option( 'single_product_message' );
	        $singular   = YITH_WC_Points_Rewards()->get_option( 'points_label_singular' );
	        $plural     = YITH_WC_Points_Rewards()->get_option( 'points_label_plural' );
	        $product_id = $product->get_id();

          //  $product_points = YITH_WC_Points_Rewards_Earning()->calculate_product_points( $product_id );
	        if( ! $product->is_type('variable')  ){
		        $product_points = YITH_WC_Points_Rewards_Earning()->calculate_product_points( $product_id );

	        }else{
		        $product_points = YITH_WC_Points_Rewards_Earning()->calculate_product_points_on_variable( $product_id );
	        }


	        $price_discount_conversion = YITH_WC_Points_Rewards_Redemption()->calculate_price_worth( $product_id, $product_points );

            if ( $product_points == 0 ) {
                return '';
            }

            $message = str_replace( '{points}', '<span class="product_point">'.$product_points.'</span>', $message );
            $message = str_replace( '{price_discount_fixed_conversion}', '<span class="product-point-conversion">'.$price_discount_conversion.'</span>', $message );

            if ( $product_points > 1 ) {
                $message = str_replace( '{points_label}', $plural, $message );
            } else {
                $message = str_replace( '{points_label}', $singular, $message );
            }

            $class = 'hide';
            if( $product->is_type('variable')  ){
            	$m = apply_filters( 'ywpar_point_message_single_page', '<div class="yith-par-message '. $class .'">' . $message . '</div><div class="yith-par-message-variation '. $class .'">'.  $message .'</div>', $product, $class );
            }else{
            	$m = apply_filters( 'ywpar_point_message_single_page', '<div class="yith-par-message">' . $message . '</div>', $product, $class );
            }
            return $m;
        }

        /**
         * Print single product message
         *
         * @author Francesco Licandro
         */
        public function print_single_product_message(){
            echo do_shortcode( '[yith_points_product_message]' );
        }

        /**
         * Set the position where display the message in single product
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function show_single_product_message_position () {
            //Table Pricing
            $position = YITH_WC_Points_Rewards ()->get_option ( 'single_product_message_position' );

            $priority_single_excerpt     = has_action ( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt' );

            switch ( $position ) {
                case 'before_add_to_cart':
                    add_action ( 'woocommerce_before_add_to_cart_form', array ( $this, 'print_single_product_message' ) );
                    break;
                case 'after_add_to_cart':
                    add_action ( 'woocommerce_after_add_to_cart_form', array ( $this, 'print_single_product_message' ));
                    break;
                case 'before_excerpt':
                    if ( $priority_single_excerpt ) {
                        add_action ( 'woocommerce_single_product_summary', array ( $this, 'print_single_product_message' ), $priority_single_excerpt - 1 );
                    } else {
                        add_action ( 'woocommerce_single_product_summary', array ( $this, 'print_single_product_message' ), 18 );
                    }
                    break;
                case 'after_excerpt':
                    if ( $priority_single_excerpt ) {
                        add_action ( 'woocommerce_single_product_summary', array ( $this, 'print_single_product_message' ), $priority_single_excerpt + 1 );
                    } else {
                        add_action ( 'woocommerce_single_product_summary', array ( $this, 'print_single_product_message' ), 22 );
                    }
                    break;
                case 'after_meta':
                    $priority_after_meta = has_action ( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta' );
                    if ( $priority_after_meta ) {
                        add_action ( 'woocommerce_single_product_summary', array ( $this, 'print_single_product_message' ), $priority_after_meta + 1 );
                    } else {
                        add_action ( 'woocommerce_single_product_summary', array ( $this, 'print_single_product_message' ), 42 );
                    }
                    break;
                default:
                    break;
            }

        }

        /**
         * Set the position where display the message in loop
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function show_single_loop_position () {
            //Table Pricing
            $position = apply_filters('ywpar_loop_position', 'woocommerce_after_shop_loop_item_title' );
            $priority = apply_filters('ywpar_loop_position_priority', 11 );
            add_action( $position, array( $this, 'print_messages_in_loop'), $priority );

        }

        /**
         * Print a message in loop
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function print_messages_in_loop () {

            global $product;
	        $message    = YITH_WC_Points_Rewards()->get_option( 'loop_message' );
	        $singular   = YITH_WC_Points_Rewards()->get_option( 'points_label_singular' );
	        $plural     = YITH_WC_Points_Rewards()->get_option( 'points_label_plural' );
	        $product_id = $product->get_id();

            if( ! $product->is_type('variable')  ){
                $product_points = YITH_WC_Points_Rewards_Earning()->calculate_product_points( $product_id );

                if ( $product_points == 0 ) {
                    return;
                }

                $product_discount = YITH_WC_Points_Rewards_Redemption()->calculate_product_discounts( $product_id );
                $price_discount_conversion = ( $product_discount ) ? wc_price( $product_discount ) : '';

            }else{
                $product_points = YITH_WC_Points_Rewards_Earning()->calculate_product_points_on_variable( $product_id );

                if ( $product_points == 0 ) {
                    return;
                }

                $product_discount = YITH_WC_Points_Rewards_Redemption()->calculate_product_discounts_on_variable( $product_id );
                $price_discount_conversion = !empty( $product_discount ) ? $product_discount : '';
            }



            $message = str_replace( '{points}', '<span class="product_point_loop">'.$product_points.'</span>', $message );

            if( ! empty( $price_discount_conversion ) ){
                $message = str_replace( '{price_discount_fixed_conversion}', '<span class="product-point-conversion">'.$price_discount_conversion.'</span>', $message );
            }else{
                $message = str_replace( '{price_discount_fixed_conversion}', '', $message );
            }


            if ( $product_points > 1 ) {
                $message = str_replace( '{points_label}', $plural, $message );
            } else {
                $message = str_replace( '{points_label}', $singular, $message );
            }

            echo apply_filters( 'ywpar_single_product_message_in_loop', '<div  class="yith-par-message">' . $message . '</div>', $product );

        }

        /**
         * Print a message in cart/checkout page
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function print_messages_in_cart () {

            $message = $this->get_cart_message( false );
	        if( !empty($message)){
		        printf ( '<div id="yith-par-message-cart" class="woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-info">%s</div>', $message );
	        }


        }

	    /**
	     * @since   1.1.3
	     * @author  Andrea Frascaspata
	     *
	     * @param int $total_points
	     *
	     * @return mixed|void
	     */
        private function get_cart_message( $total_points = 0 ) {

            $page = 'cart';

            if ( is_checkout () ) {
                $page = 'checkout';
            }

	        $message  = YITH_WC_Points_Rewards()->get_option( $page . '_message' );
	        $singular = YITH_WC_Points_Rewards()->get_option( 'points_label_singular' );
	        $plural   = YITH_WC_Points_Rewards()->get_option( 'points_label_plural' );

            if ( $total_points == 0 ) {
	            $total_points = YITH_WC_Points_Rewards_Earning ()->calculate_points_on_cart ();
	            if( $total_points == 0 ){
		            return;
	            }
            }

            $message = str_replace ( '{points}', $total_points, $message );
            if ( $total_points > 1 ) {
                $message = str_replace ( '{points_label}', $plural, $message );
            } else {
                $message = str_replace ( '{points_label}', $singular, $message );
            }

            return $message;

        }

        /**
         * @since   1.1.3
         * @author  Andrea Frascaspata
         */
        public function print_cart_message() {
           echo $this->get_cart_message();
           die;
        }

	     /**
         * @since   1.1.3
         * @author  Andrea Frascaspata
         */
        public function print_messages_in_order_pay() {

	        if( isset( $_GET['key'] ) ){
	        	$order_id = wc_get_order_id_by_order_key( $_GET['key'] );
		        if( $order_id ){
		        	$points = get_post_meta( $order_id, 'ywpar_points_from_cart', true);
		        }
		        $message = $this->get_cart_message( $points );
		        if( !empty( $message ) ){
			        printf ( '<div id="yith-par-message-cart" class="woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-info">%s</div>', $message );
		        }
	        }

        }


        /**
         * Print rewards message in cart/checkout page
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
	    public function print_rewards_message_in_cart() {

		    $coupons = WC()->cart->get_applied_coupons();

		    if ( YITH_WC_Points_Rewards_Redemption()->check_coupon_is_ywpar( $coupons ) ) {
			    return '';
		    }

		    $message = $this->get_rewards_message();

		    if ( $message ) {
			    printf( '<div id="yith-par-message-reward-cart" class="woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-info">%s</div>', $message );
		    }

	    }

        /**
         * @since   1.1.3
         * @author  Andrea Frascaspata
         * @return mixed|string|void
         */
	    private function get_rewards_message() {

		    if ( is_user_logged_in() ) {

			    $message = YITH_WC_Points_Rewards()->get_option( 'rewards_cart_message' );
			    $plural  = YITH_WC_Points_Rewards()->get_option( 'points_label_plural' );

			    $max_discount            = YITH_WC_Points_Rewards_Redemption()->calculate_rewards_discount();
			    $minimum_amount          = YITH_WC_Points_Rewards()->get_option( 'minimum_amount_to_redeem' );
			    $max_percentual_discount = YITH_WC_Points_Rewards_Redemption()->get_max_percentual_discount();

			    if ( $minimum_amount != 0 && WC()->cart->subtotal < $minimum_amount ) {
				    return;
			    }

			    if ( $max_discount ) {

				    $max_points = YITH_WC_Points_Rewards_Redemption()->get_max_points();

				    if ( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'fixed' ) {

				    	$minimum_discount_amount = YITH_WC_Points_Rewards()->get_option( 'minimum_amount_discount_to_redeem' );

				    	if ( ! empty( $minimum_discount_amount ) && $max_discount < $minimum_discount_amount ) {
						    return '';
					    }

					    $message = str_replace( '{points_label}', $plural, $message );
					    $message = str_replace( '{max_discount}', wc_price( $max_discount ), $message );
					    $message = str_replace( '{points}', $max_points, $message );
					    $message .= ' <a class="ywpar-button-message">' . YITH_WC_Points_Rewards()->get_option( 'label_apply_discounts' ) . '</a>';
					    $message .= '<div class="clear"></div><div class="ywpar_apply_discounts_container"><form class="ywpar_apply_discounts" method="post">' . wp_nonce_field( 'ywpar_apply_discounts', 'ywpar_input_points_nonce' ) . '
                                    <input type="hidden" name="ywpar_points_max" value="' . $max_points . '">
                                    <input type="hidden" name="ywpar_max_discount" value="' . $max_discount . '">
                                    <input type="hidden" name="ywpar_rate_method" value="fixed">
                                    <p class="form-row form-row-first">
                                        <input type="text" name="ywpar_input_points" class="input-text" placeholder="' . $max_points . '" id="ywpar-points-max" value="' . $max_points . '">
                                        <input type="hidden" name="ywpar_input_points_check" id="ywpar_input_points_check" value="0">
                                    </p>
                                    <p class="form-row form-row-last">
                                        <input type="submit" class="button" name="ywpar_apply_discounts" id="ywpar_apply_discounts" value="' . YITH_WC_Points_Rewards()->get_option( 'label_apply_discounts' ) . '">
                                    </p>
                                    <div class="clear"></div>
                                </form></div>';

					    return $message;
				    } elseif ( YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) == 'percentage' ) {
					    $message = str_replace( '{points_label}', $plural, $message );
					    $message = str_replace( '{max_discount}', wc_price( $max_discount ), $message );
					    $message = str_replace( '{max_percentual_discount}', $max_percentual_discount . '%', $message );
					    $message = str_replace( '{points}', $max_points, $message );
					    $message .= ' <a class="ywpar-button-message ywpar-button-percentage-discount">' . YITH_WC_Points_Rewards()->get_option( 'label_apply_discounts' ) . '</a>';
					    $message .= '<div class="ywpar_apply_discounts_container"><form class="ywpar_apply_discounts" method="post">' . wp_nonce_field( 'ywpar_apply_discounts', 'ywpar_input_points_nonce' ) . '
                                     <input type="hidden" name="ywpar_points_max" value="' . $max_points . '">
                                     <input type="hidden" name="ywpar_max_discount" value="' . $max_discount . '">
                                     <input type="hidden" name="ywpar_rate_method" value="percentage">';
					    $message .= '</form></div>';

					    return $message;
				    }
			    }

		    }

	    }

        /**
         * @since   1.1.3
         * @author  Andrea Frascaspata
         */
        public function print_rewards_message() {

            echo $this->get_rewards_message();

        }

        /**
         * Shortcode my account points
         *
         * @since 1.1.3
         * @author Francesco Licandro
         * return string
         */
        public function shortcode_my_account_points() {
        	if( ! YITH_WC_Points_Rewards()->is_enabled() || ! YITH_WC_Points_Rewards()->is_user_enabled() ){
        		return '';
	        }

            ob_start();
            wc_get_template ( 'myaccount/my-points-view.php' );
            return ob_get_clean();
        }


        /**
         * Add points section to my-account page
         *
         * @since   1.0.0
         * @author  Emanuela Castorina
         * @return  void
         */
        public function my_account_points () {
            echo do_shortcode('[ywpar_my_account_points]');
        }

        /**
         * Add custom params to variations
         *
         * @access public
         *
         * @param $args      array
         * @param $product   object
         * @param $variation object
         *
         * @return array
         * @since  1.1.1
         */
        public function add_params_to_available_variation( $args, $product, $variation ) {

        	if( $variation ){
		        $args['variation_points'] = YITH_WC_Points_Rewards_Earning()->calculate_product_points( $variation );
		        $args['variation_price_discount_fixed_conversion'] = YITH_WC_Points_Rewards_Redemption()->calculate_price_worth( $variation->get_id(), $args['variation_points'] );
	        }

            return $args;
        }

    }


}

/**
 * Unique access to instance of YITH_WC_Points_Rewards_Frontend class
 *
 * @return \YITH_WC_Points_Rewards_Frontend
 */
function YITH_WC_Points_Rewards_Frontend () {
    return YITH_WC_Points_Rewards_Frontend::get_instance ();
}

