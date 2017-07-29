<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Text Plugin Admin View
 *
 * @package    Yithemes
 * @author     Emanuela Castorina <emanuela.castorina@yithemes.it>
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$id    = $this->_panel->get_id_field( $option['id'] );
$name  = $this->_panel->get_name_field( $option['id'] );
$class = isset( $option['class'] ) ? $option['class'] : '';

?>
<div id="<?php echo $id ?>-container" <?php if ( isset( $option['deps'] ) ): ?>data-field="<?php echo $id ?>"
     data-dep="<?php echo $this->get_id_field( $option['deps']['ids'] ) ?>"
     data-value="<?php echo $option['deps']['values'] ?>" <?php endif ?>
     class="yit_options rm_option rm_input rm_text <?php echo $class ?>">
	<div class="option">

		<div class="inner-option">
			<label
				for="csv_format"><?php _e( 'Csv Format', 'yith-woocommerce-points-and-rewards' ) ?></label>
			<div class="select_wrapper">

				<select id="csv_format" name="csv_format">
					<option value="id"
					        selected><?php _e( 'User Id / Points', 'yith-woocommerce-points-and-rewards' ) ?></option>
					<option
						value="email"><?php _e( 'Email / Points', 'yith-woocommerce-points-and-rewards' ) ?></option>
				</select>

			</div>
			<span
				class="description"><?php _e( 'Choose the format of csv file', 'yith-woocommerce-points-and-rewards' ) ?></span>
		</div>
		<div class="inner-option">
			<label
				for="csv_import_action"><?php _e( 'Csv Import Action', 'yith-woocommerce-points-and-rewards' ) ?></label>
			<div class="select_wrapper">

				<select id="csv_import_action" name="csv_import_action">
					<option value="add"
					        selected><?php _e( 'Add points to existent points', 'yith-woocommerce-points-and-rewards' ) ?></option>
					<option
						value="remove"><?php _e( 'Override points', 'yith-woocommerce-points-and-rewards' ) ?></option>
				</select>
			</div>
			<span class="description"><?php _e( 'Choose the format of csv file', 'yith-woocommerce-points-and-rewards' ) ?></span>
		</div>
		<div class="inner-option">
			<label for="delimiter"><?php _e( 'Delimiter', 'yith-woocommerce-points-and-rewards' ) ?></label>
			<input type="text" id="delimiter" name="delimiter" value=",">
			<span class="description"><?php _e( 'Choose the delimiter', 'yith-woocommerce-points-and-rewards' ) ?></span>
		</div>
		<div class="inner-option">
			<label for="file_import_csv"><?php _e( 'Import Points from csv file', 'yith-woocommerce-points-and-rewards' ) ?></label>
			<input type="file" id="file_import_csv" name="file_import_csv">
		</div>
		<div class="inner-option">
			<input type="hidden" class="ywpar_safe_submit_field" name="ywpar_safe_submit_field" value="" data-std="">
			<button class="button button-primary" id="ywpar_import_points"><?php _e( 'Import points', 'yith-woocommerce-points-and-rewards' ) ?></button>
		</div>
	</div>
	<div class="clear"></div>
</div>

