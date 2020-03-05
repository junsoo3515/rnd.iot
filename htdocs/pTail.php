<?php

require_once('common.php');

PrintHtmlHead ();

?>

<script type='text/javascript'>

function SetTailContent () {
	if (parent.framesize == 0) {
		tailbody.innerHTML = "<img src='img/footer.gif'>";
		tailszbt.innerHTML = "<?=$str_move_down?>";
	} else {
		tailbody.innerHTML = "<img src='img/footersmall.gif'>";
		tailszbt.innerHTML = "<?=$str_move_up?>";
	}
}

</script>

</head>
<body style='cursor:default' onload='SetTailContent()'>
<div id='tailbody' style='position:fixed; top:0; bottom:0; left:0; right:0; background:url(img/footerbg.gif) repeat; text-align:center;'></div>
<div id='tailszbt' style='position:fixed; right:3px; top:1px; color:#CCCCCC; cursor:pointer; font-size:12px;' onclick='parent.ChangeSize()'></div>
</body>
</html>
