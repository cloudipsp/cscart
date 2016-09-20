<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }
$ExternalLibPath =realpath(dirname(__FILE__)).DS.'fondyLib.php';	require_once ($ExternalLibPath);
if (defined('PAYMENT_NOTIFICATION')) {
		
	$pp_response = array();
	$pp_response['order_status'] = 'F';
	$pp_response['reason_text'] = __('text_transaction_declined');
    $order_id = !empty($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
	
    if ($mode == 'response' && !empty($_REQUEST['order_id'])) {

		$order_info = fn_get_order_info($order_id);
		
		if (empty($processor_data)) {
			$processor_data = fn_get_processor_data($order_info['payment_id']);
		}
		$option  = array(   'merchant_id' => $processor_data['processor_params']['fondy_merchantid'],
            'secret_key' =>  $processor_data['processor_params']['fondy_merchnatSecretKey']);
		$response = FondyCls::isPaymentValid($option, $_POST);
		
        if ($response == true && $order_info['status'] == 'N') {
			if($_REQUEST['order_status'] == FondyCls::ORDER_APPROVED) {
				$pp_response['order_status'] = 'P';
				$pp_response['reason_text'] = __('transaction_approved');
				$pp_response['transaction_id'] = $_REQUEST['payment_id'];				
				if (fn_check_payment_script('fondy.php', $order_id)) {
					fn_finish_payment($order_id, $pp_response);
					fn_order_placement_routines('route', $order_id);
				}
				
				
			}
		}				
	}elseif ($mode == 'sucsses' && !empty($_REQUEST['order_id'])) {
		if ($response == true && $order_info['status'] == 'N') {
			if($_REQUEST['order_status'] == FondyCls::ORDER_APPROVED) {
				$pp_response['order_status'] = 'P';
				$pp_response['reason_text'] = __('transaction_approved');
				$pp_response['transaction_id'] = $_REQUEST['payment_id'];
				fn_finish_payment($order_id, $pp_response);
			}
		}		
	}
	exit;

	} else {
		
	$payment_url = FondyCls::URL;
	$amount = fn_format_price($order_info['total'], $processor_data['processor_params']['currency']);
	$confirm_url = fn_url("payment_notification.sucsses?payment=fondy&order_id=$order_id", AREA, 'current');
	$response_url = fn_url("payment_notification.response?payment=fondy&order_id=$order_id", AREA, 'current');

	$post_data = array(
		'merchant_id' => $processor_data['processor_params']['fondy_merchantid'],
		'lang' => $processor_data['processor_params']['fondy_lang'],
		'order_id' => time() . $order_id,
		'order_desc' => '#' . $order_id,
		'amount' => round($amount * 100),
		'currency' => $processor_data['processor_params']['currency'],
		'server_callback_url' => $confirm_url,
		'response_url' => $response_url
	);
  $post_data['signature'] = FondyCls::getSignature($post_data, $processor_data['processor_params']['fondy_merchnatSecretKey']);


	fn_create_payment_form($payment_url, $post_data, 'Fondy', false);
	exit;
}

