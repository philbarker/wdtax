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
?>
