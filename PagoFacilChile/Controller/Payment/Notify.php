<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 26/12/18
 * Time: 11:55 AM
 */

namespace PagoFacil\PagoFacilChile\Controller\Payment;


use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

use PagoFacilCore\PagoFacilSdk;

require "app/code/PagoFacil/PagoFacilChile/vendor/kdu/pagofacilcore/src/PagoFacilSdk.php";

class Notify extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

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
     * @var \PagoFacil\PagoFacilChile\Logger\Logger
     */
    protected $_pstPagoFacilLogger;

    /**
     * @var \PagoFacil\PagoFacilChile\Model\Factory\Connector
     */
    protected $_tpConnector;


    /**
     * Notify constructor.
     * @param \PagoFacil\PagoFacilChile\Logger\Logger $pstPagoFacilLogger
     * @param \PagoFacil\PagoFacilChile\Model\Factory\Connector $tpc
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param PaymentHelper $paymentHelper
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param Transaction\BuilderInterface $transactionBuilder
     */
    public function __construct(
        \PagoFacil\PagoFacilChile\Logger\Logger $pstPagoFacilLogger,
        \PagoFacil\PagoFacilChile\Model\Factory\Connector $tpc,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        PaymentHelper $paymentHelper,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
    )
    {
        parent::__construct($context);

        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->_transactionRepository = $transactionRepository;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_pstPagoFacilLogger = $pstPagoFacilLogger;
        $this->_tpConnector = $tpc;
    }

    public function execute()
    {
        $this->_logger->info("Notify!");
        $request = $this->getRequest();
        $params = $request->getParams();

        if (empty($params)) {
            exit;
        }
        $this->_pstPagoFacilLogger->debug(print_r($params, true));

        $reference = $request->getParam('x_reference');
	$transaction_id = $request->getParam('x_gateway_reference');
        $reference = explode('_', $reference);
        $order_id = $reference[0];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order_model = $objectManager->get('Magento\Sales\Model\Order');
        $order = $order_model->load($order_id);
        $method = $order->getPayment()->getMethod();
        $methodInstance = $this->_paymentHelper->getMethodInstance($method);
        $totalOrder = $methodInstance->getAmount($order);
        $ct_monto = $request->getParam('x_amount');
        $pagoFacil = PagoFacilSdk::create()
                ->setTokenSecret($this->_tpConnector->getTokenSecret())    
                ->setTokenService($this->_tpConnector->getTokenService())
                ->setEnvironment($this->_tpConnector->getEnviroment());

        //valida que concidan montos y signature
        if ($ct_monto != $totalOrder || !$pagoFacil->validateSignature($params)) {
            exit;
        }
        $status = $request->getParam('x_result');

        $payment = $order->getPayment();
	    $payment->setTransactionId($transaction_id);        
        $statuses = $methodInstance->getOrderStates();
        if ($status == 'pending') {
            exit;
        }
        switch ($status){
            case 'completed':
                $payment->setIsTransactionPending(false);
                $payment->setIsTransactionApproved(true);
                $status = $statuses["approved"];
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $invoice = $objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
                $invoice = $invoice->setTransactionId($payment->getTransactionId())
                    ->addComment("Invoice created.")
                    ->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register()
                    ->pay();
                $invoice->save();
                // Save the invoice to the order
                $transactionInvoice = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionInvoice->save();

                $order->addStatusHistoryComment(
                    __('Invoice #%1.', $invoice->getId())
                )->setIsCustomerNotified(true);

                $message = __('Payment approved');
                break;
            case 'failed':
                $payment->setIsTransactionDenied(true);
                $status = $statuses["rejected"];
                $state = \Magento\Sales\Model\Order::STATE_CANCELED;
                $order->cancel();
                $message = __('Payment declined');
            default:
        }
        try {
            $order->setState($state)->setStatus($status);
            $payment->setSkipOrderProcessing(true);
            $transaction = $this->_transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($payment->getTransactionId())
                ->build(Transaction::TYPE_ORDER);
            $payment->addTransactionCommentsToOrder($transaction, $message);
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();
            $transaction->save();
       } catch (\Exception $exception) {
            $this->_logger->info( "EXEPTION notify : " .  print_r($exception->getMessage(), TRUE));
            $this->_pstPagoFacilLogger->debug($exception->getMessage());
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
