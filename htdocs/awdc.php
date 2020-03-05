<?php

require_once('common.php');

// connect db
$cDb = @mysql_connect($sDbIp, $sDbId, $sDbPw);
if (!$cDb) die ('EC#730008');
@mysql_query('set names utf8');
if (!@mysql_select_db($sDbDb, $cDb)) die ('EC#730009');



function GetPostExp ($v,$e) {
	if (isset($v)) {
		$v = preg_replace('/\r\n|\r|\n/','',$v);
		if (is_numeric($v)) {
			$v *= $e;
			return "'$v'";
		}
	}
	return 'NULL';
}



// Ajax.Insert minute data /////////////////////////////////////////////////////
$iSite = $_POST['stationid'];

// 20180307
if(isset($iSite) && ctype_digit($iSite)){
	$iSite = (int)$iSite + 1000;
	$iSite = (string)$iSite;
}

if (!isset($iSite)) $iSite = $_POST['site'];
$iDate = $_POST['date'];
if (!$iDate) $iDate = $_POST['nalja'];
if (isset($iDate)) {
	$iTime = $_POST['time'];
	if (!isset($iTime))$iTime = $_POST['sigan'];
	
	if (isset($iTime)) $iDate .= $iTime;
} else $iDate = ($iTimeType == 1) ? date('YmdHis') : date('YmdHi');

$aDatas = array();

// check data
if (!ctype_digit($iSite)) die ('Error: site');
if (!ctype_digit($iDate)) die ('Error: date');
if (strlen($iDate) != (($iTimeType == 1)?14:12)) die ('Error: date.len');
$iDate = DateTimeToInt($iDate);

// get LNG & LAT
$dLng = $_POST['LNG'];
$dLat = $_POST['LAT'];
if ((!isset($dLng)) || (!is_numeric($dLng)) || (!isset($dLat)) || (!is_numeric($dLat)) || ($dLng < -180) || (180 < $dLng) || ($dLat < -90) || (90 < $dLat)) {
	// No Location
	$sLng = 'NULL';
	$sLat = 'NULL';
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
} else {
	$sLng = "'$dLng'";
	$sLat = "'$dLat'";
}

// POST data $aDbItems[] to double[]
$i = 0;
for ($i=0; $i<sizeof($aDbItems); $i++) {
		 if ($aSbItemDec[$i] == 1) $exp = 0.1;
	else if ($aSbItemDec[$i] == 2) $exp = 0.01;
	else if ($aSbItemDec[$i] == 3) $exp = 0.001;
	else $exp = 1;	// ==0 or 10, 11, ...
	$aDatas[$i] = GetPostExp($_POST[$aDbItems[$i]], $exp);
}

//$sensor = $_POST['sensor'];								// $aDbItems[3..6] = 'SENSOR0'..'SENSOR3'
//$sensor = isset($sensor) ? explode(':', $sensor) : array();
//for ($j=0; $j<4; $j++) $aDatas[$i++] = GetPostExp($sensor[$j], 0.001);

$iWdate = 'NULL';
$iWxy   = 'NULL';
$sWdata = 'NULL';

// Insert or update min data
$sQuery = "INSERT INTO `datas` (`SITENO`, `DATE`, `LNG`, `LAT`, `WDATE`, `WXY`";
for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ", `$aDbItems[$i]`";
$sQuery .= ") VALUES ('$iSite', '$iDate', $sLng, $sLat, $iWdate, $iWxy";
for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ", $aDatas[$i]";
$sQuery .= ") ON DUPLICATE KEY UPDATE `LNG`=$sLng, `LAT`=$sLat, `WDATE`=$iWdate, `WXY`=$iWxy";
for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ", `$aDbItems[$i]`=$aDatas[$i]";
@mysql_query($sQuery, $cDb) or die ('EC#730101');

// Insert or update last data
$sQuery = "INSERT INTO `lasts` (`SITENO`, `DATE`, `LNG`, `LAT`, `WDATA`";
for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ", `$aDbItems[$i]`";
$sQuery .= ") VALUES ('$iSite', '$iDate', $sLng, $sLat, $sWdata";
for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ", $aDatas[$i]";
$sQuery .= ") ON DUPLICATE KEY UPDATE `DATE`='$iDate', `LNG`=$sLng, `LAT`=$sLat, `WDATA`=$sWdata";
for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ", `$aDbItems[$i]`=$aDatas[$i]";
@mysql_query($sQuery, $cDb) or die ('EC#730102');

@mysql_close ($cDb);

?>
