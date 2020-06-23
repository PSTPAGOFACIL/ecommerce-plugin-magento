<?php
namespace PagoFacil\PagoFacilChile\Observer;

use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;


class DataAssignObserver extends AbstractDataAssignObserver
{
    const PAYMENT_METHOD_SUBOPT = 'payment_method_subopt';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::PAYMENT_METHOD_SUBOPT,
    ];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \PagoFacil\PagoFacilChile\Logger\Logger
     */
    protected $_pstPagoFacilLogger;

    public function __construct(
        \PagoFacil\PagoFacilChile\Logger\Logger $pstPagoFacilLogger,
        \Psr\Log\LoggerInterface $logger
    ) 
    {
        $this->_logger = $logger;
        $this->_pstPagoFacilLogger = $pstPagoFacilLogger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try{

            $this->_logger->info("Ejecutando Data Assign Observer");
            $data = $this->readDataArgument($observer);
            $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
            if (!is_array($additionalData)) {
                return;
            }
            $paymentInfo = $this->readPaymentModelArgument($observer);
            foreach ($this->additionalInformationList as $additionalInformationKey) {
                if (isset($additionalData[$additionalInformationKey])) {
                    $paymentInfo->setAdditionalInformation(
                        $additionalInformationKey,
                        $additionalData[$additionalInformationKey]
                    );
                }
            }
        } catch (\Exception $exception){
            $this->_pstPagoFacilLogger->debug($exception->getMessage());
        }
    }
}