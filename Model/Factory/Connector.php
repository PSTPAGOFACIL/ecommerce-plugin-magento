<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/12/18
 * Time: 08:03 PM
 */

namespace PagoFacil\PagoFacilChile\Model\Factory;

use PagoFacilCore\EnvironmentEnum;

require "app/code/PagoFacil/PagoFacilChile/vendor/kdu/pagofacilcore/src/EnvironmentEnum.php";

class Connector
{
    protected $_scopeConfig;

    protected $_enviroment;

    protected $_token_secret;

    protected $_token_service;

    /**
     * Connector constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_enviroment = $this->_scopeConfig->getValue('payment/pagofacilchile/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $this->_logger->info("env: " . print_r($this->_enviroment, TRUE) );
        if ($this->_enviroment == EnvironmentEnum::DEVELOPMENT){
            $this->_token_secret = $this->_scopeConfig->getValue('payment/pagofacilchile/enviroment_g/development/token_secret',   \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $this->_token_service = $this->_scopeConfig->getValue('payment/pagofacilchile/enviroment_g/development/token_service',   \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }else{
            $this->_token_secret = $this->_scopeConfig->getValue('payment/pagofacilchile/enviroment_g/production/token_secret',   \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $this->_token_service = $this->_scopeConfig->getValue('payment/pagofacilchile/enviroment_g/production/token_service',   \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
    }

    /**
     * @return mixed
     */
    public function getEnviroment()
    {
        return $this->_enviroment;
    }

    /**
     * @return mixed
     */
    public function getTokenSecret()
    {
        return $this->_token_secret;
    }

    /**
     * @return mixed
     */
    public function getTokenService()
    {
        return $this->_token_service;
    }
}