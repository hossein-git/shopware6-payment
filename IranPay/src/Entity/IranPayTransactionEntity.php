<?php declare(strict_types=1);

namespace IranPay\Entity;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

/**
 * Class IranPayTransactionEntity
 * @package IranPay\Entity
 */
class IranPayTransactionEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     */
    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @var string
     */
    protected $technicalName;

    /**
     * @var string
     */
    protected $iranpayTransactionId;

    /**
     * @var int
     */
    protected $paymentId;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $exception;

    /**
     * @var string
     */
    protected $orderTransactionId;

    /**
     * @var string
     */
    protected $orderId;

    /**
     * @var string
     */
    protected $latestActionName;

    /**
     * @var string
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $orderStateId;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var string
     */
    protected $dispatch;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var OrderEntity
     */
    protected $order;

    /**
     * @var CustomerEntity
     */
    protected $customer;

    /**
     * @var StateMachineStateEntity
     */
    protected $stateMachineState;

    /**
     * @var OrderTransactionEntity
     */
    protected $orderTransaction;

    /**
     * @return string|null
     */
    public function getTechnicalName(): ?string
    {
        return $this->technicalName;
    }

    /**
     * @param string $technicalName
     */
    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    /**
     * @return string
     */
    public function getIranPayTransactionId(): string
    {
        return $this->iranpayTransactionId;
    }

    /**
     * @param string $iranpayTransactionId
     */
    public function setIranPayTransactionId(string $iranpayTransactionId): void
    {
        $this->iranpayTransactionId = $iranpayTransactionId;
    }

    /**
     * @return string
     */
    public function getOrderTransactionId(): string
    {
        return $this->orderTransactionId;
    }

    /**
     * @param string $orderTransactionId
     */
    public function setOrderTransactionId(string $orderTransactionId): void
    {
        $this->orderTransactionId = $orderTransactionId;
    }

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return int|null
     */
    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    /**
     * @param int $paymentId
     */
    public function setPaymentId(int $paymentId): void
    {
        $this->paymentId = $paymentId;
    }

    /**
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string|null
     */
    public function getException(): ?string
    {
        return $this->exception;
    }

    /**
     * @param string $exception
     */
    public function setException(string $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * @return string|null
     */
    public function getLatestActionName(): ?string
    {
        return $this->latestActionName;
    }

    /**
     * @param string $latestActionName
     */
    public function setLatestActionName(string $latestActionName): void
    {
        $this->latestActionName = $latestActionName;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return [
            'technicalName' => $this->getTechnicalName(),
            'iranpayTransactionId' => $this->getIranPayTransactionId(),
            'orderTransactionId' => $this->getOrderTransactionId(),
            'orderId' => $this->getOrderId(),
            'paymentId' => $this->getPaymentId(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'exception' => $this->getException(),
            'latestActionName' => $this->getLatestActionName()
        ];
    }

    /**
     * @return string
     */
    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    /**
     * @param string $customerId
     */
    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    /**
     * @return string
     */
    public function getOrderStateId(): string
    {
        return $this->orderStateId;
    }

    /**
     * @param string $orderStateId
     */
    public function setOrderStateId(string $orderStateId): void
    {
        $this->orderStateId = $orderStateId;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getDispatch(): string
    {
        return $this->dispatch;
    }

    /**
     * @param string $dispatch
     */
    public function setDispatch(string $dispatch): void
    {
        $this->dispatch = $dispatch;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $stateId): void
    {
        $this->stateId = $stateId;
    }

    /**
     * @return OrderEntity
     */
    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    /**
     * @param OrderEntity $order
     */
    public function setOrder(OrderEntity $order): void
    {
        $this->order = $order;
    }

    /**
     * @return CustomerEntity
     */
    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    /**
     * @param CustomerEntity $customer
     */
    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return OrderTransactionEntity
     */
    public function getOrderTransaction(): OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    /**
     * @param OrderTransactionEntity $orderTransaction
     */
    public function setOrderTransaction(OrderTransactionEntity $orderTransaction): void
    {
        $this->orderTransaction = $orderTransaction;
    }

    /**
     * @return StateMachineStateEntity
     */
    public function getStateMachineState(): StateMachineStateEntity
    {
        return $this->stateMachineState;
    }

    /**
     * @param StateMachineStateEntity $stateMachineState
     */
    public function setStateMachineState(StateMachineStateEntity $stateMachineState): void
    {
        $this->stateMachineState = $stateMachineState;
    }
}
