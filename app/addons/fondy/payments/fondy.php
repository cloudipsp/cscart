<?php

use Tygh\Payments\Processors\Fondy;

$fondy = new Fondy();

if (defined('PAYMENT_NOTIFICATION')) {
    if (!empty($_REQUEST['order_id'])) {
        $order_id = $_REQUEST['order_id'];
    }

    $order_info = fn_get_order_info($order_id);
    if (empty($processor_data) && !empty($order_info)) {
        $processor_data = fn_get_processor_data($order_info['payment_id']);
    }
    unset($_REQUEST['dispatch']);
    unset($_REQUEST['payment']);

    $_REQUEST['order_id'] = $order_info['timestamp'] . '_' . $_REQUEST['order_id'];

    $response = $fondy->isPaymentValid([
        'merchant' => $processor_data['processor_params']['merchant_id'],
        'secretkey' => $processor_data['processor_params']['password']
    ], $_REQUEST);

    if ($response === true) {
        if ($mode == 'ok' && $_REQUEST['order_status'] == 'approved') {
            $pp_response = [
                'order_status' => ($processor_data['processor_params']['transaction_method'] == 'hold') ? $processor_data['processor_params']['status_hold'] : 'P',
                'payment_id' => $_REQUEST['payment_id'],
            ];
        } else {
            $pp_response = [
                'order_status' => 'F',
                'order_status_fondy' => $_REQUEST['order_status'],
            ];
        }

        fn_change_order_status($order_id, $pp_response['order_status'], '', false);
        fn_update_order_payment_info($order_id, ['fondy_order_id' => $_REQUEST['order_id'], 'fondy_payment_id' => $_REQUEST['payment_id']]);

        fn_finish_payment($order_id, $pp_response);
        fn_order_placement_routines('route', $order_id, false);

        exit();
    }
} else {

    $payment_data = array(
        'order_id' => $order_info['timestamp'] . '_' . $order_info['order_id'],
        'merchant_id' => $processor_data['processor_params']['merchant_id'],
        'order_desc' => '#' . $order_info['order_id'],
        'amount' => round($order_info['total'] * 100),
        'currency' => $processor_data['processor_params']['currency'],
        'response_url' => fn_url('index.php?dispatch=payment_notification.ok&payment=fondy&order_id=' . $order_info['order_id']),
        'server_callback_url' => fn_url('index.php?dispatch=payment_notification.ok&payment=fondy&order_id=' . $order_info['order_id']),
        'lang' => $processor_data['processor_params']['language'],
        'sender_email' => $order_info['email'],
    );

    if ($processor_data['processor_params']['transaction_method'] == 'hold') {
        $payment_data['preauth'] = 'Y';
    }

    $payment_data['signature'] = $fondy->getSignature($payment_data, $processor_data['processor_params']['password']);

    $response = $fondy->generateFondyUrl($payment_data);
    fn_update_order_payment_info($order_info['order_id'], ['order_id' => $payment_data['order_id'], 'payment_id' => $response['payment_id']]);

    if ($response['result'] == true) {
        fn_create_payment_form($response['url'], [], 'Fondy', true, 'GET');
    } else {
        fn_print_r($response);
    }
}
