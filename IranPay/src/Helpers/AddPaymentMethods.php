<?php

namespace IranPay\Helpers;


use IranPay\IranPay;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper Class to install/uninstall paymanet methods
 * Class AddPaymentMethods
 * @package IranPay\Helpers
 */
class AddPaymentMethods
{

    /** @var Context */
    private $context;

    /** @var ContainerInterface */
    private $container;

    /** @var EntityRepositoryInterface $paymentRepository */
    private $paymentRepository;

    /**
     * here new method and payment service class should add
     * name should be same as class name without Payment at the End
     * @var array
     */
    private $methods = [
        'PayPing' => 'payping.ir',
        'PayIr' => 'pay.ir',
        'FaraPal' => 'farapal.ir',
        'NextPay' => 'nextpay.com',
    ];

    /**
     * AddPaymentMethods constructor.
     * @param                    $context
     * @param ContainerInterface $container
     */
    public function __construct($context, ContainerInterface $container)
    {
        $this->context = $context;
        $this->container = $container;
        $this->paymentRepository = $container->get('payment_method.repository');
    }

    /**
     * this runs on install
     * if method does not exist then will create it
     */
    public function addAllMethods()
    {
        $PaymentData = [];
        foreach ($this->methods as $name => $desc) {
            $paymentMethodExists = $this->getPaymentMethodId($name);
            //if method not exist then create it
            if (!$paymentMethodExists) {
                $PaymentData[] = $this->setPaymentsData($name, $desc);
            }
        }
        if (count($PaymentData)) {
            $this->paymentRepository->create($PaymentData, $this->context);
        }
    }

    /**
     * return paymentId if method exist
     * @param string $className
     * @return array|array[]|string[]
     */
    private function getPaymentMethodId(string $className)
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->container->get('payment_method.repository');

        // Fetch ID for update
        $paymentCriteria = (new Criteria())->addFilter(
            new EqualsFilter('handlerIdentifier', "IranPay\Service\\$className" . 'Payment')
        );
        $paymentIds = $paymentRepository->searchIds($paymentCriteria, Context::createDefaultContext());

        if ($paymentIds->getTotal() === 0) {
            return [];
        }

        return $paymentIds->getIds()[0];
    }

    /**
     * set varibles for creating new method
     * @param string $name
     * @param string $description
     * @return array
     */
    private function setPaymentsData(string $name, string $description)
    {
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        $pluginId = $pluginIdProvider->getPluginIdByBaseClass(IranPay::class, $this->context);
        $class = "IranPay\Service\\$name" . 'Payment';
        return [
            'handlerIdentifier' => $class,
            'name' => $name,
            'description' => $description,
            'pluginId' => $pluginId,
        ];
    }

    /**
     * this runs on active/uninstall
     * if method exist then change (de-)activate it
     * @param bool $active
     */
    public function changeMethodStatus(bool $active)
    {
        $paymentMethods = [];
        foreach ($this->methods as $name => $desc) {
            $paymentMethodId = $this->getPaymentMethodId($name);
            // Payment exist, so (de-)activate here
            if ($paymentMethodId) {
                $paymentMethods[] = [
                    'id' => $paymentMethodId,
                    'active' => $active,
                ];
            }
        }

        if (count($paymentMethods)) {
            $this->paymentRepository->update($paymentMethods, $this->context);
        }
    }

}
