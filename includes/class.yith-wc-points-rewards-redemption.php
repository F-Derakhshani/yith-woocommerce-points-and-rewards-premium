<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWPAR_VERSION' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Implements features of YITH WooCommerce Points and Rewards
 *
 * @class   YITH_WC_Points_Rewards_Redemption
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */
if ( ! class_exists( 'YITH_WC_Points_Rewards_Redemption' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Redemption
	 */
	class YITH_WC_Points_Rewards_Redemption {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WC_Points_Rewards_Redemption
		 */
		protected static $instance;

		/**
		 * @var string
		 */
		protected $label_coupon_prefix = 'ywpar_discount';

		/**
		 * @var string
		 */
		protected $coupon_type = 'fixed_cart';
		/**
		 * @var string
		 */
		protected $current_coupon_code = '';

		/**
		 * @var int
		 */
		protected $max_points = 0;

		/**
		 * @var int
		 */
		protected $max_discount = 0;

		/**
		 * @var int
		 */
		protected $max_percentual_discount = 0;

		/**
		 * @var array
		 */
		protected $args = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WC_Points_Rewards_Redemption
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
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
		public function __construct() {

			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'add_order_meta' ), 10 );

			//remove points if are used in order
			if ( version_compare( WC()->version, '2.7', '<' ) ) {
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'deduce_order_points' ) );
			}else{
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'deduce_order_points' ), 20);
				add_action( 'woocommerce_removed_coupon', array( $this, 'clear_current_coupon' ) );
				add_action( 'woocommerce_checkout_create_order', array( $this, 'clear_ywpar_coupon_after_create_order' ) );
			}

			add_action( 'wp_loaded', array( $this, 'apply_discount' ), 30 );
			add_action( 'woocommerce_cart_item_removed', array( $this, 'update_discount' ) );
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'update_discount' ), 99 );
			
			//remove point when the order is cancelled
			if ( YITH_WC_Points_Rewards()->get_option( 'remove_point_order_deleted' ) == 'yes' ) {
				add_action( 'woocommerce_order_status_cancelled', array( $this, 'remove_redeemed_order_points' ) );
				add_action( 'woocommerce_order_status_cancelled_to_completed', array(
					$this,
					'add_redeemed_order_points'
				) );
				add_action( 'woocommerce_order_status_cancelled_to_processing', array(
					$this,
					'add_redeemed_order_points'
				) );
			}

			//remove point when the order is refunded
			if( YITH_WC_Points_Rewards()->get_option( 'reassing_redeemed_points_refund_order' ) == 'yes' ){
				add_action( 'woocommerce_order_fully_refunded', array( $this, 'remove_redeemed_order_points' ), 11, 2 );
				add_action( 'wp_ajax_nopriv_woocommerce_delete_refund', array( $this, 'add_redeemed_order_points' ), 9, 2 );
				add_action( 'wp_ajax_woocommerce_delete_refund', array( $this, 'add_redeemed_order_points' ), 9, 2 );
			}

			if ( is_user_logged_in() ) {
				if ( version_compare( WC()->version, '2.7', '<' ) ) {
					add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'create_coupon_discount' ), 15, 2 );
				}
				add_filter( 'woocommerce_coupon_message', array( $this, 'coupon_rewards_message' ), 15, 3 );
				add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'coupon_label' ), 10, 2 );
			}

			add_action( 'wp_loaded', array( $this, 'ywpar_set_cron' ) );
			add_action( 'ywpar_clean_cron', array( $this, 'clear_coupons') );

		}

		public function clear_ywpar_coupon_after_create_order( $order, $data ){
			$coupon_used = $order->get_used_coupons();
			if( $coupon_used ){
				foreach ( $coupon_used as $coupons_code ) {
					$coupon = new WC_Coupon( $coupons_code );
					if( $this->check_coupon_is_ywpar( $coupon ) ){
						$coupon->delete();
					}
				}
			}
		}


		public function clear_current_coupon( $coupon_code ){
			$current_coupon = $this->get_current_coupon();
			if( $current_coupon->is_valid() && $current_coupon->get_code() == $coupon_code ){
				$current_coupon->delete();
			}
		}

		/**
		 * Add the redeemed points when an order is cancelled
		 **
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 *
		 * @param $order_id
		 *
		 * @return void
		 */
		public function remove_redeemed_order_points( $order_id ) {
			$order           = wc_get_order( $order_id );
			$redemped_points = yit_get_prop( $order, '_ywpar_redemped_points', true );

			if ( $redemped_points == '' ) {
				return;
			}

			$customer_user = method_exists($order,'get_customer_id') ? $order->get_customer_id() : yit_get_prop( $order, '_customer_user', true );
			$points        = $redemped_points;
			$action        = ( current_action() == 'woocommerce_order_fully_refunded' ) ? 'order_refund' : 'order_' . $order->get_status();

			if ( $customer_user ) {
				$current_point = get_user_meta( $customer_user, '_ywpar_user_total_points', true );
				$new_point     = $current_point + $points;
				update_user_meta( $customer_user, '_ywpar_user_total_points', $new_point > 0 ? $new_point : 0);
				YITH_WC_Points_Rewards()->register_log( $customer_user, $action, $order_id, $points );
				$order->add_order_note( sprintf( __( 'Added %d %s for order %s.' ), $points, YITH_WC_Points_Rewards()->get_option( 'points_label_plural' ), YITH_WC_Points_Rewards()->get_action_label( $action ) ), 0 );
			}
		}

		/**
		 * Removed the redeemed points when an order changes status from cancelled to complete
		 **
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 *
		 * @param $order_id
		 *
		 * @return void
		 */
		public function add_redeemed_order_points( $order_id ) {
			$order   = wc_get_order( $order_id );
			$redemped_points = yit_get_prop( $order, '_ywpar_redemped_points', true );

			if ( $redemped_points == '' ) {
				return;
			}

			$customer_user = method_exists($order,'get_customer_id') ? $order->get_customer_id() : yit_get_prop( $order, '_customer_user', true );
			$points = $redemped_points;
			$action = 'order_' . $order->get_status();

			if ( $customer_user > 0 ) {
				$current_point = get_user_meta( $customer_user, '_ywpar_user_total_points', true );
				$new_point     = ( $current_point - $points > 0 ) ? ( $current_point - $points ) : 0;
				update_user_meta( $customer_user, '_ywpar_user_total_points', $new_point );
				YITH_WC_Points_Rewards()->register_log( $customer_user, $action, $order_id, - $points );
				$order->add_order_note( sprintf( __( 'Removed %d %s for order %s.' ), - $points, YITH_WC_Points_Rewards()->get_option( 'points_label_plural' ), YITH_WC_Points_Rewards()->get_action_label( $action ) ), 0 );
			}
		}

		/**
		 * Apply the discount to cart after that the user set the number of points
		 **
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 * @return void
		 */
		public function apply_discount() {

			if ( wp_verify_nonce( 'ywpar_input_points_nonce', 'ywpar_apply_discounts' ) || ! is_user_logged_in() || ! isset( $_POST['ywpar_rate_method'] ) || ! isset( $_POST['ywpar_points_max'] ) || ! isset( $_POST['ywpar_max_discount'] ) || ( isset( $_POST['coupon_code'] ) && $_POST['coupon_code'] != '' ) ) {
				return;
			}

			$posted = $_POST;

			$this->apply_discount_calculation( $posted );

		}

		/**
		 * @param      $posted
		 * @param bool $apply_coupon
		 */
		public function apply_discount_calculation( $posted, $apply_coupon = true ) {
			$max_points   = $posted['ywpar_points_max'];
			$max_discount = $posted['ywpar_max_discount'];
			$coupon_label = $this->get_coupon_code_prefix();
			$discount     = 0;

			if ( $posted['ywpar_rate_method'] == 'fixed' ) {

				if ( ! isset( $posted['ywpar_input_points_check'] ) || $posted['ywpar_input_points_check'] == 0 ) {
					return;
				}

				$input_points = $posted['ywpar_input_points'];

				if ( $input_points == 0 ) {
					return;
				}

				$input_points       = ( $input_points > $max_points ) ? $max_points : $input_points;
				$conversion         = $this->get_conversion_rate_rewards();
				$input_max_discount = $input_points / $conversion['points'] * $conversion['money'];
				//check that is not lg than $max discount
				$input_max_discount      = ( $input_max_discount > $max_discount ) ? $max_discount : $input_max_discount;
				$minimum_discount_amount = YITH_WC_Points_Rewards()->get_option( 'minimum_amount_discount_to_redeem' );

				if ( ! empty( $minimum_discount_amount ) && $input_max_discount < $minimum_discount_amount ) {
					$input_max_discount = $minimum_discount_amount;
					$input_points       = $conversion['points'] / $conversion['money'] * $input_max_discount;
				}

				if ( $input_max_discount > 0 ) {
					WC()->session->set( 'ywpar_coupon_code_points', $input_points );
					WC()->session->set( 'ywpar_coupon_code_discount', $input_max_discount );
					$discount = $input_max_discount;
				};

			} elseif ( $posted['ywpar_rate_method'] == 'percentage' ) {
				WC()->session->set( 'ywpar_coupon_code_points', $max_points );
				WC()->session->set( 'ywpar_coupon_code_discount', $max_discount );
				$discount = $max_discount;
			}

			WC()->session->set( 'ywpar_coupon_posted', $posted );

			//apply the coupon in cart
			if ( $apply_coupon && $discount ) {
				if ( version_compare( WC()->version, '2.7', '<' ) ) {
					$coupon = new WC_Coupon( $coupon_label );
					add_post_meta( $coupon->id, 'ywpar_coupon', 1 );
				} else {

					$coupon = $this->get_current_coupon();
					$coupon->set_amount( $discount );

					if ( ! $coupon->is_valid() ) {

						$args   = array(
							'id'             => false,
							'discount_type'  => 'fixed_cart',
							'individual_use' => false,
							'free_shipping'  => false,
							'usage_limit'=> 1
						);

						$coupon->add_meta_data( 'ywpar_coupon', 1 );
						$coupon->read_manual_coupon( $coupon->get_code(), $args );
					}

					$coupon->save();
					$coupon_label = $coupon->get_code();

				}

				if ( $coupon->is_valid() && ! WC()->cart->has_discount( $coupon_label ) ) {
					WC()->cart->add_discount( $coupon_label );
				}
			}
		}

		/**
		 * Update the coupon code points and discount
		 *
		 * @since  1.3.0
		 * @author Emanuela Castorina
		 * @return void
		 */
		public function update_discount() {
			$applied_coupons = WC()->cart->applied_coupons;
			if ( $coupon = $this->check_coupon_is_ywpar( $applied_coupons ) ) {
				$posted       = WC()->session->get( 'ywpar_coupon_posted' );
				$max_discount = $this->calculate_rewards_discount();
				if ( $max_discount ) {
					$max_points = $this->get_max_points();
				}
				$posted['ywpar_max_discount'] = $max_discount;
				$posted['ywpar_points_max']   = $max_points;

				$this->apply_discount_calculation( $posted, false );
			}
		}

		/**
		 * Return the coupon code
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 * @return string
		 */
		public function get_coupon_code_prefix() {
			return apply_filters( 'ywpar_label_coupon', $this->label_coupon_prefix );
		}

		/**
		 * Return the coupon code
		 * This method is @deprecated from YITH Points and Rewards 1.2.0. Use 'get_coupon_code_prefix' instead.
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 * @return string
		 */
		public function get_coupon_code() {
			return $this->get_coupon_code_prefix();
		}

		/**
		 * Return the coupon code attributes
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 *
		 * @param $args
		 * @param $code
		 *
		 * @return array
		 */
		function create_coupon_discount( $args, $code ) {

			if ( $code == $this->get_coupon_code_prefix() ) {

				$this->args = array(
					'amount'           => $this->get_discount_amount(),
					'coupon_amount'    => $this->get_discount_amount(), // 2.2
					'apply_before_tax' => 'yes',
					'type'             => $this->coupon_type,
					'free_shipping'    => YITH_WC_Points_Rewards()->get_option( 'allow_free_shipping_to_redeem', 'no' ),
					'individual_use'   => 'no',
				);

				return $this->args;

			}

			return $args;
		}

		/**
		 * Set the coupon label in cart
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 *
		 * @param $string
		 * @param $coupon
		 *
		 * @return string
		 * @internal param $label
		 *
		 */
		public function coupon_label( $string, $coupon ) {

			return $this->check_coupon_is_ywpar( $coupon ) ? esc_html( __( 'Redeem points', 'yith-woocommerce-points-and-rewards' ) ) :  $string;

		}

		/**
		 * Set the message when the discount is applied with success
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 *
		 * @param $message
		 * @param $message_code
		 * @param $coupon
		 *
		 * @return string
		 */
		public function coupon_rewards_message( $message, $message_code, $coupon ) {
			if ( $message_code === WC_Coupon::WC_COUPON_SUCCESS && $this->check_coupon_is_ywpar( $coupon ) ) {
				return __( 'Reward Discount Applied Successfully', 'yith-woocommerce-points-and-rewards' );
			} else {
				return $message;
			}
		}

		/**
		 * Return the discount amount
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 * @return float
		 */
		public function get_discount_amount() {
			$discount = 0;
			if ( WC()->session !== null ) {
				$discount = WC()->session->get( 'ywpar_coupon_code_discount' );
			}

			return $discount;
		}

		/**
		 * Register the coupon amount and points in the post meta of order
		 * if there's a rewards
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 *
		 * @param $order_id
		 *
		 * @return mixed
		 */
		public function add_order_meta( $order_id ) {
			$order        = wc_get_order( $order_id );
			$used_coupons = $order->get_used_coupons();

			//check if the coupon was used in the order
			if( ! $coupon = $this->check_coupon_is_ywpar( $used_coupons ) ){
				return;
			}

			yit_save_prop( $order, array(
				'_ywpar_coupon_amount' => WC()->session->get( 'ywpar_coupon_code_discount' ),
				'_ywpar_coupon_points' => WC()->session->get( 'ywpar_coupon_code_points' )
			), false, true);
		}

		/**
		 * Deduct the point from the user total points
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since    1.0.0
		 * @author   Emanuela Castorina
		 *
		 * @param $order
		 *
		 * @return void
		 * @internal param $order_id
		 *
		 */
		public function deduce_order_points( $order) {
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			$customer_user = method_exists( $order, 'get_customer_id' ) ? $order->get_customer_id() : yit_get_prop( $order, '_customer_user', true );
			$used_coupons  = $order->get_used_coupons();

			//check if the coupon was used in the order
			if( ! $coupon = $this->check_coupon_is_ywpar( $used_coupons ) ){
				return;
			}

			$points          = yit_get_prop( $order, '_ywpar_coupon_points' );
			$discount_amount = yit_get_prop( $order, '_ywpar_coupon_amount' );
			$redemped_points = yit_get_prop( $order, '_ywpar_redemped_points' );

			if ( $redemped_points != '' ) {
				return;
			}

			if ( $customer_user ) {
				$current_point                 = get_user_meta( $customer_user, '_ywpar_user_total_points', true );
				$current_discount_total_amount = get_user_meta( $customer_user, '_ywpar_user_total_discount', true );

				$new_point = ( $current_point - $points > 0 ) ? ( $current_point - $points ) : 0;

				update_user_meta( $customer_user, '_ywpar_user_total_points', $new_point );
				update_user_meta( $customer_user, '_ywpar_user_total_discount', $current_discount_total_amount + $discount_amount );
				yit_save_prop( $order, '_ywpar_redemped_points', $points, false, true );

				YITH_WC_Points_Rewards()->register_log( $customer_user, 'redeemed_points', yit_get_prop( $order, 'id' ), - $points );

				$order->add_order_note( sprintf( __( '%d %s to get a reward' ), - $points, YITH_WC_Points_Rewards()->get_option( 'points_label_plural' ) ), 0 );
			}

		}

		/**
		 * Return the conversion rate rewards based on the role of users
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 * @return float
		 */
		public function get_conversion_rate_rewards() {
			$conversion           = YITH_WC_Points_Rewards()->get_option( 'rewards_conversion_rate' );
			$conversion['money']  = ( empty( $conversion['money'] ) ) ? 1 : $conversion['money'];
			$conversion['points'] = ( empty( $conversion['points'] ) ) ? 1 : $conversion['points'];

			$conversion_rate_level = YITH_WC_Points_Rewards()->get_option( 'rewards_points_level' );

			if ( is_user_logged_in() ) {
				$current_user    = wp_get_current_user();
				$conversion_rate = abs( $conversion['points'] / $conversion['money'] );
				if ( YITH_WC_Points_Rewards()->get_option( 'rewards_points_for_role' ) == 'yes' ) {
					if ( ! empty( $current_user->roles ) ) {
						foreach ( $current_user->roles as $role ) {
							$c = YITH_WC_Points_Rewards()->get_option( 'rewards_points_role_' . $role );
							if ( $c['points'] != '' && $c['money'] != '' && $c['money'] != 0 ) {
								$current_conversion_rate = abs( $c['points'] / $c['money'] );

								if ( ( $conversion_rate_level == 'high' && $current_conversion_rate <= $conversion_rate ) || ( $conversion_rate_level == 'low' && $current_conversion_rate > $conversion_rate ) ) {
									$conversion_rate = $current_conversion_rate;
									$conversion      = $c;
								}
							}
						}
					}
				}
			}

			return apply_filters( 'ywpar_rewards_conversion_rate', $conversion );
		}

		/**
		 * Return the conversion percentual rate rewards
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 * @return array
		 */
		public function get_conversion_percentual_rate_rewards() {

			$conversion             = YITH_WC_Points_Rewards()->get_option( 'rewards_percentual_conversion_rate' );
			$conversion['points']   = ( empty( $conversion['points'] ) ) ? 1 : $conversion['points'];
			$conversion['discount'] = ( empty( $conversion['discount'] ) ) ? 1 : $conversion['discount'];

			$conversion_rate_level = YITH_WC_Points_Rewards()->get_option( 'rewards_points_level' );

			if ( is_user_logged_in() ) {
				$current_user    = wp_get_current_user();
				$conversion_rate = abs( $conversion['points'] / $conversion['discount'] );
				if ( YITH_WC_Points_Rewards()->get_option( 'rewards_points_for_role' ) == 'yes' ) {
					if ( ! empty( $current_user->roles ) ) {
						foreach ( $current_user->roles as $role ) {
							$c = YITH_WC_Points_Rewards()->get_option( 'rewards_points_percentual_role_' . $role );
							if ( $c['points'] != '' && $c['discount'] != '' && $c['discount'] != 0 ) {
								$current_conversion_rate = abs( $c['points'] / $c['discount'] );

								if ( ( $conversion_rate_level == 'high' && $current_conversion_rate <= $conversion_rate ) || ( $conversion_rate_level == 'low' && $current_conversion_rate > $conversion_rate ) ) {
									$conversion_rate = $current_conversion_rate;
									$conversion      = $c;
								}
							}
						}
					}
				}
			}

			return apply_filters( 'ywpar_rewards_percentual_conversion_rate', $conversion );
		}


		/**
		 * Calculate the points of a product/variation for a single item
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 * @return  int $points
		 */
		public function calculate_rewards_discount() {

			$user_id       = get_current_user_id();
			$points_usable = get_user_meta( $user_id, '_ywpar_user_total_points', true );

			if ( $points_usable <= 0 ) {
				return false;
			}

			$items              = WC()->cart->get_cart();
			$this->max_discount = 0;
			$this->max_points   = 0;

			if ( $this->get_conversion_method() == 'fixed' ) {
				$conversion = $this->get_conversion_rate_rewards();
				//get the items of cart

				foreach ( $items as $item => $values ) {
					$product_id       = ( isset( $values['variation_id'] ) && $values['variation_id'] != 0 ) ? $values['variation_id'] : $values['product_id'];
					$product_discount = $this->calculate_product_max_discounts( $product_id );

					if ( $product_discount != 0 ) {
						$this->max_discount += $product_discount * $values['quantity'];
					}
				}

				$general_max_discount = YITH_WC_Points_Rewards()->get_option( 'max_points_discount' );
				$subtotal             = WC()->cart->subtotal;

				if ( $subtotal <= $this->max_discount ) {
					$this->max_discount = $subtotal;
}
				$this->max_discount = apply_filters( 'ywpar_set_max_discount_for_minor_subtotal', $subtotal, $this->max_discount );

				//check if there's a max discount amount
				if ( $general_max_discount != '' ) {
					$is_percent = strpos( $general_max_discount, '%' );
					if ( $is_percent === false ) {
						$max_discount = ( $subtotal >= $general_max_discount ) ? $general_max_discount : $subtotal;
					} else {
						$general_max_discount = str_replace( '%', '', $general_max_discount );
						$max_discount         = $subtotal * $general_max_discount / 100;
					}

					if ( $max_discount < $this->max_discount ) {
						$this->max_discount = $max_discount;
					}
				}

				$this->max_points = ceil( $this->max_discount / $conversion['money'] * $conversion['points'] );

				if ( $this->max_points > $points_usable ) {
					$this->max_points   = $points_usable;
					$this->max_discount = $this->max_points / $conversion['points'] * $conversion['money'];
				}
			} elseif ( $this->get_conversion_method() == 'percentage' ) {
				$conversion                      = $this->get_conversion_percentual_rate_rewards();
				$general_max_percentual_discount = YITH_WC_Points_Rewards()->get_option( 'max_percentual_discount' );

				$max_points = $conversion['points'] / $conversion['discount'] * $general_max_percentual_discount;

				if ( $points_usable >= $max_points ) {
					$this->max_points              = $max_points;
					$this->max_percentual_discount = $general_max_percentual_discount;
				} else {
					$this->max_percentual_discount = intval( $points_usable / $conversion['points'] ) * $conversion['discount'];
					$this->max_points              = intval( $this->max_percentual_discount / $conversion['discount'] ) * $conversion['points'];
				}

				foreach ( $items as $item => $values ) {
					$product_id       = ( isset( $values['variation_id'] ) && $values['variation_id'] != 0 ) ? $values['variation_id'] : $values['product_id'];
					$item_price       = apply_filters( 'ywpar_calculate_rewards_discount_item_price', ywpar_get_price( $values['data'] ), $values );
					$product_discount = $this->calculate_product_max_discounts_percentage( $product_id, $item_price );

					if ( $product_discount != 0 ) {
						$this->max_discount += $product_discount * $values['quantity'];
					}

				}

			}


			return $this->max_discount;

		}

		/**
		 * @param $product_id
		 * @param $points_earned
		 *
		 * @return mixed
		 */
		public function calculate_price_worth( $product_id, $points_earned ) {

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				return 0;
			}

			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_available_variations();
				$price_worth = array();
				if( $variations ){
					foreach ( $variations as $variation ) {
						$price_worth[ $variation['variation_id']] = $this->calculate_price_worth( $variation['variation_id'], YITH_WC_Points_Rewards_Earning()->calculate_product_points( $variation['variation_id'] ) );
					}

					$price_worth = array_unique( $price_worth );

					if ( count( $price_worth ) == 0 ) {
						$return = wc_price( 0 );
					} elseif ( count( $price_worth ) == 1 ) {
						$return = wc_price( $price_worth[0] );
					} else {
						$return = min( $price_worth ). '-' . max( $price_worth ) ;
					}

					return $return;
				}
			}

			$product_price           = ywpar_get_price( $product );
			$price_from_point_earned = YITH_WC_Points_Rewards_Earning()->get_price_from_point_earned( $points_earned );

			if ( $price_from_point_earned != $product_price ) {
				$product_price = $price_from_point_earned;
			}

			$max_discount = $this->calculate_product_max_discounts( $product_id, $product_price );

			$discount          = 0;
			$conversion_method = $this->get_conversion_method();

			if ( $conversion_method == 'fixed' ) {
				$conversion = $this->get_conversion_rate_rewards();
				$discount   = $max_discount / $conversion['points'] * $conversion['money'];
			}

			return apply_filters( 'ywpar_calculate_product_discount', wc_price( $discount ), $product_id );

		}

		/**
		 * @param $product_id
		 *
		 * @param int $price
		 *
		 * @return float|mixed|string
		 */
		public function calculate_product_max_discounts( $product_id, $price = 0 ) {

			$product = wc_get_product( $product_id );

			$max_discount         = ywpar_get_price( $product );
			$max_discount_updated = false;
			$general_max_discount = YITH_WC_Points_Rewards()->get_option( 'max_points_product_discount' );
			$max_product_discount = get_post_meta( $product_id, '_ywpar_max_point_discount', true );
			$product_price        = $price ? $price : ywpar_get_price( $product );

			if ( $max_product_discount != '' ) {
				$is_percent = strpos( $max_product_discount, '%' );
				if ( $is_percent === false ) {
					$max_discount = ( $product_price >= $max_product_discount ) ? $max_product_discount : $product_price;
				} else {
					$max_product_discount = str_replace( '%', '', $max_product_discount );
					$max_discount         = $product_price * $max_product_discount / 100;
				}
				$max_discount_updated = true;
			}

			if ( ! $max_discount_updated ) {
				if ( $product->is_type( 'variation' ) ) {
					$categories = get_the_terms( yit_get_base_product_id( $product ), 'product_cat' );
				} else {
					$categories = get_the_terms( $product_id, 'product_cat' );
				}

				if ( ! empty( $categories ) ) {
					$max_discount = $product_price; //reset the global discount

					foreach ( $categories as $term ) {
						$max_category_discount = get_term_meta( $term->term_id, 'max_point_discount', true );

						if ( $max_category_discount != '' ) {

							$is_percent = strpos( $max_category_discount, '%' );

							if ( $is_percent === false ) {
								$max_discount = ( $product_price >= $max_category_discount ) ? $max_category_discount : $product_price;
							} else {
								$max_category_discount = str_replace( '%', '', $max_category_discount );
								$max_discount          = $product_price * $max_category_discount / 100;
							}

							$max_discount_updated = true;
						}


					}
				}
			}

			if ( ! $max_discount_updated && $general_max_discount != '' ) {
				$is_percent = strpos( $general_max_discount, '%' );
				if ( $is_percent === false ) {
					$max_discount = ( $product_price >= $general_max_discount ) ? $general_max_discount : ywpar_get_price( $product );
				} else {
					$general_max_discount = str_replace( '%', '', $general_max_discount );
					$max_discount         = $product_price * $general_max_discount / 100;

				}
			}

			return $max_discount;
		}

		/**
		 * @param $product_id
		 *
		 * @param int $price
		 *
		 * @return float|int|string
		 */
		public function calculate_product_max_discounts_percentage( $product_id, $price = 0 ) {

			$max_discount                   = 0;
			$max_discount_updated           = false;
			$conversion                     = $this->get_conversion_percentual_rate_rewards();
			$general_max_discount           = YITH_WC_Points_Rewards()->get_option( 'max_percentual_discount' );
			$product                        = wc_get_product( $product_id );
			$product_price                  = $price ? $price : ywpar_get_price( $product );
			$redemption_percentage_discount = yit_get_prop( $product, '_ywpar_redemption_percentage_discount', true );
			$redemption_percentage_discount = str_replace( '%', '', $redemption_percentage_discount );

			if ( $redemption_percentage_discount != '' ) {
				$max_discount = ( $this->max_percentual_discount / $conversion['discount'] ) * $redemption_percentage_discount * $product_price / 100;

				$max_discount_updated = true;
			}

			if ( ! $max_discount_updated ) {
				if ( $product->is_type( 'variation' ) ) {
					$categories = get_the_terms( yit_get_base_product_id( $product ), 'product_cat' );
				} else {
					$categories = get_the_terms( $product_id, 'product_cat' );
				}

				if ( ! empty( $categories ) ) {
					$max_discount = ywpar_get_price( $product ); //reset the global discount

					foreach ( $categories as $term ) {
						$redemption_category_discount = get_term_meta( $term->term_id, 'redemption_percentage_discount', true );
						$redemption_category_discount = str_replace( '%', '', $redemption_category_discount );

						if ( $redemption_category_discount != '' ) {
							$max_discount         = ( $this->max_percentual_discount / $conversion['discount'] ) * $redemption_category_discount * $product_price / 100;
							$max_discount_updated = true;
						}
					}
				}
			}

			if ( ! $max_discount_updated && $general_max_discount != '' ) {
				$max_discount = $product_price * $this->max_percentual_discount / 100;
			}

			$max_discount = ( $product_price >= $max_discount ) ? $max_discount : $product_price;

			return $max_discount;
		}


		/**
		 * Return the maximum discount of a product
		 *
		 * @param $product_id
		 *
		 * @since   1.1.3
		 * @author  Emanuela Castorina
		 * @return mixed
		 */
		public function calculate_product_discounts( $product_id ) {
			$discount          = 0;
			$conversion_method = $this->get_conversion_method();

			if ( $conversion_method == 'fixed' ) {
				$max_discount = $this->calculate_product_max_discounts( $product_id );
				$conversion   = $this->get_conversion_rate_rewards();
				$discount     = $max_discount / $conversion['points'] * $conversion['money'];
			}

			return apply_filters( 'ywpar_calculate_product_discount', $discount, $product_id );
		}

		/**
		 * Return the min and maximum discount of a product variable
		 *
		 * @param $product
		 *
		 * @return mixed
		 * @internal param $product_id
		 * @since    1.1.3
		 * @author   Emanuela Castorina
		 */
		public function calculate_product_discounts_on_variable( $product ) {

			if ( is_numeric( $product ) ) {
				$product = wc_get_product( $product );
			}

			if ( ! is_object( $product ) ) {
				return 0;
			}

			if ( $product->is_type( 'variable' ) ) {
				return;
			}

			$variations = $product->get_available_variations();
			$discounts  = array();
			if ( ! empty( $variations ) ) {
				foreach ( $variations as $variation ) {
					$discounts[] = $this->calculate_product_discounts( $variation['variation_id'] );
				}
			}

			$discounts = array_unique( $discounts );

			if ( count( $discounts ) == 0 ) {
				$return = '';
			} elseif ( count( $discounts ) == 1 ) {
				$return = wc_price( $discounts[0] );
			} else {
				$return = wc_price( min( $discounts ) ) . '-' . wc_price( max( $discounts ) );

			}

			return apply_filters( 'calculate_product_discounts_on_variable', $return, $product );

		}

		/**
		 * Return the conversion method that can be used in the cart fore rewards
		 *
		 * @since   1.1.3
		 * @author  Emanuela Castorina
		 * @return  string
		 */
		public function get_conversion_method() {
			return apply_filters( 'ywpar_conversion_method', YITH_WC_Points_Rewards()->get_option( 'conversion_rate_method' ) );
		}

		/**
		 * Return the max points that can be used in the cart fore rewards
		 * must be called after the function calculate_points_and_discount
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 * @return  int
		 */
		public function get_max_points() {
			return apply_filters( 'ywpar_rewards_max_points', $this->max_points );
		}

		/**
		 * Return the max discount that can be used in the cart fore rewards
		 * must be called after the function calculate_points_and_discount
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 * @return  float
		 */
		public function get_max_discount() {
			return apply_filters( 'ywpar_rewards_max_discount', $this->max_discount );
		}

		/**
		 * Return the max discount that can be used in the cart fore rewards
		 * must be called after the function calculate_points_and_discount
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 * @return  float
		 */
		public function get_max_percentual_discount() {
			return apply_filters( 'ywpar_rewards_max_percentual_discount', $this->max_percentual_discount );
		}

		/**
		 * Check id a YWPAR is in the list
		 * @param $coupon_list array|WC_Coupon
		 *
		 * @return bool|WC_Coupon
		 */
		public function check_coupon_is_ywpar( $coupon_list ) {

			if ( version_compare( WC()->version, '2.7', '<' ) ) {
				if ( is_array( $coupon_list ) && ! empty( $coupon_list ) ) {
					foreach ( $coupon_list as $coupon_in_cart_code ){
						$coupon_in_cart = new WC_Coupon( $coupon_in_cart_code );
						return $this->label_coupon_prefix == $coupon_in_cart->code;
					}
				}elseif ( $coupon_list instanceof WC_Coupon  ){
					return $this->label_coupon_prefix == $coupon_list->code;
				}

			}else{
				if ( is_array( $coupon_list ) && ! empty( $coupon_list ) ) {
					foreach ( $coupon_list as $coupon_in_cart_code ){
						$coupon_in_cart = new WC_Coupon( $coupon_in_cart_code );
						if( $coupon_in_cart){
							$meta = $coupon_in_cart->get_meta( 'ywpar_coupon' );
							if ( ! empty( $meta ) ) {
								return $coupon = $coupon_in_cart;
							}
						}
					}
				}elseif ( $coupon_list instanceof WC_Coupon  ){
					$var1 = $coupon_list->get_meta( 'ywpar_coupon' );
					return !empty( $var1);
				}
			}

			return false;
		}

		/**
		 * Return the coupon to apply
		 * @return WC_Coupon
		 */
		public function get_current_coupon() {

			if ( empty( $this->current_coupon_code ) ) {
				//check if in the cart
				$coupons_in_cart = WC()->cart->get_applied_coupons();

				foreach ( $coupons_in_cart as $coupon_in_cart_code ) {
					if ( $this->check_coupon_is_ywpar( $coupon_in_cart_code ) ) {
						$this->current_coupon_code = $coupon_in_cart_code;
						break;
					}
				}
			}

			if ( empty( $this->current_coupon_code ) ) {
				if ( is_user_logged_in() ) {
					$this->current_coupon_code = apply_filters( 'ywpar_coupon_code', $this->label_coupon_prefix . '_' . get_current_user_id(), $this->label_coupon_prefix );
				}
			}

			$coupon = empty( $this->current_coupon_code ) ? false : new WC_Coupon( $this->current_coupon_code );

			return $coupon;
		}

		/**
		 * Set cron to clear coupon
		 */
		public function ywpar_set_cron() {

			if ( ! wp_next_scheduled( 'ywpar_clean_cron' ) ) {
				$duration = apply_filters('ywpar_set_cron_time', 'daily' );
				wp_schedule_event( time(), $duration, 'ywpar_clean_cron' );
			}
		}
		
		/**
		 * Clear coupons after use
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		function clear_coupons() {

			$args = array(
				'post_type'       => 'shop_coupon',
				'posts_per_pages' => - 1,
				'meta_key'        => 'ywpar_coupon',
				'meta_value'      => 1,
				'date_query'      => array(
					array(
						'column' => 'post_date_gmt',
						'before' => '1 day ago',
					),
				),
			);

			$coupons = get_posts( $args );

			if ( ! empty( $coupons ) ) {
				foreach ( $coupons as $coupon ) {
					wp_delete_post( $coupon->ID, true );
				}
			}
		}
	}
}

/**
 * Unique access to instance of YITH_WC_Points_Rewards_Redemption class
 *
 * @return \YITH_WC_Points_Rewards_Redemption
 */
function YITH_WC_Points_Rewards_Redemption() {
	return YITH_WC_Points_Rewards_Redemption::get_instance();
}

