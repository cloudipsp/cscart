<?php
use Tygh\Storage;
class FondyCls
{
    const ORDER_APPROVED = 'approved';
    const ORDER_DECLINED = 'declined';
    const ORDER_SEPARATOR = '#';
    const SIGNATURE_SEPARATOR = '|';
    const URL = "https://api.fondy.eu/api/checkout/redirect/";

    public static function getSignature($data, $password, $encoded = true)
    {
        $data = array_filter($data, function($var) {
            return $var !== '' && $var !== null;
        });
        ksort($data);
        $str = $password;
        foreach ($data as $k => $v) {
            $str .= self::SIGNATURE_SEPARATOR . $v;
        }
        if ($encoded) {
            return sha1($str);
        } else {
            return $str;
        }
    }
    public static function isPaymentValid($fondySettings, $response)
    {
        if ($fondySettings['merchant_id'] != $response['merchant_id']) {
            return 'An error has occurred during payment. Merchant data is incorrect.';
        }
        if ($response['order_status'] == self::ORDER_DECLINED) {
            return 'An error has occurred during payment. Order is declined.';
        }
		$originalResponse = $response;
        $strs = explode(self::SIGNATURE_SEPARATOR, $originalResponse['response_signature_string']);
        $str = (str_replace($strs[0],$fondySettings['secret_key'],$originalResponse['response_signature_string']));
        if (sha1($str) != $originalResponse['signature']) {
            return 'An error has occurred during payment. Signature is not valid.';
        }

        return true;
    }
}

?>
