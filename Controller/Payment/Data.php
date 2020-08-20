<?php

/**
 * Created by PhpStorm.
 * User: smp
 * Date: 26/12/18
 * Time: 02:17 AM
 */

namespace PagoFacil\PagoFacilChile\Controller\Payment;


use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Payment\Transaction;
use PagoFacilCore\PagoFacilSdk;
use PagoFacilCore\Transaction as PfcTransaction;

require_once("app/code/PagoFacil/PagoFacilChile/vendor/kdu/pagofacilcore/src/PagoFacilSdk.php");
require_once("app/code/PagoFacil/PagoFacilChile/vendor/kdu/pagofacilcore/src/Transaction.php");


use PagoFacil\PagoFacilChile\Observer\DataAssignObserver;

class Data extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $_transactionRepository;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \PagoFacil\PagoFacilChile\Logger\Logger
     */
    protected $_pstPagoFacilLogger;

    /**
     * @var \PagoFacil\PagoFacilChile\Model\Factory\Connector
     */
    protected $_tpConnector;

    public function __construct(
        \PagoFacil\PagoFacilChile\Logger\Logger $pstPagoFacilLogger,
        \PagoFacil\PagoFacilChile\Model\Factory\Connector $tpc,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Psr\Log\LoggerInterface $logger,
        PaymentHelper $paymentHelper,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->_url = $context->getUrl();
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_sessionManager = $sessionManager;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->_transactionRepository = $transactionRepository;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_pstPagoFacilLogger = $pstPagoFacilLogger;
        $this->_tpConnector = $tpc;
    }

    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function execute()
    {
        $url = '';
        $error_message = '';
        try{
            $order = $this->_getCheckoutSession()->getLastRealOrder();
            $payment = $order->getPayment();
            $subopt = $payment->getAdditionalInformation(DataAssignObserver::PAYMENT_METHOD_SUBOPT);
            $method = $payment->getMethod();
            $methodInstance = $this->_paymentHelper->getMethodInstance($method);
            $total = $methodInstance->getAmount($order);
            $json = $this->generateTransaction($order, $subopt, $total);
            $this->_logger->debug( "json result : " .  print_r( $json, TRUE));
            if ($json){
                $url = $json->urlTrx;

                $payment = $order->getPayment();
                $payment->setTransactionId($json->trxId)
                    ->setIsTransactionClosed(0);

                $payment->setParentTransactionId($order->getId());
                $payment->setIsTransactionPending(true);
                $transaction = $this->_transactionBuilder->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($payment->getTransactionId())
                    ->build(Transaction::TYPE_ORDER);

                $payment->addTransactionCommentsToOrder($transaction, __('pending'));


                $statuses = $methodInstance->getOrderStates();
                $status = $statuses["pending"];
                $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
                $order->setState($state)->setStatus($status);
                $payment->setSkipOrderProcessing(true);
                $order->save();
            }

        }catch (\Exception $exception){
            $this->_logger->debug( "EXEPTION execute method : " .  print_r($exception->getMessage(), TRUE));
            $this->_pstPagoFacilLogger->debug($exception->getMessage());
            $error_message = $exception->getMessage();
        }
        $result = $this->_resultJsonFactory->create();
        return $result->setData([
            'url' => $url,
            'error_message' => $error_message
        ]);
    }

    public function generateTransaction($order, $subopt, $total)
    {
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $country = empty($shipping->getCountryId())  ? $billing->getCountryId() : $shipping->getCountryId();
        $data = '';
        try{
            $pagoFacil = PagoFacilSdk::create()
                ->setTokenSecret($this->_tpConnector->getTokenSecret())    
                ->setTokenService($this->_tpConnector->getTokenService())
                ->setEnvironment($this->_tpConnector->getEnviroment());

            $orderId = $order->getId();
            $reference = $orderId . "_" . time();

            $transaction = new PfcTransaction();
            $transaction->setUrlCallback($this->_url->getUrl('pagofacilchile/payment/notify'));
            $transaction->setUrlCancel($this->_url->getUrl('checkout/onepage/failure'));
            $transaction->setUrlComplete($this->_url->getUrl('pagofacilchile/payment/complete'));
            $transaction->setCustomerEmail($order->getCustomerEmail());
            $transaction->setReference($reference);
            $transaction->setAmount($total);
            $transaction->setCurrency($order->getOrderCurrencyCode());
            $transaction->setShopCountry($country);
            $transaction->setSessionId($this->_sessionManager->getSessionId());
            $transaction->setAccountId($this->_tpConnector->getTokenService());
            $this->_logger->info( "Transaction : " .  print_r( $transaction, TRUE));
            $this->_logger->info( "metodo pago : " .  $subopt);
            $data = (object)$pagoFacil->initPayment($transaction, $subopt);
        } catch (\Exception $exception) {
            $this->_logger->info( "EXEPTION init Payment method : " .  print_r($exception->getMessage(), TRUE));
            $this->_pstPagoFacilLogger->debug($exception->getMessage());
            throw new \Exception($exception->getMessage());
        }
        return $data;
    }
}
