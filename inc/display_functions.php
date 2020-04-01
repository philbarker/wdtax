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

function wdtax_list_terms( $atts ) {
  // creates a shortcode wdtax_list_terms that requires one parameter
  // custom_taxonomy, and which will list terms from that custom taxonomy.
  // use custom_taxonomy = 'all' to list all terms from all taxonomies.
  // To use this shortcode drag and drop a Text widget into your sidebar.
  // Add this shortcode in your Widget and save.
  // [wdtax_list_terms custom_taxonomy='customtaxonomyname']
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

function wdtax_get_the_term_list( $atts ) {
// Returns an HTML string of taxonomy terms associated with a post and
// wdtax taxonomy. Terms are linked to their respective term listing pages.
  extract( shortcode_atts( array( 'custom_taxonomy' => '', ), $atts ) );
  $id = get_the_ID();
  $tax_arr = get_taxonomies(  );
  if ( array_key_exists( $custom_taxonomy, $tax_arr ) ) {
    $post_terms = get_the_term_list($id, $custom_taxonomy, ' ', ' ', ' ');
  } else {
    $post_terms = __('Taxonomy does not exist: ', 'wdtax').$custom_taxonomy;
  }
  return $post_terms;
}
add_shortcode( 'wdtax_post_terms', 'wdtax_get_the_term_list' );
// creates a shortcode wdtax_post_terms that requires one parameter
// custom_taxonomy, and which will list terms from that custom taxonomy with
// which the current  post has been tagged.
// To use this shortcode drag and drop a Text widget into your sidebar.
// Add this shortcode in your Widget and save.
// [wdtax_post_terms custom_taxonomy=customtaxonomyname]
// based on http://www.wpbeginner.com/plugins/how-to-display-custom-taxonomy-terms-in-wordpress-sidebar-widgets/



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


/***
 * Functions for on page indexes
 */
function wdtax_list_the_terms( ) {
  global $wdtax_taxonomies;
  $options_arr = get_option( 'wdtax_options' );
  if ( !isset( $options_arr['rels'] ) ) {
    die('wdtax plugin does not seem have any taxonomies set up');
  }
  $id = get_the_ID();
  $tax_arr = get_taxonomies(  );
  foreach ( $options_arr['rels'] as $rel ) {
    $custom_taxonomy = 'wdtax_'.$rel;
    if ( array_key_exists( $custom_taxonomy, $tax_arr ) ) {
      $wdtax_taxonomy = $wdtax_taxonomies[$rel];
      $post_terms = get_the_terms( $id, $custom_taxonomy );
      if ( $post_terms ) {
        echo '<h4 class="wdtax-relation">'.$rel.'</h4>';
        echo '<ul class="wdtax-terms" vocab="http://schema.org/">';
        foreach ( $post_terms as $term ) {
          $term_id = $term->term_id;
          $term_meta = get_term_meta( $term_id );
          $meta_schema_name = $wdtax_taxonomy->schema_text( $term_id, 'name' );
          $schema_type = implode(" ", $term_meta['schema_type']);
          $args['tag'] = 'meta';
          $meta_schema_id = $wdtax_taxonomy->schema_text( $term_id, 'wd_id', $args );
          $index_url = site_url().'/'.$rel.'-term/'.$term->slug.'/';
          echo '<li class="wdtax-term">' ;
          echo '<a rel="'.$rel.'" href="'.$index_url.' " typeof="'.$schema_type.'">' ;
          echo $meta_schema_id;
          echo $meta_schema_name;
          echo '</a></li>';
        }
        echo '</ul>';
      } else { //no post terms in relevant taxonomy
        ;
      }
    } else { // relevant taxonomy not found
      echo '<p>No taxonomy found for the relationship'.$rel;
      echo ', which is odd.</p>';
    }
  }
}
?>
