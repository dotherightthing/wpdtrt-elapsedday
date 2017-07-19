<?php
/**
 * Functions which generate HTML strings
 *
 * This file contains PHP.
 *
 * @link        http://dotherightthing.co.nz
 * @since       0.1.0
 *
 * @package     WPDTRT_Tourdates
 * @subpackage  WPDTRT_Tourdates/app
 */

if ( !function_exists( 'wpdtrt_tourdates_html_image' ) ) {

  /**
   * Generate the HTML for a (linked) image
   *
   * @param       string $key
   *    The key of the corresponding JSON object
   * @param       boolean $has_enlargement (optional)
   *    Whether the image should link to an enlargement
   * @return      string <a href="..."><img src="..." alt="..."></a>
   *
   * @since       0.1.0
   */
  function wpdtrt_tourdates_html_image( $key, $has_enlargement = 0 ) {

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
}

if ( !function_exists( 'wpdtrt_tourdates_html_latlng' ) ) {

  /**
   * Get the coordinates of a map location
   *
   * @param string $key
   *    The key of the JSON object.
   * @return      string "lat,lng" | ""
   *
   * @since       0.1.0
   */
  function wpdtrt_tourdates_html_latlng( $key ) {

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
}

if ( !function_exists( 'wpdtrt_tourdates_html_title' ) ) {

  /**
   * Generate an Alt attribute
   *
   * @param       string $key
   *    The key of the JSON object.
   * @param       boolean $has_enlargement (optional)
   *    Whether the image should link to an enlargement
   * @return      string The title
   *
   * @since       0.1.0
   */
  function wpdtrt_tourdates_html_title( $key, $has_enlargement = 0 ) {

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
}

if ( !function_exists( 'wpdtrt_tourdates_html_date' ) ) {

  /**
   * Generate the HTML for the last modified date
   *
   * @return      string <p class="wpdtrt_soundcloud_pages_date">Last updated 23rd April 2017</p>
   *
   * @since       0.1.0
   */
  function wpdtrt_tourdates_html_date() {

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
}

?>
