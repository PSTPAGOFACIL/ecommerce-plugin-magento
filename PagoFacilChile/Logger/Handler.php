<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/12/18
 * Time: 05:30 PM
 */

namespace PagoFacil\PagoFacilChile\Logger;


class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $fileName = '/var/log/pagofacilchile/info.log';
    protected $loggerType = \Monolog\Logger::INFO;
}