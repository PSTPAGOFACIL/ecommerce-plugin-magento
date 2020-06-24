<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/12/18
 * Time: 09:26 AM
 */

namespace PagoFacil\PagoFacilChile\Model;

class PagoFacilChile extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'pagofacilchile';

    protected $_code = self::CODE;

    protected $_isGateway = true;

    protected $_canOrder = true;

    protected $_canAuthorize = true;

    protected $_canCapture = true;

    protected $_canCapturePartial = true;

    protected $_canRefund = false;

    protected $_canRefundInvoicePartial = false;

    protected $_canVoid = true;

    protected $_canFetchTransactionInfo = true;

    protected $_canReviewPayment = true;

    protected $_minAmount = null;

    protected $_maxAmount = null;

    protected $_tpConnector;

    protected $_pstPagoFacilLogger;


    /**
     * PagoFacilChile constructor.
     * @param \PagoFacil\PagoFacilChile\Logger\Logger $pstPagoFacilLogger
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \PagoFacil\PagoFacilChile\Logger\Logger $pstPagoFacilLogger,
        \PagoFacil\PagoFacilChile\Model\Factory\Connector $tpc,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
        $this->_tpConnector = $tpc;
        $this->_pstPagoFacilLogger = $pstPagoFacilLogger;
        $this->_scopeConfig = $scopeConfig;

    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {

        if ($quote && (
                $quote->getBaseGrandTotal() < $this->_minAmount
                || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }
        if (!$this->_tpConnector->getTokenSecret() || !$this->_tpConnector->getTokenService()) {
            return false;
        }
        return parent::isAvailable($quote);
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    public function getOrderStates()
    {
        return array(
            'pending' => $this->_scopeConfig->getValue('payment/pagofacilchile/states/pending', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'approved' => $this->_scopeConfig->getValue('payment/pagofacilchile/states/approved', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'rejected' => $this->_scopeConfig->getValue('payment/pagofacilchile/states/rejected', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        );
    }

    public function getAmount($order)
    {
        $amount = $order->getGrandTotal();
        return round($amount);

    }
}