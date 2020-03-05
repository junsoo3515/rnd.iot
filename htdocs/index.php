<?php

require_once('common.php');

if ($ISM) {
	header("Location: pRealtime.php");
	exit;
}

header("Content-Type: text/html; charset=UTF-8");

?>

<!DOCTYPE html>
<html>
<head>
<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=UTF-8'>
<meta name='viewport' content='user-scalable=no, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, width=device-width, height=device-height'>
<meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'>
</head>

<script type='text/javascript'>

function SetCookie(cKey, cValue, iValid) {
	var date = new Date();
	date.setDate(date.getDate() + iValid);
	document.cookie = cKey + '=' + escape(cValue) + ';expires=' + date.toGMTString();
}
function GetCookie(cKey, cDef) {
	var cookies = document.cookie.split("; ");
	for (var i = 0; i < cookies.length; i++) {
		var keyValues = cookies[i].split("=");
		if ((0 < keyValues.length) && (keyValues[0] == cKey)) return unescape(keyValues[1]);
	}
	return cDef;
}

var framesize = GetCookie ('framesize', 0);
function GerRows () {
	return (framesize == 0) ? "100,*,78" : "50,*,18";
}
function ChangeSize () {
	framesize = 1 - framesize;
	SetCookie ('framesize', framesize, 10000);
	document.getElementsByTagName('frameset')[0].rows = GerRows();
	frames[0].SetHeadContent();
	frames[2].SetTailContent();
}

document.write ("<frameset rows='" + GerRows() + "' cols='*' frameborder='no' border='0' framespacing='0'>");

</script>

<frame src='pHead.php'     name='pageHead' scrolling='no'  frameborder='0' noresize>
<?php
echo ("<frame src='");
if ($_GET['j'] == 'se') echo ("pInfo.php?j=se");
else echo ("pRealtime.php");
echo ("' name='pageBody' scrolling='yes' frameborder='0'>\n");
?>
<noframes>
<body>NO FRAME</body>
</noframes>

</html>
