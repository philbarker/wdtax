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
  # set new template for archives of custom taxonomies.
  # by default will use templates/archive-template.php and  templates/style.css.
  # will look for /wdtax/templates in theme folder first, if they do not exist,
  # will use files from this plugin's folder.
  global $post;
  global $wdtax_dir;
  $theme_dir = get_stylesheet_directory();
  if ( is_tax() ) {
    if ( file_exists( $theme_dir.'/wdtax/templates/archive-template.php' ) ) {
      $archive_template = $theme_dir.'/wdtax/templates/archive-template.php';
    } else {
      $archive_template = $wdtax_dir.'/templates/archive-template.php';
    }
    return $archive_template;
  } else {
    return; // do nothing if not taxonomy archive
  }
}
add_filter( 'archive_template', 'wdtax_custom_taxonomy_template' ) ;

function wdtax_add_stylesheet() {
  $theme_dir = get_stylesheet_directory();
  if ( file_exists( $theme_dir.'/wdtax/templates/style.css' ) ) {
    $src = get_stylesheet_directory_uri().'/wdtax/templates/style.css';
  } else {
    $src = plugins_url('../templates/style.css', __FILE__ );
  }
  wp_register_style( 'wdtax_style', $src, null, null, 'screen' );
  wp_enqueue_style( 'wdtax_style', $src, null, null, 'screen' );
}
add_action('wp_enqueue_scripts', 'wdtax_add_stylesheet');

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

function wdtax_widget_list_terms( $atts ) {
// creates a shortcode wdtax_terms that requires one parameter custom_taxonomy,
// and which will list all the terms in that custom taxonomy.
// To use this shortcode drag and drop a Text widget into your sidebar.
// Add this shortcode in your Widget and save.
// [wdtax_widget_terms custom_taxonomy=customtaxonomyname]
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
add_shortcode( 'wdtax_widget_terms', 'wdtax_widget_list_terms' );
add_filter('widget_text', 'do_shortcode');

function wdtax_list_terms( $atts ) {
  global $wdtax_taxonomies;
  $taxonomy_keys = array_keys($wdtax_taxonomies);
  $tax_arr = array();
  foreach ($taxonomy_keys as $key) {
    $tax_arr[$key] = $wdtax_taxonomies[ $key ]->id;
  }
  extract( shortcode_atts( array( 'custom_taxonomy' => '', ), $atts ) );
  // if $custom_taxonomy = all then print terms from all Taxonomies
  if ( 'all' === $custom_taxonomy ) {
    $args = array (
        'taxonomy' => $tax_arr,
        'orderby' => 'name',
        'order' => 'ASC',
      );
    wdtax_print_terms( $args );
    return;
} elseif ( in_array( $custom_taxonomy, array_keys($tax_arr) ) ) {
  // else check that $custom_taxonomy is a name of an existing taxonomy
    // if so, print out terms from that taxonomy
    $args = array (
        'taxonomy' => $tax_arr[$custom_taxonomy],
        'orderby' => 'name',
        'order' => 'ASC',
      );
    wdtax_print_terms( $args );
    return;
  } else {
    echo $custom_taxonomy.' is not a valid taxonomy name.';
    $result = '<p><strong>Usage:</strong> <br />
               [wdtax_widget_terms custom_taxonomy=customtaxonomyname]</p>
               <p><strong>Known taxonomies are: </strong> all';
    foreach ( array_keys($tax_arr) as $taxonomy ) {
      $result = $result.', '.$taxonomy;
    }
    return $result;

  }
}
add_shortcode( 'wdtax_terms', 'wdtax_list_terms' );

function wdtax_print_terms( $args ) {
  $terms = get_terms( $args );
  echo '<ul>';
  foreach ( $terms as $term ) {
    $url = get_term_link( $term );
    $term_id =  $term->term_id ;
    $class = get_term_meta( $term_id, 'schema_type', true );
    echo '<li class="'.$class.'"><a href="'.$url.'">'.$term->name.'</a> '.$term->description.'</li>';
  }
  echo '<ul>';
}


function wdtax_post_terms( $atts ) {
// creates a shortcode wdtax_post_terms that requires one parameter
// custom_taxonomy, and which will list terms from that custom taxonomy with
// which the current  post has been tagged.
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
  echo '<h1 class="page-title">Index: ';
  echo $wdtax_taxonomy->schema_text( $term_id, 'name' );
  echo '</h1>';
  echo '<div class="taxonomy-description  wdtax-clearfix" > ';
  wdtax_archive_page_image( $term_id );
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
   echo $wdtax_taxonomy->schema_text($term_id, 'description');
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
		$heading = 'Articles about ';
	} elseif ( 'mentions' == $rel ) {
		$heading = 'Articles that mention ';
	} elseif ( 'citation' == $rel ) {
		$heading = 'Articles citing ';
	} else  {
		$heading = 'Articles that '.$rel.': ';
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
