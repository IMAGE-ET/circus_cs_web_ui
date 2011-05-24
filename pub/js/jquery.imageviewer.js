/**
 * jQuery UI custom widget: Stackable image viewer.
 *
 * Author:
 *   Soichiro Miki
 * Depends:
 *   jquery-ui-1.7.x.js with Slider widget (or newer)
 *   jquery.mousewheel.(min).js (optional)
 *   layout.css
 */

$.widget('ui.imageviewer', {
	_init: function()
	{
		// preparing cache
		var body = $('body');
		var self = this;
		this._cache = body.data('imageviewerCache') || {};
		body.data('imageviewerCache', this._cache);
		body.bind('imageviewerImageload', function (event, data) {
			self._imageLoadHandler(data);
		});
		this._draw();
		this._initialized = true;
	},

	_draw: function ()
	{
		var root = this.element.empty();
		var self = this;
		root.addClass('ui-imageviewer')
		var imgdiv = $('<div class="ui-imageviewer-image">')
			.css({ width: this.options.width, height: this.options.height })
			.appendTo(root);
		this._scale = this.options.width / this.options.imageWidth;
		var height = this.options.imageHeight * this._scale;
		var img = $('<img>')
			.css({ width: this.options.width, height: height })
			.appendTo(imgdiv);
		img.mousedown(function(){return false;}); // prevent selection/drag
		if (this.options.useWheel && img.mousewheel)
		{
			imgdiv.mousewheel(function (event, delta) {
				self.step(delta > 0 ? -1 : 1);
				return false; // supress browser scroll
			})
		}

		if (this.options.useSlider)
		{
			var table = $(this._sliderHtml()).appendTo(root);
			table.find('td').eq(0).find('button').click(function () { self.step(-1); });
			table.find('td').eq(2).find('button').click(function () { self.step(1); });
			var sliderdiv = $('<div class="ui-imageviewer-slider">')
				.slider({
					min: this.options.min,
					max: this.options.max,
					step: 1,
					value: this.options.index,
					slide: function(event, ui) {
						self._label(ui.value);
						if (self.options.sliderHotTrack)
							self.changeImage(ui.value);
					},
					change: function(event, ui) {
						self.changeImage(ui.value);
					}
				});
			table.find('td').eq(1).append(sliderdiv);
		}
		if (this.options.useLocationText)
		{
			$('<div class="ui-imageviewer-location" />').appendTo(root);
		}
		this.changeImage(this.options.index);
		if (this.options.role == 'locator')
		{
			img.click(function(e) {
				if (!e.offsetX){ e.offsetX = e.pageX - $(e.target).offset().left; }
				if (!e.offsetY){ e.offsetY = e.pageY - $(e.target).offset().top; }
				self._locate(e.offsetX, e.offsetY);
				return false; // prevent image selection on dblclick
			})
			.css('cursor', 'crosshair');
		}
	},

	_locate: function(x, y)
	{
		var newitem = {
			location_x: parseInt(x / this._scale + 0.5),
			location_y: parseInt(y / this._scale + 0.5),
			location_z: this.options.index,
		};
		// The handler for 'locating' event can modify the new item.
		var event = $.Event('locating');
		event.newItem = newitem;
		this.element.trigger(event);
		if (!event.isDefaultPrevented()) {
			this.options.markers.push(newitem);
			var event = $.Event('locate');
			event.newItem = newitem;
			this.element.trigger(event);
		}
		this._drawMarkers();
	},

	_drawMarkers: function()
	{
		if (!(this.options.markers instanceof Array))
			return;
		var imgdiv = $('div.ui-imageviewer-image', this.element);
		var index = this.options.index;
		imgdiv.find('div.ui-imageviewer-dot, div.ui-imageviewer-dotlabel').remove();
		if (!this.options.showMarkers)
			return;
		var max = this.options.markers.length;
		for (var i = 0; i < max; i++)
		{
			var mark = this.options.markers[i];
			var x = mark.location_x * this._scale;
			var y = mark.location_y * this._scale;
			if (mark.location_z == index)
			{
				$('<div class="ui-imageviewer-dot" />')
					.css({left: x - 1, top:  y - 1})
					.appendTo(imgdiv);
				$('<div class="ui-imageviewer-dotlabel" />')
					.text(mark.display_id || i+1)
					.css({left: x + 3, top: y - 1})
					.appendTo(imgdiv);
			}
		}
	},

	_sliderHtml: function()
	{
		return '<table class="ui-imageviewer-navi"><tr><td class="updown"><button>-</button></td><td /><td class="updown"><button>+</button></td></tr></table>';
	},

	step: function(delta)
	{
		this.changeImage(this.options.index + delta);
	},

	_label: function(index) {
		$('.ui-imageviewer-location', this.element).text(this.options.locationLabel + index);
	},

	_drawImage: function(index, imgFileName)
	{
		$('img', this.element).attr('src', this.options.toTopDir + imgFileName);
		this._label(index);
		this._drawMarkers();
	},

	_imageLoadHandler: function (data)
	{
		if (data.errorMessage)
		{
			console && console.log(data.errorMessage);
		}
		else if (data.imgFname && data.sliceNumber)
		{
			this._cache[data.sliceNumber] = data.imgFname;
			if (this._waiting && this._waiting.index == data.sliceNumber)
			{
				this._drawImage(data.sliceNumber, data.imgFname);
				this._waiting = null;
			}
			else
			{
				var dummy = new Image(); // browser image preload
				dummy.src = this.options.toTopDir + data.imgFname;
			}
		}
	},

	_query: function(index)
	{
		var self = this;
		var param = {
				studyInstanceUID: this.options.study_instance_uid,
				seriesInstanceUID: this.options.series_instance_uid,
				imgNum: index,
		};
		// prevent requesting image more than once
		if (this._cache[index] instanceof Date)
			return;
		$.post(
			this.options.toTopDir + 'jump_image.php',
			param,
			function (data) {
				$('body').trigger('imageviewerImageload', data); // broadcast
			},
			'json'
		);
		this._cache[index] = new Date();

	},

	preload: function()
	{
		for (var i = this.options.min; i <= this.options.max; i++)
		{
			this._query(i);
		}
	},

	changeImage: function(index)
	{
		var old = this.options.index;
		var self = this;
		index = Math.min(Math.max(index, this.options.min), this.options.max);
		this.options.index = index;
		if (old == index && this._initialized)
			return;
		$('.ui-imageviewer-slider').slider('option', 'value', index);
		var toTopDir = this.options.toTopDir;
		if (typeof(this._cache[index]) == 'string')
		{
			// the image is already created
			this._drawImage(index, this._cache[index]);
		}
		else
		{
			// the image may need creation
			this._waiting = { index: index };
			this._query(index);
		}
		$(this.element).trigger('imagechange');
	},

	_setData: function(key, value, animated)
	{
		if (key == 'index')
		{
			this.changeImage(value);
			return; // supress calling super method
		}
		$.widget.prototype._setData.apply(this, arguments);
		switch (key) {
			case 'markers':
			case 'showMarkers':
				this._drawMarkers();
				break;
			case 'role':
			case 'imageWidth':
			case 'imageHeight':
				this._draw();
				break;
		}
	},
});

$.extend($.ui.imageviewer, {
	defaults: {
		min: 1,
		max: 100,
		windowLevel: 10,
		windowWidth: 100,
		index: 1,
		width: 300,
		imageWidth: 512,
		imageHeight: 512,
		useSlider: true,
		sliderHotTrack: false,
		useLocationText: true,
		locationLabel: 'Image Number: ',
		toTopDir: '',
		role: 'viewer',
		showMarkers: true,
		useWheel: true,
		markers: []
	}
});