<?php

use Tygh\Payments\Processors\Fondy;

$fondy = new Fondy();

if (defined('PAYMENT_NOTIFICATION')) {

    $body = json_decode(file_get_contents('php://input'), true);

    if (!empty($_REQUEST['order_id'])) {
        $order_id = $_REQUEST['order_id'];
    }

    $order_info = fn_get_order_info($order_id);
    if (empty($processor_data) && !empty($order_info)) {
        $processor_data = fn_get_processor_data($order_info['payment_id']);
    }

    $_REQUEST['order_id'] = $order_info['timestamp'] . '_' . $_REQUEST['order_id'];

    $response = $fondy->isPaymentValid([
        'merchant' => $processor_data['processor_params']['merchant_id'],
        'secretkey' => $processor_data['processor_params']['password']
    ], $body);

    if ($response === true) {

        if($mode == 'response') {
            $pp_response = ['order_status' => ($processor_data['processor_params']['transaction_method'] == 'hold') ? $processor_data['processor_params']['status_hold'] : $processor_data['processor_params']['paid_order_status']];
            fn_finish_payment($order_id, $pp_response);
            fn_clear_cart($_SESSION['cart']);
            fn_order_placement_routines('route', $order_id);
            exit();
        }

        if ($mode == 'ok' && $body['order_status'] == 'approved') {

            if ($order_info['status'] == $processor_data['processor_params']['paid_order_status'] && $processor_data['processor_params']['transaction_method'] == 'hold') {
                $order_status = $processor_data['processor_params']['paid_order_status'];
            } else {
                $order_status = ($processor_data['processor_params']['transaction_method'] == 'hold') ? $processor_data['processor_params']['status_hold'] : $processor_data['processor_params']['paid_order_status'];
            }

            $pp_response = [
                'order_status' => $order_status,
                'payment_id' => $body['payment_id'],
            ];
        } else {
            $pp_response = [
                'order_status' => 'F',
                'order_status_fondy' => $body['order_status'],
            ];
        }

        fn_change_order_status($order_id, $pp_response['order_status'], '', false);
        fn_update_order_payment_info($order_id, ['order_id' => $_REQUEST['order_id'], 'payment_id' => $body['payment_id']]);
        fn_finish_payment($order_id, $pp_response);
        fn_redirect('/cart');
        exit();
    }
    $pp_response = ['order_status' => ($processor_data['processor_params']['transaction_method'] == 'hold') ? $processor_data['processor_params']['status_hold'] : $processor_data['processor_params']['paid_order_status']];
    fn_finish_payment($order_id, $pp_response);
    fn_clear_cart($_SESSION['cart']);
    fn_redirect('checkout.complete');
    exit();
} else {

    if (empty($processor_data) && !empty($order_info)) {
        $processor_data = fn_get_processor_data($order_info['payment_id']);
    }

    $currency_f = CART_SECONDARY_CURRENCY;
    if ($processor_data['processor_params']['currency'] == 'shop_cur') {
        $amount = fn_format_price_by_currency($order_info['total']);
    } else {
        $amount = fn_format_price($order_info['total'], $processor_data['processor_params']['currency']);
        $currency_f = $processor_data['processor_params']['currency'];
    }

    $payment_data = array(
        'order_id' => $order_info['timestamp'] . '_' . $order_info['order_id'],
        'merchant_id' => $processor_data['processor_params']['merchant_id'],
        'order_desc' => '#' . $order_info['order_id'],
        'amount' => round($amount * 100),
        'currency' => $currency_f,
        'response_url' => fn_url("payment_notification.response?payment=fondy&order_id=" . $order_info['order_id'], AREA, 'current'),
        'server_callback_url' => fn_url('index.php?dispatch=payment_notification.ok&payment=fondy&order_id=' . $order_info['order_id']),
        'lang' => $processor_data['processor_params']['language'],
        'sender_email' => $order_info['email'],
    );

    if ($processor_data['processor_params']['transaction_method'] == 'hold') {
        $payment_data['preauth'] = 'Y';
    }

    $payment_data['signature'] = $fondy->getSignature($payment_data, $processor_data['processor_params']['password']);

    $response = $fondy->generateFondyUrl($payment_data);
    fn_update_order_payment_info($order_info['order_id'], ['order_id' => $payment_data['order_id'], 'response_status' => $response['response_status']]);

    if ($response['result'] == true) {
        fn_create_payment_form($response['url'], [], 'Fondy', true, 'GET');
    } else {
        fn_update_order_payment_info($order_info['order_id'], ['request_id' => $response['request_id'], 'message' => $response['message']]);
        fn_print_r($response);
    }
}
