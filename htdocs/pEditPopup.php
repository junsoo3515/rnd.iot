<?php

require_once('common.php');

session_start();

function ArgToNum ($v) {
	if (isset($v) && is_numeric($v)) return $v;
	return null;
}
function GetPostNum ($name) {
	$v = $_POST[$name];
	if (isset($v) && is_numeric($v)) return "'$v'";
	return 'NULL';
}

$sId = $_SESSION[$sDbDb.'_id'];
if ($sId != 'admin') die ("<html><body><script type='text/javascript'>window.opener.location.reload();window.close();</script></body></html>");

// connect db
$cDb = mysql_connect($sDbIp, $sDbId, $sDbPw);
if (!$cDb) die ('EC#730008');
mysql_query('set names utf8');
if (!mysql_select_db($sDbDb, $cDb)) die ('EC#730009');

$opt = $_POST['opt'];
if (isset($opt)) {
	$sid = ArgToNum($_POST['sid']);
	$t   = ArgToNum($_POST['t']);
	if (($sid != null) && ($t != null)) {
		if ($opt == 1) {
			// modify data
			$lng = GetPostNum('lng');
			$lat = GetPostNum('lat');
			$wdate = GetPostNum('wdate');
			$wxy = GetPostNum('wxy');
			
			$sQuery = "UPDATE `datas` SET `lng`=$lng,`lat`=$lat,`wdate`=$wdate,`wxy`=$wxy";
			for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ",`$aDbItems[$i]`=".GetPostNum($aDbItems[$i]);
			$sQuery .= " WHERE (`SITENO`='$sid') and (`DATE`='$t')";
			mysql_query($sQuery, $cDb);
		} else
		if ($opt == 2) {
			// delete data
			$sQuery = "DELETE FROM `datas` WHERE (`SITENO`='$sid') and (`DATE`='$t')";
			mysql_query($sQuery, $cDb);
		}
	}
	mysql_close ($cDb);
	die ("<html><body><script type='text/javascript'>window.opener.location.reload();window.close();</script></body></html>");
}

$sid = ArgToNum($_GET['sid']);
$t   = ArgToNum($_GET['t']);
if (($sid == null) || ($t == null)) {
	mysql_close ($cDb);
	die ("<html><body><script type='text/javascript'>window.close();</script></body></html>");
}

PrintHtmlHead ();

?>

<link rel='stylesheet' href='js/pure.css' />
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript'>
function idwork(opt) {
	inform.opt.value = opt;
	inform.submit();
}
function fOnLoad() {
	var iw = window.innerWidth;
	var ih = window.innerHeight;
	var ow = window.outerWidth;
	var oh = window.outerHeight;
	var obj = $('#mtb');
	var w = obj.width();
	var h = obj.height();
	if ((0 < iw) && (iw < ow) && (0 < ih) && (ih < oh)) {
		w += ow - iw + 2;
		h += oh - ih + 2;
	} else {
		w += 16 + 2;
		h += 67 + 2;
	}
	window.outerWidth = w;
	window.outerHeight = h;
	self.resizeTo(w,h);
}
</script>

</head>
<body style='overflow:hidden; cursor:default' onload='fOnLoad()'>

<?php

$iIsGps = GetSettingInt ($cDb, 'isgps', 0, 0, 1);

$sQuery = "SELECT `SITENO`,`DATE`,`LNG`,`LAT`,`WDATE`,`WXY`";
for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= ",`$aDbItems[$i]`";
$sQuery .= " FROM `datas` WHERE (`SITENO`='$sid') and (`DATE`='$t')";
$cResult = mysql_query($sQuery, $cDb);
if ($cResult == null) die('EC#730105');
$aRow = mysql_fetch_row($cResult);
if ($aRow == null) die('EC#730105');

echo ("<table id='mtb'><form name='inform' method='post' action='pEditPopup.php?j=e'>\n");
	echo ("	<input type='hidden' name='opt' value='0'>\n");
	echo ("	<input type='hidden' name='sid' value='$sid'>\n");
	echo ("	<input type='hidden' name='t' value='$t'>\n");
	echo ("	<tr><td class='TitleFont'>Site</td><td>$sid</td></tr>\n");
	echo ("	<tr><td class='TitleFont'>Date</td><td>$t</td></tr>\n");
	if ($iIsGps == 1) {
		echo ("	<tr><td class='TitleFont'>LNG(&#177;180)</td><td><input type='text' name='lng' value='$aRow[2]'></td></tr>\n");
		echo ("	<tr><td class='TitleFont'>LAT(&#177;90)</td><td><input type='text' name='lat' value='$aRow[3]'></td></tr>\n");
	} else {
		echo ("	<input type='hidden' name='lng' value='$aRow[2]'>\n");
		echo ("	<input type='hidden' name='lat' value='$aRow[3]'>\n");
	}
	echo ("	<tr><td class='TitleFont'>WDATE</td><td><input type='text' name='wdate' value='$aRow[4]'></td></tr>\n");
	echo ("	<tr><td class='TitleFont'>WXY</td><td><input type='text' name='wxy' value='$aRow[5]'></td></tr>\n");
	
	for ($i=0; $i<sizeof($aDbItems); $i++) {
		echo ("	<tr><td class='TitleFont'>".$aDbItems[$i]."</td><td><input type='text' name='".$aDbItems[$i]."' value='".$aRow[6+$i]."'></td></tr>\n");
	}
	
	echo ("<tr><td></td><td><input type='button' value='$str_save' onclick='idwork(1)' class='pure-button'> <input type='button' value='$str_delete' onclick='idwork(2)' class='pure-button'></td></tr>\n");
echo ("</form></table>\n");

mysql_free_result($cResult);

echo ("</body>\n");
echo ("</html>\n");

mysql_close ($cDb);
?>
