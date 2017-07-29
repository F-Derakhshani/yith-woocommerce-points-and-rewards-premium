<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWPAR_VERSION' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Implements features of YITH WooCommerce Points and Rewards
 *
 * @class   YITH_WC_Points_Rewards
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */
if ( ! class_exists( 'YITH_WC_Points_Rewards' ) ) {

	class YITH_WC_Points_Rewards {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WC_Points_Rewards
		 */
		protected static $instance;

		/**
		 * @var string
		 */
		public $plugin_options = 'yit_ywpar_options';

		/**
		 * @var array
		 */
		private $usermeta_list = array();

		/**
		 * @var array
		 */
		private $ordermeta_list = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WC_Points_Rewards
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

			add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );

			/* general actions */
			add_filter( 'woocommerce_locate_core_template', array( $this, 'filter_woocommerce_template' ), 10, 3 );
			add_filter( 'woocommerce_locate_template', array( $this, 'filter_woocommerce_template' ), 10, 3 );

			//add shortcode
			add_shortcode( 'yith_ywpar_points', array( $this, 'add_shortcode' ) );
			add_shortcode( 'yith_ywpar_points_list', array( $this, 'add_shortcode_list' ) );

			if ( ! $this->is_enabled() ) {
				return false;
			}

			/* email actions and filter */
			add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_emails' ) );
			add_action( 'woocommerce_init', array( $this, 'load_wc_mailer' ) );
			add_action( 'wp_loaded', array( $this, 'set_cron' ) );

			if ( $this->get_option( 'enable_update_point_email' ) == 'yes' ) {
				add_action( 'ywpar_cron', array( $this, 'send_email_update_points' ) );
			}

			//register widget
			add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		}

		/**
		 * Load YIT Plugin Framework
		 *
		 * @since  1.0.0
		 * @return boolean
		 * @author Emanuela Castorina
		 */
		public function is_enabled() {

			$enabled = $this->get_option( 'enabled' );

			if ( $enabled == 'yes' ) {
				return true;
			}

			return false;
		}

		/**
		 * Set Cron
		 *
		 * Set ywpar_cron action
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function set_cron() {
			if ( ! wp_next_scheduled( 'ywpar_cron' ) ) {
				wp_schedule_event( time(), 'daily', 'ywpar_cron' );
			}
		}

		/**
		 * Load YIT Plugin Framework
		 *
		 * @since  1.0.0
		 * @return void
		 * @author Emanuela Castorina
		 */
		public function plugin_fw_loader() {
			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( ! empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once( $plugin_fw_file );
				}
			}
		}

		/**
		 * Add a record inside the table of log
		 *
		 * @param            $user_id
		 * @param            $action
		 * @param            $order_id
		 * @param            $amount
		 * @param bool|false $data_earning
		 * @param bool|false $expired
		 */
		public function register_log( $user_id, $action, $order_id, $amount, $data_earning = false, $expired = false ) {
			global $wpdb;
			$date       = apply_filters( 'ywpar_points_registration_date', date_i18n( "Y-m-d H:i:s" ) );
			$table_name = $wpdb->prefix . 'yith_ywpar_points_log';
			$args       = array(
				'user_id'      => $user_id,
				'action'       => $action,
				'order_id'     => $order_id,
				'amount'       => $amount,
				'date_earning' => ( $data_earning ) ? $data_earning : $date
			);

			if ( $expired ) {
				$args['cancelled'] = $date;
			}

			$wpdb->insert( $table_name, $args );
		}

		/**
		 * Reset points of a user
		 *
		 * @since 1.1.3
		 *
		 * @param $user_id
		 *
		 * @return void
		 */
		public function reset_user_points( $user_id ) {

			//remove the history
			$this->remove_user_log( $user_id );
			//remove points to user
			$user_meta = $this->get_usermeta_list();
			foreach ( $user_meta as $meta ) {
				delete_user_meta( $user_id, $meta );
			}

		}

		/**
		 * Delete the history of a user
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $user_id
		 */
		public function remove_user_log( $user_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'yith_ywpar_points_log';
			$wpdb->delete( $table_name, array( 'user_id' => $user_id ), array( '%d' ) );
		}

		/**
		 * Get options from db
		 *
		 * @access  public
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 *
		 * @param $option string
		 * @param $value  mixed
		 *
		 * @return mixed
		 */
		public function get_option( $option, $value = false ) {
			// get all options
			$options = get_option( $this->plugin_options );

			if ( isset( $options[ $option ] ) ) {
				$value = $options[ $option ];
			}

			return $value;
		}

		/**
		 * Locate default templates of woocommerce in plugin, if exists
		 *
		 * @param $core_file     string
		 * @param $template      string
		 * @param $template_base string
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function filter_woocommerce_template( $core_file, $template, $template_base ) {

			$located = yith_ywpar_locate_template( $template );

			if ( $located ) {
				return $located;
			} else {
				return $core_file;
			}
		}

		/**
		 * Filters woocommerce available mails, to add wishlist related ones
		 *
		 * @param $emails array
		 *
		 * @return array
		 * @since 1.0
		 */
		public function add_woocommerce_emails( $emails ) {
			$emails['YITH_YWPAR_Expiration']    = include( YITH_YWPAR_INC . 'emails/class.yith-ywpar-expiration.php' );
			$emails['YITH_YWPAR_Update_Points'] = include( YITH_YWPAR_INC . 'emails/class.yith-ywpar-update-points.php' );

			return $emails;
		}

		/**
		 * Loads WC Mailer when needed
		 *
		 * @return void
		 * @since 1.0
		 */
		public function load_wc_mailer() {
			add_action( 'expired_points_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
			add_action( 'update_points_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
		}

		/**
		 * @param $user_id
		 *
		 * @return array|null|object
		 */
		public function get_history( $user_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'yith_ywpar_points_log';
			$query      = "SELECT ywpar_points.* FROM $table_name as ywpar_points where user_id = $user_id ORDER BY date_earning DESC LIMIT 0,15";
			$items      = $wpdb->get_results( $query );

			return $items;
		}

		/**
		 * Get the label for an action
		 *
		 * @param $label     string
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function get_action_label( $label ) {
			$label = $this->get_option( 'label_' . $label );
			if ( ! $label ) {
				return '';
			}

			return $label;
		}

		/**
		 * @return bool
		 */
		public function set_expired_points() {
			global $wpdb;
			$table_name  = $wpdb->prefix . 'yith_ywpar_points_log';
			$date        = date( "Y-m-d H:i:s" );
			$expire_date = $this->get_option( 'days_before_expiration' );

			if ( $expire_date == '' || $expire_date <= 0 ) {
				return false;
			}

			$query = "SELECT * FROM $table_name where ( date_earning <= CURDATE() - INTERVAL $expire_date DAY ) AND amount > 0 AND cancelled IS NULL ORDER BY date_earning DESC";
			$items = $wpdb->get_results( $query );

			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					$this->register_log( $item->user_id, 'expired_points', $item->order_id, - abs( $item->amount ), false, true );
					$wpdb->update( $table_name, array( 'cancelled' => $date ), array( 'id' => $item->id ) );
					$current_points = get_user_meta( $item->user_id, '_ywpar_user_total_points', true );
					$new_points     = $current_points - $item->amount;

					update_user_meta( $item->user_id, '_ywpar_user_total_points', ( $new_points <= 0 ? 0 : $new_points ) );
				}
			}

		}

		/**
		 * @return bool
		 */
		public function send_email_before_expiration() {

			if ( YITH_WC_Points_Rewards()->get_option( 'send_email_before_expiration_date', 'no' ) != 'yes' ) {
				return false;
			}

			global $wpdb;

			$table_name           = $wpdb->prefix . 'yith_ywpar_points_log';
			$expire_date          = $this->get_option( 'days_before_expiration' ); //validity time in day
			$days_before_send     = $this->get_option( 'send_email_days_before' );  //days before send email
			$expire_date_string   = strtotime( "+" . $days_before_send . " day", time() );
			$interval             = $expire_date - $days_before_send;
			$email_content_option = $this->get_option( 'expiration_email_content' );

			if ( $expire_date == '' || $expire_date <= 0 || $days_before_send == '' || $days_before_send <= 0 ) {
				return false;
			}

			$query = "SELECT * FROM $table_name where ( date_earning <= CURDATE() - INTERVAL $interval DAY ) AND amount > 0 AND cancelled IS NULL ORDER BY user_id,date_earning DESC";
			$items = $wpdb->get_results( $query );

			if ( ! empty( $items ) ) {

				$user_sent = array();

				foreach ( $items as $item ) {

					if ( ! $this->is_user_enabled( 'earn', $item->user_id ) ) {
						continue;
					}
					$email_content = $email_content_option;

					$user = get_user_by( 'id', $item->user_id );


					if ( in_array( $item->user_id, $user_sent ) ) {
						continue;
					}

					$current_points = get_user_meta( $item->user_id, '_ywpar_user_total_points', true );

					$email_content = str_replace( '{username}', $user->user_login, $email_content );
					$email_content = str_replace( '{expiring_points}', abs( $item->amount ), $email_content );
					$email_content = str_replace( '{label_points}', YITH_WC_Points_Rewards()->get_option( 'points_label_plural' ), $email_content );
					$email_content = str_replace( '{expiring_date}', date_i18n( wc_date_format(), $expire_date_string ), $email_content );
					$email_content = str_replace( '{total_points}', $current_points, $email_content );

					$args = array(
						'user_email'     => $user->user_email,
						'email_content'  => $email_content,
						'expiration_day' => $expire_date,
						'user_id'        => $item->user_id,
						'item_id'        => $item->id
					);

					$user_sent[] = $item->user_id;

					do_action( 'expired_points_mail', $args );

				}
			}
		}

		/**
		 * Send the email if the user has updated his points
		 */
		public function send_email_update_points() {
			global $wpdb;

			$table_name = $wpdb->prefix . 'yith_ywpar_points_log';
			$query      = "SELECT * FROM $table_name where ( date_earning >= CURDATE() - INTERVAL 1 DAY ) AND cancelled IS NULL GROUP BY user_id";

			$items = $wpdb->get_results( $query );


			if ( ! empty( $items ) ) {
				$current_user_id = 0;

				foreach ( $items as $item ) {

					if ( ! $this->is_user_enabled( 'earn', $item->user_id ) ) {
						continue;
					}

					if ( $current_user_id != $item->user_id ) {
						$current_user_id = $item->user_id;
					}

					$email_content = $this->get_option( 'update_point_email_content' );

					$query   = "SELECT * FROM $table_name where ( date_earning >= CURDATE() - INTERVAL 1 DAY ) AND cancelled IS NULL and user_id = $current_user_id ORDER BY date_earning";
					$history = $wpdb->get_results( $query );

					if ( ! empty( $history ) ) {

						$user           = get_user_by( 'id', $current_user_id );
						$current_points = get_user_meta( $current_user_id, '_ywpar_user_total_points', true );

						ob_start();
						$email_content = str_replace( '{username}', $user->user_login, $email_content );
						$email_content = str_replace( '{label_points}', strtolower( $this->get_option( 'points_label_plural' ) ), $email_content );
						$email_content = str_replace( '{total_points}', $current_points, $email_content );

						wc_get_template( 'emails/latest-updates.php', array( 'history' => $history ) );

						$args = array(
							'user_email'    => $user->user_email,
							'email_content' => str_replace( '{latest_updates}', ob_get_clean(), $email_content )
						);


						do_action( 'update_points_mail', $args );
					}

				}

			}
		}

		/**
		 * Empty the table of log and delete the post meta to order and usermeta to users
		 *
		 * @return void
		 */
		public function reset_points() {
			global $wpdb;

			$table_name = $wpdb->prefix . 'yith_ywpar_points_log';

			$user_meta = "'" . implode( "','", $this->get_usermeta_list() ) . "'";
			$post_meta = "'" . implode( "','", $this->get_ordermeta_list() ) . "'";

			$wpdb->query( "TRUNCATE TABLE $table_name" );
			$wpdb->query( "DELETE FROM {$wpdb->usermeta}  WHERE {$wpdb->usermeta}.meta_key IN( {$user_meta} )" );
			$wpdb->query( "DELETE FROM {$wpdb->postmeta}  WHERE {$wpdb->postmeta}.meta_key IN( {$post_meta} )" );

			delete_option( 'yith_ywpar_porting_done' );

		}

		/**
		 * @param $number
		 *
		 * @return array
		 */
		public function user_list_points( $number ) {
			$user_query = new WP_User_Query( array(
				                                 'number'   => $number,
				                                 'meta_key' => '_ywpar_user_total_points',
				                                 'orderby'  => 'meta_value_num',
				                                 'order'    => 'DESC',
				                                 'fields'   => array( 'ID', 'display_name' )
			                                 ) );
			$users      = $user_query->get_results();

			return $users;
		}

		/**
		 * @param $user_id
		 *
		 * @return void
		 * @internal param $number
		 */
		public function user_reset_points( $user_id ) {
			global $wpdb;
			$user_meta = "'" . implode( "','", $this->get_usermeta_list() ) . "'";
			$wpdb->query( "DELETE FROM {$wpdb->usermeta}  WHERE {$wpdb->usermeta}.meta_key IN( {$user_meta} )" );
		}

		/**
		 * @param $number
		 *
		 * @return array
		 */
		public function user_list_discount( $number ) {
			$user_query = new WP_User_Query( array(
				                                 'number'   => $number,
				                                 'meta_key' => '_ywpar_user_total_discount',
				                                 'orderby'  => 'meta_value_num',
				                                 'order'    => 'DESC',
				                                 'fields'   => array( 'ID', 'display_name' )
			                                 ) );
			$users      = $user_query->get_results();

			return $users;
		}

		/**
		 * @param      $atts
		 * @param null $content
		 *
		 * @return string|void
		 */
		public function add_shortcode( $atts, $content = null ) {

			if ( ! $this->is_user_enabled() ) {
				return;
			}

			$a = shortcode_atts( array(
				                     'label' => __( 'Your credit is ', 'yith-woocommerce-points-and-rewards' )
			                     ), $atts );


			$points   = get_user_meta( get_current_user_id(), '_ywpar_user_total_points', true );
			$points   = ( $points == '' ) ? 0 : $points;
			$singular = YITH_WC_Points_Rewards()->get_option( 'points_label_singular' );
			$plural   = YITH_WC_Points_Rewards()->get_option( 'points_label_plural' );


			ob_start();

			echo '<p>' . $a['label'] . ' ';
			printf( _n( '<strong>%s</strong> ' . $singular, '<strong>%s</strong> ' . $plural, $points, 'yith-woocommerce-points-and-rewards' ), $points );
			echo '</p>';

			return ob_get_clean();


		}

		/**
		 * @param      $atts
		 * @param null $content
		 *
		 * @return string|void
		 */
		public function add_shortcode_list( $atts, $content = null ) {

			if ( ! $this->is_user_enabled() ) {
				return;
			}

			ob_start();

			wc_get_template( 'myaccount/my-points-view.php' );

			return ob_get_clean();

		}

		/**
		 * Return if the user is enable to earn or redeem points
		 *
		 * @param string $action
		 *
		 * @param string $user_id
		 *
		 * @return bool
		 */
		public function is_user_enabled( $action = 'earn', $user_id = '' ) {

			if ( $user_id ) {
				$user = get_user_by( 'id', $user_id );
			} elseif ( is_user_logged_in() ) {
				$user = wp_get_current_user();
			} else {
				return false;
			}

			$roles_enabled = ( $action == 'earn' ) ? $this->get_option( 'user_role_enabled', array() ) : $this->get_option( 'user_role_redeem_enabled', array() );

			$return = false;

			if ( ! $roles_enabled || in_array( 'all', (array) $roles_enabled ) || count( array_intersect( $user->roles, (array) $roles_enabled ) ) ) {
				$return = true;
			}

			return apply_filters( 'ywpar_enabled_user', $return, $user, $action );

		}

		/**
		 * Returns the list of all usermeta used be plugin
		 *
		 * @return array
		 * @since 1.1.3
		 */
		public function get_usermeta_list() {
			$usermeta = array( '_ywpar_user_total_points', '_ywpar_user_total_discount', '_ywpar_extrapoint' );

			return apply_filters( 'ywpar_usermeta_list', $usermeta );
		}

		/**
		 * Returns the list of all postmeta of orders used be plugin
		 *
		 * @return array
		 * @since 1.1.3
		 */
		public function get_ordermeta_list() {
			$ordermeta = array( '_ywpar_points_earned', '_ywpar_conversion_points', '_ywpar_total_points_refunded' );

			return apply_filters( 'ywpar_ordermeta_list', $ordermeta );
		}

		/**
		 * Register the widgets
		 *
		 * @since   1.0.0
		 * @author  Emanuela Castorina
		 * @return  void
		 */
		public function register_widgets() {
			register_widget( 'YITH_YWPAR_Points_Rewards_Widget' );
		}

		/**
		 * Clear coupons of points and rewards after use
		 */
		public function clear_coupons() {
			$delete_after_use = YITH_WC_Points_Rewards()->get_option( 'coupon_delete_after_use' );

			if ( $delete_after_use != 'yes' ) {
				return;
			}

			$args = array(
				'post_type'       => 'shop_coupon',
				'posts_per_pages' => -1,
				'meta_key'        => 'ywpar_coupon',
				'meta_value'      => '1'
			);

			$coupons = get_posts( $args );

			if ( ! empty( $coupons ) ) {
				foreach ( $coupons as $coupon ) {
					if ( $delete_after_use == 'yes' ) {
						$usage_count = get_post_meta( $coupon->ID, 'usage_count', true );
						if ( $usage_count == 1 ) {
							wp_delete_post( $coupon->ID, true );
						}
					}
				}
			}
		}

	}

}

/**
 * Unique access to instance of YITH_WC_Points_Rewards class
 *
 * @return \YITH_WC_Points_Rewards
 */
function YITH_WC_Points_Rewards() {
	return YITH_WC_Points_Rewards::get_instance();
}

