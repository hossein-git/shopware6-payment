<?php


namespace IranPay\Models\PayIr;

use GuzzleHttp\Client;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;


/**
 * Class PayIr
 * @package IranPay\Models\PayIr
 * @author Hossein Haghparst
 */
class PayIr
{


    const BASE_URL = 'https://pay.ir/pg/';

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
            'Content-type' => 'application/json; charset=utf-8',
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
        $result = $this->restCall->post(self::BASE_URL . 'send', ['body' => json_encode($params)]);
        $result = json_decode($result->getBody()->getContents(), false);

        if ($result->status !== 1) {
            throw new AsyncPaymentProcessException(
                $this->transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway'
            );
        }
        return self::BASE_URL . $result->token;
    }

    /**
     * verify a payment if there is no exception
     * @param $refId
     * @return bool
     * @throws \Exception
     */
    public function verify($refId)
    {
        $result = $this->restCall->post(
            self::BASE_URL . 'verify',
            [
                'api' => $this->token,
                'token' => $refId,
            ]
        );
        $result = json_decode($result->getBody()->getContents(), false);
        if (isset($result->status) and $result->status == 1) {
            return true;
        }
        return false;
//            dd($result);


    }
}
