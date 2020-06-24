<?php

use PagoFacilCore\EnvironmentEnum;

namespace PagoFacilCore;


abstract class UrlEnum 
{
    const API_STATUS_TRANSACTION = 'https://apis.pgf.cl/v2/trxs/{id}/status';
    const API_DEV_STATUS_TRANSACTION = 'https://apis-dev.pgf.cl/v2/trxs/{id}/status';
    //BETA?

    const API_METODOS_PAGO = 'https://apis-dev.pgf.cl/v2/payments';
    const API_DEV_METODOS_PAGO = 'https://apis-dev.pgf.cl/v2/payments';
    //BETA?

    const API_CREATE_PAY_TRANSACTION = 'https://apis.pgf.cl/v2/trxs';
    const API_DEV_CREATE_PAY_TRANSACTION = 'https://apis-dev.pgf.cl/v2/trxs';
    //BETA?

    const API_LOGIN_TOKEN_JWT = 'https://apis.pgf.cl/v2/login';
    const API_DEV_LOGIN_TOKEN_JWT = 'https://apis-dev.pgf.cl/v2/login';
    //BETA?
}

class Url 
{
    const ERROR_VALUE_INVALID = "Ambiente invalido";

    /**
     * Obtiene url de status transaction segun ambiente activado
     */
    public static function STATUS_TRANSACTION($environment) {
        if ($environment == EnvironmentEnum::PRODUCTION) {
            return UrlEnum::API_STATUS_TRANSACTION;
        } else if ($environment == EnvironmentEnum::DEVELOPMENT) {
            return UrlEnum::API_DEV_STATUS_TRANSACTION;
        }
        throw new PagoFacilSDKException(self::ERROR_VALUE_INVALID);
    }

    /**
     * Obtiene url de metodo de pago segun ambiente activado
     */
    public static function METODOS_PAGO($environment) {
        if ($environment == EnvironmentEnum::PRODUCTION) {
            return UrlEnum::API_METODOS_PAGO;
        } else if ($environment == EnvironmentEnum::DEVELOPMENT) {
            return UrlEnum::API_DEV_METODOS_PAGO;
        }
        throw new PagoFacilSDKException(self::ERROR_VALUE_INVALID);
    }

    /**
     * Obtiene url de pay transaction segun ambiente activado
     */
    public static function CREATE_PAY_TRANSACTION($environment) {
        if ($environment == EnvironmentEnum::PRODUCTION) {
            return UrlEnum::API_CREATE_PAY_TRANSACTION;
        } else if ($environment == EnvironmentEnum::DEVELOPMENT) {
            return UrlEnum::API_DEV_CREATE_PAY_TRANSACTION;
        }
        throw new PagoFacilSDKException(self::ERROR_VALUE_INVALID);
    }

    /**
     * Obtiene url de login token jwt segun ambiente activado
     */
    public static function LOGIN_TOKEN_JWT($environment) {
        if ($environment == EnvironmentEnum::PRODUCTION) {
            return UrlEnum::API_LOGIN_TOKEN_JWT;
        } else if ($environment == EnvironmentEnum::DEVELOPMENT) {
            return UrlEnum::API_DEV_LOGIN_TOKEN_JWT;
        }
        throw new PagoFacilSDKException(self::ERROR_VALUE_INVALID);
    }
}