<?php

namespace PagoFacil\PagoFacilChile\Controller\Payment;

use PagoFacilCore\PagoFacilSdk;

require "app/code/PagoFacil/PagoFacilChile/vendor/kdu/pagofacilcore/src/PagoFacilSdk.php";

class PaymentMethod extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \PagoFacil\PagoFacilChile\Model\Factory\Connector
     */
    protected $_tpConnector;

    public function __construct(
        \PagoFacil\PagoFacilChile\Model\Factory\Connector $tpc,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Psr\Log\LoggerInterface $logger
    ) 
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_logger = $logger;
        $this->_tpConnector = $tpc;
    }



    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function execute()
    {
        $types = [];
        $error_message = '';
        try {
            $pagoFacil = PagoFacilSdk::create()
                ->setTokenService($this->_tpConnector->getTokenService())
                ->setEnvironment($this->_tpConnector->getEnviroment());
            $patmentMethods = $pagoFacil->getPaymentMethods();
            $types = $patmentMethods["types"];
        } catch(\Exception $exception) {
            $error_message = $exception->getMessage();
            $this->_logger->debug( "Error " .  print_r($exception->getMessage(), TRUE));
        }
        $result = $this->_resultJsonFactory->create();
        return $result->setData([
            'types' => $types,
            'error_message' => $error_message
        ]);
    }
}
