<?php

use Tygh\Payments\Processors\Fondy;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

function fn_fondy_install()
{
    fn_fondy_uninstall();
    $_data = array(
        'processor' => 'Fondy',
        'processor_script' => 'fondy.php',
        'processor_template' => 'views/orders/components/payments/cc_outside.tpl',
        'admin_template' => 'fondy.tpl',
        'callback' => 'N',
        'type' => 'P',
        'addon' => 'fondy'
    );
    db_query("INSERT INTO ?:payment_processors ?e", $_data);
}

function fn_fondy_uninstall()
{
    db_query("DELETE FROM ?:payment_processors WHERE processor_script = ?s", "fondy.php");
}

function fn_fondy_change_order_status(&$status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order)
{
    $fondy = new Fondy();

    $processor_data = fn_get_processor_data($order_info['payment_id']);

    $currency_f = CART_SECONDARY_CURRENCY;
    if ($processor_data['processor_params']['currency'] == 'shop_cur') {
        $amount = fn_format_price_by_currency($order_info['total']);
    } else {
        $amount = fn_format_price($order_info['total'], $processor_data['processor_params']['currency']);
        $currency_f = $processor_data['processor_params']['currency'];
    }

    if ($processor_data['processor_params']['status_hold'] == $status_from && $status_to == $processor_data['processor_params']['paid_order_status']) {
        $payment_data = [
            'order_id' => ($order_info['payment_info']['order_id']) ? $order_info['payment_info']['order_id'] : $order_info['payment_info']['fondy_order_id'],
            'currency' => $currency_f,
            'amount' => round($amount * 100),
            'merchant_id' => $processor_data['processor_params']['merchant_id'],
        ];

        $payment_data['signature'] = $fondy->getSignature($payment_data, $processor_data['processor_params']['password']);
        $response = $fondy->generateFondyUrl($payment_data, true);

        if ($response['response_status'] == 'success') {
            fn_finish_payment($_REQUEST['order_id'], ['response_status' => $response['response_status']]);
        } else {
            $status_to = 'F';
            fn_update_order_payment_info($_REQUEST['order_id'], ['order_id' => $payment_data['order_id'], 'request_id' => $response['request_id'], 'response_status' => $response['response_status']]);
        }
    }
}
