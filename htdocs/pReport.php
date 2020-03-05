<?php

require_once('common.php');

session_start();

$sId = $_SESSION[$sDbDb.'_id'];

// connect db
$cDb = mysql_connect($sDbIp, $sDbId, $sDbPw);
if (!$cDb) die ('EC#730008');
mysql_query('set names utf8');
if (!mysql_select_db($sDbDb, $cDb)) die ('EC#730009');

// Block anonymous access
if (!isset($sId)) {
	$iIsGuest = GetSettingInt ($cDb, 'isguest', 0, 0, 1);
	if ($iIsGuest == 0) {
		echo ("<html><body>\n");
		echo ("<script type='text/javascript'>\n");
		echo ("window.opener.location.reload();\n");
		echo ("window.close();\n");
		echo ("</script>\n");
		die("</body>\n</html>\n");
	}
	$sUserName = 'Guest';
} else {
	$sQuery = "select `NAME` from member where `ID`='$sId'";
	$cResult = mysql_query($sQuery) or die('EC#730105');
	$aRow = mysql_fetch_row($cResult) or die('EC#730106');
	$sUserName = ($aRow[0] != '') ? $aRow[0] : $sId;
}

$sSelectSite = $_POST['selectsite'];
if ((!is_array ($sSelectSite)) || (sizeof($sSelectSite) != 1)) die();
$sSelectSite = $sSelectSite[0];
if (!ctype_digit($sSelectSite)) die();

$aSelectItem = $_POST['selectitem'];
if ((!is_array ($aSelectItem)) || (sizeof($aSelectItem)  < 1)) die();
$selectItemNo = sizeof($aSelectItem);
for ($i=0; $i<$selectItemNo; $i++) {
	if (!in_array($aSelectItem[$i],$aDbItems)) die();
}

// Check date
$sDate1 = $_POST['date1'];
if ((!isset($sDate1)) || (strlen($sDate1)!=10)) die();
$d = strtotime($sDate1);
if ($d == 0) die();

$opt = (int)($_GET['opt']);
if ($opt == 3) {
	$sTitle = $str_report_term;
	$sTerm  = date("Y{$str_year} m{$str_month} d{$str_day}", $d);
	$sDate1 = date("Y-m-d", $d);
	
	$sDate2 = $_POST['date2'];
	if ((!isset($sDate2)) || (strlen($sDate2)!=10)) die();
	$d = strtotime($sDate2);
	if ($d == 0) die();
	
	$sTerm .= date(" ~ Y{$str_year} m{$str_month} d{$str_day}", $d);
	$sDate2 = date("Y-m-d", $d);
} else
if ($opt == 2) {
	$sTitle = $str_report_year;
	$sTerm  = date("Y{$str_year}", $d);
	$sDate1 = date("Y-01-01", $d);
	$sDate2 = date("Y-12-31", $d);
} else
if ($opt == 1) {
	$sTitle = $str_report_month;
	$sTerm  = date("Y{$str_year} m{$str_month}", $d);
	$sDate1 = date("Y-m-01", $d);
	$sDate2 = date("Y-m-t", $d);
} else {
	$sTitle = $str_report_day;
	$sTerm  = date("Y{$str_year} m{$str_month} d{$str_day}", $d);
	$sDate1 = date("Y-m-d", $d);
	$sDate2 = $sDate1;
}

// String time to packed int
$iDate1 = DateStrToInt ($sDate1,  0,  0,  0);
$iDate2 = DateStrToInt ($sDate2, 23, 59, 59);
if (($iDate1 <= 0) || ($iDate2 <= 0)) die();



// Get site info
$sSiteName = 'NA';
$sQuery = "SELECT `NAME` FROM `sites` WHERE (`SITENO`='$sSelectSite') and (`FILE` is NULL)";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	$aRow = mysql_fetch_row($cResult);
	if ($aRow != null) $sSiteName = $aRow[0];
	mysql_free_result($cResult);
}

// Get item info
$aItemName = array ();
$aItemUnit = array ();
$aItemDec = array ();
$aItemLo  = array ();
$aItemHi  = array ();
$aItemBot = array ();
$aItemTop = array ();
$sQuery = "SELECT `ID`,`NAME`,`UNIT`,`DEC`,`LO`,`HI`,`BOTTOM`,`TOP` FROM `items`";
if ($sId != 'admin') $sQuery .= " WHERE `USING`!='0'";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	while ($aRow = mysql_fetch_row($cResult)) {
		$rv = array_search ($aRow[0], $aSelectItem);
		if ($rv === false) continue;
		$aItemName[$rv] = $aRow[1];
		$aItemUnit[$rv] = ($aRow[2] != '') ? ' ('.$aRow[2].')' : '';
		$aItemDec[$rv] = $aRow[3];
		$aItemLo[$rv]  = $aRow[4];
		$aItemHi[$rv]  = $aRow[5];
		$aItemBot[$rv] = $aRow[6];
		$aItemTop[$rv] = $aRow[7];
	}
	mysql_free_result($cResult);
}



PrintHtmlHead ();

?>

<style type='text/css'>
.graphContainer {
	box-sizing: border-box;
	width: 100%; height: 300px; padding: 0px 15px 20px 15px; margin: 0px auto 0px auto;
	page-break-inside: avoid;
}
.graphPlaceholder {
	width: 100%; height: 100%; font-size: 14px; line-height: 1.2em;
}
</style>
<link rel='stylesheet' href='js/jquery.ui.min.css?modified=20170706' />
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.ui.min.js?modified=20170706'></script>
<script type='text/javascript' src='js/jquery.flot.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.time.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.resize.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.windir.js'></script>

</head>
<body style='cursor:default'>

<?php

// Title

echo ("<h1 align='center'>{$sTitle}{$str_report}</h1>\n");
echo ("<table width='100%'><tr>");
echo ("<td width='33%' align='center'><h3>$str_report_location : $sSiteName</h3></td>");
echo ("<td width='34%' align='center'><h3>$str_report_date : $sTerm</h3></td>");
echo ("<td width='33%' align='center'><h3>$str_report_manager : $sUserName</h3></td>\n");
echo ("</tr></table>\n");

echo ("<h3>1. $str_report_1</h3>\n");
echo ("<p>\n");
echo ("<table width='94.5%' style='margin-left:38px;' border='1' cellpadding='5' cellspacing='0' bordercolor='#000000'>\n");
echo ("<tr align='center'>\n");
echo ("<td width='20%' class='TitleFont' rowspan='2'>$str_report_item</td>\n");
echo ("<td width='32%' class='TitleFont' colspan='2'>$str_report_min</td>\n");
echo ("<td width='32%' class='TitleFont' colspan='2'>$str_report_max</td>\n");
echo ("<td width='16%' class='TitleFont' rowspan='2'>$str_average</td>\n");
echo ("</tr>\n");
echo ("<tr align='center'>\n");
echo ("<td width='16%' class='TitleFont'>$str_time</td>\n");
echo ("<td width='16%' class='TitleFont'>$str_report_measures</td>\n");
echo ("<td width='16%' class='TitleFont'>$str_time</td>\n");
echo ("<td width='16%' class='TitleFont'>$str_report_measures</td>\n");
echo ("</tr>\n");


// Min, Max, Avg

$aItemAvg = array ();
$aItemAvgNo = array ();
$aItemMin = array ();
$aItemMax = array ();
$aItemMinTime= array ();
$aItemMaxTime= array ();
for ($i=0; $i<$selectItemNo; $i++) $aItemAvgNo[$i] = 0;

$sQuery = "SELECT ";
for ($i=0; $i<$selectItemNo; $i++) {
	if (0 < $i) $sQuery .= ", ";
	$sQuery .= "`".$aSelectItem[$i]."`";
}
$sQuery .= ", `DATE` FROM `datas` WHERE ('$iDate1'<=`DATE`) and (`DATE`<='$iDate2') and (`SITENO`='$sSelectSite')";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult == null) die ("</table></p><p>EC#730105</p>");
while ($aRow = mysql_fetch_row($cResult)) {
	for ($i=0; $i<$selectItemNo; $i++) {
		$row = $aRow[$i];
		if ($row === null) continue;
		if (0 < $aItemAvgNo[$i]) {
			$aItemAvg[$i] += $row;
			if ($row < $aItemMin[$i]) {
				$aItemMin[$i] = $row;
				$aItemMinTime[$i] = $aRow[$selectItemNo];
			}
			if ($aItemMax[$i] < $row) {
				$aItemMax[$i] = $row;
				$aItemMaxTime[$i] = $aRow[$selectItemNo];
			}
		} else {
			$aItemAvg[$i] = $aItemMin[$i] = $aItemMax[$i] = $row;
			$aItemMinTime[$i] = $aItemMaxTime[$i] = $aRow[$selectItemNo];
		}
		$aItemAvgNo[$i]++;
	}
}
for ($i=0; $i<$selectItemNo; $i++) {
	if (1 < $aItemAvgNo[$i]) $aItemAvg[$i] /= $aItemAvgNo[$i];
}
mysql_free_result($cResult);

for ($i=0; $i<$selectItemNo; $i++) {
	$dec = $aItemDec[$i];
	if (10 <= $dec) continue;
	
	echo ("<tr align='center'><td class='TitleFont'>{$aItemName[$i]}{$aItemUnit[$i]}</td>\n");
	if (0 < $aItemAvgNo[$i]) {
		echo ("<td>".DateIntToStr($aItemMinTime[$i])."</td>");
		echo ("<td>".number_format($aItemMin[$i],$dec)."</td>");
		echo ("<td>".DateIntToStr($aItemMaxTime[$i])."</td>");
		echo ("<td>".number_format($aItemMax[$i],$dec)."</td>");
		echo ("<td>".number_format($aItemAvg[$i],$dec)."</td>");
	} else {
		echo ("<td align='center' colspan='5'>$str_nodata</td>");
	}
	echo ("</tr>\n");
}

echo ("</table>\n");
echo ("</p>\n");



// Draw graphs

$iJsDate1 = IntToJs($iDate1);
$iJsDate2 = IntToJs($iDate2);
$iDayNum = (int)(($iJsDate2-$iJsDate1+60)/86400);

if (120 < $iDayNum) {
	$iGroupSize = ($iTimeType == 1) ? 24*60*60 : 10000;
	$iGraphStep = 1440;
} else if (25 <= $iDayNum) {
	$iGroupSize = ($iTimeType == 1) ? 6*60*60 : 500;
	$iGraphStep = 300;
} else if (5 <= $iDayNum) {
	$iGroupSize = ($iTimeType == 1) ? 60*60 : 100;
	$iGraphStep = 60;
} else if (1 <  $iDayNum) {
	$iGroupSize = ($iTimeType == 1) ? 10*60 : 10;
	$iGraphStep = 20;
} else {
	$iGroupSize = ($iTimeType == 1) ? 60 : 1;
	$iGraphStep = 20;
}
$iGraphStep *= 60;

// time format
if (366 < $iDayNum) { $xformat = '%y/%m/%d'; $xmintic = '[1,"day"]'; }
else if (3 < $iDayNum) { $xformat = '%m/%d'; $xmintic = '[1,"day"]'; }
else { $xformat = '%H:%M'; $xmintic = '[1,"hour"]'; }

// Prevents the data is too many.
$sQuery = "SELECT `DATE`";
for ($i=0; $i<sizeof($aSelectItem); $i++) {
	$sitem = $aSelectItem[$i];
	if ($aItemDec[$i] == 10) $sQuery .= ",AVG(cos((`$sitem`+3)*0.3927)),AVG(sin((`$sitem`+3)*0.3927))";	// Special code: wind_direction
	else if ($aItemDec[$i] == 11) $sQuery .= ",MAX(`$sitem`)";
	else $sQuery .= ",AVG(`$sitem`)";
}
// foreach ($aSelectItem as $sitem) $sQuery .= ",AVG(`$sitem`)";
$sQuery .= " FROM `datas` WHERE ('$iDate1'<=`DATE`) and (`DATE`<='$iDate2') and (`SITENO`='$sSelectSite')";
$sQuery .= " GROUP BY `DATE` DIV $iGroupSize";
$sQuery .= " ORDER BY `DATE`";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult == null) die('EC#730105');

echo ("&nbsp;\n");
echo ("<h3>2. $sTitle $str_report_2</h3>\n");
echo ("<center>\n");
for ($i=0; $i<$selectItemNo; $i++) {
	if ($aItemAvgNo[$i] == 0) continue;
	echo ("<div class='graphContainer'");
	if ($aItemDec[$i] == 10) echo (" style='padding-bottom:50px'");
	if ($aItemDec[$i] == 12) echo (" style='padding-bottom:50px'");
	echo ("><center><b>{$aItemName[$i]}{$aItemUnit[$i]}");
	echo ("</b></center><div id='graph$i' class='graphPlaceholder'></div>");
	if ($aItemDec[$i] == 10) echo ("$str_win_dir_comment");
	if ($aItemDec[$i] == 12) echo ("$str_alm_comment");
	echo ("</div>");
}
echo ("</center>\n");

echo ("<script type='text/javascript'>\n");

// Lack of data, graph is disconnected.
$iGraphStep *= 3;
for ($skipn=$i=0; $i<$selectItemNo; $i++) {
	$dec = $aItemDec[$i];
	if ($dec == 10) $skipn = 1;		// 20170308 PJW bug fix. $aSelectDec[$i] -> $aItemDec[$i] -> $dec
	if ($aItemAvgNo[$i] == 0) continue;
	echo ("$.plot('#graph$i',[");
	echo ("{");
	echo ("color:'#000000',");
	echo ("data:[");
	@mysql_data_seek($cResult,0);
	$oldsec = 0;
	while ($aRow = mysql_fetch_row($cResult)) {
		$dval = $aRow[1+$i+$skipn];
		
		if ($dval !== null) {
			if ($dec == 10) {	// Special code: wind_direction
				$dval = (int)(atan2($aRow[2+$i],$aRow[1+$i]) * 57.29578 - 90);
				if (359 < $dval) $dval -= 360;
				if ($dval < 0) $dval += 360;
			} else
			if ((0 <= $dec) && ($dec <= 9)) {
				$dval = round($dval,$dec);
			}
			$thissec = IntToJs((int)$aRow[0]);
			if ((0 < $oldsec) && ($iGraphStep < $thissec - $oldsec)) echo (',null');
			$oldsec = $thissec;
			
			echo (",[{$thissec}000,$dval]");
		} else echo (',null');
	}
	echo ("]}\n");
	echo ("],{");
	
	$lo = $aItemLo[$i];
	$hi = $aItemHi[$i];
	echo ("	grid: { hoverable:false, autoHighlight:false, margin:{bottom:10,top:0}");
	if (($lo !== null) && ($hi !== null)) {
		if ($hi < $lo) echo (",markings:[{color:'#E0E0E0',yaxis:{from:$lo}}]");
		else echo (",markings:[{color:'#E0E0E0',yaxis:{from:$hi}},{color:'#E0E0E0',yaxis:{to:$lo}}]");
	} else if ($lo !== null) echo (",markings:[{color:'#E0E0E0',yaxis:{to:$lo}}]");
	else if ($hi !== null) echo (",markings:[{color:'#E0E0E0',yaxis:{from:$hi}}]");
	echo (" },");
	
	echo ("	xaxis:{ mode:'time', timezone:'browser', timeformat:'$xformat', minTickSize:{$xmintic}, min:{$iJsDate1}000, max:{$iJsDate2}000 },");
	
	// y-axis range
	$bot = $aItemBot[$i];
	$top = $aItemTop[$i];
	$ymin = $ymax = 'null';
	if ($dec == 10) { $ymin = -25; $ymax = 385; }
	if ($dec == 11) { $ymin =   0; $ymax =   2; }
	if ($bot !== null) $ymin = $bot;
	if ($top !== null) $ymax = $top;
	echo ("	yaxis:{ min:$ymin, max:$ymax},");
	
	if ($dec < 10) echo ("	lines: { show: true },");
	else echo ("	lines: { show: false },");
		
	if ($dec == 10) echo ("	points: { show:false, winddir:'wd'},");
	else echo ("	points: { show: true },");
	
	echo ("});\n");
}
mysql_free_result($cResult);

echo ("</script>\n");

// Day report : print error values
if ($sDate1 == $sDate2) {
	
	function PrintoutOverData ($overt1, $overt2, $overv1, $overv2, $name, $unit, $dec, $lo, $hi) {
		echo ("<tr align='center'><td class='TitleFont'>{$name}{$unit}</td>");
		echo ("<td>".DateIntToStr($overt1)." ~ ".DateIntToStr($overt2)."</td>");
		echo ("<td>".number_format($overv1,$dec)." ~ ".number_format($overv2,$dec)."</td>");
		echo ("<td>");
		if ($lo != null) echo (number_format($lo,$dec));
		echo (" ~ ");
		if ($hi != null) echo (number_format($hi,$dec));
		echo ("</td></tr>");
	}
	
	echo ("<h3>3. $str_report_3</h3>\n");
	
	$sQuery = "SELECT `DATE`";
	foreach ($aSelectItem as $sitem) $sQuery .= ",`$sitem`";
	$sQuery .= " FROM `datas` WHERE ('$iDate1'<=`DATE`) and (`DATE`<='$iDate2') and (`SITENO`='$sSelectSite')";
	$sQuery .= " ORDER BY `DATE`";
	$cResult = mysql_query($sQuery, $cDb);
	if ($cResult == null) die('EC#730105');
	
	echo ("<p>\n");
	echo ("<table width='94.5%' style='margin-left:38px;' border='1' cellpadding='5' cellspacing='0' bordercolor='#000000'>\n");
	echo ("<tr align='center'>\n");
	echo ("<td width='20%' class='TitleFont'>$str_report_item</td>\n");
	echo ("<td width='40%' class='TitleFont'>$str_time</td>\n");
	echo ("<td width='20%' class='TitleFont'>$str_report_measures</td>\n");
	echo ("<td width='20%' class='TitleFont'>$str_report_reference</td>\n");
	echo ("</tr>\n");
	
	$isnodata = true;
	for ($i=0; $i<$selectItemNo; $i++) {
		if ($aItemAvgNo[$i] == 0) continue;
		
		$name = $aItemName[$i];
		$unit = $aItemUnit[$i];
		$dec = $aItemDec[$i];
		if (10 <= $dec) continue;
		
		$lo = $aItemLo[$i];
		$hi = $aItemHi[$i];
		if (($lo === null) && ($hi === null)) continue;
		if (($lo !== null) && ($hi !== null) && ($hi < $lo)) {
			$hi = $lo;
			$lo = null;
		}
		
		$status = 0;
		@mysql_data_seek($cResult,0);
		while ($aRow = mysql_fetch_row($cResult)) {
			$dval = $aRow[1+$i];
			if ($dval === null) continue;
			
			// check Abnormal type.
			$nowstatus = 0;
			if (($lo !== null) && ($dval < $lo)) $nowstatus = 1;
			if (($hi !== null) && ($hi < $dval)) $nowstatus = 2;
			
			if ($status != 0) {
				if ($status == $nowstatus) {
					// contiue Abnormal type.
					if ($dval < $overv1) $overv1 = $dval;
					if ($overv2 < $dval) $overv2 = $dval;
					$overt2 = $aRow[0];
				} else
				if ($status != $nowstatus) {
					// change Abnormal type.
					PrintoutOverData ($overt1, $overt2, $overv1, $overv2, $name, $unit, $dec, $lo, $hi);
					$isnodata = false;
					$status = 0;
				}
			}
			
			if (($status == 0) && ($nowstatus != 0)) {
				// new Abnormal type.
				$overv1 = $overv2 = $dval;
				$overt1 = $overt2 = $aRow[0];
				$status = $nowstatus;
			}
		}
		
		// final Abnormal type.
		if (($status == 1) || ($status == 2)) {
			PrintoutOverData ($overt1, $overt2, $overv1, $overv2, $name, $unit, $dec, $lo, $hi);
			$isnodata = false;
			$status = 0;
		}
	}
	if ($isnodata) echo ("<td align='center' colspan='4'>$str_nodata</td></tr>\n");
	
	echo ("</table>\n");
	echo ("&nbsp;</p>\n");
}

echo ("</body>\n");

echo ("<script type='text/javascript'>\n");
//echo ("window.print();\n");
echo ("if (ism == 0) $('.graphContainer').resizable({handles: 's', maxHeight: 900, minHeight: 300});\n");
echo ("</script>\n");

echo ("</html>\n");


mysql_close ($cDb);
?>
