(function($) {
	$(document).on('click', '.upload-image', function(event) {
		event.preventDefault();
		event.stopPropagation();
		var $input = $(this).parent().prev().find('input');
		var files = $input[0].files;
		if (files.length) {
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
			}
			else {
				var acceptableFileTypesHtml = acceptableFileTypes.join(', ');
			   	alert('Cannot upload file of type ' + files[0].type + '.  Only acceptable formats are ' + acceptableFileTypesHtml);
			}
		}
		else {
			console.log('No files exist on input element');
		}
	});
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
			$.cookie('fscarousel_remove_settings_image', property_names, {'path': '/'});
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
		}
	})	
})(jQuery);