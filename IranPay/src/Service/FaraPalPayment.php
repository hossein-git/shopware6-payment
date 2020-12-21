<?php

declare(strict_types=1);

namespace IranPay\Service;


use IranPay\DAL\InsertToDB;
use IranPay\Helpers\GenerateToken;
use IranPay\Models\FaraPal\FaraPal;
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
use Symfony\Component\Routing\RouterInterface;

/**
 * Class FaraPalPayment
 * @package IranPay\Service
 * @author Hossein Haghparast
 */
class FaraPalPayment implements AsynchronousPaymentHandlerInterface
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
     * @var RouterInterface
     */
    private $router;
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
        $this->token = $setting->getFaraPalToken();
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
//        dd($returnRoute);
        try {
            $customer = $salesChannelContext->getCustomer();
            $mobile = '';
            if ($cMobile = $customer->getDefaultBillingAddress()->getPhoneNumber()) {
                $mobile = preg_match('/^09[0-9]{9}/i', $cMobile) ? $cMobile : '';
            }
            $data = [
                'SandBox' => true,
                'MerchantCode' => $this->token,
                'PriceValue' => (integer)$transaction->getOrder()->getAmountTotal(),
                'ReturnUrl' => $this->tokenGenerate->getIranPayReturnUrl($transaction),
                'InvoiceNumber' => $transaction->getOrder()->getOrderNumber(),
                'CustomQuery' => [],
                'CustomPost' => [],
                'PaymenterName' => $customer->getFirstName() . ' ' . $customer->getLastName(),
                'PaymenterEmail' => $customer->getEmail(),
                'PaymenterMobile' => $mobile,
                'PaymentNote' => $transaction->getOrder()->getCustomerComment(),
//                'ExtraAccountNumbers' => $ExtraAccountNumbers,
//                'Bank'				  => $Bank,
            ];

            $this->insertToDB->storeIranPayTransactionData(
                $transaction,
                $salesChannelContext,
                $transaction->getOrder()->getOrderNumber()
            );
            $redirectUrl = (new FaraPal($this->token, $transaction))->pay($data);
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
        $refID = $request->get('Token');
        $status = $request->get('Status');
        // Cancelled payment?
        if (!$status || $request->get('Status') != 1) {
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
            $payping = new FaraPal($this->token, $transaction);
            $paymentState = $payping->verify($refID);
        } catch (\Throwable $exception) {
            $e = new AsyncPaymentFinalizeException(
                $transactionId,
                'an error occurred during verify the payment Token: ' . $refID . PHP_EOL . $exception->getMessage()
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
                $transaction,
                $salesChannelContext,
                $refID,
                null,
                'SUCCESS'
            );

            // Payment completed, set transaction status to "paid"
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
        } else {
            $this->insertToDB->storeIranPayTransactionData(
                $transaction,
                $salesChannelContext,
                $refID,
                null,
                'Error in verifying payment line 162'
            );
            throw new AsyncPaymentFinalizeException(
                $transactionId,
                'could not verify token: ' . $refID
            );

            // Payment not completed, set transaction status to "open"
//            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
        }
    }


}
