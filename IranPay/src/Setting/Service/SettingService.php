<?php declare(strict_types=1);

namespace IranPay\Setting\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * get values from config
 * Class SettingService
 * @package IranPay\Setting\Service
 * @author Hossein Haghparast
 */
class SettingService
{
    public const SYSTEM_CONFIG_DOMAIN = 'IranPay.config.';

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @return string
     */
    public function getPayIrToken() :string
    {
        return $this->systemConfigService->get('IranPay')['config']['payir'] ?? '';
    }

    /**
     * @return string
     */
    public function getPayPingToken() :string
    {
        return $this->systemConfigService->get('IranPay')['config']['payping'] ?? '';
    }

    /**
     * @return string
     */
    public function getFaraPalToken() :string
    {
        return $this->systemConfigService->get('IranPay')['config']['farapal'] ?? '';
    }

    /**
     * @return string
     */
    public function getNextPayToken() :string
    {
        return $this->systemConfigService->get('IranPay')['config']['nextpay'] ?? '';
    }
}
