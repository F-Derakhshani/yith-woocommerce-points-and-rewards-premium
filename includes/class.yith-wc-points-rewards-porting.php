<?php

if ( !defined( 'ABSPATH' ) || !defined( 'YITH_YWPAR_VERSION' ) ) {
	exit; // Exit if accessed directly
}

/**
 * This class it used to migrate points from WooCommerce Points and Rewards to YITH WooCommerce Points and Rewards Premium
 *
 * @class   YITH_WC_Points_Rewards_Porting
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */
if ( !class_exists( 'YITH_WC_Points_Rewards_Porting' ) ) {

	class YITH_WC_Points_Rewards_Porting {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WC_Points_Rewards_Porting
		 */
		protected static $instance;

		public $admin_notices= array();


		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WC_Points_Rewards_Porting
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
			
		}

		public function migrate_points( ){

			if( !class_exists('WC_Points_Rewards')){
				return;
			}

			$success = 0;

			$porting_done = get_option( "yith_ywpar_porting_done" );

			if( $porting_done ){
				return $success;
			}

			global $wpdb;
			$actions = array(
				'product-review'   => 'reviews_exp',
				'order-redeem'     => 'redeemed_points',
				'account-signup'   => 'registration_exp',
				'order-placed'     => 'order_completed',
				'expire'           => 'expired_points',
				'admin-adjustment' => 'admin_action',
				'order-cancelled'  => 'order_refund',
			);

			// initialize the custom table names
			$user_points_log_db_tablename = $wpdb->prefix . 'wc_points_rewards_user_points_log';
			$user_points_db_tablename     = $wpdb->prefix . 'wc_points_rewards_user_points';

			$sql_users_old = "SELECT wpp.user_id  FROM $user_points_db_tablename as wpp  WHERE wpp.points = 100 AND wpp.date LIKE '2015-07-22%'";
			$results_users_old = $wpdb->get_col( $sql_users_old );

			$sql = "SELECT
					wplog.user_id as user_id,
					wplog.type as type,
					wplog.points as points,
					wplog.order_id as order_id,
					wplog.date as datelog,
					up.id as point_id,
					up.points_balance as points_balance,
					up.date as up_date
					FROM $user_points_log_db_tablename wplog LEFT JOIN $user_points_db_tablename up ON(wplog.user_points_id = up.id) WHERE 1 ";

			$results = $wpdb->get_results( $sql );

			$users = array();
			$counter = 0;

			if( $results ){

				$ywpar_table = $wpdb->prefix . 'yith_ywpar_points_log';
				$initial_query = "INSERT INTO $ywpar_table ( user_id, action, order_id, amount, date_earning, cancelled ) VALUES ";

				$values = array();
				$place_holders = array();
				$step = 100;

				foreach ( $results as $item ) {

					if( isset( $users[ $item->user_id] ) ){
						$users[ $item->user_id] = $users[ $item->user_id] + $item->points;
					}else{
						$users[ $item->user_id] = $item->points;
					}


					if( ! in_array( $item->type, array( 'expire', 'order-cancelled' ) ) ){
						array_push($values, $item->user_id,$actions[$item->type], $item->order_id ? $item->order_id : 0 ,$item->points, $item->datelog, '' );
						$place_holders[] = "('%d', '%s', '%d', '%d', '%s', '%s')";

					}else{
						array_push($values, $item->user_id,$actions[$item->type], $item->order_id ? $item->order_id : 0 ,$item->points, $item->up_date, $item->datelog);
						$place_holders[] = "('%d', '%s', '%d', '%d', '%s', '%s')";

					}

					if( $counter%$step == 0 ){
						$initial_query .= implode(', ', $place_holders);
						$wpdb->query( $wpdb->prepare("$initial_query ", $values));

						$values = array();
						$place_holders = array();
						$initial_query = "INSERT INTO $ywpar_table ( user_id, action, order_id, amount, date_earning, cancelled ) VALUES ";

					}

					$counter++;
				}

				if ( ! empty( $place_holders ) ) {
					$initial_query .= implode( ', ', $place_holders );
					$wpdb->query( $wpdb->prepare( "$initial_query ", $values ) );
				}

			}

			if( $users ){
				foreach( $users as $user_id => $points){
					$current_point = get_user_meta( $user_id, '_ywpar_user_total_points', true);

					if( is_array($results_users_old) && in_array($user_id, $results_users_old) ){
						$current_point += 100;
					}

					$new_points = $current_point + $points;
					update_user_meta( $user_id, '_ywpar_user_total_points', ( $new_points > 0 ) ? $new_points : 0);
				}
			}

			update_option( "yith_ywpar_porting_done", true );

			return $counter;
		}

		public function import( $posted ){

			if (!isset($_FILES['file_import_csv']) || !is_uploaded_file($_FILES['file_import_csv']['tmp_name'])) {
				return;
			}

			$uploaddir = wp_upload_dir();

			$userfile_tmp = $_FILES['file_import_csv']['tmp_name'];
			$userfile_name = $_FILES['file_import_csv']['name'];

			if ( !move_uploaded_file($userfile_tmp, $uploaddir . $userfile_name)) {
				return;
			}

			$this->import_from_csv( $uploaddir . $userfile_name, $_REQUEST['delimiter'], $_REQUEST['csv_format'], $_REQUEST['csv_import_action']);

		}

		/**
		 * Import points from a csv file
		 *
		 * @param $file
		 *
		 * @param $delimiter
		 * @param $format
		 * @param $action
		 *
		 * @return mixed|void
		 */
		public function import_from_csv( $file, $delimiter, $format, $action ) {

			$response = '';
			$this->import_start();

			$loop = 0;

			if ( ( $handle = fopen( $file, "r" ) ) !== false ) {

				$header = fgetcsv( $handle, 0, $delimiter );

				if ( 2 === sizeof( $header ) ) {

					while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
						
						if ( ! is_array( $row ) || count( $row ) < 2 ) {
							continue;
						}

						list( $field1, $points ) = $row;
						//check if the user exists
						$user = get_user_by( $format, $field1 );
						if ( $user === false ) {
							//user id does not exist
							continue;
						} else {
							//user id exists
							if ( $action == 'remove' ) {
								//delete all the entries in the log table of user
								//remove points from the usermeta
								YITH_WC_Points_Rewards()->reset_user_points( $user->ID );
							}

							YITH_WC_Points_Rewards_Earning()->add_points( $user->ID, $points, '', 0 );
							$loop++;
						}

					}

					$response = $loop;

				} else {

					$this->admin_notices[] = array(
						'class' => 'error',
						'message' => __( 'The CSV is invalid.', 'yith-woocommerce-points-and-rewards' )
					);
				}

				fclose( $handle );
			}


			return apply_filters( 'ywpar_import_from_csv_response', $response, $loop, $file, $delimiter, $format, $action );
		}

		/**
		 * Start import
		 *
		 * @return void
		 * @since 1.0.0
		 */
		private function import_start() {
			if ( function_exists( 'gc_enable' ) ) {
				gc_enable();
			}
			@set_time_limit( 0 );
			@ob_flush();
			@flush();
			@ini_set( 'auto_detect_line_endings', '1' );
		}

		public function show_update_error() {

			if ( ! $this->admin_notices ) {
				return;
			}

			foreach ( $this->admin_notices as $admin_notice ) {
				printf( '<div class="%s"><p>%s</p>', $admin_notice['class'], $admin_notice['message'] );
			}

		}

	}


}

/**
 * Unique access to instance of YITH_WC_Points_Rewards_Porting class
 *
 * @return \YITH_WC_Points_Rewards_Porting
 */
function YITH_WC_Points_Rewards_Porting() {
	return YITH_WC_Points_Rewards_Porting::get_instance();
}
