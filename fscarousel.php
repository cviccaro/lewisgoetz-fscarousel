<?php
/*
Plugin Name: Fullscreen Carousel
Plugin URI:  http://lewisgoetz.jpedev.com
Description: A full-screen carousel with chapter support
Version:     1.0
Author:      Chris Viccaro
Author URI:  http://lewisgoetz.jpedev.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: fs-carousel
*/
?>
<?php

// @link: https://codex.wordpress.org/Writing_a_Plugin
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Include PageTemplater class
// @link: https://github.com/wpexplorer/page-templater/blob/master/pagetemplater.php
require_once('includes/PageTemplater.class.php');

// Register our AJAX actions (before any content is ever printed from this PHP file)
add_action( 'wp_ajax_fscarousel_remove_chapter', 'fscarousel_remove_chapter_ajax' );
add_action( 'wp_ajax_fscarousel_upload_image', 'fscarousel_upload_image_ajax' );
add_action( 'wp_ajax_fscarousel_get_image_url', 'fscarousel_get_image_url_ajax' );
add_action( 'wp_ajax_fscarousel_upload_si_ajax', 'fscarousel_upload_si_ajax' ); // upload a settings image

/*
 * admin_menu Action Hooks
 * -- Adds a menu page with submenus
 */
function fscarousel_admin_panel() {
    add_menu_page('Theme page title', 'Fullscreen Carousel', 'delete_pages', 'fscarousel_admin_edit', 'fscarousel_admin_edit', plugins_url('images/sidebar.icon.png', __FILE__));
    add_submenu_page('fscarousel_admin_edit', 'Edit', 'Edit', 'delete_pages', 'fscarousel_admin_edit', 'fscarousel_admin_edit');
    add_submenu_page('fscarousel_admin_edit', 'Settings', 'Settings', 'manage_options', 'fscarousel_admin_settings', 'fscarousel_admin_settings');
}
add_action('admin_menu', 'fscarousel_admin_panel');

// Edit Page
function fscarousel_admin_edit() {
	echo '<h1 id="page-title" class="page-title">Edit the Carousel</h1>';
	echo '<div class="page-description"><ul class="help-list"><li>Click any heading to collapse or expand it.</li><li>Chapters and Images are both sorted by weight.  Items with less weight appear at the top.</li></ul></div>';
	echo wp_get_form('fscarousel-carousel-form');
}	

// Settings Page
function fscarousel_admin_settings() {
	echo '<h1 id="page-title" class="page-title">Fullscreen Carousel Settings</h1>';
	echo wp_get_form('fscarousel-carousel-settings-form');
}

/**
 * wp-forms Action Hooks
 */
add_action( 'wp_forms_register', 'fscarousel_register_forms');
function fscarousel_register_forms() {
	wp_register_form( 'fscarousel-carousel-form', 'fscarousel_carousel_form' );
	wp_register_form( 'fscarousel-carousel-settings-form', 'fscarousel_settings_form' );
}

/**
 * Settings Form
 */
function fscarousel_settings_form($form) {
	$settings = fscarousel_get_settings();

	// SETTING: Page Title
	$page_title = WP_Form_Element::create('text')
			->set_name('page_title')
			->set_label('Page Title')
			->set_attribute('size', 40);
	if (isset($settings['page_title']) && !empty($settings['page_title'])) {
		$page_title->set_default_value($settings['page_title']);
	}
	$form->add_element($page_title);

	// SETTING: Carousel Title
	$carousel_title = WP_Form_Element::create('text')
			->set_name('carousel_title')
			->set_label('Carousel Title')
			->set_attribute('size', 40);
	if (isset($settings['carousel_title']) && !empty($settings['carousel_title'])) {
		$carousel_title->set_default_value($settings['carousel_title']);
	}
	$form->add_element($carousel_title);

	// SETTING: Logo
	$logo_upload = WP_Form_Element::create('file')
						->set_name('logo')
						->set_label('Logo')
						->set_description('Logo should be 71px tall if using the default template.')
						->set_attribute('class', 'fscarousel-logo-upload');
	$show_upload_button = TRUE;
    if (isset($settings['logo']) && !empty($settings['logo'])) {
    	$logo_upload->set_attribute('data-url', $settings['logo']);
    	$show_upload_button = FALSE;
    }
	$form->add_element($logo_upload);

	if ($show_upload_button) {
		$upload_button = WP_Form_Element::create('button')
						->set_view(new WP_Form_View_Button())
						->apply_default_decorators()
						->set_name('logo[upload]')
						->set_label('Upload')
						->set_attribute('class', 'upload-image logo');

		$form->add_element($upload_button);
	}

	// SETTING: FavIcon
	$favicon_upload = WP_Form_Element::create('file')
						->set_name('favicon')
						->set_label('Favicon')
						->set_attribute('class', 'fscarousel-favicon-upload');
	$show_upload_button = TRUE;
    if (isset($settings['favicon']) && !empty($settings['favicon'])) {
    	$favicon_upload->set_attribute('data-url', $settings['favicon']);
    	$show_upload_button = FALSE;
    }
	$form->add_element($favicon_upload);

	if ($show_upload_button) {
		$upload_button = WP_Form_Element::create('button')
						->set_view(new WP_Form_View_Button())
						->apply_default_decorators()
						->set_name('favicon[upload]')
						->set_label('Upload')
						->set_attribute('class', 'upload-image favicon');

		$form->add_element($upload_button);
	}

	// SETTING: Exit Destination
	$exit_destination_default = 'back';
	$exit_destination = WP_Form_Element::create('select')
							->set_name('exit_destination')
							->set_label('Exit Destination')
							->set_description('The page to goto when the user exits out of the app.')
							->add_option('back', 'Back to the page where they came from')
							->add_option('custom', 'Custom URL');
	if (isset($settings['exit_destination'])) {
		$exit_destination_default = $settings['exit_destination'];
		$exit_destination->set_default_value($exit_destination_default);
	}
	$form->add_element($exit_destination);

	if ($exit_destination_default == 'custom') {
		// Custom Exit Destination
		$custom_exit = WP_Form_Element::create('text')
							->set_name('exit_destination_custom')
							->set_label('Custom Exit URL')
							->set_description('Set the URL to goto when the user exits the app');	
		if (isset($settings['exit_destination_custom']) && $settings['exit_destination_custom']) {
			$custom_exit->set_default_value($settings['exit_destination_custom']);
		}
		$form->add_element($custom_exit);
	}

	// SETTING: Show infobox by default
	$infobox_show = WP_Form_Element::create('checkbox')
						->set_name('infobox_show')
						->set_label('Open infobox by default')
						->set_description('Start the slideshow with the caption information box already visible');
	if (isset($settings['infobox_show']) && $settings['infobox_show']) {
		$infobox_show->set_attribute('checked', $settings['infobox_show']);
	}
	$form->add_element($infobox_show);

	// SETTING: Autoplay
	$autoplay = WP_Form_Element::create('checkbox')
						->set_name('autoplay')
						->set_label('Autoplay')
						->set_description('Begin the slideshow when the page loads');
	if (isset($settings['autoplay']) && $settings['autoplay']) {
		$autoplay->set_attribute('checked', $settings['autoplay']);
	}
	$form->add_element($autoplay);

	// SETTING: Slide Interval
	$interval = WP_Form_Element::create('text')
						->set_name('interval')
						->set_label('Slide Interval (ms)')
						->set_description('Set the duration a slide appears for in the slideshow when the slideshow is playing.  Leave blank for a default value of 5000ms');	
	if (isset($settings['interval']) && $settings['interval']) {
		$interval->set_default_value($settings['interval']);
	}
	$form->add_element($interval);

	// SETTING DISPLAY MEHTOD
	$display_method_default = 'stacked';
	$display_method = WP_Form_Element::create('select')
							->set_name('display_method')
							->set_label('Display Method')
							->add_option('stacked', 'Stacked')
							->add_option('slide', 'Slide');
	if (isset($settings['display_method']) && !empty($settings['display_method'])) {
		$display_method->set_default_value($settings['display_method']);
		$display_method_default = $settings['display_method'];
	}
	$form->add_element($display_method);

	if ($display_method_default == 'stacked') {
		$animation_method_default = 'css';

	// SETTING: Animation method
		$animation_method = WP_Form_Element::create('select')
								->set_name('animation_method')
								->set_label('Animation Method')
								->add_option('css', 'CSS (Smoother, better performance)')
								->add_option('js', 'Javascript (More compatible, less performance)');
		if (isset($settings['animation_method']) && !empty($settings['animation_method'])) {
			$animation_method->set_default_value($settings['animation_method']);
			$animation_method_default = $settings['animation_method'];
		}
		$form->add_element($animation_method);

		// Javascript Animation Options
		if ($animation_method_default == 'js') {
			$animation_categories = _fscarousel_settings_animation_categories();
			$easing_options = _fscarousel_settings_easing_options();

			foreach($animation_categories as $name => $label) {
				$fieldset = WP_Form_Element::create('fieldset')
											->set_name($name)
											->set_label($label)
											->set_attribute('class', 'form-item animation-options ' . $name);
				

				// Allow configuration of duration							
			    $duration = WP_Form_Element::create('text')
			    							->set_name($name . '[duration]')
			    							->set_label('Duration (ms)')
			    							->set_attribute('size', 4);
			    if (isset($settings[$name]['duration'])) {
			    	$duration->set_default_value($settings[$name]['duration']);
			    }
			    $fieldset->add_element($duration);

			    // Allow configuration of easing
			    $easing = WP_Form_Element::create('select')
			    							->set_name($name . '[easing]')
			    							->set_label('Easing');
			    foreach($easing_options as $val_easing => $key_easing) {
			    	$easing->add_option($key_easing, $val_easing);
			    }
			    if (isset($settings[$name]['easing'])) {
			    	$easing->set_default_value($settings[$name]['easing']);
			    }
			    $fieldset->add_element($easing);

			    if (isset($settings['display_method']) && $settings['display_method'] == "slide" && $name == 'slideAnimationOptions') {
			    	$effect = WP_Form_Element::create('select')
			    			->set_name($name . '[effect]')
			    			->set_label('Effect');
			    	$effect_options = _fscarousel_settings_effect_options();
			    	foreach($effect_options as $val_effect => $key_effect) {
			    		$effect->add_option($key_effect, $val_effect);
			    	}
			    	if (isset($settings[$name]['effect'])) {
			    		$effect->set_default_value($settings[$name]['effect']);
			    	}
			    	$fieldset->add_element($effect);
			    }


				$form->add_element($fieldset);
			}
		}

	}

	// Add validator, processor, and submit button
	$form->add_validator('fscarousel_settings_form_validate', 10 );
	$form->add_processor('fscarousel_settings_form_submit', 10);
	$form->add_element(
		WP_Form_Element::create('submit')
			->set_name('submit')
	);

	// Queue styles and scripts
	wp_enqueue_style('carousel_form_css',plugins_url('css/fscarousel.carousel_form.css', __FILE__));
	wp_enqueue_script("jquery_cookie",  plugins_url("js/jquery.cookie.js", __FILE__), FALSE);
	wp_enqueue_script("carousel_settings_js",  plugins_url("js/fscarousel.settings_form.js", __FILE__), FALSE);
}


function fscarousel_settings_form_validate(WP_Form_Submission $submission, WP_Form $form) {
	$required = array('carousel_title', 'page_title');
	foreach($required as $name) {
		if (trim($submission->get_value($name)) == '') {
			$submission->add_error($name, ucwords(str_replace('_', ' ', $name)) . ' cannot be blank.');
		}
	}
}

function fscarousel_settings_form_submit(WP_Form_Submission $submission, WP_Form $form) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_settings';
	$settings = array();

	// Gather from submission
	$gather = array('autoplay', 'animation_method', 'display_method', 'carousel_title', 'page_title', 'infobox_show', 'interval', 'exit_destination', 'exit_destination_custom');
	foreach($gather as $name) {
		$value = $submission->get_value($name);
		switch($name) {
			case 'infobox_show':
			case 'autoplay':
				$settings[$name] = !is_null($value);
			break;
			default:
				$settings[$name] = $value;
		}
	}

	// Remove images?
	if (isset($_COOKIE['fscarousel_remove_settings_image'])) {
		$keys = explode(',', $_COOKIE['fscarousel_remove_settings_image']);
		foreach($keys as $key) {
			$settings[$key] = '';
		}
		setcookie('fscarousel_remove_settings_image', '', time() - 3600, '/');
	}

   	$animation_categories = _fscarousel_settings_animation_categories();
	foreach($animation_categories as $key => $name) {
		$val = $submission->get_value($key);
		if ($val != '') {
			$settings[$key] = $val;
		}
	}
   	fscarousel_update_settings($settings);
}
// END SETTINGS FORM


/**
 * Carousel Edit Form
 */
function fscarousel_carousel_form($form) {
	// Get our data first
	$chapter_count = fscarousel_get_chapter_count();
	$chapters = fscarousel_get_chapters();

	/**
	 * Preliminary CRUD tasks
	 */
		
	// Remove Image
	if (isset($_COOKIE['fscarousel_remove_image'])) {
		$remove_info = explode(',',$_COOKIE['fscarousel_remove_image']);
		$remove_image_chapter_id = $remove_info[0];
		$remove_image_delta = $remove_info[1];
		fscarousel_unregister_image($remove_image_chapter_id, $remove_image_delta);
		setcookie('fscarousel_remove_image', '', time() - 3600, '/');
	}

	// Add Chapter
	if (isset($_COOKIE['fscarousel_add_new_chapter'])) {
		$empty_chapter = new stdClass();
		$empty_chapter->title = '';
		$empty_chapter->chapter_id = fscarousel_next_chapter_id();
		$empty_chapter->weight = fscarousel_next_chapter_weight();
		$empty_chapter->is_empty = true;
		$chapters[] = $empty_chapter;
		//setcookie('fscarousel_add_new_chapter', '', time() - 3600, '/');
	}

	// Add Image
	$add_image_to_chapter_id = FALSE;
	if (isset($_COOKIE['fscarousel_add_new_image'])) {
		$add_image_to_chapter_id = $_COOKIE['fscarousel_add_new_image'];
	}

	/**
	 * Build Form
	 */

	// Create overarching fieldset, "chapters"
	$chapters_fieldset = WP_Form_Element::create('fieldset')
						->set_name('chapters')
						->set_label('Chapters')
						->set_attribute('class', 'chapters-fieldset');

	$i = 0;

	if (!empty($chapters)) {
		// Create a fieldset for each chapter
		foreach($chapters as $chapter) {
			$chapter_id = $chapter->chapter_id;
			$chapter_prefix = 'chapters[' . $i . ']';
			$chapter_fieldset = WP_Form_Element::create('fieldset')
								->set_name($chapter_prefix)
								->set_label("Chapter " . ($i + 1))
								->set_attribute('class', 'chapter-fieldset collapsible');
			$chapter_id_field = WP_Form_Element::create('hidden')
								->set_name($chapter_prefix . '[chapter_id]')
								->set_default_value($chapter_id)
								->set_attribute('class', 'chapter-id-hidden');
			$chapter_title = WP_Form_Element::create('text')
								->set_name($chapter_prefix . '[title]')
								->set_label('Chapter Title')
								->set_attribute('class', 'chapter-title');
			$chapter_title->set_default_value($chapter->title);
			$chapter_weight = WP_Form_Element::create('text')
								->set_name($chapter_prefix . '[weight]')
								->set_label('Weight')
								->set_attribute('class', 'chapter-weight');
			$chapter_weight->set_default_value($chapter->weight);	

			$chapter_remove_button = WP_Form_Element::create('button')
							->set_view(new WP_Form_View_Button())
							->apply_default_decorators()
							->set_name($chapter_prefix . '[remove]')
							->set_label('Remove Chapter')
							->set_attribute('class', 'remove-chapter');
			$chapter_images_prefix = $chapter_prefix . '[images]';
			$chapter_images_fieldset = WP_Form_Element::create('fieldset')
										->set_name($chapter_images_prefix)
										->set_label('Images')
										->set_attribute('class', 'chapter-images-fieldset collapsible');
			$chapter_images = fscarousel_get_chapter_images($chapter_id);
			if (empty($chapter_images)) {
				$chapter_image_prefix = $chapter_images_prefix . '[0]';
				$empty_image_fieldset = WP_Form_Element::create('fieldset')
											->set_name($chapter_image_prefix)
											->set_label('Image 1')
											->set_attribute('class', 'chapter-image-fieldset');
				$empty_chapter_image = WP_Form_Element::create('file')
										->set_name($chapter_image_prefix . '[image]')
										->set_label('Choose file')
										->set_attribute('class', 'chapter-image')
										->set_attribute('data-delta', 0);
				$empty_chapter_image_weight = WP_Form_Element::create('text')
										->set_name($chapter_image_prefix . '[weight]')
										->set_label('Weight')
										->set_attribute('class', 'image-weight')
										->set_default_value(0);							
				$empty_chapter_image_title = WP_Form_Element::create('text')
										->set_name($chapter_image_prefix . '[title]')
										->set_label('Title')
										->set_attribute('class', 'chapter-image-title');
				$empty_chapter_image_caption = WP_Form_Element::create('textarea')
										->set_name($chapter_image_prefix . '[caption]')
										->set_label('Caption')
										->set_attribute('class', 'chapter-image-caption')
										->set_attribute('required', 'required');
				$empty_chapter_image_upload_button = WP_Form_Element::create('button')
								->set_view(new WP_Form_View_Button())
								->apply_default_decorators()
								->set_name($chapter_image_prefix . '[upload]')
								->set_label('Upload')
								->set_attribute('class', 'upload-image')
								->set_attribute('data-delta', 0);

				$empty_image_fieldset->add_element($empty_chapter_image);
				$empty_image_fieldset->add_element($empty_chapter_image_upload_button);
				$empty_image_fieldset->add_element($empty_chapter_image_title);
				$empty_image_fieldset->add_element($empty_chapter_image_caption);
				$empty_image_fieldset->add_element($empty_chapter_image_weight);

				$chapter_images_fieldset->add_element($empty_image_fieldset);
			}
			else {
				$n = 0;
				$highest_weight = 0;
				foreach($chapter_images as $image) {
					$chapter_image_prefix = $chapter_images_prefix . '[' . $n . ']';
					$chapter_image_fieldset = WP_Form_Element::create('fieldset')
												->set_name($chapter_image_prefix)
												->set_label('Image ' . ($n + 1))
												->set_attribute('class', 'chapter-image-fieldset');

					$chapter_image = WP_Form_Element::create('file')
									->set_name($chapter_image_prefix . '[image]')
									->set_label('Choose file')
									->set_attribute('class', 'chapter-image')
									->set_attribute('data-delta', $image->delta)
									->set_default_value(basename($image->filepath))
									->set_attribute('data-url', _fscarousel_baseurl() . $image->fileurl);
					$chapter_image_chapter_id = WP_Form_Element::create('text')
												->set_name($chapter_image_prefix . '[image][chapter_id]')
												->set_attribute('class', 'hidden chapter-id-hidden')
												->set_default_value($image->chapter_id);
					$chapter_image_weight = WP_Form_Element::create('text')
											->set_name($chapter_image_prefix . '[weight]')
											->set_label('Weight')
											->set_attribute('class', 'image-weight')
											->set_default_value($image->weight);		

					if ($image->weight > $highest_weight) {
						$highest_weight = $image->weight;
					}

					$chapter_image_delta = WP_Form_Element::create('text')
												->set_name($chapter_image_prefix . '[image][delta]')
												//->set_label('Delta')
												->set_attribute('class', 'hidden delta-hidden')
												->set_default_value($image->delta);
					$chapter_image_title = WP_Form_Element::create('text')
											->set_name($chapter_image_prefix . '[title]')
											->set_label('Title')
											->set_attribute('class', 'chapter-image-title')
											->set_default_value($image->title);												
					$chapter_image_caption = WP_Form_Element::create('textarea')
											->set_name($chapter_image_prefix . '[caption]')
											->set_label('Caption')
											->set_attribute('class', 'chapter-image-caption')
											->set_default_value($image->caption);

					$chapter_image_fieldset->add_element($chapter_image);
					$chapter_image_fieldset->add_element($chapter_image_chapter_id);
					$chapter_image_fieldset->add_element($chapter_image_delta);
					$chapter_image_fieldset->add_element($chapter_image_title);
					$chapter_image_fieldset->add_element($chapter_image_caption);
					$chapter_image_fieldset->add_element($chapter_image_weight);	

					$chapter_images_fieldset->add_element($chapter_image_fieldset);
					$n++;
				}
				if ($add_image_to_chapter_id !== FALSE && $add_image_to_chapter_id == $chapter_id) {
					$chapter_image_prefix = $chapter_images_prefix . '[' . $n . ']';
					$chapter_image_fieldset = WP_Form_Element::create('fieldset')
												->set_name($chapter_image_prefix)
												->set_label('Image ' . ($n + 1))
												->set_attribute('class', 'chapter-image-fieldset');
					$new_chapter_image = WP_Form_Element::create('file')
											->set_name($chapter_image_prefix . '[image]')
											->set_label('Choose file')
											->set_attribute('class', 'chapter-image')
											->set_attribute('data-delta', $n);
					$new_chapter_image_weight = WP_Form_Element::create('text')
											->set_name($chapter_image_prefix . '[weight]')
											->set_label('Weight')
											->set_attribute('class', 'image-weight')
											->set_default_value($highest_weight + 10);
					$new_chapter_image_title = WP_Form_Element::create('text')
											->set_name($chapter_image_prefix . '[title]')
											->set_label('Title')
											->set_attribute('class', 'chapter-image-title');											
					$new_chapter_image_caption = WP_Form_Element::create('textarea')
											->set_name($chapter_image_prefix . '[caption]')
											->set_label('Caption')
											->set_attribute('class', 'chapter-image-caption');
					$upload_button = WP_Form_Element::create('button')
									->set_view(new WP_Form_View_Button())
									->apply_default_decorators()
									->set_name($chapter_image_prefix . '[upload]')
									->set_label('Upload')
									->set_attribute('class', 'upload-image')
									->set_attribute('data-delta', $n);

					$chapter_image_fieldset->add_element($new_chapter_image);
					$chapter_image_fieldset->add_element($upload_button);
					$chapter_image_fieldset->add_element($new_chapter_image_title);
					$chapter_image_fieldset->add_element($new_chapter_image_caption);
					$chapter_image_fieldset->add_element($new_chapter_image_weight);

					$chapter_images_fieldset->add_element($chapter_image_fieldset);
				}
				else {
					// Add New Image Button
					$add_new_image = WP_Form_Element::create('button')
									->set_view(new WP_Form_View_Button())
									->apply_default_decorators()
									->set_name($chapter_images_prefix . '[add_new_image]')
									->set_label('Add New Image')
									->set_attribute('data-chapter-id', $chapter_id)
									->set_attribute('class', 'add-new-image');
					$chapter_images_fieldset->add_element($add_new_image);
				}
			}


			$chapter_fieldset->add_element($chapter_id_field);
			$chapter_fieldset->add_element($chapter_title);
			$chapter_fieldset->add_element($chapter_weight);
			$chapter_fieldset->add_element($chapter_images_fieldset);
			if ($chapter_count > 1) {
				$chapter_fieldset->add_element($chapter_remove_button);
			}

			$chapters_fieldset->add_element($chapter_fieldset);
			$i++;
		}
	}
	$add_new_chapter_button = WP_Form_Element::create('button')
							->set_view(new WP_Form_View_Button())
							->apply_default_decorators()
							->set_name('add_new_chapter')
							->set_label('Add New Chapter')
							->set_attribute('class', 'add-new-chapter');
							// ->set_attribute('onclick', "window.location.href='admin.php?page=fscarousel_admin_menu&task=add_new&cs=" . ($chapter_count + 1) . "'");
	$chapters_fieldset->add_element($add_new_chapter_button);
	$form->add_element($chapters_fieldset);

	$form->add_element(
		WP_Form_Element::create('submit')
			->set_name('submit')
	);
	$form->add_validator('fscarousel_carousel_form_validate', 10 );
	$form->add_processor('fscarousel_carousel_form_submit', 10);
	wp_enqueue_style('carousel_form_css',plugins_url('css/fscarousel.carousel_form.css', __FILE__));
	wp_enqueue_script("jquery_once",  plugins_url("js/jquery.once.min.js", __FILE__), FALSE);
	wp_enqueue_script("jquery_cookie",  plugins_url("js/jquery.cookie.js", __FILE__), FALSE);
	wp_enqueue_script("jquery_collapsible",  plugins_url("js/collapse.js", __FILE__), FALSE);
	wp_enqueue_script("jquery_ui",  plugins_url("js/jquery-ui.min.js", __FILE__), FALSE);
	wp_enqueue_style('jquery_ui_css',plugins_url('css/jquery-ui.min.css', __FILE__));
	wp_enqueue_style('jquery_ui_structure_css',plugins_url('css/jquery-ui.structure.min.css', __FILE__));
	wp_enqueue_script("carousel_form_js",  plugins_url("js/fscarousel.carousel_form.js", __FILE__), FALSE);
}

function fscarousel_carousel_form_validate(WP_Form_Submission $submission, WP_Form $form) {
	  $chapters = $submission->get_value('chapters');
	  foreach($chapters as $idx => $chapter) {
	  	if (empty($chapter['title'])) {
	  		$submission->add_error('chapters[' . $idx . '][title]', 'Chapter title cannot be blank.');
	  	}
	  	$images = $submission->get_value('chapters[' . $idx . '][images]');
	  	//log_error(print_r($images, TRUE));
	  	foreach($images as $image_idx => $image) {
	  		if (isset($image['image'])) {
	  			if (is_array($image['image'])) {
	  				// already uploaded
	  			}
	  		}
	  		else {
				$submission->add_error('chapters[' . $idx . '][images][' . $image_idx . '][image]', 'The image file cannot be blank.');
	  		}
	  		if (!isset($image['caption']) || strlen($image['caption']) === 0) {
	  			$submission->add_error('chapters[' . $idx . '][images][' . $image_idx . '][caption]', 'Image caption cannot be blank.');
	  		}
	  	}
	  }
}

function fscarousel_carousel_form_submit(WP_Form_Submission $submission, WP_Form $form) {
	global $wpdb;

   	// Update chapters
   	$chapters = $submission->get_value('chapters');

   	foreach($chapters as $chapter) {
   		$chapter_id = $chapter['chapter_id'];
   		$chapter_row = fscarousel_get_chapter($chapter_id);
   		if (!is_null($chapter_row)) {
   			fscarousel_update_chapter($chapter_id, $chapter);
   		}
   		else {
   			fscarousel_insert_chapter($chapter_id, $chapter);	
   		}
   	}
}

function fscarousel_update_chapter($chapter_id, $chapter) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_chapters';
	$query = "UPDATE $table_name SET title = %s, weight = %d, modified = %s WHERE chapter_id = %d";
	$updated = $wpdb->query(
		$wpdb->prepare($query, $chapter['title'], $chapter['weight'], time(), $chapter_id)
	);
	if (isset($chapter['images'])) {
		fscarousel_update_chapter_images($chapter['images']);
	}
	return $updated;
}

function fscarousel_insert_chapter($chapter_id, $chapter) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_chapters';
	$query = "INSERT INTO $table_name (chapter_id, title, weight, created, modified) VALUES (%d, %s, %d, %s, %s)";
	$inserted = $wpdb->query(
		$wpdb->prepare($query, $chapter['chapter_id'], $chapter['title'], $chapter['weight'], time(), time())
	);
	if (isset($chapter['images'])) {
		fscarousel_update_chapter_images($chapter['images']);
	}
	return $inserted;
}

function fscarousel_remove_chapter($chapter_id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_chapters';
	return $wpdb->delete($table_name, array('chapter_id' => $chapter_id), array('%d'));
}

function fscarousel_update_chapter_images($images) {
	global $wpdb;
	//log_error(print_r($images, TRUE));
	foreach($images as $image) {
		if (is_array($image['image'])) {
			// previously uploaded
			// update attributes
			$table_name = $wpdb->prefix . 'fscarousel_images';
			$query = "UPDATE $table_name SET caption = %s, title = %s, weight = %d WHERE chapter_id = %d AND delta = %d";
			$image_updated = $wpdb->query(
				$wpdb->prepare($query, $image['caption'], $image['title'], $image['weight'], $image['image']['chapter_id'], $image['image']['delta'])
			);
		}
	}
}

/**
 * Ajax Handlers
 */
function fscarousel_remove_chapter_ajax() {
	$chapter_id = $_POST['data']['chapter_id'];
	$chapter = fscarousel_get_chapter($chapter_id);
	if (is_null($chapter)) {
		if (isset($_COOKIE['fscarousel_add_new_chapter'])) {
			setcookie('fscarousel_add_new_chapter', '', time() - 300, '/');
		}
		if (isset($_COOKIE['fscarousel_add_new_image'])) {
			setcookie('fscarousel_add_new_image', '', time() - 300, '/');
		}
		$response = array(
		   'response'=>'unsaved_chapter_deleted',
		   'id'=> $chapter_id,
		);
	}
	else {
		fscarousel_remove_chapter($chapter_id);
		$response = array(
		   'response'=>'chapter_deleted',
		   'id'=> $chapter_id,
		);
	}
	wp_send_json($response);
}

function fscarousel_upload_si_ajax() {
	$baseurl = _fscarousel_baseurl();
	$data = $_POST['data'];
	$setting_name = $data['setting_name'];
	$file_data = base64_decode($data['file']);
	$upload_dir = wp_upload_dir();
	$destination = $upload_dir['path'] . '/' . $data['file_name'];
	if (file_exists($destination)) {
		@unlink($destination);
	}

	file_put_contents($destination, $file_data);
	$destinationURL = str_replace($baseurl, '', $upload_dir['url']) . '/' . $data['file_name'];
	$updated = fscarousel_update_settings(array($setting_name => $destinationURL));

	wp_send_json(array(
		'updated' => $updated,
	));
}

function fscarousel_upload_image_ajax() {
	$baseurl = _fscarousel_baseurl();
	$data = $_POST['data'];
	// base64 file data
	$file_data = base64_decode($data['file']);
	$upload_dir = wp_upload_dir();
	$destination = $upload_dir['path'] . '/' . $data['file_name'];

	$chapter_id = $data['chapter_id'];
	$image_delta = $data['delta'];
	$caption = $data['caption'];
	$title = $data['title'];
	$weight = $data['weight'];

	// Delete file and entry in DB
	if (file_exists($destination)) {
		@unlink($destination);
	}
	fscarousel_unregister_image($chapter_id, $image_delta);
	
	file_put_contents($destination, $file_data);
	$destinationURL = str_replace($baseurl, '', $upload_dir['url']) . '/' . $data['file_name'];
	$inserted = fscarousel_register_image($destination, $destinationURL, $chapter_id, $image_delta, $caption, $title, $weight);

	//file_put_contents()
	wp_send_json(array(
		'inserted' => $inserted,
	));
}

function fscarousel_get_image_url_ajax() {
	$data = $_POST['data'];
	$chapter_id = $data['chapter_id'];
	$delta = $data['delta'];
	$image = fscarousel_get_chapter_image($chapter_id, $delta);
	wp_send_json(array('url' => $image->fileurl));
}


/* End Ajax */

/**
 * Image Management
 */
function fscarousel_register_image($image_path, $destinationURL, $chapter_id, $image_delta, $caption = '', $title = '', $weight = 0) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_images';
	return $wpdb->query(
		$wpdb->prepare("INSERT INTO $table_name (chapter_id, delta, weight, filepath, fileurl, caption, title, created) VALUES (%d, %d, %d, %s, %s, %s, %s, %s)", $chapter_id, $image_delta, $weight, $image_path, $destinationURL, $caption, $title, time())
	);
}

function fscarousel_unregister_image($chapter_id, $image_delta) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_images';
	$image = fscarousel_get_chapter_image($chapter_id, $image_delta);
	if ($image && file_exists($image->filepath)) {
		@unlink($image->filepath);
	}
	return $wpdb->delete($table_name, array('chapter_id' => $chapter_id, 'delta' => $image_delta), array('%d', '%d'));
}

/** 
 * Chapter Properties get/set
 */
function fscarousel_get_chapter($chapter_id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_chapters';
	$query = $wpdb->prepare("SELECT * FROM $table_name WHERE chapter_id = %d", $chapter_id);
	return $wpdb->get_row($query);
}

function fscarousel_get_chapters() {
	global $wpdb;
	if (!function_exists('fscarousel_sortByWeight')) {
		function fscarousel_sortByWeight($a, $b) {
			if ($a->weight == $b->weight) {
				return 0;
			}
			return ($a->weight < $b->weight) ? -1 : 1;
		}
	}
	$table_name = $wpdb->prefix . 'fscarousel_chapters';
	$results = $wpdb->get_results("SELECT * FROM $table_name");
	uasort($results, 'fscarousel_sortByWeight');
	return $results;
}

function fscarousel_get_chapter_count() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_chapters';
	return intval($wpdb->get_var("SELECT COUNT(chapter_id) FROM $table_name"));
}

function fscarousel_get_title() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_settings';
	$title = $wpdb->get_var("SELECT value FROM $table_name WHERE name = 'title'");
	return $title;
}

function fscarousel_next_chapter_id() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_chapters';
	$max_id = $wpdb->get_var("SELECT MAX(chapter_id) FROM $table_name");
	return $max_id + 1;
}

function fscarousel_next_chapter_weight() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_chapters';
	$max_id = $wpdb->get_var("SELECT MAX(weight) FROM $table_name");
	return $max_id + 10;
}

function fscarousel_get_chapter_images($chapter_id) {
	global $wpdb;
	if (!function_exists('fscarousel_sortByWeight')) {
		function fscarousel_sortByWeight($a, $b) {
			if ($a->weight == $b->weight) {
				return 0;
			}
			return ($a->weight < $b->weight) ? -1 : 1;
		}
	}
	$table_name = $wpdb->prefix . 'fscarousel_images';
	$results = $wpdb->get_results(
		$wpdb->prepare("SELECT * FROM $table_name WHERE chapter_id = %d", $chapter_id)
	);
	uasort($results, 'fscarousel_sortByWeight');
	foreach($results as &$result) {
		$path = $result->filepath;
		$dir = dirname($path);
		$basename = basename($path);
		$thumb = $dir . '/thumbs//' . $basename;
		$urlPath = str_replace($basename, '', $result->fileurl);
		if (file_exists($thumb)) {
			$result->thumb = _fscarousel_baseurl() . $urlPath . 'thumbs/' . $basename;
		}
	}
	return $results;
}

function fscarousel_get_chapter_image($chapter_id, $delta) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_images';
	$query = $wpdb->prepare("SELECT * FROM $table_name WHERE chapter_id = %d and delta = %d", $chapter_id, $delta);
	$result = $wpdb->get_row($query);
	$path = $result->filepath;
	$dir = dirname($path);
	$basename = basename($path);
	$thumb = $dir . '//thumbs/thumb_' . $basename;
	$urlPath = str_replace($basename, '', $result->fileurl);
	if (file_exists($thumb)) {
		$result->thumb = _fscarousel_baseurl() . '/' . $urlPath . 'thumbs/thumb_' . $basename;
	}
	return $result;
}

/**
 * Settings Get/Set
 */
function fscarousel_update_settings($settings) {
	//log_error('Update settings: ' . print_r($settings, TRUE));
	global $wpdb;
	$table_name = $wpdb->prefix . 'fscarousel_settings';
	$updated = 0;
	foreach($settings as $name => $value) {
		if (is_array($value)) {
			foreach($value as $val_key => $val_val) {
				$key = $name . '[' . $val_key . ']';
				$current = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE name = %s",$key));
				if ($current) {
					$did_update = $wpdb->query(
						$wpdb->prepare("UPDATE $table_name SET value = %s WHERE name = %s", $val_val, $key)
					);
					if ($did_update) {
						$updated++;
					}
				}
				else {
					$did_update = $wpdb->query(
						$wpdb->prepare("INSERT INTO $table_name (name, value) VALUES (%s,%s)", $key, $val_val)
					);
					if ($did_update) {
						$updated++;
					}
				}
			}
		}
		else if (!empty($value)) {
			$current = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE name = %s",$name));
			if ($current) {
				$did_update = $wpdb->query(
					$wpdb->prepare("UPDATE $table_name SET value = %s WHERE name = %s", $value, $name)
				);
				if ($did_update) {
					$updated++;
				}
			}
			else {
				$did_update = $wpdb->query(
					$wpdb->prepare("INSERT INTO $table_name (name, value) VALUES (%s,%s)", $name, $value)
				);
				if ($did_update) {
					$updated++;
				}
			}
		}
	}
	return $updated;
}

function fscarousel_get_settings() {
	global $wpdb;
	$defaults = _fscarousel_settings_defaults();
	$baseurl = _fscarousel_baseurl();

	$table_name = $wpdb->prefix . 'fscarousel_settings';
	$results = $wpdb->get_results("SELECT * FROM $table_name");
	$settings = $defaults;
	if ($results) {
		foreach($results as $result) {
			switch($result->name) {
				case 'logo':
				case 'favicon':
					if ($result->value) {
						$settings[$result->name] = $baseurl . $result->value;
					}
				break;
				default:
					if (strpos($result->name, '[') !== FALSE) {
						$exploded = explode('[',$result->name);
						$main_key = $exploded[0];
						$sub_key = str_replace(']','',$exploded[1]);
						if (!isset($settings[$main_key])) {
							$settings[$main_key] = array();
						}
						$settings[$main_key][$sub_key] = $result->value;
					}
					else {
						$settings[$result->name] = $result->value;
					}
			}
		}
	}
	return $settings;
}

/**
 * Activation hook
 */
function fscarousel_activate() {
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$charset_collate = $wpdb->get_charset_collate();

	$table_name = $wpdb->prefix . 'fscarousel_chapters';

	$sql = "CREATE TABLE $table_name (
	  chapter_id mediumint(9) NOT NULL AUTO_INCREMENT,
	  title tinytext NOT NULL,
	  weight mediumint(9) NOT NULL DEFAULT 0,
	  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  UNIQUE KEY chapter_id (chapter_id)
	) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . 'fscarousel_images';

	$sql = "CREATE TABLE $table_name (
	  image_id mediumint(9) NOT NULL AUTO_INCREMENT,
	  chapter_id mediumint(9) NOT NULL,
	  delta mediumint(9) NOT NULL DEFAULT 0,
	  weight mediumint(9) NOT NULL DEFAULT 0,
	  filepath varchar(255) NOT NULL,
	  fileurl varchar(255) NOT NULL,
	  title varchar(255) NOT NULL DEFAULT '',
	  caption longtext DEFAULT '',
	  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  UNIQUE KEY image_id (image_id)
	) $charset_collate;";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . 'fscarousel_settings';

	$sql = "CREATE TABLE $table_name (
	  setting_id mediumint(9) NOT NULL AUTO_INCREMENT,
	  name varchar(255) NOT NULL,
	  value varchar(255) NOT NULL,
	  UNIQUE KEY setting_id (setting_id)
	) $charset_collate;";
	dbDelta( $sql );

	// Schedule our cron task
	if ( ! wp_next_scheduled( 'fscarousel_make_thumbs' ) ) {
	  wp_schedule_event( time(), 'hourly', 'fscarousel_make_thumbs' );
	}
}

register_activation_hook( __FILE__, 'fscarousel_activate' );
register_deactivation_hook(__FILE__, 'fscarousel_deactivate');

function fscarousel_deactivate() {
	// Deschedule our cron task
	wp_clear_scheduled_hook('fscarousel_make_thumbs');
}

/**
 * Helpers
 */
function _fscarousel_baseurl() {
	$baseurl = 'http';
	if ($_SERVER['HTTPS'] == 'on') {
		$baseurl .= 's';
	}
	$baseurl .= '://' . $_SERVER['HTTP_HOST'] . '/';
	return $baseurl;
}

function _fscarousel_settings_easing_options() {
	return array(
		'Swing' => 'swing',
		'Ease Out Quadratic' => 'easeOutQuad',
		'Ease In Quadratic' => 'easeInQuad',
		'Ease In/Out Quadratic' => 'easeInOutQuad',
		'Ease Out Cubic' => 'easeOutCubic',
		'Ease In Cubic' => 'easeInCubic',
		'Ease In/Out Cubic' => 'easeInOutCubic',
	);
}

function _fscarousel_settings_animation_categories() {
	return array( 
		'slideAnimationOptions' => 'Default Slide Animation Options',
   		'infoBoxAnimationOptions' => 'Default Infobox Animation Options',
   		'navigationAnimationOptions' => 'Default Navigation Animation Options'
	);
}

function _fscarousel_settings_effect_options() {
	return array(
		'Fade' => 'fade',
		'Blind' => 'blind',
		'Slide' => 'slide',
	);
}

function _fscarousel_settings_defaults() {
	return array(
		'display_method' => 'stacked',
		'page_title' => 'Full Screen Carousel Page',
		'carousel_title' => 'Full Screen Carousel',
		'plugins_url' => plugins_url('', __FILE__),
	);
}


/**
 * Alter the decorators (label and tags) for wp-forms elements
 */
add_filter( 'wp_form_default_decorators', 'filter_file_decorators', 10, 2 );
function filter_file_decorators( $decorators, $element ) {
	switch($element->type) {
		case 'file':
			$decorators = array(
				'WP_Form_Decorator_Errors' => array(),
				'WP_Form_Decorator_Label' => array(),
			    'WP_Form_Decorator_HtmlTag' => array('tag' => 'div', 'attributes' => array( 'class' => 'file-wrapper' )),
			);
		break;
		case 'button':
			$decorators = array(
			     'WP_Form_Decorator_HtmlTag' => array('tag' => 'div', 'attributes' => array( 'class' => 'button-wrapper' )),
			 );
		break;
		case 'submit':
			$decorators = array(
			     'WP_Form_Decorator_HtmlTag' => array('tag' => 'div', 'attributes' => array( 'class' => 'submit-wrapper' )),
			 );
		break;
		break;
		case 'textarea':
			$decorators['WP_Form_Decorator_HtmlTag']['attributes'] = array('class' => 'textarea-wrapper');		
		break;
		case 'fieldset':
		case 'hidden':
		break;
		default:
			$decorators['WP_Form_Decorator_HtmlTag']['attributes'] = array('class' => 'form-item');
	}
    return $decorators;
}

/**
 * Thumbnail generation via Cron
 */
add_action( 'fscarousel_make_thumbs', 'fscarousel_make_thumbs_task' );

function fscarousel_make_thumbs_task() {
  // Get chapters
  $chapters = fscarousel_get_chapters();
  foreach($chapters as $chapter) {
  	$chapter_id = $chapter->chapter_id;
  	// Get chapter images
  	$chapter_images = fscarousel_get_chapter_images($chapter_id);
  	if ($chapter_images) {
  		// Iterate over images and create a thumbnail in subdirectory
  		foreach($chapter_images as $image) {
  			$editor = wp_get_image_editor($image->filepath);
  			if ( !is_wp_error($editor)) {
  				$size = $editor->get_size();

  				$dirname = dirname($image->filepath);
  				$thumb_path = $dirname . '/thumbs//' . basename($image->filepath);

  				$editor->resize(118, 177, true);
  				$saved = $editor->save($thumb_path);	
  			}
  		}
  	}
  }
}