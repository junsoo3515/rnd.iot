function RGBColor(color_string) {
	this.ok = false;
	
	// strip any leading #
	if (color_string.charAt(0) == '#') {
		color_string = color_string.substr(1,6);
	}
	
	color_string = color_string.replace(/ /g,'');
	color_string = color_string.toLowerCase();
	
	// array of color definition objects
	var color_defs = [
		{
			re: /^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/,	// 'rgb(123, 234, 45)'
			process: function (bits) {
				return [ parseInt(bits[1]), parseInt(bits[2]), parseInt(bits[3]) ];
			}
		}, {
			re: /^(\w{2})(\w{2})(\w{2})$/,	// '#00ff00'
			process: function (bits) {
				return [ parseInt(bits[1], 16), parseInt(bits[2], 16), parseInt(bits[3], 16) ];
			}
		}, {
			re: /^(\w{1})(\w{1})(\w{1})$/,	// '#fb0'
			process: function (bits) {
				return [ parseInt(bits[1] + bits[1], 16), parseInt(bits[2] + bits[2], 16), parseInt(bits[3] + bits[3], 16) ];
			}
		}
	];
	
	// search through the definitions to find a match
	for (var i = 0; i < color_defs.length; i++) {
		var re = color_defs[i].re;
		var processor = color_defs[i].process;
		var bits = re.exec(color_string);
		if (bits) {
			channels = processor(bits);
			this.r = channels[0];
			this.g = channels[1];
			this.b = channels[2];
			this.ok = true;
		}
	}
	
	// validate/cleanup values
	this.r = (this.r < 0 || isNaN(this.r)) ? 0 : ((this.r > 255) ? 255 : this.r);
	this.g = (this.g < 0 || isNaN(this.g)) ? 0 : ((this.g > 255) ? 255 : this.g);
	this.b = (this.b < 0 || isNaN(this.b)) ? 0 : ((this.b > 255) ? 255 : this.b);
	
	// some getters
	this.toRGB = function () {
		return 'rgb(' + this.r + ', ' + this.g + ', ' + this.b + ')';
	}
	this.toHex = function () {
		var r = this.r.toString(16);
		var g = this.g.toString(16);
		var b = this.b.toString(16);
		if (r.length == 1) r = '0' + r;
		if (g.length == 1) g = '0' + g;
		if (b.length == 1) b = '0' + b;
		return '#' + r + g + b;
	}
}

(function ($) {
	var options = { series: { points: { winddir: null } } };	//should be 'wd'
	
	function drawWindDir(ctx, s){
		if (s.points.winddir != 'wd') return;
		var points = s.datapoints.points;
		var ps = s.datapoints.pointsize;
		var ax = [s.xaxis, s.yaxis];
		ctx.lineWidth = s.points.lineWidth;
		
		var headcolor;
		var color = new RGBColor(s.color);
		if (color.ok) {
			color.r >>= 1;
			color.g >>= 1;
			color.b >>= 1;
			headcolor = color.toHex();
		} else {
			headcolor = '#000000';
		}
		
		for (var i = 0; i < s.datapoints.points.length; i += ps) {
			var x = points[i];
			var y = Math.round(points[i + 1] * 1000);
			var ws = y % 1000;					// get wind-speed from wind-direction
			y /= 1000;
			if (x > ax[0].max || x < ax[0].min || y < ax[1].min || y > ax[1].max) continue;
			
			var r = (0<ws)?(ws*0.1+3):10;
			if (20 < r) r = 20;
			var th = y*Math.PI * 0.005555556;	// convert deg to rad
			x = ax[0].p2c(x);					// convert to pixels
			y = ax[1].p2c(y);
			
			var xe = Math.sin(th)*r;
			var ye = Math.cos(th)*r;
			r *= 0.6;
			
			ctx.strokeStyle = s.color;
			ctx.beginPath();
			ctx.moveTo(x-xe, y-ye);
			ctx.lineTo(x+xe, y+ye);
			ctx.stroke();
			
			ctx.strokeStyle = headcolor;
			ctx.fillStyle = headcolor;
			ctx.beginPath();
			ctx.moveTo(x + Math.sin(th-0.3)*r, y + Math.cos(th-0.3)*r);
			ctx.lineTo(x+xe, y+ye);
			ctx.lineTo(x + Math.sin(th+0.3)*r, y + Math.cos(th+0.3)*r);
			ctx.closePath();
			ctx.stroke();
			ctx.fill();
		}
	}

	function draw(plot, ctx){
		var plotOffset = plot.getPlotOffset();
		ctx.save();
		ctx.translate(plotOffset.left, plotOffset.top);
		$.each(plot.getData(), function (i, s) {
			drawWindDir(ctx, s);
		});
		ctx.restore();
	}
	
	function drawWindDirHighlight(ctx, s, points){
		if (s.points.winddir != 'wd') return;
		var ax = [s.xaxis, s.yaxis];
		ctx.lineWidth = 7 + s.points.lineWidth;
		ctx.strokeStyle = (typeof s.highlightColor === "string") ? s.highlightColor : $.color.parse(s.color).scale('a', 0.5).toString();
		ctx.lineCap="round";
		
		var x = points[0];
		var y = Math.round(points[1] * 1000);
		var ws = y % 1000;					// get wind-speed from wind-direction
		y /= 1000;
		if (x > ax[0].max || x < ax[0].min || y < ax[1].min || y > ax[1].max) return;
		
		var r = (0<ws)?(ws*0.1+3):10;
		if (20 < r) r = 20;
		var th = y*Math.PI * 0.005555556;	// convert deg to rad
		x = ax[0].p2c(x);					// convert to pixels
		y = ax[1].p2c(y);
		
		var xe = Math.sin(th)*r;
		var ye = Math.cos(th)*r;
		r *= 0.6;
		
		ctx.beginPath();
		ctx.moveTo(x-xe, y-ye);
		ctx.lineTo(x+xe, y+ye);
		ctx.moveTo(x + Math.sin(th-0.3)*r, y + Math.cos(th-0.3)*r);
		ctx.lineTo(x+xe, y+ye);
		ctx.moveTo(x + Math.sin(th+0.3)*r, y + Math.cos(th+0.3)*r);
		ctx.lineTo(x+xe, y+ye);
		ctx.stroke();
	}
	
	function drawOverlay(plot, octx, highlights) {
		var plotOffset = plot.getPlotOffset();
		octx.save();
		octx.translate(plotOffset.left, plotOffset.top);
		var i, hi;
		for (i = 0; i < highlights.length; ++i) {
			hi = highlights[i];
			drawWindDirHighlight(octx, hi.series, hi.point);
		}
		octx.restore();
	}
	
	function init(plot) {
		plot.hooks.drawOverlay.push(drawOverlay);
		plot.hooks.draw.push(draw);
	}

	$.plot.plugins.push({
		init: init,
		options: options,
		name: 'winddir',
		version: '1.0'
	});
})(jQuery);
