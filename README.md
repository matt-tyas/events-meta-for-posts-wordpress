Events Meta for posts Wordpress
===============================

> A simple plugin that adds Event meta to normal posts in Wordpress.

Code for front end display
--------------------------

###Advanced display

> Splits out all date/time/location information into <span class=""></span> elements for very granular styling.

> *This needs converting to shortcodes or something. Please help!*

`
<?php
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
	
	/* To pull in events that end later than now - beta, 
	you may not want this and/or it may not work properly which is why I've not done a pull request yet */
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
	   'posts_per_page' => 6
	   );
	
	$events = new WP_Query( $args );
	
	// Do second query - beta
	$args = array(
	   'post_type' => 'post',
	   'meta_key' => '_start_eventtimestamp',
	   'orderby'=> 'meta_value_num',
	   'order' => 'ASC',
	   'meta_query' => $meta_query_two,
	   'posts_per_page' => 6
	   );
	$events_end = new WP_Query( $args );
	
	// Merge posts into new object, removing duplicates (beta)
	// This will put events that [started before today and end later than now] first
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
`

###Simple display

> Out puts the date/time/location information in a simple sentence

> *This needs converting to shortcodes or something. Please help!*

`
<?php
	$has_start = get_post_meta($post->ID, '_start_month', true);
	if ( $has_start != '100' ) {
		// Get start date to display
		$str = date('d m Y H i', strtotime('' . get_post_meta($post->ID, '_start_eventtimestamp', true) .''));
		list($startday,$startmonth,$startyear,$starthour,$startminute) = preg_split('/[ ,]/',$str,false,PREG_SPLIT_NO_EMPTY);
	?>
		<p class="event-time-car">
	<?php
		echo $startday . '/' . $startmonth . '/' . $startyear;
		$has_start_time = get_post_meta($post->ID, '_start_hour', true);
		if ( $has_start_time ) {
			echo " &#8211; " . $starthour . ':' . $startminute;
		}
	}
	
	$has_end = get_post_meta($post->ID, '_end_month', true);
	if ( $has_start != '100' && $has_end != '100') {
		// Get end date to display
		$str = date('d m Y H i', strtotime('' . get_post_meta($post->ID, '_end_eventtimestamp', true) .''));
		list($endday,$endmonth,$endyear,$endhour,$endminute) = preg_split('/[ ,]/',$str,false,PREG_SPLIT_NO_EMPTY);
		echo " to " .  $endday . '/' . $endmonth . '/' . $endyear;
		$has_end_time = get_post_meta($post->ID, '_end_hour', true);
		if ( $has_end_time ) {
				echo " &#8211; " . $endhour . ':' . $endminute;
		}
	}
	if ( $has_start != '100' ) {
		$has_location = get_post_meta($post->ID, '_event_location', true);
		if ( $has_location ) {
			echo " " . $has_location . "";
		}
	?>
		</p>
	<?php
	}
?>
`