<?php

require_once('common.php');

session_start();

$sId = $_SESSION[$sDbDb.'_id'];

PrintHtmlHead ();

print <<<EOF

<style type="text/css">
.menuItem {
	cursor:pointer;
	padding-left:10px;
	padding-right:10px;
	padding-top:15px;
	padding-bottom:15px;
	font-weight:bold;
	display:none;
}
.menuItem:hover {
	background:linear-gradient(#EEEEEE,#7799BB);
}
</style>

<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript'>
var selMenu = 0;
function setMenuItemColor (idx,tf) {
//	background:linear-gradient(rgba(0,0,0,0.1),rgba(0,0,0,0));
	var obj = $('#menu'+idx);
	if (tf) {
		obj.css('background','linear-gradient(#AACCEE,#444488)');
		obj.css('color','#FFFFFF');
	} else {
		obj.css('background','');
		obj.css('color','#000000');
	}
}
function fc(idx,action) {
	setMenuItemColor (selMenu,false);
	selMenu = idx;
	setMenuItemColor (selMenu,true);
	if (action != null) window.open(action,'pageBody'); // parent.frames[1].location.replace(action);
}
function fSetMenu(idx,id) {
	fc(idx,null);
	
	if (id == 'admin') {
		$('#menu3').show();
	} else {
		$('#menu3').hide();
	}
	
	if (id != '') {
		if ($isMulti == 1) {
			// *** Multi DB ***
			$('#menu1').hide();
		} else {
			// *** Single DB only ***
			$('#menu1').show();
		}
		$('#menu4').show();
		$('#menu5').hide();
	} else {
		$('#menu1').hide();
		$('#menu4').hide();
		$('#menu5').show();
	}
}

function SetHeadContent () {
	if (parent.framesize == 0) {
		topbg.style.top =  '0px';
		topig.style.top =  '0px';
		toplo.style.top = '32px';
		topn1.style.top = '33px';
		topn2.style.top = '52px';
		topmn.style.top = '27px';
	} else {
		topbg.style.top = '-25px';
		topig.style.top = '-25px';
		toplo.style.top = '7px';
		topn1.style.top = '8px';
		topn2.style.top = '27px';
		topmn.style.top = '2px';
	}
}

function fOnLoad() {
	SetHeadContent ();
	
	$('#menu0').show();
	$('#menu2').show();
	fSetMenu (0,'$sId');
	
	$('#topig').animate({left:0},1500);
	$('#topmn').animate({right:-20},1500);
	$('#toplo').animate({opacity:1},2000);
	$('#topn1').animate({opacity:1},2000);
	$('#topn2').animate({opacity:1},2000);
}
</script>

</head>
<body onload='fOnLoad()' style='cursor:default'>

<div id='topbg' style='position:fixed; top:0; left:0; right:0; height:100px; background:url(img/titlebg.png) repeat;'></div>
<img id='topig' style='position:fixed; top:0; left:-600px;' src='img/title.png'>
<img id='toplo' src='img/logo.png' style='position:fixed; left:21px; top:32px; opacity:0; cursor:pointer;' onclick='fc(0,"pRealtime.php")'>
<div id='topn1' style='position:fixed; left:66px; top:33px; font-size:16px; font-weight:bold; color:#404040; opacity:0; cursor:pointer;' onclick='fc(0,"pRealtime.php")'>$str_title1</div>
<div id='topn2' style='position:fixed; left:67px; top:52px; font-size:13px; font-weight:bold; color:#808080; opacity:0; cursor:pointer;' onclick='fc(0,"pRealtime.php")'>$str_title2</div>

<div id='topmn' style='position:fixed; top:27px; right:-410px; background:linear-gradient(#FFFFFF,#99BBDD); padding-left:25px; padding-right:30px; padding-top:0px; padding-bottom:0px; border-radius:50px;'>
<table border='0' cellpadding='0' cellspacing='0px'><tr>

<td id='menu0' class='menuItem' onclick='fc(0,"pRealtime.php")'>$str_realtime</td>
<td id='menu1' class='menuItem' onclick='fc(1,"pData.php")'>$str_data</td>
<td id='menu2' class='menuItem' onclick='fc(2,"pInfo.php")'>$str_info</td>
<td id='menu3' class='menuItem' onclick='fc(3,"pAdmin.php")'>$str_admin</td>
<td id='menu4' class='menuItem' onclick='fc(4,"pRealtime.php?logout=1")'>$str_logout</td>
<td id='menu5' class='menuItem' onclick='fc(5,"pRealtime.php?login=1")'>$str_login</td>

</tr></table>
</div>

</body>
</html>

EOF;

?>
