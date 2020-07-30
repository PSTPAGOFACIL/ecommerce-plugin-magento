<?php

namespace PagoFacilCore;

class Utils {
    
    /**
     * remueve palabras especiales de php y escapa codigo html
     */
    public static function sanitize($value) {
        $invalid_keys = array(
            'file_put_contents',
            '<?=',
            '<?php',
            '?>',
            'eval(',
            '$_REQUEST',
            '$_POST',
            '$_GET',
            '$_SESSION',
            '$_SERVER',
            'exec(',
            'shell_exec(',
            'invokefunction',
            'call_user_func_array',
            'display_errors',
            'ini_set',
            'set_time_limit',
            'set_magic_quotes_runtime',
            'DOCUMENT_ROOT',
            'include(',
            'include_once(',
            'require(',
            'require_once(',
            'base64_decode',
            'file_get_contents',
            'sizeof',
            'array(',
            'system(',
            '\\n',
            '\\r',
        );
        return strip_tags(str_replace($invalid_keys, "", $value));
    }

    /**
     * limpia valores que no sean alphanumericos
     */
    public static function clean_non_alphanumeric($value) {
        return preg_replace( '/[\W]/', '', $value);
    }

}