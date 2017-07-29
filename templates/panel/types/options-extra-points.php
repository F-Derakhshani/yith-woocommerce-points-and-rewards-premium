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

$id   = $this->_panel->get_id_field( $option['id'] );
$name = $this->_panel->get_name_field( $option['id'] );
$extra_points_options  = YITH_WC_Points_Rewards_Admin()->extra_points_options;
?>
<div id="<?php echo $id ?>-container" class="ywpar-sections-group">
    <div class="ywpar-section ywpar-select-wrapper main-section open">
        <div class="section-head">
            <?php _e('Rules', 'yith-woocommerce-points-and-rewards') ?>
        </div>
        <div class="section-body">
            <table class="extra-points">
                <tr>
                    <td>
                        <table>
                            <tr>
                                <th><?php _e('Option', 'yith-woocommerce-points-and-rewards') ?></th>
                                <th><?php _e('Value', 'yith-woocommerce-points-and-rewards') ?></th>
                                <th><?php _e('Points', 'yith-woocommerce-points-and-rewards') ?></th>
                                <th  width="1"><?php _e('Repeat', 'yith-woocommerce-points-and-rewards') ?></th>
                                <th></th>
                            </tr>
                            <?php
                            $suffix_name = $name;
                            $suffix_id = $name;
                            if( !empty( $db_value )  ):
                                $limit = count( $db_value );

                                for ( $i = 1; $i <= $limit; $i ++ ):
                                    $hide_first_remove = ( $i == 1 ) ? ' hide-remove' : '';
                                    if( isset( $db_value[$i]) ):

                            ?>
                                    <tr data-index="<?php echo $i ?>" class="ywpar-select-wrapper">
                                        <td>
                                            <select name="<?php echo $suffix_name ."[{$i}][option]" ?>" id="<?php echo $suffix_id . '[option]' ?>">
                                                <?php foreach ( $extra_points_options['options'] as $key_type => $type ): ?>
                                                    <option value="<?php echo $key_type ?>" <?php selected( $db_value[$i]['option'], $key_type ) ?>><?php echo $type ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="<?php echo $suffix_name . "[{$i}][value]" ?>" id="<?php echo $suffix_name . "[{$i}][value]" ?>" value="<?php echo $db_value[$i]['value'] ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="<?php echo $suffix_name . "[{$i}][points]" ?>" id="<?php echo $suffix_name . "[{$i}][points]" ?>" value="<?php echo $db_value[$i]['points'] ?>">
                                        </td>
                                        <td>
                                            <input type="checkbox" name="<?php echo $suffix_name . "[{$i}][repeat]" ?>" id="<?php echo $suffix_name . "[{$i}][repeat]" ?>" value="1" <?php echo ( isset( $db_value[ $i ]['repeat'] ) && $db_value[ $i  ]['repeat'] == 1 ) ?  'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <span class="ywpar-add-row"></span><span class="remove-row <?php echo $hide_first_remove ?>"></span>
                                        </td>
                                    </tr>
                            <?php endif;
                                endfor ?>
                                <?php else: ?>
                                <tr data-index="1" class="ywpar-select-wrapper">

                                    <td>
                                        <select name="<?php echo $suffix_name ?>[1][option]" id="<?php echo $suffix_name . '[1][option]' ?>">
                                            <?php foreach ( $extra_points_options['options'] as $key_type => $type ): ?>
                                                <option value="<?php echo $key_type ?>"><?php echo $type ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="<?php echo $suffix_name . "[1][value]" ?>" id="<?php echo $suffix_name . "[1][value]" ?>" value="">
                                    </td>
                                    <td>
                                        <input type="text" name="<?php echo $suffix_name . "[1][points]" ?>" id="<?php echo $suffix_name . "[1][points]" ?>" value="">
                                    </td>
                                    <td>
                                        <input type="checkbox" name="<?php echo $suffix_name . "[1][repeat]" ?>" id="<?php echo $suffix_name . "[1][repeat]" ?>" value="0">
                                    </td>
                                    <td>
                                        <span class="ywpar-add-row"></span><span class="remove-row hide-remove"></span>
                                    </td>
                                </tr>
                            <?php endif ?>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
