<?php
/*
 * Plugin Name: Wikidata Taxonomies
 * Plugin URI: https://github.com/philbarker/wdtax
 * Description: Wikidata enhanced taxonomies for linked data indexes in WordPress
 * Version: 0
 * Author: Phil Barker
 * Author URI: http://people.pjjk.net/phil
 * @license GPL 2.0+
 */

defined( 'ABSPATH' ) or die( 'Be good. If you can\'t be good be careful' );

$wdtax_dir = plugin_dir_path( __FILE__ );
include_once( $wdtax_dir.'inc/option_functions.php');
include_once( $wdtax_dir.'inc/taxonomy_class.php' );
include_once( $wdtax_dir.'inc/wikidata_class.php' );
include_once( $wdtax_dir.'inc/display_functions.php' );
$wdtax_taxonomies = wdtax_init_taxonomies( );

function wdtax_init_taxonomies( ) {
	$t_arr = array();  // will be an array of taxonomies
	$options_arr = get_option( 'wdtax_options' ); //returns False if no options
  if ( $options_arr ) {
    $keys = array_keys( $options_arr );
  } else {
    $keys = array();
  }
  foreach ( $keys as $rel ) {
		if ('rels' !== $rel){  //'rels' elemnt in options arrary is list of
			                     //relationships, not taxonomy options.
			$options = $options_arr[$rel];
		} else {
			$options = '';
		}
		if ( isset( $options['wdtax_types'] ) ) {
			$types = array_keys($options['wdtax_types']);
			$t_arr[$rel] = new wdtax_taxonomy('wdtax_'.$rel,
																					$types,
																					$rel.' Term',
																					$rel.' Terms');
			$t_arr[$rel]->init(); //registers methods with various init hooks
		} else {
		//need error trapping here
		}
	}
	return $t_arr;
}
