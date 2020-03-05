<?php

require_once('string.php');
require_once('ainfo.php');



// Common functions ////////////////////////////////////////////////////////////////////

// Logging
function LogMsg ($msg) {
	$now = new DateTime();
	$now = $now->format('Y/m/d H:i:s');
	file_put_contents (
		'log/log.txt',
		$now.' '.$_SERVER['REMOTE_ADDR'].' '.$msg."\r\n",
		LOCK_EX|FILE_APPEND
	);
}

// Remove the quotation marks to prevent the risk of SQL.
function MakeSafeString ($str) {
	if ($str == null) return $str;
	return str_replace("'", '_', str_replace('"', '_', str_replace('`', '_', $str)));
}

// Changes to the 'compressed int'. '2016-06-17' -> 16367hhnn
function DateStrToInt ($s, $h,$n,$z) {
	Global $iTimeType;
	
	$y = (int)substr($s, 0, 4);		// year
	$m = (int)substr($s, 5, 2);		// month
	$d = (int)substr($s, 8, 2);		// day
	
	if ($m <   1) $m = 1;
	if (12  < $m) $m = 12;
	if ($d <   1) $d = 1;
	if (31  < $d) $d = 31;
	if ($h  <  0) $h = 0;
	if (23  < $h) $h = 23;
	if ($n  <  0) $n = 0;
	if (59  < $n) $n = 59;
	
	if ($iTimeType == 1) {
		if ($y < 1970) $y = 1970;
		if (2037 < $y) $y = 2037;
		if ($z  <  0) $z = 0;
		if (59  < $z) $z = 59;
		return mktime($h,$n,$z,$m,$d,$y);
	}
	
	$y -= 2000;
	if ($y <   0) $y = 0;
	if (213 < $y) $y = 213;
	return $y*10000000+(50+$m*50+$d)*10000 + ($h*100) + $n;
}

// Changes to the 'compressed int'. '201606171537' -> 163671537
function DateTimeToInt ($s) {
	Global $iTimeType;
	
	$y = (int)substr($s, 0, 4);		// year
	$m = (int)substr($s, 4, 2);		// month
	$d = (int)substr($s, 6, 2);		// day
	$h = (int)substr($s, 8, 2);		// hour
	$n = (int)substr($s,10, 2);		// minute
	
	if ($m  <  1) $m = 1;
	if (12  < $m) $m = 12;
	if ($d  <  1) $d = 1;
	if (31  < $d) $d = 31;
	if ($h  <  0) $h = 0;
	if (23  < $h) $h = 23;
	if ($n  <  0) $n = 0;
	if (59  < $n) $n = 59;
	
	if ($iTimeType == 1) {
		$z = (int)substr($s,12, 2);	// second
		if ($y < 1970) $y = 1970;
		if (2037 < $y) $y = 2037;
		if ($z  <  0) $z = 0;
		if (59  < $z) $z = 59;
		return mktime($h,$n,$z,$m,$d,$y);
	}
	
	$y -= 2000;
	if ($y  <  0) $y = 0;
	if (213 < $y) $y = 213;
	return ($y*10000000)+((50+$m*50+$d)*10000) + ($h*100) + $n;	// 3+3+2+2
}

// Change the compression int to a string. 16367hhmm -> '2016-06-17 hh:mm'
function DateIntToStr ($i) {
	Global $iTimeType;
	if ($iTimeType == 1) return date('Y-m-d H:i:s',$i);
	
	$n = $i % 100;
	$i = (int)($i / 100);
	$h = $i % 100;
	$i = (int)($i / 100);
	$d = $i % 50;
	$i = (int)($i / 50);
	$m = ($i % 20) -1;
	$i = (int)($i / 20);
	$y = ($i % 1000) + 2000;
	return sprintf ("%04d-%02d-%02d %02d:%02d", $y,$m,$d,$h,$n);
}

// Change the compression int to Javascript int type. 16367hhmm -> 1970sec
function IntToJs ($i) {
	Global $iTimeType;
	if ($iTimeType == 1) return $i;
	
	$n = $i % 100;
	$i = (int)($i / 100);
	$h = $i % 100;
	$i = (int)($i / 100);
	$d = $i % 50;
	$i = (int)($i / 50);
	$m = ($i % 20) -1;
	$i = (int)($i / 20);
	$y = ($i % 1000) + 2000;
	return mktime($h,$n,0,$m,$d,$y);
}

function DateJsToInt ($i) {
	Global $iTimeType;
	if ($iTimeType == 1) return $i;
	return DateTimeToInt(date('YmdHi',$i));
}

// For execution time measurement. Return the system time.
function MicrotimeFloat() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

// The use MySQL as INI file.
function GetSetting ($cDb, $id, $value) {
	$id = MakeSafeString($id);
	$sQuery = "SELECT `VALUE` FROM `setting` WHERE `ID`='$id'";
	$cResult = mysql_query($sQuery, $cDb);
	if ($cResult != null) {
		if ($aRow = mysql_fetch_row($cResult)) $value = $aRow[0];
		mysql_free_result($cResult);
	}
	return $value;
}
function GetSettingInt ($cDb, $id, $def, $min, $max) {
	$value = (int)GetSetting($cDb, $id, $def);
	if ($value < $min) return $min;
	if ($max < $value) return $max;
	return $value;
}
function SetSetting ($cDb, $id, $value) {
	$id = MakeSafeString($id);
	$value = MakeSafeString($value);
	$sQuery = "INSERT INTO `setting` (`ID`, `VALUE`) VALUES ('$id','$value') ON DUPLICATE KEY UPDATE `VALUE`='$value'";
	mysql_query($sQuery, $cDb);
}

function IsMobile($agent) {
	$agent = strtolower($agent);
	$MobileArray  = array("iphone","ipod","lgtelecom","skt","mobile","samsung","nokia","blackberry","android","sony","phone");
	$checkCount = 0;
	for($i=0; $i<sizeof($MobileArray); $i++){
		if(preg_match("/$MobileArray[$i]/", $agent)) return true;
	}
	return false;
}
$ISMorg = IsMobile($_SERVER['HTTP_USER_AGENT']);

$browsertype = $_COOKIE['BrowserType'];
if ($browsertype == 'mobile') $ISM = true;
else if ($browsertype == 'pc') $ISM = false;
else $ISM = $ISMorg;

$isMulti = isset($sDbs) ? 1 : 0;	// Multi or Single DB



// Common HTML head ////////////////////////////////////////////////////////////////////

function PrintHtmlHead () {
	Global $ISM, $str_font;
	Global $arr_win_dir, $arr_pozip, $arr_alm;
	Global $cDb;
	
	if (isset($cDb)) {
		$sWindowTitle = GetSetting ($cDb, 'windowtitle', '');
	} else {
		$sWindowTitle = '';
	}
	
	header("Content-Type: text/html; charset=UTF-8");
	header("Pragma: no-cache");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	
print <<<EOF
<!DOCTYPE html>
<html>
<head>
<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=UTF-8'>
<meta name='viewport' content='user-scalable=no, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, width=device-width, height=device-height'>
<meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'>
<title>$sWindowTitle</title>

<style type='text/css'>

html, body { height: 100%; margin: 0; padding: 0; }

BODY,TD,P,DIV,INPUT,SELECT,TEXTAREA {
	font-family:$str_font;
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

EOF;
	echo ("	position:fixed; top:".($ISM?50:0)."px; left:0; right:0; height:16px;\n");
print <<<EOF
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
	display:inline-block;
	color:#FFFFFF;
	border:1px solid rgba(0,0,0,0.5);
	box-shadow: 0 2px 10px 5px rgba(0,0,0,0.2);
	padding:15px;
	border-radius:10px;
	text-align:center;
	
	background-color: #008800;
	background-image: linear-gradient(-45deg,
		rgba(255, 255, 255, 0)   22.9%,
		rgba(255, 255, 255, 0.1) 23%,
		rgba(255, 255, 255, 0.1) 50%,
		rgba(255, 255, 255, 0)   50.1%,
		rgba(255, 255, 255, 0)   72.9%,
		rgba(255, 255, 255, 0.1) 73%);
	background-size: 20px 20px;
	-webkit-background-size:20px 20px;
	background-repeat: repeat;
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

<script type='text/javascript'>

EOF;
echo ("	var ism = ".($ISM?1:0).";\n");
print <<<EOF

var gEcBusy=false;
/*
window.addEventListener('error', function(e) {
	if (gEcBusy) return;
	gEcBusy = true;
	if ((document.getElementById('map') != null) && (map.innerHTML == '')) {
		// MAP loading error
		map.innerHTML = "<table width='100%' height='100%'><tr><td align='center'><h3>Wait...</h3></td></tr></table>";
		setTimeout ('location.reload()',3000);
	} else
	if (document.getElementById('msgPopupTxt') != null) {
		var msg = 'EC#'+e.lineno+':'+e.colno+'<p>'+e.message+'</p>';
		msg += "<input type='button' value='Close' onclick='MsgPopupHideGo()' class='pure-button'> <input type='button' value='Reload' onclick='location.reload()' class='pure-button'>";
		MsgPopupError (msg, 0);
	} else {
		alert ('EC#'+e.lineno+':'+e.colno+'\\n'+e.message);
	}
	PrintAll(e.target);
}, true);
*/
window.onerror = function(emsg, url, eline, ecol, eerror) {
	if (gEcBusy) return true;
	gEcBusy = true;
	if ((document.getElementById('map') != null) && (map.innerHTML == '')) {
		// MAP loading error
		map.innerHTML = "<table width='100%' height='100%'><tr><td align='center'><h3>Wait...</h3></td></tr></table>";
		setTimeout ('location.reload()',3000);
	} else
	if (document.getElementById('msgPopupTxt') != null) {
		var msg = '<p>EC#';
		msg += eline ? eline : 'unknown';
		if (ecol) msg += ':' + ecol;
		msg += '</p>';
		if (emsg) msg += '<p>'+emsg+'</p>';
		if ((emsg) && (eerror) && (emsg.indexOf(eerror) < 0)) msg += '<p>'+eerror+'</p>';
		if (url) msg += '<p>'+url+'</p>';
		msg += "<input type='button' value='Close' onclick='MsgPopupHideGo()' class='pure-button'> <input type='button' value='Reload' onclick='location.reload()' class='pure-button'>";
		MsgPopupError (msg, 0);
	} else {
		var msg = 'EC#';
		msg += eline ? eline : 'unknown';
		if (ecol) msg += ':' + ecol;
		if (emsg) msg += '\\n'+emsg;
		if ((emsg) && (eerror) && (emsg.indexOf(eerror) < 0)) msg += '\\n'+eerror;
		if (url) msg += '\\n'+url;
		alert (msg);
	}
	return true;
};

// Prohibited area selection.
function ReturnFalse () {
	return false;
}
window.document.onselectstart = ReturnFalse;
window.document.ondragstart   = ReturnFalse;
if (navigator.userAgent.indexOf('Firefox') >= 0) {
	var eventNames = ["mousedown","mouseover","mouseout","mousemove","mousedrag","click","dblclick","keydown","keypress","keyup"];
	for(var i=0; i<eventNames.length; i++) {
		window.addEventListener( eventNames[i], function(e) { window.event = e; }, true );
	}
}

function pwKeyCursor (npobj) {
	var nplen = npobj.value.length;
	npobj.setSelectionRange(nplen, nplen);
}
function pwKeyDown () {
	var e = event || window.event;
	var keycode = e.keyCode || e.charCode;
	if ((33 <= keycode) && (keycode <= 40)) {
		if (e.preventDefault) e.preventDefault(); else e.stop();
	}
}
function pwKeyPress (obj) {
	var e = event || window.event;
	var keycode = e.keyCode || e.charCode;
	if (keycode == 13) return;
	if (keycode == 8) return;
	
	var npobj = obj;
	var opobj = eval(obj.form.name+'.'+obj.name.slice(0, -1));
	npobj.value += '*';
	opobj.value += String.fromCharCode(keycode);
	if (e.preventDefault) e.preventDefault(); else e.stop();
	obj.blur();
	obj.focus();
}
function pwKeyUp (obj) {
	var npobj = obj;
	var opobj = eval(obj.form.name+'.'+obj.name.slice(0, -1));
	var nplen = npobj.value.length;
	var oplen = opobj.value.length;
	if (nplen < oplen) opobj.value = opobj.value.substr(0, nplen);
	else if (oplen < nplen) opobj.value += npobj.value.substr(oplen);
	else return;
	npobj.value = '';
	for (var i=0; i<nplen; i++) npobj.value += '*';
	obj.blur();
	obj.focus();
}

function PrintAll (obj) {
	var msg = ''
	for (propName in obj) try { msg += propName+'='+obj[propName]+'\\n'; } catch (err) { }
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
function MsgPopupColor (msg, c, ms, ani) {
	if (hMsgTimeout != null) {
		clearTimeout(hMsgTimeout);
		hMsgTimeout = null;
	}
	$('#msgPopupTxt').html(msg);
	$('#msgPopupTxt').css('background-color',c);
	$('#msgPopupBox').css('display','table');
	if (0 < ms) hMsgTimeout = setTimeout (MsgPopupHideGo,ms);
	if (ani) $('#msgPopupTxt').animate({zoom:'110%'},100).animate({zoom:'95%'},100).animate({zoom:'100%'},100);
}
function MsgPopupOnly (msg, ms) {	// no animation
	MsgPopupColor (msg, '#008800', ms, false);
}
function MsgPopupShow (msg, ms) {	// normal dialog box + popup animation
	MsgPopupColor (msg, '#008800', ms, true);
}
function MsgPopupError (msg, ms) {	// error dialog box + popup animation
	MsgPopupColor ('<img src="img/attention.png" style="vertical-align:top"> '+msg, '#B81900', ms, true);
}
function MsgPopupHideGo () {
	hMsgTimeout = null;
	$('#msgPopupBox').fadeOut();
}
function MsgPopupHide () {
	if (hMsgTimeout != null) {
		clearTimeout(hMsgTimeout);
		MsgPopupHideGo ();
	}
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

EOF;
	echo ("var arr_win_dir = [");
	for ($i=0; $i<sizeof($arr_win_dir); $i++) {
		if (0 < $i) echo (",");
		echo ("'$arr_win_dir[$i]'");
	}
	echo ("];\n");
	echo ("var arr_pozip = [");
	for ($i=0; $i<sizeof($arr_pozip); $i++) {
		if (0 < $i) echo (",");
		echo ("'$arr_pozip[$i]'");
	}
	echo ("];\n");
	echo ("var arr_alm = [");
	for ($i=0; $i<sizeof($arr_alm); $i++) {
		if(0 < $i) echo (",");
		echo ("'$arr_alm[$i]'");
	}
	echo ("];\n");
print <<<EOF

function js_item_format	(val, dec, zero) {
	if ((0 <= dec) && (dec <= 9)) {
		return js_number_format (val, dec, zero);
	}
	if (10 == dec) {	// Special code: wind_direction
		if (typeof val != 'number') val = parseInt(val);
		else val = Math.round(val);
		if ((isNaN(val)) || (val < 1) || (16 < val)) val = 1;
		return arr_win_dir[val-1] + " <img src='img/windd"+val+".png' class='windImg'>";
	}
	if (11 == dec) {	// Special code: pozip
		if (typeof val != 'number') val = parseInt(val);
		else val = Math.round(val);
		if ((isNaN(val)) || (val < 0) || (1 < val)) val = 0;
		return arr_pozip[val];
	}
	if (12 == dec) {	// Special code: alm
		if (typeof val != 'number') val = parseInt(val);
		else val = Math.round(val);
		if ((isNaN(val)) || (val < 0) || (3 < val)) val = 0;
		return arr_alm[val];
	}
	return val;
}

EOF;
	if ($ISM) {
print <<<EOF

var menuStatus = false;
function mfc(action) {
	MenuClose(1);
	document.location = action;
}
function MenuClose (opt) {
	if (opt != 1) {
		menuStatus = false;
		$('body,html').css({'overflow':'visible'});
		$('body').unbind('touchmove');
		$('#menuBack').fadeOut();
	}
	var obj = $('#menuMenu');
	var w = obj.width()+10;
	obj.animate({left:-w});
}
function MenuClick () {
	menuStatus = true;
	$('body,html').css({'overflow':'hidden'});
	$('body').bind('touchmove', function(e){if(e.preventDefault)e.preventDefault();else e.stop()});
	$('#menuBack').fadeIn();
	
	var obj = $('#menuMenu');
	var w = obj.width()+10;
	obj.css({left:-w});
	obj.animate({left:0});
}
function MoveLastUp() {
	$('body').scrollTop(0);
}
function MoveLastDn() {
	$('body').scrollTop($(document).height());
}

// Call from Android APP.
var mMyLat = 0, mMyLng = 0;
function SetLocation (lat, lng) {
	mMyLat = lat;
	mMyLng = lng;
	if (typeof OnMyLocation == 'function') OnMyLocation (false);
}

EOF;
	} else {
		if ($sWindowTitle != '') echo ("try{parent.document.title='$sWindowTitle';}catch(err){}\n");
	}
	echo ("</script>\n");
}

function SetTailImage () {
	echo ("<div style='z-index:-3; position:fixed; bottom:0; left:0; right:0; height:192px; background:url(img/tailbg.png) repeat;'></div>\n");
	echo ("<img src='img/tail2.png' style='z-index:-2; position:fixed; bottom:0; right:0;'>\n");
	echo ("<img src='img/tail.png' style='z-index:-1; position:fixed; bottom:0; left:0;'>\n");
}
function SetMobileMenu () {
	Global $sId, $str_realtime, $str_data, $str_board, $str_ad, $str_etc, $str_chpw, $str_logout, $str_login, $str_move_up, $str_move_down;
	
	echo ("<div id='moveLast' style='position:fixed; right:0; bottom:0; z-index:1; display:none;'>");
	echo ("<table border=0 cellpadding='5' cellspacing='5' width='90' height='45'><tr>");
	echo ("<td onclick='MoveLastUp()' class='mMoveLast' align='center'>$str_move_up</td>");
	echo ("<td onclick='MoveLastDn()' class='mMoveLast' align='center'>$str_move_down</td></tr></table></div>\n");
	echo ("<div id='menuBack' style='position:fixed; top:0; left:0; right:0; bottom:0; margin:0; z-index:9; background-color:rgba(0,0,0,0.7); display:none;' onclick='MenuClose()' ontouchend='MenuClose()'></div>\n");
	echo ("<div id='menuM' style='position:fixed; top:0; left:0; width:50px; height:50px; margin:0; z-index:9; cursor:pointer;'><img src='img/menu_gray.png' onclick='MenuClick()'></div>\n");
	echo ("<div id='menuMenu' style='position:fixed; top:0; left:-100%; bottom:0; margin:0; z-index:9; background:#C00000;'>");
		echo ("<table border=0 cellpadding=0 cellspacing=0 height=100%>");
		echo ("<tr height=1><td width=1><img src='img/menu_white.png' onclick='MenuClose()' style='cursor:pointer;'></td>");
		if (isset($sId)) {
			echo ("<td height=1 width=1 onclick='mfc(\"pRealtime.php?logout=1\")' style='cursor:pointer;'><div class=mMenuLogin>$sId / $str_logout</div></td></tr>");
		} else {
			echo ("<td height=1 width=1 onclick='mfc(\"pRealtime.php?login=1\")' style='cursor:pointer;'><div class=mMenuLogin style='margin-left:30px;'>$str_login</div></td></tr>");
		}
		echo ("<tr height=1><td colspan=2 class=mMenuItem onclick='mfc(\"pRealtime.php\")'>$str_realtime</td></tr>");
		echo ("<tr height=1><td colspan=2></td></tr>");
		if (isset($sId)) {
			echo ("<tr height=1><td colspan=2 class=mMenuItem onclick='mfc(\"pData.php\")'>$str_data</td></tr>");
			echo ("<tr height=1><td colspan=2></td></tr>");
		}
		if (!isset($sDbs)) {
			echo ("<tr height=1><td colspan=2 class=mMenuItem onclick='mfc(\"pInfo.php\")'>$str_board</td></tr>");
			echo ("<tr height=1><td colspan=2></td></tr>");
		}
		echo ("<tr height=1><td colspan=2 class=mMenuItem onclick='mfc(\"pInfo.php?j=it\")'>$str_ad</td></tr>");
		echo ("<tr height=1><td colspan=2></td></tr>");
		if (isset($sId)) {
			echo ("<tr height=1><td colspan=2 class=mMenuItem onclick='mfc(\"pInfo.php?j=pw\")'>$str_chpw</td></tr>");
			echo ("<tr height=1><td colspan=2></td></tr>");
		}
		echo ("<tr height=1><td colspan=2 class=mMenuItem onclick='mfc(\"pInfo.php?j=se\")'>$str_etc</td></tr>");
		echo ("<tr height=1><td colspan=2></td></tr>");
		echo ("<tr height='*'><td colspan=2 style='background:#FFEEEE;' ontouchend='MenuClose()'></td></tr>");
		echo ("</table>");
	echo ("</div>\n");
}
function SetMobileTitle ($msg) {
	echo ("<div style='position:fixed; top:0px; left:0px; right:0px; height:50px; padding-left:50px; padding-top:1px; background:#FFFFFF; z-index:1;' onclick='MenuClick()'><h2>$msg</h2></div>\n");
}

?>
