<?php
/**
 * Plugin Name: PayScrow Premium Escrow Service for Woocommerce
 * Plugin URI: http://www.payscrow.net/
 * Description:  This extension enables your Woocommerce store for use with the PayScrow Escrow Services. PayScrow is a unique escrow service that improves trusted payments between buyers and sellers. Sellers are able to deliver products or services to buyers knowing that their funds have been secured and vice versa. Visit www.payscrow.net for more details. To use this plugin, simply install and input your unique access key as provided by PayScrow and set maximum delivery duration.
 * Version: 1.0
 * Author: Byteworks Limited
 * Author URI: http://www.byteworksng.com/
 * Requires at least: 4.4.2 and newer
 * Date: 5/29/16
 * Time: 10:56 PM
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: payscrow
 * Domain Path:/lang/
 */

if( preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF']) ) { die('You are not allowed to call this page directly.'); }

add_action('plugins_loaded', 'payscrow_init');

/**
 * Initialize payment gateway
 */
function payscrow_init(){
    if ( !class_exists('WC_Payment_Gateway') ){
        return;
    }
    load_plugin_textdomain('payscrow', False, dirname(plugin_basename(__FILE__)).'/lang/');
    require_once(plugin_basename(  'class-wc-gateway-payscrow.php' ));

    /**
     * inform woocommerce about plugin presence using filters
     * @param $methods
     * @return array
     */
    function wc_payscrow_add_gateway($methods) {
        $methods[] = 'WC_Gateway_Payscrow';
        return $methods;

    }

    add_filter('woocommerce_payment_gateways', 'wc_payscrow_add_gateway');
}



