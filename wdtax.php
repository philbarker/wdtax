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
//include_once( $wdtax_dir.'inc/settings.php');
include_once( $wdtax_dir.'inc/taxonomy_class.php' );
include_once( $wdtax_dir.'inc/wikidata_class.php' );
include_once( $wdtax_dir.'inc/display_functions.php' );
$wdtax_taxonomies = wdtax_init_taxonomies( );

function wdtax_init_taxonomies( ) {
	$t_arr = array();  // will be an array of taxonomies
	$options_arr = get_option( 'wdtax_options' );
	foreach ($options_arr as $options) {
		if (isset( $options )) {
			if (isset( $options['wdtax_rel'] )) {
				$rel = $options['wdtax_rel'];
			}
			if ( isset( $options['wdtax_types'] )
			&&  isset( $rel ) ) {
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
	}
	return $t_arr;
}


/*$wdtax_mentions_taxonomy = new wdtax_taxonomy('wdtax_mentions',

		                             'post',
																 'Mentions Term',
																 'Mentions Terms');
$wdtax_mentions_taxonomy->init(); //registers methods with various init hooks
*/
