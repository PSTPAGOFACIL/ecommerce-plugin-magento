<?php

use PHPUnit\Framework\TestCase;
use PagoFacilCore\Utils;

class ValidatorTest extends TestCase {


    public function test_sanitizePhpCodeValue() {
        $description =  'C\";\n }}\n system($_GET[\"exec\"]);\n class Model{\n public function pwmed(){\n $test=\"'; 
        $new_description = Utils::sanitize($description);
        $this->assertEquals($new_description, 'C\"; }} [\"exec\"]); class Model{ public function pwmed(){ $test=\"');
    }

    public function test_sanatizeHtmlCodeValue() {
        $descripcion = '<script>console.log("hola mundo");<script>';
        $new_description = Utils::sanitize($descripcion);
        $this->assertEquals($new_description, 'console.log("hola mundo");');
    }

    public function test_remove_non_alphanumericValue() {
        $descripcion = "!@PagoFAcil2020?"; 
        $new_description = Utils::clean_non_alphanumeric($descripcion);
        $this->assertEquals($new_description, "PagoFAcil2020");
    }
}