<!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=10">
<title></title>
<style>
body {
	position: absolute;
	top: 0px;
	right: 0px;
	bottom: 0px;
	left: 0px;
}
</style>

<script type='text/javascript' src='http://10.13.50.26:3310/js/jquery.js'></script>

</head>
<body>

<!--bject id="vlcPlayer" classid="clsid:9BE31822-FDAD-461B-AD51-BE1D1C159921" width="100%" height="100%">
	<param name="autostart" value="false" />
	<param name="allowfullscreen" value="true" />
	<param name="controls" value="false" />
</object-->

<script>
	$(function() {
		
		if (navigator.plugins && navigator.mimeTypes && navigator.mimeTypes.length)
		  document.write('<object type="application/x-vlc-plugin" version="VideoLAN.VLCPlugin.2" pluginspage="http://www.videolan.org"');
	   else   // for IE ActiveX
		  document.write('<object classid="clsid:9BE31822-FDAD-461B-AD51-BE1D1C159921" codebase="http://download.videolan.org/pub/videolan/vlc/0.8.6c/win32/axvlc.cab"');
	   document.writeln(' id="vlcPlayer" name="vlcPlayer" width="100%" height="100%">');
	//   OK, But I'll change the MRL later.
	//   document.writeln('\t<param name="MRL" value="" />');
	//   Bug??, cannot work with IE
	   document.writeln('\t<param name="autostart" value="false" />');
	   document.writeln('\t<param name="allowfullscreen" value="true" />');
	   document.writeln('\t<param name="controls" value="false" />');
	   document.writeln('</object>');
		
		console.log('로딩');
		console.log(localStorage.getItem('cctvInfo'));
		var cctvInfo = JSON.parse(localStorage.getItem('cctvInfo'));
		
		if(cctvInfo.cctvnm != "") {
			document.title = cctvInfo.cctvnm;
		}
		
		console.log(cctvInfo.cctv_use_fl);

		if(cctvInfo.cctv_use_fl == "Y") {
			console.log(cctvInfo.url);
			var vlc = document.getElementById('vlcPlayer');
			//vlc.playlist.add('rtsp://admin:rkdtjrn1!@10.13.132.25:554');
			vlc.playlist.add(cctvInfo.url);
			vlc.playlist.play();
			console.log('[RTSP] play.');
		}
		if(cctvInfo.cctv_use_fl == "N") {
			alert('사용불가 CCTV 입니다.');
			console.log('[RTSP] play fail');
		}
	});
</script>
</body>
</html>