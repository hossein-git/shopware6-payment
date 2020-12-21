<?php

declare(strict_types=1);

namespace IranPay\Service;


use IranPay\DAL\InsertToDB;
use IranPay\Helpers\GenerateToken;
use IranPay\Models\PayIr\PayIr;
use IranPay\Setting\Service\SettingService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PayIrPayment
 * @package IranPay\Service
 * @author Hossein Haghparast
 */
class PayIrPayment implements AsynchronousPaymentHandlerInterface
{
    /**
     * @var OrderTransactionStateHandler
     */
    private $transactionStateHandler;
    /**
     * @var string
     */
    private $token;
    /**
     * @var InsertToDB
     */
    private $insertToDB;
    /**
     * @var GenerateToken
     */
    private $tokenGenerate;

    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        InsertToDB $insertToDB,
        SettingService $setting,
        GenerateToken $tokenGenerate
    ) {

        $this->transactionStateHandler = $transactionStateHandler;
        $this->insertToDB = $insertToDB;
        $this->tokenGenerate = $tokenGenerate;
        $this->token = $setting->getPayIrToken();
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param RequestDataBag                $dataBag
     * @param SalesChannelContext           $salesChannelContext
     * @return RedirectResponse
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        try {
            $customer = $salesChannelContext->getCustomer();
            $data = [
                'api' => $this->token,
                'amount' => (integer)$transaction->getOrder()->getAmountTotal(),
                'redirect' => $this->tokenGenerate->getIranPayReturnUrl($transaction),
                'mobile' => $customer->getDefaultBillingAddress()->getPhoneNumber() ?? $customer->getEmail(),
                'factorNumber' => $transaction->getOrder()->getOrderNumber(),
                'description' => $transaction->getOrder()->getDocuments(),
            ];
            $this->insertToDB->storeIranPayTransactionData(
                $transaction,
                $salesChannelContext,
                $transaction->getOrder()->getOrderNumber()
            );
            $redirectUrl = (new PayIr($this->token, $transaction))->pay($data);
        } catch (\Throwable $e) {
            $this->insertToDB->storeIranPayTransactionData(
                $transaction,
                $salesChannelContext,
                $transaction->getOrder()->getOrderNumber(),
                $e,
                'ERROR ON SEND DATA'
            );
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
        return new RedirectResponse($redirectUrl);
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param Request                       $request
     * @param SalesChannelContext           $salesChannelContext
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $context = $salesChannelContext->getContext();
        $refID = $request->get('token');
        dd($request);
        // Cancelled payment?
        if (isset($refID) and $refID != 1) {
            $exception = new CustomerCanceledAsyncPaymentException(
                $transactionId,
                'Customer canceled the payment on the Getway page'
            );
//            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
            $this->insertToDB->storeIranPayTransactionData(
                $transaction,
                $salesChannelContext,
                $transaction->getOrder()->getOrderNumber(),
                $exception,
                'CANCELED BY CUSTOMER'
            );
            throw $exception;
        }

        try {
            $payping = new PayIr($this->token, $transaction);
            $paymentState = $payping->verify($refID);
        } catch (\Throwable $exception) {
            $e = new AsyncPaymentFinalizeException(
                $transactionId,
                'an error occurred during verify the payment refID: ' . $refID . PHP_EOL . $exception->getMessage()
            );
            $this->insertToDB->storeIranPayTransactionData(
                $refID,
                $salesChannelContext,
                $transaction->getOrder()->getOrderNumber(),
                $e,
                'Error on sending verify request'
            );
            throw $e;
        }

        if ($paymentState) {
            $this->insertToDB->storeIranPayTransactionData(
                $refID,
                $salesChannelContext,
                $transaction->getOrder()->getOrderNumber(),
                null,
                'SUCCESS'
            );

            // Payment completed, set transaction status to "paid"
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
        } else {
            $this->insertToDB->storeIranPayTransactionData(
                $refID,
                $salesChannelContext,
                $transaction->getOrder()->getOrderNumber(),
                null,
                'Error in verifying payment line 146'
            );

            // Payment not completed, set transaction status to "open"
            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
        }
    }


}
