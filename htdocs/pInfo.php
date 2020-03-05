<?php

require_once('common.php');

session_start();

$sId = $_SESSION[$sDbDb.'_id'];
$job = $_GET['j'];

// connect db
$cDb = mysql_connect($sDbIp, $sDbId, $sDbPw);
if (!$cDb) die ('EC#730008');
mysql_query('set names utf8');
if (!mysql_select_db($sDbDb, $cDb)) die ('EC#730009');

// Block anonymous access
$iIsGuest = GetSettingInt ($cDb, 'isguest', 0, 0, 1);
if (($iIsGuest == 0) && (!isset($sId))) {
	die ("<html><head><meta http-equiv='refresh' content='0;url=pRealtime.php?login=1'></head></html>");
}

// selected menu
if ($job == 'it') $iSel = 1;
else if (($job== 'pw') && (isset($sId))) $iSel = 2;
else if ($job == 'se') $iSel = 3;
else if (!isset($sDbs)) $iSel = 0;
else $iSel = 1;


// Head & Menu /////////////////////////////////////////////////////////////////

PrintHtmlHead ();

if (!$ISM) {
	echo ("<script type='text/javascript'>\n");
	echo ("try{window.parent.frames[0].fSetMenu(2,'$sId');}catch(err){}\n");
	echo ("</script>\n");
}

print <<<EOF
<link rel='stylesheet' href='js/pure.css' />
<style type='text/css'>
HTML { overflow-y:scroll; }
</style>
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='rsa/rsa.js'></script>

</head>
<body style='cursor:default'>

EOF;

SetTailImage ();
if ($ISM) {
	SetMobileMenu ();
	echo ("<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;\n");
} else {
	echo ("<table width='800px' align='center' border='0' cellpadding='0' cellspacing='0'><tr>\n");
	echo ("<td width='156px' valign='top' style='padding-top:62px;'>\n");
	echo ("	<div style='width:120px; background:#EEEEEE; padding:10px; border-radius:10px; border:3px solid #FFBB88;'>\n");
	
	echo ("	<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n");

	function PrintSubMenu ($i, $j, $s, $lk) {
		echo ("<tr><td class='".(($j==$i)?'SubMenuSel':'SubMenu')."' onclick='document.location=\"$lk\"'>$s</td></tr>\n");
	}
	if (!isset($sDbs)) PrintSubMenu (0, $iSel, $str_board, 'pInfo.php');
	//PrintSubMenu (1, $iSel, $str_ad, 'pInfo.php?j=it');
	if (isset($sId)) PrintSubMenu (2, $iSel, $str_chpw, 'pInfo.php?j=pw');
	PrintSubMenu (3, $iSel, $str_etc, 'pInfo.php?j=se');

	echo ("	</table>\n");
	
	echo ("	</div>\n");
	echo ("</td>\n");
	echo ("<td width='644px' valign='top' style='padding-top:2px;'>\n");
}



// 설정 ///////////////////////////////////////////////////////////////

if ($iSel == 3) {

if ($ISM) {
	SetMobileTitle ($str_etc);
} else {
	echo ("<h1>$str_etc</h1>\n");
}

print <<<EOF
	<script type='text/javascript'>
	function PcToMobile () {
		try {
			parent.document.location = 'pInfo.php?j=se';
		} catch (err) {
			document.location = 'index.php?j=se';
		}
	}
	function MobileToPc () {
		document.location = 'index.php?j=se';
	}
	function SaveSeData () {
		var bt = seform.browsertype.value;
		SetCookie ('BrowserType', bt, 10000);
		MsgPopupShow ('$str_save_ok',1000);
		
		if ((bt != 'mobile') && (bt != 'pc')) {
			bt = ('$ISMorg' != '') ? 'mobile' : 'pc';
		}
		if ((ism == 1) && (bt == 'pc')) {		// mobile -> pc
			setTimeout ('MobileToPc()',1000);
		} else
		if ((ism == 0) && (bt == 'mobile')) {	// pc -> mobile
			setTimeout ('PcToMobile()',1000);
		}
	}
	</script>

EOF;
	if ($ISM) {
		echo ("<br>&nbsp;<br>&nbsp;\n");
		echo ("<table align='center' style='background:#EEEEEE; padding:10px; border-radius:10px; border:3px solid #FFBB88;'>");
	} else {
		echo ("<table>");
	}
	echo ("<form name='seform'>\n");
	
	$browsertype = $_COOKIE['BrowserType'];
	if (($browsertype != 'mobile') && ($browsertype != 'pc')) $browsertype = 'auto';
	
	echo ("<tr><td class='TitleFont'>$str_browsertype</td><td><select name='browsertype'>");
	echo ("<option value='auto'".(($browsertype=='auto')?" selected":"").">Auto</option>");
	echo ("<option value='pc'".(($browsertype=='pc')?" selected":"").">PC</option>");
	echo ("<option value='mobile'".(($browsertype=='mobile')?" selected":"").">Mobile</option>");
	echo ("</select></td></tr>\n");
	
	echo ("<tr><td></td><td><input type='button' value='$str_save' onclick='SaveSeData()' class='pure-button'></td></tr>\n");
	
	echo ("</form></table>\n");
	
} else // 설정



// 비밀번호 변경 ///////////////////////////////////////////////////////////////

if ($iSel == 2) {

require_once('rsa/RSA.php');
$RSAPrivatekey = GetSetting ($cDb, 'rsakey', '');

if ($ISM) {
	SetMobileTitle ($str_chpw);
} else {
	echo ("<h1>$str_chpw</h1>\n");
}

print <<<EOF
	<script type='text/javascript'>
	function PasswordChangeCheck () {
		if (pwform.pw1.value == '') {
			if (ism == 1) pwform.pw1.focus();
			else pwform.pw1i.focus();
			MsgPopupError ('$str_chpw_err1', 2000);
			return false;
		}
		if (pwform.pw2.value == '') {
			if (ism == 1) pwform.pw2.focus();
			else pwform.pw2i.focus();
			MsgPopupError ('$str_chpw_err2', 2000);
			return false;
		}
		if (pwform.pw2.value != pwform.pw3.value) {
			if (ism == 1) pwform.pw3.focus();
			else pwform.pw3i.focus();
			MsgPopupError ('$str_chpw_err3', 2000);
			return false;
		}

EOF;
	// RSA
	if ($RSAPrivatekey != '') {
		echo ("		var publickey = '".RSAGetPublicKey($RSAPrivatekey)."';\n");
		echo ("		pwform.pw1.value = RSAEncryption(publickey, GetCookie('PHPSESSID','')+pwform.pw1.value);\n");
		echo ("		pwform.pw2.value = RSAEncryption(publickey, GetCookie('PHPSESSID','')+pwform.pw2.value);\n");
	}
print <<<EOF
		pwform.pw1i.value = '';
		pwform.pw2i.value = '';
		pwform.pw3i.value = '';
		pwform.pw3.value = '';
		return true;
	}
	</script>

EOF;
	$sPw1 = $_POST['pw1'];
	$sPw2 = $_POST['pw2'];
	if (($sPw1 !== null) && ($sPw2 !== null)) {
		// RSA
		$sid = session_id();
		$sidlen = strlen($sid);
		if (($sPw1 != '') && ($RSAPrivatekey != '')) {
			$sPw1 = @RSADecryption($RSAPrivatekey, $sPw1);
			if (substr ($sPw1, 0, $sidlen) == $sid) $sPw1 = substr($sPw1, $sidlen);
			else $sPw1 = '';
		}
		if (($sPw2 != '') && ($RSAPrivatekey != '')) {
			$sPw2 = @RSADecryption($RSAPrivatekey, $sPw2);
			if (substr ($sPw2, 0, $sidlen) == $sid) $sPw2 = substr($sPw2, $sidlen);
			else $sPw2 = '';
		}
		
		if (($sPw1 != '') && ($sPw2 != '')) {
			$sPw1 = MakeSafeString ($sPw1);
			$sPw2 = MakeSafeString ($sPw2);
			$sQuery = "SELECT count(*) FROM `member` WHERE `ID`='$sId' and `PW`=password('$sPw1')";
			$cResult = mysql_query($sQuery, $cDb);
			if ($cResult != null) {
				$aRow = mysql_fetch_row($cResult);
				$bPwResult = ($aRow) && ($aRow[0] == 1);
				mysql_free_result($cResult);
				
				if (!$bPwResult) {
					echo ("<h4>$str_chpw_err4</h4>\n");
				} else {
					echo ("<h4><font color=#4444FF>$str_chpw_ok</font></h4>\n");
					$sQuery = "UPDATE `member` SET `PW`=password('$sPw2') WHERE `ID`='$sId'";
					mysql_query($sQuery, $cDb) or die ('EC#730104');
				}
			} else {
				echo ("<h4><font color=#FF4444>EC#730105</font></h4>\n");
			}
		}
	}
	
	if ($ISM) {
		echo ("<br>&nbsp;<br>&nbsp;\n");
		echo ("<table align='center' style='background:#EEEEEE; padding:10px; border-radius:10px; border:3px solid #FFBB88;'>");
	} else {
		echo ("<table>");
	}
	echo ("<form name='pwform' method='post' action='pInfo.php?j=pw' onsubmit='return PasswordChangeCheck()'>\n");
	echo ("	<tr><td class='TitleFont'>$str_id</td><td><input type='text' name='na' size='16' value='$sId' readonly></td></tr>\n");
	if ($ISM) {
		echo ("	<tr><td class='TitleFont'>$str_chpw_pw1</td><td><input type='password' name='pw1' size='16'><input type='hidden' name='pw1i'></td></tr>\n");
		echo ("	<tr><td class='TitleFont'>$str_chpw_pw2</td><td><input type='password' name='pw2' size='16'><input type='hidden' name='pw2i'></td></tr>\n");
		echo ("	<tr><td class='TitleFont'>$str_chpw_pw3</td><td><input type='password' name='pw3' size='16'><input type='hidden' name='pw3i'></td></tr>\n");
	} else {
		echo ("	<tr><td class='TitleFont'>$str_chpw_pw1</td><td><input type='text' name='pw1i' size='16' autocomplete=off ontouch='pwKeyCursor(this)' onclick='pwKeyCursor(this)' onselect='pwKeyCursor(this)' onkeydown='pwKeyDown()' onkeypress='pwKeyPress(this)' onblur='pwKeyUp(this)' onkeyup='pwKeyUp(this)' style='ime-mode:disabled'><input type='hidden' name='pw1'></td></tr>\n");
		echo ("	<tr><td class='TitleFont'>$str_chpw_pw2</td><td><input type='text' name='pw2i' size='16' autocomplete=off ontouch='pwKeyCursor(this)' onclick='pwKeyCursor(this)' onselect='pwKeyCursor(this)' onkeydown='pwKeyDown()' onkeypress='pwKeyPress(this)' onblur='pwKeyUp(this)' onkeyup='pwKeyUp(this)' style='ime-mode:disabled'><input type='hidden' name='pw2'></td></tr>\n");
		echo ("	<tr><td class='TitleFont'>$str_chpw_pw3</td><td><input type='text' name='pw3i' size='16' autocomplete=off ontouch='pwKeyCursor(this)' onclick='pwKeyCursor(this)' onselect='pwKeyCursor(this)' onkeydown='pwKeyDown()' onkeypress='pwKeyPress(this)' onblur='pwKeyUp(this)' onkeyup='pwKeyUp(this)' style='ime-mode:disabled'><input type='hidden' name='pw3'></td></tr>\n");
	}
	echo ("	<tr><td></td><td><input type='submit' value='$str_save' class='pure-button'></td></tr>\n");
	echo ("</form></table>\n");
	echo ("<script type='text/javascript'>\n");
	if ($ISM) echo ("pwform.pw1.focus();\n");
	else echo ("pwform.pw1i.focus();\n");
	echo ("</script>\n");

} else // 비밀번호 변경



// 제품소개 ///////////////////////////////////////////////////////////////

if ($iSel == 1) {
	if ($ISM) {
		SetMobileTitle ($str_ad);
		echo ("<div style='width:100%; max-width:644px; margin:0 auto;'>\n");
	} else {
		echo ("<h1>$str_ad</h1>\n");
	}
print <<<EOF

<style type='text/css'>
.SubSm    { background:#EEEEEE; color:#0099CC; font-weight:bold; cursor:pointer; border-radius:7px; }
.SubSm:hover { background:#DDEEFF; }
.SubSmSel { background:#444488; color:#FFFFFF; font-weight:bold; cursor:pointer; border-radius:7px; }
</style>

<div width='100%' class='OrangeBorder' style='white-space:normal'>$str_ad_msg</div>
<table border='0' cellpadding='6' cellspacing='1' style='padding-top:20px; padding-bottom:2px;'><tr>
<td id='smno1' class='SubSm' style='white-space:normal' onclick='ChangeSubMenu(1)'>$str_ad_1</td>
<td id='smno2' class='SubSm' style='white-space:normal' onclick='ChangeSubMenu(2)'>$str_ad_2</td>
<td id='smno3' class='SubSm' style='white-space:normal' onclick='ChangeSubMenu(3)'>$str_ad_3</td>
</tr></table>
<div>
<img id='smsrc' src='img/item_01.png' width='100%'>
</div>

<script type='text/javascript'>
function ChangeSubMenu(idx) {
	smsrc.src = 'img/item_0'+idx+'.png';
	for (var i=1; i<=3; i++) {
		$('#smno'+i).removeClass('SubSm');
		$('#smno'+i).removeClass('SubSmSel');
		$('#smno'+i).addClass((i==idx)?'SubSmSel':'SubSm');
	}
}
ChangeSubMenu(1);
</script>

EOF;

	if ($ISM) {
		echo ("</div>\n");
	}
} else // 제품소개



// Board ////////////////////////////////////////////////////////////////////////

{

	if ($ISM) {
		SetMobileTitle ($str_board);
	} else {
		echo ("<h1>$str_board</h1>\n");
	}
print <<<EOF
	
	<style type='text/css'>
	.greg { border-top:#EE6633 2px solid; border-bottom:#EE6633 2px solid; }
	.gtd1 { padding:10px; background:#EEEEEE; text-align:center; }
	.gtd2 { padding:10px; background:#FFFFFF; }
	.glth { height:30px; background:#EEEEEE; text-align:center; }
	.gltd { padding-top:6px; padding-bottom:6px; color:#666666; text-align:center; }
	.gltl { background:#FFFFFF; }
	.gltl:hover { background:#FFEEDD; cursor:pointer; }
	</style>
	<script type='text/javascript'>
	function BoardDelConfirm(idx) {
		document.location = "pInfo.php?j=bd&idx="+idx;
	}
	function BoardDel (idx) {
		msg = "<p>$str_delete_confirm</p>";
		msg += "<input type='button' value='$str_cancel' onclick='MsgPopupHide()' class='pure-button'> ";
		msg += "<input type='button' value='$str_delete' onclick='BoardDelConfirm("+idx+")' class='pure-button'>";
		MsgPopupShow (msg, 60*60*1000);
	}
	function BoardRead (idx) {
		document.location = 'pInfo.php?j=br&idx='+idx;
	}
	</script>

EOF;
	
	if (($job == 'bw') && isset($sId)) {
		// Board write
		
		$iIdx = $_GET['idx'];
		$iEditMode = 0;
		if (isset($iIdx)) {
			// Edit post
			$sQuery = "select * from board where idx='$iIdx'";
			if ($sId != 'admin') $sQuery .= " and `user_id`='$sId'";
			$cResult = mysql_query($sQuery) or die('EC#730105');
			$aRow = mysql_fetch_array($cResult) or die('EC#730106');
			$sTitle = $aRow['title'];
			$sContent = $aRow['content'];
			$sUserName = $aRow['user_name'];
			$sEmail = $aRow['user_email'];
			$sFile = $aRow['file_name'];
			$iEditMode = 1;
		} else {
			// New post
			$sQuery = "select `NAME` from member where `ID`='$sId'";
			$cResult = mysql_query($sQuery) or die('EC#730105');
			$aRow = mysql_fetch_row($cResult) or die('EC#730106');
			$sUserName = ($aRow[0] != '') ? $aRow[0] : $sId;
		}
		
print <<<EOF
		<form name='write' method='post' action='pInfo.php?j=bo' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='$iEditMode'>
		<input type='hidden' name='idx' value='$iIdx'>
		
			<table width='100%' border='0' cellpadding='0' cellspacing='0' class='greg'>
			<tr>
				<td width='18%' class='gtd1'>$str_board_title</td>
				<td colspan='3' class='gtd2'><input name='title' type='text' autocomplete='off' style='width:100%;' maxlength='50' value='$sTitle'></td>
			</tr><tr><td colspan='4' height='1' style='background:#CCCCCC'></td></tr><tr>
				<td class='gtd1'>$str_board_name</td>
				<td width='19%' class='gtd2'><input name='user_name' type='text' style='width:100%;' maxlength='20' autocomplete='off' value='$sUserName'></td>
				<td width='18%' class='gtd1'>$str_board_email</td>
				<td width='45%' class='gtd2'><input name='email' type='text' style='width:100%;' maxlength='50' autocomplete='off' value='$sEmail'></td>
			</tr><tr><td colspan='4' height='1' style='background:#CCCCCC'></td></tr><tr>
				<td class='gtd1'>$str_board_content</td>
				<td colspan='3' class='gtd2'><textarea name='content' rows='15' style='width:100%;'>$sContent</textarea></td>
			</tr><tr><td colspan='4' height='1' style='background:#CCCCCC'></td></tr><tr>
				<td class='gtd1'>$str_board_file</td>
				<td colspan='3' class='gtd2'>

EOF;
		if ($sFile != '') echo ("<div style='margin-bottom:5px'>$sFile</div>");
print <<<EOF
				<input name='file' type='file' autocomplete='off'></td>
			</tr>
		</table>
		<p align='center'><input type='button' value='$str_save' class='pure-button' onclick='BoardWriteCheck()'></p>
		</form>
		<script type='text/javascript'>
		function BoardWriteCheck() {
			if (write.title.value == '') {
				MsgPopupError ('$str_board_err1', 2000);
				write.title.focus();
				return;
			}
			if (write.user_name.value == '') {
				MsgPopupError ('$str_board_err2', 2000);
				write.user_name.focus();
				return;
			}
			if (write.content.value == '') {
				MsgPopupError ('$str_board_err3', 2000);
				write.content.focus();
				return;
			}
			write.submit();
		}
		write.title.focus();
		</script>
EOF;
	} else
	if (($job == 'bo') && isset($sId)) {
		// Save post
		$sTitle = $_POST['title'];
		$sContent = $_POST['content'];
		$sUserName = $_POST['user_name'];
		$sEmail = $_POST['email'];
		$sFile = $_FILES['file']['name'];
		$iBoardType = $_POST['btype'];
		$iEditMode = $_POST['mode'];
		$iIdx = $_POST['idx'];
		
		$sTitle = htmlspecialchars ($sTitle, ENT_QUOTES);
		$sContent = htmlspecialchars ($sContent, ENT_QUOTES);
		$sUserName = htmlspecialchars ($sUserName, ENT_QUOTES);
		$sEmail = htmlspecialchars ($sEmail, ENT_QUOTES);
		$sFile = htmlspecialchars ($sFile, ENT_QUOTES);
		
		if (!isset($iBoardType)) $iBoardType = 1;
		
		if ($sTitle == '' || $sContent == '' || $sUserName == '') {
			$sAlertMsg = $str_board_err4;
			$sAlertAction = 'history.back()';
		} else {
			if ($iEditMode == 0) {
				// Save new post
				$cResult = mysql_query("select ifnull(max(idx),0)+1 from board") or die('EC#730105');
				$aRow = mysql_fetch_row($cResult) or die('EC#730106');
				$iIdx = $aRow[0];
				$cResult = mysql_query("select ifnull(max(num),0)+1 from board where board_type=$iBoardType") or die('EC#730105');
				$aRow = mysql_fetch_row($cResult) or die('EC#730106');
				$iNum = $aRow[0];
				$sQuery ="insert into board values ($iIdx, $iNum, '$sTitle', '$sContent', $iBoardType, '$sFile', '".$sId."', '$sUserName', '$sEmail', '', 0, now())";
			} else {
				// Save edited post
				$sQuery = "update `board` set `title`='$sTitle', `content`='$sContent', `user_name`='$sUserName', `user_email`='$sEmail'";
				if ($sFile != '') $sQuery .= ", `file_name`='$sFile'";
				$sQuery .= " where `idx`='$iIdx'";
				if ($sId != 'admin') $sQuery .= " and `user_id`='$sId'";
			}
			if (mysql_query($sQuery) != 1) {
				$sAlertMsg = $str_board_err4;
				$sAlertAction = 'history.back()';
			} else {
				if ($sFile != '') {
					$uploadfile = 'upload/'.$iIdx;
					move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile);
				}
				$sAlertMsg = $str_save_ok;
				$sAlertAction = 'document.location.replace("pInfo.php")';
			}
		}
	} else
	if (($job == 'bd') && isset($sId)) {
		// Delete post
		
		$iIdx = $_GET['idx'];
		if ($iIdx == '') {
			$sAlertMsg = $str_delete_fail;
			$sAlertAction = 'history.back()';
		} else {
			$sQuery = "delete from `board` where `idx`='$iIdx'";
			if ($sId != 'admin') $sQuery .= " and `user_id`='$sId'";
			mysql_query($sQuery) or die('EC#730103');
			if (0 < mysql_affected_rows()) {
				@unlink('upload/'.$iIdx);
				$sAlertMsg = $str_delete_ok;
				$sAlertAction = 'document.location.replace("pInfo.php")';
			} else {
				$sAlertMsg = $str_delete_fail;
				$sAlertAction = 'history.back()';
			}
		}
	} else
	if ($job == 'br') {
		// Read post
		
		$iIdx = $_GET['idx'];
		
		$sQuery = "select * from board where idx=$iIdx";
		$cResult = mysql_query($sQuery) or die('EC#730105');
		$aRow = mysql_fetch_array($cResult) or die('EC#730106');
		
		$sTitle = $aRow['title'];
		$sContent = nl2br($aRow['content']);
		$sUserName = $aRow['user_name'];
		$sEmail = $aRow['user_email'];
		$sFile = $aRow['file_name'];
		$sUserId = $aRow['user_id'];
		$iReadCount = $aRow['read_count'];
		$sRegDate = $aRow['reg_date'];
		
		// Read count++
		$iReadCount++;
		$sQuery = "update `board` set `read_count`='$iReadCount' where `idx`='$iIdx'";
		mysql_query($sQuery);
		
		echo ("	<table width='100%' border='0' cellpadding='0' cellspacing='0' class='greg'>\n");
		echo ("	<tr>\n");
		echo ("		<td class='gtd1'>$str_board_title</td>\n");
		echo ("		<td colspan='5' class='gtd2'>$sTitle</td>\n");
		echo ("	</tr><tr><td colspan='6' height='1' style='background:#CCCCCC'></td></tr><tr>\n");
		echo ("		<td class='gtd1'>$str_board_name</td>\n");
		echo ("		<td class='gtd2'>$sUserName</td>\n");
		echo ("		<td class='gtd1'>$str_board_date</td>\n");
		echo ("		<td class='gtd2'>$sRegDate</td>\n");
		echo ("		<td class='gtd1'>$str_board_count</td>\n");
		echo ("		<td class='gtd2'>$iReadCount</td>\n");
		if ($sId == 'admin') {
			echo ("	</tr><tr><td colspan='6' height='1' style='background:#CCCCCC'></td></tr><tr>\n");
			echo ("		<td class='gtd1'>$str_id</td>\n");
			echo ("		<td class='gtd2'>$sUserId</td>\n");
			echo ("		<td class='gtd1'>$str_board_email</td>\n");
			echo ("		<td class='gtd2' colspan='3'>$sEmail</td>\n");
		}
		echo ("	</tr><tr><td colspan='6' height='1' style='background:#CCCCCC'></td></tr><tr>\n");
		echo ("		<td colspan=6 style='white-space:normal' class='gtd2'>$sContent</td>\n");
		if ($sFile != null) {
			echo ("	</tr><tr><td colspan='6' height='1' style='background:#CCCCCC'></td></tr><tr>\n");
			echo ("		<td class='gtd1'>$str_board_file</td>\n");
			echo ("		<td class='gtd2' colspan='5'><a href='./upload/$iIdx' target='_blank' download='$sFile'>$sFile</a></td>\n");
		}
		echo ("	</tr>\n");
		echo ("	</table>\n");
		echo ("	<p align='right'>\n");
		echo ("		<a href='pInfo.php' class='pure-button'>$str_board_list</a>\n");
		
		if (isset($sId) && (($sId == $aRow['user_id']) || ($sId == 'admin'))) {
			echo ("		&nbsp;\n");
			echo ("		<a href='pInfo.php?j=bw&idx=$iIdx' class='pure-button'>$str_edit</a>\n");
			echo ("		&nbsp;\n");
			echo ("		<a href='javascript:BoardDel($iIdx)' class='pure-button'>$str_delete</a>\n");
		}
		echo ("	</p>\n");
		
	} else {
		// List
		$iPage = $_GET['page'];
		$iBoardType = 1;
		$iBoardPageSize = GetSettingInt ($cDb, 'boardline', 20, 5, 1000);	// Number of posts per page
		
		$sQuery = "select count(*) from board where board_type=$iBoardType";
		$cResult = mysql_query($sQuery) or die('EC#730105');
		$aRow = mysql_fetch_row($cResult) or die('EC#730106');
		
		$tCount = $aRow[0];
		$tPage = ($tCount < $iBoardPageSize) ? 1 : ceil($tCount / $iBoardPageSize);
		if ($iPage < 1) $iPage = 1;
		if ($tPage < $iPage) $iPage = $tPage;
		
		echo ("<table width='100%' border='0' cellpadding='0' cellspacing='0' class='greg'>\n");
		echo ("<tr>\n");
		echo ("	<td width='10%' class='glth'>$str_board_number</td>\n");
		echo ("	<td width='1' style='background:#CCCCCC'></td>\n");
		echo ("	<td width='*' class='glth'>$str_board_title</td>\n");
		echo ("	<td width='3%' class='glth'></td>\n");
		echo ("	<td width='1' style='background:#CCCCCC'></td>\n");
		echo ("	<td width='14%' class='glth'>$str_board_name</td>\n");
		echo ("	<td width='1' style='background:#CCCCCC'></td>\n");
		echo ("	<td width='14%' class='glth'>$str_board_date</td>\n");
		echo ("	<td width='1' style='background:#CCCCCC'></td>\n");
		echo ("	<td width='10%' class='glth'>$str_board_count</td>\n");
		echo ("</tr>\n");
		if ($tCount == 0) {
			echo ("<tr><td colspan='10' height='1' style='background:#CCCCCC'></td></tr>\n");
			echo "<tr><td class='gltd' colspan='10'>$str_board_err6</td></tr>";
		} else {
			$sQuery = "select * from board where board_type=$iBoardType order by num desc limit ".($iBoardPageSize*($iPage-1)).", $iBoardPageSize";
			$cResult = mysql_query($sQuery) or die('EC#730105');
			while ($aRow = mysql_fetch_array($cResult)) {
				$i = $aRow['idx'];
				echo ("<tr><td colspan='10' height='1' style='background:#CCCCCC'></td></tr>\n");
				echo "<tr class='gltl' onclick='BoardRead($i)')>";
				echo "<td class='gltd'>".$aRow['num']."</td>";
				echo ("<td></td>");
				echo "<td class='gltd' style='white-space:normal'>".$aRow['title']."</td>";
				echo "<td class='gltd'>";
				if ($aRow['file_name'] != null) echo "@";
				echo "</td>";
				echo ("<td></td>");
				echo "<td class='gltd'>".$aRow['user_name']."</td>";
				echo ("<td></td>");
				echo "<td class='gltd'>".substr($aRow['reg_date'],0,10)."</td>";
				echo ("<td></td>");
				echo "<td class='gltd'>".$aRow['read_count']."</td>";
				echo "</tr>";
			}
		}
		echo ("</table>\n");
		echo ("<p>\n");
		if (1 < $tPage) {
			echo ("<script type='text/javascript'>\n");
			echo ("function PageChange(obj) {\n");
			echo ("	document.location='pInfo.php?page='+obj.value;\n");
			echo ("}\n");
			echo ("function PageGoto(page) {\n");
			echo ("	document.location='pInfo.php?page='+page;\n");
			echo ("}\n");
			echo ("</script>\n");
			echo ("<center>");
			if ($tPage < 11) {
				for ($i=1; $i<=$tPage; $i++) {
					if (1 < $i) echo (" / ");
					if ($i == $iPage) echo ("<b>");
					echo ("<font color='#000000' onclick='PageGoto($i)' style='cursor:pointer'>$i</font>");
					if ($i == $iPage) echo ("</b>");
				}
			} else {
				// too many pages
				if (1 < $iPage) echo ("<font color='#000000' onclick='PageGoto(".($iPage-1).")' style='cursor:pointer'>$str_move_left</font>");
				else echo ("<font color='#AAAAAA'>$str_move_left</font>");
				echo ("<select OnChange='PageChange(this)' style='margin:5px'>");
				for ($i=1; $i<=$tPage; $i++) echo ("<option value='$i'".(($iPage==$i)?" selected":"").">{$i}</option>");
				echo ("</select>");
				if ($iPage < $tPage) echo ("<font color='#000000' onclick='PageGoto(".($iPage+1).")' style='cursor:pointer'>$str_movr_right</font>");
				else echo ("<font color='#AAAAAA'>$str_movr_right</font>");
			}
			echo ("</center>\n");
		}
		if (isset($sId)) echo ("<div align='right'><a href='pInfo.php?j=bw' class='pure-button'>$str_board_write</a></div></p>\n");
	}
}



mysql_close ($cDb);

if (!$ISM) {
	echo ("</td></tr></table>\n");
}

print <<<EOF

<div class='TopCurtain'></div>
<div id='msgPopupBox' class='msgPopupBox' onclick='MsgPopupHide()'><div class='msgPopupInn'><div id='msgPopupTxt' class='msgPopupTxt'></div></div></div>

</body>

<script type='text/javascript'>
$(document).keydown(function(e) {
	if (e.keyCode == 27) MsgPopupHide ();
});
</script>

EOF;
// Perform a scheduled action
if (isset($sAlertMsg)) {
	echo ("<script type='text/javascript'>\n");
	echo ("MsgPopupOnly ('$sAlertMsg',0);\n");
	echo ("setTimeout('$sAlertAction',1000);\n");
	echo ("</script>\n");
}
?>

</html>
