(function($) {
	var supportedDisplayMethods = ['stacked', 'slide'];
	var _currentImage;
	var _currentChapter;
	var _currentImageId;
	var _options;
	var $ul;
	var playTimer;
	var isPlaying;
	var $buttonPrevious;
	var $buttonNext;
	var $nav = {};
	var lastTransform = 0;
	var _lastMultiplier = 0;

	var methods = {
		init: function(options) {
			var opts = $.extend({}, $.fn.fscarousel.defaults, options);
			_options = opts;

			// Ensure integere are integers and booleans are booleans
			for (var key in _options) {
				var option = _options[key];
				if (option.hasOwnProperty('duration')) {
					_options[key].duration = parseInt(option.duration);
				} else if (option === '1') {
					option = true;
				} else if (option == '0') {
					option = false;
				}
			}
			if (!Modernizr.csstransforms3d) {
				_options.animation_method = 'js';
			}
			// Ensure a proper display method is set
			if ($.inArray(_options.display_method, supportedDisplayMethods) == -1) {
				_options.display_method = 'slide';
			}
			// Ensure we have an interval
			if (!_options.hasOwnProperty('interval') || isNaN(parseInt(_options.interval))) {
				_options.interval = 5000;
			}
			//console.log('fscarousel init with options: ', _options);

			return this.each(function() {
				var that = this;
				var elem = $(this);
				var images = $(opts.selectors.chapterImagesWrapper, elem);
				var thumbs = $(opts.selectors.chapterThumbsWrapper, elem);

				var $exitButton = $('.fscarousel-button.fscarousel-button-exit');
				var $infoButton = $('.fscarousel-button.fscarousel-button-info')
				var $infobox = $('.fscarousel-infobox');
				if ($infobox.length) {
					$infobox.hide();
				} else {
					$infobox = $('<div id="fscarousel_infobox" class="fscarousel-infobox" />');
					$(_options.selectors.header).after($infobox);
				}

				$ul = $(opts.selectors.chapterMenu, elem);
				$ul.addClass('.fscarousel-chapter-menu').hide();

				_currentImage = null;

				// Position the menu
				var $select_wrapper = $('.fscarousel-chapter-menu-wrapper', elem);

				$ul.css({
					position: 'absolute',
					width: $select_wrapper.outerWidth()
				})
					.position({
						my: 'center top',
						at: 'center bottom',
						of: $select_wrapper
					})

				/**
				 * EVENT HANDLERS
				 **/

				// HOVER
				var hoverOut;
				$ul.hover(function() {
					clearTimeout(hoverOut);
				}, function() {
					$ul.fadeOut(_options.navigationAnimationOptions);
				})

				$select_wrapper
					.hover(
						function(event) {
							clearTimeout(hoverOut);
							var $select_wrapper = $('.fscarousel-chapter-menu-wrapper', elem);
							$ul
								.fadeIn(_options.navigationAnimationOptions)
								.position({
									my: 'center top',
									at: 'center bottom',
									of: $select_wrapper
								})
						},
						function(event) {
							hoverOut = setTimeout(function() {
								$ul.fadeOut(_options.navigationAnimationOptions);
							}, 500);
						}
				);

				// CLICK: Chapter Dropdown Menu
				$ul.children('li').each(function() {
					var chapter_id = $(this).attr('data-chapter-id');
					$(this).children('a').click(function(event) {
						event.preventDefault();
						if (elem.fscarousel('currentChapter') != chapter_id) {
							elem.fscarousel('selectChapter', chapter_id);
						}
					})
				})

				// CLICK: Image Thumbnails
				thumbs.find('a.fscarousel-chapter-thumb-link').once('fsclick').click(function(event) {
					event.preventDefault();
					var image_id = $(this).children('.fscarousel-chapter-thumb-img').attr('data-image-id');
					var chapter_id = $(this).parents('.fscarousel-chapter-thumbs').attr('data-chapter-id');
					if (elem.fscarousel('currentChapter') != chapter_id || elem.fscarousel('currentImage').attr('data-image-id') != image_id) {
						elem.fscarousel('selectChapterImageById', chapter_id, image_id);
					}
				})

				// CLICK: Chapter Thumb Covers
				thumbs.find('.fscarousel-chapter-thumb-cover').once('fsclick').click(function(event) {
					event.preventDefault();
					var chapter_id = $(this).parents('.fscarousel-chapter-thumbs').attr('data-chapter-id');
					if (elem.fscarousel('currentChapter') != chapter_id) {
						elem.fscarousel('selectChapter', chapter_id);
					}
				});

				// CLICK: Info button
				$infoButton.click(function(event) {
					event.preventDefault();
					elem.fscarousel('toggleImageInfo');
				})

				// CLICK: Exit
				$exitButton.click(function(event) {
					event.preventDefault();
					switch (_options.exit_destination) {
						case 'back':
							window.history.back();
							break;
						case 'custom':
							window.location.href = _options.exit_destination_custom;
							break;
					}
				})

				// Show infobox by default
				if (_options.infobox_show) {
					$infoButton.click();
				}

				// CLICK: Nav Prev/Next
				// These are scrollers, not silde changes
				$buttonPrevious = $('.fscarousel-button-nav-prev'),
				$buttonNext = $('.fscarousel-button-nav-next'),
				$nav = $('.fscarousel-nav-select');

				$buttonNext.mousedown(function() {
					if (!$nav.is(':animated')) {
						// Move nav over the length of 5 thumbs (if possible)
						var $thumbs = $nav.find('.fscarousel-chapter-thumb');
						var $lastThumb = $($thumbs[$thumbs.length - 1]);
						var rect = $lastThumb[0].getBoundingClientRect();
						var offsetLeft = $lastThumb.outerWidth() * ($thumbs.length - 1);
						if (offsetLeft >= $(window).width()) {
							var offsetX = 5 * ($lastThumb.outerWidth() + (2 * parseInt($lastThumb.css('marginLeft'))));
							$nav.animate({
								left: '-=' + offsetX
							}, _options.navigationAnimationOptions)
						}
					}
				});
				$buttonPrevious.mousedown(function() {
					if (!$nav.is(':animated')) {
						// Move nav over the length of 5 thumbs (if possible)
						if ($nav.offset().left < 0) {
							var $thumbs = $nav.find('.fscarousel-chapter-thumb');
							var $firstThumb = $($thumbs[0]);
							var offsetX = 5 * ($firstThumb.outerWidth() + (2 * parseInt($firstThumb.css('marginLeft'))));
							$nav.animate({
								left: '+=' + offsetX
							}, _options.navigationAnimationOptions)
						}
					}
				});

				// WINDOW RESIZE
				var refresh;
				$(window).resize(function() {
					if (!Modernizr.flexbox) {
						$('.fscarousel-nav-canvas').width($(window).width() - ($('.fscarousel-button-nav-prev').width() * 2.09));
						var w = 0;
						$('.fscarousel-chapter-thumbs').each(function() {
							$(this).children().each(function() {
								w += $(this).width();
							});
						})
						$('.fscarousel-nav-select').width(w);
					}
					// Only resize 250ms after the user is done resizing the window.
					clearTimeout(refresh);
					refresh = setTimeout(function() {
						elem.fscarousel('showImage');
					}, 250);
				})

				// Select a chapter
				if (_options.hasOwnProperty('default_chapter_id') && _options.default_chapter_id != null) {
					elem.fscarousel('selectChapter', _options.default_chapter_id);
				} else {
					var chapter_id = $(images[0]).attr('data-chapter-id');
					elem.fscarousel('selectChapter', chapter_id);
				}

				// Start slider
				if (_options.autoplay) {
					elem.fscarousel('play');
				}
			});
		},
		selectChapter: function(chapter_id, image_index) {
			_currentChapter = chapter_id;
			var element = this[0];
			// Choose menu option
			var $li = $ul.children('#chapter_select_option_' + chapter_id);
			$ul.children().removeClass('active');
			if ($li.length) {
				$('#fscarousel_chapter_title').text($li.text());
				$li.addClass('active');
			}

			// Slide nav to Chapter cover
			var $thumbs = $('.fscarousel-chapter-thumbs[data-chapter-id="' + chapter_id + '"]');
			var $thumb = $thumbs.find('.fscarousel-chapter-thumb-cover');
			var idx = $thumb.index('.fscarousel-chapter-thumb');
			var $nav = $thumbs.parents('.fscarousel-nav-select');

			// Calculate offset manually instead of relying on $.offset because it is getting it wrong for IE.
			// var offsetX = $thumb.offset().left - $nav.offset().left - parseInt($thumb.css('marginLeft'));
			var offsetX = idx * ($thumb.outerWidth() + parseInt($thumb.css('marginLeft')) + parseInt($thumb.css('marginRight')));
			switch (_options.animation_method) {
				case 'css':
					// move the nav after a set timer to make animations smoother
					setTimeout(function() {
						$nav.css({
							left: -offsetX
						});
					}, 0)

					break;
				case 'js':
					setTimeout(function() {
						$nav.animate({
							left: -offsetX
						}, _options.navigationAnimationOptions)
					}, 0)
					break;
			}

			// Show image
			if (image_index != false) {
				if (image_index == undefined) {
					image_index = 0;
				}
				this.fscarousel('selectImageAtIndex', chapter_id, image_index);
			}

		},
		selectImageAtIndex: function(chapter_id, index) {
			var that = this;
			this.find('#fscarousel_canvas').children().each(function() {
				var this_chapter_id = $(this).attr('data-chapter-id');
				if (this_chapter_id == chapter_id) {
					var $img = $(this).find('.fscarousel-image').eq(index);
					that.fscarousel('showImage', $img);
				}
			})
		},
		selectChapterImageAtDelta: function(chapter_id, image_delta) {
			if (_currentChapter != chapter_id) {
				this.fscarousel('selectChapter', chapter_id, false);
			}
			this.fscarousel('selectImageAtDelta', chapter_id, image_delta);
		},
		selectChapterImageById: function(chapter_id, image_id) {
			if (_currentChapter != chapter_id) {
				this.fscarousel('selectChapter', chapter_id, false);
			}
			this.fscarousel('selectImageById', chapter_id, image_id);
		},
		selectImageById: function(chapter_id, image_id) {
			var that = this;
			this.find('.fscarousel-chapter-images').each(function() {
				var this_chapter_id = $(this).attr('data-chapter-id');
				if (this_chapter_id == chapter_id) {
					var $img = $(this).find('.fscarousel-image[data-image-id="' + image_id + '"]');
					that.fscarousel('showImage', $img);
				}
			})
		},
		selectImageAtDelta: function(chapter_id, delta) {
			var that = this;
			this.find('.fscarousel-chapter-images').each(function() {
				var this_chapter_id = $(this).attr('data-chapter-id');
				if (this_chapter_id == chapter_id) {
					var $img = $(this).find('.fscarousel-image[data-delta="' + delta + '"]');
					that.fscarousel('showImage', $img);
				}
			})
		},
		showImage: function(element) {
			if (element == undefined) {
				element = _currentImage;
			}
			_currentImage = element;
			_currentImageId = element.attr('data-image-id');
			var display_method = _options.display_method;
			if (display_method == 'stacked') {
				var chapter_top = element.parents('.fscarousel-chapter-images').position().top,
					image_top = element.offset().top;
				switch (_options.animation_method) {
					case 'css':
						var y = lastTransform - image_top
						var multiplier = Math.ceil(Math.abs(Math.round((y - lastTransform) / $(window).height())) / 10);
						var _lastTransform = lastTransform;
						lastTransform = y;
						$('body').removeClass('cssmultiplier' + _lastMultiplier);
						$('body').addClass('cssmultiplier' + multiplier);
						_lastMultiplier = multiplier;

						element.parents('.fscarousel-chapter-images').siblings().css({
							transform: 'translate3d(0px,' + y + 'px,0px)',
							//marginTop: y
						});
						element.parents('.fscarousel-chapter-images').show().css({
							transform: 'translate3d(0px,' + y + 'px,0px)',
							//marginTop: y
						});
						break;
					case 'js':
						var y = -1 * element.position().top;
						lastTransform = y;
						element.parents('.fscarousel-chapter-images').siblings().hide();
						element.parents('.fscarousel-chapter-images').show().animate({
							top: y
						}, _options.slideAnimationOptions);
						break;
				}
			} else if (display_method == 'slide') {
				// default
				$("#fscarousel_canvas").children().children().not(element).hide();
				element.show(_options.slideAnimationOptions);
			}
			var delta = element.attr('data-delta');
			var image_id = element.attr('data-image-id');
			var chapter_id = element.parents('.fscarousel-chapter-images').attr('data-chapter-id');
			$('.fscarousel-chapter-thumb').removeClass('active');

			var $thumb = $('.fscarousel-chapter-thumbs[data-chapter-id="' + chapter_id + '"] .fscarousel-chapter-thumb-img[data-image-id="' + image_id + '"]')
				.parents('.fscarousel-chapter-thumb');

			$thumb.addClass('active');
			this.fscarousel('placeImageInfo');

			//Facilitate moving around the nav by nudging it as it approaches window edges
			// setTimeout(function() {
			// 	if (delta != 0 && $thumb.offset().left > ($(window).width() * 5/6)) {
			// 		$buttonNext.click();
			// 	}
			// 	else if (delta != 0 && $thumb.offset().left < ($(window).width() * 1/6)) {
			// 		$buttonPrevious.click();
			// 	}
			// },1000);

			if (isPlaying) {
				this.fscarousel('play');
			}
		},
		placeImageInfo: function() {
			var $infobox = $('.fscarousel-infobox');
			var element = _currentImage;
			var caption = element.attr('data-caption');
			var title = $.trim(element.attr('data-title'));
			if (title == undefined || title == '') {
				// get title from chapter
				var chapter_id = _currentImage.parents('.fscarousel-chapter-images').attr('data-chapter-id');
				title = $('.fscarousel-chapter-menu-option[data-chapter-id="' + chapter_id + '"]').text();
			}
			var infoboxHtml = '<h2 class="image-title" style="display:none">' + title + '</h2><p class="caption" style="display:none">' + caption + '</p>';
			$infobox.children().fadeOut(500);
			$infobox.html(infoboxHtml).children().fadeIn(500);
		},
		toggleImageInfo: function() {
			var $infobox = $('.fscarousel-infobox');
			$infobox.toggle(_options.infoBoxAnimationOptions);
		},
		currentChapter: function() {
			return _currentChapter;
		},
		currentImage: function() {
			return _currentImage;
		},
		play: function() {
			var that = this;
			isPlaying = true;
			clearInterval(playTimer);
			playTimer = setInterval(function() {
				that.fscarousel('gotoNextSlide');
			}, _options.interval);
		},
		stop: function() {
			clearInterval(playTimer);
			isPlaying = false;
		},
		gotoNextSlide: function() {
			var $currentChapter = $('.fscarousel-chapter-thumbs[data-chapter-id=' + _currentChapter + ']');
			var $currentThumb = $currentChapter.find('.fscarousel-chapter-thumb-img[data-image-id=' + _currentImageId + ']');
			if ($currentThumb.parents('.fscarousel-chapter-thumb').next().length) {
				$currentThumb.parents('.fscarousel-chapter-thumb').next().find('a').click();
			} else {
				if ($currentChapter.next().length) {
					$currentChapter.next().find('.fscarousel-chapter-thumb-cover').click();
				} else {
					$('.fscarousel-chapter-thumbs').eq(0).find('.fscarousel-chapter-thumb-cover').click();
				}
			}
		}
	};

	$.fn.fscarousel = function(methodOrOptions) {
		if (methods[methodOrOptions]) {
			return methods[methodOrOptions].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
			// Default to "init"	
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + methodOrOptions + ' does not exist on jQuery.fscarousel');
		}
	};

	$.fn.fscarousel.defaults = {
		display_method: 'stacked',
		selectors: {
			chapterImagesWrapper: '.fscarousel-chapter-images',
			chapterThumbsWrapper: '.fscarousel-chapter-thumbs',
			chapterMenu: '.fscarousel-chapter-menu',
			header: '.fscarousel-header'
		},
		slideAnimationOptions: {
			duration: 1000,
			effect: 'fade',
		},
		infoBoxAnimationOptions: {
			effect: 'fade',
			direction: 'right',
			duration: 500
		},
		navigationAnimationOptions: {
			duration: 500,
			easing: 'swing'
		},
		exit_destination: 'back'
	}
})(jQuery);