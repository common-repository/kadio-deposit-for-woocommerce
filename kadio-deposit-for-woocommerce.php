<?php

/**
 * Plugin Name: Kadio Deposit for Woocommerce
 * Plugin URI: https://df.kadio.fr/nos-produits/kadio-checkout-deposit-for-woocommerce/
 * Description: This plugin allows making woocommerce deposit payment.
 * Version: 2.1.0
 * Author: KADIO Consulting
 * Author URI: http://kadio.fr
 *
 * Text Domain: kadio-deposit-for-woocommerce
 *
 * License: A "GPL2"  General Public License
 */

if (!defined('WPINC')) {
    die();
}

if (!defined('KDC_WD_BASE_PATH')) {
    define("KDC_WD_BASE_PATH", dirname(__FILE__));
}

if (!defined('KDC_PLUGIN_DIR_PATH')) {
    define("KDC_PLUGIN_DIR_PATH", plugin_dir_path( __FILE__ ));
}

require KDC_WD_BASE_PATH . "/includes/functions.php";
require KDC_WD_BASE_PATH . "/includes/class-init.php";
require KDC_WD_BASE_PATH . "/includes/class-controller.php";
require KDC_WD_BASE_PATH . "/includes/class-wc-admin.php";
require KDC_WD_BASE_PATH . "/includes/class-kadio-cart.php";
require KDC_WD_BASE_PATH . "/includes/class-kadio-deposit.php";
require KDC_WD_BASE_PATH . "/includes/class-kadio-payments.php";
// require KDC_WD_BASE_PATH . "/includes/class-kdc-email-customer-completed-order.php";

$kadioInit = new Kadio_Init();

add_action('plugins_loaded', 'kadio_deposit_load_plugin');

function kadio_deposit_load_plugin()
{
    load_plugin_textdomain('kadio-deposit-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    global $kadioInit;
    if ($kadioInit->kadio_deposit_is_environment_compatible()) {
        new Kadio_WC_Admin();
        new Kadio_Cart();
        new Kadio_Deposit();
        new Kadio_Payments();
    }
}
