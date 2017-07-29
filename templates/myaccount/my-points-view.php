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

if ( ! is_user_logged_in() ) { ?>

	<p><?php _e( 'You must to be logged in to view your points.', 'yith-woocommerce-points-and-rewards' ) ?></p>
	<?php
	return;
}


$points   = get_user_meta( get_current_user_id(), '_ywpar_user_total_points', true );
$points   = ( $points == '' ) ? 0 : $points;
$singular = YITH_WC_Points_Rewards()->get_option( 'points_label_singular' );
$plural   = YITH_WC_Points_Rewards()->get_option( 'points_label_plural' );

$history = YITH_WC_Points_Rewards()->get_history( get_current_user_id() );

?>
<div class="ywpar-wrapper">
	<h2><?php echo apply_filters( 'ywpar_my_account_my_points_title', sprintf( __( 'My %s', 'yith-woocommerce-points-and-rewards' ), $plural ) ); ?></h2>

	<p><?php printf( __( 'You have', 'yith-woocommerce-points-and-rewards' ) . _n( ' <strong>%s</strong> ' . $singular, ' <strong>%s</strong> ' . $plural, $points, 'yith-woocommerce-points-and-rewards' ), $points ) ?></p>


	<h3><?php echo apply_filters( 'ywpar_my_account_my_points_history_title', sprintf( __( 'My %s History', 'yith-woocommerce-points-and-rewards' ), $singular ) ); ?></h3>


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
						<?php echo date_i18n( wc_date_format(), strtotime( $item->date_earning ) ) ?>
					</td>
					<td class="ywpar_points_rewards-action">
						<?php echo YITH_WC_Points_Rewards()->get_action_label( $item->action ) ?>
					</td>
					<td class="ywpar_points_rewards-order">
						<?php
						if ( $item->order_id != 0 ):
							$order = wc_get_order( $item->order_id );
							if ( $order ) {
								echo '<a href="' . esc_url( $order->get_view_order_url() ) . '">#' . $order->get_order_number() . '</a>';
							} else {
								echo '#' . $item->order_id;
							}
						endif ?>
					</td>
					<td class="ywpar_points_rewards-points" width="1%">
						<?php echo $item->amount ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
