<?php

function encryptStr ($str, $key, $iv) {
	$method = "AES-256-CBC";
    $key = hash( 'sha256', $key );
	$iv = substr( hash( 'sha256', $iv ), 0, 16 );
 
	$str = base64_encode( openssl_encrypt( $str, $method, $key, OPENSSL_RAW_DATA, $iv ) );
	
	return $str;
}

function decryptStr ($str, $key, $iv) {
	$method = "AES-256-CBC";
    $key = hash( 'sha256', $key );
	$iv = substr( hash( 'sha256', $iv ), 0, 16 );

	$str = openssl_decrypt( base64_decode( $str ), $method, $key, OPENSSL_RAW_DATA, $iv );
    
	return $str;
}

/*
 * Important: Change the encryption/decryption static keys
 */
function encryptStrStatic ($str, $iv) {
	return encryptStr ($str, 'asgaegasdgasgasgasgs', $iv);
}

function decryptStrStatic ($str, $iv) {
	return decryptStr ($str, 'asgaegasdgasgasgasgs', $iv);	
}
