<?php

require_once('common.php');

session_start();

$sId = $_SESSION[$sDbDb.'_id'];

// connect db
$cDb = mysql_connect($sDbIp, $sDbId, $sDbPw);
if (!$cDb) die ('EC#730008');
mysql_query('set names utf8');
if (!mysql_select_db($sDbDb, $cDb)) die ('EC#730009');

$aSelectSite = $_POST['selectsite'];
if ((!is_array ($aSelectSite)) || (sizeof($aSelectSite) < 1)) die();

// Check item
$sitem = $_GET['item'];
if ((!isset ($sitem)) || (!in_array($sitem,$aDbItems))) die();

// Check date
$t = $_GET['t'];
if ((!isset($t)) || (!is_numeric($t))) die();

// Get all site
$aSiteName = array ();
$sQuery = "SELECT `SITENO`,`NAME` FROM `sites` WHERE `FILE` is NULL ORDER BY `NAME`";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	while ($aRow = mysql_fetch_row($cResult)) {
		$id = $aRow[0];
		$aSiteName[$id] = $aRow[1];
	}
	mysql_free_result($cResult);
}

// Get item info
$itemName = "";
$itemUnit = "";
$itemDec  = 0;
$sQuery = "SELECT `ID`,`NAME`,`UNIT`,`DEC` FROM `items`";
if ($sId != 'admin') $sQuery .= " WHERE `USING`!='0'";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	while ($aRow = mysql_fetch_row($cResult)) {
		if ($aRow[0] != $sitem) continue;
		$itemName = $aRow[1];
		$itemUnit = ($aRow[2] != '') ? ' ('.$aRow[2].')' : '';
		$itemDec  = $aRow[3];
	}
	mysql_free_result($cResult);
}

PrintHtmlHead ();

?>

<style type='text/css'>
.graphContainer {
	box-sizing: border-box;
	width: 100%; height: 100%; padding: 0px 15px 20px 15px; margin: 0px auto 0px auto;
	page-break-inside: avoid;
}
.graphPlaceholder {
	width: 100%; height: 90%; font-size: 14px; line-height: 1.2em;
}
</style>
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.flot.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.resize.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.categories.min.js'></script>

</head>
<body style='overflow:hidden; cursor:default'>

<?php

function item_format($val,$dec) {
	Global $arr_win_dir, $arr_pozip;
	
	if ((0 <= $dec) && ($dec <= 9)) {
		return number_format($val,$dec);
	}
	if (10 == $dec) {	// Special code: wind_direction
		$val = (int)$val;
		if (($val < 1) || (16 < $val)) $val = 1;
		return $arr_win_dir[$val-1];
	}
	if (11 == $dec) {	// Special code: pozip
		$val = (int)$val;
		if (($val < 0) || (1 < $val)) $val = 0;
		return $arr_pozip[$val];
	}
	return $val;
}

// Draw graphs

$dt = ($iTimeType == 1) ? 600 : 10;
$iDate1 = $t-$dt;
$iDate2 = $t+$dt;

// time format
$xformat = '%H:%M'; $xmintic = '[1,"hour"]';

$sQuery = "SELECT `SITENO`,`DATE`,`$sitem`";
$sQuery .= " FROM `datas` WHERE ($iDate1<=`DATE`) and (`DATE`<=$iDate2) and (`SITENO` in (";
for ($i=0; $i<sizeof($aSelectSite); $i++) {
	if (0 < $i) $sQuery .= ",";
	$sQuery .= "'$aSelectSite[$i]'";
}
$sQuery .= ")) ORDER BY `DATE`";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult == null) die('EC#730105');

echo ("<div class='graphContainer'><center><h2>".DateIntToStr($t)." {$itemName}{$itemUnit}</h2></center><div id='graphb' class='graphPlaceholder'></div></div>\n");

echo ("<script type='text/javascript'>\n");
echo ("$.plot('#graphb',[[");

$t = IntToJs($t);
for ($i=0; $i<sizeof($aSelectSite); $i++) {
	$ssite = $aSelectSite[$i];
	
	$mint = 600;	// 10minute
	$miny = null;
	@mysql_data_seek($cResult,0);
	while ($aRow = mysql_fetch_row($cResult)) {
		if ($aRow[0] != $ssite) continue;
		$dt = abs($t - IntToJs($aRow[1]));
		if ($dt < $mint) {
			$mint = $dt;
			$miny = $aRow[2];
		}
	}
	if ($miny !== null) {
		$nums = item_format ($miny, $itemDec);
		$numv = round ($miny, (9<$itemDec)?0:$itemDec);
		echo ("['".$aSiteName[$ssite]."<br>".$nums."',".$numv."],");
	} else {
		echo ("['".$aSiteName[$ssite]."<br>".$str_nodata."',0],");
	}
}
echo ("]],{");
echo ("	series: { bars: { show: true, barWidth: 0.6, align: 'center' } },");
echo ("	xaxis: { mode: 'categories', tickLength: 0, autoscaleMargin: 0.01 }");
echo ("});\n");
echo ("</script>\n");

mysql_free_result($cResult);

echo ("</body>\n");

echo ("</html>\n");


mysql_close ($cDb);
?>
