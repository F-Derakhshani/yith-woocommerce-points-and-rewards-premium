<?php
/**
 * My Points
 *
 * Shows total of user's points account page
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  Yithemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$singular = YITH_WC_Points_Rewards()->get_option( 'points_label_singular' );
$plural   = YITH_WC_Points_Rewards()->get_option( 'points_label_plural' );

?>
<h2><?php echo apply_filters( 'ywpar_my_account_my_points_title', sprintf( __( '%s', 'yith-woocommerce-points-and-rewards' ), $plural ) ); ?></h2>

<?php if ( $history ) : ?>
	<table class="shop_table ywpar_points_rewards my_account_orders">
		<thead>
		<tr>
			<th class="ywpar_points_rewards-date"><?php _e( 'Date', 'yith-woocommerce-points-and-rewards' ); ?></th>
			<th class="ywpar_points_rewards-action"><?php _e( 'Action', 'yith-woocommerce-points-and-rewards' ); ?></th>
			<th class="ywpar_points_rewards-order"><?php _e( 'Order No.', 'yith-woocommerce-points-and-rewards' ); ?></th>
			<th class="ywpar_points_rewards-points"><?php echo $plural; ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $history as $item ) : ?>
			<tr class="ywpar-item">
				<td class="ywpar_points_rewards-date">
					<?php echo date_i18n( wc_date_format(), strtotime(  $item->date_earning ) ) ?>
				</td>
				<td class="ywpar_points_rewards-action">
					<?php echo YITH_WC_Points_Rewards()->get_action_label( $item->action ) ?>
				</td>
				<td class="ywpar_points_rewards-order">
					<?php
					if( $item->order_id != 0 ):
						$order = wc_get_order( $item->order_id );
					 ?>
					<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
						#<?php echo $order->get_order_number(); ?>
					</a>
					<?php endif ?>
				</td>
				<td class="ywpar_points_rewards-points" width="1%">
					<?php  echo $item->amount ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endif;