<?php 

namespace Core;

class Criptografia{
	
	private $cipher = "aes-256-ctr";
	private $key = "tJB{a(67P2T6";

	public static function criptografar($texto,$iv):string{
		$obj = new Criptografia();
		if (in_array($obj->cipher, openssl_get_cipher_methods())){
		    $texto_cifrado = openssl_encrypt($texto, $obj->cipher, $obj->key, $options=0, $iv);
		    return $texto_cifrado;
		}
	}
	public static function descriptografar($textocifrado,$iv):string{
		$obj = new Criptografia();
		if (in_array($obj->cipher, openssl_get_cipher_methods())){
			$texto_original = openssl_decrypt($textocifrado, $obj->cipher, $obj->key, $options=0, $iv);
			return $texto_original;
		}
	}

}