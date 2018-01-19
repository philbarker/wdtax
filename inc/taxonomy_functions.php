<?php
/*
 * Package Name: wdtax
 * Description: functions to set up taxonomies
 * Version: 0
 * Author: Phil Barker
 * Author URI: http://people.pjjk.net/phil
 * @license GPL 2.0+
*/
 
defined( 'ABSPATH' ) or die( 'Be good. If you can\'t be good be careful' );

function wdtax_register_taxonomy($taxonomy, $type, $s_name='', $p_name='') {
/* Registers a taxonomy. Adds a sub menu for it. Adds form fields for wikidata
 * $taxonomy = id of taxonomy to register, $type = types of post to attach it
 * to, $s_name & $p_name = singluar and plural names, used for labels and in
 * description.
 * $s_name and $p_name will default to $taxonomy and $taxonomy.'s'
 */
 	if ( empty( $s_name ) )
 		$s_name = $taxonomy;
 	if ( empty( $p_name ) )
 		$p_name = $taxonomy.'s';
	$args = array(
    	'labels' => array (
	    	'name'          => $p_name,
   			'singular name' => $s_name,
    		'menu_name'     => $p_name
    		),
    	'public'       => true,
    	'hierarchical' => false,
    	'show_admin_column' => true,
    	'show_in_menu' => false,
	    'rewrite'      => array( 'slug' => $p_name ),
	    'description'  => 'Indexes '.$p_name.' mentioned in articles',
	    'sort'         => true
	    );
	register_taxonomy( $taxonomy, $type, $args );
	$title = $p_name.' mentioned.';
	add_submenu_page( 'wdtax_taxonomies', $title, $title, '',
			'edit-tags.php?taxonomy='.$taxonomy.'&post_type='.$type, null);
	add_action( $taxonomy.'_add_form_fields', 'wdtax_add_form_fields', 10, 2 );
	add_action( $taxonomy.'_edit_form_fields', 
	            'wdtax_edit_form_fields', 10, 2 );
	add_action( 'created_'.$taxonomy, 'wdtax_save_meta', 10, 2 );
	add_action( 'edited_'.$taxonomy, 'wdtax_save_meta', 10, 2 );
}

function wdtax_display_admin_page() {
	echo '<div class="wrap">' . PHP_EOL;
	echo '<h2>Index Taxonomies</h2>' . PHP_EOL;
	echo '<p>Choose taxonomy to edit.</p>' . PHP_EOL;
	echo '</div><!-- end .wrap -->' . PHP_EOL;
	echo '<div class="clear"></div>' . PHP_EOL;
}

function wdtax_add_form_fields( $taxonomy ) {
    ?>
    <div class="form-field term-group">
        <label for="wd_id"><?php _e( 'Wikidata ID', 'omniana' ); ?></label>
        <input type="url" id="wd_id" name="wd_id" />
    </div>
    <?php
}

function wdtax_edit_form_fields( $term, $taxonomy ) {
    $wd_id = ucfirst( get_term_meta( $term->term_id, 'wd_id', true ) );
    $wd_name = get_term_meta( $term->term_id, 'wd_name', true ); 
    $wd_description = get_term_meta( $term->term_id, 'wd_description', true ); 
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row">
            <label for="wd_id"><?php _e( 'Wikidata ID', 'omniana' ); ?></label>
        </th>
        <td>
            <input type="text" id="wd_id"  name="wd_id" 
            	   value="<?php echo ucfirst($wd_id); ?>" />
        </td>
    </tr>

<!--JavaScript required so that name and description fields are updated-->
    <script>
	  var f = document.getElementById("edittag");
  	  function updateFields() {
	  	var i = document.getElementById("wd_id");
	  	var n = document.getElementById("name");
  	  	var d = document.getElementById("description");
  		if (i.value.charAt(0) == "Q") {
	  		n.value = "<?php echo($wd_name) ?>";
  			d.innerHTML = "<?php echo($wd_description) ?>";
  		}
  	  }
	  f.onsubmit=updateFields();
	</script>


    <?php
}

function wdtax_save_meta( $term_id, $tag_id ) {
// there is no need to save metadata that comes from wikidata here
// as it is saved when fetched, not edited in form.
    if( isset( $_POST['wd_id'] ) ) {
        update_term_meta( 
        	$term_id, 'wd_id', 
        	ucfirst( esc_attr( $_POST['wd_id'] ) ) 
        );
    }
}
