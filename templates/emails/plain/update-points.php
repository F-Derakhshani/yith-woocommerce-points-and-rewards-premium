<?php
/**
 * Plain Template Email
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @version 1.0.0
 * @author  Yithemes
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo $email_heading . "\n\n";

echo $email_content;
echo "\n****************************************************\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );