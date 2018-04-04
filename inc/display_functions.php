<?php
/*
 * Package Name: wdtax
 * Description: functions related to displaying wikidata taxonomies
 * Version: 0
 * Since: mvp0
 * Author: Phil Barker
 * Author URI: http://people.pjjk.net/phil
 * @license GPL 2.0+
*/
defined( 'ABSPATH' ) or die( 'Be good. If you can\'t be good be careful' );

function wdtax_custom_taxonomy_template( $archive_template ) {
  global $post;
  global $wdtax_dir;
  $archive_template = $wdtax_dir.'/templates/wdtax-template.php';
  return $archive_template;
}
add_filter( 'archive_template', 'wdtax_custom_taxonomy_template' ) ;

function wdtax_admin_notice( $class, $msg ) {
  if ( 'notice-error'== $class ) {
    $message = __('wdtax error: ', 'wdtax').$msg;
  } elseif ( 'notice-warning'== $class ) {
    $message = __('wdtax warning: ', 'wdtax').$msg;
  } elseif ( 'notice-info'== $class ) {
    $message = __('wdtax info: ', 'wdtax').$msg;
  } else {
    $message = $msg;
  }
      ?>
    <div class="notice <?php echo esc_attr( $class ) ?> is-dismissible">
        <p><?php echo esc_html( $message ); ?></p>
    </div>
    <?php
}

function wdtax_list_terms( $atts ) {
// creates a shortcode wdtax_terms that requires one parameter custom_taxonomy.
// To use this shortcode drag and drop a Text widget into your sidebar.
// Add this shortcode in your Widget and save.
// [wdtax_terms custom_taxonomy=customtaxonomyname]
// based on http://www.wpbeginner.com/plugins/how-to-display-custom-taxonomy-terms-in-wordpress-sidebar-widgets/
// we extract custom taxonomy parameter of our shortcode
  extract( shortcode_atts( array( 'custom_taxonomy' => '', ), $atts ) );
  $args = array(
    'taxonomy' => $custom_taxonomy,
    'title_li' => '',
    'echo' => false
  );
  $widgettext = '<ul>'.wp_list_categories($args).'</ul>';
  return $widgettext;
}
add_shortcode( 'wdtax_terms', 'wdtax_list_terms' );
add_filter('widget_text', 'do_shortcode');

function wdtax_post_terms( $atts ) {
// creates a shortcode wdtax_post_terms that requires one parameter
// custom_taxonomy.
// To use this shortcode drag and drop a Text widget into your sidebar.
// Add this shortcode in your Widget and save.
// [wdtax_post_terms custom_taxonomy=customtaxonomyname]
// based on http://www.wpbeginner.com/plugins/how-to-display-custom-taxonomy-terms-in-wordpress-sidebar-widgets/
  extract( shortcode_atts( array( 'custom_taxonomy' => '', ), $atts ) );
  $id = get_the_ID();
  $tax_arr = get_taxonomies(  );
  if ( array_key_exists( $custom_taxonomy, $tax_arr ) ) {
    $widgettext = get_the_term_list($id, $custom_taxonomy, ' ', ' ', ' ');
  } else {
    $widgettext = __('Taxonomy does not exist: ', 'wdtax').$custom_taxonomy;
  }
  return $widgettext;
}
add_shortcode( 'wdtax_post_terms', 'wdtax_post_terms' );

/***
 * Functions for taxonomy archive pages
 */
function wdtax_archive_page_header( $term_id ) {
  global $wdtax_taxonomies;
  $term = get_term( $term_id );
  $wdtax_rel = str_replace('wdtax_','',$term->taxonomy);
  $wdtax_taxonomy = $wdtax_taxonomies[$wdtax_rel];
  $type = get_term_meta( $term_id, 'schema_type', True );
  echo '<h1 class="page-title">Index page for ';
  echo $wdtax_taxonomy->schema_text( $term_id, 'wd_name' );
  echo '</h1>';
  echo '<div class="taxonomy-description" > ';
  if ( 'Person' === $type ){
   echo $wdtax_taxonomy->schema_person_details( $term_id );
  } elseif ('Organization' === $type ) {
   echo $wdtax_taxonomy->schema_organization_details( $term_id );
  } elseif ('Book' === $type ) {
   echo $wdtax_taxonomy->schema_book_details( $term_id );
  } elseif ('CreativeWork' === $type ) {
   echo $wdtax_taxonomy->schema_creativework_details( $term_id );
  } elseif ('Place' === $type ) {
   echo $wdtax_taxonomy->schema_place_details( $term_id );
  } elseif ('Event' === $type ) {
   echo $wdtax_taxonomy->schema_event_details( $term_id );
  } else {
   echo $wdtax_taxonomy->schema_text($term_id, 'wd_description');
  }
  echo '</div>';
  echo $wdtax_taxonomy->schema_sameas_all( $term_id );
}
function wdtax_archive_section_heading( $term_id, $rel ) {
  global $wdtax_taxonomies;
  $term = get_term( $term_id );
  $wdtax_rel = str_replace('wdtax_','',$term->taxonomy);
  $wdtax_taxonomy = $wdtax_taxonomies[$wdtax_rel];
	if ('about' == $rel) {
		$heading = 'Pages about ';
	} elseif ( 'mentions' == $rel ) {
		$heading = 'Pages that mention ';
	} elseif ( 'citation' == $rel ) {
		$heading = 'Pages citing ';
	} else  {
		$heading = 'Pages that '.$rel.': ';
	}
	echo '<h2>'.$heading.$term->name.'</h2>';
}
function wdtax_archive_page_image( $term_id ) {
  global $wdtax_taxonomies;
  $term = get_term( $term_id );
  $wdtax_rel = str_replace('wdtax_','',$term->taxonomy);
  $wdtax_taxonomy = $wdtax_taxonomies[$wdtax_rel];
  echo $wdtax_taxonomy->schema_image( $term_id );
}
?>
