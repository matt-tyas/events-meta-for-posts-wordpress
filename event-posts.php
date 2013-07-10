<?php
/*
Plugin Name: Event Posts
Plugin URI: http://www.wptheming.com
Description: EDITED BY MATT TYAS 
Version: 0.2
Author: Devin Price
Author URI: http://www.wptheming.com
License: GPLv2 or later
*/

/* FRONT END CODE EXAMPLES
-------------------------- */

// SIMPLE
/*
<?php
$has_start = get_post_meta($post->ID, '_start_month', true);
if ( $has_start != '100' && $has_start != '' ) {
	// Get start date to display
	$str = date('d m Y H i', strtotime('' . get_post_meta($post->ID, '_start_eventtimestamp', true) .''));
	list($startday,$startmonth,$startyear,$starthour,$startminute) = preg_split('/[ ,]/',$str,false,PREG_SPLIT_NO_EMPTY);
?>
	<p class="event-time-car">
<?php
	echo $startday . '/' . $startmonth . '/' . $startyear;
	$has_start_time = get_post_meta($post->ID, '_start_hour', true);
	if ( $has_start_time ) {
		echo $starthour . ':' . $startminute;
	}
}

$has_end = get_post_meta($post->ID, '_end_month', true);
if ( $has_start != '100' && $has_end != '100' && $has_start != '' && $has_end != '') {
	// Get end date to display
	$str = date('d m Y H i', strtotime('' . get_post_meta($post->ID, '_end_eventtimestamp', true) .''));
	list($endday,$endmonth,$endyear,$endhour,$endminute) = preg_split('/[ ,]/',$str,false,PREG_SPLIT_NO_EMPTY);
	echo " &#8211; " .  $endday . '/' . $endmonth . '/' . $endyear;
	$has_end_time = get_post_meta($post->ID, '_end_hour', true);
	if ( $has_end_time ) {
			echo $endhour . ':' . $endminute;
	}
}
if ( $has_start != '100' && $has_start != '' ) {
	$has_location = get_post_meta($post->ID, '_event_location', true);
	if ( $has_location ) {
		echo ' ' . $has_location . ' ';
	}
    if ( $has_start != '100' && $has_start != '' ) {
?>
        </p>
<?php
    }
}
?>
*/

// ADVANCED
// http://codex.wordpress.org/Function_Reference/current_time
$current_time = current_time('mysql');
list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = split( '([^0-9])', $current_time );

// Set the hour and minute to midnight so events today that started between midnight and now are still pulled in
$midnight_today = $today_year . $today_month . $today_day . '0000'; 

$current_endtimestamp = $today_year . $today_month . $today_day . $hour . $minute; 

// To pull in events that start later than midnight today
$meta_query = array(
  'relation'=>'AND',
   array(
       'key' => '_start_eventtimestamp',
       'value' => $midnight_today,
       'compare' => '>'
   )
);

// To pull in events that end later than now (and started before midnight today)
$meta_query_two = array(
   'relation'=>'AND',
   array(
       'key' => '_end_eventtimestamp',
       'value' => $current_endtimestamp,
       'compare' => '>'
   ),
   array(
       'key' => '_start_eventtimestamp',
       'value' => $midnight_today,
       'compare' => '<'
   )
);

// Do first query
$args = array(
   'post_type' => 'post',
   'meta_key' => '_start_eventtimestamp',
   'orderby'=> 'meta_value_num',
   'order' => 'ASC',
   'meta_query' => $meta_query,
   'posts_per_page' => 4
   );

$events = new WP_Query( $args );

// Do second query - beta
$args = array(
   'post_type' => 'post',
   'meta_key' => '_start_eventtimestamp',
   'orderby'=> 'meta_value_num',
   'order' => 'ASC',
   'meta_query' => $meta_query_two,
   'posts_per_page' => 4
   );
$events_end = new WP_Query( $args );

// Merge posts into new object, removing duplicates just in case
// Puts events that [started before today and end later than now] first
$merged = new WP_Query();
$merged->posts =  array_unique(array_merge((array)$events_end->posts, (array)$events->posts), SORT_REGULAR);
$merged->post_count = count( $merged->posts );
//print_r($merged);
$events = $merged;

if ( $events->have_posts() ) :

while ( $events->have_posts() ) : $events->the_post();
// Single event

// Start 
// Get start date to display
$str = date('D d M H i', strtotime('' . get_post_meta($post->ID, '_start_eventtimestamp', true) .''));
list($startweekday,$startday,$startmonth,$starthour,$startminute) = preg_split('/[ ,]/',$str,false,PREG_SPLIT_NO_EMPTY);

// Get start time for calculation
$calc_start = strtotime('' . get_post_meta($post->ID, '_start_eventtimestamp', true) .'');

// Setting month to "None" gives it a value of 100 which we get separately
$has_start = get_post_meta($post->ID, '_start_month', true);
   
// End 
// Get end date to display
$str = date('D d M', strtotime('' . get_post_meta($post->ID, '_end_eventtimestamp', true) .''));
list($endweekday,$endday,$endmonth) = preg_split('/[ ,]/',$str,false,PREG_SPLIT_NO_EMPTY);

// Get end date for calculation
$calc_end = strtotime('' . get_post_meta($post->ID, '_end_eventtimestamp', true) .'');

// Setting month to "None" gives it a value of 100 which we get separately
$has_ending = get_post_meta($post->ID, '_end_month', true);
 
// Don't display the event at all if
    // (Start AND End are set to none) OR
    // (End is NOT none AND End before midnight today) OR
    // (End is none AND Start before midnight today)
 
// This is a terrible way to do it but at least it's quite easy to see what's going on    
if (!( 
      ($has_ending == '100' && $has_start == '100') ||
      ($has_ending != '100' && $calc_end < strtotime('midnight')) ||
      ($has_ending == '100' && $calc_start < strtotime('midnight'))
      )) {
    
    echo '<article class="media event">';
    echo '<a class="event-link" href="' . get_permalink() . '">';
    ?>
        <div class="event-date multi-date">
    <?php
       if ( $has_start !== '100' ) {
    ?>  
            <div class="start-date">
                <div class="event-weekday">
                    <?php echo $startweekday; ?>
                </div>
                <div class="event-day">
                    <?php echo $startday; ?>
                </div>
                <div class="event-month">
                    <?php echo $startmonth; ?>
                </div>
            </div>
    <?php
       }

        if ( $has_ending !== '100' ) {
    ?>
            <span class="date-separator">-</span>
            <div class="end-date">
                <div class="event-weekday">
                <?php echo $endweekday; ?>
                </div>
                <div class="event-day">
                <?php echo $endday; ?>
                </div>
                <div class="event-month">
                <?php echo $endmonth; ?>
                </div>
            </div>
    <?php        
        }
    ?>
       </div>
    <?php
       // Title
       echo '<h6>' . get_the_title() . '</h6>';
       // Time and location
       echo '<span class="time-location">';
       echo $starthour . ':' . $startminute;
       echo ' ' . get_post_meta($post->ID, '_event_location', true) . '';
       echo '</span>';
       echo '</a>';
       echo '</article>';
}
   endwhile;
endif; ?>
*/

/**
 * Flushes rewrite rules on plugin activation to ensure event posts don't 404
 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
 */

function ep_eventposts_activation() {
	ep_eventposts();
	flush_rewrite_rules();
}

/*register_activation_hook( __FILE__, 'ep_eventposts_activation' );

function ep_eventposts() { 

	**
	 * Enable the event custom post type
	 * http://codex.wordpress.org/Function_Reference/register_post_type
	 */

/*	$labels = array(
		'name' => __( 'Events', 'eventposttype' ),
		'singular_name' => __( 'Event', 'eventposttype' ),
		'add_new' => __( 'Add New Event', 'eventposttype' ),
		'add_new_item' => __( 'Add New Event', 'eventposttype' ),
		'edit_item' => __( 'Edit Event', 'eventposttype' ),
		'new_item' => __( 'Add New Event', 'eventposttype' ),
		'view_item' => __( 'View Event', 'eventposttype' ),
		'search_items' => __( 'Search Events', 'eventposttype' ),
		'not_found' => __( 'No events found', 'eventposttype' ),
		'not_found_in_trash' => __( 'No events found in trash', 'eventposttype' )
	);

	$args = array(
    	'labels' => $labels,
    	'public' => true,
		'supports' => array( 'title', 'editor', 'thumbnail', 'comments' ),
		'capability_type' => 'post',
		'rewrite' => array("slug" => "event"), // Permalinks format
		'menu_position' => 5,
		'menu_icon' => plugin_dir_url( __FILE__ ) . '/images/calendar-icon.gif',  // Icon Path
		'has_archive' => true
	); 

	register_post_type( 'event', $args );
} */

add_action( 'init', 'ep_eventposts' );

/**
 * Adds event post metaboxes for start time and end time
 * http://codex.wordpress.org/Function_Reference/add_meta_box
 *
 * We want two time event metaboxes, one for the start time and one for the end time.
 * Two avoid repeating code, we'll just pass the $identifier in a callback.
 * If you wanted to add this to regular posts instead, just swap 'event' for 'post' in add_meta_box.
 */

function ep_eventposts_metaboxes() {
	add_meta_box( 'ept_event_location', 'Event Location', 'ept_event_location', 'post', 'side', 'default', array('id'=>'_end') );
	add_meta_box( 'ept_event_date_start', 'Start Date and Time', 'ept_event_date', 'post', 'side', 'default', array( 'id' => '_start') );
	add_meta_box( 'ept_event_date_end', 'End Date and Time (optional - SELECT NONE IN MONTH TO IGNORE THIS)', 'ept_event_date', 'post', 'side', 'default', array('id'=>'_end') );
}
add_action( 'admin_init', 'ep_eventposts_metaboxes' );

// Metabox HTML

function ept_event_date($post, $args) {
	$metabox_id = $args['args']['id'];
	global $post, $wp_locale;

	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'ep_eventposts_nonce' );

	$time_adj = current_time( 'timestamp' );
	$month = get_post_meta( $post->ID, $metabox_id . '_month', true );

	if ( empty( $month ) ) {
		$month = gmdate( 'm', $time_adj );
	}

	$day = get_post_meta( $post->ID, $metabox_id . '_day', true );

	if ( empty( $day ) ) {
		$day = gmdate( 'd', $time_adj );
	}

	$year = get_post_meta( $post->ID, $metabox_id . '_year', true );

	if ( empty( $year ) ) {
		$year = gmdate( 'Y', $time_adj );
	}
	
	$hour = get_post_meta($post->ID, $metabox_id . '_hour', true);
 
    if ( empty($hour) ) {
        $hour = gmdate( 'H', $time_adj );
    }
 
    $min = get_post_meta($post->ID, $metabox_id . '_minute', true);
 
    if ( empty($min) ) {
        $min = '00';
    }

	$month_s = '<select name="' . $metabox_id . '_month">';
	$month_s .= '<option value="100">None</option>';
	for ( $i = 1; $i < 13; $i = $i +1 ) {
		$month_s .= "\t\t\t" . '<option value="' . zeroise( $i, 2 ) . '"';
		if ( $i == $month )
			$month_s .= ' selected="selected"';
		$month_s .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
	}
	$month_s .= '</select>';

	echo $month_s;
	echo '<input type="text" name="' . $metabox_id . '_day" value="' . $day  . '" size="2" maxlength="2" />';
    echo '<input type="text" name="' . $metabox_id . '_year" value="' . $year . '" size="4" maxlength="4" /> @ ';
    echo '<input type="text" name="' . $metabox_id . '_hour" value="' . $hour . '" size="2" maxlength="2"/>:';
    echo '<input type="text" name="' . $metabox_id . '_minute" value="' . $min . '" size="2" maxlength="2" />';
 
}

function ept_event_location() {
	global $post;
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'ep_eventposts_nonce' );
	// The metabox HTML
	$event_location = get_post_meta( $post->ID, '_event_location', true );
	echo '<label for="_event_location">Location:</label>';
	echo '<input placeholder="format EG: @Sankeys" type="text" name="_event_location" value="' . $event_location  . '" />';
}

// Save the Metabox Data

function ep_eventposts_save_meta( $post_id, $post ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	if ( !isset( $_POST['ep_eventposts_nonce'] ) )
		return;

	if ( !wp_verify_nonce( $_POST['ep_eventposts_nonce'], plugin_basename( __FILE__ ) ) )
		return;

	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;

	// OK, we're authenticated: we need to find and save the data
	// We'll put it into an array to make it easier to loop though
	
	$metabox_ids = array( '_start', '_end' );

	foreach ($metabox_ids as $key ) {
	    
	    $aa = $_POST[$key . '_year'];
		$mm = $_POST[$key . '_month'];
		$jj = $_POST[$key . '_day'];
		$hh = $_POST[$key . '_hour'];
		$mn = $_POST[$key . '_minute'];
		
		$aa = ($aa <= 0 ) ? date('Y') : $aa;
		$mm = ($mm <= 0 ) ? date('n') : $mm;
		$jj = sprintf('%02d',$jj);
		$jj = ($jj > 31 ) ? 31 : $jj;
		$jj = ($jj <= 0 ) ? date('j') : $jj;
		$hh = sprintf('%02d',$hh);
		$hh = ($hh > 23 ) ? 23 : $hh;
		$mn = sprintf('%02d',$mn);
		$mn = ($mn > 59 ) ? 59 : $mn;
		
		$events_meta[$key . '_year'] = $aa;
		$events_meta[$key . '_month'] = $mm;
		$events_meta[$key . '_day'] = $jj;
		$events_meta[$key . '_hour'] = $hh;
		$events_meta[$key . '_minute'] = $mn;
	    $events_meta[$key . '_eventtimestamp'] = $aa . $mm . $jj . $hh . $mn;
	    
    }
    
    	// Save Locations Meta
    	
    	 $events_meta['_event_location'] = $_POST['_event_location'];	
 

	// Add values of $events_meta as custom fields

	foreach ( $events_meta as $key => $value ) { // Cycle through the $events_meta array!
		if ( $post->post_type == 'revision' ) return; // Don't store custom data twice
		$value = implode( ',', (array)$value ); // If $value is an array, make it a CSV (unlikely)
		if ( get_post_meta( $post->ID, $key, FALSE ) ) { // If the custom field already has a value
			update_post_meta( $post->ID, $key, $value );
		} else { // If the custom field doesn't have a value
			add_post_meta( $post->ID, $key, $value );
		}
		if ( !$value ) delete_post_meta( $post->ID, $key ); // Delete if blank
	}

}

add_action( 'save_post', 'ep_eventposts_save_meta', 1, 2 );

/**
 * Helpers to display the date on the front end
 */

// Get the Month Abbreviation
 
function eventposttype_get_the_month_abbr($month) {
    global $wp_locale;
    for ( $i = 1; $i < 13; $i = $i +1 ) {
                if ( $i == $month )
                    $monthabbr = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
                }
    return $monthabbr;
}
 
// Display the date
 
function eventposttype_get_the_event_date() {
    global $post;
    $eventdate = '';
    $month = get_post_meta($post->ID, '_month', true);
    $eventdate = eventposttype_get_the_month_abbr($month);
    $eventdate .= ' ' . get_post_meta($post->ID, '_day', true) . ',';
    $eventdate .= ' ' . get_post_meta($post->ID, '_year', true);
    $eventdate .= ' at ' . get_post_meta($post->ID, '_hour', true);
    $eventdate .= ':' . get_post_meta($post->ID, '_minute', true);
    echo $eventdate;
}

// Add custom CSS to style the metabox
add_action('admin_print_styles-post.php', 'ep_eventposts_css');
add_action('admin_print_styles-post-new.php', 'ep_eventposts_css');

function ep_eventposts_css() {
	wp_enqueue_style('your-meta-box', plugin_dir_url( __FILE__ ) . '/event-post-metabox.css');
}

/**
 * Customize Event Query using Post Meta
 * 
 * @link http://www.billerickson.net/customize-the-wordpress-query/
 * @param object $query data
 *
 */
function ep_event_query( $query ) {

	// http://codex.wordpress.org/Function_Reference/current_time
	$current_time = current_time('mysql'); 
	list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = split( '([^0-9])', $current_time );
	$current_timestamp = $today_year . $today_month . $today_day . $hour . $minute;

	global $wp_the_query;
	
	if ( $wp_the_query === $query && !is_admin() && is_post_type_archive( 'post' ) ) {
		$meta_query = array(
			array(
				'key' => '_start_eventtimestamp',
				'value' => $current_timestamp,
				'compare' => '>'
			)
		);
		$query->set( 'meta_query', $meta_query );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'meta_key', '_start_eventtimestamp' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', '4' );
	}

}

add_action( 'pre_get_posts', 'ep_event_query' );

?>