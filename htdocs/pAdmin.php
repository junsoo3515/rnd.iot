<?php

require_once('common.php');

session_start();

$sId = $_SESSION[$sDbDb.'_id'];
$job = $_GET['j'];

if ($sId != 'admin') {
	die ("<html><head><meta http-equiv='refresh' content='0;url=pRealtime.php?login=1'></head></html>");
}

// connect db
$cDb = mysql_connect($sDbIp, $sDbId, $sDbPw);
if (!$cDb) die ('EC#730008');
mysql_query('set names utf8');
if (!mysql_select_db($sDbDb, $cDb)) die ('EC#730009');



// Ajax.Site save //////////////////////////////////////////////////////////////
if ($_GET['q'] == 'ss') {
	$iSite = $_POST['siteno'];
	$sName = $_POST['name'];
	$fLng  = $_POST['lng'];
	$fLat  = $_POST['lat'];
	$sAddr = $_POST['addr'];
	$sFile = $_POST['file'];
	$sRema = $_POST['rema'];
	if ($sFile == '') {
		$sFile = 'NULL';
		$sUseFl = 'Y';
	}
	else {
		$sFile = "'$sFile'";
		$sUseFl = 'N';
	}
	
	$sQuery = "INSERT INTO `sites` (`SITENO`, `NAME`, `LNG`, `LAT`, `ADDR`, `FILE`, `REMARK`)".
		" VALUES ('$iSite','$sName','$fLng','$fLat','$sAddr',$sFile,'$sRema')".
		" ON DUPLICATE KEY UPDATE `NAME`='$sName', `LNG`='$fLng', `LAT`='$fLat', `ADDR`='$sAddr', `FILE`=$sFile, `REMARK`='$sRema'";
	mysql_query($sQuery, $cDb) or die ('EC#730101');
	
	$sQuery = "INSERT INTO `cctv` (`CCTVNO`,`CCTVNM`,`URL`,`CCTV_USE_FL`,`SENSOR_USE_FL`) VALUES ('$iSite', '$sName', '', 'N', '$sUseFl')".
		" ON DUPLICATE KEY UPDATE `CCTVNM` = '$sName', `SENSOR_USE_FL` = '$sUseFl'";
	mysql_query($sQuery, $cDb) or die ('EC#730101');
	
	mysql_close ($cDb);
	die ('OK');
}



// Ajax.Site delete ////////////////////////////////////////////////////////////
if ($_GET['q'] == 'sd') {
	$iSite = $_POST['siteno'];
	
	$sQuery = "DELETE FROM `sites` WHERE `SITENO`='$iSite'";
	mysql_query($sQuery, $cDb) or die ('EC#730103');
	
	$sQuery = "DELETE FROM `cctv` WHERE `CCTVNO`='$iSite'";
	mysql_query($sQuery, $cDb) or die ('EC#730103');
	
	mysql_close ($cDb);
	die ('OK');
}



// Ajax.Etc rsa ////////////////////////////////////////////////////////////

if ($_GET['q'] == 'rsa') {
	$rsakeybit = $_POST['rsakeybit'];
	if ((isset($rsakeybit)) && (0 < $rsakeybit)) {
		// Create RSA Key
		$iTime = MicrotimeFloat();
		require_once('rsa/RSA.php');
		$rsa = new Crypt_RSA();
		$rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
		$rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
		extract($rsa->createKey($rsakeybit));
		$keylen = isset($privatekey) ? strlen($privatekey) : 0;
		if (400 < $keylen) {
			SetSetting ($cDb, 'rsakey', $privatekey);
			$iTime = MicrotimeFloat() - $iTime;
			// $msg = $str_rsa_new.'<br>'.$rsakeybit.' bit RSA Key, Length '.$keylen.' byte, '.number_format($iTime,3).'sec';
			$msg = 'ok';
		} else {
			$msg = 'err';
		}
	} else {
		SetSetting ($cDb, 'rsakey', '');
		$msg = 'del';
	}
	mysql_close ($cDb);
	die ($msg);
}



// Ajax.Etc setting ////////////////////////////////////////////////////////////

if ($_GET['q'] == 'se') {
	// Save settings
	$rv = false;
	foreach ($_POST as $id => $value) {
		SetSetting ($cDb, $id, $value);
		$rv = true;
	}
	mysql_close ($cDb);
	if ($rv) die ("ok");
	else die ("err");
}




// Head & Menu /////////////////////////////////////////////////////////////////

PrintHtmlHead ();

if (!$ISM) {
	echo ("<script type='text/javascript'>\n");
	echo ("try{window.parent.frames[0].fSetMenu(3,'$sId');}catch(err){}\n");
	echo ("</script>\n");
}

function PrintSubMenu ($j, $s, $lk) {
	echo ("	<tr><td class='".(($j==$s)?'SubMenuSel':'SubMenu')."' onclick='document.location=\"pAdmin.php?j=$s\"'>$lk</td></tr>\n");
}

print <<<EOF

<link rel='stylesheet' href='js/pure.css' />
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='rsa/rsa.js'></script>

<script type='text/javascript'>

function fOnLoad() {
	try {
		var h = $('#adminmenu').outerHeight();
		$('#sitelist').css({top:60+h});
	} catch (e) { }
}

</script>

</head>
<body style='cursor:default' onload='fOnLoad()'>

<div id='adminmenu' style='position:fixed; top:50px; left:10px; width:134px; background:#EEEEEE; padding:10px; border-radius:10px; border:3px solid #FFBB88;'>
	<table width='100%' border='0' cellpadding='6' cellspacing='0'>

EOF;
	if (!isset($sDbs)) PrintSubMenu ($job, 'st', $str_site);	// *** Single DB only ***
	/*
	*/
	PrintSubMenu ($job, 'pw', $str_user);
	PrintSubMenu ($job, 'it', $str_item);
	if (!isset($sDbs)) PrintSubMenu ($job, 'in', $str_input);
	PrintSubMenu ($job, 'se', $str_etc);
	//PrintSubMenu ($job, 'cd', $str_db);
print <<<EOF
	</table>
</div>

<div style='position:absolute; top:0px; left:180px; bottom:0px; right:0px'>

EOF;



// 데이터베이스 ///////////////////////////////////////////////////////////

if ($job == 'cd') {
	echo ("<h1>$str_db</h1>\n");
	
	echo ("<p><table class='ReadBorder'><tr><td>$str_db_msg</td></tr></table></p>\n");
	
	echo ("&nbsp;\n");
	echo ("<p class='TitleFont'>DELETE DB</p>\n");
	echo ("<p><a href='createdb.php?org=pAdmin.php?j=cd' class='pure-button'>DELETE DB</a></p>\n");
	echo ("<p><font color=#FF4444>$str_db_del_msg</font></p>\n");

} else // 데이터베이스



// 기타 설정 //////////////////////////////////////////////////////////////

if ($job == 'se') {
	echo ("<h1>$str_etc</h1>\n");
	
	$sWindowTitle = GetSetting ($cDb, 'windowtitle', '');
	$iIsGuest  = GetSettingInt ($cDb, 'isguest',     0,  0,     1);
	$iIsAdmin  = GetSettingInt ($cDb, 'isadmin',     1,  0,     1);
	$sMapType  = GetSetting    ($cDb, 'maptype', $sMapType);
	$iRtUpdate = GetSettingInt ($cDb, 'rtupdate',   10,  1, 10000);
	$iRtOld    = GetSettingInt ($cDb, 'rtold',       1,  1, 10000);
	$iIsGps    = GetSettingInt ($cDb, 'isgps',       0,  0,     1);
	$iPageSize = GetSettingInt ($cDb, 'pagesize', 1000, 10, 10000);
	$iMaxDates = GetSettingInt ($cDb, 'maxdates',  366,  1, 10000);
	$iBoardLine= GetSettingInt ($cDb, 'boardline',  20,  5,  1000);
	
	echo ("<p><table border='0' cellpadding='0' cellspacing='8'>\n");
	echo ("<form id='seform1' name='seform1'>\n");
	
	echo ("<tr><td class='TitleFont' colspan='2'>$str_etc_common</td></tr>\n");
	echo ("<tr><td>$str_etc_title</td><td><input type='text' name='windowtitle' value='$sWindowTitle'></td></tr>\n");
	echo ("<tr><td>$str_etc_isguest</td><td><input type='number' name='isguest' value='$iIsGuest'></td></tr>\n");
	echo ("<tr><td>$str_etc_isadmin</td><td><input type='number' name='isadmin' value='$iIsAdmin'></td></tr>\n");
	
	echo ("<tr><td class='TitleFont' colspan='2'><br>$str_realtime</td></tr>\n");
	if ((!isset($sMapType)) || ($sMapType == NULL) || ($sMapType == '')) {
		$sMapType = 'vworld';
	}
	echo ("<tr><td>$str_maptype</td><td><select name='maptype'>");
	echo ("<option value='baidu' ".(($sMapType=='baidu' )?" selected":"").">Baidu</option>");
	echo ("<option value='vworld'".(($sMapType=='vworld')?" selected":"").">VWorld</option>");
	echo ("<option value='osm'   ".(($sMapType=='osm'   )?" selected":"").">OpenStreetMap</option>");
	echo ("</select></td></tr>\n");
	
	echo ("<tr><td>$str_etc_rtupdate</td><td><input type='number' name='rtupdate' value='$iRtUpdate'></td></tr>\n");
	echo ("<tr><td>$str_etc_rtold</td><td><input type='number' name='rtold' value='$iRtOld'></td></tr>\n");
	echo ("<tr><td>$str_etc_isgps</td><td><input type='number' name='isgps' value='$iIsGps'></td></tr>\n");
	
	echo ("<tr><td class='TitleFont' colspan='2'><br>$str_data</td></tr>\n");
	echo ("<tr><td>$str_etc_pagesize</td><td><input type='number' name='pagesize' value='$iPageSize'></td></tr>\n");
	echo ("<tr><td>$str_etc_maxdates</td><td><input type='number' name='maxdates' value='$iMaxDates'></td></tr>\n");
	
	echo ("<tr><td class='TitleFont' colspan='2'><br>$str_board</td></tr>\n");
	echo ("<tr><td>$str_etc_boardline</td><td><input type='number' name='boardline' value='$iBoardLine'></td></tr>\n");
	
	echo ("<tr><td></td><td><input type='button' value='$str_save' onclick='checkEtc();' class='pure-button'></td></tr>\n");
	echo ("</form></table></p>\n");
	
	// RSA Key length check
	$RSAPrivatekey = GetSetting ($cDb, 'rsakey', '');
	$keylen = strlen($RSAPrivatekey);
	if ($keylen < 400) $keylen = 0;			// N/A
	else if ($keylen < 1200) $keylen = 1;	// 1024bit, $keylen = 896byte
	else if ($keylen < 2400) $keylen = 2;	// 2048bit, $keylen = 1696byte
	else $keylen = 3;						// 4096bit, $keylen = 3278byte
	
	echo ("<p><table border='0' cellpadding='0' cellspacing='8'>\n");
	echo ("<form id='seform2' name='seform2' method='post'>\n");
	echo ("<tr><td class='TitleFont'>$str_rsa</td></tr>\n");
	echo ("<tr><td>RSA Key <select name='rsakeybit'>\n");
	echo ("<option value='0'   ".(($keylen==0)?' selected':'').">$str_rsa_no</option>");
	echo ("<option value='1024'".(($keylen==1)?' selected':'').">1024</option>");
	echo ("<option value='2048'".(($keylen==2)?' selected':'').">2048</option>");
	echo ("<option value='4096'".(($keylen==3)?' selected':'').">4096</option>");
	echo ("</select>\n");
	echo ("	bit <input type='button' value='$str_make' class='pure-button' onclick='MakeRsaCall()'></td></tr>\n");
	echo ("<tr><td><font color='#666666'>$str_rsa_msg</font></td></tr>\n");
	echo ("</table></p>\n");
	
print <<<EOF
	
<script type='text/javascript'>
function MakeRsaCall () {
	var dataarr = {
		'rsakeybit':seform2.rsakeybit.value
	};
	
	MsgPopupOnly ('$str_rsa_ing', 0);
	var request = $.ajax({
		url:'pAdmin.php?q=rsa',
		type:'post',
		data:dataarr,
		cache: false,
		error: function(xhr, status, msg) {
			MsgPopupError ('$str_comm_error', 2000);
		},
		success : function(msg) {
			if (msg == "err") MsgPopupError ('$str_rsa_err', 2000);
			else if (msg == "del") MsgPopupOnly ('$str_rsa_del', 1000);
			else if (msg == "ok") MsgPopupShow ('$str_rsa_new', 1000);
			else alert(msg);
		}
	});
}
function isErrItem(name, obj, mn, mx) {
	var val = obj.value;
	if ((isNaN(val)) || (val < mn) || (mx < val)) {
		MsgPopupError (name+'$str_etc_range_err1'+mn+'~'+mx+'$str_etc_range_err2', 2000);
		obj.focus();
		return true;
	}
 return false;
}
function checkEtc() {
	if (isErrItem('$str_etc_isguest',  seform1.isguest,  0, 1))     return;
	if (isErrItem('$str_etc_isadmin',  seform1.isadmin,  0, 1))     return;
	if (isErrItem('$str_etc_rtupdate', seform1.rtupdate, 1, 10000)) return;
	if (isErrItem('$str_etc_rtold',    seform1.rtold,    1, 10000)) return;
	if (isErrItem('$str_etc_isgps',    seform1.isgps,    0, 1))     return;
	if (isErrItem('$str_etc_pagesize', seform1.pagesize,10, 10000)) return;
	if (isErrItem('$str_etc_maxdates', seform1.maxdates, 1, 10000)) return;
	if (isErrItem('$str_etc_boardline',seform1.boardline,5, 1000))  return;
	
	MsgPopupOnly ('$str_save_ing', 0);
	var request = $.ajax({
		url:'pAdmin.php?q=se',
		type:'post',
		data:$("#seform1").serialize(),
		cache: false,
		error: function(xhr, status, msg) {
			MsgPopupError ('$str_comm_error', 2000);
		},
		success : function(msg) {
			if (msg == "err") MsgPopupError ('$str_save_error', 2000);
			else if (msg == "ok") {
				if (ism == 1) document.title = seform1.windowtitle.value;
				else try{parent.document.title = seform1.windowtitle.value;}catch(err){}
				MsgPopupShow ('$str_save_ok', 1000);
			} else alert(msg);
		}
	});
}
</script>

EOF;

	if (!function_exists('openssl_public_encrypt')) {
		echo ("<p><table class='ReadBorder'><tr><td>$str_rsa_warning</td></tr></table></p>\n");
	}
	
} else // 기타 설정



// 자료 입력 ///////////////////////////////////////////////////////////////////

if ($job == 'in') {
	echo ("<h1>$str_input</h1>\n");
	
	$iIsGps = GetSettingInt ($cDb, 'isgps', 0, 0, 1);
	
	$iSite = $_POST["site"];
	$iDat1 = $_POST["date"];
	$iDat2 = $_POST["dat2"];
	$dLng  = $_POST["lng"];
	$dLat  = $_POST["lat"];
	$usingLngLat = isset($dLng) && is_numeric($dLng) && isset($dLat) && is_numeric($dLat) && (-180 < $dLng) && ($dLng < 180) && (-90 < $dLat) && ($dLat < 90);
	if ($usingLngLat == false) {
		// No Location
		$sQuery = "SELECT `LNG`,`LAT` FROM `sites` WHERE `SITENO`='$iSite'";
		$cResult = @mysql_query($sQuery, $cDb);
		if ($cResult != null) {
			$aRow = @mysql_fetch_row($cResult);
			if ($aRow != null) {
				$dLng = $aRow[0];
				$dLat = $aRow[1];
			}
			@mysql_free_result($cResult);
		}
	}
	
	while (isset ($iDat1)) {
		$emsg = '';
		if (!ctype_digit($iSite)) $emsg = 'site';
		else if ((!ctype_digit($iDat1)) || (strlen($iDat1) != (($iTimeType == 1)?14:12))) $emsg = 'date';
		else if ((!ctype_digit($iDat2)) || (strlen($iDat2) != (($iTimeType == 1)?14:12))) $emsg = 'date2';
		else if (($iIsGps == 1) && ($usingLngLat == false)) $emsg = 'lng,lat';
		if ($emsg != '') {
			echo ("<p><b><font color=#FF4444>Error: ".$emsg."</font></b></p>\n");
			break;
		}
		
		$iTime = MicrotimeFloat();
		
		// time to int
		$iDate1 = DateTimeToInt ($iDat1);
		$iDate0 = DateTimeToInt ($iDat2);
		
		// POST data to array
		$aDatas = array();
		$ad = array();
		for ($i=0; $i<sizeof($aDbItems); $i++) {
			$v = $_POST[$aDbItems[$i]];
			if (isset($v) && is_numeric($v)) {
				$ad[$i] = $v;
			} else {
				$aDatas[$i] = 'NULL';
				$ad[$i] = null;
			}
		}
		
		$iWdate = 'NULL';
		$iWxy   = 'NULL';
		$sWdata = 'NULL';
		
		// Insert multi datas
		$maxmonth = array (0,31,28,31,30,31,30,31,31,30,31,30,31);
		$cnt = 0;
		while ($iDate0 <= $iDate1) {
			$th = $cnt*0.01;
			for ($i=0; $i<sizeof($aDbItems); $i++) {
				$v = $ad[$i];
				if ($v !== null) $aDatas[$i] = "'".($v + sin($th)*$v*0.5)."'";
			}
			
			if ($usingLngLat) {
				if ($cnt == 0) {
					$lng = $dLng;
					$lat = $dLat;
				} else {
					$lng = $dLng + sin($th*10)*(0.01166 + 0.0001166*$cnt);
					$lat = $dLat + cos($th*10)*(0.01    + 0.0001*$cnt);
				}
				$lng = "'$lng'";
				$lat = "'$lat'";
			} else {
				$lng = 'NULL';
				$lat = 'NULL';
			}
			
			$iDate = $iDate0;
			$sQuery = "INSERT INTO `datas` (`SITENO`,`DATE`, `LNG`, `LAT`, `WDATE`, `WXY`";
			for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ",`$aDbItems[$i]`";
			$sQuery .= ") VALUES ('$iSite','$iDate',$lng,$lat,$iWdate,$iWxy";
			for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ",$aDatas[$i]";
			$sQuery .= ") ON DUPLICATE KEY UPDATE `LNG`=$lng, `LAT`=$lat, `WDATE`=$iWdate, `WXY`=$iWxy";
			for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ",`$aDbItems[$i]`=$aDatas[$i]";
			mysql_query($sQuery, $cDb) or die ('EC#730101');
			
			// +5 minute
			$iDate0 = DateJsToInt (IntToJs($iDate0) + 5*60);
			
			$cnt++;
			if (10000 <= $cnt) break;	// check max day (max 34)
		}
		
		// Insert Last data
		$sQuery = "INSERT INTO `lasts` (`SITENO`,`DATE`, `LNG`, `LAT`, `WDATA`";
		for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ",`$aDbItems[$i]`";
		$sQuery .= ") VALUES ('$iSite','$iDate',$lng,$lat, $sWdata";
		for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ",$aDatas[$i]";
		$sQuery .= ") ON DUPLICATE KEY UPDATE `DATE`='$iDate', `LNG`=$lng, `LAT`=$lat, `WDATA`=$sWdata";
		for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ",`$aDbItems[$i]`=$aDatas[$i]";
		mysql_query($sQuery, $cDb) or die ('EC#730102');
		
		$iTime = MicrotimeFloat() - $iTime;
		echo ("<p><b><font color=#4444FF>$str_input ".number_format($cnt,0)." lines</font></b><br>\n");
		echo ("<font color=#808080>[ ".number_format($iTime,3)."sec ]</font></p>\n");
		break;
	}
	
	if (!isset($iSite)) $iSite = '101';
	
	$dateformats = ($iTimeType == 1) ? 'YmdHis' : 'YmdHi';
	if (!isset($iDat1)) $iDat1 = date($dateformats);
	if (!isset($iDat2)) $iDat2 = date($dateformats,strtotime('-1 days'));
	
	echo ("<p>$str_input_msg</p>\n");
	
	echo ("<table><form name='inform' method='post' action='pAdmin.php?j=in'>\n");
	echo ("	<tr><td class='TitleFont'>Site</td><td><input type='number' name='site' value='$iSite'></td></tr>\n");
	echo ("	<tr><td class='TitleFont'>Date</td><td><input type='number' name='date' value='$iDat1'></td></tr>\n");

	if ($iIsGps == 1) {
		echo ("	<tr><td class='TitleFont'>LNG(&#177;180)</td><td><input type='number' name='lng' value='$dLng'></td></tr>\n");
		echo ("	<tr><td class='TitleFont'>LAT(&#177;90)</td><td><input type='number' name='lat' value='$dLat'></td></tr>\n");
	}
	
	// Get all item info
	$sQuery = "SELECT `ID`,`NAME`,`UNIT` FROM `items` ORDER BY `ORDER`";
	$cResult = mysql_query($sQuery, $cDb);
	$aItems = array();
	if ($cResult != null) {
		for ($i=0; $aRow = mysql_fetch_row($cResult); $i++) {
			if (in_array($aRow[0], $aDbItems)) {
				$aItems[$i] = $aRow[0];
				$v = $_POST[$aRow[0]];
				echo ("	<tr><td class='TitleFont'>$aRow[1]</td><td><input type='number' name='$aRow[0]' value='$v'> $aRow[2]</td></tr>\n");
			}
		}
		mysql_free_result($cResult);
	}
	
print <<<EOF
	<tr><td></td><td><input type='button' value='$str_input_single' onclick='idwork(1)' class='pure-button'> <input type='button' value='aodc.php' onclick='idwork(3)' class='pure-button'></td></tr>
	<tr><td></td><td><font color='#666666'>$str_input_single_msg</font></td></tr>
	<tr><td></td><td>&nbsp;</td></tr>
	<tr><td class='TitleFont'>Date2</td><td><input type='number' name='dat2' value='$iDat2'> $str_input_start</td></tr>
	<tr><td></td><td><input type='button' value='$str_input_multi' onclick='idwork(2)' class='pure-button'></td></tr>
	<tr><td></td><td><font color='#666666'>$str_input_multi_msg</font></td></tr>
</form></table>

<script type='text/javascript'>
function idwork(opt) {
	if (opt == 3) {
		aodcwork();
		return;
	}
	if (opt == 1) inform.dat2.value = inform.date.value;
	inform.submit();
}
// single input
function getFVal(obj) {
	if ((obj == null) || (obj.value == null) || (obj.value == '')) return '';
	return obj.value;
}
function aodcwork() {
	var dataarr = {
		'site':inform.site.value,
		'date':inform.date.value,
		

EOF;
if ($iIsGps == 1) {
	echo ("		'LNG' :inform.lng.value,\n");
	echo ("		'LAT' :inform.lat.value,\n");
}
for ($i=0; $i<sizeof($aItems); $i++) {
	echo ("		'$aItems[$i]':getFVal(inform.$aItems[$i]),\n");
}
print <<<EOF
	};
	
	var request = $.ajax({
		url:'http://192.168.0.124:8080/iot/setStinkData',
		type:'POST',
		contentType: "application/x-www-form-urlencoded; charset=utf-8",
		data:dataarr,
		cache: false,
		error: function(xhr, status, msg) { MsgPopupError ('Error: ' + msg, 2000); },
		success : function(msg) {
			if (msg == '') MsgPopupOnly ('Result: OK', 1000);
			else MsgPopupError ('Result: ' + msg, 5000);
		}
	});
	
	/*
	var request = $.ajax({
		url:'aodc.php', type:'post', data:dataarr, cache: false,
		error: function(xhr, status, msg) { MsgPopupError ('Error: ' + msg, 2000); },
		success : function(msg) {
			if (msg == '') MsgPopupOnly ('Result: OK', 1000);
			else MsgPopupError ('Result: ' + msg, 5000);
		}
	});
	*/
}
inform.site.focus();
</script>

EOF;

} else // 자료 입력



// 측정 항목 관리 //////////////////////////////////////////////////////////////

if ($job == 'it') {
	echo ("<h1>$str_item</h1>\n");
	
	// save Post data.
	for ($i=0; isset($_POST["item{$i}0"]); $i++) {
		$id = $_POST["item{$i}0"];
		if (in_array($id, $aDbItems)) {
			$name   = $_POST["item{$i}1"];
			$unit   = $_POST["item{$i}2"];
			$dec    = $_POST["item{$i}3"];
			$lo     = $_POST["item{$i}4"];
			$hi     = $_POST["item{$i}5"];
			$bottom = $_POST["item{$i}6"];
			$top    = $_POST["item{$i}7"];
			$remark = $_POST["item{$i}8"];
			$using  = $_POST["item{$i}9"];
			
			if ((!isset($dec)) || (!is_numeric($dec))) $dec = 2;
			if ((!isset($lo)) || (!is_numeric($lo))) $lo = '';
			if ((!isset($hi)) || (!is_numeric($hi))) $hi = '';
			if ((!isset($bottom)) || (!is_numeric($bottom))) $bottom = '';
			if ((!isset($top)) || (!is_numeric($top))) $top = '';
			
			$sQuery = "UPDATE `items` SET `NAME`='$name', `UNIT`='$unit', `DEC`='$dec'";
			$sQuery .= ", `LO`=" . (($lo != '') ? "'$lo'" : "NULL");
			$sQuery .= ", `HI`=" . (($hi != '') ? "'$hi'" : "NULL");
			$sQuery .= ", `BOTTOM`=" . (($bottom != '') ? "'$bottom'" : "NULL");
			$sQuery .= ", `TOP`=" . (($top != '') ? "'$top'" : "NULL");
			$sQuery .= ", `REMARK`='$remark', `ORDER`='$i', `USING`='$using' WHERE `ID`='$id'";
			mysql_query($sQuery, $cDb);
		}
	}
	if (0 < $i) echo ("<h4><font color=#4444FF>$str_save_ok</font></h4>\n");

print <<<EOF
<div id='movebox' style='position:absolute; display:none; z-index:1; cursor:move;'></div>
<table><form name='itform' method='post' action='pAdmin.php?j=it'>
	<tr>
		<td class='TitleFont'>&nbsp; &nbsp;$str_item_id</td>
		<td class='TitleFont'>$str_item_name</td>
		<td class='TitleFont'>$str_item_unit</td>
		<td class='TitleFont'>$str_item_dec</td>
		<td class='TitleFont'>$str_item_lo</td>
		<td class='TitleFont'>$str_item_hi</td>
		<td class='TitleFont'>$str_item_bottom</td>
		<td class='TitleFont'>$str_item_top</td>
		<td class='TitleFont'>$str_item_comment</td>
		<td class='TitleFont'>$str_item_using</td>
	</tr>

EOF;
	// Get all item info.
	$sQuery = "SELECT `ID`,`NAME`,`UNIT`,`DEC`,`LO`,`HI`,`BOTTOM`,`TOP`,`REMARK`,`USING` FROM `items` ORDER BY `ORDER`";
	$cResult = mysql_query($sQuery, $cDb);
	if ($cResult != null) {
		$iItemNo = 0;
		while ($aRow = mysql_fetch_row($cResult)) {
			if (in_array($aRow[0], $aDbItems)) {
				echo ("	<tr>\n");
				echo ("	<td><div id='item{$iItemNo}' onmousedown='fOnMouseDown($iItemNo)' style='cursor:move'><b><font color='#AAAAAA'>::</font></b> <input type='text' name='item{$iItemNo}0' size='5' value='$aRow[0]' readonly style='cursor:move'></div></td>\n");
				for ($j=1; $j<=2; $j++) echo ("	<td><input type='text' name='item{$iItemNo}{$j}' size='".(($j==1)?"":"6")."' value='$aRow[$j]'></td>\n");
				echo ("	<td><select name='item{$iItemNo}3'>");
					echo ("<option value='0'".(($aRow[3]=='0')?' selected':'').">$str_item_type[0]</option>");
					echo ("<option value='1'".(($aRow[3]=='1')?' selected':'').">$str_item_type[1]</option>");
					echo ("<option value='2'".(($aRow[3]=='2')?' selected':'').">$str_item_type[2]</option>");
					echo ("<option value='3'".(($aRow[3]=='3')?' selected':'').">$str_item_type[3]</option>");
					echo ("<option value='12'".(($aRow[3]=='12')?' selected':'').">$str_item_type[4]</option>");
					echo ("<option value='10'".(($aRow[3]=='10')?' selected':'').">$str_item_type[5]</option>");
					echo ("<option value='11'".(($aRow[3]=='11')?' selected':'').">$str_item_type[6]</option>");
					echo ("</select></td>\n");
				for ($j=4; $j<8; $j++) echo ("	<td><input type='number' name='item{$iItemNo}{$j}' value='$aRow[$j]' style='width:50px;'></td>\n");
				echo ("	<td><input type='text' name='item{$iItemNo}8' value='$aRow[8]'></td>\n");
				echo ("	<td><select name='item{$iItemNo}9'>");
					echo ("<option value='1'".(($aRow[9]=='1')?' selected':'').">$str_item_important</option>");
					echo ("<option value='2'".(($aRow[9]=='2')?' selected':'').">$str_item_realtime</option>");
					echo ("<option value='3'".(($aRow[9]=='3')?' selected':'').">$str_item_used</option>");
					echo ("<option value='0'".(($aRow[9]=='0')?' selected':'').">$str_item_notused</option>");
					echo ("</select></td>\n");
				echo ("	</tr>\n");
				$iItemNo++;
			}
		}
		mysql_free_result($cResult);
	}

print <<<EOF
	<tr><td></td><td colspan='8'><input type='button' value='$str_save' class='pure-button' onclick='checkName()'></td></tr>
	<tr><td></td><td colspan='8'><font color='#666666'>$str_item_msg</font></td></tr>
</form></table>

<script type='text/javascript'>
function checkName () {
	var i;
	var name = {};
	for (i=0; i<100; i++) {
		try {
			var na = eval('itform.item'+i+'1.value').trim();
			if (na == '') {
				MsgPopupError ('$str_item_no_name', 2000);
				eval('itform.item'+i+'1').focus();
				return;
			}
			if (name[na]) {
				MsgPopupError ('$str_item_dup_name ['+na+']', 2000);
				eval('itform.item'+i+'1').focus();
				return;
			}
			name[na] = true;
		} catch (err) {
			break;
		}
	}
	itform.submit();
}

function getPosition(el,n) {
	var xPos = 0;
	var yPos = 0;
	while (el && (0 < n)) {
		xPos += (el.offsetLeft - el.scrollLeft + el.clientLeft);
		yPos += (el.offsetTop - el.scrollTop + el.clientTop);
		el = el.offsetParent;
		n--;
	}
	return {x:xPos,y:yPos};
}

var dragNo=-1, findNo, dragX, dragY;
window.document.onmousemove = fOnMouseMove;
window.document.onmouseup = fOnMouseUp;
function fOnMouseDown(idx) {
	dragNo = idx;
	var xy = getPosition(document.getElementById('item'+dragNo),3);
	var e = event || window.event;
	dragX = e.clientX - xy.x;
	dragY = e.clientY - xy.y;
	var obj = $('#movebox');
	obj.html ("<b><font color='#AAAAAA'>::</font></b> <input type='text' size='5' value='" +eval('itform.item'+dragNo+'0.value')+ "' style='cursor:move'>");
	obj.css({left:xy.x, top:xy.y});
	obj.show();
	$('#item'+dragNo).css({opacity:0.3});
	return false;
}
function fOnMouseMove() {
	if (dragNo < 0) return;
	
	var e = event || window.event;
	$('#movebox').css({left:e.clientX - dragX, top:e.clientY - dragY});
	return false;
}
function fOnMouseUp() {
	if (dragNo < 0) return;
	
	$('#item'+dragNo).css({opacity:1.0});
	
	// Find the closest item.
	findNo = -1;
	var e = event || window.event;
	var y = e.clientY - dragY;
	for (var i=0; i<$iItemNo; i++) {
		var itemobj = document.getElementById('item'+i);
		var xy = getPosition(itemobj,3);
		if (xy.y < y) findNo = i;
	}
	
	// change item data
	if (findNo+1 < dragNo) {
		for (var i=dragNo; findNo+1<i; i--) ChangeObj (i-1, i);
		findNo++;
	} else
	if (dragNo < findNo) {
		for (var i=dragNo; i<findNo; i++) ChangeObj (i, i+1);
	} else {
		findNo = dragNo;
	}
	
	// final animation
	var xy = getPosition(document.getElementById('item'+findNo),3);
	$('#movebox').animate({left:xy.x, top:xy.y},fFinal);
	$('#item'+findNo).css({opacity:0.3});
	dragNo = -1;
	return false;
}
function fFinal() {
	$('#item'+findNo).css({opacity:1.0});
	$('#movebox').hide();
}

function ChangeObj(idx1,idx2) {
	for (var i=0; i<=9; i++) {
		var obj1 = eval('itform.item'+idx1+''+i);
		var obj2 = eval('itform.item'+idx2+''+i);
		if ((i == 3) || (i == 9)) {
			var temp = obj1.selectedIndex;
			obj1.selectedIndex = obj2.selectedIndex;
			obj2.selectedIndex = temp;
		} else {
			var temp = obj1.value;
			obj1.value = obj2.value;
			obj2.value = temp;
		}
	}
}
</script>

EOF;
} else // 측정 항목 관리



// 사용자 관리 ///////////////////////////////////////////////////////////////

if ($job == 'pw') {

require_once('rsa/RSA.php');
$RSAPrivatekey = GetSetting ($cDb, 'rsakey', '');

echo ("<h1>$str_user</h1>\n");

$ac = $_POST['ac'];
$id = $_POST['id'];
$pw = $_POST['pw'];
$na = $_POST['na'];
if (isset($ac) && isset($id) && ($id != '')) {
	if ($pw != '') {
		if ($RSAPrivatekey != '') {
			$pw = @RSADecryption($RSAPrivatekey, $pw);
			$sid = session_id();
			$sidlen = strlen($sid);
			if (substr ($pw, 0, $sidlen) == $sid) $pw = substr($pw, $sidlen);
			else $pw = '';
		}
	}
	
	$id = MakeSafeString($id);
	$pw = MakeSafeString($pw);
	$na = MakeSafeString($na);
	if ($ac =='ed') {
		if ($pw == '') $sQuery = "UPDATE `member` SET `NAME`='$na', `ECNT`='0', `ETIME`='0' WHERE `ID`='$id'";
		else $sQuery = "UPDATE `member` SET `PW`=password('$pw'), `NAME`='$na', `ECNT`='0', `ETIME`='0' WHERE `ID`='$id'";
		mysql_query($sQuery, $cDb) or die ('EC#730104');
		echo ("<h4><font color=#4444FF>ID: $id $str_save_ok</font></h4>\n");
	} else
	if ($ac == 're') {
		if ($id != 'admin') {
			$sQuery = "DELETE FROM `member` WHERE `ID`='$id'";
			mysql_query($sQuery, $cDb) or die ('EC#730103');
			echo ("<h4><font color=#4444FF>ID: $id $str_delete_ok</font></h4>\n");
		}
	} else
	if ($ac == 'ad') {
		$sQuery = "INSERT INTO `member` VALUES ('$id', password('$pw'), '$na', '0', '0')";
		if (mysql_query($sQuery, $cDb)) echo ("<h4><font color=#4444FF>ID: $id $str_site_add_ok</font></h4>\n");
		else echo ("<h4><font color=#FF4444>ID: $id $str_site_add_fail</font></h4>\n");
	}
}

echo ("<script type='text/javascript'>\n");
if ($RSAPrivatekey != '') echo ("var publickey = '".RSAGetPublicKey($RSAPrivatekey)."';\n");

print <<<EOF

function UserEdit(idx) {
	userform.ac.value = 'ed';
	userform.id.value = eval('idform.i'+idx+'.value');
	userform.pw.value = eval('idform.p'+idx+'.value');
	userform.na.value = eval('idform.n'+idx+'.value');

EOF;
	if ($RSAPrivatekey != '') {
		echo ("	userform.pw.value = RSAEncryption(publickey, GetCookie('PHPSESSID','')+userform.pw.value);\n");
	}
print <<<EOF
	userform.submit();
}
function UserRemove(idx) {
	msg = "<p>$str_delete_confirm</p>";
	msg += "<input type='button' value='$str_cancel' onclick='MsgPopupHide()' class='pure-button'> ";
	msg += "<input type='button' value='$str_delete' onclick='UserRemoveConfirm("+idx+")' class='pure-button'>";
	MsgPopupShow (msg, 60*60*1000);
}
function UserRemoveConfirm(idx) {
	userform.ac.value = 're';
	userform.id.value = eval('idform.i'+idx+'.value');
	userform.submit();
}
function UserAdd() {
	userform.ac.value = 'ad';
	userform.id.value = idform.i.value;
	userform.pw.value = idform.p.value;
	userform.na.value = idform.n.value;
	if (userform.id.value == '') {
		MsgPopupError ('$str_id_error', 2000);
		idform.i.focus();
		return;
	}
	if (userform.pw.value == '') {
		MsgPopupError ('$str_pw_error', 2000);
		idform.p.focus();
		return;
	}

EOF;
	// RSA
	if ($RSAPrivatekey != '') {
		echo ("	userform.pw.value = RSAEncryption(publickey, GetCookie('PHPSESSID','')+userform.pw.value);\n");
	}
print <<<EOF
	userform.submit();
}
</script>

<table>
<form name='userform' method='post' action='pAdmin.php?j=pw'>
<input type='hidden' name='ac'>
<input type='hidden' name='id'>
<input type='hidden' name='pw'>
<input type='hidden' name='na'>
</form>
<form name='idform'>
<tr><td class='TitleFont'>$str_id</td><td class='TitleFont'>$str_pw</td><td class='TitleFont'>$str_name</td></tr>
	
EOF;
	
	// get all users.
	$lockno = 0;
	$sQuery = "SELECT `ID`,`NAME`,`ECNT`,`ETIME` FROM `member` ORDER BY `ID`";
	$cResult = mysql_query($sQuery, $cDb);
	if ($cResult != null) {
		$now = strtotime("now");
		$iItemNo = 0;
		for ($i=0; $aRow = mysql_fetch_row($cResult); $i++) {
			echo ("<tr>");
			echo ("<td><input type='text' name='i{$i}' size='16' value='$aRow[0]' readonly></td>");
			echo ("<td><input type='text' name='p{$i}i' size='16' autocomplete=off ontouch='pwKeyCursor(this)' onclick='pwKeyCursor(this)' onselect='pwKeyCursor(this)' onkeydown='pwKeyDown()' onkeypress='pwKeyPress(this)' onblur='pwKeyUp(this)' onkeyup='pwKeyUp(this)' style='ime-mode:disabled'><input type='hidden' name='p{$i}'></td>");
			echo ("<td><input type='text' name='n{$i}' size='16' value='$aRow[1]'></td>");
			echo ("<td><input type='button' value='$str_save' onclick='UserEdit($i)' class='pure-button'>");
			if ($aRow[0] != 'admin') echo (" <input type='button' value='$str_delete' onclick='UserRemove($i)' class='pure-button'>");
			echo ("</td></tr>\n");
			if ($now < $aRow[3]) {
				$locktime = $aRow[3] - $now;
				echo ("<tr><td colspan='4'><font color=#C00000>");
				echo (" * $aRow[0] $str_login_locked $str_user_ecnt {$aRow[2]}. $str_login_locktime ".((int)($locktime/60)).":".($locktime%60));
				echo ("</font></td></tr>\n");
				$lockno++;
			}
		}
		mysql_free_result($cResult);
	}
	if (0 < $lockno) echo ("<tr><td colspan='4'><font color='#666666'>$str_user_lockmsg</font></td></tr>\n");
	
print <<<EOF
<tr><td>&nbsp;</td></tr>
<tr><td class='TitleFont'>$str_id</td><td class='TitleFont'>$str_pw</td><td class='TitleFont'>$str_name</td></tr>
<tr><td><input type='text' name='i' size='16' value=''></td>
<td><input type='text' name='pi' size='16' autocomplete=off ontouch='pwKeyCursor(this)' onclick='pwKeyCursor(this)' onselect='pwKeyCursor(this)' onkeydown='pwKeyDown()' onkeypress='pwKeyPress(this)' onblur='pwKeyUp(this)' onkeyup='pwKeyUp(this)' style='ime-mode:disabled'><input type='hidden' name='p'></td>
<td><input type='text' name='n' size='16'></td>
<td><input type='button' value='$str_user_add' onclick='UserAdd()' class='pure-button'></td>
</tr></form></table>

EOF;

} else // 사용자 관리




// 악취측정지점 관리 ///////////////////////////////////////////////////////////

if ($job == 'st') {

$sMapType = GetSetting ($cDb, 'maptype', $sMapType);

print <<<EOF

<style type='text/css'>
.sitePopup {
	position:absolute;
	border-left:1px solid #888888;
	border-right:3px solid #888888;
	border-top:1px solid #888888;
	border-bottom:3px solid #888888;
	background:#FFFFFF;
	padding:10px;
	z-index:2;
	display:none;
	border-radius:10px;
}
</style>

<h1>$str_site</h1>

<div id='sitelist' style='position:fixed; top:250px; left:10px; width:140px; bottom:10px; background:#EEEEEE; padding:10px; overflow:auto; border-radius:10px;'></div>
<div id='mapbox' style='position:fixed; top:50px; bottom:10px; left:180px; right:10px; overflow:hidden; border-radius:10px;'>
<div id='map'    style='position:absolute; left:0; right:0; top:0; bottom:0; width:100%; height:100%; overflow:hidden;'></div>
</div>
<div id='sitePopup' class='sitePopup'>
	<table><form name='stform'>
		<tr><td colspan='3' id='siteformtitle'></td></tr>
		<tr><td>$str_site_no</td><td colspan='2'><input type='number' name='siteno' size='10' value='' placeholder='1'></td></tr>
		<tr><td>$str_site_name</td><td colspan='2'><input type='text' name='name' size='10' value=''></td></tr>
		<tr><td>$str_site_addr</td><td colspan='2'><input type='text' name='addr' size='20' value=''></td></tr>
		<tr><td>$str_site_remark</td><td colspan='2'><input type='text' name='remark' size='20' value=''></td></tr>
		<tr><td>$str_item_using</td><td colspan='2'><select name='file'><option value=''>$str_item_used</option><option value='1'>$str_item_notused</option></select></td></tr>
		<tr><td></td><td>
			<input type='button' value='$str_save' onclick='SiteEdit()' class='pure-button'>
			<input type='button' value='$str_cancel' onclick='SiteCancel()' class='pure-button'>
		</td><td align='right'>
			<input type='button' value='$str_delete' onclick='SiteDelete()' name='siteformdel' class='pure-button'>
		</td></tr>
	</form></table>
</div>

<script type='text/javascript'>

var map;
var markers = [];
var popups = [];
var map_popups_no = 0;
var container = document.getElementById('map');

// Marker mouse events
function makeMarkerClickListener (idx) {
	return function() {
		MapInfowindowClose ();
		clickLatLng = MapMarkerPosition (idx);
		SitePopup (idx);
	};
}

function makeMarkerOverListener(idx) {
	return function() {
		ShowSitePopup (idx);
	};
}
function makeMarkerOutListener (idx) {
	return function() {
		MapInfowindowClose ();
	};
}
function makeMarkerDragStart(idx) {
	return function() {
		clickIdx = idx;
		MapInfowindowClose ();
	}
}
function makeMarkerDragEnd(idx) {
	return function() {
//		MapInfowindowClose ();
		clickLatLng = MapMarkerPosition (idx);
		SitePopup (idx);
/*	
	
		ShowSitePopup (idx);
		var latlng = MapMarkerPosition (idx);
		sitearr[idx].lat = latlng.lat;
		sitearr[idx].lng = latlng.lng;
		clickIdx = idx;
		SiteSave ();*/
	};
}

// Map click event
function ActionMapClick() {
	return function(mouseEvent) {
		// close site info popup
		MapInfowindowClose ();
		
		// close market edit popup
		if (-1 <= clickIdx) {
			SiteCancel ();
			return;
		}
		clickLatLng = MapGetLatLng (mouseEvent);
		SitePopup (-1);
	};
}

</script>

EOF;

if ((!isset($sMapType)) || ($sMapType == NULL) || ($sMapType == '')) {
	$sMapType = 'vworld';
}
require_once('openlayers.php');

echo ("<script type='text/javascript'>\n");

// get all site info.
$sQuery = "SELECT `SITENO`,`NAME`,`LNG`,`LAT`,`ADDR`,`FILE`,`REMARK` FROM `sites` ORDER BY `NAME`";
$cResult = mysql_query($sQuery, $cDb);
echo ("var sitearr = [];");
if ($cResult != null) {
	for ($i=0; $aRow = mysql_fetch_row($cResult); $i++) {
		echo ("sitearr[$i] = {'siteno':'$aRow[0]', 'name':'$aRow[1]', 'lng':'$aRow[2]', 'lat':'$aRow[3]', 'addr':'$aRow[4]', 'file':'$aRow[5]', 'remark':'$aRow[6]'};\n");
	}
	mysql_free_result($cResult);
}

print <<<EOF

var clickLatLng;		// click location for add
var clickIdx = -2;		// -2:N/A, -1:new, 0<=site no

function SiteEdit () {
	if (clickIdx == -2) return;
	
	if ((stform.siteno.value == '') || !(0 < stform.siteno.value && stform.siteno.value < 10000)) {
		stform.siteno.focus();
		MsgPopupError ('$str_site_nomsg', 2000);
		return;
	}
	if (stform.name.value == '') {
		stform.name.focus();
		MsgPopupError ('$str_site_namemsg', 2000);
		return;
	}
	
	// check duplicate site number
	if (clickIdx == -1) {
		for (var i=0; i<markers.length; i++) {
			if ((markers[i].getMap() != null) && (sitearr[i].siteno == stform.siteno.value)) {
				stform.siteno.focus();
				MsgPopupError ("$str_site_noerr ["+sitearr[i].name+"]", 2000);
				return;
			}
		}
	}
	
	var idx = (clickIdx == -1) ? markers.length : clickIdx;
	sitearr[idx] = {
		'siteno':stform.siteno.value,
		'name':stform.name.value,
		'lng':clickLatLng.lng,
		'lat':clickLatLng.lat,
		'addr':stform.addr.value,
		'file':stform.file.value,
		'remark':stform.remark.value
	};
	
	SiteSave ();
}

function SiteSave () {
	MsgPopupOnly ('$str_save_ing', 0);
	
	var idx = (clickIdx == -1) ? markers.length : clickIdx;
	var dataarr = {
		'siteno':sitearr[idx].siteno,
		'name':sitearr[idx].name,
		'lng' :sitearr[idx].lng,
		'lat' :sitearr[idx].lat,
		'addr':sitearr[idx].addr,
		'file':sitearr[idx].file,
		'rema':sitearr[idx].remark
	};
	var request = $.ajax({
		url:'pAdmin.php?q=ss',
		type:'post',
		data:dataarr,
		cache: false,
		error: function(xhr, status, msg) {
			MsgPopupError ('$str_comm_error', 2000);
			SiteCancel ();
		},
		success : function(msg) {
			if (msg == 'OK') {
				MsgPopupOnly ('$str_save_ok', 1000);
				if (clickIdx == -1) MapMarkerAdd (clickLatLng.lat, clickLatLng.lng, -1);
				UpdateSubMenu ();
			} else {
				MsgPopupError ('$str_save_error:'+msg, 2000);
			}
			SiteCancel ();
		}
	});

}

function SiteDeleteConfirm () {
	if (clickIdx < 0) return;
	
	MsgPopupOnly ('$str_delete_ing', 0);
	
	var dataarr = {
		'siteno':sitearr[clickIdx].siteno
	};
	var request = $.ajax({
		url:'pAdmin.php?q=sd',
		type:'post',
		data:dataarr,
		cache: false,
		error: function(xhr, status, msg) {
			MsgPopupError ('$str_comm_error', 2000);
			SiteCancel ();
		},
		success : function(msg) {
			if (msg == 'OK') {
				MsgPopupOnly ('$str_delete_ok', 1000);
				MapMarkerRemove (clickIdx);
				UpdateSubMenu ();
			} else {
				MsgPopupError ('$str_delete_fail:'+msg, 2000);
			}
			SiteCancel ();
		}
	});
}
function SiteDelete () {
	if (clickIdx < 0) return;
	
	msg = "<p>$str_delete_confirm</p>";
	msg += "<input type='button' value='$str_cancel' onclick='MsgPopupHide()' class='pure-button'> ";
	msg += "<input type='button' value='$str_delete' onclick='SiteDeleteConfirm()' class='pure-button'>";
	MsgPopupShow (msg, 60*60*1000);
}

function SiteCancel () {
	if (0 <= clickIdx) {
		MapMarkerMove (clickIdx, sitearr[clickIdx].lat, sitearr[clickIdx].lng);
	}
	clickIdx = -2;
	$('#sitePopup').hide();
}

// edit Marker(site)
function SitePopup (idx) {
	clickIdx = idx;
	if (0 <= idx) {
		stform.siteno.value = sitearr[idx].siteno;
		stform.name.value = sitearr[idx].name;
		stform.addr.value = sitearr[idx].addr;
		stform.file.value = sitearr[idx].file;
		stform.remark.value = sitearr[idx].remark;
		$("[name='siteformdel']").show();
		siteformtitle.innerHTML = '<b>$str_site_edit</b>';
	} else {
		stform.siteno.value = '';
		stform.name.value = '';
		stform.addr.value = '';
		stform.file.value = '';
		stform.remark.value = '';
		$("[name='siteformdel']").hide();
		siteformtitle.innerHTML = '<b>$str_site_add</b>';
	}
	var e = event || window.event;
	$('#sitePopup').css({left:e.clientX-310, top:e.clientY-90});
	$('#sitePopup').show();
	if (0 <= idx) {
		stform.siteno.readOnly = true;
		stform.name.focus();
	} else {
		stform.siteno.readOnly = false;
		stform.siteno.focus();
	}
}

function ShowSitePopup (idx) {
	msg = '<table><tr><td>$str_site_no : '+sitearr[idx].siteno+'<br>$str_site_name : '+sitearr[idx].name+'</td></tr></table>';
	prevInfowindowIdx = idx;
	MapInfowindowShow (idx, msg);
}
function SelectSite(idx) {
	SiteCancel ();
	MapInfowindowClose ();
	if (idx == 0) {
		MapMarkerShowAll (0);
	} else {
		idx--;
		MapMarkerCenter (idx);
		ShowSitePopup (idx);
	}
}

function ViewAllSites () {
	SelectSite(0);
}

function UpdateSubMenu () {
	var msg = "<table width='100%' border='0' cellpadding='6' cellspacing='0'>";
	msg += "<tr><td class='SubMenu' onclick='ViewAllSites()'>$str_view_all</td></tr>";
	for (var i=0; i<markers.length; i++) {
		if (markers[i].getMap() != null) {
			msg += "<tr><td class='SubMenu' onclick='SelectSite("+(i+1)+")'>"+sitearr[i].name+"</td></tr>";
		}
	}
	msg += "</table>";
	sitelist.innerHTML = msg;
	var h = $('#adminmenu').outerHeight();
	$('#sitelist').css({top:60+h});
}

// Register all site & Map region correction.
for (var i=0; i<sitearr.length; i++) {
	MapMarkerAdd (sitearr[i].lat, sitearr[i].lng, -1);
}
UpdateSubMenu ();
MapMarkerShowAll (0);

</script>

EOF;
} else // 악취측정지점 관리



// Tail ////////////////////////////////////////////////////////////////////////

{
	echo ("<h1>$str_admin</h1>\n");
	echo ("<p>$str_admin_msg</p>\n");
}

mysql_close ($cDb);
?>

</div>

<div class='TopCurtain'></div>
<div id='msgPopupBox' class='msgPopupBox' onclick='MsgPopupHide()'><div class='msgPopupInn'><div id='msgPopupTxt' class='msgPopupTxt'></div></div></div>

</body>

<script type='text/javascript'>
$(document).keydown(function(e) {
	if (e.keyCode == 27) MsgPopupHide ();
});
</script>

</html>
