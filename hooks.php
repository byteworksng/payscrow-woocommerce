<?php
/**
 * Created by Byteworks Limited.
 * Author: Chibuzor Ogbu
 * Date: 5/30/16
 * Time: 12:34 AM
 */

// Actions
add_action('valid-payscrow-standard-confirm-request', array($this, 'process_payscrow_response'));
add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
add_action('woocommerce_receipt_payscrow', array(&$this, 'receipt_page'));


// Payment listener/API hook
add_action('woocommerce_api_wc_gateway_payscrow', array($this, 'check_payscrow_response'));



