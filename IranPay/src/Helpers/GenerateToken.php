<?php

namespace IranPay\Helpers;


use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * create custom token in case your Bank does not support Long length JWK Token
 * Class GenerateToken
 * @package IranPay\Helpers
 */
class GenerateToken
{

    /**
     * @var RouterInterface
     */
    private $router;


    /**
     * GenerateToken constructor.
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Get return back URL
     * @param AsyncPaymentTransactionStruct $transaction
     * @return string
     */
    public function getIranPayReturnUrl(AsyncPaymentTransactionStruct $transaction) : string
    {
        $t = time() + 1800;
        $hashVars = $transaction->getOrderTransaction()->getId() .'|'.$transaction->getOrderTransaction()->getPaymentMethodId() .'|'. $t;
        $token = $this->generateToken($hashVars);
        return $this->assembleReturnUrl((string)$token);

    }


    /**
     * @param string $token
     * @return string
     */
    private function assembleReturnUrl(string $token): string
    {
        $parameter = ['_s_p_t' => $token];

        return $this->router->generate('payment.finalize.iran-transaction', $parameter, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param string $plaintext
     * @return string
     */
    private function generateToken(string $plaintext)
    {
        $key = file_get_contents('file:///app/config/jwt/private.pem');
        $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        return base64_encode( $iv.$hmac.$ciphertext_raw );
    }
}
