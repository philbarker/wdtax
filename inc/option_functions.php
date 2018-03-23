<?php
/*
 * Package Name: wdtax
 * Description: functions related to displaying wikidata taxonomies
 * Version: 0
 * Since: mvp1
 * Author: Phil Barker
 * Author URI: http://people.pjjk.net/phil
 * @license GPL 2.0+
*/
defined( 'ABSPATH' ) or die( 'Be good. If you can\'t be good be careful' );

/***
 * functions to register options and create a settings page:
 *   wdtax_settings_init() sets up the settings, hooked into admin_init
 *   echo_wdtax_section() echos html for settings section
 *   echo_wdtax_rel( $args ) echos html for relationship settings
 *   wdtax_options_page() adds page to options menu, hooked into admin_menu
 *   echo_wdtax_options_html() html for the wdtax options page
 * based on https://developer.wordpress.org/plugins/settings/custom-settings-page/
 */

function wdtax_settings_init() {
  //sets up the settings
  register_setting( 'wdtax', 'wdtax_options'); //option_group, option_name
  add_settings_section(
    'wdtax',  //section id
    __( 'Create Custom Taxonomies for', 'wdtax' ), //Section title
    'echo_wdtax_section', //callback func to display html for section
    'wdtax' //slug for options page
  );
  add_settings_field(
    'wdtax_taxonomies',  //field id; this one for relationship of taxons to post
    __( 'Relationship', 'wdtax'), //field label
    'echo_wdtax_rel',  //callback function to html for field
    'wdtax',  //slug for options page
    'wdtax',  //id of section
    [  //params passed to callback function as args
      'label_for'=>'wdtax_rel',  // @for attrib for form label tag
      'class'=>'wdtax_row',      // @class attrib for tr tag in form
    ]
  );
  $options_arr = get_option( 'wdtax_options' );
  if ( isset( $options_arr['rels'] ) ) {
    $rels_set = $options_arr['rels'];
  } else {
    $rels_set = array();
  }
  foreach ( $rels_set as $rel ) {
    wdtax_add_fields($rel);
  }
}
add_action( 'admin_init', 'wdtax_settings_init');

function wdtax_add_fields($n) {
  add_settings_section(
    'wdtax'.$n,  //section id
    __( 'What types of post is the \''.$n.'\' taxonomy used on?', 'wdtax' ),
                 //Section title
    'echo_wdtax_section', //callback func to display html for section
    'wdtax' //slug for options page
  );
  add_settings_field(
    'wdtax_type',        //field id; this one for types of post to show taxmy on
    __('Use on these types of post:', 'wdtax' ), //field label
    'echo_wdtax_type_field',  //callback function to html for field
    'wdtax',  //slug for options page
    'wdtax'.$n,  //id of section
    [  //params passed to callback function as args
      'label_for'=>'wdtax_type',  // @for attrib for form label tag
      'class'=>'wdtax_row',    // @class attrib for tr tag in form
      'taxonomy_n'=>$n            // array number of taxonomy in wdtax_options
    ]
  );
}
function echo_wdtax_section() {
  // callback from add_settings_section, echos html for settings section

}
function echo_wdtax_rel( $args ) {
  // callback from add_settings_field, echos html for relationship settings
  $options_arr = get_option( 'wdtax_options' );
  if ( isset( $options_arr['rels'] ) ) {
    $rels_set = $options_arr['rels'];
  } else {
    $rels_set = array();
  }
  $rels_arr = array('about','mentions','citation','isBasedOn');
//to do set checked from options
  foreach ($rels_arr as $rel) {
    if (isset( $rels_set[$rel] ) && $rels_set[ $rel ]) {
      $checked = 'checked = "checked"';
    } else {
      $checked = '';
    }
    echo '<input type="checkbox"
                 name="wdtax_options[rels]['.$rel.']"
                 id="'.$rel.'"
                 value="'.$rel.'"
                 '.$checked.' /> ';
    echo $rel.'<br />';
  }
  echo '<p class="description">';
  echo esc_html_e( 'The relationships between posts and terms ', 'wporg' );
  echo esc_html_e( 'for which you want taxonomies. ' , 'wporg' );
  echo '<br />';
  echo esc_html_e( ' Used for schema.org markup.', 'wporg' );
  echo '</p>';
  submit_button( 'Create Taxonomies' );
}

function echo_wdtax_type_field( $args ) {
  // callback from add_settings_field, echos html for relationship settings
  $options_arr = get_option( 'wdtax_options' );
  $taxonomy_n = $args['taxonomy_n'];
  if ( isset ($options_arr[$taxonomy_n] ) ) {
    $options = $options_arr[$taxonomy_n];
  } else {
    $options = array();
  }
  $post_types = array_keys( get_post_types( ['public'=>True] ) );
  foreach ( $post_types as $type) {
    $option_name = esc_attr('wdtax_options['.$taxonomy_n.'][wdtax_types]['.$type.']');
    $option_id = esc_attr( $type );
    if (isset( $options[ 'wdtax_types' ][ $type ]) ) {
          $checked = 'checked = "checked"';
    } else {
      $checked = '';
    }
    echo '<input type="checkbox"
                 name='.$option_name.'
                 id="'.$option_id.'"
                 value="'.$option_id.'"
                 '.$checked.' />';
    echo $type.'<br />';
  }
  submit_button( 'Save Taxonomies' );
}

function wdtax_options_page() {
  //add page to options menu
  add_submenu_page(
    'options-general.php', //slug of parent page
    __('Wikidata Taxonomy settings', 'wdtax'), //page title
    __('wdTaxonomy', 'wdtax'), //menu text
    'manage_options', //capability
    'wdtax', //menu slug
    'echo_wdtax_options_html' //callback function; echos html for page
  );
}
add_action( 'admin_menu', 'wdtax_options_page' );

function echo_wdtax_options_html() {
  //html for the wdtax options page
  if ( ! current_user_can( 'manage_options' ) ) {
    return;  //do nothing if the user does not have authority to manage options
  }
  echo '<div class="wrap">';
  echo '<h1>';
  echo esc_html( get_admin_page_title() );
  echo '</h1>';
  echo '<form action="options.php" method="post">';
  // output security fields for the registered setting "wporg"
  settings_fields( 'wdtax' );
  // output setting sections and their fields
  do_settings_sections( 'wdtax' );
  echo '</form>';
  echo '</div>';
}
