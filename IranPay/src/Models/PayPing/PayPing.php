<?php


namespace IranPay\Models\PayPing;

use GuzzleHttp\Client;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;


/**
 * Class PayPing
 * @package IranPay\Models\PayPing
 * @author Hossein Haghparst
 */
class PayPing
{

    const BASE_URL = 'https://api.payping.ir/v2/';

    /**
     * @var string
     */
    private $token = "";
    /**
     * @var Client
     */
    private $restCall;
    /**
     * @var
     */
    private $transaction;

    /**
     * Payment constructor.
     * @param $token
     * @param $transaction
     */
    public function __construct($token,$transaction)
    {
        $this->token = $token;
        $this->transaction = $transaction;

        $headers = [
            'Content-type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            "cache-control: no-cache",
            'Authorization' => 'Bearer ' . $this->token,
        ];

        $this->restCall = new Client(
            [
                'base_uri' => self::BASE_URL,
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
        try {
            $result = $this->restCall->post(self::BASE_URL . 'pay', ['body' => json_encode($params)]);
            $result = json_decode($result->getBody()->getContents(), false);
            if (!isset($result->code)) {
                throw new AsyncPaymentProcessException(
                    $this->transaction->getOrderTransaction()->getId(),
                    'An error occurred during the communication with external payment gateway'
                );
            }
            return self::BASE_URL . 'pay/gotoipg/' . $result->code;
        } catch (\Exception $exception) {
            throw new AsyncPaymentProcessException(
                $this->transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $exception->getMessage()
            );
        }
    }

    /**
     * verify a payment if there is no exception
     * @param $refId
     * @param $amount
     * @return bool
     * @throws \Exception
     */
    public function verify($refId, $amount)
    {
        $params = [
            'refId' => $refId,
            'amount' => $amount,
        ];
        try {
            $result = $this->restCall->post(self::BASE_URL . 'pay/verify', ['body' => json_encode($params)]);
            if ($result->getStatusCode() >= 200 && $result->getStatusCode() < 300) {
                return true;
            }
        } catch (\Exception $re) {
            throw new \Exception();
        }


    }

}
