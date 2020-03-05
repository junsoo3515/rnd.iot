<?php

require_once('common.php');

session_start();

$sId = $_SESSION[$sDbDb.'_id'];
$org = $_GET['org'];
if (!isset($org)) $org = 'pRealtime.php';

// Delete Database. Admin only
$opt = $_GET['opt'];
if (($opt == 1) && ($sId != 'admin')) {
	die ("<html><head><meta http-equiv='refresh' content='0;url=pRealtime.php?login=1'></head></html>");
}

// Connect DB
$cDb = mysql_connect($sDbIp, $sDbId, $sDbPw);
if (!$cDb) {
	die("<html><body>EC#730008</body></html>");
}
mysql_query('set names utf8');

// delete all files
function rmdirAll($dir) {
	$cnt = 0;
	$dirs = dir($dir);
	while(false !== ($entry = $dirs->read())) {
		if(($entry != '.') && ($entry != '..')) {
			$fn = $dir.'/'.$entry;
			if(is_dir($fn)) $cnt += rmdirAll($fn);
			else {
				@unlink($fn);
				$cnt++;
			}
		}
	}
	$dirs->close();
	@rmdir($dir);
	return $cnt;
}

PrintHtmlHead ();
echo("</head><body>");

// Localhost only
$sIpOnly = explode(":", $sDbIp);
if ($_SERVER['REMOTE_ADDR'] !== $sIpOnly[0]) {
	echo("Error: Can not delete DB from remote.<br><br>");
	echo("Delete DB is only allowed on the server.<br><br>");
	echo("<a href='$org'>GO BACK</a><br>");
	echo("</body></html>");
	mysql_close ($cDb);
	die();
}

// Delete Database.
$msg = $_GET['msg'];
if (($opt == 1) && ($msg == 'delete db')) {
	$sQuery = "DROP DATABASE `$sDbDb`";
	if (mysql_query($sQuery, $cDb)) {
		echo ("Drop database $sDbDb OK<br>");
		LogMsg ("Drop database $sDbDb, $sId");
	}
	
	// Delete all board attachments.
	$cnt = rmdirAll ('upload');
	if (0 < $cnt) {
		echo ("Delete $cnt board attachments<br>");
	}
}

// Check DB. Admin only
if (mysql_select_db($sDbDb, $cDb)) {
	if ($sId != 'admin') {
		echo ("<script type='text/javascript'>");
		echo ("document.location.replace('pRealtime.php?login=1');");
		echo ("</script>");
		echo("</body></html>");
		mysql_close ($cDb);
		die();
	}

print <<<EOF

<script type='text/javascript'>
var msgstate = false;
function deletedb () {
	return msgstate;
}
function checkmsg () {
	var newmsgstate = (dd.msg.value == 'delete db');
	if (msgstate != newmsgstate) {
		msgstate = newmsgstate;
		if (msgstate) {
			bt.style.background = '#000080';
			bt.style.color = '#FFFFFF';
			bt.style.cursor = 'pointer';
		} else {
			bt.style.background = '#F0F0F0';
			bt.style.color = '#C0C0C0';
			bt.style.cursor = '';
		}
	}
}
setInterval('checkmsg()',500);
</script>
DB already exists.<br>
<p><form id='dd' method='get' onsubmit='return deletedb()'>
<b><font color='red'>Warning!</font></b> All data will be permanently deleted.<br>
This action cannot be undone. Do you want to continue?<br><br>
Type 'delete db' to confirm deletion of all data.<br>
<input type='hidden' name='opt' value='1'>
<input type='hidden' name='org' value='$org'>
<div style='padding:5px'>
<input type='text' name='msg' placeholder="Type 'delete db'"> 
<input id='bt' type='submit' value='&nbsp;Delete DB&nbsp;' style='background:#F0F0F0; color:#C0C0C0;'>
</div>
</form></p>
<a href='$org'>GO BACK</a><br>
</body></html>

EOF;

	mysql_close ($cDb);
	die();
}

// Create folds.
$sPath = 'log';
if (!is_dir($sPath)) mkdir($sPath);
$sPath = 'upload';
if (!is_dir($sPath)) mkdir($sPath);

// Create DB
$sQuery = "CREATE DATABASE `$sDbDb`";
mysql_query($sQuery, $cDb) or die('Can not create db : '.mysql_error().'</body></html>');
echo ("Create database $sDbDb OK<br>");

// Select DB
mysql_select_db($sDbDb, $cDb) or die('Can not found db : '.mysql_error().'</body></html>');

// Create Table 'setting'
$sQuery = "CREATE TABLE `setting` ("
		."`ID` char(20) NOT NULL,"
		."`VALUE` varchar(10000) NOT NULL,"
		."PRIMARY KEY (`ID`)) ENGINE=MyISAM default CHARSET=utf8";
mysql_query($sQuery, $cDb) or die('Can not create table setting : '.mysql_error().'</body></html>');
$sQuery = "INSERT INTO `setting` VALUES ('rsakey', 'MIICXAIBAAKBgQCYd39DyDv+ZMw5XCLa6+jm39bYlf6PQtY5ZA4UFkuQ08Bi8PZBcbowZKcZpSVq 5zimH3kRnqLd7Fb8Q7eOtvHFfLmjBscXPfAgGzEydrn9RQ6Q2DALjRgBv9EtNuLT7PN7cL19OkkX W808lCehRpcop0yMAPUfBRfq6R7n2MgiVwIDAQABAoGAOmekP8HngXb5aIur6nLuX+qlMYib4CM5 TvjOD9HOlqcXDo/BtaYsLbeQ71j88Wurnq91wFMljp+nXrFOEoRwLpzlXYbv30QyFDEfQYplCSOQ dqg/NOJVUQcJFDmSCINWv2b/s4xlhyd93b7Z4DTzZgdZ7Bd21LdYpgaEjS+FuikCQQCfyrTNAyQv uguYNRb9pZEGpBEp374/XjCwNE9g88jC8cnxCA+b913lKSiy1moUnOhQFFwNNzUI8QyIyEOgkL8r AkEA9EPBq854iSpMZnqoXFP8juqiWBPcq5vyCPkrL08xH9ZeQw4L1jWLR75x87JIKkMmr+o8UT2S ECpH6lS1AN3zhQJAPWsGj35TnFygNELDsX6//ZN2XWf4kha8FB4nHJbXZcbV3eVBSquL1Zc6Y3Ly lzWwPYd10kaUBfnQ3YpMJB0+vQJBAJlaRKgRi3lKFgcssLCuhdxQELZNWfhfgpgBnwwnosbDNhDR K7tDiHr1ZONDpazq1coRu+ahSidd9CJ5Jd4yemUCQFFtd/sjqgnzqeV6p0hTIcgS4pYLoazHZa/c MKM7d0c72WKKTgP34piUyS2VYORiUTNMyK+Ydp7k5CoEonTAM+I=')";
mysql_query($sQuery, $cDb) or die('Can not insert into setting : '.mysql_error().'</body></html>');
$sQuery = "INSERT INTO `setting` VALUES ('version', '2')";
mysql_query($sQuery, $cDb) or die('Can not insert into setting : '.mysql_error().'</body></html>');
echo ("Create table setting OK<br>");

// Create Table 'member' & add 'admin'
$sQuery = "CREATE TABLE `member` ("
		."`ID` char(20) NOT NULL,"
		."`PW` char(64) NOT NULL,"
		."`NAME` char(40) NOT NULL,"
		."`ECNT` int default 0,"
		."`ETIME` bigint default 0,"
		."PRIMARY KEY (`ID`)) ENGINE=MyISAM default CHARSET=utf8";
mysql_query($sQuery, $cDb) or die('Can not create table member : '.mysql_error().'</body></html>');
$sQuery = "INSERT INTO `member` VALUES ('admin', password('1'), '', '0', '0')";
mysql_query($sQuery, $cDb) or die('Can not insert into member : '.mysql_error().'</body></html>');
echo ("Create table member OK<br>");

// Create Table 'items' & add $aDbItems
$sQuery = "CREATE TABLE `items` ("
		."`ID` char(16) NOT NULL,"
		."`NAME` char(20) NOT NULL,"
		."`UNIT` char(16) default NULL,"
		."`DEC` tinyint default 2,"		// The number of decimal points.
		."`LO` double default NULL,"
		."`HI` double default NULL,"
		."`BOTTOM` double default NULL,"
		."`TOP` double default NULL,"
		."`REMARK` varchar(400) default NULL,"
		."`ORDER` int NOT NULL,"
		."`USING` int NOT NULL,"
		."PRIMARY KEY (`ID`)) ENGINE=MyISAM default CHARSET=utf8";
mysql_query($sQuery, $cDb) or die('Can not create table item : '.mysql_error().'</body></html>');
for ($i=0; $i<sizeof($aDbItems); $i++) {
	$using = ($i == 0) ? 1 : 2;		// 0:N/A, 1:Important, 2:Realtime, 3:Normal
	$sQuery = "INSERT INTO `items` VALUES ('$aDbItems[$i]', '$aDbItems[$i]', '', '$aSbItemDec[$i]', NULL, NULL, NULL, NULL, '', '$i', '$using')";
	mysql_query($sQuery, $cDb) or die('Can not insert into items : '.mysql_error().'</body></html>');
}
echo ("Create table items OK<br>");

// *** Single DB only ***
if (!isset($sDbs)) {
	
	// Create Table 'sites'
	$sQuery = "CREATE TABLE `sites` ("
			."`SITENO` smallint NOT NULL,"
			."`NAME` varchar(50) NOT NULL,"
			."`LNG` double default NULL,"
			."`LAT` double default NULL,"
			."`ADDR` varchar(200) default NULL,"
			."`FILE` tinyint default NULL,"		// NULL:Using, 1:Hide
			."`REMARK` varchar(400) default NULL,"
			."PRIMARY KEY (`SITENO`)) ENGINE=MyISAM default CHARSET=utf8";
	mysql_query($sQuery, $cDb) or die('Can not create table sites : '.mysql_error().'</body></html>');
	$sQuery = "INSERT INTO `sites` VALUES ('101', 'Site101', '127.390136', '36.4295159', 'www.sclab.co.kr', NULL, 'SLC')";
	mysql_query($sQuery, $cDb) or die('Can not insert into sites : '.mysql_error().'</body></html>');
	echo ("Create table sites OK<br>");
	
	// Create Table 'weathers'
	$sQuery = "CREATE TABLE `weathers` ("
			."`WDATE` int NOT NULL,"
			."`WXY` int NOT NULL,"
			."`WDATA` char(80) default NULL,"
			."`PTY` tinyint default NULL,"		// Rain type
			."`REH` tinyint default NULL,"		// Humidity
			."`RN1` float   default NULL,"		// 1 hours rainfall
			."`SKY` tinyint default NULL,"		// Sky conditions
			."`T1H` float   default NULL,"		// Temperatures
			."`VEC` tinyint default NULL,"		// Wind direction
			."`WSD` float   default NULL,"		// Wind speed
			."PRIMARY KEY (`WDATE`,`WXY`)) ENGINE=MyISAM default CHARSET=utf8";
	mysql_query($sQuery, $cDb) or die('Can not create table weathers : '.mysql_error().'</body></html>');
	echo ("Create table weathers OK<br>");
	
	// Create Table 'datas'
	$sQuery = "CREATE TABLE `datas` ("
			."`SITENO` smallint NOT NULL,"
			."`DATE` int NOT NULL,"
			."`LNG` double default NULL,"
			."`LAT` double default NULL,"
			."`WDATE` int default NULL,"
			."`WXY` int default NULL,";
	for ($i=0; $i<sizeof($aDbItems); $i++) {
		$sQuery .= "`$aDbItems[$i]` ";
		if (($aSbItemDec[$i] == 10) || ($aSbItemDec[$i] == 11)) $sQuery .= "tinyint";
		else $sQuery .= "float";
		$sQuery .= " default NULL,";
	}
	$sQuery .= "PRIMARY KEY (`SITENO`,`DATE`)) ENGINE=MyISAM default CHARSET=utf8";
	mysql_query($sQuery, $cDb) or die('Can not create table datas : '.mysql_error().'</body></html>');
	echo ("Create table datas OK<br>");
	
	// Create Table 'lasts'
	$sQuery = "CREATE TABLE `lasts` ("
			."`SITENO` smallint NOT NULL,"
			."`DATE` int NOT NULL,"
			."`LNG` double default NULL,"
			."`LAT` double default NULL,"
			."`WDATA` char(80) default NULL,";
	for ($i=0; $i<sizeof($aDbItems); $i++) $sQuery .= "`$aDbItems[$i]` double default NULL,";
	$sQuery .= "PRIMARY KEY (`SITENO`)) ENGINE=MyISAM default CHARSET=utf8";
	mysql_query($sQuery, $cDb) or die('Can not create table lasts : '.mysql_error().'</body></html>');
	echo ("Create table lasts OK<br>");
	
	// Create Table 'board'
	$sQuery = "CREATE TABLE IF NOT EXISTS `board` ("
			."`idx` int(10) NOT NULL,"						// Position in 'board' table
			."`num` int(10) NOT NULL,"						// Position in `board_type`
			."`title` varchar(100) NOT NULL,"
			."`content` varchar(10000) NOT NULL,"
			."`board_type` int(2) NOT NULL default '1',"	// board ID
			."`file_name` varchar(100) default NULL,"
			."`user_id` varchar(20) NOT NULL,"
			."`user_name` varchar(40) NOT NULL,"
			."`user_email` varchar(100) default NULL,"
			."`password` varchar(20) NOT NULL,"
			."`read_count` int(10) NOT NULL default '0',"
			."`reg_date` datetime NOT NULL,"
			."PRIMARY KEY (`idx`)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
	mysql_query($sQuery, $cDb) or die('Can not create table board : '.mysql_error().'</body></html>');
	echo ("Create table board OK<br>");
	
}

// Completed.
echo("Completed.<br><br>");
echo("<a href='$org'>");
echo("GO BACK</a><br>");

// HTML tail
echo("</body></html>");
mysql_close ($cDb);

?>
