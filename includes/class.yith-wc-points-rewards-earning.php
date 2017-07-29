<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWPAR_VERSION' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Implements features of YITH WooCommerce Points and Rewards
 *
 * @class   YYITH_WC_Points_Rewards_Earning
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */
if ( ! class_exists( 'YITH_WC_Points_Rewards_Earning' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Earning
	 */
	class YITH_WC_Points_Rewards_Earning {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WC_Points_Rewards_Earning
		 */
		protected static $instance;

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WC_Points_Rewards_Earning
		 */
		protected $points_applied = false;

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WC_Points_Rewards_Earning
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

			if ( apply_filters('ywpar_enable_points_upon_sales', YITH_WC_Points_Rewards()->get_option( 'enable_points_upon_sales', 'yes' ) == 'yes') ) {
				//add point when
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_points_earned_from_cart' ) );

				add_action( 'woocommerce_payment_complete', array( $this, 'add_order_points' ), 12 );
				add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'add_order_points' ), 12 );
				add_action( 'woocommerce_order_status_processing', array( $this, 'add_order_points' ), 12 );
				add_action( 'woocommerce_order_status_completed', array( $this, 'add_order_points' ), 12 );
				add_action( 'woocommerce_order_status_failed_to_processing', array( $this, 'add_order_points' ), 12 );
				add_action( 'woocommerce_order_status_failed_to_completed', array( $this, 'add_order_points' ), 12 );

				//remove point when the order is refunded or cancelled
				if ( YITH_WC_Points_Rewards()->get_option( 'remove_point_order_deleted' ) == 'yes' ) {
					add_action( 'woocommerce_order_status_cancelled', array( $this, 'remove_order_points' ) );
					add_action( 'woocommerce_order_status_cancelled_to_completed', array( $this, 'add_order_points_after_order_status' ),11 );
					add_action( 'woocommerce_order_status_cancelled_to_processing', array( $this, 'add_order_points_after_order_status' ), 11 );
				}

				if ( YITH_WC_Points_Rewards()->get_option( 'remove_point_refund_order' ) == 'yes' ) {
					add_action( 'woocommerce_order_partially_refunded', array(
						$this,
						'remove_order_points_refund'
					), 11, 2 );
					add_action( 'woocommerce_order_fully_refunded', array( $this, 'remove_order_points_refund' ), 11, 2 );
					add_action( 'wp_ajax_nopriv_woocommerce_delete_refund', array( $this, 'refund_delete' ), 9, 2 );
					add_action( 'wp_ajax_woocommerce_delete_refund', array( $this, 'refund_delete' ), 9, 2 );
				}
			}

			//add point for review
			if ( class_exists( 'YITH_WooCommerce_Advanced_Reviews' ) ) {
				add_action( 'ywar_review_approve_status_changed', array(
					$this,
					'add_order_points_with_advanced_reviews'
				), 10, 2 );
			} else {
				add_action( 'comment_post', array( $this, 'add_order_points_with_review' ), 10, 2 );
				add_action( 'wp_set_comment_status', array( $this, 'add_order_points_with_review' ), 10, 2 );
			}

			//extrapoint to registration
			add_action( 'user_register', array( $this, 'extrapoints_to_new_customer' ), 10 );
		}


		public function calculate_product_points_in_order( $product_id, $integer = true, $order_item ) {

//			$product = wc_get_product( $product_id );
//			if ( $product ) {
//				$points = $this->calculate_product_points( $product, $integer );
//			} else {
//				$qty    = $order_item['qty'] ? $order_item['qty'] : 1;
//				$points = $this->get_point_earned_from_price( $order_item['line_subtotal'] / $qty, $integer );
//			}
			$qty    = $order_item['qty'] ? $order_item['qty'] : 1;
			$points = $this->get_point_earned_from_price( $order_item['line_subtotal'] / $qty, $integer );

			return $points;
		}

		/**
		 * Calculate the points of a product/variation for a single item
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $product
		 * @param bool $integer
		 *
		 * @return int $points
		 */
		public function calculate_product_points( $product, $integer = true ) {

			if ( is_numeric( $product ) ) {
				$product = wc_get_product( $product );
			}

			if ( ! is_object( $product ) ) {
				return 0;
			}

			if ( $product->is_type( 'grouped' ) ) {
				$grouped_points = 0;

				foreach ( $product->get_children() as $child_id ) {
					$child = wc_get_product( $child_id );
					$grouped_points += $this->calculate_product_points( $child, $integer );
				}

				return $grouped_points;
			}

			$points_updated = false;
			$points         = 0;

			$product_id              = $product->get_id();
			$main_id                 = yit_get_base_product_id( $product );
			$point_earned            = yit_get_prop( $product, '_ywpar_point_earned', true );
			$point_earned_dates_from = yit_get_prop( $product, '_ywpar_point_earned_dates_from', true );
			$point_earned_dates_to   = yit_get_prop( $product, '_ywpar_point_earned_dates_to', true );



			if ( $point_earned != '' && $this->is_ondate( $point_earned_dates_from, $point_earned_dates_to ) ) {

				$is_percent = strpos( $point_earned, '%' );

				if ( $is_percent !== false ) {
					$point_earned = str_replace( '%', '', $point_earned );
					$points = $this->get_point_earned( $product, 'product', false ) * $point_earned / 100;
				} else {
					$points = $point_earned;
				}

				$points_updated = true;
			}
			if ( ! $points_updated ) {
				if ( $product->is_type( 'variation' ) ) {
					$categories = get_the_terms( $main_id, 'product_cat' );
				} else {
					$categories = get_the_terms( $product_id, 'product_cat' );
				}

				if ( ! empty( $categories ) ) {
					$points = 0; //reset the global point

					foreach ( $categories as $term ) {
						$point_earned            = get_term_meta( $term->term_id, 'point_earned', true );
						$point_earned_dates_from = get_term_meta( $term->term_id, 'point_earned_dates_from', true );
						$point_earned_dates_to   = get_term_meta( $term->term_id, 'point_earned_dates_to', true );

						if ( $point_earned != '' && $this->is_ondate( $point_earned_dates_from, $point_earned_dates_to ) ) {

							$is_percent = strpos( $point_earned, '%' );
							if ( $is_percent !== false ) {
								$point_earned   = str_replace( '%', '', $point_earned );
								$current_points = $this->get_point_earned( $product, 'product', false ) * $point_earned / 100;
							} else {
								$current_points = $point_earned;
							}

							$points         = ( $current_points > $points ) ? $current_points : $points;
							$points_updated = true;
						}
					}
				}
			}
			if ( ! $points_updated ) {
				$points = $this->get_point_earned( $product, 'product', false );
			}


            $points =  apply_filters( 'ywpar_get_product_point_round', $points );
            
			if ( $integer ) {
               
				$points =  round( $points ) ;
			}


			//  Let third party plugin to change the points earned for this product
			return apply_filters( 'ywpar_get_product_point_earned', $points, $product );
		}


		/**
		 * Calculate the points of a product variable for a single item
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $product WC_Product_Variable
		 * @param bool $integer
		 *
		 * @return int $points
		 */
		public function calculate_product_points_on_variable( $product, $integer = true ) {

			if ( is_numeric( $product ) ) {
				$product = wc_get_product( $product );
			}

			if ( ! is_object( $product ) ) {
				return 0;
			}

			if ( ! $product->is_type('variable') ) {
				return 0;
			}

			$variations = $product->get_available_variations();
			$points     = array();
			if ( ! empty( $variations ) ) {
				foreach ( $variations as $variation ) {
					$points[] = $this->calculate_product_points( $variation['variation_id'] );
				}
			}

			$points = array_unique( $points );

			if ( count( $points ) == 0 ) {
				$return = 0;
			} elseif ( count( $points ) == 1 ) {
				$return = $points[0];
			} else {
				$return = min( $points ) . '-' . max( $points );
			}

			return apply_filters( 'ywpar_calculate_product_points_on_variable', $return, $product );
		}


		/**
		 * Calculate the total points in the carts
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param bool $integer
		 *
		 * @return int $points
		 */
		public function calculate_points_on_cart( $integer = true ) {

			$items      = WC()->cart->get_cart();

			$tot_points = 0;
			foreach ( $items as $item => $values ) {
				$product_point = $this->calculate_product_points( $values['data'], false );
                $tot_points += $product_point * $values['quantity'];
                
			}

			if ( WC()->cart->applied_coupons && YITH_WC_Points_Rewards()->get_option( 'remove_points_coupon' ) == 'yes' && isset( WC()->cart->discount_cart ) && WC()->cart->discount_cart > 0 ) {
				$remove_points     = 0;
				$conversion_points = $this->get_conversion_option();

				if ( $conversion_points['money'] * $conversion_points['points'] != 0 ) {
					$discount_cart = ( get_option( 'woocommerce_tax_display_cart' ) == 'excl' ) ? WC()->cart->discount_cart : WC()->cart->discount_cart + WC()->cart->discount_cart_tax;
					$discount_cart = apply_filters( 'ywpar_discount_amount_calculation', $discount_cart );
					$remove_points += round( $discount_cart / $conversion_points['money'] * $conversion_points['points'] );
				}

				$tot_points -= $remove_points;
			}

			$tot_points = ( $tot_points < 0 ) ? 0 : $tot_points;

			if ( $integer ) {

				if ( apply_filters( 'ywpar_floor_points', false ) ) {
					$tot_points = floor( $tot_points );
				} else {
					$tot_points = round( $tot_points );
				}

			}

			return apply_filters( 'ywpar_calculate_points_on_cart', $tot_points );
		}


		/**
		 * Save the points that are in the cart in a post meta of the order
		 *
		 * @param   int $order_id
		 *
		 * @since   1.5.0
		 * @author  Emanuela Castorina
		 * @return  void
		 */
		public function save_points_earned_from_cart( $order_id ) {
			$points_from_cart = $this->calculate_points_on_cart();
			$order            = wc_get_order( $order_id );
			yit_save_prop( $order, 'ywpar_points_from_cart', $points_from_cart );
		}

		/**
		 * Check the validate on an interval of date
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $datefrom
		 * @param $dateto
		 *
		 * @return int $points
		 */
		public function is_ondate( $datefrom, $dateto ) {
			$now = time();


			if ( $datefrom == '' && $dateto == '' ) {
				return true;
			}

			//fix the $dateto
			$dateto += ( 24 * 60 * 60 ) - 1;

			if ( $datefrom == '' && $dateto != '' && $now <= $dateto ) {
				return true;
			}

			if ( $dateto == '' && $datefrom != '' && $now >= $datefrom ) {
				return true;
			}

			$ondate = ( ( $datefrom != '' && $now >= $datefrom ) && ( $dateto != '' && $now <= $dateto ) ) ? true : false;

			return $ondate;
		}

		/**
		 * Add points to the order from order_id
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $order_id
		 *
		 * @return void
		 */
		public function add_order_points( $order_id ) {


			$order = wc_get_order( $order_id );
			$customer_user = method_exists($order,'get_customer_id') ? $order->get_customer_id() : yit_get_prop( $order, '_customer_user', true );

			if ( ! YITH_WC_Points_Rewards()->is_user_enabled( 'earn', $customer_user ) || apply_filters( 'ywpar_add_order_points', false, $order_id ) || $customer_user == 0 ) {
				return;
			}

			$is_set = yit_get_prop( $order, '_ywpar_points_earned', true );

			//return if the points are just calculated
			if ( is_array( $this->points_applied ) && in_array( $order_id, $this->points_applied ) || $is_set != '') {
				return;
			}

			$tot_points = yit_get_prop( $order, 'ywpar_points_from_cart', true );

			//this is necessary for old orders
			if ( empty( $tot_points ) ) {
				$tot_points = 0;
				$order_items = $order->get_items();

				if ( ! empty( $order_items ) ) {
					foreach ( $order_items as $order_item ) {
						$product_id = ( $order_item['variation_id'] != 0 && $order_item['variation_id'] != '' ) ? $order_item['variation_id'] : $order_item['product_id'];
						$item_points = $this->calculate_product_points_in_order( $product_id, false, $order_item );
						$tot_points += $item_points * $order_item['qty'];
					}
				}

				$coupons = $order->get_used_coupons();
				if ( sizeof( $coupons ) > 0 && YITH_WC_Points_Rewards()->get_option( 'remove_points_coupon' ) == 'yes' ) {
					$remove_points     = 0;
					$conversion_points = $this->get_conversion_option();
					if ( $order->get_total_discount() ) {
						if ( $conversion_points['money'] * $conversion_points['points'] != 0 ) {
							$remove_points += $order->get_total_discount() / $conversion_points['money'] * $conversion_points['points'];
						}
					}

					$tot_points -= $remove_points;
				}

				$tot_points = ( $tot_points < 0 ) ? 0 : round( $tot_points );
			}

			//update order meta and add note to the order
			yit_save_prop( $order, array(
				'_ywpar_points_earned'     => $tot_points,
				'_ywpar_conversion_points' => $this->get_conversion_option()
			) );

			$this->points_applied[]= $order_id;
			$order->add_order_note( sprintf( __( 'Customer earned %d %s for this purchase.' ), $tot_points, YITH_WC_Points_Rewards()->get_option( 'points_label_plural' ) ), 0 );

			if ( $customer_user > 0 ) {
				$this->add_points( $customer_user, $tot_points, 'order_completed', $order_id );
				$this->extra_points( array( 'num_of_orders', 'amount_spent', 'points' ), $customer_user, $order_id );
			}

		}

		/**
		 * Remove points to the order from order_id
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $order_id
		 *
		 * @return void
		 */
		public function remove_order_points( $order_id ) {

			$order   = wc_get_order( $order_id );
			$point_earned = yit_get_prop( $order, '_ywpar_points_earned', true );

			if ( $point_earned == '' ) {
				return;
			}

			$customer_user = method_exists($order,'get_customer_id') ? $order->get_customer_id() : yit_get_prop( $order, '_customer_user', true );
			$points = $point_earned;
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
		 * Add point to the order if the refund is deleted
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 * @return  void
		 */
		public function refund_delete() {
			check_ajax_referer( 'order-item', 'security' );

			$refund_id = absint( $_POST['refund_id'] );

			if ( $refund_id && 'shop_order_refund' === get_post_type( $refund_id ) ) {
				$order_id = wp_get_post_parent_id( $refund_id );
			}

			$point_earned = get_post_meta( $order_id, '_ywpar_points_earned', true );

			if ( $point_earned == '' ) {
				return;
			}

			$order          = wc_get_order( $order_id );
			$order_subtotal = $order->get_subtotal();
			$user_id        = $order->get_user_id();

			$refund_obj    = new WC_Order_Refund( $refund_id );
			$refund_amount = method_exists( $refund_obj, 'get_amount' ) ? $refund_obj->get_amount() : $refund_obj->get_refund_amount();
			$order_shipping_total = method_exists( $refund_obj, 'get_shipping_total' ) ? $refund_obj->get_shipping_total() : $refund_obj->get_total_shipping();

			if ( $refund_amount > 0 ) {

				if ( $refund_amount > $order_subtotal ) {
					//shipping must be removed from
					$refund_amount        = $refund_amount - $order_shipping_total;
				}

				$conversion_points = yit_get_prop( $order, '_ywpar_conversion_points', true );
				if ( $conversion_points == '' ) {
					$conversion_points = $this->get_conversion_option();
				}
				$points = round( $refund_amount / $conversion_points['money'] * $conversion_points['points'] );
				$action = 'refund_deleted';

				if ( $user_id > 0 ) {
					$current_point = get_user_meta( $user_id, '_ywpar_user_total_points', true );
					$p = $current_point + $points;
					update_user_meta( $user_id, '_ywpar_user_total_points', $p > 0 ? $p : 0 );
					yit_save_prop( $order, '_ywpar_points_earned', $points + $point_earned);
					YITH_WC_Points_Rewards()->register_log( $user_id, $action, $order_id, $points );
					$order->add_order_note( sprintf( __( 'Added %d %s for cancelled refund.' ), $points, YITH_WC_Points_Rewards()->get_option( 'points_label_plural' ) ), 0 );
				}
			}

		}

		/**
		 * Remove points to the order if there's a partial refund
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $order_id
		 * @param $refund_id
		 *
		 * @return void
		 */
		public function remove_order_points_refund( $order_id, $refund_id ) {

			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			$point_earned          = yit_get_prop( $order, '_ywpar_points_earned', true );
			$total_points_refunded = yit_get_prop( $order, '_ywpar_total_points_refunded', true );

			if ( $point_earned == '' ) {
				return;
			}

			$refund_obj = new WC_Order_Refund( $refund_id );
			$refund_amount  = method_exists( $refund_obj, 'get_amount') ? $refund_obj->get_amount() : $refund_obj->get_refund_amount();

			$order_total    = $order->get_total();
			$order_subtotal = $order->get_subtotal();
			$user_id        = $order->get_user_id();

			if ( $refund_amount > 0 ) {

				if ( $refund_amount > $order_subtotal ) {
					//shipping must be removed from
					$order_shipping_total = method_exists( $order, 'get_shipping_total' ) ? $order->get_shipping_total() : $order->get_total_shipping();
					$refund_amount        = $refund_amount - $order_shipping_total;
				}

				$conversion_points = get_post_meta( $order_id, '_ywpar_conversion_points', true );

				if ( $conversion_points == '' ) {
					$conversion_points = $this->get_conversion_option();
				}

				if ( $refund_amount == abs( $order_total ) ) {
					$points = $point_earned;
				} else {
					$points = round( $refund_amount / $conversion_points['money'] * $conversion_points['points'] );
				}

				//fix the points to refund calculation if points are more of the gap
				$gap    = $point_earned - $total_points_refunded;
				$points = ( $points > $gap ) ? $gap : $points;
				$action = 'order_refund';
				$total_points_refunded += $points;

				if ( $user_id > 0 ) {
					$current_point = get_user_meta( $user_id, '_ywpar_user_total_points', true );
					$new_point     = ( $current_point - $points > 0 ) ? ( $current_point - $points ) : 0;
					update_user_meta( $user_id, '_ywpar_user_total_points', $new_point );
					yit_save_prop( $order, '_ywpar_total_points_refunded', $total_points_refunded );
					YITH_WC_Points_Rewards()->register_log( $user_id, $action, $order_id, - $points );
					$order->add_order_note( sprintf( __( 'Removed %d %s for order refund.' ), $points, YITH_WC_Points_Rewards()->get_option( 'points_label_plural' ) ), 0 );
				}

			}
		}


		/**
		 * Add point to the order if the status of order from cancelled become processing or completed
		 *
		 * @since   1.1.3
		 * @author  Emanuela Castorina
		 *
		 * @param $order_id
		 *
		 * @return void
		 */
		public function add_order_points_after_order_status( $order_id ) {
			$order   = wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			$point_earned = yit_get_prop( $order, '_ywpar_points_earned', true );

			if ( $point_earned == '' ) {
				return;
			}

			$user_id = $order->get_user_id();
			$points = $point_earned;
			$action = 'order_' . $order->get_status();

			if ( $user_id > 0 ) {
				$current_point = get_user_meta( $user_id, '_ywpar_user_total_points', true );
				$new_point     = $current_point + $points;
				update_user_meta( $user_id, '_ywpar_user_total_points', $new_point > 0 ? $new_point : 0 );
				YITH_WC_Points_Rewards()->register_log( $user_id, $action, $order_id, $points );
				$order->add_order_note( sprintf( __( 'Added %d %s for order %s.' ), $points, YITH_WC_Points_Rewards()->get_option( 'points_label_plural' ), YITH_WC_Points_Rewards()->get_action_label( $action ) ), 0 );
			}
		}

		/**
		 * Return the global points of an object
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $object
		 * @param string $type
		 * @param bool $integer
		 *
		 * @return int
		 */
		public function get_point_earned( $object, $type = 'order', $integer = false ) {

			$conversion = $this->get_conversion_option();
			$price      = 0;
			switch ( $type ) {
				case 'order':
					$price = $object->get_total();
					break;
				case 'product':
					$price = ( get_option( 'woocommerce_tax_display_cart' ) == 'excl' ) ? yit_get_price_excluding_tax($object) : yit_get_price_including_tax($object);
					break;
				default:

			}

			$points = $price / $conversion['money'] * $conversion['points'];

			return $integer ? round( $points ) : $points;
		}

		/**
		 * Return the global points of an object from price
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $price
		 * @param bool $integer
		 *
		 * @return int
		 */
		public function get_point_earned_from_price( $price, $integer = false ) {
			$conversion = $this->get_conversion_option();

			$points = $price / $conversion['money'] * $conversion['points'];

			return $integer ? round( $points ) : $points;
		}


		/**
		 * Return the global points of an object from price
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $points
		 * @param bool $integer
		 *
		 * @return int
		 */
		public function get_price_from_point_earned( $points, $integer = false ) {
			$conversion = $this->get_conversion_option();

			$price = $points * $conversion['money'] / $conversion['points'];

			return $price;
		}

		/**
		 * Return the global points of an object
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 * @return  array
		 */
		public function get_conversion_option() {

			$role_conversion_enabled = YITH_WC_Points_Rewards()->get_option( 'enable_conversion_rate_for_role' );
			$conversion_rate_level   = YITH_WC_Points_Rewards()->get_option( 'conversion_rate_level' );
			$conversion              = YITH_WC_Points_Rewards()->get_option( 'earn_points_conversion_rate' );

			if ( $role_conversion_enabled == 'yes' && is_user_logged_in() ) {
				$current_user    = wp_get_current_user();
				$conversion_rate = 0;
				if ( ! empty( $current_user->roles ) ) {
					foreach ( $current_user->roles as $role ) {
						$c = YITH_WC_Points_Rewards()->get_option( 'earn_points_role_' . $role );
						if ( $c['points'] != '' && $c['money'] != '' && $c['money'] != 0 ) {
							$current_conversion_rate = abs( $c['points'] / $c['money'] );
							if ( ( $conversion_rate_level == 'high' && $current_conversion_rate >= $conversion_rate ) || ( $conversion_rate_level == 'low' && $current_conversion_rate < $conversion_rate ) ) {
								$conversion_rate = $current_conversion_rate;
								$conversion      = $c;
							}
						}
					}
				}
			}

			$conversion['money']  = ( empty( $conversion['money'] ) ) ? 1 : $conversion['money'];
			$conversion['points'] = ( empty( $conversion['points'] ) ) ? 1 : $conversion['points'];

			return apply_filters( 'ywpar_conversion_points_rate', $conversion );
		}

		/**
		 * Add extra points to the user
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param array $type
		 * @param $user_id
		 * @param int $order_id
		 *
		 * @return void
		 */
		public function extra_points( $type = array(), $user_id, $order_id = 0 ) {

			if ( empty( $type ) ) {
				return false;
			}

			$extra_points     = 0;
			$user_extrapoint  = get_user_meta( $user_id, '_ywpar_extrapoint', true );
			$current_points   = get_user_meta( $user_id, '_ywpar_user_total_points', true );
			$reusable_points  = get_user_meta( $user_id, '_ywpar_user_reusable_points', true );
			$extrapoint_rules = YITH_WC_Points_Rewards()->get_option( 'extra_points' );
			$action           = '';
			$counter          = 0;


            if( empty( $user_extrapoint ) ){

                $user_extrapoint = array();
            }

			if ( empty( $extrapoint_rules ) ) {
				return;
			}

			foreach ( $extrapoint_rules as $rule ) {
				if ( ! in_array( $rule['option'], $type ) ) {
					continue;
				}

				$extra_points = 0;

				$flag   = true;
				$repeat = isset( $rule['repeat'] ) ? $rule['repeat'] : 0;

				if ( ! empty( $user_extrapoint ) ) {
					foreach ( $user_extrapoint as $ue_item ) {
						if ( ! $repeat ) {
							if ( $ue_item['option'] == $rule['option'] && $ue_item['value'] == $rule['value'] && $ue_item['points'] == $rule['points'] ) {
								$flag = false;
							}
						} else {
							$counter = ( isset( $ue_item['counter'] ) ) ? $ue_item['counter'] : 0;
						}
					}
				}


				if ( $flag ) {
					switch ( $rule['option'] ) {
						case 'points':

							$usable_points = $current_points;

							if ( $repeat == 1 && $counter != 0 ) {
								//calculate the number of points from the last points extra earning
								$usable_points = $this->get_usable_points( $user_id );
								$usable_points += ( $reusable_points != '' ) ? $reusable_points : 0;
							}

							if ( $usable_points >= $rule['value'] ) {
								$extra_points = intval($usable_points/$rule['value']) *  $rule['points'];
								$reusable_points = $usable_points - ( intval($usable_points/$rule['value']) * $rule['value']);
								$counter ++;
							}

							update_user_meta( $user_id, '_ywpar_user_reusable_points', $reusable_points );

							break;

						case 'amount_spent':
							$usable_amount = yith_ywpar_calculate_user_total_orders_amount( $user_id, $order_id );

							if ( $repeat == 1 && $counter != 0 ) {
								$usable_amount = $usable_amount - $counter * $rule['value'];
							}

							if ( $usable_amount >= $rule['value'] ) {

								$extra_points = $rule['points'];
								$counter ++;
							}

							break;

						case 'num_of_orders':
							$usable_num_of_order = ywpar_get_customer_order_count( $user_id );

							if ( $repeat == 1 && $counter != 0 ) {
								$usable_num_of_order = $usable_num_of_order - $counter * $rule['value'];
							}
							if ( $usable_num_of_order >= $rule['value'] ) {
								$extra_points = $rule['points'];
								$counter ++;
							}
							break;
						case 'reviews':
							$review_num      = 0;
							$usable_comments = get_comments( array(
								'status'    => 'approve',
								'user_id'   => $user_id,
								'post_type' => 'product',
								'number'    => ''
							) );

							if ( ! empty( $usable_comments ) ) {
								$review_num = count( $usable_comments );
								if ( $repeat == 1 && $counter != 0 ) {
									$review_num = $review_num - $counter * $rule['value'];
								}
							}
							if ( $review_num >= $rule['value'] ) {
								$extra_points = $rule['points'];
								$counter ++;
							}

							$action = __( 'Reviews', 'yith-woocommerce-points-and-rewards' );
							break;
						case 'registration':

							if ( $rule['value'] != '' ) {
								$extra_points = $rule['points'];
								$counter ++;
							}

							break;
						default:
					}


					if ( $extra_points > 0 ) {
						$rule['counter']   = $counter;
						$user_extrapoint[] = $rule;

						update_user_meta( $user_id, '_ywpar_extrapoint', $user_extrapoint );
						$this->add_points( $user_id, $extra_points, $rule['option'] . '_exp', $action );
						$current_points += $extra_points;
					}

				}

			}

		}

		/**
		 * Return usable points
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $user_id
		 *
		 * @return int
		 */
		public function get_usable_points( $user_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'yith_ywpar_points_log';
			$from_id    = 1;
			$query      = "SELECT id  FROM  $table_name where user_id = $user_id AND action='points_exp' ORDER BY date_earning DESC LIMIT 1";
			$res        = $wpdb->get_row( $query );

			if ( ! empty( $res ) ) {
				$from_id = $res->id;
			}

			$query = "SELECT SUM(ywpar_points.amount) as usable_points FROM $table_name as ywpar_points where user_id = $user_id AND id > $from_id";
			$res   = $wpdb->get_row( $query );

			if ( ! empty( $res ) ) {
				return $res->usable_points;
			}
		}

		/**
		 * Add Point to the user
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $user_id
		 * @param $points
		 * @param $action
		 * @param $order_id
		 *
		 * @return void
		 */
		public function add_points( $user_id, $points, $action, $order_id ) {
			$current_point = get_user_meta( $user_id, '_ywpar_user_total_points', true );
			$p = $current_point + $points;
			update_user_meta( $user_id, '_ywpar_user_total_points', $p > 0 ? $p : 0 );
			YITH_WC_Points_Rewards()->register_log( $user_id, $action, $order_id, $points );
		}


		/**
		 * @param $user_id
		 *
		 * @return mixed
		 */
		public function count_orders( $user_id ) {

			return get_user_meta( $user_id, '_order_count', true );
		}


		/**
		 * @param $comment_ID
		 * @param $status
		 */
		public function add_order_points_with_review( $comment_ID, $status ) {
			//only if the review is approved assign the point to the user
			if ( ( $status !== 'approve' && $status != 1 ) || ! is_user_logged_in() ) {
				return;
			}

			$comment = get_comment( $comment_ID );

			//only if is a review
			$post_type = get_post_type( $comment->comment_post_ID );

			if ( 'product' != $post_type ) {
				return;
			}

			//check if the review is set as extra-point rule
			$extrapoint_rules = YITH_WC_Points_Rewards()->get_option( 'extra_points' );

			if ( ! is_array( $extrapoint_rules ) || array_search( 'reviews', array_column( $extrapoint_rules, 'option' ) ) === false ) {
				return;
			}

			$this->extra_points( array( 'reviews' ), $comment->user_id );
		}

		public function add_order_points_with_advanced_reviews( $review_id, $status ) {
			//only if the review is approved assign the point to the user
			if ( $status != 1 || ! is_user_logged_in() ) {
				return;
			}

			//check if the review is set as extra-point rule
			$extrapoint_rules = YITH_WC_Points_Rewards()->get_option( 'extra_points' );

			if ( ! is_array( $extrapoint_rules ) || array_search( 'reviews', array_column( $extrapoint_rules, 'option' ) ) === false ) {
				return;
			}

			$review_user = get_post_meta( $review_id, '_ywar_review_user_id', true );
			$this->extra_points( array( 'reviews' ), $review_user );
		}

		/**
		 *
		 * @param $customer_user
		 */
		public function extrapoints_to_new_customer( $customer_user ) {
			//check if the review is set as extra-point rule
			$extrapoint_rules = YITH_WC_Points_Rewards()->get_option( 'extra_points' );

			if ( ! is_array( $extrapoint_rules ) || array_search( 'registration', array_column( $extrapoint_rules, 'option' ) ) === false ) {
				return;
			}

			$this->extra_points( array( 'registration' ), $customer_user );
		}
	}


}

/**
 * Unique access to instance of YITH_WC_Points_Rewards_Earning class
 *
 * @return \YITH_WC_Points_Rewards_Earning
 */
function YITH_WC_Points_Rewards_Earning() {
	return YITH_WC_Points_Rewards_Earning::get_instance();
}

