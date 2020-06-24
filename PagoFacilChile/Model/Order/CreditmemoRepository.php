<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 26/12/18
 * Time: 09:18 PM
 */

namespace PagoFacil\PagoFacilChile\Model\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Sales\Api\Data\CreditmemoInterface as CM;

class CreditmemoRepository
{
    /**
     * 2016-03-18
     * Bug: the @see \Magento\Sales\Model\Order\CreditmemoRepository::save() method
     * misses (does not log and does not show) the actual exception message
     * on a credit memo saving falure.
     * https://mage2.pro/t/973
     *
     * @see \Magento\Sales\Model\Order\CreditmemoRepository::save()
     * @param \Closure $proceed
     * @param CM $element
     * @return CM
     * @throws CouldNotSaveException|LE;
     */
    public function aroundSave(\Closure $proceed, CM $element)
    {
        /** @var CM $result */
        try {
            $result = $proceed($element);
        }
        catch(CouldNotSaveException $e) {
            /** @var \Exception|null $previous */
            $previous = $e->getPrevious();
            throw $previous instanceof LE ? $previous : $e;
        }
        return $result;
    }
}