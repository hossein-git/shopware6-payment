<?php


namespace IranPay\Models\FaraPal;

use Exception;
use GuzzleHttp\Client;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;


/**
 * Class FaraPal
 * @package IranPay\Models\FaraPal
 * @author Hossein Haghparst
 */
class FaraPal
{

    const BASE_URL = 'https://farapal.com/';

    /**
     * @var string
     */
    private $token = "";
    /**
     * @var Client
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

        $headers = [
//            'Content-type' => 'text/html; charset=utf-8',
            'Content-type' => 'application/json; charset=utf-8',
//            'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
//            'Pragma: no-cache',
        ];

        $this->restCall = new Client(
            [
//                'base_uri' => self::BASE_URL,
                'headers' => $headers,
            ]
        );
    }

    /**
     * Make payment
     * @param array $params
     * @return string
     */
    public function pay(array $params)
    {
        $result = $this->restCall->post(
            self::BASE_URL . 'services/paymentRequest.json',
            [
                'body' => json_encode($params),
            ]
        );
        $result = json_decode($result->getBody()->getContents(), false);


        if (!isset($result->Status) || $result->Status !== 1) {
            throw new AsyncPaymentProcessException(
                $this->transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway'
            );
        }
        //payment_test is for testing
//        return self::BASE_URL . 'services/payment_test/' . $result->Token;
        return self::BASE_URL . 'services/payment/' . $result->token;
    }

    /**
     * verify a payment if there is no exception
     * @param $refId
     * @return bool
     * @throws Exception
     */
    public function verify($refId)
    {
        // SandBox is for testing
        $result = $this->restCall->post(
            self::BASE_URL . 'services/paymentVerify.json',
            [
//                'SandBox' => true,
                'MerchantCode' => $this->token,
                'Token' => $refId,
            ]
        );
        $result = json_decode($result->getBody()->getContents(), false);
        if (isset($result->Status) and $result->Status == 1) {
            return true;
        }
        return false;


    }
}
