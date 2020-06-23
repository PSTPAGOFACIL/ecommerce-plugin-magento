<?php


namespace PagoFacilCore;


abstract class EnvironmentEnum
{
    const DEVELOPMENT = "DEVELOPMENT";
    const PRODUCTION = "PRODUCTION";
    const BETA = "BETA"; 

    public static function isValid($value) {
        return ($value == EnvironmentEnum::DEVELOPMENT ||
                        $value == EnvironmentEnum::PRODUCTION ||
                        $value == EnvironmentEnum::BETA);
    }
}