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
include_once( $wdtax_dir.'inc/wikidata_functions.php' );


$wd_about_taxonomy = new wdtax_taxonomy('wdtax_about',
	                             'post',
															 'About Term',
															 'About Terms');
$wd_about_taxonomy->init(); //registers methods with init hook
$wd_about_taxonomy->admin_init(); //registers methods with admin_init hook

//	wdtax_register_taxonomy('wdtax_places', 'chapter', 'place', 'places');
//	wdtax_register_taxonomy('wdtax_events', 'chapter', 'event', 'events');
//	wdtax_register_taxonomy('wdtax_works', 'chapter', 'work', 'works');

/*
function wdtax_about_edit_form_fields( $term, $taxonomy ) {
    $wd_id = ucfirst( get_term_meta( $term->term_id, 'wd_id', true ) );
    $wd_name = get_term_meta( $term->term_id, 'wd_name', true );
    $wd_description = get_term_meta( $term->term_id, 'wd_description', true );
    $wd_birth_year  = get_term_meta( $term->term_id, 'wd_birth_year', true );
    $wd_birth_place  = get_term_meta( $term->term_id, 'wd_birth_place', true );
    $wd_birth_country = get_term_meta( $term->term_id, 'wd_birth_country', true );
    $wd_death_year  = get_term_meta( $term->term_id, 'wd_death_year', true );
    $wd_death_place  = get_term_meta( $term->term_id, 'wd_death_place', true );
    $wd_death_country = get_term_meta( $term->term_id, 'wd_death_country', true );
    $wd_VIAF  = get_term_meta( $term->term_id, 'wd_VIAF', true );
    $wd_ISNI  = get_term_meta( $term->term_id, 'wd_ISNI', true );
    ?>
    <tr class="form-field term-group-wrap">
    	<th>From Wikidata</th>
        <td><strong>Born: </strong><?php echo $wd_birth_year; ?>.
        	                     <?php echo $wd_birth_place ?>.
        	                     <?php echo $wd_birth_country ?>.
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
    	<td></td>
        <td><strong>Died: </strong><?php echo $wd_death_year; ?>.
        	                     <?php echo $wd_death_place; ?>.
        	                     <?php echo $wd_death_country; ?>.
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
    	<td></td>
        <td><strong>Identifiers: </strong> VIAF <?php echo $wd_VIAF; ?>;
        	                     ISNI <?php echo $wd_ISNI; ?>.
        </td>
    </tr>

    <?php
}
add_action( 'wdtax_about_edit_form_fields', 'wdtax_about_edit_form_fields', 20, 2 );

function wdtax_get_person_wikidata( $term ) {
	$term_id = $term->term_id;
    $wd_id = ucfirst( get_term_meta( $term_id, 'wd_id', true ) );
   	$args = array();
	$person_wd = new wdtax_person_wikidata( $wd_id, $term_id );
	$person_wd->store_term_data( );
	$person_wd->store_property( 'description', 'wd_description' );
	$person_wd->store_property( 'label', 'wd_name' );
	$person_wd->store_property( 'dob', 'wd_birth_year' );
	$person_wd->store_property( 'dod', 'wd_death_year' );
	$person_wd->store_property( 'pob', 'wd_birth_place' );
	$person_wd->store_property( 'cob', 'wd_birth_country' );
	$person_wd->store_property( 'pod', 'wd_death_place' );
	$person_wd->store_property( 'cod', 'wd_death_country' );
}
add_action( 'wdtax_people_pre_edit_form', 'wdtax_get_person_wikidata' );
*/
