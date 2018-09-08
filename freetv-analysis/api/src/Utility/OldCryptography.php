<?php

/*
*	This class is used to encrypt agency users passwords when a agency user is created within the cad system. (See agencyUser.mdl)
*	It is based on the encryption method provided by NetStarter and used in the OAS system
*	Netstarter source code as per 21/01/2015 in netStarter folder 
*	The encryption method should be changed if Netstarter changes his own one. 
*	Netstarter contact: Kendrick Xin <kxin@netstarter.com>
*
*   Note from Thimira: This is only used to decrypt old agency users. Do not use it for any future decryption.
*	 
*/

namespace App\Utility;

class OldCryptography {

	// HAS to be same as netstarter
	private static $defaultKey = array(
			                   1, 45, 5, 1,
			                   3, 23, 17, 43,
			                   5, 1,
			                   7, 111, 17, 43,
			                   9, 2,
			                   11, 0, 7, 111,
			                   13, 55,
			                   15, 12,
			                   17, 43,
			                   19, 34,13, 55,
			                   21, 0
	               								);

	// HAS to be same as netstarter
	private static $defaultVector = array(	  
									239,
									86,
									178,
									246,
									151,
									156,
									249,
									90,
									72,
									249,
									171,
									20,
									117,
									6,
									12,
									193
										);
	//string used to stored converted $defaultKey and $defaultVector
	private static $key;
	private static $iv;


	//Public Functions for encryption/decryption
	public static function encrypt($toEncrypt){
		self::convertArraysToString();
		return self::mc_encrypt($toEncrypt, self::$key, self::$iv);
	}

	public static function decrypt($toEncrypt){
		self::convertArraysToString();
		return self::mc_decrypt($toEncrypt, self::$key, self::$iv);
	}
	


	// private functions
	private static function convertArraysToString(){
		self::$key = implode(array_map("chr", self::$defaultKey));
		self::$iv = implode(array_map("chr", self::$defaultVector));
	}

	//Encryption function 
	private static function mc_encrypt($encrypt, $key, $iv)
	{
		$encrypt = stripslashes($encrypt);
		// Add Padding. This is necessary as RijndaelManaged in C# has padding set to PKCS7
	    $padding = 16 - (strlen($encrypt) % 16);
	  	$encrypt .= str_repeat(chr($padding), $padding);
	  	// do the actual encryption using Rijndeal 128
	    $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encrypt, MCRYPT_MODE_CBC, $iv);
	    $encode = base64_encode($encrypted);
	    return rtrim($encode);
	}

	//Decryption function
	private static function mc_decrypt($decrypt, $key, $iv)
	{
	    $decoded = base64_decode($decrypt);
	    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
	    mcrypt_generic_init($td, $key, $iv);
	    $decrypted = mdecrypt_generic($td, $decoded);
	    mcrypt_generic_deinit($td);
	    mcrypt_module_close($td);
	    return trim($decrypted);
	}

}

