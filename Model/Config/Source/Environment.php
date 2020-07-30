<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/12/18
 * Time: 10:30 AM
 */

namespace PagoFacil\PagoFacilChile\Model\Config\Source;

use PagoFacilCore\EnvironmentEnum;

require "app/code/PagoFacil/PagoFacilChile/vendor/kdu/pagofacilcore/src/EnvironmentEnum.php";

class Environment
{
    public function toOptionArray()
    {
        return [
            ['value' => EnvironmentEnum::DEVELOPMENT, 'label' => __('Development')],
            ['value' => EnvironmentEnum::PRODUCTION, 'label' => __('Production')]
        ];
    }
}