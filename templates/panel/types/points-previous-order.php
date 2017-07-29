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

$id        = $this->_panel->get_id_field( $option['id'] );
$name      = $this->_panel->get_name_field( $option['id'] );
$show_data = $option['show_data'];

?>
<div id="<?php echo $id ?>-container" <?php if ( isset( $option['deps'] ) ): ?>data-field="<?php echo $id ?>" data-dep="<?php echo $this->get_id_field( $option['deps']['ids'] ) ?>" data-value="<?php echo $option['deps']['values'] ?>" <?php endif ?> class="yit_options rm_option rm_input rm_text">
    <div class="option">
        <?php if( $show_data ): ?>
        <input type="text" name="<?php echo $name ?>" id="<?php echo $id ?>" value="<?php echo esc_attr( $db_value ) ?>" class="panel-datepicker"/>
        <?php endif ?>
        <button id="<?php echo $id ?>_btn"><?php echo $option['label'] ?></button>
    </div>

    <span class="description"><?php echo $option['desc'] ?></span>
    <div class="clear"></div>
</div>