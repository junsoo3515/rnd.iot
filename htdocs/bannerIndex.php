<?
require_once('common.php');


// MySQL info
$sDbIp ='127.0.0.1:3311';	// MySQL IP localhost
$sDbId = 'root';			// MySQL User ID
$sDbPw = 'root';			// MySQL User Password

// DataBase name
$sDbDb = 'rtodor';

// DB items : Using DB creation. name[16]
$aDbItems   = array ('ou', 'oi', 'signal', 'sout1', 'sout2', 'sout3', 'sout4', 'H2S', 'NH3', 'CH3SH', 'VOC', 'temperature', 'humidity', 'winddirection', 'windspeed', 'pozip', 'ou_Alm', 'H2S_Alm', 'NH3_Alm', 'VOC_Alm', 'atm');


// connect db //////////////////////////////////////////////////////////////////
$cDb = mysql_connect($sDbIp, $sDbId, $sDbPw);
$iRtOld    = GetSettingInt ($cDb, 'rtold',1, 1, 10000) * 3600;
$iIsGps    = GetSettingInt ($cDb, 'isgps', 0, 0, 1);
$iIsGuest  = GetSettingInt ($cDb, 'isguest', 1, 0, 1);
$sId = $_SESSION[$sDbDb.'_id'];

if (!$cDb) die ('EC#730008');
if (!$cDb) {
	LogMsg ('Can not connect db : '.mysql_error());
	die();
}
mysql_query('set names utf8');
if (!mysql_select_db($sDbDb, $cDb)) {
	$sIpOnly = explode(":", $sDbIp);
	if ($_SERVER['REMOTE_ADDR'] === $sIpOnly[0]) {
	} else {
		LogMsg ('Can not select db : '.mysql_error());
	}
	mysql_close ($cDb);
	die();
}

// Ajax.Get realtime values ////////////////////////////////////////////////////
if ($_GET['q'] == 'rt') {
	header("Content-Type: text/html; charset=UTF-8");
	
	if (($iIsGuest == 0) && (!isset($sId))) {
		mysql_close ($cDb);
		die('EC#7304034');	// Error anonymous access
	}
	
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
		$sQuery .= " FROM `lasts` ORDER BY `SITENO`";
	
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
?>
<!DOCTYPE html>
<html>
<head>
<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=UTF-8'>
<meta name='viewport' content='user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=device-width'>
<title>부산강서구청</title>
<style type='text/css'>

html, body { height: 100%; margin: 0; padding: 0; }

BODY,TD,P,DIV,INPUT,SELECT,TEXTAREA {
	font-family:Tahoma,새굴림;
	font-size:12px;
	white-space:nowrap;
}
A {
	color:#4444FF;
	cursor:pointer;
	text-decoration:none;
}
A:hover {
	text-decoration:underline;
}
INPUT,SELECT,TEXTAREA {
	color:#000000;
	background:#FFFFFF;
	padding:2px;
	border:1px solid #BBBBBB;
}
INPUT:-moz-read-only {
	background:#DDDDDD;
	border:1px solid #DDDDDD;
}
INPUT:read-only {
	background:#DDDDDD;
	border:1px solid #DDDDDD;
}
.TopCurtain {
	position:fixed; top:0px; left:0; right:0; height:16px;
	background:linear-gradient(rgba(0,0,0,0.1),rgba(0,0,0,0));
	pointer-events: none;
}
.ReadBorder {
	border-width:3px;
	border-style:solid;
	padding:5px;
	border-color:#FF8888;
	background:#FFEEEE;
}
.OrangeBorder {
	border-width:3px;
	border-style:solid;
	padding:5px;
	border-color:#FFBB88;
	background:#FFFFFF;
}
.OrangeFont {
	color:#EE6633;
	font-weight:bold;
}
.TitleFont {
	color:#666666;
	font-weight:bold;
}
.SubMenu {
	color:#444444;
	font-weight:bold;
	cursor:pointer;
	border-radius:7px;
}
.SubMenu:hover {
	background:#DDDDDD;
}
.SubMenuSel {
	color:#444444;
	background:#FFFFFF;
	font-weight:bold;
	cursor:pointer;
	border-radius:7px;
}

.msgPopupBox {
	z-index:3;
	display: none;
	position:fixed;
	margin:0;
	top:0;
	left:0;
	right:0;
	bottom:0;
	height:100%;
	width:100%;
	background-color:rgba(0,0,0,0.7);
}
.msgPopupInn {
	display: table-cell;
	vertical-align: middle;
	text-align:center;
}
.msgPopupTxt {
	color:#FFFFFF;
	border-left:1px solid #888888;
	border-right:3px solid #888888;
	border-top:1px solid #888888;
	border-bottom:3px solid #888888;
	background:#EE4444;
	padding:15px;
	border-radius:10px;
	text-align:center;
}

.mMenuLogin {
	padding-left:6px;
	padding-right:6px;
	padding-top:4px;
	padding-bottom:4px;
	margin-right:10px;
	margin-left:10px;
	font-weight:bold;
	color:#FFFFFF;
	border-radius:20px;
	border:2px solid #FFFFFF;
}
.mMenuItem {
	cursor:pointer;
	padding-top:12px;
	padding-bottom:12px;
	padding-left:16px;
	padding-right:32px;
	font-weight:bold;
	background:#FFFFFF;
}
.mMoveLast {
	background:rgba(255,255,255,0.7);
	border-radius:10px;
	border:1px solid rgba(0,0,0,0.5);
	color:rgba(0,0,0,0.5);
}

.windImg {
	width:16px;
	height:16px;
	vertical-align:middle;
}

</style>

</head>
<script type='text/javascript'>



window.addEventListener('error', function(e) {
	if ((document.getElementById('map') != null) && (map.innerHTML == '')) {
		// DAUM MAP loading error
		map.innerHTML = "<table width='100%' height='100%'><tr><td align='center'><h3>Wait...</h3></td></tr></table>";
//		setTimeout ('location.reload()',3000);
	} else {
		var msg = 'EC#'+e.lineno+':'+e.colno+'\n'+e.message;
		if (document.getElementById('msgPopupTxt') != null) {
			msg = '<p>'+msg+'</p><p>'+e.message+'</p>';
			msg += "<input type='button' value='Close' onclick='MsgPopupHide()' class='pure-button'> <input type='button' value='Reload' onclick='location.reload()' class='pure-button'>";
			MsgPopupShow (msg, '#008800', 0);
		} else {
			alert (msg);
		}
	}
}, true);

// Prohibited area selection.
function ReturnFalse () {
	return false;
}
window.document.onselectstart = ReturnFalse;
window.document.ondragstart   = ReturnFalse;

function PrintAll (obj) {
	var msg = ''
	for (propName in obj) try { msg += propName+'='+obj[propName]+'\n'; } catch (err) { }
	alert(msg);
}

function SetCookie(cKey, cValue) {
	document.cookie = cKey + '=' + escape(cValue);
}
function SetCookie(cKey, cValue, iValid) {
	var date = new Date();
	date.setDate(date.getDate() + iValid);
	document.cookie = cKey + '=' + escape(cValue) + ';expires=' + date.toGMTString();
}
function DelCookie(cKey) {
	var date = new Date();
	date.setDate(date.getDate() - 1);
	document.cookie = cKey + '=;expires=' + date.toGMTString();
}
function GetCookie(cKey, cDef) {
	var cookies = document.cookie.split("; ");
	for (var i = 0; i < cookies.length; i++) {
		var keyValues = cookies[i].split("=");
		if ((0 < keyValues.length) && (keyValues[0] == cKey)) return unescape(keyValues[1]);
	}
	return cDef;
}

var hMsgTimeout = null;
function MsgPopupShow (msg, c, ms) {
	$('#msgPopupTxt').html(msg);
	$('#msgPopupTxt').css('background',c);
	$('#msgPopupBox').css('display','table');
	if (0 < ms) hMsgTimeout = setTimeout (MsgPopupHideGo,ms);
}
function MsgPopupHideGo () {
	hMsgTimeout = null;
	$('#msgPopupBox').fadeOut();
}
function MsgPopupHide () {
	if (hMsgTimeout != null) clearTimeout(hMsgTimeout);
	MsgPopupHideGo ();
}

function js_number_format (num, decimals, zero) {
	if (typeof num != 'number') return '';
	num = num.toFixed(decimals);
	var reg = /(^[+-]?\d+)(\d{3})/;
	var tmp = num.split('.');
	var n = tmp[0];
	var d = tmp[1];
	if (d) {
		var l = d.length;
		if (zero === 0) {
			while (0 < l) {
				if (d.charAt(l-1) != '0') break;
				d = d.substring (0, --l);
			}
		}
		d = (0<l) ? ('.' + d) : '';
	} else {
		d = '';
	}
	
	while(reg.test(n)) n = n.replace(reg, "$1,$2");
	return n + d;
}
var arr_win_dir = ['북','북북서','북서','서북서','서','서남서','남서','남남서','남','남남동','남동','동남동','동','동북동','북동','북북동'];
var arr_pozip = ['포집준비','포집완료'];

function js_item_format	(val, dec, zero) {
	if ((0 <= dec) && (dec <= 9)) {
		return js_number_format (val, dec, zero);
	}
	if (10 == dec) {	// Special code: wind_direction
		if (typeof val != 'number') val = parseInt(val);
		else val = val.toFixed();
		if ((val < 1) || (16 < val)) val = 1;
		return arr_win_dir[val-1] + " <img src='img/windd"+val+".png' class='windImg'>";
	}
	if (11 == dec) {	// Special code: pozip
		if (typeof val != 'number') val = parseInt(val);
		else val = val.toFixed();
		if ((val < 0) || (1 < val)) val = 0;
		return arr_pozip[val];
	}
	return val;
}
parent.document.title = '부산강서구청';
</script>

<link href="boot/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="boot/vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
<link href='https://fonts.googleapis.com/css?family=Kaushan+Script' rel='stylesheet' type='text/css'>
<link href='https://fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
<link href='https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700' rel='stylesheet' type='text/css'>
<link href="boot/css/agency.min.css" rel="stylesheet">
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type='text/javascript'>
var sitearr = [];
var itemarr = [];
</script>
<?php
echo ("<script type='text/javascript'>\n");
// Get all site
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
	// 180427 지점 정보 조회 시 WEATHER_FL 추가
	$sQuery = "SELECT `SITENO`,`NAME`,`LNG`,`LAT`,`ADDR`,`REMARK`, `WEATHER_FL` FROM `sites` WHERE `FILE` is NULL ORDER BY `SITENO`";
}
$cResult = mysql_query($sQuery, $cDb);
if ($cResult != null) {
	for ($i=0; $aRow = mysql_fetch_row($cResult); $i++) {
		echo ("sitearr[$i] = {'id':'$aRow[0]', 'name':'$aRow[1]', 'lng':'$aRow[2]', 'lat':'$aRow[3]', 'addr':'$aRow[4]', 'remark':'$aRow[5]', 'weatherFl':'$aRow[6]', 'date':null, 'old':0, 'data':[], 'wdata':null};\n");
		$aSiteId[] = $aRow[0];
		$aSiteName[] = $aRow[1];
		$aSiteRemark[] = $aRow[5];
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
$sQuery = "SELECT `ID`,`NAME`,`UNIT`,`DEC`,`LO`,`HI`,`BOTTOM`,`TOP`,`REMARK`,`USING` FROM `items` WHERE `USING`!='0' ORDER BY `ORDER`";
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
?>

<script type='text/javascript'>

var selectMarkerNo = -1;	// Selected site number. (-2:N/A, -1:All, 0<=:site)
var prevSelectMarkerNo;

var map;
var markers = [];
var popups = [];

var old_siteName=[];
var old_siteNamec=[];
var test=[];


// <body> OnLoad
function fOnLoad() {
	// left item list : select 'View all'
	SelectItem (-2);
	
	// update realtime data
	UpdateRealtime();
	setInterval('UpdateRealtime()',10000);

	$('#map' ).show();
	$('#site').show();
	
	MapRelayout();
	//alert("test");
	// bottom size list : select 'View all'
	SelectMarker (-1,1);
}


function ItemInfoShow(idx) {
	var obj = itemarr[idx];
	var msg = '';
	if ((obj.lo != null) && (obj.hi != null) && (obj.hi < obj.lo)) {
		msg += '<br>주의:' + js_item_format(obj.hi, obj.dec, 0)+obj.unit + ', 경고:' + js_item_format(obj.lo, obj.dec, 0)+obj.unit;
	} else
	if ((obj.lo != null) || (obj.hi != null)) {
		msg += '<br>기준값 : ';
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
	var x = window.event.x - (w / 2);
	//if (resizeX2 < x+w) x = resizeX2 - w;
	obj.css({left:x, top:window.event.y-h});
}
function ItemInfoHide() {
	$('#msgPopup').hide ();
}

function SiteInfoShow(idx) {
	if (sitearr[idx].old == 0) return;
	
	var obj = $('#msgPopup');
	obj.css ('background-color','rgba(204,64,64,0.8)');
	obj.html ("OldData : " + sitearr[idx].date);
	obj.show ();
	ItemInfoMove ();
}

function ChangeFontSize(){
	var a=document.getElementsByClassName('fSize');
	for(var i=0;i<a.length;i++){
		a[i].style.fontSize= fSize.value+"px";
	}
	
	var a=document.getElementsByClassName('OrangeFont');
	for(var i=0;i<a.length;i++){
		a[i].style.fontSize= fSize.value+"px";
	}
	
	var a=document.getElementsByClassName('SubMenu');
	for(var i=0;i<a.length;i++){
		a[i].style.fontSize= fSize.value+"px";
	}
	
	var a=document.getElementsByClassName('windImg');
	for(var i=0;i<a.length;i++){
		a[i].style.width= fSize.value+"px";
		a[i].style.height= fSize.value+"px";
	}
	
}
function webSite(){
	location.href='./bannerIndex.php';	
}





function makeMarkerClickListener(idx) {
	return function() {
		if (idx < sitearr.length) {
			if (selectMarkerNo != idx) SelectMarker (idx);
			else SelectMarker (-2);
		} else
		if (0 == 0) {
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

function ActionMapClick() {
	return function(mouseEvent) {
		if (selectMarkerNo != -2) SelectMarker (-2);
		else SelectMarker (-1);
	};
}

function ActionMapZoomStart() {
	return function() {
		prevSelectMarkerNo = selectMarkerNo;
		if (0 <= selectMarkerNo) MapInfowindowClose ();
		if (-1 == selectMarkerNo) ClosePopups ();
	};
}
function ActionMapZoomEnd() {
	return function() {
		if (0 <= prevSelectMarkerNo) ShowInfo (prevSelectMarkerNo);
		if (-1 == prevSelectMarkerNo) ShowPopups ();
	};
}

</script>

<script type='text/javascript'>
google.charts.load('current', {packages:['corechart']});

// 0:green, 1:gray, 2:orange, 3:red

function CheckMarkerColor (idx) {
	var rv = 0;
	var nacnt = 0;
	for (var i=0; i<itemarr.length; i++) {
		if ((selectItemNo == -1) || (selectItemNo == i)) {
			var ival = (idx < sitearr.length) ? sitearr[idx].data[i] : paths[idx - sitearr.length][4+i];
			if (ival == null) nacnt++;
			else if ((itemarr[i].lo != null) && (itemarr[i].hi != null) && (itemarr[i].hi < itemarr[i].lo)) {
				if (itemarr[i].lo < ival) rv = 3;
				else if ((itemarr[i].hi < ival) && (rv < 2)) rv = 2;
			} else if (((itemarr[i].lo != null) && (ival < itemarr[i].lo)) || ((itemarr[i].hi != null) && (itemarr[i].hi < ival))) rv = 3;
		} else nacnt++;
	}
	if (nacnt == itemarr.length) rv = 1;
	
	// site & olddata -> Warning
	if ((idx < sitearr.length) && (sitearr[idx].old != 0)) rv = 3;
	
	return rv;
}

function UpdateMarkersColor (n) {
	for (var i=0; i<n; i++) {
		var rv = CheckMarkerColor (i);
		MapMarkerColor (i, rv);
	}
}

// Show detail information
var selectItemNo = 0;	// Selected item number. (-1:All, 0<=:item)

// bar graph width
function CalcBarW (ival, hi, lo) {
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

// 180427 MakeInfoTd 함수에 기상장치 유무(weatherFl) 파라미터 추가
function MakeInfoTd (ival,i,def, weatherFl) {
	var msg = "<tr><td>"+itemarr[i].name+"</td>";
	
	if ((ival === null) || (ival === '')) {
		if (def == '') return '';
		return msg + "<td class='popupdate'>" + def + "</td></tr>";
	}
	
	var lo = itemarr[i].lo;
	var hi = itemarr[i].hi;
	// 180427 측정항목이 기상정보 인지 확인을 위한 변수값 가져오기
	var id = itemarr[i].id;
	if ((lo == null) && (hi == null)) {
		// 180427 기상장치 유무에 따른 -(하이픈) 처리
		if((id == 'temperature' || id == 'humidity' || id == 'winddirection' || id == 'windspeed') && weatherFl == 'N' ) {
			return msg + "<td><font color='black'>-</font></td></tr>";
		}
		else {
			return msg + "<td><font color='black'>"+js_item_format(ival,itemarr[i].dec)+"</font>"+itemarr[i].unit+"</td></tr>";
		}
	}
	
	// bar graph
	var cc = CalcBarMsg (ival, hi, lo);
	var barw = CalcBarW (ival, hi, lo);
	msg += "<td><table border=0 cellpadding=0 cellspacing=0><tr>";
	msg += "<td width='" + barw + "' bgcolor='" + cc + "'></td>";
	msg += "<td><font color='" + cc + "'>&nbsp;"+js_item_format(ival,itemarr[i].dec) + itemarr[i].unit + "</font></td>";
	msg += "</tr></table></td>";
	msg += "</tr>";
	return msg;
}

function ShowInfo (idx) {
	var msg;
	// 180427 sitearr[idx] 값 sobj에 담기
	var sobj = sitearr[idx];
	
	if (idx < sitearr.length) {
		// site
		if ((sitearr[idx].date != null) && (sitearr[idx].date != '')) {
			//msg = "<table width='100%' bgcolor='#";
			//msg += (sitearr[idx].old != 0) ? "d95050" : "50A050";
			msg = "<table width='100%' style='background-color:#";
			msg += (sitearr[idx].old != 0) ? "d95050" : "50A050";
			msg += ";color:#ffffff; cursor:pointer;'><tr><td><b>"+sitearr[idx].name+"</b>";
			msg += "<img src='img/right.png' width='15' height='15' border='0' align='right'></td></tr>";
			msg += "<tr><td align='right'>";
			msg += (sitearr[idx].old != 0) ? "OldData" : "Update";
			msg += " : "+sitearr[idx].date+"</td></tr>";
			msg += "</table><table width='100%'>";
			// 180427 MakeInfoTd 함수에 기상장치 유무(sobj.weatherFl) 파라미터 추가
			if (selectItemNo == -1) {
				for (var i=0; i<itemarr.length; i++) msg += MakeInfoTd (sitearr[idx].data[i], i, '', sobj.weatherFl);
			} else {
				msg += MakeInfoTd (sitearr[idx].data[selectItemNo], selectItemNo, 'No data', sobj.weatherFl);
			}
			msg += "</table>";
			if ((1 == 1) && (sitearr[idx].wdata != null) && (sitearr[idx].wdata != '')) {
				msg += '<table width=\"100%\"><tr><td class=\"popupdate\">'+sitearr[idx].wdata+'</td></tr></table>';
			}
		} else {
			msg = "<table width='100%'><tr><td><b>"+sitearr[idx].name+"</b></td></tr>";
			msg += "<tr><td class='popupdate'>No data</td></tr></table>";
		}
	} else {
		// path
		var p = idx - sitearr.length;
		msg = "<table width='100%' bgcolor='#E8E8E8'><tr><td><b>"+sitearr[pathId].name+"</b></td></tr>";
		msg += "<tr><td class='popupdate'>Update : "+paths[p][0];
		if (0 == 1) {
			var lat = paths[p][2];
			var lng = paths[p][1];
			if ((lat != null) && (lat != '') && (lat != 0) && (lng != null) && (lng != '') && (lng != 0)) msg += "<br>"+js_item_format(lat,5)+", "+js_item_format(lng,5);
		}
		msg += "</td></tr></table><table width='100%'>";
		if (selectItemNo == -1) {
			for (var i=0; i<itemarr.length; i++) msg += MakeInfoTd (paths[p][4+i], i, '');
		} else {
			msg += MakeInfoTd (paths[p][4+selectItemNo], selectItemNo, 'No data');
		}
		msg += "</table>";
		if ((1 == 1) && (paths[p][3] != null)) {
			msg += '<table width=\"100%\"><tr><td class=\"popupdate\">'+paths[p][3]+'</td></tr></table>';
		}
	}
	MapInfowindowShow (idx < markers.length ? idx : pathId, msg);
}

function ShowPopup (idx) {
	var msg;
	if ((sitearr[idx].date != null) && (sitearr[idx].date != '')) {
		if (sitearr[idx].old != 0) {
			msg = "<table width='100%' bgcolor='#d95050' style='color:#ffffff;'>";
			msg += "<tr><td><b>"+sitearr[idx].name+"</b></td></tr>";
			msg += "<tr><td align='right'>OldData : "+sitearr[idx].date+"</td></tr>";
			msg += "</table>";
		} else {
			msg = "<table width='100%'><tr><td><b>"+sitearr[idx].name+"</b></td></tr></table>";
			msg += "<table width='100%'>";
			if (selectItemNo == -1) {
				var tdmsg = "";
				for (var i=0; i<itemarr.length; i++) {
					if (itemarr[i].using == '1') tdmsg += MakeInfoTd (sitearr[idx].data[i], i, '');
				}
				if (tdmsg == "") {
					for (var i=0; i<itemarr.length; i++) tdmsg += MakeInfoTd (sitearr[idx].data[i], i, '');
				}
				msg += tdmsg;
			} else {
				msg += MakeInfoTd (sitearr[idx].data[selectItemNo], selectItemNo, 'No data');
			}
			msg += "</table>";
		}
	} else {
		msg = "<table width='100%'><tr><td><b>"+sitearr[idx].name+"</b></td></tr>";
		msg += "<tr><td class='popupdate'>No data</td></tr>";
		msg += "</table>";
	}
	MapPopupShow (idx, msg);
}
function ShowPopups () {
	var n=0;
	for (var i=0; i<itemarr.length; i++) {
		if (itemarr[i].using == '1') n++;
	}
if (0<n) for (var i=0; i<popups.length; i++) ShowPopup (i);
}
function ClosePopups () {
	for (var i=0; i<popups.length; i++) MapPopupClose (i);
}

// Selected 'site' or 'path'.
function SelectMarker (idx,opt) {
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
	}
}

// Click 'item' at the left list
function SelectItem (idx) {
	if (-1 <= idx) {
		$('#lItem'+selectItemNo).css('background','');
		selectItemNo = idx;
		UpdateMarkersColor (markers.length);
		if (0 <= selectMarkerNo) ShowInfo (selectMarkerNo);
		if (-1 == selectMarkerNo) ShowPopups ();
	} else {
		selectItemNo = -1;
	}
	$('#lItem'+selectItemNo).css('background','#FFFFFF');
}

function js_number_format (num, decimals, zero) {
	if (typeof num != 'number') return '';
	num = num.toFixed(decimals);
	var reg = /(^[+-]?\d+)(\d{3})/;
	var tmp = num.split('.');
	var n = tmp[0];
	var d = tmp[1];
	if (d) {
		var l = d.length;
		if (zero === 0) {
			while (0 < l) {
				if (d.charAt(l-1) != '0') break;
				d = d.substring (0, --l);
			}
		}
		d = (0<l) ? ('.' + d) : '';
	} else {
		d = '';
	}
	
	while(reg.test(n)) n = n.replace(reg, "$1,$2");
	return n + d;
}
var arr_win_dir = ['북','북북서','북서','서북서','서','서남서','남서','남남서','남','남남동','남동','동남동','동','동북동','북동','북북동'];
var arr_pozip = ['포집준비','포집완료'];

function js_item_format	(val, dec, zero) {
	if ((0 <= dec) && (dec <= 9)) {
		return js_number_format (val, dec, zero);
	}
	if (10 == dec) {	// Special code: wind_direction
		if (typeof val != 'number') val = parseInt(val);
		else val = val.toFixed();
		if ((val < 1) || (16 < val)) val = 1;
		return arr_win_dir[val-1] + " <img src='img/windd"+val+".png' class='windImg'>";
	}
	if (11 == dec) {	// Special code: pozip
		if (typeof val != 'number') val = parseInt(val);
		else val = val.toFixed();
		if ((val < 0) || (1 < val)) val = 0;
		return arr_pozip[val];
	}
	return val;
}







var gUpdateRealtimeBusy = 0;	// check busy (0<)
var siteDataarr = [];


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
		url:"bannerIndex.php?q=rt",
		type:"post",
		data:dataarr,
		cache: false,
		error: function(xhr, status, msg) {
			gUpdateRealtimeBusy = 0;
		},
		success : function(msg) {
			if (msg == 'EC#730403') {
				// logout : Error anonymous access
				//setTimeout ('document.location.replace("pRealtime.php?login=1")',2000);
				return;
			}
			var token = msg.split (";");
			if ((token.length-1)%(itemarr.length+6) != 0) {
				// change site or item
				setTimeout ('document.location.replace("bannerIndex.php")',2000);
				return;
			}

			var isMove = false;
			var p = 1;
			
			siteDataarr = [];
			//$('#time').html('$str_update : '+token[0]);
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
										if(x == 0) siteDataarr[i] = token[p+6];
									} else
									if ((obj.lo == null) && (obj.hi == null)) {
										$(idname+'_'+x).html(js_item_format(ival,obj.dec)+obj.unit);
										if(x == 0) siteDataarr[i] = token[p+6];
									} else {
										var cc = CalcBarMsg (ival, obj.hi, obj.lo);
										var barw = CalcBarW (ival, obj.hi, obj.lo, obj.bot, obj.top);
										var cellmsg = "<font color='"+cc+"'>"+js_item_format(ival,obj.dec)+obj.unit+"</font>";
										$(idname+'_'+x).html(cellmsg);
										if(x == 0) siteDataarr[i] = token[p+6];
									}
								}
							} else {
								sobj.data[x] = '';
								if ((obj.using == '1') || (obj.using == '2')) {
									$(idname+'_'+x).html('');
									if(x == 0) siteDataarr[i] = '0';
								}
							}
						}
						break;
					}
					
					if(siteDataarr[i] == undefined) siteDataarr[i] = '0';
				}
				p += itemarr.length + 6;
			}
			
			if ((0 <= selectMarkerNo) && (selectMarkerNo < sitearr.length)) ShowInfo (selectMarkerNo);	// update maker information
			if (selectMarkerNo == -1) ShowPopups ();
			
			gUpdateRealtimeBusy = 0;
			if (isMove) MapMarkerShowAll (0);
			google.charts.setOnLoadCallback(drawChart);
			UpdateMarkersColor (sitearr.length);
		}
	});
}

    function drawChart() {
		
		var graph1arr = [];
		var graph2arr = [];
		
		var tmp = ['Elements','악취희석배수'];
		graph1arr.push(tmp);
		graph2arr.push(tmp);
		tmp = [];
		for(var i = 0; i < sitearr.length; i++) {
			if(sitearr[i].remark == '부지경계') {
				tmp.push(sitearr[i].name);
				tmp.push(parseFloat(siteDataarr[i]));
				graph1arr.push(tmp);
				tmp = [];
			}
			else {
				tmp.push(sitearr[i].name);
				tmp.push(parseFloat(siteDataarr[i]));
				graph2arr.push(tmp);
				tmp = [];
			}
		}
		
		if(graph1arr.length == 1) {
			graph1arr.push(['데이터가 없습니다.',0]);
		}
		
		if(graph2arr.length == 1) {
			graph2arr.push(['데이터가 없습니다.',0]);
		}
		
		var data = google.visualization.arrayToDataTable(graph1arr);
		
		var data2 = google.visualization.arrayToDataTable(graph2arr);
		
		var options = {
			title: "악취희석배수 (OU)/부지경계",
			width: 1200,
			height: 400
		
		};
		
		var options2 = {
			title: "악취희석배수 (OU)/배출구,기타지역",
			width: 1200,
			height: 400
	
		};
	
		var chart = new google.visualization.ColumnChart(document.getElementById("graph1"));
		var chart2 = new google.visualization.ColumnChart(document.getElementById("graph2"));
		chart.draw(data,options);
		chart2.draw(data2, options2);
  }


	
</script>


<body id="page-top" class="index" onLoad='fOnLoad()'>


    <!-- Navigation -->
    <nav id="mainNav" class="navbar navbar-default navbar-custom navbar-fixed-top">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header page-scroll">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span> Menu <i class="fa fa-bars"></i>
                </button>
                <a class="navbar-brand page-scroll" href="#page-top" >부산 강서구청</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right">
                    <li class="hidden">
                        <a href="#page-top"></a>
                    </li>
                    <li>
                        <a class="page-scroll" href="#services">Odor Info</a>
                    </li>
                    <li>
                        <a class="page-scroll" href="#portfolio">Site</a>
                    </li>
                    <li>
                        <a class="page-scroll" href="#about">Map & Value</a>
                    </li>
                    <li>
                        <a class="page-scroll" href="#team">Graph</a>
                    </li>
                    
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container-fluid -->
    </nav>

    <!-- Header -->
    <header>
        <div class="container">
            <div class="intro-text">
                <div class="intro-lead-in">부산 강서구청 </div>
                <div class="intro-heading">실시간 악취 모니터링</div>
                <a href="#services" class="page-scroll btn btn-xl">실시간 악취 정보</a>
            </div>
        </div>
    </header>

    <!-- Services Section -->
    <section id="services">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h2 class="section-heading">악취 정보</h2>
                    <h3 class="section-subheading text-muted">복합 악취 배출허용 기준</h3>
                </div>
            </div>
        	<table class="table table-condensed">
				<tr class="info">
					<td align="center">구분&nbsp;</td>
					<td align="center">공업지역&nbsp;</td>					       
					<td align="center">기타지역&nbsp;</td>	
				</tr>
				<tr>
					<td align="center">배출구&nbsp;</td>
					<td align="center"><b>1,000</b> 이하&nbsp;</td>					       
					<td align="center"><b>500</b> 이하&nbsp;</td>	
				</tr>
				<tr>
					<td align="center">부지경계선&nbsp;</td>
					<td align="center"><b>20</b> 이하&nbsp;</td>					       
					<td align="center"><b>15</b> 이하&nbsp;</td>	
				</tr>
			</table>
			
			<br><br>
			<table class="table table-condensed">	
				<tr>
					<td class="info" align="center">시스템 흐름도&nbsp;</td>
											   
				</tr>
				<tr>
					<td align="center"><img src="img/system.jpg"></td>
				</tr>
			</table>
			<table class="table table-condensed">
				<tr>
					<td class="info" align="center">운영방법</td>
				</tr>
				<tr>
					<td width='50px'>○ 악취 발생 사업장 주변과 민원발생 지역에 악취센서를 설치, 실시간으로 악취 모니터링 실시</td>
				</tr>
				
				<tr>
					<td>○ 측정자료를 무선데이터 통신망을 이용하여 감시센타 서버로 전송관리</td>
				</tr>
				<tr>
					<td>○ 웹 서버를 이용한 실시간 악취정보 관리</td>
				</tr>
				<tr>
					<td>※ 고농도 악취 발생시 현장 확인 후 시료 포집 및 검사 의뢰</td>
				</tr>	
			</table>
        </div>
    </section>

    <!-- Portfolio Grid Section -->
    <section id="portfolio" class="bg-light-gray">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h2 class="section-heading">측정지점 정보</h2>
                    <h3 class="section-subheading text-muted">측정소 목록</h3>
                </div>
            </div>
			<table class="table table-condensed">
				<tr class="danger">
					<td align="center"><strong>설치지점</strong></td>					       
					<td align="center"><strong>설치위치</strong></td>
				</tr>
<?php
for($i=0; $i<sizeof($aSiteName); $i++) {
	echo("<tr><td align='center'>$aSiteName[$i]</td><td align='center'>$aSiteRemark[$i]</td></tr>");
}
?>
			
				</table>
		
		
		
        </div>
    </section>

    <!-- About Section -->
    <section id="about">
        <div class="container">
          <div class="row">
                <div class="col-lg-12 text-center">
                    <h2 class="section-heading">Map & Value</h2>
                    <h3 class="section-subheading text-muted">지도상 측정지점 위치 &nbsp; & &nbsp;실시간 악취 값</h3>
                </div>
          </div>
						
			<div class="row" id="map" style="height:300px;"></div>
			<table class="table table-condensed" id="content">
<?php

echo ("<tr onclick='SelectMarker(-1,1)'><td id='bSite-1' class='SubMenu'>전체보기</td>\n");
		for ($i=0; $i<2; $i++) {
			if (($aItemUsing[$i] == '1') || ($aItemUsing[$i] == '2')) {
				echo ("<td class='OrangeFont' align='center' style='white-space:normal'><font onMouseOver='ItemInfoShow({$i})' onMouseMove='ItemInfoMove()' onMouseOut='ItemInfoHide()'>{$aItemName[$i]}</font></td>\n");
			}
		}
		echo ("</tr>\n");
		
		for ($i=0; $i<sizeof($aSiteId); $i++) {
			echo ("<tr onclick='SelectMarker({$i},1);'><td id='bSite{$i}' class='SubMenu'><font onMouseOver='SiteInfoShow({$i})' onMouseMove='ItemInfoMove()' onMouseOut='ItemInfoHide()'>{$aSiteName[$i]}</font></td>\n");
			for ($j=0; $j<2; $j++) {
				if (($aItemUsing[$j] == '1') || ($aItemUsing[$j] == '2')) {
					echo ("<td id='bItem{$aSiteId[$i]}_{$j}' align='center' style='font-size:15px;font-weight:bold;'></td>\n");
				}
			}
			echo ("</tr>\n");
		}
?>
			</table>
			
       </div>
    </section>

    <!-- Team Section -->
    <section id="team" class="bg-light-gray">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h2 class="section-heading">Graph</h2>
                    <h3 class="section-subheading text-muted">실시간 악취 측정 값(그래프)</h3>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="team-member">
                       
					   
						<div class="row" id="graph1"></div>
                        <h4>부지경계</h4>
                        <p class="text-muted">악취희석배수</p>
                 
                    </div>
                </div>
			</div>
			<div class="row">
                <div class="col-lg-12 text-center">
                    <div class="team-member">
                        
						
						<div class="row" id="graph2"></div>
                        <h4>배출구/기타지역</h4>
                        <p class="text-muted">악취희석배수</p>
                      
                    </div>
                </div>
			</div>        
        </div>
    </section>
	
	<script src="boot/vendor/jquery/jquery.min.js"></script>
    <script src="boot/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js" integrity="sha384-mE6eXfrb8jxl0rzJDBRanYqgBxtJ6Unn4/1F7q4xRRyIw7Vdg9jP4ycT7x1iVsgb" crossorigin="anonymous"></script>
    <script src="boot/js/agency.min.js"></script>
	
	<script type='text/javascript'>
	var container = document.getElementById('map');
	</script>
	
	<script type='text/javascript' src='//dapi.kakao.com/v2/maps/sdk.js?appkey=165f885acce1dd9142338181840d0ba0'></script>
	
	<script type='text/javascript'>

var pathLine = null;
var pathLines = [];

var options = {center: new daum.maps.LatLng(36.4295159, 127.390136), level: 3};
map = new daum.maps.Map(container, options);
map.addControl(new daum.maps.MapTypeControl(), daum.maps.ControlPosition.TOPLEFT);

// Save & Set MapType (map:1 or skyview:3)
var maptypeid = GetCookie ('daummaptypeid', null);
if (maptypeid != null) map.setMapTypeId(maptypeid);
daum.maps.event.addListener (map, 'maptypeid_changed', function() {
	SetCookie ('daummaptypeid', map.getMapTypeId(), 1000);
});



var infowindow  = new daum.maps.InfoWindow({removable:false, disableAutoPan:true});
var infowindowF = new daum.maps.InfoWindow({removable:false, disableAutoPan:false});
var enableAutoPan;
for (enableAutoPan in infowindow) {
	if (infowindow[enableAutoPan] === !infowindowF[enableAutoPan]) break;
}
infowindowF = null;
infowindow.setZIndex (4);

var markerImg = [];
markerImg[0] = new daum.maps.MarkerImage('img/ledgreen.png', new daum.maps.Size(20,20), {offset: new daum.maps.Point(10,10)});
markerImg[1] = new daum.maps.MarkerImage('img/ledgray.png',  new daum.maps.Size(20,20), {offset: new daum.maps.Point(10,10)});
markerImg[2] = new daum.maps.MarkerImage('img/ledorange.png',new daum.maps.Size(20,20), {offset: new daum.maps.Point(10,10)});
markerImg[3] = new daum.maps.MarkerImage('img/ledred.png',   new daum.maps.Size(20,20), {offset: new daum.maps.Point(10,10)});

if (typeof ActionMapClick == 'function') daum.maps.event.addListener(map, 'click', ActionMapClick());
if (typeof ActionMapZoomStart == 'function') daum.maps.event.addListener(map, 'zoom_start', ActionMapZoomStart());
if (typeof ActionMapZoomChangned == 'function') daum.maps.event.addListener(map, 'zoom_changed', ActionMapZoomChangned());



function MapRelayout () {
	map.relayout ();
}

function MapGetLatLng (mouseEvent) {
	return {lat:mouseEvent.latLng.getLat(), lng:mouseEvent.latLng.getLng()};
}



// makers: site position
function MapMarkerAdd (lat, lng, img, name) {
	var position = new daum.maps.LatLng(lat, lng)
	var mk = new daum.maps.Marker({
		map:map,
		position:position,
		draggable:(typeof makeMarkerDragStart == 'function'),
		clickable:true,
		image:(0<=img)?markerImg[img]:null,
		title:name
	});
	mk.markerColor = img;
	mk.markerZindex = 0;	// 0:green, 1:red, 2:selected
	if (typeof makeMarkerClickListener == 'function') daum.maps.event.addListener(mk, 'click',     makeMarkerClickListener(markers.length));
	if (typeof makeMarkerOverListener  == 'function') daum.maps.event.addListener(mk, 'mouseover', makeMarkerOverListener(markers.length));
	if (typeof makeMarkerOutListener   == 'function') daum.maps.event.addListener(mk, 'mouseout',  makeMarkerOutListener(markers.length));
	if (typeof makeMarkerDragStart     == 'function') daum.maps.event.addListener(mk, 'dragstart', makeMarkerDragStart(markers.length));
	if (typeof makeMarkerDragEnd       == 'function') daum.maps.event.addListener(mk, 'dragend',   makeMarkerDragEnd(markers.length));
	markers.push(mk);
}

function MapMarkerResize (n) {
	if (n < markers.length) {
		for (var i=n; i<markers.length; i++) markers[i].setMap(null);
		markers.length = n;
	}
}

function MapMarkerColor (idx, cn) {
	if (markers[idx].markerColor == cn) return;
	markers[idx].markerColor = cn;
	markers[idx].setImage(markerImg[cn]);
	
	if (markers[idx].markerZindex == 4) return;
	if (markers[idx].markerZindex != cn) {
		markers[idx].markerZindex = cn;
		markers[idx].setZIndex (cn);
	}
}

function MapMarkerShowAll (si) {
	var bounds = new daum.maps.LatLngBounds();
	var n = 0;
	for (var i=si; i<markers.length; i++) {
		bounds.extend(markers[i].getPosition());
		n++;
	}
	
	// The site is only one -> extends the area.
	if (1 == n) {
		var sw = bounds.getSouthWest();
		var ne = bounds.getNorthEast();
		bounds.extend(new daum.maps.LatLng(ne.getLat()+0.01, ne.getLng()+0.01));
		bounds.extend(new daum.maps.LatLng(sw.getLat()-0.01, sw.getLng()-0.01));
	} else
	if (1 < n) {
		var sw = bounds.getSouthWest();
		var ne = bounds.getNorthEast();
		var dlng = (ne.getLng() - sw.getLng()) * 0.05;
		bounds.extend(new daum.maps.LatLng(ne.getLat(), ne.getLng()+dlng));
		bounds.extend(new daum.maps.LatLng(sw.getLat(), sw.getLng()-dlng));
	}
	if (0 < n) map.setBounds(bounds);
}

function MapMarkerMove (idx, lat, lng) {
	markers[idx].setPosition(new daum.maps.LatLng(lat, lng));
}

function MapMarkerCenter (idx) {
	map.setCenter (markers[idx].getPosition());
}

function MapMarkerPosition (idx) {
	var latlng = markers[idx].getPosition();
	return {lat:latlng.getLat(), lng:latlng.getLng()};
}



// infowindow: detail site info
var prevInfowindowIdx = -1;
function MapInfowindowShow (idx, msg) {
	infowindow.close();
	infowindow[enableAutoPan] = prevInfowindowIdx != idx;
	infowindow.setContent(msg);
	infowindow.open(map, markers[idx]);
	prevInfowindowIdx = idx;
	for (var i=0; i<markers.length; i++) {
		var cn = markers[i].markerColor;
		if (idx == i) cn = 4;
		if (markers[i].markerZindex != cn) {
			markers[i].markerZindex = cn;
			markers[i].setZIndex (cn);
		}
	}
}

function MapInfowindowClose () {
	infowindow.close ();
	prevInfowindowIdx = -1;
	for (var i=0; i<markers.length; i++) {
		var cn = markers[i].markerColor;
		if (markers[i].markerZindex != cn) {
			markers[i].markerZindex = cn;
			markers[i].setZIndex (cn);
		}
	}
}



// popups: bar graph window of sites
function MapPopupAdd () {
	var piw = new daum.maps.InfoWindow({removable:false, disableAutoPan:true});
	piw.setZIndex (4);
	popups.push (piw);
}

function MapPopupClose (idx) {
	popups[idx].close();
}

function MapPopupShow (idx, msg) {
	popups[idx].close();	// Required for popup window resizing.
	popups[idx].setContent(msg);
	popups[idx].open(map, markers[idx]);
}



// pathLines: Connect the measured position to the day.
function MapPathAdd (lat, lng) {
	pathLines.push(new daum.maps.LatLng(lat, lng));
}

function MapPathClear () {
	if (pathLine != null) {
		pathLine.setMap(null);
		pathLine = null;
	}
	pathLines = [];
}

function MapPathDraw () {
	pathLine = new daum.maps.Polyline({
		path: pathLines,
		strokeWeight: 4,
		strokeColor: '#FFAE00',
		strokeOpacity: 0.7,
		strokeStyle: 'solid'
	});
	pathLine.setMap(map);
}



</script>

<script type='text/javascript'>
// Add all site on the map
for (var i=0; i<sitearr.length; i++) {
	MapMarkerAdd(sitearr[i].lat, sitearr[i].lng, 0, sitearr[i].name);
	MapPopupAdd();
}
	</script>
	
</body>

</html>
