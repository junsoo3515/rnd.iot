<?php function crypt_random($min=0,$max=0x7FFFFFFF){if($min==$max){return $min;}if(function_exists('openssl_random_pseudo_bytes')){if((PHP_OS & "\xDF\xDF\xDF")!=='WIN'){extract(unpack('Nrandom',openssl_random_pseudo_bytes(4)));return abs($random)%($max - $min)+ $min;}}static $urandom=true;if($urandom===true){$urandom=@fopen('/dev/urandom','rb');}if(!is_bool($urandom)){extract(unpack('Nrandom',fread($urandom,4)));return abs($random)%($max - $min)+ $min;}if(version_compare(PHP_VERSION,'5.2.5','<=')){static $seeded;if(!isset($seeded)){$seeded=true;mt_srand(fmod(time()* getmypid(),0x7FFFFFFF)^ fmod(1000000 * lcg_value(),0x7FFFFFFF));}}static $crypto;if(!isset($crypto)){$key=$iv='';for($i=0;$i<8;$i++){$key.=pack('n',mt_rand(0,0xFFFF));$iv.=pack('n',mt_rand(0,0xFFFF));}switch(true){case class_exists('Crypt_AES'):$crypto=new Crypt_AES(CRYPT_AES_MODE_CTR);break;case class_exists('Crypt_TripleDES'):$crypto=new Crypt_TripleDES(CRYPT_DES_MODE_CTR);break;case class_exists('Crypt_DES'):$crypto=new Crypt_DES(CRYPT_DES_MODE_CTR);break;case class_exists('Crypt_RC4'):$crypto=new Crypt_RC4();break;default:extract(unpack('Nrandom',pack('H*',sha1(mt_rand(0,0x7FFFFFFF)))));return abs($random)%($max - $min)+ $min;}$crypto->setKey($key);$crypto->setIV($iv);$crypto->enableContinuousBuffer();}extract(unpack('Nrandom',$crypto->encrypt("\0\0\0\0")));return abs($random)%($max - $min)+ $min;};