<?php

declare(strict_types=1);

namespace IranPay\Service;


use IranPay\DAL\InsertToDB;
use IranPay\Models\NextPay\NextPay;
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
 * Class NextPayPayment
 * @package IranPay\Service
 * @author Hossein Haghparast
 */
class NextPayPayment implements AsynchronousPaymentHandlerInterface
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

    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        InsertToDB $insertToDB,
        SettingService $setting
    ) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->insertToDB = $insertToDB;
        $this->token = $setting->getNextPayToken();
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
            $data = [
                "api_key" => "$this->token",
                "order_id" => $transaction->getOrder()->getOrderNumber(),
                "amount" => (integer)$transaction->getOrder()->getAmountTotal(),
                "callback_uri" => $transaction->getReturnUrl(),
            ];
            //send request to get redirect link to gateway
            $redirectUrl = (new NextPay($this->token, $transaction))->pay($data);
            //take trans_id as ref id from the end of redirect URL
            $array = explode("/", $redirectUrl);
            $refId = end($array);
            //save it into DB
            $this->insertToDB->storeIranPayTransactionData(
                $transaction,
                $salesChannelContext,
                $refId,
                null,
                'getting redirect URL'
            );
        } catch (\Throwable $e) {
            $this->insertToDB->storeIranPayTransactionData(
                $transaction,
                $salesChannelContext,
                $transaction->getOrderTransaction()->getId(),
                $e,
                $e->getMessage()
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
        $refID = $request->get('trans_id');
        //check if the transaction made by us
        $paymentRow = $this->insertToDB->getData($salesChannelContext, $refID);
        if (!$paymentRow->getTotal()) {
            $e = new AsyncPaymentFinalizeException(
                $transactionId,
                'This transaction has never made by us refID: ' . $refID
            );
            $this->insertToDB->updateData(
                $salesChannelContext,
                $transactionId,
                $e,
                'An authorized transaction CODE: ' . $refID . $e->getMessage()
            );
            throw $e;
        }
        //check transition from nextPay servers
        try {
            $paymentState = (new NextPay($this->token, $transaction))->verify($refID);
        } catch (\Throwable $exception) {
            $e = new AsyncPaymentFinalizeException(
                $transactionId,
                'an error occurred during verify the payment refID: ' . $refID . PHP_EOL . $exception->getMessage()
            );
            $this->insertToDB->updateData(
                $salesChannelContext,
                $transactionId,
                $e,
                'Error on sending verify request CODE: ' . $refID
            );
            throw $e;
        }
        // Cancelled payment?
        if ($paymentState->code == -4) {
            $exception = new CustomerCanceledAsyncPaymentException(
                $transactionId,
                "CODE: $paymentState->code Customer canceled the payment on the Gateway page"
            );
            $this->insertToDB->updateData(
                $salesChannelContext,
                $transactionId,
                $exception,
                $paymentState->message
            );
            throw $exception;
        }

        if ($paymentState->code == 0) {
            $this->insertToDB->updateData(
                $salesChannelContext,
                $transactionId,
                null,
                $paymentState->message . 'CODE:' . $paymentState->code
            );

            // Payment completed, set transaction status to "paid"
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
        } else {
            $this->insertToDB->updateData(
                $salesChannelContext,
                $transactionId,
                null,
                $paymentState->message . 'CODE:' . $paymentState->code
            );
            // Payment not completed, set transaction status to "open"
            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
        }
    }


}
