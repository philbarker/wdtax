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
  for ($n=1; $n<=2; $n++) {
    wdtax_add_fields($n);
  }
}
add_action( 'admin_init', 'wdtax_settings_init');

function wdtax_add_fields($n=1) {
  add_settings_section(
    'wdtax'.$n,  //section id
    __( 'Settings for Custom Taxonomy '.$n, 'wdtax' ), //Section title
    'echo_wdtax_section', //callback func to display html for section
    'wdtax' //slug for options page
  );
  add_settings_field(
    'wdtax_rel',  //field id; this one for relationship of taxons to post
    __( 'Relationship', 'wdtax'), //field label
    'echo_wdtax_rel',  //callback function to html for field
    'wdtax',  //slug for options page
    'wdtax'.$n,  //id of section
    [  //params passed to callback function as args
      'label_for'=>'wdtax_rel',  // @for attrib for form label tag
      'class'=>'wdtax_row',      // @class attrib for tr tag in form
      'taxonomy_n'=>$n            // array number of taxonomy in wdtax_options
    ]
  );
  $post_types = array_keys( get_post_types( ['public'=>True] ) );
  foreach ( $post_types as $type) {
    $field_label = __('Use on '.$type, 'wdtax');
    add_settings_field(
      $type,        //field id; this one for types of post to show taxmy on
      $field_label, //field label
      'echo_wdtax_type_field',  //callback function to html for field
      'wdtax',  //slug for options page
      'wdtax'.$n,  //id of section
      [  //params passed to callback function as args
        'label_for'=>$type,  // @for attrib for form label tag
        'class'=>'wdtax_row',    // @class attrib for tr tag in form
        'taxonomy_n'=>$n            // array number of taxonomy in wdtax_options
      ]
    );
  }
}

function echo_wdtax_section() {
  // callback from add_settings_section, echos html for settings section

}

function echo_wdtax_rel( $args ) {
  // callback from add_settings_field, echos html for relationship settings
  $options_arr = get_option( 'wdtax_options' );
  $taxonomy_n = $args['taxonomy_n'];
  $options = $options_arr[$taxonomy_n];
  $about = isset( $options[ $args['label_for'] ] )
            ? ( selected( $options[ $args['label_for'] ], 'about', false ) )
            : ( '' ); //true if option is set to about
  $mentions = isset( $options[ $args['label_for'] ] )
            ? ( selected( $options[ $args['label_for'] ], 'mentions', false ) )
            : ( '' );  //true if option is set to mentions
  $name = 'wdtax_options['.$taxonomy_n.']['.esc_attr( $args['label_for'].']' );
  ?>
  <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
          name=<?php echo esc_attr( $name ); ?>"
  >
    <option value="about" <?php echo esc_attr( $about ); ?>>
      <?php esc_html_e( 'about', 'wdtax' ); ?>
    </option>
    <option value="mentions" <?php echo esc_attr( $mentions ); ?>>
      <?php esc_html_e( 'mentions', 'wporg' ); ?>
    </option>
 </select>
 <p class="description">
 <?php esc_html_e( 'The relationship between the post and term.', 'wporg' ); ?>
 <?php esc_html_e( 'Used for schema.org markup.', 'wporg' ); ?>
</p>
 <?php
}

function echo_wdtax_type_field( $args ) {
  // callback from add_settings_field, echos html for relationship settings
  $options_arr = get_option( 'wdtax_options' );
  $taxonomy_n = $args['taxonomy_n'];
  $options = $options_arr[$taxonomy_n];
  $option_name = esc_attr('wdtax_options['.$taxonomy_n.'][wdtax_types]['.$args[ 'label_for' ].']');
  $option_id = esc_attr( $args['label_for'] );
  if (isset( $options[ 'wdtax_types' ][ $args['label_for'] ]) ) {
        $checked = 'checked = "checked"';
      } else {
        $checked = '';
      }
  echo '<input type="checkbox"
               name='.$option_name.'
               id="'.$option_id.'"
               value="'.$option_id.'"
               '.$checked.' />';
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
  submit_button( 'Save Taxonomy' );
  echo '</form>';
  $options = get_option( 'wdtax_options' );
  echo '<br/> Options: ';print_r($options);
}
