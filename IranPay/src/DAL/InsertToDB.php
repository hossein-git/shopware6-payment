<?php

namespace IranPay\DAL;


use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

/**
 * Handle insert information into DB
 * Class InsertToDB
 * @package IranPay\DAL
 * @author Hossein Haghparst
 * @date Dec 16 2020
 */
class InsertToDB
{

    /**
     * @var EntityRepositoryInterface
     */
    private $iranTransactionRepository;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(EntityRepositoryInterface $iranTransactionRepository, ContainerInterface $container)
    {
        $this->iranTransactionRepository = $iranTransactionRepository;
        $this->container = $container;
    }

    /**
     * create a row for current payment
     * @param AsyncPaymentTransactionStruct $transaction
     * @param SalesChannelContext           $salesChannelContext
     * @param string                        $iranpayTransactionId
     * @param Throwable|null                $exception
     * @param string                        $status
     */
    public function storeIranPayTransactionData(
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext,
        string $iranpayTransactionId,
        ?Throwable $exception = null,
        string $status = ''
    ): void {
        $shopwarePaymentMethodName = $salesChannelContext->getPaymentMethod()->getTranslated()['name'];

        /** @var CustomerEntity $customer */
        $customer = $salesChannelContext->getCustomer();
        $transactionId = $transaction->getOrderTransaction()->getId();
        if (!$customer) {
            throw new AsyncPaymentFinalizeException(
                $transactionId,
                'an error occurred during getting customer ID/ refID: ' . $iranpayTransactionId
            );
        }
        $transactionData = [
            'iranpayTransactionId' => $iranpayTransactionId,
            'paymentMethod' => $shopwarePaymentMethodName,
            'customerId' => $customer->getId(),
            'orderId' => $transaction->getOrder()->getId(),
            'orderTransactionId' => $transactionId,
//            'paymentId' => 1372,
            'amount' => $transaction->getOrder()->getAmountTotal(),
            'status' => $status,
//            'latestActionName' => StateMachineTransitionActions::ACTION_REOPEN,
            'currency' => $salesChannelContext->getCurrency()->getIsoCode(),
            'orderStateId' => $transaction->getOrder()->getStateId(),
            'dispatch' => $salesChannelContext->getShippingMethod()->getId(),
            'exception' => substr((string)$exception, 0, 450),
        ];
        $this->iranTransactionRepository->create([$transactionData], $salesChannelContext->getContext());
    }

    /**
     * change payment status and add exception
     * @param SalesChannelContext $salesChannelContext
     * @param                     $transactionId
     * @param Throwable|null      $exception
     * @param string              $status
     */
    public function updateData(
        SalesChannelContext $salesChannelContext,
        $transactionId,
        ?Throwable $exception = null,
        string $status = ''
    ) : void {
        $data = $this->getData($salesChannelContext, $transactionId, 'orderTransactionId');
        $this->iranTransactionRepository->update(
            [
                [
                    'id' => (string)$data->first()->getId(),
                    'status' => $status,
                    'exception' => substr((string)$exception, 0, 450),
                ],
            ]
            ,
            $salesChannelContext->getContext()
        );
    }

    /**
     * get payment row by refId
     * @param SalesChannelContext $salesChannelContext
     * @param                     $refId
     * @param string              $filed
     * @return EntitySearchResult
     */
    public function getData(SalesChannelContext $salesChannelContext, $refId, string $filed = 'iranpayTransactionId'): EntitySearchResult
    {
        return $this->iranTransactionRepository->search(
            (new Criteria())->addFilter(
                new EqualsAnyFilter(
                    "$filed",
                    [
                        $refId,
                    ]
                )
            ),
            $salesChannelContext->getContext()
        );
    }


}
