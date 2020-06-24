<?php
namespace PagoFacil\PagoFacilChile\Observer;

use Magento\Framework\Event\ObserverInterface;


class AfterPlaceOrder implements ObserverInterface
{
    protected $_quoteFactory;
    protected $_logger;


    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_quoteFactory = $quoteFactory;
        $this->_logger = $logger;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
    */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //$this->_logger->info("Ejecutando AfterPlaceOrder -- inicio");
        try {
            $order = $observer->getEvent()->getOrder();
            $quote =  $this->_quoteFactory->create()->load($order->getQuoteId());
            //vuelve a activar carro por si usuario vuleve desde la pagina de pago.
            $quote->setIsActive(true)->save();    
        } catch(\Exception $exception) {
            $this->_logger->info( "EXEPTION AfterPlaceOrder : " .  print_r($exception->getMessage(), TRUE));
        }
        //$this->_logger->info("Ejecutando AfterPlaceOrder -- fin");

        return $this;
    }
}