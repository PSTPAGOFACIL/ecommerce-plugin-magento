<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 26/12/18
 * Time: 01:14 AM
 */

namespace PagoFacil\PagoFacilChile\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class CustomConfigProvider implements ConfigProviderInterface
{

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo
    )
    {
        $this->_assetRepo = $assetRepo;
    }

    public function getConfig()
    {
        return [
            'logoUrl' => $this->_assetRepo->getUrl("PagoFacil_PagoFacilChile::images/logo.png")
        ];
    }
}