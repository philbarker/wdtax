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
 	//reads wdtax_options, and returns an array of taxonomies, one for each
	//relationship, by calling wdtax_taxonomy->init() [see inc/taxonomy_class.php]
	//with options from wdtax_options.
	$t_arr = array();  // will be an array of taxonomies
	$options_arr = get_option( 'wdtax_options' );
	//'rels' elmt is array of taxonomy relationships (an index if you like)
	//other elements are arrays of taxonomy options with relationship as key
	if (isset($options_arr['rels'])) {
		$n_rels = sizeof($options_arr['rels']); //we should get this many taxnmies
	} else {
		$message = __('Wikidata Taxonomy plugin installed, but no taxonomies created',
		              'wdtax');
		wdtax_admin_notice( 'notice-info', $message );
		return;
  }
	unset($options_arr['rels']);
  $rels = array_keys( $options_arr );
  foreach ( $rels as $rel ) {
		$options = $options_arr[$rel];
		$types = array_keys($options['wdtax_types']);
		$t_arr[$rel] = new wdtax_taxonomy('wdtax_'.$rel,
																				$types,
																				$rel.' Term',
																				$rel.' Terms');
		$t_arr[$rel]->init(); //registers methods with various init hooks
	}
	if ( 0 === sizeof($t_arr) ) {
		$messsage = __('No Wikidata Taxonomy is assigned to a post type','wdtax');
		wdtax_admin_notice( 'notice-info', $messsage );
		return;
	} elseif ( sizeof($t_arr) !== $n_rels) {
		$messsage = __('Some Wikidata Taxonomies are not assigned to a post type','wdtax');
		wdtax_admin_notice( 'notice-info', $messsage );
		return $t_arr;
	} else {
		return $t_arr;
	}
}
