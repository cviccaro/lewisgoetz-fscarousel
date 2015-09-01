(function($) {

	$(document).ready(function() {
		// quick loading indicator
		window.$overlay = $('<div id="overlay" />').css({
			backgroundColor: 'rgba(0,0,0,0.85)',
			width: '100%',
			height: $(window).height(),
			position: 'fixed',
			top: 0,
			left: 0,
			'z-index': 9998,
			display: 'none',
			color: '#fff'
		})
		var $spinner_container = $('<div id="spinner-container" style="position: relative; height: 100%; width: 100%;"/>');
		var $spinner = $('<div id="spinner" style="position: relative" />');
		$spinner_container.append($spinner);
		$overlay.append($spinner_container);
		$("body").append($overlay);
		$spinner.spin({
			color: '#fff'
		});
		
		// remove functional cookies
		$.removeCookie('fscarousel_add_new_image', {
			path: '/'
		});
		$.removeCookie('fscarousel_add_new_chapter', {
			path: '/'
		});

		// @credit: https://mathiasbynens.be/notes/localstorage-pattern
		// Feature detect + local reference
		var hasStorage = (function() {
			var mod = 'modernizr';
			try {
				localStorage.setItem(mod, mod);
				localStorage.removeItem(mod);
				return true;
			} catch (exception) {
				return false;
			}
		}());
		if (hasStorage) {
			var focus_id = localStorage.getItem('fscarousel_focus_id');
			if (focus_id != null && $('#' + focus_id).length) {
				//window.location.hash = '#' + focus_id;
				$("html,body").animate({scrollTop: $('#' + focus_id).offset().top}, 500)
			}
		}
		Drupal.behaviors.collapse.attach(document, {});

		var query = window.location.search.substr(1, window.location.search.length - 1).split('&');
		var queryObj = {};
		var page_name = '';
		for (var i = 0, q; q = query[i]; i++) {
			var split = q.split('=');
			queryObj[split[0]] = split[1];
			if (split[0] == 'page') {
				page_name = split[1];
			}
		}
		// ADD NEW CHAPTER
		$('.add-new-chapter').button({
			icons:{
				primary: 'ui-icon-plusthick'
			}
		})
		$(document).on('click', '.add-new-chapter', function(event) {
			$.cookie('fscarousel_add_new_chapter', 1, {
				'path': '/'
			});
			$('#fscarousel-carousel-form input[type="submit"]').click();
		});

		// REMOVE CHAPTER
		$('.remove-chapter').button({
			icons:{
				primary: 'ui-icon-trash'
			}
		})
		$(document).on('click', '.remove-chapter', function(event) {
			var $fieldset = $(this).parents('.chapter-fieldset');
			var chapter_id = $fieldset.find('.chapter-id-hidden').val();
			var chapterIndex = $fieldset.index() + 1;
			var $confirmDialog = $('<div id="confirmDialog"><p>Are you sure you want to remove chapter ' + chapterIndex + '?  This will remove it and all of its images.  <strong>This cannot be undone.</strong></p></div>')
			$confirmDialog
				.dialog({
					autoOpen: true,
					modal: true,
					title: "Please confirm",
					buttons:[
						{
							text: "OK",
							click: function() {
								$.post(ajaxurl, {
										'action': 'fscarousel_remove_chapter',
										'data': {
											'chapter_id': chapter_id
										},
									},
									function(response) {
										if (response.hasOwnProperty('response')) {
											var answer = response.response;
											if (answer == 'unsaved_chapter_deleted') {
												window.location = window.location.origin + window.location.pathname + '?page=' + page_name;
											} else if (answer == 'chapter_deleted') {
												window.location.reload();
											}
										}
									});
								$confirmDialog.dialog('close');
							}
						},
						{
							text: "Cancel",
							click: function() {
								$confirmDialog.dialog('close');
							}
						}
					],
				})

		});


		// UPLOAD IMAGE
		$('.upload-image').button({
			icons:{
				primary: 'ui-icon-arrowthick-1-n'
			}
		})
		$(document).on('click', '.upload-image', function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $image_fieldset = $(this).parents('.chapter-image-fieldset');
			var $input = $(this).parent().prev().find('input');
			var files = $input[0].files;
			var empty = $input.hasClass('empty');
			var chapter_id = $(this).parents('.chapter-fieldset').find('.chapter-id-hidden').val();
			var image_delta = $(this).attr('data-delta');
			var image_id = $(this).attr('data-image-id');
			var image_weight = $image_fieldset.find('.image-weight').val();
			var caption = $image_fieldset.find('.chapter-image-caption').val();
			if (empty) {
				$input.removeClass('empty');
			}
			if (caption == undefined) {
				caption = ' ';
			}
			var title = $image_fieldset.find('.chapter-image-title').val();
			if (title == undefined) {
				title = ' ';
			}
			// console.log('clicked upload image on chapter_id ' + chapter_id + ', image delta ' + image_delta);
			// console.log('our files: ', files );
			if (files.length) {
				//console.log('files exist in upload.  time to upload');
				if (files[0].type == 'image/gif' || files[0].type == 'image/png' || files[0].type == 'image/jpg' || files[0].type == 'image/jpeg') {
					var reader = new FileReader();
					$overlay.fadeIn(500);
					reader.onload = function(readerEvt) {
						var binaryString = readerEvt.target.result;
						//var imageString = 'data:' + files[0].type + ';base64,' + btoa(binaryString);
						var imageString = btoa(binaryString);
						var uploadData = {
							'chapter_id': chapter_id,
							'weight': image_weight,
							'file_type': files[0].type,
							'file_name': files[0].name,
							'file': imageString,
							'caption': caption,
							'title': title
						};
						if (image_delta != -1) {
							uploadData["delta"] = image_delta;
						}
						if (image_id != undefined) {
							uploadData["image_id"] = image_id;
						}
						$.post(ajaxurl, {
								action: 'fscarousel_upload_image',
								data: uploadData
						},
						function(response) {
							//window.location.reload();
							$('#fscarousel-carousel-form input[type="submit"]').click();
						});
					};
					reader.readAsBinaryString(files[0]);
				} else {
					var file_type = files[0].type ? files[0].type : "unknown";
					var $alert = $('<div id="alert"><p>Cannot upload file of type ' + file_type + '.  Only acceptable formats are PNG, JPEG, and GIF.</p></div>');
					$alert
						.dialog({
							autoOpen: true,
							modal: true,
							title: "Unacceptable file format",
							buttons:[
								{
									text: "OK",
									click: function() {
										$alert.dialog('close').dialog('destroy');
									}
								}
							]
						});
				}
			} else {
				//console.log('No files exist on input element');
			}
		});


		// CLICk: Remove Image
		$(document).on('click', '.remove-image', function(event) {
			event.preventDefault();
			
			var chapter_id = $(this).parents('.chapter-fieldset').find('.chapter-id-hidden').val();

			$(this).hide();
			var $input = $(this).siblings('input[type="file"]');
			$input.show().val('').addClass('empty');
			var delta = $input.attr('data-delta');
			var image_id = $input.attr('data-image-id');
			//console.log('clicked remove image on chapter_id ' + chapter_id + ' and delta ' + delta);
			$(this).parent().after('<div class="button-wrapper"><button class="upload-image" data-delta="' + delta + '" data-image-id="' + image_id + '">Upload</button></div>');
			$(this).siblings('img').remove();
			$('.upload-image').button({
				icons:{
					primary: 'ui-icon-arrowthick-1-n'
				}
			})
		});


		// SUBMIT: Form
		$('input[type="submit"]').button({
			icons: {
				primary: 'ui-icon-disk'
			}
		})

		$(document).on('submit', 'form', function(event) {
			var $file_wrappers = $(this).find('.file-wrapper');
			$file_wrappers.each(function() {
				var $input = $(this).find('input[type="file"]');
				var chapter_id = $input.parents('.chapter-fieldset').find('.chapter-id-hidden').val();
				var delta = $input.attr('data-delta');
				var image_id = $input.attr('data-image-id');
				if ($input.hasClass('empty')) {
					$.cookie('fscarousel_remove_image', chapter_id + ',' + image_id, {
						'path': '/'
					});
				}
			})
		})

		// CLICK: Add New Image
		$('.add-new-image').button({
			icons:{
				primary: "ui-icon-plus"
			}
		})
		$(document).on('click', '.add-new-image', function(event) {
			var chapter_id = $(this).attr('data-chapter-id');
			//console.log('clicked add new image for chapter ' + chapter_id);
			$.cookie('fscarousel_add_new_image', chapter_id, {
				'path': '/'
			});
			$('#fscarousel-carousel-form input[type="submit"]').click();
		});


		// Track anchors
		$(document).on('mousedown', function(event) {
			var $fieldset = $(event.target).parents('.chapter-image-fieldset');
			if (!$fieldset.length) {
				$fieldset = $(event.target).parents('.chapter-fieldset');
			}
			if ($fieldset.length) {
				var focusId = $fieldset.attr('id');
				if (hasStorage) {
					localStorage.setItem('fscarousel_focus_id', focusId);
				//	console.log('last focused fieldset: ' + focusId);
				}
			}
		})

		// images
		var $image_wrappers = $('.file-wrapper');
		$image_wrappers.each(function() {
			var $input = $(this).find('input[type="file"]');
			var data_url = $input.attr('data-url');
			if (data_url != undefined) {
				$input.hide();
				var $img = $('<img src="' + data_url + '" class="image-preview" />');
				$input.after($img);
				var $removeImage = $('<button class="remove-image">Remove</button>');
				$input.before($removeImage);
				$removeImage.button({
					icons:{
						primary: "ui-icon-trash"
					}
				})
			}
		})
	})
})(jQuery);