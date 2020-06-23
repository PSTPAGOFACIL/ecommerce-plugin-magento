<?php


namespace PagoFacilCore;

require "app/code/PagoFacil/PagoFacilChile/vendor/kdu/pagofacilcore/src/Url.php";
require "app/code/PagoFacil/PagoFacilChile/vendor/kdu/pagofacilcore/src/PagoFacilSDKException.php";

class PagoFacilSdk
{

    const ERROR_MSG = "Error en llamada a servicio. ";
    const ERROR_MSG_NOT_VALID_PAYMENT_METHOD = "Metodo de pago invalido.";
    const ERROR_MSG_SIGNATURE_NULL_OR_BLANK = "ERROR: Signature es null.";
    const ERROR_TOKEN_SERVICE_NULL = "ERROR: Token service null.";
    const ERROR_TOKEN_SECRET_NULL = "ERROR: Token secret null.";
    const ERROR_USERNAME_NULL = "ERROR: Username is null.";
    const ERROR_PASSWORD_NULL = "ERROR: Password is null.";
    const ERROR_ENVIRONMENT_NOT_AVAILEBLE = "ERROR: Ambiente no disponble.";
    const ERROR_ENVIRONMENT_NOT_VALID = "ERROR: Ambiente no valido.";

    private $tokenService;
    private $tokenSecret;
    private $username;
    private $password;
    private $environement;
    private $currency;
    private $available_environments;

    
    public function __construct() {
        //carga ambientes disponibles
        $this->available_environments = array();
        $this->available_environments[] = EnvironmentEnum::DEVELOPMENT;
        $this->available_environments[] = EnvironmentEnum::PRODUCTION;
    }

    /**
     * Crea instancia de PagoFacilSDk
     */
    public static function create() {
        $instance = new self();
        return $instance;
    }

    /**
     * Setter tokenSecret
     */
    public function setTokenSecret($token_secret) {
        $this->tokenSecret = $token_secret;
        return $this;
    }

    /**
     * Setter tokenSecret
     */
    public function setTokenService($token_secret) {
        $this->tokenService = $token_secret;
        return $this;
    }

    /**
     * Setter Username
     */
    public function setUsername($username) {
        $this->username =  $username;
        return $this;
    }

    /**
     * Setter Password
     */
    public function setPassword($password) {
        $this->password =  $password;
        return $this;
    }

    /**
     * Setter Environment
     */
    public function setEnvironment($environment) {
        $this->isAvailibleEnvironment($environment);
        $this->environement = $environment;
        return $this;
    }

    /**
     * Setter Currency
     */
    public function setCurrency($currency) {
        $this->currency = $currency;
        return $this;
    }


    /**
     * Retorna credenciales
     */
    public function getCredentials() {
        $data = array(
            'tokenService' => $this->tokenService,
            'tokenSecret' => $this->tokenSecret
        );
        return $data;
    }

    /**
     * Retorna lista de ambientes disponibles
     */
    private function isAvailibleEnvironment($value) {
        if(!EnvironmentEnum::isValid($value)) {
            throw  new PagoFacilSDKException(self::ERROR_ENVIRONMENT_NOT_VALID);
        }
        if (!in_array($value, $this->available_environments)) {
            throw  new PagoFacilSDKException(self::ERROR_ENVIRONMENT_NOT_AVAILEBLE);
        }
    }

    /**
     * Retorna metodos de pago
    */
    public function getPaymentMethods() {
        if (is_null($this->tokenService)) {
            throw new PagoFacilSDKException(self::ERROR_TOKEN_SERVICE_NULL);
        }
        $url = Url::METODOS_PAGO($this->environement);
        //add parameter accound_id to the get payment request
        $dataArray = array("x_account_id" => $this->tokenService);
        if ($this->currency) {
            $dataArray["x_currency"] = $this->currency;
        }
        $url = $url."?".http_build_query($dataArray);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $resp = curl_exec($ch);
        $resp = json_decode($resp, true);
        $this->validateResp($ch, $resp);
        curl_close($ch);
        return $resp;
    }

    /**
     * crear la transaccion.
     * 
     * @param PagoFacilCore\Transaction  $transaction Datos para iniciar transaccion.
     */
    public function createTransaction($transaction) {
        if (is_null($this->tokenSecret)) {
            throw new PagoFacilSDKException(self::ERROR_TOKEN_SECRET_NULL);
        }
        $transaction->firmar($this->tokenSecret);
        if (is_null($transaction->getSignature())) {
            throw new PagoFacilSDKException(self::ERROR_MSG_SIGNATURE_NULL_OR_BLANK);
        }
        $arrayTransacction = $transaction->toValidArray(true);
        $url = Url::CREATE_PAY_TRANSACTION(($this->environement));
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayTransacction));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        $resp = curl_exec($ch);
        $decodedresp = json_decode($resp, true);
        $this->validateResp($ch, $decodedresp);
        curl_close($ch);
        return $decodedresp;
    }

    /**
     * Inicia Transaccion
     */
    public function initPayment($input, $paymentMethod) {
        $trxResult = $this->createTransaction($input);
        $urls = $trxResult['data']['payUrl'];
        $index = array_search($paymentMethod, array_column($urls, 'code'));
        if ($index === false) {
            throw new PagoFacilSDKException(self::ERROR_MSG_NOT_VALID_PAYMENT_METHOD);
        } else {
            $selected = $urls[$index]['url'];
            $result = array(
                'trxId' => $trxResult['data']['idTrx'],
                'urlTrx' => $selected
            );
            return $result;
        }
    }

    /**
     * Genera Token
     */
    public function generateToken() {
        if (is_null($this->username)) {
            throw new PagoFacilSDKException(self::ERROR_USERNAME_NULL);
        }
        if (is_null($this->password)) {
            throw new PagoFacilSDKException(self::ERROR_PASSWORD_NULL);
        }
        $url = Url::LOGIN_TOKEN_JWT($this->environement);
        $ch = curl_init($url);
        $data = array(
            'username' => $this->username,
            'password' => $this->password
        );
        //attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        $resp = curl_exec($ch);
        $decodedresp = json_decode($resp, true);
        $this->validateResp($ch, $decodedresp);
        curl_close($ch);

        return $decodedresp;
    }

    /**
     * Obtiene estado de la transaccion
     */
    public function getTrxStatus($idTrx) {
        $JWTResponse = $this->generateToken();
        $accessToken = $JWTResponse['accessToken'];
        $tokenType = $JWTResponse['token_type'];
        $url = Url::STATUS_TRANSACTION($this->environement);
        $requestStr = str_replace('{id}', $idTrx, $url);
        $headers[] = "Authorization: $tokenType $accessToken";
        $ch = curl_init($requestStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($ch);
        $decodedresp = json_decode($resp, true);
        $this->validateResp($ch, $decodedresp);
        return $decodedresp;
    }

    /**
     * Valida respuesta
     */
    function validateResp($curl, $decodedresp) {
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        if ($err) {
            curl_close($curl);
            throw new PagoFacilSDKException(self::ERROR_MSG . $err);
        }

        if ($code != 200) {
            $MSJ = self::ERROR_MSG;
            $MSJ = $MSJ . " Codigo : $code. Message :" . $decodedresp['message'];
            curl_close($curl);
            $resp = '';
            if (array_key_exists('errors', $decodedresp)) {
                $resp = $decodedresp['errors'];
            }
            throw new PagoFacilSDKException($MSJ, $resp, $code);
        }
    }

    /**
     * Valida firma del response
     * 
     * @param $data contiene los datos con los cuales se genera la firma
     * @return true si la signature 
     * */
     public function validateSignature($data) {
        if (is_null($this->tokenSecret)) {
            throw new PagoFacilSDKException(self::ERROR_TOKEN_SECRET_NULL);
        }
        // Si no tiene firma se devuleve como error
        if (empty($data['x_signature'])) {
            return false;
        }
        $signatureData = $data['x_signature'];
        // Se elimina firma anterior
        unset($data['x_signature']);
        // Se genera la firma
        $signature = Transaction::firmarArreglo($data, $this->tokenSecret);
        return  $signature == $signatureData;
    }


}
