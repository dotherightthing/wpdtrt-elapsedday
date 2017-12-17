<?php
/**
 * Unit tests, using PHPUnit and wp-cli.
 *
 * @package wpdtrt_tourdates
 * @see http://kb.dotherightthing.dan/php/wordpress/php-unit-testing-revisited/ - Links
 * @see http://richardsweeney.com/testing-integrations/
 * @see https://gist.github.com/benlk/d1ac0240ec7c44abd393 - Collection of notes on WP_UnitTestCase
 * @see https://core.trac.wordpress.org/browser/trunk/tests/phpunit/includes/factory.php
 * @see https://core.trac.wordpress.org/browser/trunk/tests/phpunit/includes//factory/class-wp-unittest-factory-for-term.php
 */

/**
 * TourdatesTest unit tests, using PHPUnit, wp-cli, WP_UnitTestCase
 * Note that the plugin is 'active' within a WP test environment
 * so the plugin class has already been instantiated
 * with the options set in wpdtrt-tourdates.php
 * Note: only function names prepended with test_ are run
 * $debug logs are output with the test output in Terminal
 */
class TourdatesTest extends WP_UnitTestCase {

    /**
     * SetUp
     * Automatically called by PHPUnit before each test method is run
     *
     * @see https://codesymphony.co/writing-wordpress-plugin-unit-tests/#object-factories
     */
    public function setUp() {
  		// Make the factory objects available.
        parent::setUp();

        $this->taxonomy = $this->create_taxonomy();

		// store results as properties, for sharing between test methods
		$this->taxonomy_name = 'wpdtrt_tourdates_taxonomy_tour'; // $taxonomy->get_name()
		$this->region_term_id = $this->mock_region_term();
		$this->tour_term_id = $this->mock_tour_term();
		$this->tour_leg_term_id = $this->mock_tour_leg_term();
    }

    /**
     * TearDown
     * Automatically called by PHPUnit after each test method is run
     *
     * @see https://codesymphony.co/writing-wordpress-plugin-unit-tests/#object-factories     
     */
    public function tearDown() {

    	// prevents error presumably due to existing terms being added again
    	wp_delete_term( $this->region_term_id, $this->taxonomy_name );
    	wp_delete_term( $this->tour_term_id, $this->taxonomy_name );
    	wp_delete_term( $this->tour_leg_term_id, $this->taxonomy_name );
    }

    public function create_taxonomy() {
		$taxonomy = wpdtrt_tourdates_taxonomy_tour_init();

		return $taxonomy;
    }

    // ########## MOCK DATA ########## //

 	/**
	 * Mock a custom post type
	 *
	 * @todo ...
	 */

 	/**
	 * Mock a region
	 *
	 * @return number $term_id
	 */
	public function mock_region_term() {

		$term_id = $this->factory->term->create([
			'name' => 'Asia',
			'taxonomy' => $this->taxonomy_name,
			'slug' => 'asia'
		]);

		update_term_meta($term_id, 'term_type', 'region');

		return $term_id;
	}

	/**
	 * Mock a tour
	 *
	 * @return number $term_id
	 */
	public function mock_tour_term() {

		$term_id = $this->factory->term->create([
			'taxonomy' => $this->taxonomy_name,
			'name' => 'East Asia (2015-2016)',
			'slug' => 'east-asia',
			'parent' => $this->region_term_id,
			'description' => 'Russia - Mongolia - China - Hong Kong -Japan - New Zealand (298 days)'
		]);

		update_term_meta($term_id, 'term_type', 'tour');
		update_term_meta($term_id, 'start_date', '2015-9-2');
		update_term_meta($term_id, 'end_date', '2016-6-25');
		update_term_meta($term_id, 'first_visit', '');
		update_term_meta($term_id, 'leg_count', '6');
		update_term_meta($term_id, 'thumbnail_id', '');

		return $term_id;
	}

	/**
	 * Mock a tour_leg
	 *
	 * @return number $term_id
	 */
	public function mock_tour_leg_term() {

		$term_id = $this->factory->term->create([
			'taxonomy' => $this->taxonomy_name,
			'name' => 'China',
			'slug' => 'china-2',
			'parent' => $this->tour_term_id
		]);

		update_term_meta($term_id, 'term_type', 'tour_leg');
		update_term_meta($term_id, 'start_date', '2015-11-29');
		update_term_meta($term_id, 'end_date', '2016-1-17');
		update_term_meta($term_id, 'first_visit', 1);
		update_term_meta($term_id, 'leg_count', '');
		update_term_meta($term_id, 'thumbnail_id', 926); // this won't exist yet

		return $term_id;
	}

    // ########## TEST ########## //

	/**
	 * Test set_foo()
	 * Checks that we are dealing with the expected config
	 */
	public function test_config() {
		$taxonomy = 		$this->taxonomy;
		$name = 			$taxonomy->get_name();	
		$instance_options = $taxonomy->get_instance_options();
		$labels = 			$taxonomy->get_labels();	
		$plugin = 			$taxonomy->get_plugin();	

		$this->assertTrue( is_string($name) );
		$this->assertEquals( $name, 'wpdtrt_tourdates_taxonomy_tour' );
		$this->assertTrue( taxonomy_exists( $name ) );

		$this->assertTrue( is_array($instance_options) );
		$this->assertEquals( $instance_options, array() );

		$this->assertTrue( is_array($labels) );
		$this->assertEquals( $labels, array(
          'slug' => 'tours',
          'singular' => 'Tour',
          'plural' => 'Tours',
          'description' => 'Multiday rides',
          'posttype' => 'tourdiaries'
        ) );

		$this->assertTrue( is_object($plugin) );
	}

	/**
	 * Test region meta
	 */
	public function test_region_term() {

		$term_id = $this->region_term_id;

		$meta_term_type = get_term_meta( $term_id, 'term_type', true );

		$this->assertEquals( $meta_term_type, 'region' );
	}

	/**
	 * Test tour meta
	 */
	public function test_tour_term() {

		$term_id = $this->tour_term_id;
		$taxonomy = $this->taxonomy;
		$plugin = $taxonomy->get_plugin();

		$meta_term_type = get_term_meta( $term_id, 'term_type', true );
		$meta_start_date = get_term_meta( $term_id, 'start_date', true );
		$meta_end_date = get_term_meta( $term_id, 'end_date', true );
		$meta_first_visit = get_term_meta( $term_id, 'first_visit', true );
		$meta_leg_count = get_term_meta( $term_id, 'leg_count', true );
		$meta_thumbnail_id = get_term_meta( $term_id, 'thumbnail_id', true );

		$this->assertEquals( $meta_term_type, 'tour' );
		$this->assertEquals( $meta_start_date, '2015-9-2' );
		$this->assertEquals( $meta_end_date, '2016-6-25' );
		$this->assertEquals( $meta_first_visit, '' );
		$this->assertEquals( $meta_leg_count, 6 );
		$this->assertEquals( $meta_thumbnail_id, '' );
	}

	/**
	 * Test tour_leg meta
	 */
	public function test_tour_leg_term() {

		$term_id = $this->tour_leg_term_id;
		$taxonomy = $this->taxonomy;
		$plugin = $taxonomy->get_plugin();

		$meta_term_type = get_term_meta( $term_id, 'term_type', true );
		$meta_start_date = get_term_meta( $term_id, 'start_date', true );
		$meta_end_date = get_term_meta( $term_id, 'end_date', true );
		$meta_first_visit = get_term_meta( $term_id, 'first_visit', true );
		$meta_leg_count = get_term_meta( $term_id, 'leg_count', true );
		$meta_thumbnail_id = get_term_meta( $term_id, 'thumbnail_id', true );

		$this->assertEquals( $meta_term_type, 'tour_leg' );
		$this->assertEquals( $meta_start_date, '2015-11-29' );
		$this->assertEquals( $meta_end_date, '2016-1-17' );
		$this->assertEquals( $meta_first_visit, true );
		$this->assertEquals( $meta_leg_count, '' );
		$this->assertEquals( $meta_thumbnail_id, 926 ); // this won't exist yet

		$start_date = $plugin->get_term_start_date( $term_id );
		$end_date = $plugin->get_term_end_date( $term_id );
    	$tour_length_days = $plugin->get_term_days_elapsed( $start_date, $end_date );
    	$tour_length = $plugin->get_tourlengthdays( $term_id );

		$this->assertEquals( $start_date, '2015-11-29 00:01:00' );
		$this->assertEquals( $end_date, '2016-1-17 00:01:00' );
		$this->assertEquals( $tour_length_days, 50 );
		$this->assertEquals( $tour_length, 50 );
	}
}