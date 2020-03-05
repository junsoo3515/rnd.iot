<?php

require_once('common.php');

session_start();

$sId = $_SESSION[$sDbDb.'_id'];

// connect db //////////////////////////////////////////////////////////////////
$cDb = mysql_connect($sDbIp, $sDbId, $sDbPw);
if (!$cDb) {
	PrintHtmlHead ();
	LogMsg ('Can not connect db : '.mysql_error());
	echo("</head>\n<body style='cursor:default'>\n");
	echo("Can not connect DB. Check common.php.<br>\n");
	echo("</body>\n</html>\n");
	die();
}
mysql_query('set names utf8');
if (!mysql_select_db($sDbDb, $cDb)) {
	PrintHtmlHead ();
	echo("</head>\n<body style='cursor:default'>\n");
	$sIpOnly = explode(":", $sDbIp);
	if ($_SERVER['REMOTE_ADDR'] === $sIpOnly[0]) {
		echo("Can not found DB. <a href='createdb.php?org=pRealtime.php'>CREATE DB</a><br>\n");
	} else {
		LogMsg ('Can not select db : '.mysql_error());
		echo("EC#730009<br>\n");
	}
	echo("</body>\n</html>\n");
	mysql_close ($cDb);
	die();
}

$iIsGuest  = GetSettingInt ($cDb, 'isguest', 0, 0, 1);
$sMapType  = GetSetting    ($cDb, 'maptype', $sMapType);
$iRtUpdate = GetSettingInt ($cDb, 'rtupdate', 10, 1, 10000) * 1000;
$iRtOld    = GetSettingInt ($cDb, 'rtold',     1, 1, 10000) * 3600;
$iIsGps    = GetSettingInt ($cDb, 'isgps', 0, 0, 1);
$iVersion  = GetSettingInt ($cDb, 'version', 0, 0, 100);

if ($iVersion == 0) {
	// 20170108 Change DB Table Name
	mysql_query("RENAME TABLE `odor_item`    TO `items`", $cDb);
	mysql_query("RENAME TABLE `odor_site`    TO `sites`", $cDb);
	mysql_query("RENAME TABLE `odor_weather` TO `weathers`", $cDb);
	mysql_query("RENAME TABLE `odor_data`    TO `datas`", $cDb);
	mysql_query("RENAME TABLE `odor_last`    TO `lasts`", $cDb);
	$iVersion = 1;
}
if ($iVersion == 1) {
	// 20170703 Add Top/Bottom bound at items table
	mysql_query("ALTER TABLE `items` ADD `TOP`    double default NULL AFTER `HI`");
	mysql_query("ALTER TABLE `items` ADD `BOTTOM` double default NULL AFTER `HI`");
	$iVersion = 2;
	SetSetting ($cDb, 'version', $iVersion);
}


// Ajax.Get realtime values ////////////////////////////////////////////////////
if ($_GET['q'] == 'rt') {
	header("Content-Type: text/html; charset=UTF-8");
	
	if (($iIsGuest == 0) && (!isset($sId))) {
		mysql_close ($cDb);
		die('EC#730403');	// Error anonymous access
	}
	
	if (isset($sDbs)) {
		// *** Multi DB ***
		$sQuery = "";
		for ($dbi=0; $dbi < sizeof($sDbs); $dbi++) {
			$dbname = $sDbs[$dbi];
			if (0 < $dbi) $sQuery .= " UNION ";
			
			$sQuery .= "(SELECT concat('$dbname',`SITENO`),`DATE`,`LNG`,`LAT`,`WDATA`";
			$iItemNo = 5;
			for ($i=0; isset($_POST['id'.$i]); $i++) {
				$id = $_POST['id'.$i];
				if (in_array($id, $aDbItems)) {	// SQL text is check for safety!
					$sQuery .= ",`$id`";
					$iItemNo++;
				}
			}
			$sQuery .= " FROM `$dbname`.`lasts`)";
		}
	} else {
		// *** Single DB only ***
		$sQuery = "SELECT `SITENO`,`DATE`,`LNG`,`LAT`,`WDATA`";
		$iItemNo = 5;
		for ($i=0; isset($_POST['id'.$i]); $i++) {
			$id = $_POST['id'.$i];
			if (in_array($id, $aDbItems)) {	// SQL text is check for safety!
				$sQuery .= ",`$id`";
				$iItemNo++;
			}
		}
		$sQuery .= " FROM `lasts`";
	}
	
	$cResult = mysql_query($sQuery, $cDb);
	if ($cResult != null) {
		echo(date("Y/m/d H:i:s",time()));
		while ($aRow = mysql_fetch_row($cResult)) {
			echo (";$aRow[0];");
			if (($aRow[1] !== null) && ($aRow[1] != 0)) {
				echo (DateIntToStr($aRow[1]));
				$dt = time() - IntToJs ($aRow[1]);
				if ($iRtOld < $dt) echo (";1");		// too old data: n-hour
				else echo (";0");
			} else {
				echo (";0");
			}
			if ($iIsGps == 0) $aRow[2] = $aRow[3] = '';
			for ($i=2; $i<$iItemNo; $i++) {
				echo (';');
				if ($aRow[$i] !== null) echo ($aRow[$i]);
			}
		}
		mysql_free_result($cResult);
	} else {
		echo ('EC#730105');
	}
	mysql_close ($cDb);
	die();
}



// Ajax.Get path values ////////////////////////////////////////////////////////
if ($_GET['q'] == 'pa') {
	header("Content-Type: text/html; charset=UTF-8");
	
	if (($iIsGuest == 0) && (!isset($sId))) {
		mysql_close ($cDb);
		die('EC#730403');	// Error anonymous access
	}
	
	$iSelectSite = $_POST['id'];
	if (!isset($iSelectSite)) {
		mysql_close ($cDb);
		die ('EC#730105');
	}
	
	$sDate1 = $_POST['date'];
	if (!isset ($sDate1)) $sDate1 = date('Y-m-d');
	
	$iDate1 = $iDate2 = 0;
	if (strlen($sDate1)==10) {
		$iDate1 = DateStrToInt ($sDate1,  0,  0,  0);
		$iDate2 = DateStrToInt ($sDate1, 23, 59, 59);
	}
	
	$silen = strlen($iSelectSite);
	if (isset($sDbs) && (3 < $silen)) {
		// *** Multi DB ***
		$dbname = substr ($iSelectSite, 0, $silen-3);
		$iSelectSite = substr ($iSelectSite, $silen-3);
	} else {
		// *** Single DB only ***
		$dbname = $sDbDb;
	}
	
	$sQuery = "SELECT `DATE`,`LNG`,`LAT`,`WDATA`";
	$iItemNo = 4;
	for ($i=0; isset($_POST['id'.$i]); $i++) {
		$id = $_POST['id'.$i];
		if (in_array($id, $aDbItems)) {	// SQL text is check for safety!
			$sQuery .= ",`$id`";
			$iItemNo++;
		}
	}
	$sQuery .= " FROM `$dbname`.`datas` LEFT JOIN `$dbname`.`weathers`";
	$sQuery .= " ON (`datas`.`WDATE`=`weathers`.`WDATE`) and (`datas`.`WXY`=`weathers`.`WXY`)";
	$sQuery .= " WHERE (`SITENO`='$iSelectSite') and ($iDate1<=`DATE`) and (`DATE`<=$iDate2)";
	
	$cResult = mysql_query($sQuery, $cDb);
	if ($cResult != null) {
		echo(date("Y/m/d H:i:s",time()));
		while ($aRow = mysql_fetch_row($cResult)) {
			echo (";");
			if (($aRow[0] !== null) && ($aRow[0] != 0)) echo (DateIntToStr($aRow[0]));
			if ($iIsGps == 0) $aRow[1] = $aRow[2] = '';
			for ($i=1; $i<$iItemNo; $i++) {
				echo (';');
				if ($aRow[$i] !== null) echo ($aRow[$i]);
			}
		}
		mysql_free_result($cResult);
	} else {
		echo ('EC#730105');
	}
	mysql_close ($cDb);
	die();
}



// Logout //////////////////////////////////////////////////////////////////////
if ($_GET['logout'] == 1) {
	if (isset($sId)) {
		//session_regenerate_id(true);
		unset ($_SESSION[$sDbDb.'_id']);
		$sId = null;
	}
}

// Login check /////////////////////////////////////////////////////////////////
require_once('rsa/RSA.php');

// Calc lock time (1minute ~ 1hour)
function CalcDelayTime ($ecnt, $etime) {
	if (5 < $ecnt) $etime += 60*60;
	else if ($ecnt == 5) $etime += 60*15;
	else if ($ecnt == 4) $etime += 60*5;
	else if ($ecnt == 3) $etime += 60;
	return $etime;
}

$RSAPrivatekey = GetSetting ($cDb, 'rsakey', '');
$isLoginFail = $_GET['login'] == 1;
if ($isLoginFail) {
	$id = $_POST['id'];
	$pw = $_POST['pw'];
	
	// Block remote admin
	$iIsAdmin  = GetSettingInt ($cDb, 'isadmin', 1, 0, 1);
	$sIpOnly = explode(":", $sDbIp);
	if (($iIsAdmin==0) && ($id == 'admin') && ($_SERVER['REMOTE_ADDR'] !== $sIpOnly[0])) {
		$msgRemoteAdmin = $str_login_block;
		$id = '';
	}
	
	if (($id != '') && ($pw != '')) {
		// Get block status
		$sQuery = "SELECT `ECNT`, `ETIME` from `member` where `ID`='$id'";
		$cResult = mysql_query($sQuery, $cDb);
		if ($cResult != null) {
			if ($aRow = mysql_fetch_row($cResult)) {
				$ecnt = $aRow[0];
				$etime = $aRow[1];
			}
			mysql_free_result($cResult);
		}
		
		if (isset($ecnt)) {
			$now = strtotime("now");
			if ($etime <= $now) {
				// RSA
				if ($RSAPrivatekey != '') {
					$pw = @RSADecryption($RSAPrivatekey, $pw);
					$sid = session_id();
					$sidlen = strlen($sid);
					if (substr ($pw, 0, $sidlen) == $sid) $pw = substr($pw, $sidlen);
					else $pw = '';
				}
				
				// Password check
				$id = MakeSafeString ($id);
				$pw = MakeSafeString ($pw);
				$sQuery = "select count(*) from `member` where `ID`='$id' and `PW`=password('$pw')";
				$cResult = mysql_query($sQuery, $cDb);
				$rv = 0;
				if ($cResult != null) {
					$aRow = mysql_fetch_row($cResult);
					if (($aRow) && ($aRow[0] == 1)) {
						$_SESSION[$sDbDb.'_id'] = $id;
						$sId = $id;
						$isLoginFail = false;
						LogMsg ("login $id");
						$rv = 1;
					} else {
						$rv = 2;
					}
					mysql_free_result($cResult);
				}
				
				// Save login time
				if (0 < $rv) {
					if ($rv == 1) {
						// Login Success
						$sQuery = "UPDATE `member` SET `ECNT`='0',      `ETIME`='$now' WHERE `ID`='$id'";
					} else {
						$ecnt++;
						$etime = CalcDelayTime ($ecnt, $now);
						$sQuery = "UPDATE `member` SET `ECNT`=`ECNT`+1, `ETIME`='$etime' WHERE `ID`='$id'";
					}
					mysql_query($sQuery, $cDb) or die ('EC#730104');
				}
				
				// Move to pData (Single DB only)
				if (($rv == 1) && ($iIsGuest != 0) && (!isset($sDbs))) {
					echo ("<html>\n<head>\n<script type='text/javascript'>\n");
					if (!$ISM) echo ("try{window.parent.frames[0].fSetMenu(1,'$sId');}catch(err){}\n");
					echo ("location.href='pData.php';\n");
					echo ("</script>\n</head>\n<body>\ngg\n</body>\n</html>\n");
					mysql_close ($cDb);
					die ();
				}
			}
			
			// Account lockout
			if ($now < $etime) {
				$lockid = $id;
				$locktime = $etime - $now;
			}
		}
	}
}



////////////////////////////////////////////////////////////////////////////////
// Login form
////////////////////////////////////////////////////////////////////////////////

if (($isLoginFail && ($_GET['login'] == 1)) || (($iIsGuest == 0) && (!isset($sId)))) {
	if (($id == '') && ($pw == '')) $isLoginFail = false;
	
	PrintHtmlHead ();
	echo ("<link rel='stylesheet' href='js/pure.css' />\n");
	
	if (!$ISM) {
		echo ("<script type='text/javascript'>\n");
		echo ("try{window.parent.frames[0].fSetMenu(5,'$sId');}catch(err){}\n");
		echo ("</script>\n");
	}

print <<<EOF
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='rsa/rsa.js'></script>

<script type='text/javascript'>
function LoginCheck () {

EOF;
	// RSA
	if ($RSAPrivatekey != '') {
		echo ("var publickey = '".RSAGetPublicKey($RSAPrivatekey)."';\n");
		echo ("idform.pw.value = RSAEncryption(publickey, GetCookie('PHPSESSID','')+idform.pw.value);\n");
	}
print <<<EOF
	idform.pwi.value = '';
	return true;
}
</script>

</head>
<body style='overflow:hidden; cursor:default'>

EOF;

SetTailImage ();
if ($ISM) {
	SetMobileMenu ();
	SetMobileTitle ($str_title1);
}

print <<<EOF
<div style='display: table; position:fixed; margin:0; top:0; left:0; right:0; bottom:0; height:100%; width:100%;'>
<div style='display: table-cell; vertical-align: middle; text-align:center;'>

<h1><font color='#888888'>LOGIN</font></h1>

EOF;
	if (isset($msgRemoteAdmin)) echo ("<h4 id='lockmsg'><font color=#FF4444>$msgRemoteAdmin</font></h4>\n");
	else if (isset($lockid)) echo ("<h4 id='lockmsg'>&nbsp;</h4>\n");
	else if ($isLoginFail) echo ("<h4>$str_login_error</h4>\n");
	else echo ("<h4>$str_login_msg</h4>\n");

print <<<EOF
<table id='loginform' style='background:#EEEEEE; padding:10px; border-radius:10px;' align='center' border=0 cellpadding=1 cellspacing=2>
	<form name='idform' method='post' action='pRealtime.php?login=1' onsubmit='return LoginCheck()'>
	<tr>
		<td align='left'>$str_id</td>
		<td><input name='id' type='text' value="$sId" tabindex=1 style='height:15px; width:150px'></td>
		<td rowspan=2><input value='&nbsp;{$str_login}&nbsp;' type='submit' tabindex=3 style='height:46px;' class='pure-button'></td>
	</tr>
	<tr>
		<td align='left'>$str_pw</td>
EOF;
if ($ISM) {
	echo ("<td><input name='pw' type='password' tabindex=2 style='height:15px; width:150px;'><input type='hidden' name='pwi'></td></tr>\n");
} else {
	echo ("<td><input name='pwi' type='text' autocomplete=off ontouch='pwKeyCursor(this)' onclick='pwKeyCursor(this)' onselect='pwKeyCursor(this)' onkeydown='pwKeyDown()' onkeypress='pwKeyPress(this)' onblur='pwKeyUp(this)' onkeyup='pwKeyUp(this)' tabindex=2 style='height:15px; width:150px; ime-mode:disabled;'><input type='hidden' name='pw'></td></tr>\n");
}
print <<<EOF
	</tr>
	</form>
</table>
</div></div>

<script type='text/javascript'>
idform.id.focus();

function LockMsg () {
	var dt = etime - new Date().getTime();
	var msg = "";
	if (dt <= 0) {
		clearTimeout(dt);
		msg += "<font color=#4444FF>$lockid $str_login_unlock</font>";
	} else {
		dt += 999;
		msg += "<font color=#FF4444>$lockid $str_login_locked</font> <font color=#666666>$str_login_locktime ";
		msg += ((dt/60000)|0) + ":";
		
		var ds = ((dt/1000)|0)%60;
		if (ds < 10) ds = '0'+ds;
		
		msg += ds+"</font>";
	}
	document.getElementById('lockmsg').innerHTML = msg;
}

EOF;
	if (isset($lockid)) {
		echo ("var etime = new Date().getTime() + ($locktime * 1000);\n");
		echo ("LockMsg();\n");
		echo ("var iid = setInterval ('LockMsg()', 1000);\n");
	}
	
print <<<EOF

</script>
<div class='TopCurtain'></div>
<div id='msgPopupBox' class='msgPopupBox' onclick='MsgPopupHide()'><div class='msgPopupInn'><div id='msgPopupTxt' class='msgPopupTxt'></div></div></div>

</body>
</html>

EOF;
	mysql_close ($cDb);
	die();
}



////////////////////////////////////////////////////////////////////////////////
// Relatime
////////////////////////////////////////////////////////////////////////////////

PrintHtmlHead ();

echo ("<script type='text/javascript'>\n");
if (!$ISM) echo ("try{window.parent.frames[0].fSetMenu(0,'$sId');}catch(err){}\n");

// Get all site
echo ("var sitearr = [];\n");
echo ("var cctvarr = [];\n");

$aSiteId = array ();
$aSiteName = array ();
if (isset($sDbs)) {
	// *** Multi DB ***
	$sQuery = "";
	for ($dbi=0; $dbi < sizeof($sDbs); $dbi++) {
		$dbname = $sDbs[$dbi];
		if (0 < $dbi) $sQuery .= " UNION ";
		$sQuery .= "(SELECT concat('$dbname',`SITENO`),`NAME`,`LNG`,`LAT`,`ADDR`,`REMARK` FROM `$dbname`.`sites` WHERE `FILE` is NULL)";
	}
	$sQuery .= " ORDER BY `NAME`";
} else {
	// *** Single DB only ***
	// 180425 지점 정보 조회 시 WEATHER_FL 추가
	$sQuery = "SELECT `SITENO`,`NAME`,`LNG`,`LAT`,`ADDR`,`REMARK`, `WEATHER_FL` FROM `sites` WHERE `FILE` is NULL ORDER BY `NAME`";
}
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	for ($i=0; $aRow = mysql_fetch_row($cResult); $i++) {
		echo ("sitearr[$i] = {'id':'$aRow[0]', 'name':'$aRow[1]', 'lng':'$aRow[2]', 'lat':'$aRow[3]', 'addr':'$aRow[4]', 'remark':'$aRow[5]', 'weatherFl':'$aRow[6]', 'date':null, 'old':0, 'data':[], 'wdata':null};\n");
		$aSiteId[] = $aRow[0];
		$aSiteName[] = $aRow[1];
	}
	mysql_free_result($cResult);
}


if (isset($sDbs)) {
	// *** Multi DB ***
	$sQuery = "";
} else {
	// *** Single DB only ***
	$sQuery = "SELECT A.`SITENO`, A.`NAME`, B.`URL`, B.`CCTV_USE_FL`, B.`SENSOR_USE_FL` FROM (SELECT `SITENO`,`NAME`,`LNG`,`LAT`,`ADDR`,`REMARK` FROM `sites` WHERE `FILE` is NULL ORDER BY `NAME`) AS A LEFT JOIN (SELECT `CCTVNO`,`CCTVNM`,`URL`,`CCTV_USE_FL`, `SENSOR_USE_FL` FROM `cctv` WHERE `SENSOR_USE_FL` = 'Y'  ORDER BY `CCTVNM`) AS B ON A.`SITENO` = B.`CCTVNO`";
} 

$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	for ($i=0; $aRow = mysql_fetch_row($cResult); $i++) {
		echo ("cctvarr[$i] = {'cctvno':'$aRow[0]', 'cctvnm':'$aRow[1]', 'url':'$aRow[2]', 'cctv_use_fl':'$aRow[3]', 'sensor_use_fl':'$aRow[4]'};\n");
	}
	mysql_free_result($cResult);
}

// Get all item
// step1. Get last items.
$aItemIds = array();
if (isset($sDbs)) {
	// *** Multi DB ***
	for ($i=0; $i<sizeof($aDbItems); $i++) $aItemIds[] = $aDbItems[$i];
} else {
	// *** Single DB only ***
	$sQuery = "SELECT SUM(`$aDbItems[0]`)";
	for ($i=1; $i<sizeof($aDbItems); $i++) $sQuery .= ", SUM(`$aDbItems[$i]`)";
	$sQuery .= " FROM `lasts`";
	$cResult = mysql_query($sQuery, $cDb);
	if ($cResult != null) {
		$aRow = mysql_fetch_row($cResult);
		if ($aRow != null) {
			for ($i=0; $i<sizeof($aDbItems); $i++)
				if ($aRow[$i] !== null) $aItemIds[] = $aDbItems[$i];
		}
		mysql_free_result($cResult);
	}
}

// step2. Get item information
$aItemName  = array();
$aItemUsing = array();
echo ("var itemarr = [];\n");
$sQuery = "SELECT `ID`,`NAME`,`UNIT`,`DEC`,`LO`,`HI`,`BOTTOM`,`TOP`,`REMARK`,`USING` FROM `items`";
if ($sId != 'admin') $sQuery .= " WHERE `USING`!='0'";
$sQuery .= " ORDER BY `ORDER`";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	$i=0;
	while ($aRow = mysql_fetch_row($cResult)) {
		if (in_array($aRow[0], $aItemIds)) {
			for ($j=4; $j<8; $j++) {	// lo, hi, bottom, top
				if ($aRow[$j] === null) $aRow[$j] = 'null';
			}
			echo ("itemarr[$i] = {'id':'$aRow[0]', 'name':'$aRow[1]', 'unit':'$aRow[2]', 'dec':'$aRow[3]', 'lo':$aRow[4], 'hi':$aRow[5], 'bot':$aRow[6], 'top':$aRow[7], 'remark':'$aRow[8]', 'using':'$aRow[9]'};\n");
			$aItemName[]  = $aRow[1];
			$aItemUsing[] = $aRow[9];
			$i++;
		}
	}
	mysql_free_result($cResult);
}

echo ("</script>\n");

print <<<EOF

<style type='text/css'>
.popupdate {
	color:#888888;
	text-align:right;
}
.msgPopup {
	position:absolute;
	color:#FFFFFF;
	border-left:1px solid rgba(0,0,0,0.2);
	border-right:3px solid rgba(0,0,0,0.2);
	border-top:1px solid rgba(0,0,0,0.2);
	border-bottom:3px solid rgba(0,0,0,0.2);
	background-color:rgba(64,160,64,0.8);
	padding:10px;
	z-index:2;
	display:none;
	border-radius:10px;
}
</style>

<link rel='stylesheet' href='js/jquery.ui.min.css' />
<link rel='stylesheet' href='js/pure.css' />
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.ui.min.js'></script>
<script type='text/javascript'>

// resize window
window.document.onmousemove = fOnMouseMove;
window.document.onmouseup   = fOnMouseUp;
var resizeX1, resizeY1;		// 'rbody' left top
var resizeX2, resizeY2;		// 'rbody' right bottom
var resizeW,  resizeH;		// 'rbody' size
var resizeType = 0;			// 0:bottom, 1:right
var resizeRatio;			// ratio=info/map
var resizeSize;				// Object sizeof left or top
var resizeState = false;	// true:moving

// 1 day path info
var pathId = -1;			// -1:NA, 0<=id
var pathNo = 0;				// path number
var paths = null;			// path data [pathNo][4+itemarr.length]

var selectMarkerNo = -1;	// Selected site number. (-2:N/A, -1:All, 0<=:site)

var mIsShowMap = -1;
function ResizeWork() {

EOF;
	$pathW = ($ISM?120:150) + ($iTimeType==1?12:0);
	echo ("	var pathw = ".$pathW.";\n");	// path div width

print <<<EOF
	var pad = 10;		// div padding
	var pad2 = 5;		// padding / 2
	var isshowmap = 1;
	
	if (resizeType == 0) {
		// Vertival
		
		// check minimum height
		if (60+pad+60 <= resizeH) {
			if (resizeH <= resizeSize) resizeSize = resizeH;
			else if (resizeSize <= 0) resizeSize = 0;
			else if (resizeH-pad-60 < resizeSize) resizeSize = resizeH-pad-60;
			else if (resizeSize < 60) resizeSize = 60;
		} else {
			if (0 < resizeSize) resizeSize = resizeH;
		}
		
		// check path width
		var mapr = 0;
		if (0 <= pathId) {
			var pathl;
			if (resizeW < pathw*2+pad) {
				mapr = ((resizeW-pad)>>1) + pad;
				pathl = resizeX1 + ((resizeW-pad)>>1) + pad;
			} else {
				mapr = pad+pathw;
				pathl = resizeX2-pathw;
			}
			$('#pathhead').css({left:pathl, right:pad, top:resizeY1, height:35});
			$('#pathbody').css({left:pathl, right:pad, top:resizeY1+48, bottom:pad});
		}
		
		if (resizeSize == 0) {
			// Site only
			isshowmap = 0;
			$('#mapbox' ).css({left:pad+1, right:mapr+pad+1, top:resizeY1+1, bottom:pad+1});
			$('#rbar').css({left:pad, right:mapr+pad, top:resizeY1-pad, bottom:resizeH+pad});
			$('#site').css({left:pad, right:mapr+pad, top:resizeY1, bottom:pad});
		} else
		if (resizeSize == resizeH) {
			// Map only
			$('#mapbox' ).css({left:resizeX1, right:mapr+pad, top:resizeY1, bottom:pad});
			$('#rbar').css({left:resizeX1, right:mapr+pad, top:resizeY1+resizeH, bottom:0});
			$('#site').css({left:resizeX1, right:mapr+pad, top:resizeY1+resizeH+pad, bottom:0});
		} else {
			// Map + Site
			var mapw = resizeW-mapr;
			var maph = resizeSize;
			var sith = resizeH - maph - pad;
			var y1 = resizeY1;
			var y2 = resizeH + pad - maph;
			$('#mapbox' ).css({left:resizeX1, right:mapr+pad, top:y1, bottom:y2});
			y1 += maph;
			y2 -= pad;
			$('#rbar').css({left:resizeX1, right:mapr+pad, top:y1, bottom:y2});
			y1 += pad;
			y2 -= sith;
			$('#site').css({left:resizeX1, right:mapr+pad, top:y1, bottom:y2});
		}
	} else {
		// Horizontal
		if (0 <= pathId) {
			// with path
			var patw = pathw;
			if (60+pad+patw+pad+60 <= resizeW) {
				if (resizeW-pad-60 < resizeSize) resizeSize = resizeW-pad-60;
				if (resizeSize < 60+pad+patw) resizeSize = 60+pad+patw;
			} else
			if (60+pad+60+pad+60 <= resizeW) {
				resizeSize = resizeW - pad-60;
				patw = resizeSize - pad-60;
			} else {
				patw = (resizeW-pad-pad)/3;
				resizeSize = patw*2 + pad;
			}
			var mapw = resizeSize - patw - pad;
			var sitw = resizeW - mapw - pad - patw - pad;
			var x1 = resizeX1;
			var x2 = resizeW + pad - mapw;
			$('#mapbox' ).css({left:x1, right:x2, top:resizeY1, bottom:pad});
			x1 += mapw + pad;
			x2 -= patw + pad;
			$('#pathhead').css({left:x1, right:x2, top:resizeY1, height:35});
			$('#pathbody').css({left:x1, right:x2, top:resizeY1+48, bottom:pad});
			x1 += patw;
			x2 -= pad;
			$('#rbar').css({left:x1, right:x2, top:resizeY1, bottom:pad});
			x1 += pad;
			x2 -= sitw;
			$('#site').css({left:x1, right:x2, top:resizeY1, bottom:pad});
		} else {
			// without path
			if (60+pad+60 <= resizeW) {
				if (resizeW-pad-60 < resizeSize) resizeSize = resizeW-pad-60;
				if (resizeSize < 60) resizeSize = 60;
			} else {
				resizeSize = (resizeW-pad) >> 1;
			}
			var mapw = resizeSize;
			var sitw = resizeW - mapw - pad;
			var x1 = resizeX1;
			var x2 = resizeW + pad - mapw;
			$('#mapbox' ).css({left:x1, right:x2, top:resizeY1+40, bottom:pad});
			x1 += mapw;
			x2 -= pad;
			$('#rbar').css({left:x1, right:x2, top:resizeY1, bottom:pad});
			x1 += pad;
			x2 -= sitw;
			$('#site').css({left:x1, right:x2, top:resizeY1, bottom:pad});
		}
	}
	
	if ((ism == 0) && (mIsShowMap != isshowmap)) {
		mIsShowMap = isshowmap;
		if (mIsShowMap == 0) {
			$('#titlediv').css({left:pad});
			$('#itemdiv').hide();
		} else {
			$('#titlediv').css({left:resizeX1});
			$('#itemdiv').show();
		}
	}
}



// remove path markers & line
function RemovePath () {
	if ($iIsGps == 1) {
		MapMarkerResize (sitearr.length);
		MapPathClear ();
	}
}

function UpdatePathData () {
	if (sitearr.length <= selectMarkerNo) SelectMarker (-2);	// hide path popup
//	else if (0 <= selectMarkerNo) MapInfowindowClose ();		// hide popup (Avoid overlapping of marker and pop-up). delete 20170119
	RemovePath ();
	
	var preLat = sitearr[pathId].lat;
	var preLng = sitearr[pathId].lng;
	
	// make path table & marker & line
	var msg;
	if (0 < pathNo) {
		msg = "<table width='100%' border='0' cellpadding='2' cellspacing='0'>";
		for (var i=0; i<pathNo; i++) {
			msg += "<tr><td id='bSite"+(sitearr.length+i)+"' class='SubMenu' align='center' onclick='SelectMarker("+(sitearr.length+i)+",1)'>"+paths[i][0]+"</td></tr>";
			if ($iIsGps == 1) {
				var lat = paths[i][2];
				var lng = paths[i][1];
				if ((lat == null) || (lat == '') || (lat == 0) || (lng == null) || (lng == '') || (lng == 0)) {
					lat = preLat;
					lng = preLng;
				}
				MapMarkerAdd(lat, lng, 0);
				MapPathAdd(lat, lng);
				preLat = lat;
				preLng = lng;
			}
		}
		msg += "</table>";
	} else {
		msg = "<table width='100%' height='100%'><tr><td align='center'>$str_nodata</td></tr></table>";
	}
	$('#pathbody').html (msg);
	
	$('#date1').datepicker({changeYear:true, changeMonth:true, showMonthAfterYear:true, nextText:'', prevText:'', dayNamesMin:$str_calendar_day, monthNamesShort:$str_calendar_month, dateFormat:'yy-mm-dd'});	// date from
	if (pathNo < 1) return;
	
	// set path marker color
	UpdateMarkersColor (markers.length);
	
	// Draw lines hear
	if ($iIsGps == 1) {
		MapPathDraw ();
		MapMarkerShowAll (sitearr.length);
	}
//	if (0 <= selectMarkerNo) MapInfowindowShow (selectMarkerNo);	// delete 20170119
}

function GetPathData () {
	var dataarr = 'id='+sitearr[pathId].id+'&date='+paform.date1.value;
	for (var i=0; i<itemarr.length; i++) dataarr += '&id'+i+'='+itemarr[i].id;
	
	$.ajax ({
		url:"pRealtime.php?q=pa",
		type:"post",
		data:dataarr,
		cache: false,
		error: function(xhr, status, msg) {
		},
		success : function(msg) {
			if (msg == 'EC#730403') {
				// logout : Error anonymous access
				setTimeout ('document.location.replace("pRealtime.php?login=1")',2000);
				return;
			}
			
			var n = itemarr.length+4;
			msg = msg.split (";");
			if ((msg.length-1)%n != 0) {
				// Error
				HidePathWindow ();
				setTimeout ('document.location.replace("pRealtime.php")',2000);
				return;
			}
			
			var p = 1;
			paths = new Array(((msg.length-1)/n)|0);
			for (pathNo=0; p<msg.length; pathNo++) {
				paths[pathNo] = new Array(n);
				paths[pathNo][0] = msg[p++];
				for (var i=1; i<n; i++) {
					if (i != 3) {
						if (msg[p] != '') {
							var ival = parseFloat(msg[p]);
							paths[pathNo][i] = isNaN(ival) ? null : ival;
						} else {
							paths[pathNo][i] = null;
						}
					} else paths[pathNo][i] = (msg[p] != '') ? msg[p] : null;	// i==3:WDATA(weather string)
					p++;
				}
			}
			
			UpdatePathData ();
		}
	});
}

function ShowPathWindow (idx) {
	if (pathId == idx) {
		HidePathWindow ();
		return;
	}
	
	pathId = idx;
	ResizeWork ();

	var msg = "<b>"+sitearr[pathId].name+"</b><img src='img/close.png' width='16' height='16' border='0' align='right' onclick='HidePathWindow()' style='cursor:pointer'><br>";
	msg += "<form name='paform'><input type='text' id='date1' name='date1' value='";
	if ((sitearr[pathId].date != null) && (sitearr[pathId].date != '')) {
		msg += sitearr[pathId].date.substring(0,10);
	} else {
		var date = new Date();
		msg += date.toISOString().substring(0,10);
	}
	msg += "' size='10' readonly onchange='GetPathData()'></form>";
	$('#pathhead').html (msg);
	
	msg = "<table width='100%' height='100%'><tr><td align='center'>Wait...</td></tr></table>";
	$('#pathbody').html (msg);
	
	$('#pathhead').show();
	$('#pathbody').show();
	MapRelayout ();
	
	$('#date1').datepicker({changeYear:true, changeMonth:true, showMonthAfterYear:true, nextText:'', prevText:'', dayNamesMin:$str_calendar_day, monthNamesShort:$str_calendar_month, dateFormat:'yy-mm-dd'});	// date from
	
	GetPathData ();
}

function HidePathWindow () {
	if (sitearr.length <= selectMarkerNo) SelectMarker (-2);	// hide path popup
	RemovePath ();
	pathId = -1;
	$('#pathhead').html ('');
	$('#pathbody').html ('');
	$('#pathhead').hide();
	$('#pathbody').hide();
	ResizeWork ();
	MapRelayout ();
}



function GetBodySize () {
	var obj = $('#rbody');
	resizeW = obj.outerWidth();
	resizeH = obj.outerHeight();
	resizeX1 = obj.offset().left;
	resizeY1 = obj.offset().top;
	resizeX2 = resizeX1 + resizeW - 1;
	resizeY2 = resizeY1 + resizeH - 1;
}

function SetResizeCursor () {
	$('#rbar').css('cursor', (resizeType == 0) ? 'n-resize' : 'e-resize');
//	if (ism == 0) $('#rbar').css('background', (resizeType == 0) ? 'url(img/rbar_w.png)' : 'url(img/rbar_h.png)');
}

// <body> OnLoad
function fOnLoad() {
	// left item list : select 'View all'
	SelectItem (-2);
	
	// update realtime data
	UpdateRealtime();
	setInterval('UpdateRealtime()',$iRtUpdate);
	
	// Resize initialization
	GetBodySize ();
	
	var tmp = (ism == 0) ? GetCookie ('resizeType', -1) : -1;
	resizeType = parseInt(tmp);
	if (isNaN(resizeType)) resizeType = -1;
	if (resizeType == -1) resizeType = (resizeW < resizeH) ? 0 : 1;
	
	SetResizeCursor ();
	$('#time').css({top:(resizeY1-20)});
	tmp = (ism == 0) ? GetCookie ('resizeRatio', 0.667) : 0.667;
	resizeRatio = parseFloat(tmp);
	if (isNaN(resizeRatio)) resizeRatio = 0.667;
	resizeSize = parseInt(((resizeType==0)?resizeH:resizeW) * resizeRatio);
	ResizeWork();
	$('#mapbox' ).show();
	$('#rbar').show();
	$('#site').show();
	MapRelayout ();
	
	// bottom size list : select 'View all'
	SelectMarker (-1,1);
}

// <body> OnResize
function fOnResize() {
	GetBodySize ();
	var tmp = (ism == 0) ? GetCookie ('resizeType', -1) : -1;
	var oldResizeType = resizeType;
	if (parseInt(tmp) == -1) resizeType = (resizeW < resizeH) ? 0 : 1;
	if ((ism == 1) && (oldResizeType != 1) && (resizeType == 1)) resizeRatio = 0.667;
	resizeSize = parseInt(((resizeType==0)?resizeH:resizeW) * resizeRatio);
	ResizeWork();
	MapRelayout ();
	SetResizeCursor ();
}

function ResizeStart() {
	resizeState = true;
}

function fOnMouseMove() {
	if (!resizeState) return true;
	
	var e = event || window.event;
	var mx = e.clientX;
	var my = e.clientY;
	
	if (ism == 0) {
		var newtype = resizeType;
		if ((my < resizeY1+60) || (resizeY2-60 < my)) newtype = 0;
		else if (resizeX2-60 < mx) newtype = 1;
		if (resizeType != newtype) {
			resizeType = newtype;
			SetResizeCursor ();
			GetBodySize ();
		}
	}
	
	resizeSize = ((resizeType == 0) ? (my - resizeY1) : (mx - resizeX1)) - 5;	// pad size is 10. 5 = pad/2.
	ResizeWork();
	MapRelayout ();
	return false;
}

function fOnMouseUp() {
	if (resizeState) {
		var wh = (resizeType==0) ? resizeH : resizeW;
		resizeRatio = (1 < wh) ? (resizeSize / wh) : 0.5;
		SetCookie ('resizeType', resizeType, 10000);
		SetCookie ('resizeRatio', resizeRatio, 10000);
		resizeState = false;
	}
}

function ItemInfoShow(idx) {
	var obj = itemarr[idx];
	var msg = '';
	if ((obj.lo != null) && (obj.hi != null) && (obj.hi < obj.lo)) {
		msg += '<br>$str_item_warning:' + js_item_format(obj.hi, obj.dec, 0)+obj.unit + ', $str_item_danger:' + js_item_format(obj.lo, obj.dec, 0)+obj.unit;
	} else
	if ((obj.lo != null) || (obj.hi != null)) {
		msg += '<br>$str_report_reference : ';
		if (obj.lo != null) msg += js_item_format(obj.lo, obj.dec, 0)+obj.unit;
		msg += ' ~ ';
		if (obj.hi != null) msg += js_item_format(obj.hi, obj.dec, 0)+obj.unit;
	}
	if (obj.remark != '') {
		msg += '<br>'+obj.remark;
	}
	if (msg == '') return;
	msg = obj.name + msg;
	obj = $('#msgPopup');
	obj.css ('background-color','rgba(64,160,64,0.8)');
	obj.html (msg);
	obj.show ();
	ItemInfoMove ();
}
function ItemInfoMove() {
	var obj = $('#msgPopup');
	var w = obj.width() + 22;
	var h = obj.height() + 28;
	var e = event || window.event;
	if (e.clientY-h < 20) h = -14;
	var x = e.clientX - (w / 2);
	if (resizeX2 < x+w) x = resizeX2 - w;
	else if (x < 10) x = 10;
	obj.css({left:x, top:e.clientY-h});
}
function ItemInfoHide() {
	$('#msgPopup').hide ();
}

function SiteInfoShow(idx) {
	if (sitearr[idx].old == 0) return;
	
	var obj = $('#msgPopup');
	obj.css ('background-color','rgba(204,64,64,0.8)');
	obj.html ("$str_olddate : " + sitearr[idx].date);
	obj.show ();
	ItemInfoMove ();
}

</script>

</head>
<body onLoad='fOnLoad()' onResize='fOnResize()' style='overflow:hidden; cursor:default'>

EOF;
if ($ISM) {
	SetMobileMenu ();
	echo ("<div style='position:fixed; top:0px; left:0px; right:0px; padding-left:50px; padding-top:1px; z-index:1;' onclick='MenuClick()'><h2>$str_realtime</h2></div>\n");
	$iMapX = '10px';
	$iMapY = '45px';
} else {
	$iMapX = '180px';
	$iMapY = '50px';
	echo ("<div id='itemdiv' style='position:fixed; display:none; top:$iMapY; left:10px; width:142px; background:#EEEEEE; padding:6px; border-radius:10px; border:3px solid #FFBB88;'>\n");
	echo ("	<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n");
	echo ("	<tr><td class='OrangeFont'>$str_select_item</td></tr>\n");
	echo ("	<tr><td id='lItem-1' class='SubMenu' onclick='SelectItem(-1)'>$str_view_all</td></tr>\n");
	for ($i=0; $i<sizeof($aItemName); $i++) {
		if (($aItemUsing[$i] == '1') || ($aItemUsing[$i] == '2')) {
			echo ("<tr><td id='lItem{$i}' class='SubMenu' onclick='SelectItem({$i})'>{$aItemName[$i]}</td></tr>\n");
		}
	}
	echo ("	</table>\n");
	echo ("</div>\n");
	echo ("<div id='titlediv' style='position:fixed; top:0px; left:180px;'><h1>$str_realtime</h1></div>\n");
}

print <<<EOF
<div id='time'  style='position:fixed; right:10px; color:#888888'></div>
<div id='rbody' style='position:fixed; top:{$iMapY}; left:{$iMapX}; bottom:10px; right:10px; overflow:hidden;'></div>
<div id='mapheader'  style='position:fixed; overflow:auto;   border-radius:10px; background:#EEEEEE; padding:6px; left: 180px; right: 587px; top: 50px; bottom: 10px;' width='100%' height='10%'>
<table width='100%' border='0' cellpadding='2' cellspacing='0'>
<tr><td><input type='radio' name='mapSwitcher' value='normal' onClick='MapChanger("normal");' checked>$str_normal_map<input type='radio' name='mapSwitcher' onClick='MapChanger("satellite");' value='satellite'>$str_satellite_map</td></tr>
</table>
</div>
<div id='mapbox' style='position:fixed; display:none; overflow:hidden; border-radius:10px;'>
<div id='map'    style='position:absolute; left:0; right:0; top:0; bottom:0; width:100%; height:100%; overflow:hidden;'></div>
</div>
<div id='site'  style='position:fixed; display:none; overflow:auto;   border-radius:10px; background:#EEEEEE; padding:6px;'>
	<table width='100%' border='0' cellpadding='4px' cellspacing='0'>

EOF;
		echo ("<tr onclick='SelectMarker(-1,1)'><td id='bSite-1' class='SubMenu'>$str_view_all</td>\n");
		for ($i=0; $i<sizeof($aItemName); $i++) {
			if (($aItemUsing[$i] == '1') || ($aItemUsing[$i] == '2')) {
				echo ("<td class='OrangeFont' align='right' style='white-space:normal'><font onMouseOver='ItemInfoShow({$i})' onMouseMove='ItemInfoMove()' onMouseOut='ItemInfoHide()'>{$aItemName[$i]}</font></td>\n");
			}
		}
		echo ("</tr>\n");
		
		for ($i=0; $i<sizeof($aSiteId); $i++) {
			echo ("<tr onclick='SelectMarker({$i},1);'><td id='bSite{$i}' class='SubMenu'><font onMouseOver='SiteInfoShow({$i})' onMouseMove='ItemInfoMove()' onMouseOut='ItemInfoHide()'>{$aSiteName[$i]}</font></td>\n");
			for ($j=0; $j<sizeof($aItemName); $j++) {
				if (($aItemUsing[$j] == '1') || ($aItemUsing[$j] == '2')) {
					echo ("<td id='bItem{$aSiteId[$i]}_{$j}' align='right'></td>\n");
				}
			}
			echo ("</tr>\n");
		}

print <<<EOF
	</table>
</div>
<div id='pathhead' style='position:fixed; display:none; overflow:hidden; border-radius:10px 10px 0px 0px; background:#EEEEEE; padding:6px;'></div>
<div id='pathbody' style='position:fixed; display:none; overflow:auto;   border-radius:0px 0px 10px 10px; background:#EEEEEE; padding:6px;'></div>
<div id='rbar'  style='position:fixed; display:none; overflow:hidden;' onMouseDown='ResizeStart()'></div>

<div id='msgPopup' class='msgPopup'></div>

<script type='text/javascript'>

var map;
var markers = [];
var popups = [];
var map_popups_no = sitearr.length;
var container = document.getElementById('map');

function makeMarkerClickListener(idx) {
	return function() {
		if (idx < sitearr.length) {
			if (selectMarkerNo != idx) SelectMarker (idx);
			else SelectMarker (-2);
		} else
		if ($iIsGps == 0) {
			if (selectMarkerNo != pathId) SelectMarker (pathId);
			else SelectMarker (-2);
		} else
		if (selectMarkerNo != idx) {
			SelectMarker (idx);
			var viewY1 = $('#pathbody').scrollTop();
			var viewY2 = $('#pathbody').height() + viewY1;
			var elemY1  = $('#bSite'+idx).position().top + viewY1;
			var elemY2  = $('#bSite'+idx).height() + elemY1;
			if ((elemY1 < viewY1) || (viewY2 < elemY2)) {
				var y = elemY1 - ((viewY2 - viewY1)>>1);
				$('#pathbody').scrollTop(y);
			}
		} else {
			SelectMarker (-2);
		}
	};
}

/*function makeMarkerOverListener(idx) {		// remove 20170119
	return function() {
		if (idx < sitearr.length) {
			SelectMarker (idx);
		}
	};
}*/

/*function makeMarkerOutListener(idx) {			// remove 20170119
	return function() {
	}
}*/

function ActionMapClick() {
	return function(mouseEvent) {
		if (selectMarkerNo != -2) SelectMarker (-2);
		else SelectMarker (-1);
	};
}

var isZooming = false;
function ActionMapZoomStart() {
	return function() {
		isZooming = true;
	};
}
function ActionMapZoomChangned() {
	return function() {
		isZooming = false;
	};
}

</script>

EOF;

if ((!isset($sMapType)) || ($sMapType == NULL) || ($sMapType == '')) {
	$sMapType = 'vworld';
}
require_once('openlayers.php');

print <<<EOF
<script type='text/javascript'>


// 0:green, 1:gray, 2:orange, 3:red
function CheckMarkerColor (idx) {
	var rv = 0;
	var nacnt = 0;
	for (var i=0; i<itemarr.length; i++) {
		var obj = itemarr[i];
		if ((selectItemNo == -1) || (selectItemNo == i)) {
			var ival = (idx < sitearr.length) ? sitearr[idx].data[i] : paths[idx - sitearr.length][4+i];
			if (ival == null) nacnt++;
			else if ((obj.lo != null) && (obj.hi != null) && (obj.hi < obj.lo)) {
				if (obj.lo < ival) rv = 3;
				else if ((obj.hi < ival) && (rv < 2)) rv = 2;
			} else if (((obj.lo != null) && (ival < obj.lo)) || ((obj.hi != null) && (obj.hi < ival))) rv = 3;
		} else nacnt++;
	}
	if (nacnt == itemarr.length) rv = 1;
	
	// site & olddata -> Warning
	if ((idx < sitearr.length) && (sitearr[idx].old != 0)) rv = 1;
	
	return rv;
}

function UpdateMarkersColor (n) {
	for (var i=0; i<n; i++) {
		var rv = CheckMarkerColor (i);
		MapMarkerColor (i, rv);
	}
	MapMarkerDraw ();
}

// Show detail information
var selectItemNo = 0;	// Selected item number. (-1:All, 0<=:item)

// bar graph width
function CalcBarW (ival, hi, lo, mn_, mx_) {
	var mn, mx;
	if ((hi != null) && (lo != null)) {
		if (hi < lo) {
			if (0 < hi) mn = 0;
			else mn = hi - (lo-hi);
			mx = lo;
		} else {
			if (0 < lo) mn = 0;
			else mn = lo - (hi-lo);
			mx = hi;
		}
	} else
	if (hi != null) {
		if (0 < hi) mn = 0;
		else mn = hi*2;
		mx = hi;
	} else
	if (lo != null) {
		if (0 < lo) mn = 0;
		else mn = lo*2;
		mx = lo;
	} else {
		mn = 0;
		mx = 100;
	}
	if (mn_ !== null) mn = mn_;
	if (mx_ !== null) mx = mx_;
	
	ival -= mn;
	mx -= mn;
	if (ival < 0) ival = 0;
	if (mx < 0.1) mx = 0.1;
	var barw = 1 - mx / (mx + ival);
	return parseInt(5 + barw*95);
}
function CalcBarMsg (ival, hi, lo) {
	if ((hi != null) && (lo != null) && (hi < lo)) {
		if (lo < ival) return 'red';
		if (hi < ival) return 'Chocolate';
		return 'green';
	}
	if ((lo != null) && (ival < lo)) return 'red';
	if ((hi != null) && (hi < ival)) return 'red';
	return 'green';
}

// 180425 MakeInfoTd 함수에 기상장치 유무(weatherFl) 파라미터 추가
function MakeInfoTd (ival,i,def, weatherFl) {
	var obj = itemarr[i];
	var msg = "<tr><td>"+obj.name+"</td>";
	
	if ((ival === null) || (ival === '')) {
		if (def == '') return '';
		return msg + "<td class='popupdate'>" + def + "</td></tr>";
	}
	
	if ((obj.lo == null) && (obj.hi == null)) {
		// 180425 기상장치 유무에 따른 -(하이픈) 처리
		if((obj.id == 'temperature' || obj.id == 'humidity' || obj.id == 'winddirection' || obj.id == 'windspeed') && weatherFl == 'N' ) {
			return msg + "<td><font color='black'>-</font></td></tr>";
		} else {
			return msg + "<td><font color='black'>"+js_item_format(ival,obj.dec)+"</font>"+obj.unit+"</td></tr>";
		}
	}
	
	// bar graph
	var cc = CalcBarMsg (ival, obj.hi, obj.lo);
	var barw = CalcBarW (ival, obj.hi, obj.lo, obj.bot, obj.top);
	msg += "<td><table border=0 cellpadding=0 cellspacing=0><tr>";
	msg += "<td width='" + barw + "' bgcolor='" + cc + "'></td>";
	msg += "<td><font color='" + cc + "'>&nbsp;"+js_item_format(ival,obj.dec) + obj.unit + "</font></td>";
	msg += "</tr></table></td>";
	msg += "</tr>";
	return msg;
}

function ShowInfo (idx) {
	var msg;
	// 180425 sitearr[idx] 값 sobj에 담기
	var sobj = sitearr[idx];
	
	if (idx < sitearr.length) {
		// site
		if ((sobj.date != null) && (sobj.date != '')) {
			msg = "<table width='100%' bgcolor='#";
			msg += (sobj.old != 0) ? "d95050" : "50A050";
			msg += "' style='color:#ffffff; cursor:pointer;' onclick='ShowPathWindow("+idx+")'><tr><td><b>"+sobj.name+"</b>";
			msg += "<img src='img/right.png' width='15' height='15' border='0' align='right'></td></tr>";
			msg += "<tr><td align='right'>";
			msg += (sobj.old != 0) ? "$str_olddate" : "$str_update";
			msg += " : "+sobj.date+"</td></tr>";
			msg += "</table><table width='100%' onclick='SelectMarker(-2)'>";
			// 180425 MakeInfoTd 함수에 기상장치 유무(sobj.weatherFl) 파라미터 추가
			if (selectItemNo == -1) {
				for (var i=0; i<itemarr.length; i++) msg += MakeInfoTd (sobj.data[i], i, '', sobj.weatherFl);
			} else {
				msg += MakeInfoTd (sobj.data[selectItemNo], selectItemNo, '$str_nodata', sobj.weatherFl);
			}
			msg += "</table>";
		} else {
			msg = "<table width='100%' onclick='SelectMarker(-2)'><tr><td><b>"+sobj.name+"</b></td></tr>";
			msg += "<tr><td class='popupdate'>$str_nodata</td></tr></table>";
		}
	} else {
		// path
		var p = idx - sitearr.length;
		msg = "<table width='100%' bgcolor='#E8E8E8' onclick='SelectMarker(-2)'><tr><td><b>"+sitearr[pathId].name+"</b></td></tr>";
		msg += "<tr><td class='popupdate'>$str_update : "+paths[p][0];
		if ($iIsGps == 1) {
			var lat = paths[p][2];
			var lng = paths[p][1];
			if ((lat != null) && (lat != '') && (lat != 0) && (lng != null) && (lng != '') && (lng != 0)) msg += "<br>"+js_item_format(lat,5)+", "+js_item_format(lng,5);
		}
		msg += "</td></tr></table><table width='100%' onclick='SelectMarker(-2)'>";
		if (selectItemNo == -1) {
			for (var i=0; i<itemarr.length; i++) msg += MakeInfoTd (paths[p][4+i], i, '');
		} else {
			msg += MakeInfoTd (paths[p][4+selectItemNo], selectItemNo, '$str_nodata');
		}
		msg += "</table>";
	}
	MapInfowindowShow (idx < markers.length ? idx : pathId, msg);
}

function ShowCctv (idx) {

	var screenH = screen.availHeight;
	var screenW = screen.availWidth;
	var scY = window.screenY || window.screenTop;
	var scX = window.screenX || window.screenLeft;
	var height = screenH + scY;
	var width = screenW + scX;
		
	var cctvInfo = cctvarr[idx];	
	localStorage.setItem('cctvInfo', JSON.stringify(cctvInfo));
	var popUrl = "rtspPlayer.php"; 
	window.open(popUrl, 'VmsPlayer', 'width=800, height=600, resizable=yes, scrollbars=no, status=no, location=no, toolbar=no, top=' + height+ ', left=' + width );
}

function ShowPopup (idx) {
	var msg;
	var minw = '100%';
	var sobj = sitearr[idx];
	
	if ((sobj.date != null) && (sobj.date != '')) {
		if (sobj.old != 0) {
			msg = "<table width='100%' bgcolor='#d95050' style='color:#ffffff;' onclick='SelectMarker("+idx+")'>";
			msg += "<tr><td><b>"+sobj.name+"</b></td></tr>";
			msg += "<tr><td align='right'>$str_olddate : "+sobj.date+"</td></tr>";
			msg += "</table>";
		} else {
			msg = "<table width='"+minw+"' onclick='SelectMarker("+idx+")'><tr><td><b>"+sobj.name+"</b></td></tr></table>";
			msg += "<table width='"+minw+"' onclick='SelectMarker("+idx+")'>";
			if (selectItemNo == -1) {
				var tdmsg = "";
				for (var i=0; i<itemarr.length; i++) {
					if (itemarr[i].using == '1') tdmsg += MakeInfoTd (sobj.data[i], i, '');
				}
				if (tdmsg == "") {
					for (var i=0; i<itemarr.length; i++) tdmsg += MakeInfoTd (sobj.data[i], i, '');
				}
				msg += tdmsg;
			} else {
				msg += MakeInfoTd (sobj.data[selectItemNo], selectItemNo, '$str_nodata');
			}
			msg += "</table>";
		}
	} else {
		msg = "<table width='"+minw+"' bgcolor='#EEEEEE' onclick='SelectMarker("+idx+")'><tr><td><b>"+sobj.name+"</b></td></tr>";
		msg += "<tr><td style='color:#888888;'>$str_nodata</td></tr>";
		msg += "</table>";
	}
	MapPopupShow (idx, msg);
}
function ShowPopups () {
	var n=0;
	for (var i=0; i<itemarr.length; i++) {
		if (itemarr[i].using == '1') n++;
	}
	if ((ism == 0) && (0<n)) for (var i=0; i<popups.length; i++) ShowPopup (i);

}
function ClosePopups () {
	for (var i=0; i<popups.length; i++) MapPopupClose (i);
}

// Selected 'site' or 'path'.
function SelectMarker (idx,opt) {
	
	var filter = 'win16|win32|win64|mac';
	
	MapInfowindowClose ();
	ClosePopups ();
	
	// table
	if (-1 <= selectMarkerNo) $('#bSite'+selectMarkerNo).css('background','');
	selectMarkerNo = idx;
	if (-1 <= selectMarkerNo) $('#bSite'+selectMarkerNo).css('background','#FFFFFF');
	
	// map
	if (idx == -1) {
		if (opt == 1) MapMarkerShowAll (0);
		ShowPopups ();
	} else
	if (0 <= idx) {
		if (opt == 1) MapMarkerCenter (idx < markers.length ? idx : pathId);
		ShowInfo (idx);
		
		if(opt != 1) {
			if(navigator.platform) {
			
				if(0 < filter.indexOf(navigator.platform.toLowerCase())) {
					
					if(cctvarr[idx].url != '') {
						ShowCctv (idx);
					}
				}
			}
		}
	}
}

// Click 'item' at the left list
function SelectItem (idx) {
	if (-1 <= idx) {
		$('#lItem'+selectItemNo).css('background','');
		selectItemNo = idx;
		UpdateMarkersColor (markers.length);
		if (0 <= selectMarkerNo) ShowInfo (selectMarkerNo, -1);
		if (-1 == selectMarkerNo) ShowPopups ();
	} else {
		selectItemNo = -1;
	}
	$('#lItem'+selectItemNo).css('background','#FFFFFF');

}

// Add all site on the map
for (var i=0; i<sitearr.length; i++) {
	MapMarkerAdd(sitearr[i].lat, sitearr[i].lng, 0, sitearr[i].name);
	MapPopupAdd(i);
}

var gUpdateRealtimeBusy = 0;	// check busy (0<)
function UpdateRealtime() {
	if ((0 < gUpdateRealtimeBusy) && (gUpdateRealtimeBusy < 5)) {
		gUpdateRealtimeBusy++;
		return;
	}
	gUpdateRealtimeBusy = 1;
	
	var dataarr = '';
	for (var i=0; i<itemarr.length; i++) {
		if (0 < i) dataarr += '&';
		dataarr += 'id'+i+'='+itemarr[i].id;
	}
	
	$.ajax ({
		url:"pRealtime.php?q=rt",
		type:"post",
		data:dataarr,
		cache: false,
		error: function(xhr, status, msg) {
			gUpdateRealtimeBusy = 0;
		},
		success : function(msg) {
			if (msg == 'EC#730403') {
				// logout : Error anonymous access
				setTimeout ('document.location.replace("pRealtime.php?login=1")',2000);
				return;
			}
			var token = msg.split (";");
			if ((token.length-1)%(itemarr.length+6) != 0) {
				// change site or item
				setTimeout ('document.location.replace("pRealtime.php")',2000);
				return;
			}
			
			var isMove = false;
			var p = 1;
			$('#time').html('$str_update : '+token[0]);
			while (p < token.length) {
				for (var i=0; i<sitearr.length; i++) {
					var sobj = sitearr[i];
					if (sobj.id == token[p]) {
						sobj.date = token[p+1];
						if (sobj.old != token[p+2]) {
							sobj.old = token[p+2];
							if (sobj.old != 0) $('#bSite'+i).css ('color', '#d95050');
							else $('#bSite'+i).css ('color', '#000000');
						}
						if ((token[p+3] != '') && (token[p+4] != '') && ((sobj.lng != token[p+3]) || (sobj.lat != token[p+4]))) {
							sobj.lng = token[p+3];
							sobj.lat = token[p+4];
							MapMarkerMove (i, sobj.lat, sobj.lng);
							
							if ((selectMarkerNo < 0) && (pathId < 0)) isMove = true;
						}
						
						sobj.wdata = token[p+5];
						var idname = '#bItem'+sobj.id;
						for (var x=0; x<itemarr.length; x++) {
							var obj = itemarr[x];
							var ival = parseFloat(token[p+6+x]);
							if (!isNaN(ival)) {
								sobj.data[x] = ival;
								if ((obj.using == '1') || (obj.using == '2')) {
									// Important or Realtime
									if (sobj.old != 0) {
										$(idname+'_'+x).html("-");
									} else
									if ((obj.lo == null) && (obj.hi == null)) {
										// 180425 기상장치 유무에 따른 -(하이픈) 처리
										if((x==7 || x==8 || x==9 || x==10) && sobj.weatherFl == 'N') { 
											$(idname+'_'+x).html("-");										
										} else {
											$(idname+'_'+x).html(js_item_format(ival,obj.dec)+obj.unit);
										}											
									} else {
										var cc = CalcBarMsg (ival, obj.hi, obj.lo);
										var barw = CalcBarW (ival, obj.hi, obj.lo, obj.bot, obj.top);
										var cellmsg = "<font color='"+cc+"'>"+js_item_format(ival,obj.dec)+obj.unit+"</font>";
										cellmsg += "<table width='100%' height='2px' border=0 cellpadding=0 cellspacing=0><tr><td bgcolor='"+cc+"' width='"+barw+"%'></td><td bgcolor='white' width='"+(100-barw)+"%'></td></tr></table>";
										$(idname+'_'+x).html(cellmsg);
									}
								}
							} else {
								sobj.data[x] = '';
								if ((obj.using == '1') || (obj.using == '2')) {
									$(idname+'_'+x).html('');
								}
							}
						}
						break;
					}
				}
				p += itemarr.length + 6;
			}
			
			if (isZooming == false) {
				if ((0 <= selectMarkerNo) && (selectMarkerNo < sitearr.length)) ShowInfo (selectMarkerNo, -1);	// update maker information
				if (selectMarkerNo == -1) ShowPopups ();
			}
			UpdateMarkersColor (sitearr.length);
			gUpdateRealtimeBusy = 0;
			
			if (isMove) MapMarkerShowAll (0);
		}
	});
}
</script>

EOF;
if (!$ISM) echo ("<div class='TopCurtain'></div>\n");

print <<<EOF
<div id='msgPopupBox' class='msgPopupBox' onclick='MsgPopupHide()'><div class='msgPopupInn'><div id='msgPopupTxt' class='msgPopupTxt'></div></div></div>

</body>

<script type='text/javascript'>
$(document).keydown(function(e) {
	if (e.keyCode == 27) MsgPopupHide ();
});
</script>

</html>

EOF;
mysql_close ($cDb);
?>
