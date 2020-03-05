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
$iIsGuest = GetSettingInt ($cDb, 'isguest', 0, 0, 1);
if (!isset($sId)) {
	die ("<html><head><meta http-equiv='refresh' content='0;url=pRealtime.php?login=1'></head></html>");
}



// get all selected site & check number format
$aSelectSite = $_POST['selectsite'];
if (is_array ($aSelectSite)) {
	for ($i=sizeof($aSelectSite)-1; 0<=$i; $i--) {
		if (!ctype_digit($aSelectSite[$i])) array_splice ($aSelectSite, $i, 1);
	}
} else {
	$aSelectSite = array();
}
$selectSiteNo = sizeof($aSelectSite);

// get all selected item & check item name
$aSelectItem = $_POST['selectitem'];
if (is_array ($aSelectItem)) {
	for ($i=sizeof($aSelectItem)-1; 0<=$i; $i--) {
		if (!in_array($aSelectItem[$i],$aDbItems)) array_splice ($aSelectItem, $i, 1);
	}
} else {
	$aSelectItem = array();
}
$selectItemNo = sizeof($aSelectItem);

$iOrder = $_POST['order'];
if (!isset($iOrder)) $iOrder = 1;

$iType = $_POST['type'];		// 0:Number, 1:Graph
if (!isset($iType)) $iType = 0;

$iPage = $_POST['page'];		// Results page the user is viewing
if (!isset($iPage)) $iPage = 0;

$iOui = $_POST['oui'];
if (!isset($iOui)) $iOui = -1;

$sDate1 = $_POST['date1'];
if (!isset ($sDate1)) $sDate1 = date('Y-m-d');

$sDate2 = $_POST['date2'];
if (!isset ($sDate2)) $sDate2 = date('Y-m-d');

// Search Terms : sDate1~sDate2
$iMaxDates = GetSettingInt ($cDb, 'maxdates', 366, 1, 10000);
if ((strlen($sDate1)==10) && (strlen($sDate2)==10)) {
	$iDiff = (strtotime($sDate2) - strtotime($sDate1)) / 86400;
	if ($iDiff < 0) { $sDate2 = $sDate1; }
	if ($iMaxDates <= $iDiff) $sDate2 = date('Y-m-d', strtotime($sDate1) + ($iMaxDates-1)*24*60*60);
}

// String time to packed int
$iDate1 = $iDate2 = 0;
if ((0 < $selectSiteNo) && (0 < $selectItemNo) && (strlen($sDate1)==10) && (strlen($sDate2)==10)) {
	$iDate1 = DateStrToInt ($sDate1,  0,  0,  0);
	$iDate2 = DateStrToInt ($sDate2, 23, 59, 59);
	$iJsDate1 = IntToJs($iDate1);
	$iJsDate2 = IntToJs($iDate2);
}



// Get all site
$aSite = array ();
$aSiteName = array ();
$sQuery = "SELECT `SITENO`,`NAME` FROM `sites` WHERE `FILE` is NULL ORDER BY `NAME`";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	while ($aRow = mysql_fetch_row($cResult)) {
		$id = $aRow[0];
		$aSite[] = $id;
		$aSiteName[$id] = $aRow[1];
	}
	mysql_free_result($cResult);
}

// Get all item
// step1. Get last item.
$sQuery = "SELECT SUM(`$aDbItems[0]`)";
for ($i=1; $i<sizeof($aDbItems); $i++) $sQuery .= ", SUM(`{$aDbItems[$i]}`)";
$sQuery .= " FROM `lasts`";
$cResult = mysql_query($sQuery, $cDb);
$aItemIds = array();
if ($cResult != null) {
	if ($aRow = mysql_fetch_row($cResult)) {
		for ($i=0; $i<sizeof($aDbItems); $i++)
			if ($aRow[$i] !== null) $aItemIds[] = $aDbItems[$i];
	}
	mysql_free_result($cResult);
}

// step2. Get item information
$aItem = array ();
$aItemName = array ('SITENO'=>$str_data_site, 'DATE'=>$str_time);
$aItemUnit = array ();
$aItemDec  = array ();
$aItemLo   = array ();
$aItemHi   = array ();
$aItemBot  = array ();
$aItemTop  = array ();
$sQuery = "SELECT `ID`,`NAME`,`UNIT`,`DEC`,`LO`,`HI`,`BOTTOM`,`TOP` FROM `items`";
if ($sId != 'admin') $sQuery .= " WHERE `USING`!='0'";
$sQuery .= " ORDER BY `ORDER`";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	while ($aRow = mysql_fetch_row($cResult)) {
		if (in_array($aRow[0], $aItemIds)) {
			$id = $aRow[0];
			$aItem[] = $id;
			$aItemName[$id] = $aRow[1];
			$aItemUnit[$id] = $aRow[2];
			$aItemDec [$id] = $aRow[3];
			$aItemLo  [$id] = $aRow[4];
			$aItemHi  [$id] = $aRow[5];
			$aItemBot [$id] = $aRow[6];
			$aItemTop [$id] = $aRow[7];
		}
	}
	mysql_free_result($cResult);
}

// Get unit
$aSelectUnit = array();
$aSelectDec  = array();
foreach ($aSelectItem as $item) {
	$aSelectUnit[] = $aItemUnit[$item];
	$aSelectDec [] = $aItemDec [$item];
}

// Make sort option.
$aOrder = array($aItemName['SITENO'], $aItemName['DATE'].$str_sort_ascent, $aItemName['DATE'].$str_sort_descent);
foreach ($aSelectItem as $item) {
	$aOrder[] = $aItemName[$item].$str_sort_ascent;
	$aOrder[] = $aItemName[$item].$str_sort_descent;
}
if (($iOrder < 0) || (sizeof($aOrder) <= $iOrder)) $iOrder = 1;

$sOrder = " ORDER BY ";
	 if ($iOrder == 0) $sOrder .= "`SITENO`,`DATE`";
else if ($iOrder == 1) $sOrder .= "`DATE`,`SITENO`";
else if ($iOrder == 2) $sOrder .= "`DATE` DESC,`SITENO`";
else {
	$i = $iOrder-3;
	$sOrder .= "`".$aSelectItem[(int)($i/2)]."`";
	if (($i%2) == 1) $sOrder .= " DESC";
	$sOrder .= ",`DATE`,`SITENO`";
}

// Make OU option
$aOuRange = array(1,5,15,20,100,300,500);
if ($iOui < 0) $sOui = "";		// ALL
else if (0 == $iOui) $sOui = " and (`ou`<{$aOuRange[0]})";
else if (sizeof($aOuRange) <= $iOui) $sOui = " and ({$aOuRange[sizeof($aOuRange)-1]}<=`ou`)";
else $sOui = " and ({$aOuRange[$iOui-1]}<=`ou`) and (`ou`<{$aOuRange[$iOui]})";

function item_format($val,$dec) {
	Global $arr_win_dir, $arr_pozip, $arr_alm;
	
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
	if (12 == $dec) {   // Special code: alm
		$val = (int)$val;
		if (($val < 0) || (1 < $val)) $val = 0;
		return $arr_alm[$val];
	}
	return $val;
}



////////////////////////////////////////////////////////////////////////////////
// Excel 다운로드
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['excel'])) {
	header("Content-Disposition: attachment; filename={$sDate1}-{$sDate2}.xls");
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
	header("Pragma: no-cache");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	
	echo  ("<html>\n<head>\n<meta http-equiv='Content-Type' content='application/vnd.ms-exel; charset=UTF-8'>\n</head>\n<body>\n");
	
	if (0 < $iDate1) {
		$cs = 2 + $selectItemNo;
		
		echo ("<table>\n");
		
		if (!isset($sId)) {
			$sUserName = 'Guest';
		} else {
			$sQuery = "select `NAME` from member where `ID`='$sId'";
			$cResult = mysql_query($sQuery) or die('EC#730105');
			$aRow = mysql_fetch_row($cResult) or die('EC#730106');
			$sUserName = ($aRow[0] != '') ? $aRow[0] : $sId;
		}
		echo ("<tr><td colspan='$cs'>$str_report_manager : $sUserName</td></tr>\n");
		
		echo ("<tr><td colspan='$cs'>$str_data_search_terms : $sDate1 ~ $sDate2</td></tr>\n");
		
		if (0 <= $iOui) {
			echo ("<tr><td colspan='$cs'>");
			if (0 < $iOui) echo ($aOuRange[$iOui-1]);
			echo ('~');
			if ($iOui < sizeof($aOuRange)) echo ($aOuRange[$iOui]);
			echo (' '.$aItemName['ou']);
			echo ("</td></tr>\n");
		}
		
		echo ("<tr align='center' style='background:#DDDDDD'><td><b>{$aItemName['SITENO']}</b></td><td><b>{$aItemName['DATE']}</b></td>");
		for ($j=0; $j<$selectItemNo; $j++) {
			$unit = $aSelectUnit[$j];
			echo ("<td><b>".$aItemName[$aSelectItem[$j]]);
			if ($unit != '') echo (" ($unit)");
			echo ("</b></td>");
		}
		echo ("</tr>\n");
		
		// Variable initialization for sum
		$aSum = array();
		$aNum = array();
		for ($i=0; $i<$selectItemNo; $i++) $aSum[$i] = $aNum[$i] = 0;
		$iLines = 0;
		
		$sQuery = "SELECT `SITENO`,`DATE`";
		foreach ($aSelectItem as $sitem) $sQuery .= ",`$sitem`";
		$sQuery .= " FROM `datas` WHERE ($iDate1<=`DATE`) and (`DATE`<=$iDate2) and (`SITENO` in (";
		for ($i=0; $i<$selectSiteNo; $i++) {
			if (0 < $i) $sQuery .= ",";
			$sQuery .= "'$aSelectSite[$i]'";
		}
		$sQuery .= "))".$sOui.$sOrder;
		$cResult = mysql_query($sQuery, $cDb);
		
		if ($cResult) {
			while ($aRow = mysql_fetch_row($cResult)) {
				echo ("<tr align='right' style='background:#EEEEEE'><td align='center'>{$aSiteName[$aRow[0]]}</td><td align='center'>");
				echo (DateIntToStr((int)$aRow[1]));
				echo ("</td>");
				for ($j=0; $j<$selectItemNo; $j++) {
					$dval = $aRow[$j+2];
					if ($dval != null) {
						echo ("<td>".item_format($dval,$aSelectDec[$j])."</td>");
						$aSum[$j] += $dval;
						$aNum[$j]++;
					} else {
						echo ("<td align='center'>-</td>");
					}
				}
				echo ("</tr>\n");
				$iLines++;
			}
			mysql_free_result($cResult);
		}
		
		echo ("<tr align='right' style='background:#DDDDDD'><td align='center'>$str_average</td><td>$iLines lines</td>");
		for ($j=0; $j<$selectItemNo; $j++) {
			$inum = $aNum[$j];
			if ((0 < $inum) && ($aSelectDec[$j] < 10)) {
				echo ("<td>".item_format($aSum[$j] / $inum, $aSelectDec[$j])."</td>");
			} else {
				echo ("<td align='center'>-</td>");
			}
		}
		echo ("</tr>\n");
		echo ("</table>\n");
	}
	
	echo ("</body>\n</html>\n");
	
	mysql_close ($cDb);
	die ();
}



////////////////////////////////////////////////////////////////////////////////
// 자료조회
////////////////////////////////////////////////////////////////////////////////

PrintHtmlHead ();

if (!$ISM) {
	echo ("<script type='text/javascript'>\n");
	echo ("try{window.parent.frames[0].fSetMenu(1,'$sId');}catch(err){}\n");
	echo ("</script>\n");
}

print <<<EOF

<style type='text/css'>
HTML { overflow-y:scroll; }
.multiselect { border:solid 1px #CCCCCC; background:#FFFFFF; overflow:auto; }
.multiselect label { display:block; }
.multiselect-on { background:#EEEEEE; }

.graphContainer {
	width: 95%; height: 300px; padding: 20px 15px 15px 15px; margin: 15px auto 30px auto; border: 1px solid #DDDDDD;
	background: #FFFFFF; background: linear-gradient(#F6F6F6 0, #FFFFFF 50px); background: -o-linear-gradient(#F6F6F6 0, #FFFFFF 50px); background: -ms-linear-gradient(#F6F6F6 0, #FFFFFF 50px); background: -moz-linear-gradient(#F6F6F6 0, #FFFFFF 50px); background: -webkit-linear-gradient(#F6F6F6 0, #FFFFFF 50px);
	box-shadow: 0 3px 10px rgba(0,0,0,0.15); -o-box-shadow: 0 3px 10px rgba(0,0,0,0.1); -ms-box-shadow: 0 3px 10px rgba(0,0,0,0.1); -moz-box-shadow: 0 3px 10px rgba(0,0,0,0.1); -webkit-box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
.graphPlaceholder {
	width: 100%; height: 100%; font-size: 14px; line-height: 1.2em;
}
.WeatherPopup {
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

<link rel='stylesheet' href='js/jquery.ui.min.css?modified=20170706' />
<link rel='stylesheet' href='js/pure.css' />
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.ui.min.js?modified=20170706'></script>
<script type='text/javascript' src='js/jquery.flot.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.time.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.resize.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.navigate.min.js'></script>
<script type='text/javascript' src='js/jquery.flot.windir.js'></script>

<script type='text/javascript'>

var maxDates = $iMaxDates;

jQuery.fn.multiselect = function() {
	$(this).each(function() {
		var checkboxes = $(this).find('input:checkbox');
		checkboxes.each(function() {
			var checkbox = $(this);
			if (checkbox.prop('checked')) checkbox.parent().addClass('multiselect-on');
			checkbox.click(function() {
				if (checkbox.prop('checked')) checkbox.parent().addClass('multiselect-on');
				else checkbox.parent().removeClass('multiselect-on');
			});
		});
	});
};

// Select all site or item.
function selectAll (obj) {
	var chk = false;
	var checkboxes = $("input[name='"+obj+"[]']");
	for (var i=0; i<checkboxes.length; i++) {
		if ($(checkboxes[i]).prop('checked') == false) chk = true;
	}
	checkboxes.each(function() {
		var checkbox = $(this);
		checkbox.prop('checked', chk);
		if (chk) checkbox.parent().addClass('multiselect-on');
		else checkbox.parent().removeClass('multiselect-on');
	});
}

function OnSubmit () {
	var d1 = new Date(daform.date1.value);
	var d2 = new Date(daform.date2.value);
	var dt = new Date();
	
	if (dt < d1) {
		MsgPopupError ('$str_data_overday', 2000);
		return;
	}
	if (d2 < d1) {
		MsgPopupError ('$str_data_minday', 2000);
		return;
	}
	
	var diff = ((d2-d1)/(1000*60*60*24))|0;
	if (maxDates <= diff) {
		MsgPopupError ('$str_data_maxday '+maxDates+'$str_day', 2000);
		return;
	}
	
	var chk = 0;
	var checkboxes = $("input[name='selectsite[]']");
	for (var i=0; i<checkboxes.length; i++) {
		if ($(checkboxes[i]).prop('checked')) chk++;
	}
	if (chk == 0) {
		MsgPopupError ('$str_data_nosite', 2000);
		return;
	}
	
	chk = 0;
	var checkboxes = $("input[name='selectitem[]']");
	for (var i=0; i<checkboxes.length; i++) {
		if ($(checkboxes[i]).prop('checked')) chk++;
	}
	if (chk == 0) {
		MsgPopupError ('$str_data_noitem', 2000);
		return;
	}
	
	daform.submit();
}

function ClickSearch () {
	daform.page.value = 0;
	daform.oui.value = -1;
	daform.order.value = 1;
	OnSubmit ();
}

function ClickReport (opt) {
	var chk = 0;
	var checkboxes = $("input[name='selectsite[]']");
	for (var i=0; i<checkboxes.length; i++) {
		if ($(checkboxes[i]).prop('checked')) chk++;
	}
	if (chk != 1) {
		MsgPopupError ('$str_data_onesite', 2000);
		return;
	}
	
	chk = 0;
	var checkboxes = $("input[name='selectitem[]']");
	for (var i=0; i<checkboxes.length; i++) {
		if ($(checkboxes[i]).prop('checked')) chk++;
	}
	if (chk == 0) {
		MsgPopupError ('$str_data_noitem', 2000);
		return;
	}

	window.open('', 'rtodor_report', 'width=1100,height=600,dependent=yes,alwaysRaised=yes,titlebar=no,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes');
	daform.target = 'rtodor_report';
	daform.action = 'pReport.php?opt='+opt;
	daform.submit();
	daform.target = '';
	daform.action = 'pData.php';
}

function SD (sid,t) {
	window.open('pEditPopup.php?sid='+sid+'&t='+t, 'rtodor_edit', 'width=200,height=200,dependent=yes,alwaysRaised=yes,titlebar=no,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no');
}

function SB (t,sitem) {
	var chk = 0;
	var checkboxes = $("input[name='selectsite[]']");
	for (var i=0; i<checkboxes.length; i++) {
		if ($(checkboxes[i]).prop('checked')) chk++;
	}
	if (chk < 1) {
		MsgPopupError ('$str_data_nosite', 2000);
		return;
	}
	
	window.open('', 'rtodor_bar', 'width=1100,height=600,dependent=yes,alwaysRaised=yes,titlebar=no,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes');
	daform.target = 'rtodor_bar';
	daform.action = 'pBarPopup.php?t='+t+'&item='+sitem;
	daform.submit();
	daform.target = '';
	daform.action = 'pData.php';
}

// Click data or graph button.
function TypeChange (type) {
	daform.type.value = type;
	OnSubmit ();
}

function PageChange (page) {
	if (page == -1) page = daform.pageselect.value;
	daform.page.value = page;
	OnSubmit ();
}

function OrderChange () {
	daform.order.value = daform.orderselect.value;
	OnSubmit ();
}

function OuChange (oui,siteid) {
	if (siteid != null) {
		var checkboxes = $("input[name='selectsite[]']");
		for (var i=0; i<checkboxes.length; i++) {
			var name = $(checkboxes[i]).prop('value');
			$(checkboxes[i]).prop('checked', name == siteid);
		}
	}
	daform.oui.value = oui;
	OnSubmit ();
}

function ExcelDown () {
	daform.action = "pData.php?excel=1";
	OnSubmit ();
	daform.action = "pData.php";
}

function WinDirSpeedToString (wds) {
	wds = (wds*1000 +0.5) |0;
	var ws = wds % 1000;		// get wind-speed from wind-direction
	wds = (wds/1000) |0;
	wds = (wds/22.5 +1.5) |0;	// 0~359 -> 1~16
	if ((wds < 1) || (16 < wds)) wds = 1;
	var msg = js_item_format(wds,10);
	if (0 < ws) msg += js_item_format((ws - 1)*0.1,1);
	return msg;
}

// More information when you hover the mouse on the graph
function fToolTip (event, pos, item) {
	if (!item) {
		$('#tooltip').hide();
		return;
	}
	
	var t = item.datapoint[0];
	var d = new Date(t);
	var msg = (1900+d.getYear())+'/'+(d.getMonth()+1)+'/'+d.getDate()+' '+d.getHours()+':'+d.getMinutes()+'<br>';
	var fy = [];
	var wds = [];
	var miny, maxy;
	
	var series = item.seriesall;
	try {
		if (typeof series.length != 'number') return;
	} catch (err) {
		return;
	}
	
	for (var i = 0; i < series.length; i++) {
		// Find the closest point to each site.
		var s = series[i];
		var points = s.datapoints.points;
		var ps = s.datapoints.pointsize;
		var ft = 600000;				// 10minute
		var y = null;
		var findx = null;
		for (var j = 0; j < points.length; j += ps) {
			var x = points[j];
			if (x === null) continue;
			var dt = Math.abs(t-x);
			if (dt < ft) {
				ft = dt;
				findx = x;
				y = points[j + 1];
			}
		}
		if (y !== null) {
			if (fy.length == 0) miny = maxy = y;
			else if (y < miny) miny = y;
			else if (maxy < y) maxy = y;
		}
		fy[i] = y;
		
		// Getting wind information at the find time.
		if ((i < arr_wind.length) && (findx !== null)) wds[i] = arr_wind[i][findx];
		else wds[i] = null;
	}
	
	if (miny == maxy) maxy += 1;
	if (0 <= miny) {
		miny = 0;
		maxy = maxy * 1.1;
	} else {
		miny -= (maxy-miny) * 0.1;
		maxy += (maxy-miny) * 0.1;
	}
	
	var lineh = 0;
	msg += "<table>";
	for (var i = 0; i < series.length; i++) {
		var s = series[i];
		var y = fy[i];
		if (y === null) continue;
		
		var bs, be, bc;
		if ((1 < fy.length) && (i == item.seriesIndex)) {
			bs = "<b>";
			be = "</b>";
			bc = "#808080";
		} else {
			bs = be = "";
			bc = "#FFFFFF";
		}
		
		if (series.length == 1) msg += "<tr><td>";
		else msg += "<tr><td>" + bs + s.label + be + "</td><td>";
		if ((fy.length == 1) || (10 <= s.dec)) {
			msg += bs;
			if (s.dec == 10) {	// Special code: wind_direction, 0~360 -> 1~16
				msg += WinDirSpeedToString (y)
			} else {
				msg += js_item_format (y, item.series.dec);
			}
			msg += be;
			if ((s.dec < 10) && (wds[i] != null)) msg += " (" + WinDirSpeedToString (wds[i]) + ")";
		} else {
			msg += "<table border=0 cellpadding=0 cellspacing=0><tr>";
			msg += "<td width='" + ((y - miny)*95/(maxy - miny) + 5) + "' bgcolor='"+bc+"'></td>";
			msg += "<td>&nbsp;" + bs + js_item_format(y,s.dec) + be;
			if (wds[i] != null) msg += " (" + WinDirSpeedToString (wds[i]) + ")";
			msg += "</td></tr></table>";
		}
		msg += "</td></tr>";
		lineh += (item.series.dec == 10) ? 23 : 19;
	}
	msg += "</table>";
	$('#tooltip').html(msg)
		.css({top:item.pageY-30-lineh, left:item.pageX-50})
		.fadeIn(200);
}

function fOnLoad() {
	var option = {
		changeYear: true,
		changeMonth: true,
		showMonthAfterYear: true,
		nextText:'',
		prevText:'',
		dayNamesMin:$str_calendar_day,
		monthNamesShort:$str_calendar_month,
		dateFormat:'yy-mm-dd'
	};
	$(function() {
		$('.multiselect').multiselect();	// checkbox Adding multi-select feature
		$( '#date1' ).datepicker(option);	// date from
		$( '#date2' ).datepicker(option);	// date to
		if (ism == 0) $('.graphContainer').resizable({handles: 's', maxHeight: 900, minHeight: 300});
	});
	
	if (document.getElementById('ttr')) {
		if (ism == 1) {					// is mobile?
			$('#ttr').css({top:50});
			ttt.onscroll = fOnScroll;
		} else {
			$('#ttr').css({top:0});
			$('#ttr').width($('#tor').width());
			for (var i=0; (i<100)&&(document.getElementById('tt'+i)); i++) $('#tt'+i).width($('#to'+i).width());
		}
	}
	fOnResize ();
}
function fOnResize() {
	if (ism == 1) {						// is mobile?
		if (window.innerHeight < document.body.scrollHeight) $('#moveLast').show();
		if (document.getElementById('ttr')) {
			$('#ttr').width($('#tor').width());
			for (var i=0; (i<100)&&(document.getElementById('tt'+i)); i++) $('#tt'+i).width($('#to'+i).width());
		}
	}
	fOnScroll ();
}
function fOnScroll() {
	var obj = document.getElementById('tor');
	if (obj) {
		var sy = getScrollY ();
		var xy = getPosition(obj,3);
		if (ism == 1) {					// is mobile?
			var sx = (ttt && ttt.scrollLeft) || ttt.scrollLeft;
			if (xy.y <= sy) {
				$('#ttr').show();
				$('#ttr').css({left:xy.x-sx});
				var x1 = sx - 5;
				var x2 = $('#ttt').width() + 5 + sx;
				var h = $('#tor').height();
				ttr.style.clip='rect(0px, '+x2+'px, '+h+'px, '+x1+'px)';
			} else $('#ttr').hide();
		} else {
			var sx = (document.documentElement && document.documentElement.scrollLeft) || document.body.scrollLeft;
			if (xy.y <= sy) {
				$('#ttr').show();
				$('#ttr').css({left:xy.x-sx});
			} else $('#ttr').hide();
		}
	}
	WeatherInfoHide ();
}
window.document.onscroll = fOnScroll;

function getPosition(el,n) {
	if (ism == 1) n -= 2;
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
function getScrollY () {
	var sy = (document.documentElement && document.documentElement.scrollTop) || document.body.scrollTop;
	if (ism == 1) sy += 50;
	return sy;
}

function WeatherInfoShow(o,msg) {
	obj = $('#WeatherPopup');
	obj.html (msg);
	obj.show ();
	var w = obj.width() + 26;
	var h = obj.height() + 28;
	var xy = getPosition (o,5);
	xy.x -= (w - $(o).width()) / 2;
	
	var sy = getScrollY ();
	if (xy.y - h <= sy) xy.y += 18;
	else xy.y -= h;
	obj.css({left:xy.x, top:xy.y});
}
function WeatherInfoHide() {
	$('#WeatherPopup').hide ();
}

function GraphAction(id, c) {
	var obj = eval('plot'+id+'g');
	if (c == 0) {
		// minus
		obj.zoomOut();
	} else
	if (c == 1) {
		// reset
		var axes = obj.getAxes(),
		xaxis = axes.xaxis.options,
		yaxis = axes.yaxis.options;
		xaxis.min = {$iJsDate1}000;
		xaxis.max = {$iJsDate2}000;
		yaxis.min = eval('ymin'+id);
		yaxis.max = eval('ymax'+id);
		obj.setupGrid();
		obj.draw();
	} else
	if (c == 2) {
		// plus
		obj.zoom();
	}
}

</script>

</head>

<body onload='fOnLoad()' onResize='fOnResize()' style='cursor:default'>

<form name='daform' method='post' action='pData.php'>
<input type='hidden' name='order' value='$iOrder'>
<input type='hidden' name='page' value='$iPage'>
<input type='hidden' name='type' value='0'>
<input type='hidden' name='oui' value='$iOui'>

EOF;

SetTailImage ();
if ($ISM) {
	SetMobileMenu ();
	SetMobileTitle ($str_data);
	echo ("<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;\n");
	echo ("<table width='100%' border='0' cellpadding='0' cellspacing='0'><tr><td>\n");
} else {
	echo ("<table align='center' width='800' border='0' cellpadding='0' cellspacing='0'><tr><td>\n");
	echo ("	<h1>$str_data</h1>\n");
	echo ("</td></tr></table>\n");
	echo ("<table align='center' width='800' border='0' cellpadding='0' cellspacing='0'><tr><td>\n");
}

print <<<EOF
	<div width='100%' class='OrangeBorder'>
	<table width='100%' border='0' cellpadding='0' cellspacing='4'>
	<tr>
		<td width='50%'><font class='OrangeFont'>$str_select_site</font> [<a href='javascript:selectAll("selectsite")'>$str_data_all</a>]</td>
		<td width='50%'><font class='OrangeFont'>$str_select_item</font> [<a href='javascript:selectAll("selectitem")'>$str_data_all</a>]</td></tr>
	<tr>
		<td class='multiselect' valign='top'>

EOF;

foreach ($aSite as $site) echo ("			<label><input type='checkbox' name='selectsite[]' value='{$site}' ".(in_array ($site, $aSelectSite)?'checked':'').">{$aSiteName[$site]}</label>\n");
echo ("		</td><td class='multiselect' valign='top'>\n");
foreach ($aItem as $item) echo ("			<label><input type='checkbox' name='selectitem[]' value='{$item}' ".(in_array ($item, $aSelectItem)?'checked':'').">{$aItemName[$item]}</label>\n");

print <<<EOF
		</td>
	</tr>
	<tr><td colspan='2'>
		<table width='100%' border='0' cellpadding='2' cellspacing='0' style='margin-top:5px;'><tr>
		<td width='1'>$str_data_search_terms</td>

EOF;

echo ("		<td width='1'><input type='text' id='date1' name='date1' value='$sDate1' size='10' readonly> ~ <input type='text' id='date2' name='date2' value='$sDate2' size='10' readonly></td>\n");
echo ("		<td width='1'><input type='button' value='$str_data_search' onclick='ClickSearch()' class='pure-button'></td>\n");
echo ("		<td width='*'>&nbsp;</td>\n");

if ((!$ISM) && (isset($sId))) {
	echo ("		<td width='1'><input type='button' value='{$str_report_day}{$str_report}' onclick='ClickReport(0)' class='pure-button'></td>\n");
	echo ("		<td width='1'><input type='button' value='{$str_report_month}{$str_report}' onclick='ClickReport(1)' class='pure-button'></td>\n");
	echo ("		<td width='1'><input type='button' value='{$str_report_year}{$str_report}' onclick='ClickReport(2)' class='pure-button'></td>\n");
	echo ("		<td width='1'><input type='button' value='{$str_report_term}{$str_report}' onclick='ClickReport(3)' class='pure-button'></td>\n");
}

print <<<EOF
		</tr></table>
	</td></tr>
	</table>
	</div>
</td></tr></table>

EOF;

flush();

function PrintTypeMenu ($n,$name) {
	Global $iType;
	$cb = ($iType == $n) ? '#444488' : '#DDEEFF';
	$ct = ($iType == $n) ? '#FFFFFF' : '#0099CC';
	echo ("<td width='1' onclick='TypeChange($n)' style='background:$cb; cursor:pointer; border-radius:10px;'><b><font color='$ct'>&nbsp; $name &nbsp;</font><b></td>\n");
}

if (!$ISM) echo ("<table align='center' width='800' border='0' cellpadding='0' cellspacing='0'><tr><td>\n");

// Searhc!
while (0 < $iDate1) {
	$iTime = MicrotimeFloat();
	
	if ($iType == 0) {
		// 'data'
		
		// Get total number and sum.
		$sQuery = "SELECT COUNT(`SITENO`)";
		foreach ($aSelectItem as $sitem) $sQuery .= ",AVG(`$sitem`)";
		$sQuery .= " FROM `datas` WHERE ($iDate1<=`DATE`) and (`DATE`<=$iDate2) and (`SITENO` in (";
		for ($i=0; $i<$selectSiteNo; $i++) {
			if (0 < $i) $sQuery .= ",";
			$sQuery .= "'$aSelectSite[$i]'";
		}
		$sQuery .= "))".$sOui;
		$cResult = mysql_query($sQuery, $cDb);
		if ($cResult == null) {
			echo ("<center>EC#730105</center>\n");
			break;
		}
		$aSumResult = mysql_fetch_row($cResult);
		mysql_free_result($cResult);
		
		// How much page?
		$iPageSize = GetSettingInt ($cDb, 'pagesize', 1000, 10, 10000);
		$iPageNo = (int)(($aSumResult[0]+$iPageSize-1) / $iPageSize);
		if (($iPage < 0) || ($iPageNo <= $iPage)) $iPage = 0;
		
		// Top menu
		echo ("<table width='100%' border='0' cellpadding='0' cellspacing='5px' height='35px' style='margin-top:16px;'><tr>\n");
		PrintTypeMenu (0, $str_data_raw);
		PrintTypeMenu (1, $str_data_graph);
		echo ("<td width='*'>&nbsp;</td>\n");
		
		echo ("<td width='1'>&nbsp; ");
		echo ("$str_data_sort ");
		echo ("<select name='orderselect' OnChange='OrderChange()'>");
		for ($i=0; $i<sizeof($aOrder); $i++) echo ("<option value='$i'".(($iOrder==$i)?" selected":"").">{$aOrder[$i]}</option>");
		echo ("</select>\n");
		echo ("</td>\n");
		
		// page selector
		echo ("<td width='1'>&nbsp; ");
		if (1 < $iPageNo) {
			$iPrevPage = $iPage - 1;
			$iNextPage = $iPage + 1;
			echo ("Page ");
			echo ("<select name='pageselect' OnChange='PageChange(-1)'>");
			if ($iPageNo < 100) {
				for ($i=0; $i<$iPageNo; $i++) echo ("<option style='direction:rtl' value='$i'".(($iPage==$i)?" selected":"").">".($i+1)."{$str_slash}{$iPageNo}</option>");
			} else {
				$pageStep = (int)($iPageNo / 20);
				for ($i=0; $i<$iPageNo; $i++) {
					if (($i < 10) || ($iPageNo-10 <= $i) || (($iPage-10 < $i) && ($i < $iPage+10)) || ($i%$pageStep == 0)) {
						echo ("<option style='direction:rtl' value='$i'".(($iPage==$i)?" selected":"").">".($i+1)."{$str_slash}{$iPageNo}</option>");
					}
				}
			}
			echo ("</select>\n");
			if (0 <= $iPrevPage) echo ("<font color='#000000' onclick='PageChange($iPrevPage)' style='cursor:pointer'>$str_move_left</font>\n");
			else echo ("<font color='#AAAAAA'>$str_move_left</font>\n");
			if ($iNextPage < $iPageNo) echo ("<font color='#000000' onclick='PageChange($iNextPage)' style='cursor:pointer'>$str_movr_right</font>\n");
			else echo ("<font color='#AAAAAA'>$str_movr_right</font>\n");
		}
		echo ("</td>\n");
		if (!$ISM) echo ("<td width='1'>&nbsp; <img src='img/dexcel.gif' border='0' onclick='ExcelDown()' style='cursor:pointer'></td>\n");
		echo ("</tr></table>\n");
		
		
		
		// GET OU range
		if (in_array('ou',$aDbItems)) {
			echo ("<div width='100%' class='OrangeBorder' style='overflow-x:auto;'>\n");
			echo ("<table width='100%' align='center' border='0' cellpadding='5' cellspacing='2'>\n");
			
			$sQuery = "SELECT `SITENO`, SUM(IF(`ou`<{$aOuRange[0]},1,0))";
			for ($i=1; $i<sizeof($aOuRange); $i++) $sQuery .= ", SUM(IF({$aOuRange[$i-1]}<=`ou` and `ou`<{$aOuRange[$i]},1,0))";
			$sQuery .= ", SUM(IF({$aOuRange[$i-1]}<=`ou`,1,0))";
			$sQuery .= " FROM `datas` WHERE ($iDate1<=`DATE`) and (`DATE`<=$iDate2) and (`SITENO` in (";
			for ($i=0; $i<$selectSiteNo; $i++) {
				if (0 < $i) $sQuery .= ",";
				$sQuery .= "'$aSelectSite[$i]'";
			}
			$sQuery .= ")) GROUP BY `SITENO`";
			$cResult = mysql_query($sQuery, $cDb);
			if ($cResult != null) {
				$ounum = sizeof($aOuRange);
				$linenum = mysql_num_rows($cResult);
				if ($linenum == 1) {
					// site number is ONE.
					if ($aRow = mysql_fetch_row($cResult)) {
						$tdw = (100/($ounum+1)).'%';
						$siteid = $aRow[0];
						for ($i=$sum=0; $i<=$ounum; $i++) $sum += $aRow[$i+1];
						
						echo ("<tr><td style='background:#DDDDDD' class='OrangeFont' colspan='$ounum'>$str_data_ou ({$aSiteName[$siteid]})</td>");
						echo ("<td style='background:#EEEEEE' align='center'>");
						echo ((-1 != $iOui) ? "<a onclick='OuChange(-1,$siteid)' style='cursor:pointer'>" : "<b>");
							echo ("$str_data_all ($sum)");
						echo ((-1 != $iOui) ? "</a>" : "</b>");
						echo ("</td></tr>\n");
						
						echo ("<tr align='center' style='background:#EEEEEE'>");
						for ($i=0; $i<=$ounum; $i++) {
							echo ("<td width='$tdw'>");
							echo (($i != $iOui) ? "<a onclick='OuChange($i,$siteid)' style='cursor:pointer'>" : "<b>");
								if (0 < $i) echo ($aOuRange[$i-1]);
								echo ('~');
								if ($i < $ounum) echo ($aOuRange[$i]);
								echo (" (".$aRow[$i+1].")");
							echo (($i != $iOui) ? "</a>" : "</b>");
							echo ("</td>");
						}
						echo ("</tr>\n");
					}
				} else
				if (1 < $linenum) {
					// 1 < site number
					$aSum = array($ounum+1);
					$tdw = (100/($ounum+3)).'%';
					echo ("<tr style='background:#DDDDDD'><td class='OrangeFont' colspan='".($ounum+3)."'>$str_data_ou</td></tr>\n");
					echo ("<tr style='background:#DDDDDD' align='center' class='OrangeFont'><td width='$tdw'>$str_data_site</td>");
					for ($i=0; $i<=$ounum; $i++) {
						$aSum[$i] = 0;
						echo ("<td width='$tdw'><b>");
							if (0 < $i) echo ($aOuRange[$i-1]);
							echo ('~');
							if ($i < $ounum) echo ($aOuRange[$i]);
						echo ("</b></td>");
					}
					echo ("<td width='$tdw' class='OrangeFont'>$str_data_all</td></tr>\n");
					
					while ($aRow = mysql_fetch_row($cResult)) {
						$siteid = $aRow[0];
						echo ("<tr style='background:#EEEEEE' align='right'><td align='center'>{$aSiteName[$siteid]}</td>");
						for ($i=$sum=0; $i<=$ounum; $i++) {
							$sum += $aRow[$i+1];
							$aSum[$i] += $aRow[$i+1];
							echo ("<td><a onclick='OuChange($i,$siteid)' style='cursor:pointer'>{$aRow[$i+1]}</a></td>");
						}
						echo ("<td><a onclick='OuChange(-1,$siteid)' style='cursor:pointer'>$sum</a></td></tr>\n");
					}
					
					echo ("<tr style='background:#DDDDDD' align='right'><td align='center'>$str_sum</td>");
					for ($i=$sum=0; $i<=$ounum; $i++) {
						$sum += $aSum[$i];
						echo ("<td>");
						echo (($i != $iOui) ? "<a onclick='OuChange($i)' style='cursor:pointer'>" : "<b>");
							echo ($aSum[$i]);
						echo (($i != $iOui) ? "</a>" : "</b>");
						echo ("</td>");
					}
					
					echo ("<td>");
					echo ((-1 != $iOui) ? "<a onclick='OuChange(-1)' style='cursor:pointer'>" : "<b>");
						echo ($sum);
					echo ((-1 != $iOui) ? "</a>" : "</b>");
					echo ("</td></tr>\n");
				} else {
					// no data
					echo ("<tr style='background:#DDDDDD'><td class='OrangeFont''>$str_data_ou</td></tr>\n");
					echo ("<tr style='background:#EEEEEE'><td>$str_nodata</td></tr>\n");
				}
				mysql_free_result($cResult);
			}
			
			echo ("</table>\n");
			echo ("</div><br>\n");
		}
		
		
		
		// Data Head
		echo ("<div id='ttt' width='100%' class='OrangeBorder' style='overflow-x:auto;'>\n");
		echo ("<div id='WeatherPopup' class='WeatherPopup'></div>\n");
		
		echo ("<table id='ttr' align='center' border='0' cellpadding='5' cellspacing='2' style='background:#FFFFFF; position:fixed; display:none;'>\n");
		echo ("<tr align='center' style='background:#DDDDDD;'><td id='tt0' class='OrangeFont'>{$aItemName['SITENO']}</td><td id='tt1' class='OrangeFont'>{$aItemName['DATE']}</td>");
		for ($i=0; $i<$selectItemNo; $i++) {
			$tdid = $i + 2;
			echo ("<td id='tt{$tdid}' class='OrangeFont'>{$aItemName[$aSelectItem[$i]]}</td>");
		}
		echo ("</tr>\n");
		echo ("</table>\n");
		
		echo ("<table id='tor' width='100%' align='center' border='0' cellpadding='5' cellspacing='2'>\n");
		echo ("<tr align='center' style='background:#DDDDDD;'><td id='to0' class='OrangeFont'>{$aItemName['SITENO']}</td><td id='to1' class='OrangeFont'>{$aItemName['DATE']}</td>");
		for ($i=0; $i<$selectItemNo; $i++) {
			$tdid = $i + 2;
			echo ("<td id='to{$tdid}' class='OrangeFont'>{$aItemName[$aSelectItem[$i]]}</td>");
		}
		echo ("</tr>\n");
		
		// Skip front
		if (0 < $iPage) {
			echo ("<tr align='center'><td>...</td><td>...</td>");
			foreach ($aSelectItem as $item) echo ("<td>...</td>");
			echo ("</tr>\n");
		}
		
		$isAdmin = ($sId == 'admin');
		
		// Print data
		$sQuery = "SELECT `SITENO`,`DATE`,`WDATA`";
		foreach ($aSelectItem as $sitem) $sQuery .= ",`$sitem`";
		$sQuery .= " FROM `datas` LEFT JOIN `weathers`";
		$sQuery .= " ON (`datas`.`WDATE`=`weathers`.`WDATE`) and (`datas`.`WXY`=`weathers`.`WXY`)";
		$sQuery .= " WHERE ($iDate1<=`DATE`) and (`DATE`<=$iDate2) and (`SITENO` in (";
		for ($i=0; $i<$selectSiteNo; $i++) {
			if (0 < $i) $sQuery .= ",";
			$sQuery .= "'$aSelectSite[$i]'";
		}
		$iPageOffset = $iPageSize * $iPage;
		$sQuery .= "))".$sOui.$sOrder;
		$sQuery .= " LIMIT $iPageSize OFFSET $iPageOffset";
		$cResult = mysql_query($sQuery, $cDb);
		if ($cResult != null) {
			while ($aRow = mysql_fetch_row($cResult)) {
				echo ("<tr align='right' style='background:#EEEEEE'><td align='center'>{$aSiteName[$aRow[0]]}</td><td align='center'>");
				if ($aRow[2] != null) {
					echo ("<font color='green' onMouseOver='WeatherInfoShow(this,");
					echo ('"'.$aRow[2].'"');
					echo (")' onMouseOut='WeatherInfoHide()'>");
				}
				echo (DateIntToStr((int)$aRow[1]));
				if ($aRow[2] != null) {
					echo ("</font>");
				}
				if ((!$ISM) && ($isAdmin)) echo ("<a onclick='SD(".$aRow[0].",".$aRow[1].")'><font color='#C0C0C0'>&#x2714;</font></a>");
				
				echo ("</td>");
				for ($j=0; $j<$selectItemNo; $j++) {
					$dval = $aRow[$j+3];
					if ($dval != null) {
						echo ('<td>'.item_format($dval,$aSelectDec[$j]).$aSelectUnit[$j]);
						if ($aSelectDec[$j] == 10) {	// Special code: wind_direction
							$dval = round($dval);
							if (($dval < 1) || (16 < $dval)) $dval = 1;
							echo (' <img src="img/windd'.$dval.'.png" class="windImg">');
						}
						if ((1 < $selectSiteNo) && ($aSelectDec[$j] != 10)) {
							echo ("<a onclick='SB(".$aRow[1].",\"".$aSelectItem[$j]."\")'>*</a>");
						}
					} else echo ('<td align="center">-');
					echo ('</td>');
				}
				echo ("</tr>\n");
			}
			mysql_free_result($cResult);
		}
		
		// Skip back
		if ($iPage < $iPageNo-1) {
			echo ("<tr align='center'><td>...</td><td>...</td>");
			foreach ($aSelectItem as $item) echo ("<td>...</td>");
			echo ("</tr>\n");
		}
		
		// Average
		echo ("<tr align='right' style='background:#DDDDDD'><td align='center'>$str_average</td><td align='center'>$aSumResult[0] lines</td>");
		for ($j=0; $j<$selectItemNo; $j++) {
			if (($aSumResult[1+$j] != null) && ($aSelectDec[$j] < 10)) {
				echo ('<td>'.item_format($aSumResult[1+$j], $aSelectDec[$j]).$aSelectUnit[$j].'</td>');
			} else {
				echo ('<td align="center">-</td>');
			}
		}
		echo ("</tr>\n");
		echo ("</table>\n");
		echo ("</div>\n");
		
	} else {
		// 'graph'
		
		$iDayNum = (int)(($iJsDate2-$iJsDate1+60)/86400);
		
		// Period to put in a single point.
		if (120 < $iDayNum) {			// 24h, num:121~
			$iGroupSize = ($iTimeType == 1) ? 24*60*60 : 10000;
			$iGraphStep = 1440;
		} else if (25 <= $iDayNum) {	//  5h, num:120~576
			$iGroupSize = ($iTimeType == 1) ? 6*60*60 : 500;
			$iGraphStep = 300;
		} else if (5 <= $iDayNum) {		// 60m, num:120~576
			$iGroupSize = ($iTimeType == 1) ? 60*60 : 100;
			$iGraphStep = 60;
		} else if (1 <  $iDayNum) {		// 10m, num:288~576
			$iGroupSize = ($iTimeType == 1) ? 10*60 : 10;
			$iGraphStep = 20;
		} else {						//  1m, num:0~1440
			$iGroupSize = ($iTimeType == 1) ? 60 : 1;
			$iGraphStep = 5;
		}
		$iGraphStep *= 60;				// minute -> second
		
		// Time format
		if (366 < $iDayNum) { $xformat = '%y/%m/%d'; $xmintic = '[1,"day"]'; }
		else if (3 < $iDayNum) { $xformat = '%m/%d'; $xmintic = '[1,"day"]'; }
		else { $xformat = '%H:%M'; $xmintic = '[1,"hour"]'; }
		
		// Calc average in iGroupSize. (AVG, MIN, MAX)
		$sQuery = "SELECT `SITENO`,`DATE`";
		for ($i=0; $i<$selectItemNo; $i++) {
			$sitem = $aSelectItem[$i];
			if ($aSelectDec[$i] == 10) $sQuery .= ",AVG(cos((`$sitem`+3)*0.3927)),AVG(sin((`$sitem`+3)*0.3927))";	// Special code: wind_direction
			else if ($aSelectDec[$i] == 11) $sQuery .= ",MAX(`$sitem`)";
			else $sQuery .= ",AVG(`$sitem`)";
		}
		$sQuery .= " FROM `datas` WHERE ($iDate1<=`DATE`) and (`DATE`<=$iDate2) and (`SITENO` in (";
		for ($i=0; $i<$selectSiteNo; $i++) {
			if (0 < $i) $sQuery .= ",";
			$sQuery .= "'$aSelectSite[$i]'";
		}
		$sQuery .= "))$sOui GROUP BY `DATE` DIV $iGroupSize,`SITENO`";
		$sQuery .= " ORDER BY `DATE`,`SITENO`";
		$cResult = mysql_query($sQuery, $cDb);
		if ($cResult == null) {
			echo ("<center>EC#730105</center>\n");
			break;
		}
		
		// Top menu
		echo ("<table width='100%' border='0' cellpadding='0' cellspacing='5px' height='35px' style='margin-top:16px;'><tr>\n");
		PrintTypeMenu (0, $str_data_raw);
		PrintTypeMenu (1, $str_data_graph);
		echo ("<td width='*'>&nbsp;</td>\n");
		
		echo ("<td width='1'>&nbsp; ");
		if (0 <= $iOui) {
			if (0 < $iOui) echo ($aOuRange[$iOui-1]);
			echo ('~');
			if ($iOui < sizeof($aOuRange)) echo ($aOuRange[$iOui]);
			echo (' '.$aItemName['ou']);
		}
		echo ("</td>\n");
		
		echo ("</tr></table>\n");
		
		// Graph body
		echo ("<div width='100%' class='OrangeBorder' style='overflow-x:auto;'>\n");
		echo ("<table width='100%' align='center' border='0' cellpadding='5' cellspacing='2'>\n");
		echo ("<tr><td>");
		for ($j=0; $j<$selectItemNo; $j++) {
			$unit = $aSelectUnit[$j];
			echo ("<div class='graphContainer'");
			if ($aSelectDec[$j] == 10) echo (" style='padding-bottom:40px'");
			echo (">");
			if (!$ISM) {
				echo ("<img src='img/zoom_minus.png' align='right' onclick='GraphAction($j,0)' style='cursor:pointer; margin-right:10px;'>");
				echo ("<img src='img/zoom_fit.png' align='right' onclick='GraphAction($j,1)' style='cursor:pointer; margin-right:2px;'>");
				echo ("<img src='img/zoom_plus.png' align='right' onclick='GraphAction($j,2)' style='cursor:pointer; margin-right:2px;'>");
			}
			echo ("<center><b>{$aItemName[$aSelectItem[$j]]}");
			if ($unit != '') echo (" ($unit)");
			echo ("</b></center><div id='graph$j' class='graphPlaceholder'></div>");
			if ($aSelectDec[$j] == 10) echo ("<center>$str_win_dir_comment</center>");
			echo ("</div>");
		}
		echo ("</td></tr>\n");
		echo ("</table>\n");
		echo ("</div>\n");
		
		// Graph data
		echo ("<script type='text/javascript'>\n");
		
		echo ("$('<div id=\"tooltip\"></div>').css({position: 'absolute',display: 'none',border: '1px solid #FFDDDD',padding: '2px','background-color': '#FFEEEE',opacity: 0.80}).appendTo('body');\n");
		
		// Lack of data, graph is disconnected.
		$iGraphStep *= 3;
		
		// check win dir, speed index
		$winddir = -1;
		$windspeed = -1;
		for ($skipn=$j=0; $j<$selectItemNo; $j++) {
			if ($aSelectDec[$j] == 10) {
				$skipn = 1;
				$winddir = 2+$j;
			}
			if ($aSelectItem[$j] == 'windspeed') $windspeed = 2+$j+$skipn;
		}
		
		echo ("var arr_wind = [");
		if (0 <= $winddir) {
			for ($si=0; $si<$selectSiteNo; $si++) {
				echo ("{");
				$ssite = $aSelectSite[$si];
				@mysql_data_seek($cResult,0);
				while ($aRow = mysql_fetch_row($cResult)) {
					if ($aRow[0] != $ssite) continue;
					if ($aRow[$winddir] != null) {
						$dval = (int)(atan2($aRow[1+$winddir],$aRow[$winddir]) * 57.29578 - 90);
						if (359 < $dval) $dval -= 360;
						if ($dval < 0) $dval += 360;
						if (0 <= $windspeed) {
							$dval += $aRow[$windspeed]*0.01 + 0.001;
						}
						$thissec = IntToJs((int)$aRow[1]);
						echo ("{$thissec}000:$dval,");
					}
				}
				echo ("},");
			}
		}
		echo ("];\n");
		
		for ($skipn=$j=0; $j<$selectItemNo; $j++) {
			$itemid = $aSelectItem[$j];
			$dec = $aSelectDec[$j];
			if ($dec == 10) $skipn = 1;
			echo ("var plot{$j}g = $.plot('#graph$j',[");
			for ($si=0; $si<$selectSiteNo; $si++) {
				$ssite = $aSelectSite[$si];
				if (0 < $si) echo (",");
				echo ("{label:'{$aSiteName[$ssite]}',dec:'{$dec}',data:[");
				@mysql_data_seek($cResult,0);
				$oldsec = 0;
				while ($aRow = mysql_fetch_row($cResult)) {
					if ($aRow[0] != $ssite) continue;
					$dval = $aRow[2+$j+$skipn];
					
					if ($dval != null) {
						if ($dec == 10) {	// Special code: wind_direction
							$dval = (int)(atan2($aRow[3+$j],$aRow[2+$j]) * 57.29578 - 90);
							if (359 < $dval) $dval -= 360;
							if ($dval < 0) $dval += 360;
							if (0 <= $windspeed) {
								$dval += $aRow[$windspeed]*0.01 + 0.001;
							}
						} else
						if ((0 <= $dec) && ($dec <= 9)) {
							$dval = round($dval,$dec);
						}
						$thissec = IntToJs((int)$aRow[1]);
						if ((0 < $oldsec) && ($iGraphStep < $thissec - $oldsec)) echo ('null,');
						$oldsec = $thissec;
						echo ("[{$thissec}000,$dval],");
					} else echo ('null,');
				}
				echo ("]}\n");
			}
			echo ("],{");
			echo ("	legend: { position:'ne' },");
			
			// warning/danger background
			$lo = $aItemLo[$itemid];
			$hi = $aItemHi[$itemid];
			echo ("	grid: { hoverable:true, autoHighlight:true, margin:{bottom:10,top:0}");	// , backgroundColor: { colors: ['#FFFFFF','#EEEEEE'] }
			if (($lo !== null) && ($hi !== null)) {
				if ($hi < $lo) echo (",markings:[{color:'#FFFFC0',yaxis:{from:$hi,to:$lo}},{color:'#FFC0C0',yaxis:{from:$lo}}]");
				else echo (",markings:[{color:'#FFC0C0',yaxis:{from:$hi}},{color:'#FFC0C0',yaxis:{to:$lo}}]");
			} else if ($lo !== null) echo (",markings:[{color:'#FFC0C0',yaxis:{to:$lo}}]");
			else if ($hi !== null) echo (",markings:[{color:'#FFC0C0',yaxis:{from:$hi}}]");
			echo (" },");
			
			echo ("	xaxis:{ mode:'time', timezone:'browser', timeformat:'$xformat', minTickSize:{$xmintic}, min:{$iJsDate1}000, max:{$iJsDate2}000, panRange:[{$iJsDate1}000,{$iJsDate2}000] },");
			
			// y-axis range
			$bot = $aItemBot[$itemid];
			$top = $aItemTop[$itemid];
			$ymin = $ymax = 'null';
			if ($dec == 10) { $ymin = -25; $ymax = 385; }
			if ($dec == 11) { $ymin =   0; $ymax =   2; }
			if ($bot !== null) $ymin = $bot;
			if ($top !== null) $ymax = $top;
			echo ("	yaxis:{ min:$ymin, max:$ymax},");
			
			if ($dec <  10) echo ("	lines: { show: true },");
			else echo ("	lines: { show: false },");
			
			if ($dec == 10) echo ("	points: { show:true, radius:0, winddir:'wd'},");
			else echo ("	points: { show: true },");
			
			if (!$ISM) {
				// echo ("zoom: { interactive: true },");
				echo ("pan: { interactive: true }");
			}
			
			echo ("});\n");
			echo ("$('#graph$j').bind('plothover', fToolTip);\n");
			
			echo ("var ymin$j = $ymin;\n");
			echo ("var ymax$j = $ymax;\n");
		}
		mysql_free_result($cResult);
		
		echo ("</script>\n");
	}
	
	$iTime = MicrotimeFloat() - $iTime;
	echo ("<p>[ ".number_format($iTime,3)."sec ]</p>\n");
	
	break;
}

if (!$ISM) echo ("</td></tr></table>\n");

print <<<EOF
</form>

<div class='TopCurtain'></div>
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
