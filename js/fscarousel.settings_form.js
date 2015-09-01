(function($) {
	$(document).ready(function() {
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
		// quick loading indicator
		var $spinner_container = $('<div id="spinner-container" style="position: relative; height: 100%; width: 100%;"/>');
		var $spinner = $('<div id="spinner" />');
		$spinner_container.append($spinner);
		$overlay.append($spinner_container);
		$("body").append($overlay);
		$spinner.spin({
			color: '#fff'
		});


		$(document).on('click', '.upload-image', function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $input = $(this).parent().prev().find('input');
			var files = $input[0].files;
			if (files.length) {
				$overlay.fadeIn(500);
				var acceptableFileTypes = ['image/gif', 'image/png', 'image/jpg', 'image/jpeg'];
				if ($input[0].name == 'favicon') {
					acceptableFileTypes = ['image/png'];
				}

				if ($.inArray(files[0].type, acceptableFileTypes) != -1) {
					var reader = new FileReader();
					reader.onload = function(readerEvt) {
						var binaryString = readerEvt.target.result;
						//var imageString = 'data:' + files[0].type + ';base64,' + btoa(binaryString);
						var imageString = btoa(binaryString);
						$.post(ajaxurl, {
								action: 'fscarousel_upload_si_ajax',
								data: {
									'setting_name': $input.attr('name'),
									'file_type': files[0].type,
									'file_name': files[0].name,
									'file': imageString,
								}
							},
							function(response) {
								window.location.reload();
							});
					}
					reader.readAsBinaryString(files[0]);
				} else {
					var acceptableFileTypesHtml = acceptableFileTypes.join(', ');
					var file_type = files[0].type ? files[0].type : "unknown";
					var $alert = $('<div id="alert"><p>Cannot upload file of type ' + file_type + '.  Only acceptable formats are ' + acceptableFileTypesHtml + '</p></div>');
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
		// REMOVE IMAGES
		$(document).on('click', '.remove-image', function(event) {
			event.preventDefault();
			event.stopPropagation();
			$(this).hide();
			var $input = $(this).siblings('input[type="file"]');
			var name = $input[0].name;
			$input.show().val('').addClass('empty').attr('data-property-name', name);
			//console.log('clicked remove image on chapter_id ' + chapter_id + ' and delta ' + delta);
			$(this).parent().after('<div class="button-wrapper"><button class="upload-image" data-property-name="' + name + '">Upload</button></div>');
			$(this).siblings('img').remove();
		});

		// SUBMIT FORM
		$(document).on('submit', 'form', function(event) {
			var $file_wrappers = $(this).find('.file-wrapper');
			var property_names = [];
			$file_wrappers.each(function() {
				var $input = $(this).find('input[type="file"]');
				var property_name = $input.attr('data-property-name');
				if ($input.hasClass('empty')) {
					property_names.push(property_name);
				}
			})
			if (property_names.length) {
				$.cookie('fscarousel_remove_settings_image', property_names, {
					'path': '/'
				});
			}
		})

		// REFRESH THUMBNAILS
		$('.fscarousel-purge-thumbs').button({icons: {
			primary: "ui-icon-arrowrefresh-1-n"
		}});
		$(document).on('click', '.fscarousel-purge-thumbs', function(event) {
			event.preventDefault();
			$overlay.fadeIn(500);
			$.post(ajaxurl, {
					action: 'fscarousel_refresh_thumbs',
					data: {}
				},
				function(response) {
					$overlay.fadeOut(500);
					var dialog = $("<div class=\"message-dialog\">Thumbnails have been successfully refreshed!</div>")
						.dialog({
							autoOpen: true,
							modal: true,
							resizable: false,
							draggable: false,
							title: '',
							dialogClass: 'message-dialog',
							buttons: [{
								'text': 'OK',
								'click': function() {
									dialog.dialog('close');
									window.location.reload();
								}
							}]
						});
				});
		})

		$('input[type="submit"]').button({
			icons: {
				primary: 'ui-icon-disk'
			}
		})

		var $image_wrappers = $('.file-wrapper');
		$image_wrappers.each(function() {
			var $input = $(this).find('input[type="file"]');
			var data_url = $input.attr('data-url');
			if (data_url != undefined && data_url != '') {
				$input.hide();
				var $img = $('<img src="' + data_url + '" class="image-preview" />');
				$input.after($img);
				var $removeImage = $('<button class="remove-image">Remove</button>');
				$input.before($removeImage);
				$removeImage.button({
					icons: {
						primary: 'ui-icon-trash'
					}
				})
			}
		})
	});
})(jQuery);