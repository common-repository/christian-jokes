<?php
/*
Plugin Name: Christian Jokes
Description: Widget that shows Christian Jokes taken from Christian-Jokes.net website.
Author: ChristianJokes
Version: 1.0
Author URI: http://www.Christian-Jokes.net
Plugin URI: http://www.Christian-Jokes.net/tools/wordpress.aspx
*/

################################################################################
################################################################################
################################################################################
#	Christian Jokes SETTINGS
#	FEEL FREE TO EDIT THIS SETTINGS
################################################################################
$cj_cache_path = ABSPATH . 'wp-content/cache/';	//	Cache path, default: wp-content/cache/
$cj_cache_file = 'cj_cache';	//	Cache file, default: cj_cache



################################################################################
################################################################################
################################################################################
#	Christian Jokes CORE
#	DO NOT EDIT ANYTHING BELOW THIS LINE!!!
################################################################################

require_once(ABSPATH . WPINC . '/rss.php');
if (!defined('MAGPIE_FETCH_TIME_OUT'))
{
	define('MAGPIE_FETCH_TIME_OUT', 2);	// 2 second timeout
}
if (!defined('MAGPIE_USE_GZIP'))
{
	define('MAGPIE_USE_GZIP', true);
}

function cj_save_data($data)
{
	global $cj_cache_path, $cj_cache_file;

	if (!$fp = @fopen($cj_cache_path . $cj_cache_file, 'w'))
	{
        echo 'Cannot open file ('.$cj_cache_path . $cj_cache_file.') Check folder permissions!';
        return '';
    }
    if (@fwrite($fp, $data) === FALSE)
	{
        echo 'Cannot write to file ('.$cj_cache_path . $cj_cache_file.') Check folder permissions!';
        return '';
    }
    if (!@fclose($fp))
	{
        echo 'Cannot close file ('.$cj_cache_path . $cj_cache_file.') Check folder permissions!';
        return '';
    }
}

function cj_read_cache($widgetData)
{
	global $cj_cache_path, $cj_cache_file;

	$data = '';

	if (!$data = @file_get_contents($cj_cache_path . $cj_cache_file))
	{
		echo 'Cannot read file ('.$cj_cache_path . $cj_cache_file.') Check folder permissions!';
        return '';
	}

	$cjRSS = new MagpieRSS($data);

	// if RSS parsed successfully
	if ($cjRSS)
	{
		$outputLines = '';
		//	Loop over items, and add some extra html :)
		foreach($cjRSS->items AS $value)
		{
			   $breakLineAddon = '<br />';
		

			
			
				$outputLines .= '<b>'. $value['title'].'</b><br />';
				$outputLines .= str_replace(':</b>', ':</b>'.$breakLineAddon, $value['description']);
	
			
		}
		$outputLines .= '<br/><small><a href="http://www.Christian-Jokes.net">Christian-Jokes.net</a></small>';

		return $outputLines;
	}
	else
	{
		$errormsg = 'Failed to parse RSS file.';

		if ($cjRSS)
		{
			$errormsg .= ' (' . $cjRSS->ERROR . ')';
		}

		return false;
	}
}

function cj_fetch_data($widgetData)
{
	global $wp_version;

	//	Set user specified data
	if (isset($widgetData['category']) && $widgetData['category'] == '2')
	{
		$cjType = 'latest';
	}
	else if (isset($widgetData['category']) && $widgetData['category'] == '1')
	{
		$cjType = 'random';
	}
	
	else
	{
		$cjType = 'random';
	}

	

	if ($wp_version >= '2.7')
	{
		$client = wp_remote_get('http://www.christian-jokes.net/widgets/GetJokesWordpress.aspx?type='.$cjType);
	}
	else
	{
		//	Fetch data
		$client = new Snoopy();
		$client->agent = MAGPIE_USER_AGENT;
		$client->read_timeout = MAGPIE_FETCH_TIME_OUT;
		$client->use_gzip = MAGPIE_USE_GZIP;

		@$client->fetch('http://www.christian-jokes.net/widgets/GetJokesWordpress.aspx?type='.$cjType);
	}

	return $client;
}

function cj_display($widgetData)
{
	global $cj_cache_path, $cj_cache_file, $wp_version;

	$htmlOutput = '';

	//	First let's check if cache file exist
	if (file_exists($cj_cache_path . $cj_cache_file) && filesize($cj_cache_path . $cj_cache_file) > 0)
	{
		if ($widgetData['cachetime'] > 0)
		{
			$cjCacheTime = $widgetData['cachetime'];
		}
		else
		{
			$cjCacheTime = 300;
		}

		//	File does exist, so let's check if its expired
		if ((time() - $cjCacheTime) > filemtime($cj_cache_path . $cj_cache_file))
		{
			//	Since cache has expired, let's fetch new data
			$htmlOutput = cj_fetch_data($widgetData);

			if ($wp_version >= '2.7')
			{
				if ($htmlOutput['response']['code'] == 200)
				{
					//	Before output, let's save new data to cache
					cj_save_data($htmlOutput['body']);
				}
			}
			else
			{
				if ($htmlOutput->status == '200')
				{
					//	Before output, let's save new data to cache
					cj_save_data($htmlOutput->results);
				}
			}

			return cj_read_cache($widgetData);
		}

		return cj_read_cache($widgetData);
	}
	else
	{
		//	No file found, someone deleted it or first time widget usage :)
		//	Let's create new file with fresh content ;)
		$htmlOutput = cj_fetch_data($widgetData);

		if ($wp_version >= '2.7')
		{
			if ($htmlOutput['response']['code'] == 200)
			{
				//	Before output, let's save new data to cache
				cj_save_data($htmlOutput['body']);
			}
		}
		else
		{
			if ($htmlOutput->status == '200')
			{
				//	Before output, let's save new data to cache
				cj_save_data($htmlOutput->results);
			}
		}

		return cj_read_cache($widgetData);
	}
}

function widget_christianjokes($args, $widget_args = 1)
{
	extract( $args, EXTR_SKIP );
	if ( is_numeric($widget_args) )
		$widget_args = array( 'number' => $widget_args );
	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
	extract( $widget_args, EXTR_SKIP );

	$options = get_option('widget_christianjokes');
	if ( !isset($options[$number]) )
		return;

	$title = $options[$number]['title'];

	echo $before_widget;

	if ( !empty( $title ) ) { echo $before_title . $title . $after_title; }

	//	Display quote(s)
	echo cj_display($options[$number]);

	echo $after_widget;
}

function widget_christianjokes_control($widget_args)
{
	global $wp_registered_widgets, $cj_cache_path, $cj_cache_file;
	static $updated = false;

	if ( is_numeric($widget_args) )
		$widget_args = array( 'number' => $widget_args );
	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
	extract( $widget_args, EXTR_SKIP );

	$options = get_option('widget_christianjokes');
	if ( !is_array($options) )
		$options = array();

	if ( !$updated && !empty($_POST['sidebar']) )
	{
		$sidebar = (string) $_POST['sidebar'];

		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( isset($sidebars_widgets[$sidebar]) )
			$this_sidebar =& $sidebars_widgets[$sidebar];
		else
			$this_sidebar = array();

		foreach ( $this_sidebar as $_widget_id )
		{
			if ( 'widget_christianjokes' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) )
			{
				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
				unset($options[$widget_number]);
			}
		}

		foreach ( (array) $_POST['widget-christianjokes'] as $widget_number => $widget_value )
		{
			$title = isset($widget_value['title']) ? trim(stripslashes($widget_value['title'])) : '';
			$category = isset($widget_value['category']) ? $widget_value['category'] : '1';
			
			
			
			$cachetime = isset($widget_value['cachetime']) ? $widget_value['cachetime'] : '300';
			
			$options[$widget_number] = compact( 'title', 'category',  'cachetime' );
		}

		//	Check if cache file exist, if so delete the file
		if ( file_exists($cj_cache_path . $cj_cache_file) )
		{
			@unlink($cj_cache_path . $cj_cache_file);
		}

		update_option('widget_christianjokes', $options);
		$updated = true;
	}

	if ( -1 == $number )
	{
		$title = 'Christian Jokes';
		$category = '1';
	
		
		$number = '%i%';
		
		$cachetime = '300';
		
	}
	else
	{
		$title = attribute_escape($options[$number]['title']);
		$category = attribute_escape($options[$number]['category']);
		
		
		
		$cachetime = attribute_escape($options[$number]['cachetime']);
		
	}
?>
		<p>
			<label for="christian-title-<?php echo $number; ?>">
				<?php _e( 'Title' ); ?>
				<input class="widefat" id="christianjokes-title-<?php echo $number; ?>" name="widget-christianjokes[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>
		<p>
			<label for="christianjokes-category-<?php echo $number; ?>"><?php _e('Select category'); ?>
				<select id="christianjokes-category-<?php echo $number; ?>" name="widget-christianjokes[<?php echo $number; ?>][category]">
					
					<option value="1"<?php if ($category == '1') echo ' selected="selected"'; ?>>Random Joke</option>
					<option value="2"<?php if ($category == '2') echo ' selected="selected"'; ?>>Latest Joke</option>
					
				</select>
			</label>
		</p>
		
		
		
		
		<p>
			<label for="christianjokes-cachetime-<?php echo $number; ?>">
				<?php _e( 'Cache time in seconds' ); ?>
				<input class="widefat" id="christianjokes-cachetime-<?php echo $number; ?>" name="widget-christianjokes[<?php echo $number; ?>][cachetime]" type="text" value="<?php echo $cachetime; ?>" />
			</label>
		</p>
		<p>
			<input type="hidden" id="christianjokes-submit-<?php echo $number; ?>" name="christianjokes-submit-<?php echo $number; ?>" value="1" />
		</p>
<?php
}

function widget_christianjokes_register()
{

	// Check for the required API functions
	if ( !function_exists('wp_register_sidebar_widget') || !function_exists('wp_register_widget_control') )
		return;

	if ( !$options = get_option('widget_christianjokes') )
		$options = array();
	$widget_ops = array('classname' => 'widget_christianjokes', 'description' => __('Clean Christian Jokes from christian-jokes.net'));
	$control_ops = array('width' => 460, 'height' => 350, 'id_base' => 'christianjokes');
	$name = __('Christian Jokes');

	$id = false;
	foreach ( array_keys($options) as $o )
	{
		// Old widgets can have null values for some reason
		if ( !isset($options[$o]['title']) || !isset($options[$o]['category']) ||  !isset($options[$o]['cachetime']) )
			continue;
		$id = "christianjokes-$o"; // Never never never translate an id
		wp_register_sidebar_widget($id, $name, 'widget_christianjokes', $widget_ops, array( 'number' => $o ));
		wp_register_widget_control($id, $name, 'widget_christianjokes_control', $control_ops, array( 'number' => $o ));
	}

	// If there are none, we register the widget's existance with a generic template
	if ( !$id )
	{
		wp_register_sidebar_widget( 'christianjokes-1', $name, 'widget_christianjokes', $widget_ops, array( 'number' => -1 ) );
		wp_register_widget_control( 'christianjokes-1', $name, 'widget_christianjokes_control', $control_ops, array( 'number' => -1 ) );
	}

}

add_action( 'widgets_init', 'widget_christianjokes_register' );

?>