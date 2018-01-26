<?php
/*
 * Package Name: wdtax
 * Description: functions related to displaying wikidata taxonomies
 * Version: 0
 * Author: Phil Barker
 * Author URI: http://people.pjjk.net/phil
 * @license GPL 2.0+
*/
function get_custom_taxonomy_template( $archive_template ) {
     global $post;
     global $wdtax_dir;
     if ( is_tax ( 'wdtax_about' ) ) {
        $archive_template = $wdtax_dir.'/templates/wdtax-about-template.php';
   }
     return $archive_template;
}
add_filter( 'archive_template', 'get_custom_taxonomy_template' ) ;

function list_terms_custom_taxonomy( $atts ) {
// creates a shortcode ct_terms that requires one parameter custom_taxonomy.
// To use this shortcode drag and drop a Text widget into your sidebar.
// Add this shortcode in your Widget and save.
// [ct_terms custom_taxonomy=customtaxonomyname]
// based on http://www.wpbeginner.com/plugins/how-to-display-custom-taxonomy-terms-in-wordpress-sidebar-widgets/
// we extract custom taxonomy parameter of our shortcode
   extract( shortcode_atts( array(
      'custom_taxonomy' => '',
  ), $atts ) );
  $args = array(
    'taxonomy' => $custom_taxonomy,
    'title_li' => '',
    'echo' => false
  );
  $widgettext = '<ul>'.wp_list_categories($args).'</ul>';
  return $widgettext;
}
add_shortcode( 'ct_terms', 'list_terms_custom_taxonomy' );
add_filter('widget_text', 'do_shortcode');


?>
