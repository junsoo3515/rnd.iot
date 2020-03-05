<?php

// MySQL info
$sDbIp ='127.0.0.1:3311';	// MySQL IP localhost
$sDbId = 'root';			// MySQL User ID
$sDbPw = 'root';			// MySQL User Password

// DataBase name
$sDbDb = 'rtodor';

// DB items : Using DB creation. name[16]
$aDbItems   = array ('ou', 'oi', 'signal', 'sout1', 'sout2', 'sout3', 'sout4', 'H2S', 'NH3', 'CH3SH', 'VOC', 'temperature', 'humidity', 'winddirection', 'windspeed', 'pozip', 'ou_Alm', 'H2S_Alm', 'NH3_Alm', 'VOC_Alm', 'atm');

// DB item decimal. 1=*0.1, 3=*0.001, Special code: 10=wind_direction, 11=pozip
$aSbItemDec = array (   3,    3,        3,       3,       3,       3,       3,     3,     3,       3,     3,             1,          1,              10,           1,      11,        0,         0,         0,        0,     0);

// Set default map
$sMapType = 'vworld';

// RSA Key (Password encryption)
$RSAPrivatekey='';

// PHP Timezone (http://php.net/manual/kr/timezones.php)
date_default_timezone_set('Asia/Seoul');

?>
