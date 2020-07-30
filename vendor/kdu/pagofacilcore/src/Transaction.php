<?php


namespace PagoFacilCore;

class Transaction
{
    private $urlCallback;
    private $urlCancel;
    private $urlComplete;
    private $customerEmail;
    private $reference;
    private $amount;
    private $currency;
    private $shopCountry;
    private $sessionId;
    private $accountId;
    private $signature;

    const ERROR_REQUIRED_FIELD = "Required field %s is null";

    public function __construct() {
    }

    public function setUrlCallback($url_callback) {
        $this->urlCallback = $url_callback;
    }

    public function setUrlCancel($url_cancel) {
        $this->urlCancel = $url_cancel;
    }

    public function setUrlComplete($url_complete) {
        $this->urlComplete = $url_complete;
    }

    public function setCustomerEmail($customer_email) {
        $this->customerEmail = $customer_email;
    }

    public function setReference($reference) {
        $this->reference = $reference;
    }

    public function setAmount($amount) {
        $this->amount = $amount;
    }

    public function setCurrency($currency) {
        $this->currency = $currency;
    }

    public function setShopCountry($shop_country) {
        $this->shopCountry = $shop_country;
    }

    public function setSessionId($session_id) {
        $this->sessionId = $session_id;
    }

    public function setAccountId($accountId) {
        $this->accountId = $accountId;
    }

    public function getSignature() {
        return $this->signature;
    }

    /**
     * valida atributos de transaccion y Guarda valor de firma en atributo x_signature
     * 
     * @param string $token_secret Clave de cifrado
     */
    public function firmar($token_secret) {
        $arreglo = $this->toValidArray(false);
        $this->signature = $this->firmarArreglo($arreglo, $token_secret);
    }

    /**
     * Obtiene firma usando datos de la transaccion
     * 
     * @param array $arreglo        Datos de la transaccion
     * @param string $token_secret  Clave de cifrado
     * @return signature 
     */
    public static function firmarArreglo($arreglo, $token_secret) {
        //Ordeno Arreglo
        ksort($arreglo);
        //Concateno Arreglo  
        $arregloFiltrado = array_filter($arreglo, function ($val) {
            return substr($val, 0,  2) === "x_";
        }, ARRAY_FILTER_USE_KEY);

        $mensaje = Transaction::concatenarArreglo($arregloFiltrado);
        //Firmo Mensaje
        $mensajeFirmado = hash_hmac('sha256', $mensaje, $token_secret);

        //Guardo y retorno el mensaje firmado
        return $mensajeFirmado;
    }

    /**
     * Concatena arreglo
     */
    private static function concatenarArreglo($arreglo) {
        $resultado = "";
        foreach ($arreglo as $field => $value) {
            if (is_array($value)) {
                $resultado .= $field . json_encode($value);
            } else {
                $resultado .= $field . $value;
            }
        }
        return $resultado;
    }

    /** 
     * Retorna objeto como un arreglo valido
     * 
     * @param bool $append_signature Si es true retorna objeto con signature
     * @throw PagoFacilSDKException si a instancia le falta algun campo
    */
    public function toValidArray($append_signature) {
        //construye arreglo
        $array = array(
            'x_url_callback' => $this->urlCallback,
            'x_url_cancel' => $this->urlCancel,
            'x_url_complete' => $this->urlComplete,
            'x_customer_email' => $this->customerEmail,
            'x_reference' => $this->reference,
            'x_amount' => $this->amount,
            'x_currency' => $this->currency,
            'x_shop_country' => $this->shopCountry,
            'x_session_id' => $this->sessionId,
            'x_account_id' => $this->accountId
        );
        //valida que se tenga todos los campos requeridos
        foreach ($array as $key => $value) {
            if (is_null($value)) {
                throw new PagoFacilSDKException(sprintf(SELF::ERROR_REQUIRED_FIELD, $key));
            }
        }
        if ($append_signature) {
            $array['x_signature'] = $this->signature;
        }
        return $array;
    }
}
