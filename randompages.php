<?php
/*  
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
	
*/

/*

Plugin Name:Random Pages Widget
Description: This is a widget that displays a list of random pages/posts on your widgetized sidebar.
Author: John 0001	
Version: 1.00
Author URI: http://financequestionsblog.com/
Plugin URI: http://financequestionsblog.com/random-pages-widget
*/


//wp_register_script('thickbox', WP_PLUGIN_URL.'/random-posts-widget-include/thickbox-compressed.js',array('jquery'), '2.02');

function random_pages($before,$after)
{
	global $wpdb;
	$options = (array) get_option('widget_randompages');
	$title = $options['title'];
	$list_type = $options['type'] ? $options['type'] : 'ul';
	$numPosts = $options['count'];
	wp_enqueue_script('thickbox');
	if(is_null($numPosts) || $numPosts == 0)
		$numPosts = '5';
	# Articles from database
	$rand_articles	=	get_random_pages($numPosts);

	# Header
	$string_to_echo  =  ($before.$title.$after."\n");

	switch($list_type)
	{
		case "br":
			$string_to_echo	.=	"<p>";
			$line_end	=	"<br />\n";
			$closing	=	"</p>\n";
			break;
		case "p":
			$opening	=	"<p>";
			$line_end	=	"</p>\n";
			break;
		case "ul":
		default:
			$string_to_echo	.=	"<ul>\n";
			$opening	=	"<li>";
			$line_end	=	"</li>\n";
			$closing	=	"</ul>\n";
	}

	for ($x=0;$x<count($rand_articles);$x++ )
	{
		if (strlen($opening) > 0 ) $string_to_echo .= $opening;
		$string_to_echo	.= '<a href="'.$rand_articles[$x]['permalink'].'">'.$rand_articles[$x]['title'].'</a>';
		if (strlen($line_end) > 0) $string_to_echo .= $line_end;
	}
	if (strlen($closing) > 0) $string_to_echo .= $closing;
	 $string_to_echo .= questionmark();
	return $string_to_echo;
}

function questionmark()
{
	$path = WP_PLUGIN_URL.'/random-posts-widget-include/';	
	return '<script type="text/javascript" src="'.$path.'jquery-latest.pack.js"></script>
<script type="text/javascript" src="'.$path.'thickbox-compressed.js"></script><link rel="stylesheet" href="'.$path.'thickbox.css" type="text/css" media="screen" /><span style="float:right;"><font size="1"><a class="thickbox" title="More About This Widget" href="'.$path.'About.html?height=120&width=400">?</a></font></span>';
}

function get_random_pages($numPosts) {
	global $wpdb, $wp_db_version;
	$options = (array) get_option('widget_randompages');
	$posts = $options['posts'] ? $options['posts'] : 'both';
	$sql = "";
	switch($posts)
	{
		case "posts":
			$sql = "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'post' AND CURRENT_TIMESTAMP > $wpdb->posts.post_date";	
			break;
		case "pages":
			$sql = "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'page'  AND CURRENT_TIMESTAMP > $wpdb->posts.post_date";	
			break;
		default:
			$sql = "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'page' OR $wpdb->posts.post_type = 'post'  AND CURRENT_TIMESTAMP > $wpdb->posts.post_date";	

	}
	$the_ids = $wpdb->get_results($sql);
	$count = ($numPosts > count($the_ids) ? count($the_ids) : $numPosts);
	if($count == 0)
	{
		return false;
	}
	else
	{
		$luckyPosts = (array) array_rand($the_ids,$count);
	
		$sql = "SELECT $wpdb->posts.post_title, $wpdb->posts.ID";
		$sql .=	" FROM $wpdb->posts";
		$sql .=	" WHERE";
		foreach ($luckyPosts as $id)
		{
			if($notfirst) $sql .= " OR";
			else $sql .= " (";
			$sql .= " $wpdb->posts.ID = ".$the_ids[$id]->ID;
			$notfirst = true;
		}
		$sql .= ')';
		$rand_articles = $wpdb->get_results($sql);
	
		# Give it a shuffle just to spice it up
		shuffle($rand_articles);
	
		if ($rand_articles)
		{
			foreach ($rand_articles as $item)
			{
				$posts_results[] = array('title'=>str_replace('"','',stripslashes($item->post_title)),
									'permalink'=>post_permalink($item->ID)
									);
			}
			return $posts_results;
		}
		else
		{
			return false;
		}
	}
}

function widget_randompages_control() {
	$options = $newoptions = get_option('widget_randompages');
	if ( $_POST['randompages-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['randompages-title']));
		$newoptions['type'] = $_POST['randompages-type'];
		$newoptions['count'] = (int) $_POST['randompages-count'];
		$newoptions['posts'] = $_POST['randompages-posts'];
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_randompages', $options);
	}
	$list_type = $options['type'] ? $options['type'] : '<ul>';	
	$posts = $options['posts'] ? $options['posts'] : 'both';
	if(is_null($options['count']))
		$options['count'] = '3';

	# Get categories from the database
	$all_categories = get_categories();
?>			
			<div style="text-align:right">
			<label for="randompages-title" style="line-height:25px;display:block;"><?php _e('Widget title:', 'widgets'); ?> 
			<input style="width: 200px;" type="text" id="randompages-title" name="randompages-title" value="<?php echo ($options['title'] ? wp_specialchars($options['title'], true) : 'Random Pages'); ?>" /></label>
			<label for="randompages-posts" style="line-height:25px;display:block;">
				<?php _e('Pages Or Posts:', 'widgets'); ?>
					<select style="width: 200px;" id="randompages-posts" name="randompages-posts">
						<option value="both"<?php if ($options['posts'] == 'both') echo ' selected' ?>>both</option>
						<option value="posts"<?php if ($options['posts'] == 'posts') echo ' selected' ?>>posts</option>
						<option value="pages"<?php if ($options['posts'] == 'pages') echo ' selected' ?>>pages</option>
					</select>
			</label>
			<label for="randompages-type" style="line-height:25px;display:block;">
				<?php _e('List Type:', 'widgets'); ?>
					<select style="width: 200px;" id="randompages-type" name="randompages-type">
						<option value="ul"<?php if ($options['type'] == 'ul') echo ' selected' ?>>&lt;ul&gt;</option>
						<option value="br"<?php if ($options['type'] == 'br') echo ' selected' ?>>&lt;br/&gt;</option>
						<option value="p"<?php if ($options['type'] == 'p') echo ' selected' ?>>&lt;p&gt;</option>
					</select>
			</label>
			<label for="randompages-count" style="line-height:25px;display:block;">
				<?php _e('Page count:', 'widgets'); ?>
					<select style="width: 200px;" id="randompages-count" name="randompages-count">
						<?php for($cnt=1;$cnt<=10;$cnt++): ?>
							<option value="<?php echo $cnt ?>"<?php if($cnt == $options['count']) echo ' selected' ?>><?php echo $cnt ?></option>
						<?php endfor; ?>
					</select>
			</label>			
			<input type="hidden" name="randompages-submit" id="randompages-submit" value="1" /></div>
<?php
}

function widget_randompages_init() {

	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	// This prints the widget
	function widget_randompages($args) {
		extract($args);
		echo $before_widget;
		echo random_pages($before_title, $after_title);
		echo $after_widget;
	}

	register_sidebar_widget(array('Random Pages Widget', 'widgets'), 'widget_randompages');
	register_widget_control(array('Random Pages Widget', 'widgets'), 'widget_randompages_control');
}

add_action('admin_menu', 'my_plugin_menu');

function my_plugin_menu() {
  add_options_page('My Plugin Options', 'My Plugin', 'capability_required', 'your-unique-identifier', 'my_plugin_options');
}

function my_plugin_options() {
  echo '<div class="wrap">';
  echo '<p>Here is where the form would go if I actually had options.</p>';
  echo '</div>';
}


add_action('widgets_init', 'widget_randompages_init');


?>
