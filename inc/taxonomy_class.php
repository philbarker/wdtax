<?php
/*
 * Package Name: wdtax
 * Description: class and mehtods for custom taxonomies & their metadata
 * Version: 0
 * Author: Phil Barker
 * Author URI: http://people.pjjk.net/phil
 * @license GPL 2.0+
*/

defined( 'ABSPATH' ) or die( 'Be good. If you can\'t be good be careful' );

class wdtax_taxonomy {
/* class for creating custom taxonomies with admin menus that can take
 * data from wikidata and can be used to provide linked data with schema.org
 * properties such as schema:about, scheam:mentions...
 *
 * After instatiating, the init and and admin init methos should be called to
 * hook various functions to init and admin_init.
 *
 * methods
 * init() : hooks into various init actions
 * register_wdtaxonomy() : registers the taxonomy (called by init)
 * add_form_fields() : fields for add new term form (called by admin_init)
 * edit_form_fields( $wd_term ) : fields for edit term form (called by
 *     admin_init)
 * save_meta() : saves term metadata (called by admin_init)
 * fetch_store_wikidata( $term ) : gets and stores wikidata for
 *           $term in the taxonomy
 */
  protected $id;     //id of the taxonomy
  protected $type;   //types of post to which taxonomy apllies
  protected $args;   //argument array of taxonomy

  function __construct($taxonomy, $type, $s_name='', $p_name='') {
    /* sets up a taxonomy.
    * $taxonomy = id of taxonomy, $type = types of post to attach it
    * to, $s_name & $p_name = singluar and plural names, used for labels and in
    * description.
    * $s_name and $p_name will default to $taxonomy and $taxonomy.'s'
    */
    if ( empty( $s_name ) ) $s_name = $taxonomy;
   	if ( empty( $p_name ) )	$p_name = $taxonomy.'s';
    $this->id = $taxonomy;
    $this->type = $type;
    $this->args = array(
        	'labels' => array (
    	    	'name'          => $p_name,
       			'singular name' => $s_name,
            'menu_name'     => __($p_name, 'wdtax'),
            'all_items'     => __('All '.$p_name, 'wdtax'),
            'edit_item'     => __('Edit '.$s_name, 'wdtax'),
            'view_item'     => __('View '.$s_name, 'wdtax'),
            'update_item'  => __('Update '.$s_name, 'wdtax'),
            'add_new_item'  => __('Add New '.$s_name, 'wdtax'),
            'search_items'  => __('Search '.$p_name, 'wdtax'),
            'popular_items'  => __('Popular '.$p_name, 'wdtax'),
            'separate_items_with_commas' =>
                        __('Separate terms with commas', 'wdtax'),
            'add_or_remove_items' => __('Add or remove '.$p_name, 'wdtax'),
            'choose_from_most_used' => __('Choose from most used '.$p_name,
                                          'wdtax'),
            'not_found'  => __('No '.$p_name.' found', 'wdtax')
        		),
        	'public'       => true,
        	'hierarchical' => false,
        	'show_admin_column' => true,
        	'show_in_menu' => true,
    	    'rewrite'      => array( 'slug' => $p_name ),
    	    'description'  => 'Indexes '.$p_name.' mentioned in articles',
    	    'sort'         => true
    	    );
  }
  function init() {
    /*hooks into various init action*/
    //first, register the taxonomy on init
    add_action( 'init', array( $this, 'register_wdtaxonomy') );
    //add fields to add term form
    $hook = $this->id.'_add_form_fields';
    $action = array($this, 'add_form_fields');
    add_action( $hook, $action, 10, 2);
    //add fields & info to edit term form
    $hook = $this->id.'_edit_form_fields';
    $action = array($this, 'edit_form_fields');
    add_action( $hook, $action, 10, 2 );
    //save term metadata on edit
    $hook = 'edited_'.$this->id;
    $action = array($this, 'on_edit_term');
    add_action( $hook, $action, 10, 2 );
    $hook = 'create_'.$this->id;
    $action = array($this, 'on_create_term');
//    $action = array($this, 'save_meta');
    add_action( $hook, $action, 10, 2 );
    //get and store metadata from wikidata before loading edit form
    $hook = $this->id.'_pre_edit_form';
    $action = array($this, 'pre_edit_form');
    add_action( $hook, $action, 10, 2 );
  }
  function register_wdtaxonomy() {
    /*registers the taxonomy*/
    register_taxonomy( $this->id, $this->type, $this->args );
  }
  function add_form_fields( ) {
    /*fields for the 'add' form*/
      ?>
      <div class="form-field term-group">
          <label for="wd_id"><?php _e( 'Wikidata ID', 'wdtax' ); ?></label>
          <input type="text" id="wd_id" name="wd_id" />
      </div>
      <?php
  }
  function edit_form_fields( $term, $taxonomy ) {
    //add fields & info to edit term form
    //hooked into {$this-id}_edit_form_fields action
    //fires after edit term form fields are displayed
    $term_id = $term->term_id;
    $wd_id = ucfirst( get_term_meta( $term_id, 'wd_id', true ) );
    $wd_name = get_term_meta( $term_id, 'wd_name', true );
    $wd_description = get_term_meta( $term_id, 'wd_description', true );
    $term_meta = get_term_meta( $term->term_id );
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row">
            <label for="wd_id"><?php _e( 'Wikidata ID', 'wdtax' ); ?></label>
        </th>
        <td>
            <input type="text" id="wd_id"  name="wd_id"
            	   value="<?php echo ucfirst($wd_id); ?>" />
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
        <th scope="row">
            Wikidata:
        </th>
        <td>
          <?php
          foreach ( array_keys( $term_meta ) as $key ) {
            print_r( '<b>'.$key.': </b>');
            print_r($term_meta[$key][0]);
            echo '<br />' ;
          }
           ?>
        </td>
    </tr>

    <!--JavaScript required so that name and description fields are updated-->
        <script>
       	  var f = document.getElementById("edittag"); //form
      	  function updateFields() {
            var i = document.getElementById("wd_id");   //id
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
  function on_create_term( $term_id ) {
    /* If new term has wd_id property will fetch metadata from wikidata
     * & store as term metadata
     * hooked in to {$taxonomy}_create_term
     */
    if( isset( $_POST['wd_id'] ) ) {
      $wd_id = ucfirst( esc_attr( $_POST['wd_id'] ) );
      update_term_meta( $term_id, 'wd_id', $wd_id );
      $this->fetch_store_wikidata( $wd_id, $term_id );
    }
  }
  function on_edit_term( $term_id ) {
  /*Hooked into edit term for this taxonomy.
   *Don't save metadata that comes from wikidata when term is editted
   *It is fetched and saved on pre-loading the term edit form, and then
   *edit form is reloaded on update.
   *If you do save metadata from wikidata here, it will call the edit term
   *events, including this method. Which would then call the edit term
   *events, including this method. Which would then call the edit term
   *events, including this method. Which would then call the edit term
   *events, including this method. Which would then call the edit term
   */
    if( isset( $_POST['wd_id'] ) ) {
      $wd_id = ucfirst( esc_attr($_POST['wd_id']) );
      update_term_meta( $term_id, 'wd_id', $wd_id );
    }
  }
  function pre_edit_form( $term ) {
  /* If term has wd_id property will fetch metadata from wikidata
   * & store as term metadata, (which is displayed in edit form)
   * hooked in to {$taxonomy}_pre_edit_form, runs before edit form is loaded
   * see on_edit_term for why this is needed
   */
  	$term_id = $term->term_id;
    $wd_id = ucfirst( get_term_meta( $term_id, 'wd_id', true ) );
   	$args = array();
    if( isset( $wd_id ) ) {
      $this->fetch_store_wikidata( $wd_id, $term_id );
    }
  }
  function delete_term_metadata( $term_id ) {
    foreach ( array_keys( get_term_meta( $term_id ) ) as $key ) {
      delete_term_meta( $term_id, $key );
    }
    return;
  }
  function fetch_store_wikidata( $wd_id, $term_id ) {
  /* will fetch wikidata for $wd_id, which should be wikidata identifier (Q#)
   * and will store relevant data as proerties/metadata for taxonomy term
   */
    $this->delete_term_metadata( $term_id );
    $wikidata = new wdtax_wikidata( $wd_id );
    $wikidata->store_id( $term_id );
    $wikidata->store_term_data( $term_id, $this->id );
    $wikidata->store_property( $term_id, 'id', 'wd_id' );
    $wikidata->store_property( $term_id, 'description', 'wd_description' );
    $wikidata->store_property( $term_id, 'label', 'wd_name' );
    $wikidata->store_property( $term_id, 'type', 'wd_type' );
    $wd_type = get_term_meta( $term_id, 'wd_type', true );
    if ( 'human' === $wd_type ) {
//      echo 'we have a human';
//      add human properties & their types to $wikidata
      $wikidata->reconstruct_human();
      $wikidata->store_property( $term_id, 'dob', 'wd_birth_year' );
    	$wikidata->store_property( $term_id, 'dod', 'wd_death_year' );
    	$wikidata->store_property( $term_id, 'pob', 'wd_birth_place' );
    	$wikidata->store_property( $term_id, 'cob', 'wd_birth_country' );
    	$wikidata->store_property( $term_id, 'pod', 'wd_death_place' );
    	$wikidata->store_property( $term_id, 'cod', 'wd_death_country' );
    } else {
//      echo 'dont know this type';
    }
  }
}
