(function($) {
	$(document).ready(function() {
		$.removeCookie('fscarousel_add_new_image', {path: '/'});
		$.removeCookie('fscarousel_add_new_chapter',{path: '/'});

		Drupal.behaviors.collapse.attach(document, {});

		var query = window.location.search.substr(1,window.location.search.length - 1).split('&');
		var queryObj = {};
		var page_name = '';
		for (var i = 0, q; q = query[i]; i++) {
			var split = q.split('=');
			queryObj[split[0]] = split[1];
			if (split[0] == 'page') {
				page_name = split[1];
			}
		}
		$(document).on('click', '.add-new-chapter', function(event) {
			$.cookie('fscarousel_add_new_chapter', 1, {'path': '/'});
			$('#fscarousel-carousel-form input[type="submit"]').click();
		});
		$(document).on('click', '.remove-chapter', function(event) {
			var $fieldset = $(this).parents('.chapter-fieldset');
			var chapter_id = $fieldset.find('.chapter-id-hidden').val();
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
					}
					else if (answer == 'chapter_deleted') {
						window.location.reload();
					}
				}
			});
		});

		$(document).on('click', '.upload-image', function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $image_fieldset = $(this).parents('.chapter-image-fieldset');
			var files = $(this).parent().prev().find('input')[0].files;
			var chapter_id = $(this).parents('.chapter-fieldset').find('.chapter-id-hidden').val();
			var image_delta = $(this).attr('data-delta');
			var image_weight = $image_fieldset.find('.image-weight').val();
			var caption = $image_fieldset.find('.chapter-image-caption').val();
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
				    reader.onload = function(readerEvt) {
				        var binaryString = readerEvt.target.result;
				        //var imageString = 'data:' + files[0].type + ';base64,' + btoa(binaryString);
				        var imageString = btoa(binaryString);
				        $.post(ajaxurl, {
				        	action: 'fscarousel_upload_image',
				        	data: {
				        		'chapter_id': chapter_id, 
				        		'delta' : image_delta,
				        		'weight': image_weight,
				        		'file_type': files[0].type,
				        		'file_name': files[0].name,
				        		'file': imageString,
				        		'caption': caption,
				        		'title': title
				        	}
				        },
				        function(response) {
				        	//window.location.reload();
				        	$('#fscarousel-carousel-form input[type="submit"]').click();
				        });
				    }
				    reader.readAsBinaryString(files[0]);
				}
				else {
				   	alert('Cannot upload file of type ' + files[0].type + '.  Only acceptable formats are PNG, JPEG, and GIF.');
				}
			}
			else {
				//console.log('No files exist on input element');
			}
		});
		// CLICk: Remove Image
		$(document).on('click', '.remove-image', function(event) {
			event.preventDefault();
			event.stopPropagation();
			var chapter_id = $(this).parents('.chapter-fieldset').find('.chapter-id-hidden').val();
			
			$(this).hide();
			var $input = $(this).siblings('input[type="file"]');
			$input.show().val('').addClass('empty');
			var delta = $input.attr('data-delta');
			//console.log('clicked remove image on chapter_id ' + chapter_id + ' and delta ' + delta);
			$(this).parent().after('<div class="button-wrapper"><button class="upload-image" data-delta="' + delta + '">Upload</button></div>');
			$(this).siblings('img').remove();
		});
		// SUBMIT: Form
		$(document).on('submit', 'form', function(event) {
			var $file_wrappers = $(this).find('.file-wrapper');
			$file_wrappers.each(function() {
				var $input = $(this).find('input[type="file"]');
				var chapter_id = $input.parents('.chapter-fieldset').find('.chapter-id-hidden').val();
				var delta = $input.attr('data-delta');
				if ($input.hasClass('empty')) {
					$.cookie('fscarousel_remove_image', chapter_id + ',' + delta, {'path': '/'});
				}
			})
		})
		// CLICK: Add New Image
		$(document).on('click', '.add-new-image', function(event) {
			var chapter_id = $(this).attr('data-chapter-id');
			//console.log('clicked add new image for chapter ' + chapter_id);
			$.cookie('fscarousel_add_new_image', chapter_id, {'path': '/'});
			$('#fscarousel-carousel-form input[type="submit"]').click();
		});
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
			}
		})
	})
})(jQuery);