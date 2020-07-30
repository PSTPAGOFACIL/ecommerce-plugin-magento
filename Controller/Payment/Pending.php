<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 28/12/18
 * Time: 02:25 PM
 */

namespace PagoFacil\PagoFacilChile\Controller\Payment;


class Pending extends \Magento\Framework\App\Action\Action
{

    protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    )
    {
        parent::__construct($context);

        $this->_pageFactory = $pageFactory;
    }

    public function execute()
    {
        return $this->_pageFactory->create();
    }
}