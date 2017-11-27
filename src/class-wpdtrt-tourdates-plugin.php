<?php
/**
 * Plugin sub class.
 *
 * @package     wpdtrt_tourdates
 * @since       1.0.0
 * @version 	1.0.0
 */

/**
 * Plugin sub class.
 *
 * Extends the base class to inherit boilerplate functionality.
 * Adds application-specific methods.
 *
 * @since       1.0.0
 * @version 	1.0.0
 */
class WPDTRT_TourDates_Plugin extends DoTheRightThing\WPPlugin\Plugin {

    /**
     * Hook the plugin in to WordPress
     * This constructor automatically initialises the object's properties
     * when it is instantiated,
     * using new WPDTRT_TourDates_Plugin
     *
     * @param     array $settings Plugin options
     *
     * @version   1.1.0
     * @since     1.0.0
     */
    function __construct( $settings ) {

    	// add any initialisation specific to wpdtrt-blocks here

		// Instantiate the parent object
		parent::__construct( $settings );
    }

    //// START WORDPRESS INTEGRATION \\\\

    /**
     * Initialise plugin options ONCE.
     *
     * @param array $default_options
     *
     * @since 1.0.0
     *
     * @todo update
     * @todo support this function in child plugin
     */
    protected function wp_setup() {
		add_action( 'post_type_link', 	[$this, 'render_permalink_placeholders', 10, 3] ); // Custom Post Type
		add_action( 'init', 			[$this, 'set_rewrite_rules'] );
		add_action( 'save_post', 		[$this, 'set_daynumber'] );
		add_filter( 'the_title', 		[$this, 'post_title_add_day'] );
		//add_filter('post_link', 		'wpdtrt_tourdates_cf_permalink_placeholders', 10, 3); // Regular post
    }

    //// END WORDPRESS INTEGRATION \\\\

    //// START SETTERS AND GETTERS \\\\

	/**
	 * Get the value of the leg count field
	 * @param number $term_id The Term ID
	 * @param string $taxonomy The taxonomy
	 * @return string $start_date The start date
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @see https://www.advancedcustomfields.com/resources/get_field/
	 * @todo calculate this instead, allowing for only unique legs
	 * @todo This is now a category level option rather than ACF
	 */
	public function get_acf_tour_category_leg_count( $term_id, $taxonomy ) {

	  $leg_count = get_field('wpdtrt_tourdates_acf_tour_category_leg_count', $taxonomy . '_' . $term_id);

	  return $leg_count;
	}

	/**
	 * Get the value of the start date field
	 * @param number $term_id The Term ID
	 * @param string $taxonomy The taxonomy
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @return string $start_date The start date
	 * @see https://www.advancedcustomfields.com/resources/get_field/
	 * @todo This is now a category level option rather than ACF
	 */
	public function get_acf_term_start_date( $term_id, $taxonomy ) {

	  $start_date = get_field('wpdtrt_tourdates_acf_tour_category_start_date', $taxonomy . '_' . $term_id);

	  return $start_date;
	}

	/**
	 * Get the value of the end date field
	 * @param number $term_id The Term ID
	 * @param string $taxonomy The taxonomy
	 * @return string $end_date The end date
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @see https://www.advancedcustomfields.com/resources/get_field/
	 * @todo This is now a category level option rather than ACF
	 */
	public function get_acf_term_end_date( $term_id, $taxonomy ) {

	  $end_date = get_field('wpdtrt_tourdates_acf_tour_category_end_date', $taxonomy . '_' . $term_id);

	  return $end_date;
	}

	/**
	 * Get the value of the term image field
	 * @param number $term_id The Term ID
	 * @param string $taxonomy The taxonomy
	 * @return string $term_thumbnail_id The thumbnail ID
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @see https://www.advancedcustomfields.com/resources/get_field/
	 * @see https://www.advancedcustomfields.com/resources/image/
	 * @todo Roll into theme as not date specific
	 * @todo This is now a category level option rather than ACF
	 */
	public function get_acf_term_thumbnail_id( $term_id, $taxonomy ) {

	  $thumbnail_id = get_field('wpdtrt_dbth_acf_tours_term_thumbnail', $taxonomy . '_' . $term_id);

	  return $thumbnail_id;
	}

	/**
	 * Get the value of the tour type field
	 *  Used to calculate date offsets.
	 *
	 * @param number $id The ID of the term
	 * @param string $taxonomy The taxonomy
	 * @return string $term_type (tour|tour_leg)
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @see https://www.advancedcustomfields.com/resources/get_field/
	 * @todo This is now a category level option rather than ACF
	 */
	public function get_acf_term_type( $term_id, $taxonomy ) {
	  $term_type = get_field('wpdtrt_tourdates_acf_tour_category_type', $taxonomy . '_' . $term_id);

	  return $term_type;
	}

	/**
	 * Get the ID of the ACF term type
	 *  so we can get the values of the ACF fields attached to this term
	 *
	 * @param string $term_type The term type (tour|tour_leg)
	 * @return number $term_id The term ID
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 * @todo This is now a category level option rather than ACF
	 */
	public function get_post_term_ids($term_type) { // // this is returning tour leg start date rather than tour start date

	  global $post;
	  $post_id = $post->ID;
	  $taxonomy = 'tours'; // get_query_var('taxonomy') isn't working

	  $term_id = null;

	  // get associated taxonomy_terms
	  // get_the_category() doesn't work with custom post type taxonomies
	  $terms = get_the_terms( $post_id, $taxonomy );

	  if ( is_array( $terms ) ) {
	    /**
	     * Sort terms into hierarchical order
	     *
	     * Has parent: $term->parent === n
	     * No parent: $term->parent === 0
	     * strnatcmp = Natural string comparison
	     *
	     * @see https://developer.wordpress.org/reference/functions/get_the_terms/
	     * @see https://wordpress.stackexchange.com/questions/172118/get-the-term-list-by-hierarchy-order
	     * @see https://stackoverflow.com/questions/1597736/how-to-sort-an-array-of-associative-arrays-by-value-of-a-given-key-in-php
	     * @see https://wpseek.com/function/_get_term_hierarchy/
	     * @see https://wordpress.stackexchange.com/questions/137926/sorting-attributes-order-when-using-get-the-terms
	     * @uses WPDTRT helpers/permalinks.php
	     */
	    uasort ( $terms , function ( $term_a, $term_b ) {
	      return strnatcmp( $term_a->parent, $term_b->parent );
	    });

	    if ( !is_wp_error( $terms ) ) {
	      foreach ( $terms as $term ) {
	        if ( !empty( $term ) && is_object( $term ) ) {

	          $term_id = $term->term_id;
	          $acf_term_type = $this->get_acf_term_type( $term_id, $taxonomy );

	          if ( $acf_term_type === $term_type ) {
	            break;
	          }
	        }
	      }
	    }
	  }

	  return $term_id;
	}

	/**
	 * Get the number of a tour day, relative to the tour start date
	 * Note that the post has to be published on (for) the target date,
	 * else this will show the creation date
	 * @param number $post_id The post ID
	 * @return number $post_daynumber The day number
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @todo Consider rewriting into a shortcode
	 */
	public function get_post_daynumber($post_id) {

		$tour_start_date = $this->get_term_start_date( $post_id, 'tour' ); // this was wrongly returning the tour leg start date
		$post_date = get_the_date( "Y-n-j 00:01:00", $post_id );
		$post_daynumber = $this->get_term_days_elapsed( $tour_start_date, $post_date );

		return $post_daynumber;
	}

	/**
	 * Get the first date in a tour
	 *
	 * @param number $id The ID of the post OR term
	 * @param string $term_type An optional term type, useful when we want to query a tour rather than a tour leg
	 * @param string $date_format An optional date format
	 * @return string $tour_start_date The date when the tour started (Y-n-j 00:01:00)
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 */
	public function get_term_start_date($id, $term_type=null, $date_format=null) {

		$taxonomy = 'tours'; // get_query_var('taxonomy') isn't working

		// if $id is the ID of a term in the 'tours' taxonomy
		// then this is a tour leg
		// and we are getting the tour leg date range
		// term_exists( $term, $taxonomy, $parent )
		if ( term_exists( $id, $taxonomy ) ) {
			$term_id = $id;
		}
		// else if it isn't then the $id is the ID of a tour day
		// and we are getting the start date for term_days_elapsed daynumber
		else { // if post
			// when this is called by add_filter( 'the_title', 'wpdtrt_tourdates_post_title_add_day' )
			// then the term is not passed
			$term_id = $this->get_post_term_ids( $term_type );
		}

		$tour_start_date = $this->get_acf_term_start_date( $term_id, $taxonomy );

		if ( $date_format !== null ) {
			$date = new DateTime($tour_start_date);
			$tour_start_date = date_format($date, $date_format);
		}

		return $tour_start_date;
	}

	/**
	 * Get the first day in a tour type
	 * If this is a tour leg, calculate how many days it starts,
	 * after the tour starts
	 *
	 * @param number $term_id The term ID
	 * @return number $tour_start_day The day when the tour started
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 */
	public function get_term_start_day( $term_id ) {

		$taxonomy = get_query_var( 'taxonomy' );
		$term = get_term_by( 'id', $term_id, $taxonomy );
		$term_type = $this->get_acf_term_type( $term_id, $taxonomy );

		if ( $term_type === 'tour' ) {
			$tour_start_day = 1;
		}
		else if ( $term_type === 'tour_leg' ) {
			$parent_term_id = $term->parent;
			$tour_start_date =      $this->get_term_start_date( $parent_term_id );
			$tour_leg_start_date =  $this->get_term_start_date( $term_id );
			$tour_start_day =       $this->get_term_days_elapsed( $tour_start_date, $tour_leg_start_date );
		}

		return $tour_start_day;
	}

	/**
	 * Get the last date in a tour
	 *
	 * @param number $term_id The term ID
	 * @param string $date_format An optional PHP date format
	 * @return string $tour_end_date The date when the tour ended (Y-n-j 00:01:00)
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 */
	public function get_term_end_date($term_id, $date_format=null) {

		$taxonomy = get_query_var( 'taxonomy' );
		$tour_end_date = $this->get_acf_term_end_date( $term_id, $taxonomy );

		if ( $date_format !== null ) {
			$date = new DateTime($tour_end_date);
			$tour_end_date = date_format($date, $date_format);
		}

		return $tour_end_date;
	}

	/**
	 * Get the start month & year in a tour
	 * @param number $term_id The term ID
	 * @return string $tour_leg_start_month The month when the tour started (Month YYYY)
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 */
	public function get_term_start_month( $term_id ) {
		$tour_leg_start_month = $this->get_term_start_date($term_id, null, 'F Y');

		return $tour_leg_start_month;
	}

	/**
	 * Get the end month & year in a tour
	 * @param number $term_id The term ID
	 * @return string $tour_leg_end_month The month when the tour ended (Month YYYY)
	 */
	public function get_term_end_month( $term_id ) {
		$tour_leg_end_month = $this->get_term_end_date($term_id, 'F Y');

		return $tour_leg_end_month;
	}

	/**
	 * Get the number of unique tour legs
	 * @param number $term_id The Term ID
	 * @param string $text_before Text to display if more than one leg
	 * @param string $text_after Text to display if more than one leg
	 * @return string $tour_leg_count The number of unique tour legs
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @todo wpdtrt_tourdates_acf_tour_category_leg_count can be determined from filtering child categories to wpdtrt_tourdates_acf_tour_category_first_visit
	 * @todo Roll into theme as nothing to do with dates
	 */
	public function get_term_leg_count($term_id, $text_before='', $text_after='') {

		$taxonomy = get_query_var( 'taxonomy' );
		$tour_leg_count = $this->get_acf_tour_category_leg_count( $term_id, $taxonomy );

		if ( $tour_leg_count > 1 ) {
			$str = $text_before . $tour_leg_count . $text_after;
			$tour_leg_count = $str;
		}
		else {
			$tour_leg_count = ''; // new zealand tour legs are tours
		}

		return $tour_leg_count;
	}

	/**
	 * Get days elapsed since tour started
	 * @param number $start_date The start date
	 * @param number $end_date The end date
	 * @return number $tour_days_elapsed Days elapsed
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @see http://www.timeanddate.com/date/durationresult.html?d1=2&m1=9&y1=2015&d2=30&m2=6&y2=2016
	 */
	public function get_term_days_elapsed($start_date, $end_date) {
		// http://stackoverflow.com/a/3923228
		$date1 = new DateTime($start_date);
		$date2 = new DateTime($end_date);

		if ( $date1 === $date2 ) {
		$tour_days_elapsed = 1;
		}
		else {
		$interval = $date1->diff($date2);
		$tour_days_elapsed = $interval->format("%r%a"); // ->d only gets days in the same month
		}

		return $tour_days_elapsed + 1;
	}

	/**
	 * Get the name of a tour leg
	 * @param string $tour_leg_slug The slug of the tour leg
	 * @return string $tour_leg_name The name of the tour leg
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @see https://wordpress.stackexchange.com/questions/16394/how-to-get-a-taxonomy-term-name-by-the-slug
	 * @see https://codex.wordpress.org/Function_Reference/get_term_by
	 * @todo Roll into theme as not date specific
	 */
	public function get_term_leg_name($tour_leg_slug) {
		$tour_leg_name = '';

		$tour_leg = get_term_by('slug', $tour_leg_slug, 'tours');

		$tour_leg_name = $tour_leg->name;

		return $tour_leg_name;
	}

	/**
	 * Get the ID of a tour leg
	 * @param string $tour_leg_slug The slug of the tour leg
	 * @return string $tour_leg_id The ID of the tour leg
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 *
	 * @see https://wordpress.stackexchange.com/questions/16394/how-to-get-a-taxonomy-term-name-by-the-slug
	 * @see https://codex.wordpress.org/Function_Reference/get_term_by
	 * @todo Roll into theme as not date specific
	 */
	public function get_term_leg_id($tour_leg_slug) {
		$tour_leg = get_term_by('slug', $tour_leg_slug, 'tours');

		$tour_leg_id = $tour_leg->term_id;

		return $tour_leg_id;
	}

	/**
	 * Get the total number of days in a tour
	 *
	 * @return string
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 */
	public function get_daytotal() {
		//$plugin_options = $this->get_plugin_options();

		global $post;
		$post_id = $post->ID;
		$tour_start_date = $this->get_term_start_date( $post_id );
		$tour_end_date = $this->get_term_end_date( $post_id );
		$day_total = $this->get_term_days_elapsed( $tour_start_date, $tour_end_date );

		return $day_total;
	}

	/**
	 * Get the coordinates of a map location
	 *
	 * @param string $key
	 *    The key of the JSON object.
	 * @return      string "lat,lng" | ""
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 */
	public function get_html_latlng( $key ) {

		// if options have not been stored, exit
		$wpdtrt_tourdates_options = get_option('wpdtrt_tourdates');

		if ( $wpdtrt_tourdates_options === '' ) {
			return '';
		}

		// the data set
		$wpdtrt_tourdates_data = $wpdtrt_tourdates_options['wpdtrt_tourdates_data'];

		// user - map block
		if ( isset( $wpdtrt_tourdates_data[$key]->{'address'} ) ) :

			$lat = $wpdtrt_tourdates_data[$key]->{'address'}->{'geo'}->{'lat'};
			$lng = $wpdtrt_tourdates_data[$key]->{'address'}->{'geo'}->{'lng'};

			$str = $lat . ',' . $lng;

		else:

			$str = '';

		endif;

		return $str;
	}

	/**
	 * Get tour length in days
	 *
	 * @param number $term_id The term ID
	 * @param string $text_before Translatable text displayed before the tour length
	 * @param string $text_after Translatable text displayed after the tour length
	 * @return string $tour_length_days The length of the tour
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 */
	public function get_tourlengthdays( $term_id, $text_before, $text_after ) {
		// convert shortcode argument to a number
		if ( isset( $term_id ) ) {
			$term_id = (int)$term_id;
		}

		$tour_start_date = $this->get_term_start_date( $term_id );
		$tour_end_date = $this->get_term_end_date( $term_id );
		$tour_length_days = $this->get_term_days_elapsed($tour_start_date, $tour_end_date);

		return $text_before . $tour_length_days . $text_after;
	}

	/**
	 * Create a custom field when a post is saved,
	 * which can be queried by the next/previous_post_link_plus plugin
	 * and used in the Yoast page title via %%cf_wpdtrt_tourdates_daynumber%%,
	 * and used in the permalink slug 'tourdiaries/%tours%/%wpdtrt_tourdates_cf_daynumber%' (wpdtrt-dbth)
	 *
	 * Use the Query Monitor plugin to view the Post type
	 *
	 * @link wpdtrt/library/permalink-placeholders.php
	 * @link wpdtrt-dbth/library/register_post_type_tourdiaries
	 * @see https://wordpress.org/support/topic/set-value-in-custom-field-using-post-by-email/
	 * @see https://wordpress.stackexchange.com/questions/61148/change-slug-with-custom-field
	 * @todo meta_key workaround requires each post to be resaved/updated, this is not ideal
	 */
	public function set_daynumber() {

		global $post;

		// if Update button used in Quick Edit view
		if ( ! $post ) {
			return;
		}

		$post_id = $post->ID;

		if( ! wp_is_post_revision($post) ) {

			$daynumber = $this->get_post_daynumber($post_id);

			// update_post_meta also runs add_post_meta, if the $meta_key does not already exist
			update_post_meta($post_id, 'wpdtrt_tourdates_cf_daynumber', $daynumber);

			// note: https://developer.wordpress.org/reference/functions/get_post_meta/#comment-1894
			//$test = get_post_meta($post_id, 'wpdtrt_tourdates_cf_daynumber', true); // true = return single value
		}
	}

    //// END SETTERS AND GETTERS \\\\

    //// START RENDERERS \\\\

	/**
	 * Generate an Alt attribute
	 *
	 * @param       string $key
	 *    The key of the JSON object.
	 * @param       boolean $has_enlargement (optional)
	 *    Whether the image should link to an enlargement
	 * @return      string The title
	 *
	 * @version 1.0.0
	 * @since       0.1.0
	 */
	public function render_html_title( $key, $has_enlargement = 0 ) {

		// if options have not been stored, exit
		$wpdtrt_tourdates_options = get_option('wpdtrt_tourdates');

		if ( $wpdtrt_tourdates_options === '' ) {
			return '';
		}

		// the data set
		$wpdtrt_tourdates_data = $wpdtrt_tourdates_options['wpdtrt_tourdates_data'];

		// user - map block
		if ( isset( $wpdtrt_tourdates_data[$key]->{'address'} ) ) {

			$str = 'Map showing the co-ordinates ' . wpdtrt_tourdates_html_latlng( $key );

		// photo - coloured block
		} else {

			$str = $wpdtrt_tourdates_data[$key]->{'title'};

		}

		if ( $has_enlargement ) {
			$str .= ". Click to view an enlargement";
		}

		return $str;
	}

	/**
	 * Generate the HTML for the last modified date
	 *
	 * @return      string <p class="wpdtrt_soundcloud_pages_date">Last updated 23rd April 2017</p>
	 *
	 * @since 0.1.0
	 * @version 1.0.0
	 */
	public function render_html_date() {

		// if options have not been stored, exit
		$wpdtrt_tourdates_options = get_option('wpdtrt_tourdates');

		if ( $wpdtrt_tourdates_options === '' ) {
			return '';
		}

		// the data set
		$last_updated = $wpdtrt_tourdates_options['last_updated'];

		// use the date format set by the user
		$wp_date_format = get_option('date_format');

		$str = '<p class="wpdtrt-tourdates-date">Data last updated: ' . date( $wp_date_format, $last_updated ) . '. </p>';

		return $str;
	}

	/**
	 * Render the HTML for a (linked) image
	 *
	 * @param       string $key
	 *    The key of the corresponding JSON object
	 * @param       boolean $has_enlargement (optional)
	 *    Whether the image should link to an enlargement
	 * @return      string <a href="..."><img src="..." alt="..."></a>
	 *
	 * @since 0.1.0
	 * @version 1.0.0
	 */
	public function render_html_image( $key, $has_enlargement = 0 ) {

		// if options have not been stored, exit
		$wpdtrt_tourdates_options = get_option('wpdtrt_tourdates');

		if ( $wpdtrt_tourdates_options === '' ) {
			return '';
		}

		// the data set
		$wpdtrt_tourdates_data = $wpdtrt_tourdates_options['wpdtrt_tourdates_data'];

		$str = '';

		if ( $has_enlargement ) {

			if ( isset( $wpdtrt_tourdates_data[$key]->{'address'} ) ) {

				$str .= '<a href="';
				$str .= 'http://maps.googleapis.com/maps/api/staticmap';
				$str .= '?scale=2';
				$str .= '&format=jpg';
				$str .= '&maptype=satellite';
				$str .= '&zoom=2';
				$str .= '&markers=' . wpdtrt_tourdates_html_latlng( $key );
				$str .= '&key=AIzaSyAyMI7z2mnFYdONaVV78weOmB0U2LThZMo';
				$str .= '&size=600x600';
				$str .= '">';

			}
			else {

				$str .= '<a href="';
				$str .= $wpdtrt_tourdates_data[$key]->{'url'};
				$str .= '">';

			}
		}

		$str .= '<img src="';

		// user - map block
		if ( isset( $wpdtrt_tourdates_data[$key]->{'address'} ) ) {

			$str .= 'http://maps.googleapis.com/maps/api/staticmap';
			$str .= '?scale=2';
			$str .= '&format=jpg';
			$str .= '&maptype=satellite';
			$str .= '&zoom=0';
			$str .= '&markers=' . wpdtrt_tourdates_html_latlng( $key );
			$str .= '&key=AIzaSyAyMI7z2mnFYdONaVV78weOmB0U2LThZMo';
			$str .= '&size=150x150';

		}
		else {

			$str .= $wpdtrt_tourdates_data[$key]->{'thumbnailUrl'};

		}

		$str .='" alt="';

		$str .= wpdtrt_tourdates_html_title( $key, $has_enlargement );

		$str .= '. ">';

		if ( $has_enlargement ) {
			$str .= '</a>';
		}

		return $str;
	}

	/**
	 * Render a previous/next navigation bar
	 *
	 * @version 1.0.0
	 * @since 0.1.0
	 */
	public function render_navigation() {

		// post object to get info about the post in which the shortcode appears
		global $post;
		$post_id = $post->ID;

		$posttype = $this->get_posttype(); // shortcode option
		$taxonomy = $this->get_taxonomy(); // shortcode option

		// vars to pass to template partial
		$previous =   $this->get_navigation_link('previous', $posttype, $taxonomy);
		$next =       $this->get_navigation_link('next', $posttype, $taxonomy);
		$daynumber =  $this->get_post_daynumber($post_id);

		/**
		* ob_start — Turn on output buffering
		* This stores the HTML template in the buffer
		* so that it can be output into the content
		* rather than at the top of the page.
		*/
		ob_start();

		require(WPDTRT_TOURDATES_PATH . 'template-parts/content-navigation.php');

		/**
		* ob_get_clean — Get current buffer contents and delete current output buffer
		*/
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Link to next/previous post
	 * @requires http://www.ambrosite.com/plugins/next-previous-post-link-plus-for-wordpress
	 * @param $direction string previous|next
	 * @param $posttype
	 * @param $taxonomy
	 * @todo Update to limit to the daycontroller category
	 */
	public function render_navigation_link($direction, $posttype, $taxonomy) {

		global $post;
		$id = $post->ID;

		$the_link = false;

		if ( $direction == 'previous' ) {
			$tooltip_prefix = 'Previous';
			$icon = 'left';
		}
		else if ( $direction == 'next' ) {
			$tooltip_prefix = 'Next';
			$icon = 'right';
		}

		$config = array(
			'order_by' => 'meta_key',
			'post_type' => '"' . $posttype . '"',
			'meta_key' => 'wpdtrt_tourdates_cf_daynumber',
			'loop' => false,
			'max_length' => 9999,
			'format' => '%link',
			'link' => '<span class="stack--navigation--text says">' . $tooltip_prefix . ': Day DAY_NUMBER</span> <span class="icon-arrow-' . $icon . ' stack--navigation--icon"></span>',
			'tooltip' => $tooltip_prefix . ': Day DAY_NUMBER.',
			'in_same_tax' => $taxonomy,
			'echo' => false
		);

		$current_daynumber = $this->get_post_daynumber($post->ID);

		if ( $direction == 'previous' ) {
			$the_id = previous_post_link_plus( array('return' => 'id') );
			$adjacent_daynumber = $this->get_post_daynumber($the_id);

			// Prevent navigation between different tours
			if ( ( $adjacent_daynumber > 0 ) && ( $adjacent_daynumber < $current_daynumber ) ) {
				$the_link = previous_post_link_plus( $config );
				$the_link = str_replace('DAY_NUMBER', $adjacent_daynumber, $the_link);
			}
		}
		else if ( $direction == 'next' ) {
			$the_id = next_post_link_plus( array('return' => 'id') );
			$adjacent_daynumber = $this->get_post_daynumber($the_id);

			// Prevent navigation between different tours
			if ( ( $adjacent_daynumber > 0 ) && ( $adjacent_daynumber > $current_daynumber ) ) {
			$the_link = next_post_link_plus( $config );
			$the_link = str_replace('DAY_NUMBER', $adjacent_daynumber, $the_link);
			}
		}

		if ( !$the_link ) {
			$the_link = '<span class="a"><span class="icon-arrow-' . $icon . ' stack--navigation--icon"></span></span>';
		}

		return $the_link;
	}

	/**
	 * Support Custom Field %placeholders% in Custom Post Type permalinks
	 * 	This replacement is only applied when the permalink is generated
	 * 	eg on an archive listing or wpadmin edit page
	 *	NOT in the rewrite rules / when the page is loaded
	 *
	 * @param $permalink See WordPress function options
	 * @param $post See WordPress function options
	 * @param $leavename See WordPress function options
	 * @return $permalink
	 *
	 * @example
	 * 	// wpdtrt-dbth/library/register_post_type_tourdiaries.php
	 * 	'rewrite' => array(
	 * 		'slug' => 'tourdiaries/%tours%/%wpdtrt_tourdates_cf_daynumber%'
	 * 		'with_front' => false
	 * 	)
	 *
	 * @see http://shibashake.com/wordpress-theme/add-custom-taxonomy-tags-to-your-wordpress-permalinks
	 * @see http://shibashake.com/wordpress-theme/custom-post-type-permalinks-part-2#conflict
	 * @see https://stackoverflow.com/questions/7723457/wordpress-custom-type-permalink-containing-taxonomy-slug
	 * @see https://kellenmace.com/edit-slug-button-missing-in-wordpress/
	 * @see http://kb.dotherightthing.dan/php/wordpress/missing-permalink-edit-button/
	 */
	public function render_permalink_placeholders($permalink, $post, $leavename) {

		// Get post
		$post_id = $post->ID;

		// extract all %placeholders% from the permalink
		// https://regex101.com/
		preg_match_all('/(?<=\/%wpdtrt_tourdates_cf_).+?(?=%\/)/', $permalink, $placeholders, PREG_OFFSET_CAPTURE);

		// placeholders in an array of taxonomy/term arrays
		foreach ( $placeholders[0] as $placeholder ) {

			$placeholder_name = 'wpdtrt_tourdates_cf_' . $placeholder[0];

			if ( metadata_exists( 'post', $post_id, $placeholder_name ) ) {
				$replacement = get_post_meta( $post_id, $placeholder_name, true );
				$permalink = str_replace( ( '%' . $placeholder_name . '%' ), $replacement, $permalink);
			}
		}

		return $permalink;
	}

    //// END RENDERERS \\\\

    //// START FILTERS \\\\

	/**
	 * Add the ACF day to the post title
	 * @see https://wordpress.org/support/topic/the_title-filter-only-for-page-title-display
	 * @todo: this is outputting into the Primary Navigation menu, need to check !if_menu
	 */

	public function filter_post_title_add_day( $title, $id = NULL ) {

		// http://php.net/manual/en/functions.arguments.php
		//if ( is_null($id) ) {
		//  $day = get_field('acf_daynumber');
		// }
		//else {
		// $day = get_post_field('acf_daynumber', $id);
		//}

		global $post;
		$id = $post->ID;

		$day = $this->get_post_daynumber($id);

		$day_html = '<span class="wpdtrt-tourdates-day theme-text_secondary"><span class="wpdtrt-tourdates-day--day">Day </span><span class="wpdtrt-tourdates-day--number">' . $day . '</span><span class="wpdtrt-tourdates-day--period">, </span></span>';
		$title_html = '<span class="wpdtrt-tourdates-day--title">' . $title . '</span>';
		$simple_title_html = '<span class="wpdtrt-tourdates-day--title">' . $title . '</span>';

		// if in the loop / rendering the post
		// || is_active_widget(false, 'widget_recent_entries')
		if ( $day && in_the_loop() && is_single() && ! is_admin() && ( !is_active_widget() || is_active_widget(false,'widget_recent_entries') ) ) {
			return $day_html . $title_html;
		}
		// if is category listings or similar
		else if ( $day && in_the_loop() && ( is_archive() || is_search() || is_home() ) && ( !is_active_widget() || is_active_widget(false,'widget_recent_entries') ) ) {
			if ( is_admin() ) { // excludes media library
				return $title;
			}
			else {
				return $day_html . $title_html;
			}
		}
		// else if the dashboard etc
		else {
			if ( is_admin() ) {
				return $title;
			}
			else {
				return $simple_title_html;
			}
		}
	}

	/**
	 * 
	 */
	  public function filter_attachment_title_remove_day( $attachment_title = '', $fallback = '' ) {

	    $regex = '/<span class="wpdtrt-tourdates-day theme-text_secondary">.*<\/span><span class="wpdtrt-tourdates-day--title">/';
	    $output = preg_replace($regex, '<span class="wpdtrt-tourdates-day--title">', $attachment_title);
	    $html_len = strlen('<span class="wpdtrt-tourdates-day--title"></span>');
	    $output = trim($output);

	    if ( ( strlen($output) - $html_len ) === 0 ) {
	      $output = $fallback;
	    }

	    return $output;
	  }

	/**
	 * 
	 */
	public function filter_attachment_title_add_day( $attachment_title, $parent_title, $parent_id ) {

		// http://php.net/manual/en/functions.arguments.php
		//if ( is_null($id) ) {
		//  $day = get_field('acf_daynumber');
		// }
		//else {
		// $day = get_post_field('acf_daynumber', $id);
		//}

		global $post;

		$parent_day = $this->get_post_daynumber($parent_id);
		$attachment_title = wpdtrt_tourdates_attachment_title_remove_day( $attachment_title );
		$title_text = 'Gallery image';

		if ( $attachment_title ) {
			$title_text .= ': ' . $attachment_title;
		}

		$day_html = '<span class="wpdtrt-tourdates-day theme-text_secondary"><span class="wpdtrt-tourdates-day--day">Day </span><span class="wpdtrt-tourdates-day--number">' . $parent_day . ': ' . $parent_title . '</span><span class="wpdtrt-tourdates-day--period">. </span></span>';
		$title_html = '<span class="wpdtrt-tourdates-day--title">' . $title_text . '</span>';

		return $day_html . $title_html;
	}

    //// END FILTERS \\\\

    //// START HELPERS \\\\

	/**
	 * Sort term objects by start date
	 * @param {array} $tour_terms Array of terms (e.g. tour legs)
	 * @return {array} $tour_terms Sorted terms
	 * @see https://stackoverflow.com/a/22231045/6850747
	 */
	function helper_order_tour_terms_by_date( $tour_terms ) {

		// usort: Sort an array with a user-defined comparison function
		// uasort: and maintain index association
		// @uasort: suppress PHP Warning: uasort(): Array was modified by the user comparison function
		@uasort( $tour_terms, function( $term_a, $term_b ) {
			$term_a_id = $term_a->term_id;
			$term_a_start_date = $this->get_term_start_date( $term_a_id );

			$term_b_id = $term_b->term_id;
			$term_b_start_date = $this->get_term_start_date( $term_b_id );

			//  compare strings using a 'natural order' algorithm
			return strnatcmp( $term_a_start_date, $term_b_start_date );
		});

		return $tour_terms;
	}

	/**
	 * Add custom rewrite rules
	 * WordPress allows theme and plugin developers to programmatically specify new, custom rewrite rules.
	 *
	 * @see http://clivern.com/how-to-add-custom-rewrite-rules-in-wordpress/
	 * @see https://www.pmg.com/blog/a-mostly-complete-guide-to-the-wordpress-rewrite-api/
	 * @see https://www.addedbytes.com/articles/for-beginners/url-rewriting-for-beginners/
	 * @see http://codex.wordpress.org/Rewrite_API
	 *
	 * @since       1.0.0
	 * @version     1.0.0
	 */
	public function set_rewrite_rules() {

	    global $wp_rewrite;

	    /**
	     * Separate out our custom field, to prevent it from breaking the %tourdiaries% CPT regex
	     * When regex is broken, monkeyman-rewrite-analyzer reports 'Regex is empty!'
	     *
	     * @param $tag %tagname%
	     * @param $regex A regex to validate the value of the tag
	     * @param $query Append query to queryreplace property array (optional)
	     * @see https://codex.wordpress.org/Rewrite_API/add_rewrite_tag
	     */
	    $wp_rewrite->add_rewrite_tag(
	        '%wpdtrt_tourdates_cf_daynumber%',
	        '([^/]+)', // get one or more of any character except slash
	        'wpdtrt_tourdates_cf_daynumber='
	    );
	}

    //// END HELPERS \\\\
}

?>