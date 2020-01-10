<?php

namespace Tygh\Payments\Processors;

class Fondy
{
    function getSignature($data, $password, $encoded = true)
    {
        $data = array_filter($data, function ($var) {
            return $var !== '' && $var !== null;
        });
        ksort($data);

        $str = $password;
        foreach ($data as $k => $v) {
            $str .= '|' . $v;
        }

        if ($encoded) {
            return sha1($str);
        } else {
            return $str;
        }
    }

    function generateFondyUrl($payment_data, $capture = false)
    {
        $url = ($capture) ? 'https://api.fondy.eu/api/capture/order_id' : 'https://api.fondy.eu/api/checkout/url/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('request' => $payment_data)));
        $result = json_decode(curl_exec($ch));
        if ($result->response->response_status == 'failure') {
            $response = array('result' => false,
                'message' => $result->response->error_message,
                'response_status' => $result->response->response_status,
                'request_id' => $result->response->request_id);
        } else {
            $response = array('result' => true,
                'url' => (isset($result->response->checkout_url)) ? $result->response->checkout_url : '',
                'response_status' => $result->response->response_status);
        }
        return $response;
    }

    function isPaymentValid($settings, $response)
    {

        if ($settings['merchant'] != $response['merchant_id']) {
            return 'Fondy_error_merchant';
        }

        $responseSignature = $response['signature'];
        if (isset($response['response_signature_string'])) {
            unset($response['response_signature_string']);
        }
        if (isset($response['signature'])) {
            unset($response['signature']);
        }
        $signature = $this->getSignature($response, $settings['secretkey']);

        if ($signature != $responseSignature) {
            return 'Fondy_error_signature';
        }
        return true;
    }
}