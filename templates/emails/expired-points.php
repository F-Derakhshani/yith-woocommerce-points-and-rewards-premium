<?php
/**
 * HTML Template Email YITH WooCommerce Points and Rewards
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @version 1.1.3
 * @author  Yithemes
 */


do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<?php echo $email_content ?>

<?php
do_action( 'woocommerce_email_footer', $email );
?>