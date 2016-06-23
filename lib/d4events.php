<?php

/*------------------------------------------------------
----------------------Admin Elements--------------------
------------------------------------------------------*/

//add settings menu and page
add_action('admin_menu', 'd4events_register_settings_page');
function d4events_register_settings_page() {
    add_submenu_page(
        'edit.php?post_type=events',
        __( 'Settings', 'textdomain' ),
        __( 'Settings', 'textdomain' ),
        'manage_options',
        'd4events-settings',
        'd4events_settings_page_callback'
    );
}

// display the admin options page
function d4events_settings_page_callback() {
	
	?>
		<div>
		<h2>Events Settings</h2>
		Add API credentials from <a target="_blank" href="https://console.developers.google.com/apis/credentials">https://console.developers.google.com/apis/credentials</a>
		<form action="options.php" method="post">
		<?php settings_fields('d4events_options'); ?>
		<?php do_settings_sections('d4events'); ?>
		 
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form></div>
	<?php
}

// add the admin settings and such
add_action('admin_init', 'd4events_admin_init');
function d4events_admin_init(){
	register_setting( 'd4events_options', 'd4events_options' );
	add_settings_section('d4events_main', 'Application Settings', 'd4events_section_text', 'd4events');
	add_settings_field('api_key', 'API Key', 'events_api_key', 'd4events', 'd4events_main');
}

function d4events_section_text(){}

function events_api_key() {
	$options = get_option('d4events_options');
	echo "<input id='events_api_key' name='d4events_options[api_key]' size='40' type='text' value='{$options['api_key']}' />";
}

$options = get_option('d4events_options');
$api_key = $options['api_key'];

//Register style sheets and scripts
function d4events_admin_elements() {
    wp_enqueue_style('d4events-admin-theme', plugins_url('../css/d4events-admin.css', __FILE__));
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-custom', plugins_url( '../css/jquery-ui-custom.css' , __FILE__ ) );
    wp_register_script( 'd4events-admin', plugins_url( '../js/d4events-admin.js' , __FILE__ ), array( 'jquery' ), 'v20131005', true );
	wp_enqueue_script('d4events-admin');
	wp_register_script( 'd4places-lib', 'https://maps.googleapis.com/maps/api/js?key='.$api_key.'&libraries=places');
	wp_enqueue_script('d4places-lib');
}
add_action('admin_enqueue_scripts', 'd4events_admin_elements');
add_action('login_enqueue_scripts', 'd4events_admin_elements');


// Register Custom Post Type
function events() {

	$labels = array(
		'name'                  => _x( 'Events', 'Post Type General Name', 'events' ),
		'singular_name'         => _x( 'Event', 'Post Type Singular Name', 'events' ),
		'menu_name'             => __( 'Events', 'events' ),
		'name_admin_bar'        => __( 'Event', 'events' ),
		'archives'              => __( 'Event Archives', 'events' ),
		'parent_item_colon'     => __( 'Parent Event:', 'events' ),
		'all_items'             => __( 'All Events', 'events' ),
		'add_new_item'          => __( 'Add New Event', 'events' ),
		'add_new'               => __( 'Add New', 'events' ),
		'new_item'              => __( 'New Event', 'events' ),
		'edit_item'             => __( 'Edit Event', 'events' ),
		'update_item'           => __( 'Update Event', 'events' ),
		'view_item'             => __( 'View Event', 'events' ),
		'search_items'          => __( 'Search Events', 'events' ),
		'not_found'             => __( 'Not found', 'events' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'events' ),
		'featured_image'        => __( 'Featured Image', 'events' ),
		'set_featured_image'    => __( 'Set featured image', 'events' ),
		'remove_featured_image' => __( 'Remove featured image', 'events' ),
		'use_featured_image'    => __( 'Use as featured image', 'events' ),
		'insert_into_item'      => __( 'Insert into item', 'events' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'events' ),
		'items_list'            => __( 'Items list', 'events' ),
		'items_list_navigation' => __( 'Items list navigation', 'events' ),
		'filter_items_list'     => __( 'Filter items list', 'events' ),
	);
	$args = array(
		'label'                 => __( 'Event', 'events' ),
		'description'           => __( 'Events', 'events' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'excerpt', 'revisions','custom-fields' ),
		'taxonomies'            => array( 'category', 'post_tag' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,		
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
		'menu_icon'				=> 'dashicons-calendar-alt',
	);
	register_post_type( 'events', $args );

}
add_action( 'init', 'events', 0 );

function d4_events_timezone_list($postid) {
	$current_offset = get_option('gmt_offset');
	$tzstring = get_post_meta( $postid, 'd4events_timezone', true );
	if ($tzstring == '') {
		$tzstring = get_option('timezone_string');
		$check_zone_info = true;

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( false !== strpos($tzstring,'Etc/GMT') )
			$tzstring = '';

		if ( empty($tzstring) ) { // Create a UTC+- zone if no timezone string exists
			$check_zone_info = false;
			if ( 0 == $current_offset )
				$tzstring = 'UTC+0';
			elseif ($current_offset < 0)
				$tzstring = 'UTC' . $current_offset;
			else
				$tzstring = 'UTC+' . $current_offset;
		}
	}

	$output = '<select data-id="'.$postid.'" data-selected="'.$tzstring.'" id="d4events_timezone" name="d4events_timezone" aria-describedby="timezone-description">';
	$output .= wp_timezone_choice($tzstring);
	$output .= '</select>';

	return $output;
}

// Add the Meta Box
function add_d4events_meta_box() {
    add_meta_box(
        'd4events_meta_box', // $id
        'Event Details', // $title 
        'show_d4events_meta_box', // $callback
        'events', // $post_type
        'normal', // $context
        'high'); // $priority
}
add_action('add_meta_boxes', 'add_d4events_meta_box');

// Field Array

$frequency_options = array('Weekly','Monthly');
$frequency_meta_array = array();
foreach ($frequency_options as $frequency) {
           $frequency_meta_array[$frequency] = array (
                'label' => $frequency,
                'value' => $frequency
            );
}

$days_options = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
$days_meta_array = array();
foreach ($days_options as $days) {
           $days_meta_array[$days] = array (
                'label' => $days,
                'value' => $days
            );
}

$prefix = 'd4events_';
$d4events_meta_fields = array(
	array(
	    'label' => 'Start Date',
	    'desc'  => 'Start Date',	    
	    'id'    => $prefix.'start_date',
	    'type'  => 'date'
	),
	array(
        'label'=> 'Start Time',
        'desc'  => 'Start Time',
        'placeholder' => '00:00am or All Day',
        'id'    => $prefix.'start_time',
        'type'  => 'text'
    ),
	array(
	    'label' => 'End Date',
	    'desc'  => 'End Date',
	    'id'    => $prefix.'end_date',
	    'type'  => 'date'
	),
    array(
        'label'=> 'End Time',
        'desc'  => 'End Time',
        'placeholder' => '00:00am or All Day',
        'id'    => $prefix.'end_time',
        'type'  => 'text'
    ),
    array(
        'label'=> 'Timezone',
        'id'    => $prefix.'timezone',
        'type'  => 'timezone'
    ),
    array(
        'label'=> 'Location',
        'desc'  => 'Enter a location',
        'id'    => $prefix.'location',
        'type'  => 'text'
    ),
    array(
        'label'=> 'Registration Link',
        'desc'  => 'Copy and paste registration link here',
        'id'    => $prefix.'registration_link',
        'type'  => 'text'
    ),
    array(
        'label'=> '',
        'desc'  => 'Remove link to event details page?',
        'id'    => $prefix.'remove_link',
        'type'  => 'checkbox',
        'options' => array('Remove Link')
    ),
    array(
        'label'=> '',
        'desc'  => 'Repeating...',
        'id'    => $prefix.'repeating',
        'type'  => 'checkbox',
        'options' => array('Repeating')
    ),
    array(
        'label'=> 'Frequency',
        'desc'  => '',
        'id'    => $prefix.'frequency',
        'type'  => 'select',
        'options' => $frequency_meta_array
    ),
    array(
        'label'=> 'Repeat on',
        'desc'  => '',
        'id'    => $prefix.'repeat_days',
        'type'  => 'checkbox_group',
        'options' => $days_meta_array
    ),
    array(
        'label'=> 'Repeat by',
        'desc'  => '',
        'id'    => $prefix.'monthly_repeat_by',
        'type'  => 'radio',
        'options' => array (
	        'day_of_the_month' => array (
	            'label' => 'day of the month',
	            'value' => 'day_of_the_month'
	        ),
	        'day_of_the_week' => array (
	            'label' => 'day of the week',
	            'value' => 'day_of_the_week'
	        ),
    	),
    ),
);

// The Callback
function show_d4events_meta_box() {
global $d4events_meta_fields, $post;
// Use nonce for verification
echo '<input type="hidden" name="d4events_meta_box_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';
     
    // Begin the field table and loop
    echo '<div class="form-table">';
    foreach ($d4events_meta_fields as $field) {
        // get value of this field if it exists for this post
        $meta = get_post_meta($post->ID, $field['id'], true);
        // begin a table row with
        echo '<div class="events-meta-row row-'.$field['id'].'">
                <label for="'.$field['id'].'">'.$field['label'].'</label>
                <div class="event-meta-input">';
                switch($field['type']) {
                    // case items will go here
	                    // date
						case 'date':
							echo '<input type="text" class="datepicker" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$meta.'" size="30" />
									<br /><span class="description">'.$field['desc'].'</span>';
						break;

						// timezone
						case 'timezone':
							$postid = $post->ID;
						    echo d4_events_timezone_list($postid);
						break;

						// select
						case 'select':
						    echo '<select name="'.$field['id'].'" id="'.$field['id'].'">';
						    foreach ($field['options'] as $option) {
						        echo '<option', $meta == $option['value'] ? ' selected="selected"' : '', ' value="'.$option['value'].'">'.$option['label'].'</option>';
						    }
						    echo '</select><br /><span class="description">'.$field['desc'].'</span>';
						break;

						// text
						case 'text':
						    echo '<input type="text" placeholder="'.$field['placeholder'].'" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$meta.'" size="30" />
						        <br /><span class="description">'.$field['desc'].'</span>';
						break;

						// checkbox
						case 'checkbox':
						    echo '<input type="checkbox" name="'.$field['id'].'" id="'.$field['id'].'" ',$meta ? ' checked="checked"' : '','/>
						        <label for="'.$field['id'].'">'.$field['desc'].'</label>';
						break;

						// checkbox_group
						case 'checkbox_group':
						    foreach ($field['options'] as $option) {
						        echo '<input type="checkbox" value="'.$option['value'].'" name="'.$field['id'].'[]" id="'.$option['value'].'"',$meta && in_array($option['value'], $meta) ? ' checked="checked"' : '',' /> 
						                <label for="'.$option['value'].'">'.$option['label'].'</label><br />';
						    }
						    echo '<span class="description">'.$field['desc'].'</span>';
						break;

						// radio
						case 'radio':
						    foreach ( $field['options'] as $option ) {
						        echo '<input type="radio" name="'.$field['id'].'" id="'.$option['value'].'" value="'.$option['value'].'" ',$meta == $option['value'] ? ' checked="checked"' : '',' />
						                <label for="'.$option['value'].'">'.$option['label'].'</label><br />';
						    }
						break;
                } //end switch

                // Add special timezone selection field

       	#echo 'Repeats the third Monday of every month';
        echo '</div></div>';
    } // end foreach
    echo '</div>'; // end table
}

// Save the Data
function save_d4events_meta($post_id) {
    global $d4events_meta_fields;
     
    // verify nonce
    if (!wp_verify_nonce($_POST['d4events_meta_box_nonce'], basename(__FILE__))) 
        return $post_id;
    // check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;
    // check permissions
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id))
            return $post_id;
        } elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
    }
     
    // loop through fields and save the data
    foreach ($d4events_meta_fields as $field) {
        $old = get_post_meta($post_id, $field['id'], true);
        $new = $_POST[$field['id']];
        if ($_POST[$field['id']] == 'day_of_the_week') {
        	$repeat_interval = ceil(date('j', strtotime($_POST['d4events_start_date'])) / 7);
        	update_post_meta($post_id, 'd4events_repeat_interval', $repeat_interval);
        	$month_weekday_repeat = date('l', strtotime($_POST['d4events_start_date']));
        	update_post_meta($post_id, 'd4events_month_weekday_repeat', $month_weekday_repeat);
        }
        if ($_POST[$field['id']] == 'day_of_the_month') {
        	$repeat_interval = date('j', strtotime($_POST['d4events_start_date']));
        	update_post_meta($post_id, 'd4events_repeat_interval', $repeat_interval);
        	delete_post_meta($post_id, 'd4events_month_weekday_repeat');
        }
        if ($new && $new != $old) {
            update_post_meta($post_id, $field['id'], $new);
        } elseif ('' == $new && $old) {
            delete_post_meta($post_id, $field['id'], $old);
        }
    } // end foreach
}
add_action('save_post', 'save_d4events_meta');


/*------------------------------------------------------
----------------------Front End-------------------------
------------------------------------------------------*/

// Register style sheet and scripts.
add_action( 'wp_enqueue_scripts', 'register_d4events_elements' );

/**
 * Register style sheet.
 */
function register_d4events_elements() {
	wp_register_style( 'd4events', plugins_url( '../css/d4events.css' , __FILE__ ) );
	wp_enqueue_style( 'd4events' );
	wp_register_style( 'add-to-calendar', plugins_url( '../css/atc-style-blue.css' , __FILE__ ) );
	wp_enqueue_style( 'add-to-calendar' );
	wp_register_script( 'd4events', plugins_url( '../js/d4events.js' , __FILE__ ), array( 'jquery' ), 'v20131005', true );
	wp_enqueue_script('d4events');	
}

function event_output() {
	$posttitle = '<h5 class="cal-event-title">'.get_the_title().'</h5>';

	if ($attr['link'] == '') {
		$linkopen = '<a href="'.get_the_permalink().'">';
	} else {
		$linkopen = $attr['link'];
	}
	$linkclose = "</a>";

	//Render output
	
	$output .= '<div class="cal-event-wrapper '.$wrapperclass.'">';
	$output .= $linkopen;
	$output .= $posttitle;
	$output .= $linkclose;
	$output .= '<div class="clearfix"></div></div>';
}

function get_events($event_date,$category,$events_query) {

	$day_of_the_week = date('l', strtotime($event_date));
	$day_of_the_month = date('j', strtotime($event_date));
	$nth_weekday_of_every_month = ceil($day_of_the_month / 7);


	while ( $events_query->have_posts() ) { $events_query->the_post();		
			

		$posttitle = '<h5 class="cal-event-title">'.get_the_title().'</h5>';

		$remove_link = get_post_meta( $event_id, 'd4events_remove_link', true);
		if ($remove_link != 'on') {
			$linkopen = '<a href="'.get_the_permalink().'">';
			$linkclose = "</a>";
		} else {
			$linkopen = '';
			$linkclose = '';
		}

		$event_id = get_the_id();

		$start_date = strtotime(get_post_meta( $event_id, 'd4events_start_date', true));
		$end_date = strtotime(get_post_meta( $event_id, 'd4events_end_date', true ));

		$event_duration = date('j', $end_date) - date('j', $start_date);
		
		$event_date2 = strtotime($event_date);	

		$repeating = get_post_meta( $event_id, 'd4events_repeating', true );
		$repeating_event = false;

		if ($repeating != '') {
			$weekly_repeat_days = get_post_meta( $event_id, 'd4events_repeat_days', true );
			if ($weekly_repeat_days != '') {
				if (in_array($day_of_the_week, $weekly_repeat_days)) {
					$repeating_event = true;
				}
			}

			$repeat_interval = get_post_meta( $event_id, 'd4events_repeat_interval', true );
			$end_day_of_the_month = $event_duration + $repeat_interval;

			$month_repeat_by = get_post_meta( $event_id, 'd4events_monthly_repeat_by', true );

			if ($month_repeat_by == 'day_of_the_week') {
				$month_weekday_repeat = get_post_meta( $event_id, 'd4events_month_weekday_repeat', true );
				if (($month_weekday_repeat == $day_of_the_week) && ($repeat_interval == $nth_weekday_of_every_month)) {
					$repeating_event = true;
				}
			}
			else {
				if (($repeat_interval <= $day_of_the_month) && ($day_of_the_month <= $end_day_of_the_month)) {
					$repeating_event = true;
				}
			}
		}
		
		if ((($event_date2 >= $start_date) && ($event_date2 <= $end_date)) || ($repeating_event == true)) {

			//Render output
			
			$output .= '<div class="cal-event-wrapper '.$wrapperclass.'">';
			$output .= $linkopen;
			$output .= $posttitle;
			$output .= $linkclose;
			$output .= '<div class="clearfix"></div></div>';		

		}
	}
	return $output;
	wp_reset_postdata();
}

/* draws a calendar */
function draw_calendar($month,$year,$category){
	if ($month == '') {
		$month = date("n");
	}
	if ($year == '') {
		$year = date("Y");
	}
	
	$dateObj   = DateTime::createFromFormat('!m', $month);
	$monthName = $dateObj->format('F'); // March
	
	/* draw table */
	$calendar = '<div data-month="'.$month.'" data-year="'.$year.'" data-category="'.$category.'" id="d4-event-calendar"><div class="cal-change-button cal-prev" data-change="cal-prev"></div><div class="cal-change-button cal-next" data-change="cal-next"></div><h2>'.$monthName.' '.$year.'</h2><table cellpadding="0" cellspacing="0" class="calendar">';

	/* table headings */
	$headings = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	$calendar.= '<tr class="calendar-row"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';

	/* days and weeks vars now ... */
	$running_day = date('w',mktime(0,0,0,$month,1,$year));
	$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
	$days_in_this_week = 1;
	$day_counter = 0;
	$dates_array = array();

	# get all events, place in array to send to get_events()
	$events_args = array (
		'post_type' => 'events',
		'category_name'	=> $category	
	);
	$events_query = new WP_Query($events_args);

	/* row for week one */
	$calendar.= '<tr class="calendar-row">';

	/* print "blank" days until the first of the current week */
	for($x = 0; $x < $running_day; $x++):
		$calendar.= '<td class="calendar-day-np"> </td>';
		$days_in_this_week++;
	endfor;

	$month_has_events = false;
	
	/* keep going with days.... */
	for($list_day = 1; $list_day <= $days_in_month; $list_day++):
		
		if (strlen($list_day) < 2) {
			$fixed_day = '0'.$list_day;	
		} else {
			$fixed_day = $list_day;
		}
		if (strlen($month) < 2) {
			$fixed_month = '0'.$month;	
		} else {
			$fixed_month = $month;
		}
		$fulldate = $fixed_month.'/'.$fixed_day.'/'.$year;
		$day_events = get_events($fulldate,$category,$events_query);

		if (!empty($day_events)) {
			$has_events = ' has-events';
			$month_has_events = true;
		}

		$calendar.= '<td class="calendar-day '.$has_events.'"><div class="day-internal">';
		unset($has_events);
		/* add in the day number */
		$calendar.= '<div class="day-number">'.$list_day.'</div>';
		#calendar.= str_repeat('<p> </p>',2);		
		$calendar .= $day_events;			
		$calendar.= '</div></td>';

		if($running_day == 6):
			$calendar.= '</tr>';
			if(($day_counter+1) != $days_in_month):
				$calendar.= '<tr class="calendar-row">';
			endif;
			$running_day = -1;
			$days_in_this_week = 0;
		endif;
		$days_in_this_week++; $running_day++; $day_counter++;
	endfor;

	/* finish the rest of the days in the week */
	if($days_in_this_week < 8):
		for($x = 1; $x <= (8 - $days_in_this_week); $x++):
			$calendar.= '<td class="calendar-day-np"> </td>';
		endfor;
	endif;

	/* final row */
	$calendar.= '</tr>';

	/* end the table */
	$calendar.= '</table></div>';

	/* Display No Events Notice */
	if ($month_has_events == false) {
		$calendar .= '<div class="no-events">There are no scheduled events for this month</div>';
	}
	
	/* all done, return result */
	return $calendar;
}



add_action( 'wp_ajax_cal_change', 'd4_ajax_cal_change' );
add_action( 'wp_ajax_nopriv_cal_change', 'd4_ajax_cal_change' );


function d4_ajax_cal_change() {
    // Handle request then generate response using WP_Ajax_Response
	if(isset($_POST['month']))
		{
		    $month = $_POST['month'];
		}
	if(isset($_POST['year']))
		{
		    $year = $_POST['year'];
		}
	if(isset($_POST['category']))
		{
		    $category = $_POST['category'];
		}	
	if(isset($_POST['change']))
		{
		    $change = $_POST['change'];		    
		}
	if ($change == "cal-prev") {
		$nextmonth = $month-'1';			
	}
	if ($change == "cal-next") {
		$nextmonth = $month+'1';	
	}
	if ($nextmonth == '13') {
			$nextmonth ='1';
			$nextyear = $year+'1';
	}
	elseif ($nextmonth == '0') {
			$nextmonth ='12';
			$nextyear = $year-'1';
	}
	else $nextyear = $year;		
    echo draw_calendar($nextmonth,$nextyear,$category);
    die();
}

// Use: [events year="" month="" category=""]
	function shortcode_events( $atts ) {
		$attr=shortcode_atts(array(
			'year' => '',
			'month'=>'',
			'search' => '',
			'category' => '',
			'agenda' => '',
		), $atts);

		$month = date("n");
		if ($attr['year'] != '') {
			$year = date("Y");
		}
		if ($attr['search'] != '') {
			$search = '<form class="search-form" role="search" method="get"action="';
			$search .= home_url( '/' );
			$search .= '">';
			$search .= '<input type="hidden" name="post_type" value="events" />';
			$search .= '<label><span class="screenreader">Search for:</span><input class="search-field" type="search" placeholder="Search Events..." value="" name="s" title="Search for:" /></label><input class="search-submit" type="submit" value="Submit" /></form>';
		}
		if ($attr['category'] != '') {
			$category = $attr['category'];
		}
		if ($attr['agenda'] != '') {
			$agenda = 'class="agenda-view"';
		}

		$event_calendar = draw_calendar($month,$year,$category);
		$buttons = '<div class="cal-change-button cal-prev" data-change="cal-prev">Previous</div><div class="cal-change-button cal-next" data-change="cal-next">Next</div>';

		$output = '';
		$output .= '<div id="d4-cal-wrapper"'.$agenda.'>';
		$output .= $search;
		$output .= '<div id="d4-cal-inner">';
		$output .= $event_calendar;
		$output .= '</div></div>';	

		return $output;
	} add_shortcode( 'events', 'shortcode_events' );

//Load the single event template
function get_d4events_template($single_template) {
     global $post;

     if ($post->post_type == 'events') {
      	$single_template .= dirname( __FILE__ ) . '/single-event.php';
     }
     return $single_template;
}
add_filter( 'single_template', 'get_d4events_template' );

function d4events_before_main_content() {
	do_action('d4events_before_main_content');
}

function d4events_after_main_content() {
	do_action('d4events_after_main_content');
}

function d4_events_wrapper_start() {
  echo '<section id="content"><div id="title-bar"><div class="page-wrapper"><h1 class="page-title">'.get_the_title().'</h1></div></div><div class="page-wrapper"><main id="main-content" class="clearfix" role="main">';
}

function d4_events_wrapper_end() {
  echo '</main></div></section>';
}

add_action('d4events_before_main_content', 'd4_events_wrapper_start', 10);
add_action('d4events_after_main_content', 'd4_events_wrapper_end', 10);

?>