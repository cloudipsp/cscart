<?php

use Tygh\Payments\Processors\Fondy;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode == 'details') {

    $_REQUEST['order_id'] = empty($_REQUEST['order_id']) ? 0 : $_REQUEST['order_id'];

    $order_info = fn_get_order_info($_REQUEST['order_id'], false, true, true, false);
    $response = [];

    if (isset($_REQUEST['send']) && $_REQUEST['send'] == 1 && $order_info['status'] == 'O' && $order_info['payment_method']['processor'] == 'Fondy') {

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

        if (!isset($order_info['payment_info']['payment_link'])) {
            $payment_data = array(
                'order_id' => $order_info['timestamp'] . '_' . $order_info['order_id'],
                'merchant_id' => $processor_data['processor_params']['merchant_id'],
                'order_desc' => '#' . $order_info['order_id'],
                'amount' => round($amount * 100),
                'currency' => $currency_f,
                'response_url' => fn_url('index.php?dispatch=payment_notification.ok&payment=fondy&order_id=' . $order_info['order_id']),
                'server_callback_url' => fn_url('index.php?dispatch=payment_notification.ok&payment=fondy&order_id=' . $order_info['order_id']),
                'lang' => $processor_data['processor_params']['language'],
                'sender_email' => $order_info['email'],
            );

            if ($processor_data['processor_params']['transaction_method'] == 'hold') {
                $payment_data['preauth'] = 'Y';
            }

            $fondy = new Fondy();
            $payment_data['signature'] = $fondy->getSignature($payment_data, $processor_data['processor_params']['password']);
            $response = $fondy->generateFondyUrl($payment_data);

            if ($response['result'] == true) {
                fn_update_order_payment_info($order_info['order_id'], ['payment_link' => $response['url']]);
            } else {
                fn_update_order_payment_info($order_info['order_id'], ['error' => $response['message']]);
            }
        }
        if (isset($response['url'])) {
            $url = $response['url'];
        } elseif (isset($order_info['payment_info']['payment_link'])) {
            $url = $order_info['payment_info']['payment_link'];
        } else {
            $url = false;
        }
        $data = array(
            'payment_link' => $url,
            'email_subj' => "Invoice #" . $order_info['order_id']
        );
        if ($url) {
            /** @var Tygh\Mailer\Mailer $mailer */
            $mailer = Tygh::$app['mailer'];

            $data = $mailer->send(array(
                'to' => $order_info['email'],
                'from' => 'default_company_orders_department',
                'data' => $data,
                'tpl' => 'addons/fondy/send_payment_link.tpl',
                'is_html' => true
            ), 'A');
        }
        fn_redirect('/admin.php?dispatch=orders.details&order_id=' . $_REQUEST['order_id']);
        exit();
    }
    if (!isset($order_info['payment_info']['payment_id'])) {
        Tygh::$app['view']->assign('sendLink', '/admin.php?dispatch=orders.details&send=1&order_id=' . $_REQUEST['order_id']);
        Tygh::$app['view']->assign('error', $response);
    }
}