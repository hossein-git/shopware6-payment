<?php

namespace IranPay\Core\Payment;


use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionChainProcessor;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenFactoryInterfaceV2;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTokenException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Payment\Exception\PaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\TokenExpiredException;
use Shopware\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * this class handle custom payment token
 * Class PaymentService
 * @package IranPay\Core\Payment
 */
class PaymentService
{

    /**
     * @var PaymentTransactionChainProcessor
     */
    private $paymentProcessor;

    /**
     * @var TokenFactoryInterfaceV2
     */
    private $tokenFactory;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    /**
     * @var PaymentHandlerRegistry
     */
    private $paymentHandlerRegistry;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepository;

    /**
     * @var OrderTransactionStateHandler
     */
    private $transactionStateHandler;
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * PaymentService constructor.
     * @param PaymentTransactionChainProcessor $paymentProcessor
     * @param TokenFactoryInterfaceV2          $tokenFactory
     * @param EntityRepositoryInterface        $paymentMethodRepository
     * @param PaymentHandlerRegistry           $paymentHandlerRegistry
     * @param EntityRepositoryInterface        $orderTransactionRepository
     * @param OrderTransactionStateHandler     $transactionStateHandler
     * @param RouterInterface                  $router
     */
    public function __construct(
        PaymentTransactionChainProcessor $paymentProcessor,
        TokenFactoryInterfaceV2 $tokenFactory,
        EntityRepositoryInterface $paymentMethodRepository,
        PaymentHandlerRegistry $paymentHandlerRegistry,
        EntityRepositoryInterface $orderTransactionRepository,
        OrderTransactionStateHandler $transactionStateHandler,
        RouterInterface $router
    ) {
        $this->paymentProcessor = $paymentProcessor;
        $this->tokenFactory = $tokenFactory;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentHandlerRegistry = $paymentHandlerRegistry;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->router = $router;
    }

    /**
     * @throws AsyncPaymentFinalizeException
     * @throws CustomerCanceledAsyncPaymentException
     * @throws InvalidTransactionException
     * @throws TokenExpiredException
     * @throws UnknownPaymentMethodException
     */
    public function finalizeTransaction(
        string $paymentToken,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): TokenStruct {
        $paymentTokenStruct = $this->parseToken($paymentToken);
        $transactionId = $paymentTokenStruct->getTransactionId();
        $context = $salesChannelContext->getContext();
        $paymentTransactionStruct = $this->getPaymentTransactionStruct($transactionId, $context);
        $orderId = $paymentTransactionStruct->getOrder()->getId();
        $paymentHandler = $this->getPaymentHandlerById($paymentTokenStruct->getPaymentMethodId(), $context);

        if (!$paymentHandler) {
            throw new UnknownPaymentMethodException($paymentTokenStruct->getPaymentMethodId());
        }

        $paymentTokenStruct = $this->setStructRoutes($orderId,$paymentTokenStruct);
        try {
            $paymentHandler->finalize($paymentTransactionStruct, $request, $salesChannelContext);
        } catch (PaymentProcessException $e) {
            $this->transactionStateHandler->fail($e->getOrderTransactionId(), $context);
            $paymentTokenStruct->setException($e);

            return $paymentTokenStruct;
        }

        return $paymentTokenStruct;
    }


    /**
     * @param string $token
     * @return string
     */
    public function parseToken(string $token)
    {
        $key = file_get_contents('file:///app/config/jwt/private.pem');
        $c = base64_decode($token);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        if (!hash_equals($hmac, $calcmac)) {
            throw new InvalidTokenException($token);
        }
        $results = explode('|', $original_plaintext);
        $transactionId = $results[0];
        $paymentId = $results[1];
        $expiredTime = $results[2];

        if ($expiredTime <= time()) {
            throw new TokenExpiredException($token);
        }

        if (!Uuid::isValid($transactionId)) {
            throw new InvalidOrderException($transactionId);
        }
//        $this->tokenFactory->invalidateToken($tokenStruct->getToken());
        return new TokenStruct(
            null,
            $expiredTime,
            $paymentId,
            $transactionId,
            null,
            null,
            null
        );
    }

    /**
     * @throws InvalidTransactionException
     */
    private function getPaymentTransactionStruct(
        string $orderTransactionId,
        Context $context
    ): AsyncPaymentTransactionStruct {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order');
        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if ($orderTransaction === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        return new AsyncPaymentTransactionStruct($orderTransaction, $orderTransaction->getOrder(), '');
    }

    /**
     * @throws UnknownPaymentMethodException
     */
    private function getPaymentHandlerById(
        string $paymentMethodId,
        Context $context
    ): ?AsynchronousPaymentHandlerInterface {
        $paymentMethods = $this->paymentMethodRepository->search(new Criteria([$paymentMethodId]), $context);

        /** @var PaymentMethodEntity|null $paymentMethod */
        $paymentMethod = $paymentMethods->get($paymentMethodId);
        if (!$paymentMethod) {
            throw new UnknownPaymentMethodException($paymentMethodId);
        }

        return $this->paymentHandlerRegistry->getAsyncHandler($paymentMethod->getHandlerIdentifier());
    }

    /**
     * @param string      $orderId
     * @param TokenStruct $paymentTokenStruct
     * @return TokenStruct
     */
    private function setStructRoutes(string $orderId, TokenStruct $paymentTokenStruct)
    {
        $finishUrl = $this->router->generate(
            'frontend.checkout.finish.page',
            ['orderId' => $orderId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $errorUrl = $this->router->generate(
            'frontend.checkout.finish.page',
            [
                'orderId' => $orderId,
                'changedPayment' => false,
                'paymentFailed' => true,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new TokenStruct(
            null,
            $paymentTokenStruct->getToken(),
            $paymentTokenStruct->getPaymentMethodId(),
            $paymentTokenStruct->getTransactionId(),
            $finishUrl,
            $paymentTokenStruct->getExpires(),
            $errorUrl
        );
    }

}
