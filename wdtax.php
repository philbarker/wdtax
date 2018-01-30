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
include_once( $wdtax_dir.'inc/taxonomy_class.php' );
include_once( $wdtax_dir.'inc/wikidata_class.php' );
include_once( $wdtax_dir.'inc/display_functions.php' );

$wdtax_about_taxonomy = new wdtax_taxonomy('wdtax_about',
		                             'post',
																 'About Term',
																 'About Terms');
$wdtax_about_taxonomy->init(); //registers methods with various init hooks
