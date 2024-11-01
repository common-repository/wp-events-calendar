<?php
/*
Plugin Name: WP Events Calendar
Plugin URI: https://wordpress.org/plugins/wp-events-calendar/
Description: Adds events capabilities to your WordPress website.
Version: 1.0.2
Author: Garth Koyle
Author URI: https://eventespresso.com/
Contributors: garthkoyle
*/

add_action('plugins_loaded', 'init_wp_events_calendar');
function init_wp_events_calendar() {
	new wp_events_calendar;
}



/**
 * Class wp_events_calendar
 *
 * @package       Event Espresso, eventespresso, sethshoultes,
 * @subpackage    core
 * @author        Patrick Garman, Brent Christensen
 * @since         1.0.0
 */
class wp_events_calendar {

	/**
	 * wp_events_calendar constructor.
	 */
	public function __construct() {
		add_action('init', array($this,'add_post_type'));
		add_action('admin_init', array($this,'add_date_metabox'));
		add_action('save_post', array($this,'save_date_metabox'));
		// add_filter('get_the_date', array($this,'date_filter'));
	}



	/**
	 * callback for the 'get_the_date' filter
	 *
	 * @param $date
	 */
	public function date_filter( $date ) {
		// print_r($date);
	}



	/**
	 * add_post_type
	 */
	public function add_post_type() {
		register_post_type('event',
			array(
				'labels' => array(
					'name' => 'Events',
					'singular_name' => 'Event',
					'search_items' => 'Search Events',
					'popular_items' => 'Common Events',
					'all_items' => 'All Events',
					'edit_item' => 'Edit Event',
					'update_item' => 'Update Event',
					'add_new_item' => 'Add New Event',
					'new_item_name' => 'New Event Name',
					'separate_items_with_commas' => NULL,
					'add_or_remove_items' => NULL,
					'choose_from_most_used' => NULL,
				),
				'hierarchical' => FALSE,
				'public' => true,
				'has_archive' => true,
				'rewrite' => array( 'slug' => 'events' ),
			)
		);
		register_taxonomy(
			'event_category',
			'event',
			array(
				'labels' => array(
					'name' => 'Category',
					'singular_name' => 'Category',
					'search_items' => 'Search Categories',
					'popular_items' => 'Common Categories',
					'all_items' => 'All Categories',
					'parent_item' => 'Parent Categories',
					'parent_item_colon'  => 'Parent Category:',
					'edit_item' => 'Edit Categories',
					'update_item' => 'Update Categories',
					'add_new_item' => 'Add New Categories',
					'new_item_name' => 'New Event Categories',
					'separate_items_with_commas' => 'Separate categories with commas',
					'add_or_remove_items' => 'Add or remove categories',
					'choose_from_most_used' => 'Choose from the most used categories',
					'menu_name' => 'Event Categories',
				),
				'sort' => true,
				'args' => array('orderby' => 'term_order'),
				'rewrite' => array( ),
				'hierarchical' => false,
				'query_var' => true,
			)
		);
	}

	public function add_date_metabox(){
		add_meta_box('event_date', 'Date & Time', array($this,'date_metabox'), 'event', 'side', 'high');
	}



	/**
	 * @param WP_Post $post
	 */
	public function date_metabox( WP_Post $post ) {
		?>
		<select name="event_month">
			<?php for ( $i = 1; $i <= 12; $i++ ) {
				if ( get_post_meta( $post->ID, 'event_time', true ) ) {
					$select = date( 'n', get_post_meta( $post->ID, 'event_time', true ) );
				} else {
					$select = date( 'n' );
				}
				echo '<option value="' . $i . '"';
				if ( $i === $select ) {
					echo ' selected="selected" ';
				}
				echo '>' . $i . '</option>';
			} ?>
		</select>
		<select name="event_day">
			<?php for ( $i = 1; $i <= 31; $i++ ) {
				if ( get_post_meta( $post->ID, 'event_time', true ) ) {
					$select = date( 'j', get_post_meta( $post->ID, 'event_time', true ) );
				} else {
					$select = date( 'j' );
				}
				echo '<option value="' . $i . '"';
				if ( $i === $select ) {
					echo ' selected="selected" ';
				}
				echo '>' . $i . '</option>';
			} ?>
		</select>
		<select name="event_year">
			<?php for ( $i = 0; $i <= 5; $i++ ) {
				$select = '';
				if ( get_post_meta( $post->ID, 'event_time', true ) ) {
					$select = date( 'Y', get_post_meta( $post->ID, 'event_time', true ) );
				}
				$year = date( 'Y' ) + $i;
				echo '<option value="' . $year . '"';
				if ( $year === $select ) {
					echo ' selected="selected" ';
				}
				echo '>' . $year . '</option>';
			} ?>
		</select>
		<select name="event_time">
			<?php for ( $i = 0; $i < 24; $i++ ) {
				$select = '8:00';
				if ( get_post_meta( $post->ID, 'event_time', true ) ) {
					$select = date( 'H:i', get_post_meta( $post->ID, 'event_time', true ) );
				}
				$hour = $i;
				$m = 'AM';
				if ( $i === 0 ) {
					$hour = 12;
				} elseif ( $i > 12 ) {
					$hour = $i - 12;
					$m = 'PM';
				}
				$value = $i . ':00';
				echo '<option value="' . $value . '"';
				if ( $value === $select ) {
					echo ' selected="selected" ';
				}
				echo '>' . $hour . ':00 ' . $m . '</option>';
				$value = $i . ':30';
				echo '<option value="' . $value . '"';
				if ( $value === $select ) {
					echo ' selected="selected" ';
				}
				echo '>' . $hour . ':30 ' . $m . '</option>';
			} ?>
		</select>

		<?php
		echo '<input type="hidden" name="event_noncename" value="' . wp_create_nonce( 'event_' . $post->ID ) . '" />';
	}



	/**
	 * @param int $post_id
	 * @return void
	 */
	public function save_date_metabox( $post_id = 0 ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! wp_verify_nonce($_POST['event_noncename'], 'event_'.$post_id )) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$post = get_post($post_id);
		if ( $post->post_type === 'event' ) {
			$time = strtotime($_POST['event_month'].'/'.$_POST['event_day'].'/'.$_POST['event_year'].' '.$_POST['event_time']);
			update_post_meta($post_id, 'event_time', $time );
		}
	}
}