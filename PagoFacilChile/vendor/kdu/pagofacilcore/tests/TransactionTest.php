<?php 
use PHPUnit\Framework\TestCase;

use PagoFacilCore\Transaction;
use PagoFacilCore\PagoFacilSDKException;

/**
*  @author Aperez
*/
class TransactionTest extends TestCase
{
    function __construct() {
        parent::__construct();
    }

    /**
    * prueba construccion de objeto transaccion
    */
    public function testNewTransaction() {
        $transaction = new Transaction();
        $this->assertNotNull($transaction);
    }

    /**
    * prueba que al generar arreglo de datos se tengan todos los campos
    */
    public function testTransactionRequireFields() {
        $this->expectException(PagoFacilSDKException::class);
        $this->expectExceptionMessage('Required field x_currency is null');
        
        $transaction = new Transaction();
        $transaction->setAmount(1000);
        $transaction->setReference(time());
        $transaction->setCustomerEmail("user@example.com");
        $transaction->setUrlComplete("http://www.google.cl");
        $transaction->setUrlCancel("http://www.google.cl");
        $transaction->setUrlCallback("http://www.google.cl");
        $transaction->setShopCountry("CL");
        $transaction->setSessionId("582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $transaction->setAccountId("tokenservice123");
        //obtiene objeto arreglo valido (retorna exception por no tener campo currency )
        $append_signature = false;
        $transaction->toValidArray($append_signature);
    }

    /**
    * prueba consistencia de datos de objeto transaccion y arreglo que genera
    */
    public function testTransactionAllFields() {
        $transaction = new Transaction();
        $transaction->setAmount(1000);
        $transaction->setReference("1234");
        $transaction->setCurrency("CLP");
        $transaction->setCustomerEmail("user@example.com");
        $transaction->setUrlComplete("http://www.google.cl");
        $transaction->setUrlCancel("http://www.google.cl");
        $transaction->setUrlCallback("http://www.google.cl");
        $transaction->setShopCountry("CL");
        $transaction->setSessionId("582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $transaction->setAccountId("tokenservice123");
        $append_signature = false;
        $array = $transaction->toValidArray($append_signature);
        $this->assertEquals($array["x_amount"], 1000);
        $this->assertEquals($array["x_reference"], "1234");
        $this->assertEquals($array["x_currency"], "CLP");
        $this->assertEquals($array["x_customer_email"], "user@example.com");
        $this->assertEquals($array["x_url_complete"], "http://www.google.cl");
        $this->assertEquals($array["x_url_cancel"], "http://www.google.cl");
        $this->assertEquals($array["x_url_callback"], "http://www.google.cl");
        $this->assertEquals($array["x_shop_country"], "CL");
        $this->assertEquals($array["x_session_id"], "582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $this->assertEquals($array["x_account_id"], "tokenservice123");
    }

    /**
     * prueba la creacion de la firma
     */
    public function testTransactionSignature() {
        $transaction = new Transaction();
        $transaction->setAmount(1000);
        $transaction->setReference("1234");
        $transaction->setCurrency("CLP");
        $transaction->setCustomerEmail("user@example.com");
        $transaction->setUrlComplete("http://www.google.cl");
        $transaction->setUrlCancel("http://www.google.cl");
        $transaction->setUrlCallback("http://www.google.cl");
        $transaction->setShopCountry("CL");
        $transaction->setSessionId("582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $transaction->setAccountId("tokenservice123");
        $tokenSecret = "1234";
        $transaction->firmar($tokenSecret);
        $this->assertNotNull($transaction->getSignature());
    }

     /**
    * prueba consistencia de datos de objeto transaccion y arreglo que genera, incluyendo campo signature
    */
    public function testTransactionAllFieldsAndSignature() {
        $transaction = new Transaction();
        $transaction->setAmount(1000);
        $transaction->setReference("1234");
        $transaction->setCurrency("CLP");
        $transaction->setCustomerEmail("user@example.com");
        $transaction->setUrlComplete("http://www.google.cl");
        $transaction->setUrlCancel("http://www.google.cl");
        $transaction->setUrlCallback("http://www.google.cl");
        $transaction->setShopCountry("CL");
        $transaction->setSessionId("582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $transaction->setAccountId("tokenservice123");
        $tokenSecret = "1234";
        $transaction->firmar($tokenSecret);
        $append_signature = true;
        $array = $transaction->toValidArray($append_signature);
        $this->assertEquals($array["x_amount"], 1000);
        $this->assertEquals($array["x_reference"], "1234");
        $this->assertEquals($array["x_currency"], "CLP");
        $this->assertEquals($array["x_customer_email"], "user@example.com");
        $this->assertEquals($array["x_url_complete"], "http://www.google.cl");
        $this->assertEquals($array["x_url_cancel"], "http://www.google.cl");
        $this->assertEquals($array["x_url_callback"], "http://www.google.cl");
        $this->assertEquals($array["x_shop_country"], "CL");
        $this->assertEquals($array["x_session_id"], "582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $this->assertEquals($array["x_account_id"], "tokenservice123");
        $this->assertEquals($array["x_signature"], $transaction->getSignature());
    }
}