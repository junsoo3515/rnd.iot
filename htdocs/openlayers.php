<?php
print <<<EOF

<link rel="stylesheet" href="js/ol.css" />
<script src="js/ol.js"></script>

<style>
.ol-popup {
	position: absolute;
	background-color: white;
	padding: 0px;
	border-radius: 2px;
	border: 1px solid #606060;
	bottom: 20px;
	transform: translateX(-50%);
}
.ol-popup:after, .ol-popup:before {
	top: 100%;
	border: solid transparent;
	content: " ";
	height: 0;
	width: 0;
	position: absolute;
	pointer-events: none;
}
.ol-popup:after {
	border-top-color: white;
	border-width: 10px;
	left: 50%;
	margin-left: -10px;
}
.ol-popup:before {
	border-top-color: #606060;
	border-width: 11px;
	left: 50%;
	margin-left: -11px;
}
</style>

<script type='text/javascript'>

var pathLine = null;
var pathLines = [];
var markerIcon = [];
var infowindow;
var infowindows = [];



// Register popup divs /////////////////////////////////////////////////////////

var msg = "<div id='pud_' class='ol-popup'></div>";
if (typeof map_popups_no !== 'undefined') {
	for (var i=0; i<map_popups_no; i++) msg += "<div id='pud_"+i+"' class='ol-popup'></div>";
}
container.innerHTML = msg;
msg = undefined;



// Drag marker /////////////////////////////////////////////////////////////////

window.app = {};
var app = window.app;

app.Drag = function() {
	if (typeof makeMarkerDragStart == 'function') {
		ol.interaction.Pointer.call(this, {
			handleDownEvent: app.Drag.prototype.handleDownEvent,
			handleDragEvent: app.Drag.prototype.handleDragEvent,
			handleMoveEvent: app.Drag.prototype.handleMoveEvent,
			handleUpEvent: app.Drag.prototype.handleUpEvent
		});
	} else {
		ol.interaction.Pointer.call(this, {
			handleMoveEvent: app.Drag.prototype.handleMoveEvent
		});
	}
	this.coordinate_ = null;
	this.feature_ = null;
	this.overfeature_ = null;
};
ol.inherits(app.Drag, ol.interaction.Pointer);

app.Drag.prototype.handleDownEvent = function(evt) {
	var feature = evt.map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) { return feature; });
	if (feature) {
		this.coordinate_ = evt.coordinate;
		this.feature_ = feature;
		try {
			feature.olMarkerDragStart ();
		} catch (err) { }
	}
	return !!feature;
};

app.Drag.prototype.handleDragEvent = function(evt) {
	var feature = evt.map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) { return feature; });
	var deltaX = evt.coordinate[0] - this.coordinate_[0];
	var deltaY = evt.coordinate[1] - this.coordinate_[1];
	var geometry = (this.feature_.getGeometry());
	geometry.translate(deltaX, deltaY);
	this.coordinate_[0] = evt.coordinate[0];
	this.coordinate_[1] = evt.coordinate[1];
};

app.Drag.prototype.handleMoveEvent = function(evt) {
	var feature = evt.map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) { return feature; });
	if (feature != this.overfeature_) {
		if (this.overfeature_) {
			try { this.overfeature_.olMarkerOutListener(); } catch (err) { }
		}
		this.overfeature_ = feature;
		if (this.overfeature_) {
			try { this.overfeature_.olMarkerOverListener(); } catch (err) { }
		}
		try {
			if (typeof this.overfeature_.olMarkerClickListener == 'function') evt.map.getTarget().style.cursor = 'pointer';
			else evt.map.getTarget().style.cursor = '';
		} catch (err) {
			evt.map.getTarget().style.cursor = '';
		}
	}
};

app.Drag.prototype.handleUpEvent = function(evt) {
	try {
		this.feature_.olMarkerDragEnd ();
	} catch (err) { }
	this.coordinate_ = null;
	this.feature_ = null;
	return false;
};



// Register map ////////////////////////////////////////////////////////////////

var vectorSource = new ol.source.Vector({
	features: markers
});

var vectorLayer = new ol.layer.Vector({
	source: vectorSource
});

var olTile;
var maptype = '$sMapType';
if (maptype == 'baidu') {
	// Baidu map
	var resolutions = [];
	for(var i=0; i<19; i++) resolutions[i] = Math.pow(2,18-i);
	olTile = new ol.source.TileImage ({
		projection: ol.proj.get ("EPSG:3857"),
		tileGrid: new ol.tilegrid.TileGrid ({
			origin: [0,0],
			resolutions: resolutions
		}),
		maxZoom: 19,
		tileUrlFunction: function(tileCoord, pixelRatio, proj){
			if(!tileCoord) return "";
			var z = tileCoord[0];
			var x = tileCoord[1];
			var y = tileCoord[2];
			if(x<0) x = 'M' + (-x);
			if(y<0) y = 'M' + (-y);
			return "http://online3.map.bdimg.com/onlinelabel/?qt=tile&x="+x+"&y="+y+"&z="+z+"&styles=pl&udt=20151021&scaler=1&p=1";
		}
	});
} else
if (maptype == 'vworld') {
	// VWorld map
	olTile = new ol.source.XYZ({
		url : 'http://xdworld.vworld.kr:8080/2d/Base/201612/{z}/{x}/{y}.png'
	});
} else
if (maptype == 'static') {
	olTile = new ol.source.OSM({
		projection: ol.proj.get ("EPSG:3857"),
		url: 'map/{z}_{x}_{y}.jpg'
	});
} else {
	// Open street map
	olTile = new ol.source.OSM();
}

var rasterLayer = new ol.layer.Tile ({
	source: olTile
});

map = new ol.Map({
	interactions: ol.interaction.defaults().extend([new app.Drag()]),
	controls: [],
	layers: [rasterLayer, vectorLayer],
	target: container,
	view: new ol.View({
		center: ol.proj.fromLonLat([127.390136, 36.4295159]),
		zoom: 10
	})
});

function MapRelayout () {
	map.updateSize();
}

function MapGetLatLng (mouseEvent) {
	var coord = mouseEvent.coordinate;
	var latlng = ol.proj.transform(coord, 'EPSG:3857', 'EPSG:4326');
	return {lat:latlng[1], lng:latlng[0]};
}

// map: marker click
var olActionMapClick = function () { }
if (typeof ActionMapClick == 'function') olActionMapClick = ActionMapClick();
map.on('click', function(evt) {
	var feature = map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) { return feature; });
	if (feature) {
		try {
			feature.olMarkerClickListener();
			return;
		} catch (err) { }
	}
	olActionMapClick(evt);
});



// Markers /////////////////////////////////////////////////////////////////////

function MakeIcon (url) {
	return new ol.style.Icon({
		anchor: [0.5,0.5],	// center
		size:[20,20],		// image size
		offset:[0,0],		// cut image
		opacity: 1,
		src: url,
	});
}
markerIcon[0] = MakeIcon('img/ledgreen.png');
markerIcon[1] = MakeIcon('img/ledgray.png');
markerIcon[2] = MakeIcon('img/ledorange.png');
markerIcon[3] = MakeIcon('img/ledred.png');

// makers: site position
function MapMarkerAdd (lat, lng, img, name) {
	var mk = new ol.Feature({
		geometry: new ol.geom.Point(ol.proj.fromLonLat([parseFloat(lng), parseFloat(lat)]))
	});
	mk.setStyle(new ol.style.Style({ image: markerIcon[0<=img?img:1] }));
	mk.markerColor = img;
	mk.markerZindex = 0;
	mk.isOn = true;
	mk.getMap = function () { return this.isOn; };
	var no = markers.length;
	if (typeof makeMarkerClickListener == 'function') mk.olMarkerClickListener = makeMarkerClickListener(no);
	if (typeof makeMarkerOverListener  == 'function') mk.olMarkerOverListener  = makeMarkerOverListener (no);
	if (typeof makeMarkerOutListener   == 'function') mk.olMarkerOutListener   = makeMarkerOutListener  (no);
	if (typeof makeMarkerDragStart     == 'function') mk.olMarkerDragStart     = makeMarkerDragStart    (no);
	if (typeof makeMarkerDragEnd       == 'function') mk.olMarkerDragEnd       = makeMarkerDragEnd      (no);
	
	vectorSource.addFeature(mk);
	markers.push(mk);
}

function MapMarkerRemove (n) {
	if (n < markers.length) {
		vectorSource.removeFeature(markers[n]);
		markers[n].isOn = null;
	}
}

function MapMarkerResize (n) {
	if (n < markers.length) {
		for (var i=n; i<markers.length; i++) vectorSource.removeFeature(markers[i]);
		markers.length = n;
	}
}

function MapMarkerDraw () {
	vectorLayer.changed();
}

function MapMarkerColor (idx, cn) {
	if (markers[idx].markerColor == cn) return;
	markers[idx].markerColor = cn;
	markers[idx].getStyle().setImage(markerIcon[cn]);
	if (markers[idx].markerZindex == 4) return;
	if (markers[idx].markerZindex != cn) {
		markers[idx].markerZindex = cn;
		markers[idx].getStyle().setZIndex (cn);
	}
}

function MapMarkerShowAll (si) {
	var bound = [];
	var n = 0;
	for (var i=si; i<markers.length; i++) {
		var coord = markers[i].getGeometry().getCoordinates();
		var x = coord[0];
		var y = coord[1];
		if (n == 0) {
			bound[0] = bound[2] = x;
			bound[1] = bound[3] = y;
		} else {
			if (x < bound[0]) bound[0] = x;
			if (y < bound[1]) bound[1] = y;
			if (bound[2] < x) bound[2] = x;
			if (bound[3] < y) bound[3] = y;
		}
		n++;
	}
	if (n == 0) return;
	
	var dv = Math.max(bound[2] - bound[0], bound[3] - bound[1]);
	dv *= 0.05;
	if (dv < 1000) dv = 1000;
	if ($isMulti == 1) {
		// Multi DB
		bound[0] += dv*0.5;		// w
		bound[1] += dv*7.5;		// s
		bound[2] += dv*0.3;		// e
		bound[3] += dv*0.2;		// n
	} else {
		// Single DB
		bound[0] -= dv;		// w
		bound[1] -= dv;		// s
		bound[2] += dv;		// e
		bound[3] += dv;		// n
	}
	
	map.getView().fit(bound);
}

function MapMarkerMove (idx, lat, lng) {
	markers[idx].setGeometry (new ol.geom.Point(ol.proj.fromLonLat([parseFloat(lng), parseFloat(lat)])));
}

function MapMarkerCenter (idx) {
	// Intelligent zoom in. Prevent mark overlap.
	var zo = 15;							// default zoom
	var minl = 25 * Math.pow(2, 18);	// maximum distance at zoom=0
	minl *= minl;
	var idxcoord = markers[idx].getGeometry().getCoordinates();
	var x, y;
	for (var i=0; i<markers.length; i++) {
		if (idx == i) continue;
		var coord = markers[i].getGeometry().getCoordinates();
		x = idxcoord[0] - coord[0];
		y = idxcoord[1] - coord[1];
		var l = x*x + y*y;
		minl = Math.min (minl, l);
	}
	while (zo < 18) {
		var zl = 25 * Math.pow(2, 18-zo);
		if (zl*zl < minl) break;
		zo++;
	}
	
	prevInfowindowIdx = idx;
	map.getView().setCenter (markers[idx].getGeometry().getCoordinates());
//	if (map.getView().getZoom() < zo) map.getView().setZoom(zo);
	map.getView().setZoom(zo);
}

function MapMarkerPosition (idx) {
	var geometry = markers[idx].getGeometry();
	var coord = geometry.getCoordinates();
	var latlng = ol.proj.transform(coord, 'EPSG:3857', 'EPSG:4326');
	return {lat:latlng[1], lng:latlng[0]};
}



// Popups //////////////////////////////////////////////////////////////////////

infowindow  = document.getElementById('pud_');
infoelement = new ol.Overlay({
	element: infowindow,
	positioning: 'bottom-center',
	autoPan: true,
	autoPanAnimation: { duration: 100 },
	autoPanMargin: 30,
	stopEvent: true
});
map.addOverlay(infoelement);

// infowindow: detail site info
var prevInfowindowIdx = -1;
function MapInfowindowShow (idx, msg) {
	infoelement.autoPan = (prevInfowindowIdx != idx);
	var geometry = markers[idx].getGeometry();
	var coord = geometry.getCoordinates();
	infowindow.innerHTML = msg;
	$(infowindow).fadeIn();
	infoelement.setPosition(coord);
	
	prevInfowindowIdx = idx;
	for (var i=0; i<markers.length; i++) {
		var cn = markers[i].markerColor;
		if (idx == i) cn = 4;
		if (markers[i].markerZindex != cn) {
			markers[i].markerZindex = cn;
			markers[i].getStyle().setZIndex (cn);
		}
	}
	MapMarkerDraw ();
}

function MapInfowindowClose () {
	$(infowindow).hide();
	prevInfowindowIdx = -1;
	for (var i=0; i<markers.length; i++) {
		var cn = markers[i].markerColor;
		if (markers[i].markerZindex != cn) {
			markers[i].markerZindex = cn;
			markers[i].getStyle().setZIndex (cn);
		}
	}
	MapMarkerDraw ();
}

// popups: bar graph window of sites
function MapPopupAdd (idx) {
	infowindows[idx] = document.getElementById('pud_'+idx);
	if (infowindows[idx]) {
		popups[idx] = new ol.Overlay({
			element: infowindows[idx],
			positioning: 'bottom-center',
			stopEvent: true
		});
		map.addOverlay(popups[idx]);
	}
}

function MapPopupClose (idx) {
	if (infowindows[idx]) {
		$(infowindows[idx]).hide();
	}
}

function MapPopupShow (idx, msg) {
	if (infowindows[idx]) {
		var geometry = markers[idx].getGeometry();
		var coord = geometry.getCoordinates();
		infowindows[idx].innerHTML = msg;
		$(infowindows[idx]).show();
		popups[idx].setPosition(coord);
	}
}



// Path ////////////////////////////////////////////////////////////////////////

// pathLines: Connect the measured position to the day.
function MapPathAdd (lat, lng) {
	pathLines.push([parseFloat(lng), parseFloat(lat)]);
}

function MapPathClear () {
	if (pathLine != null) {
		vectorSource.removeFeature(pathLine);
		pathLine = null;
	}
	pathLines = [];
}

function MapPathDraw () {
	var lineString = new ol.geom.LineString(pathLines);
	lineString.transform('EPSG:4326', 'EPSG:3857');
	pathLine = new ol.Feature({
		geometry: lineString,
		name: 'Line',
		style: ss
	});
	var ss = new ol.style.Style({
		stroke: new ol.style.Stroke({
			color: '#FFAE00',
			opacity: 0.7,
			width: 4,
			style : 'solid'
		})
	});
	pathLine.setStyle(ss);
	
	vectorSource.addFeature(pathLine);
}

function MapChanger (mapType) {
	
	rasterLayer.setOpacity(1);
	
	
	if(mapType == 'normal') {
		
		rasterLayer = new ol.layer.Tile({
				title : '배경지도',
				visible : true,
				source : new ol.source.XYZ({
					url : 'http://api.vworld.kr/req/wmts/1.0.0/937DE0F6-A5B9-37A2-8149-F41E32121639/Base/{z}/{y}/{x}.png'
				})
        });
	}
	if(mapType == 'satellite') {
		
		rasterLayer = new ol.layer.Tile({
				title: '영상지도',
				visible: true,
				source: new ol.source.XYZ({
					url: 'http://api.vworld.kr/req/wmts/1.0.0/937DE0F6-A5B9-37A2-8149-F41E32121639/Satellite/{z}/{y}/{x}.jpeg'
				})
		});
	}
	
	map.getLayers().setAt(0, rasterLayer);
}

</script>

EOF;
?>
