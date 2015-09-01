<?php
/*
 * Template Name: FSCarousel Front End
 * Description: A full width page
 */
?>
<?php
	$settings = fscarousel_get_settings();
	$chapters = fscarousel_get_chapters();
	$chapter_names = array();
	$default_chapter_id = NULL;
	if (isset($settings['default_chapter_id'])) {
		$default_chapter_id = $settings['default_chapter_id'];
	}
	foreach($chapters as $chapter) {
		$chapter_names[$chapter->chapter_id] = $chapter->title;
	}
	$thumbs = array();
?>
<!DOCTYPE html> 
<html>
	<head>
		<title><?php echo $settings['page_title']; ?></title>
		<?php if (isset($settings['favicon']) && $settings['favicon']): ?>
			<link rel="icon" type="image/png" href="<?php print $settings['favicon']; ?>" />
		<?php endif; ?>
		<script type="text/javascript" src="<?php print $settings['plugins_url']; ?>/js/modernizr.custom.21535.js"></script>
		<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
		<link rel="stylesheet" type="text/css" href="<?php print $settings['plugins_url']; ?>/css/jquery-ui.min.css" />
		<link rel="stylesheet" type="text/css" href="<?php print $settings['plugins_url']; ?>/css/jquery-ui.structure.min.css" />		
		<link rel="stylesheet" type="text/css" href="<?php print $settings['plugins_url']; ?>/css/jquery-ui.theme.min.css" />		
		<script type="text/javascript" src="<?php print $settings['plugins_url']; ?>/js/spin.js"></script>
		<script type="text/javascript" src="<?php print $settings['plugins_url']; ?>/js/jquery.spin.js"></script>
		<script type="text/javascript" src="<?php print $settings['plugins_url']; ?>/js/jquery-ui.min.js"></script>
		<script type="text/javascript" src="<?php print $settings['plugins_url']; ?>/js/jquery.once.min.js"></script>

		<link rel="stylesheet" type="text/css" href="<?php print $settings['plugins_url']; ?>/css/fscarousel.carousel.css" />
		<script type="text/javascript" src="<?php print $settings['plugins_url']; ?>/js/fscarousel.jquery.js"></script>
	</head>
	<body>
		<div id="fscarousel_carousel" class="fscarousel-wrapper <?php print $settings['animation_method']; ?>animations">

			<header class="fscarousel-header">
				<?php if ($settings['logo']): ?>
					<div class="logo">
						<img src="<?php print $settings['logo']; ?>" />
					</div>
				<?php endif; ?>

				<div class="fscarousel-chapter-menu-wrapper">
					<div class="icon-wrapper">
						<i class="fa fa-navicon"></i>
					</div>
					<div class="titles-wrapper">
						<h3 id="fscarousel_carousel_title"><?php print $settings['carousel_title']; ?></h3>
						<h2 id="fscarousel_chapter_title"></h2>
					</div>
				</div>

				<ul id="fscarousel_chapter_menu" class="fscarousel-chapter-menu">
					<?php foreach($chapter_names as $chapter_id => $chapter_name): ?>
						<li class="fscarousel-chapter-menu-option" data-chapter-id="<?php print $chapter_id; ?>" id="chapter_select_option_<?php print $chapter_id; ?>"><a href="#"><?php print $chapter_name; ?></a></li>
					<?php endforeach; ?>
				</ul>

				<div class="fscarousel-header-buttons">
					<div class="fscarousel-button fscarousel-button-exit">
						<i class="fa fa-close"></i>
					</div>
					<div class="fscarousel-button fscarousel-button-info">
						<i class="fa fa-info"></i>
					</div>
				</div>
			</header>

			<div id="fscarousel_infobox" class="fscarousel-infobox"></div>

			<div id="fscarousel_canvas">
				<?php foreach($chapters as $chapter): ?>
					<?php $chapter_id = $chapter->chapter_id; ?>
					<?php $thumbs[$chapter_id] = array('thumbs' => array(), 'title' => $chapter->title); ?>
					<div id="fscarousel_chapter-<?php print $chapter_id; ?>" data-chapter-id="<?php print $chapter_id; ?>" class="fscarousel-chapter-images">
						<?php $images = fscarousel_get_chapter_images($chapter_id); ?>
						<?php foreach($images as $image): ?>
							<div style="background-image:url('<?php print $image->fileurl; ?>')" 
								 data-caption="<?php print $image->caption; ?>" 
								 data-title="<?php print $image->title; ?>"
								 class="fscarousel-image"
								 id="chapter_<?php print $chapter_id; ?>_image_<?php print $image->image_id; ?>"
								 data-delta="<?php print $image->delta; ?>"
								 data-image-id="<?php print $image->image_id; ?>"
							 ></div>
							 <?php
							 	// add thumbnail
							 	$thumbs[$chapter_id]['thumbs'][$image->image_id] = array('fileurl' => isset($image->thumb) ?  $image->thumb : $image->fileurl, 'caption' => $image->caption);
							 ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<footer id="fscarousel_footer">
				<div class="fscarousel-navigation-wrapper">
					<div class="fscarousel-button fscarousel-button-nav-prev">
						<i class="fa fa-angle-left"></i>
					</div>
					<div class="fscarousel-nav-canvas">
						<div class="fscarousel-nav-select">
							<?php foreach($thumbs as $chapter_id => $chapter_thumbs): ?>
								<div class="fscarousel-chapter-thumbs" id="fscarousel_chapter_thumbs_<?php print $chapter_id; ?>" data-chapter-id="<?php print $chapter_id; ?>">
									<div class="fscarousel-chapter-thumb-cover fscarousel-chapter-thumb">
										<h2 class="fscarousel-chapter-thumb-cover-title"><?php print $chapter_thumbs['title']; ?></h2>
									</div>
									<?php foreach($chapter_thumbs['thumbs'] as $image_id => $image): ?>
										<div class="fscarousel-chapter-thumb-thumb fscarousel-chapter-thumb">
											<a href="#" class="fscarousel-chapter-thumb-link">
												<img src="<?php print $image['fileurl']; ?>"
													 title="<?php print $image['caption']; ?>"
													 alt="<?php print $image['caption']; ?>"
													 class="fscarousel-chapter-thumb-img"
													 data-image-id="<?php print $image_id;  ?>"
												/>
											</a>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endforeach; ?>							
						</div>
					</div>
					<div class="fscarousel-button fscarousel-button-nav-next">
						<i class="fa fa-angle-right"></i>
					</div>
				</div>
			</footer>
			
		</div>
		<div id="preloader">
			<?php if ($settings['logo']): ?>
				<div class="logo">
					<img src="<?php print $settings['logo']; ?>" />
				</div>
			<?php endif; ?>
			<div class="loading"><div id="spinner"></div></div>
			<p>Loading...</p>
		</div>
		<script type="text/javascript">
			(function($) {
				$(document).ready(function() {
					$("#spinner").spin();
				});
				$(window).load(function() {
					var settings = <?php print json_encode($settings); ?>;
					$.extend($.fn.fscarousel.defaults, settings);
					var fscarousel = $('#fscarousel_carousel').fscarousel();
					$('#preloader').fadeOut(500);
				})
			})(jQuery);
		</script>
	</body>
</html>
