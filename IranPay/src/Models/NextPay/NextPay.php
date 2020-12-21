<?php

namespace IranPay\Models\NextPay;


use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;

/**
 * Class NextPay
 * @package IranPay\Models\NextPay
 * @author  Hossein Haghparst
 */
class NextPay
{
    const BASE_URL = 'https://api.nextpay.org/gateway/';

    /**
     * @var string
     */
    private $token = "";
    /**
     * @var string
     */
    private $restCall;
    /**
     * @var string
     */
    private $transaction;

    /**
     * Payment constructor.
     * @param $token
     * @param $transaction
     */
    public function __construct($token, $transaction)
    {
        $this->token = $token;
        $this->transaction = $transaction;
    }

    /**
     * Make payment
     * @param array $params
     * @return string
     */
    public function pay(array $params)
    {
        $response = $this->initCurl(
            'token.http',
            "api_key=" . $params['api_key'] . "&order_id=" . $params['order_id'] . "&amount=" . $params['amount'] . "&callback_uri=" . $params['callback_uri']
        );
        $code = '';
        if ($response != "" && $response != null && is_object($response)) {
            $code = intval($response->code);
            if (intval($response->code) == -1) {
                return self::BASE_URL . 'payment/' . $response->trans_id;
            }
        }
        throw new AsyncPaymentProcessException(
            $this->transaction->getOrderTransaction()->getId(),
            $response->message . '-CODE: ' . $code
        );
    }

    /**
     * verify a payment if there is no exception
     * @param $refId
     * @return bool
     * @throws \Exception
     */
    public function verify($refId)
    {
        return $this->initCurl(
            'verify.http',
            "api_key=" . $this->token . "&order_id=" . $this->transaction->getOrder()->getOrderNumber(
            ) . "&amount=" . (integer)$this->transaction->getOrder()->getAmountTotal() . "&trans_id=" . $refId
        );
    }

    /**
     * @param string $uri
     * @param        $params
     * @return mixed
     */
    private function initCurl(string $uri, $params)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::BASE_URL . $uri);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            $params
        );
//        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = json_decode(curl_exec($curl));
        curl_close($curl);
        return $result;
    }
}
