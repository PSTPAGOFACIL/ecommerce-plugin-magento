<?php 
use PHPUnit\Framework\TestCase;
use PagoFacilCore\PagoFacilSdk;
use PagoFacilCore\Transaction;
use PagoFacilCore\EnvironmentEnum;
use PagoFacilCore\PagoFacilSDKException;

/**
*  @author Aperez
*/
class PagoFacilSdkTest extends TestCase
{
     private $USERNAME;
     private $PASSWORD;
     private $TOKEN_SERVICE ;
     private $TOKEN_SECRET;


     function __construct() {
        parent::__construct();

        $this->USERNAME = getenv('USERNAME');
        $this->PASSWORD = getenv('PASSWORD');
        $this->TOKEN_SERVICE = getenv('TOKEN_SERVICE');
        $this->TOKEN_SECRET = getenv('TOKEN_SECRET');
   
    }
    public  function testGetPaymentMethods()
    {
        $pagoFacil = PagoFacilSdk::create()
            ->setTokenService($this->TOKEN_SERVICE)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);
       $result = $pagoFacil->getPaymentMethods();
        var_dump($result);
       $this->assertNotEmpty($result['types']);

        
    }

     public  function testGenerateToken()
     {
         $pagoFacil = PagoFacilSdk::create()
            ->setUsername($this->USERNAME)
            ->setPassword($this->PASSWORD)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);
         $result = $pagoFacil->generateToken();
         var_dump($result);
         $this->assertNotEmpty($result["accessToken"]);
         $this->assertNotEmpty($result["token_type"]);
  
     }

    public function testInitPayment(){
        $trx = $this->getTransactionExample();
        $pagoFacil = PagoFacilSdk::create()
            ->setTokenSecret($this->TOKEN_SECRET)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);
        echo "\el inicio del pago es :=====\n";
        $result = $pagoFacil->initPayment($trx,'gateway');
        var_dump($result);
        $this->assertNotEmpty($result['trxId']);
        $this->assertNotEmpty($result['urlTrx']);
    }

    public function testGenerate(){
        $trx = $this->getTransactionExample();
        echo "\ntrx es ";
        var_dump($trx);
        $pagoFacil = PagoFacilSdk::create()
            ->setTokenSecret($this->TOKEN_SECRET)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);
        echo "\nantes de ejecutar\n";
        $result = $pagoFacil->createTransaction($trx);
        $this->assertNotEmpty($result['data']['idTrx']);
        $this->assertNotEmpty($result['data']['payUrl']);
        var_dump($result);

    }

    public function testiInitPayment(){
        $pagoFacil = PagoFacilSdk::create()
            ->setTokenSecret($this->TOKEN_SECRET)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);
        $trx = $this->getTransactionExample();
        $result  = $pagoFacil->initPayment($trx,'gateway');
        $this->assertNotEmpty($result['trxId']);
        $this->assertNotEmpty($result['urlTrx']);
        
    }

    public function testGetStatus(){
        $pagoFacil = PagoFacilSdk::create()
            ->setTokenSecret($this->TOKEN_SECRET)
            ->setUsername($this->USERNAME)
            ->setPassword($this->PASSWORD)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);
        $trx = $this->getTransactionExample();
        $initResult  = $pagoFacil->initPayment($trx,'gateway');
        $result = $pagoFacil->getTrxStatus($initResult['trxId']);
        $this -> assertSame('pending',$result["data"]["status"]);
        var_dump($result);

    }

    public function testPaymentMethodsCodesMatches(){
        $pagoFacil = PagoFacilSdk::create()
            ->setTokenSecret($this->TOKEN_SECRET)
            ->setTokenService($this->TOKEN_SERVICE)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);
        $pmethods = $pagoFacil->getPaymentMethods();
        $trx = $this->getTransactionExample();
        $payments = $pagoFacil->createTransaction($trx);
        var_dump($payments);
        foreach ($pmethods['types'] as $value){ 
            // var_dump($value['codigo']);
             $index = array_search($value['codigo'], array_column($payments['data']['payUrl'], 'code'));
            if($index===false) {
                echo "no se encontro codigo". $value['codigo'];
                $this->assertTrue($index);
          }
        }
        $this->assertTrue(true);
    }

    /**
     * Prueba que signature sea valida porque cmpos calzan entre campos de inicio de transaccion y respuesta.
     */
    public function testValidateSignatureTrue() {
        $reference = time();
        $transaction = new Transaction();
        $transaction->setAmount(1000);
        $transaction->setCurrency("CLP");
        $transaction->setReference($reference);
        $transaction->setCustomerEmail("user@example.com");
        $transaction->setUrlComplete("http://www.google.cl");
        $transaction->setUrlCancel("http://www.google.cl");
        $transaction->setUrlCallback("http://www.google.cl");
        $transaction->setShopCountry("CL");
        $transaction->setSessionId("582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $transaction->setAccountId($this->TOKEN_SERVICE);
        $transaction->firmar($this->TOKEN_SECRET);

        //mock de la respuesta
        $array = array(
            'x_url_callback' => 'http://www.google.cl',
            'x_url_cancel' => 'http://www.google.cl',
            'x_url_complete' => 'http://www.google.cl',
            'x_customer_email' => 'user@example.com',
            'x_reference' => $reference,
            'x_amount' => 1000,
            'x_currency' => 'CLP',
            'x_shop_country' => 'CL',
            'x_session_id' => '582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71',
            'x_account_id' => $this->TOKEN_SERVICE,
            'x_signature' => $transaction->getSignature()
        );
        $pagoFacil = PagoFacilSdk::create()
            ->setTokenSecret($this->TOKEN_SECRET)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);
        $result = $pagoFacil->validateSignature($array);
        $this->assertTrue($result);

    }

    /**
     * Prueba que signature no sea valida si hay campos que no calcen entre transaccion inicial y respuesta.
     */
    public function testValidateSignatureFalse() {
        $reference = time();
        $transaction = new Transaction();
        $transaction->setAmount(1000);
        $transaction->setCurrency("CLP");
        $transaction->setReference($reference);
        $transaction->setCustomerEmail("user@example.com");
        $transaction->setUrlComplete("http://www.google.cl");
        $transaction->setUrlCancel("http://www.google.cl");
        $transaction->setUrlCallback("http://www.google.cl");
        $transaction->setShopCountry("CL");
        $transaction->setSessionId("582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $transaction->setAccountId($this->TOKEN_SERVICE);
        $transaction->firmar($this->TOKEN_SECRET);

        //mock de la respuesta
        $array = array(
            'x_url_callback' => 'http://www.google.cl',
            'x_url_cancel' => 'http://www.google.cl',
            'x_url_complete' => 'http://www.google.cl',
            'x_customer_email' => 'user@example.com',
            'x_reference' => $reference,
            'x_amount' => 1001,
            'x_currency' => 'CLP',
            'x_shop_country' => 'CL',
            'x_session_id' => '582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71',
            'x_account_id' => $this->TOKEN_SERVICE,
            'x_signature' => $transaction->getSignature()
        );
        $pagoFacil = PagoFacilSdk::create()
            ->setTokenSecret($this->TOKEN_SECRET)
            ->setEnvironment(EnvironmentEnum::DEVELOPMENT);

        $result = $pagoFacil->validateSignature($array);
        //signature es incorrecta porque montos no calzan
        $this->assertFalse($result);

    }

    public function testSetValidEnvironment() {
        $pagoFacil = PagoFacilSdk::create()
            ->setEnvironment(EnvironmentEnum::PRODUCTION);
        $this->assertNotNull($pagoFacil);
    }

    public function testSetInvalidEnvironment() {
        $this->expectException(PagoFacilSDKException::class);
        $this->expectExceptionMessage('ERROR: Ambiente no valido.');

        $pagoFacil = PagoFacilSdk::create()
            ->setEnvironment("PRUEBA");
        $this->assertNotNull($pagoFacil);
    }

    public function testSetValidEnvironmentAsString() {
        $pagoFacil = PagoFacilSdk::create()
            ->setEnvironment("PRODUCTION");
        $this->assertNotNull($pagoFacil);
    }

    public function testSetNotAvailibleEnvironment() {
        $this->expectException(PagoFacilSDKException::class);
        $this->expectExceptionMessage('ERROR: Ambiente no disponble.');

        $pagoFacil = PagoFacilSdk::create()
            ->setEnvironment(EnvironmentEnum::BETA);
        $this->assertNotNull($pagoFacil);
    }


    public function getTransactionExample()
    {
        $transaction = new Transaction();
        $transaction->setAmount(1000);
        $transaction->setCurrency("CLP");
        $transaction->setReference(time());
        $transaction->setCustomerEmail("user@example.com");
        $transaction->setUrlComplete("http://www.google.cl");
        $transaction->setUrlCancel("http://www.google.cl");
        $transaction->setUrlCallback("http://www.google.cl");
        $transaction->setShopCountry("CL");
        $transaction->setSessionId("582ad7e0109b0549e0987e22925cb1ddc8ad46672c807c9340a7251a35d2ee71");
        $transaction->setAccountId($this->TOKEN_SERVICE);
        return $transaction;
    }


    private function arrays_are_similar($a, $b) {
        // if the indexes don't match, return immediately
        if (count(array_diff_assoc($a, $b))) {
          return false;
        }
        // we know that the indexes, but maybe not values, match.
        // compare the values between the two arrays
        foreach($a as $k => $v) {
          if ($v !== $b[$k]) {
            return false;
          }
        }
        // we have identical indexes, and no unequal values
        return true;
    }
}
