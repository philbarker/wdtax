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
  //next: keys used for storing wikidata properties as term metadata mapped
  //to key in $taxonomy->properties array, human label, schema id, ?wd id?...
  public $property_map = array(
    'wd_id' => ['id', 'Wikidata ID', 'sameAs'],
    'wd_description' => ['description', 'Description', 'description'],
    'wd_name' => ['label', 'Name', 'name'],
    'wd_image' => ['image', 'Image', 'image'],
    'wd_type' => ['type', 'Type', ''],
    'wd_birth_year' => ['dob', 'Year of birth', 'birthDate'],
    'wd_death_year' => ['dod', 'Year of death', 'deathDate'],
    'wd_birth_place' =>['pob', 'Place of birth', 'birthPlace'],
    'wd_birth_country' => ['cob', 'Country of birth', 'birthPlace'],
    'wd_death_place' => ['pod', 'Place of death', 'deathPlace'],
    'wd_death_country' => ['cod', 'Country of death', 'deathPlace'],
    'wd_viaf' => ['viaf', 'VIAF', 'sameAs'],
    'wd_isni' => ['isni', 'ISNI', 'sameAs']
  );
  // $type map maps wikidata class labels to schema Types, most specific first
  public $type_map = array(
    'book' => 'Book',
    'creative work' => 'CreativeWork',
    'human' => 'Person'
  );
  public $generic_properties = array(
    'id' =>'',     //id (wikidata Q#)
    'label' =>'',  //label or name
    'description' => '', //description text
    'image' => '', // url to image
    'type' =>''    // class in wikidata
  );
  public $human_properties = array(
    'dob' => '', // date of birth
    'pob' => '', // place of birth
    'cob' => '', // country of birth
    'dod' => '', // date of death
    'pod' => '', // place of death
    'cod' => '', // country of death
    'viaf' => '', // VIAF id
    'isni' => '' // ISNI id
  );
  public $generic_property_types = array(
    'label'=>'',
    'description'=>'',
    'type'=>'Label'
  );
  public $human_property_types = array(
    'label'=>'',
    'description'=>'',
    'type'=>'Label',
    'dob'=>'Year',
    'pob'=>'Label',
    'cob'=>'Label',
    'dod'=>'Year',
    'pod'=>'Label',
    'cod'=>'Label',
    'viaf' => '',
    'isni' => ''
  );
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
    //$action = array($this, 'save_meta');
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
    echo $this->id;
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
            print_r( '<br />' );
            unset( $key );
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
      unset( $key );
    }
    return;
  }
  function fetch_store_wikidata( $wd_id, $term_id ) {
   // will fetch wikidata for $wd_id, which should be wikidata identifier (Q#)
   // and will store relevant data as proerties/metadata for taxonomy term
   //
   $property_types = array(
         'label'=>'',
         'description'=>'',
        'image' => '',
        'type'=>'Label'
       );

    $p_map = $this->property_map;
    $props = $this->generic_properties;
    $types = $this->generic_property_types;
    $this->delete_term_metadata( $term_id );
    $wd = new wdtax_generic_wikidata( $wd_id, $props, $types );
    $wd->store_term_data( $term_id, $this->id ); //update term name and descr
    $wd->store_property( $term_id, 'wd_id', $p_map['wd_id'][0]);
    $wd->store_property( $term_id, 'wd_description', $p_map['wd_description'][0] );
    $wd->store_property( $term_id, 'wd_name', $p_map['wd_name'][0] );
    $wd->store_property( $term_id, 'wd_image', $p_map['wd_image'][0] );
    $wd->store_property( $term_id, 'wd_type', $p_map['wd_type'][0] );
    $wd_type = get_term_meta( $term_id, 'wd_type', true );
    if ( 'human' === $wd_type ) {
      $props = array_merge($this->generic_properties, $this->human_properties);
      $types = $this->human_property_types;
      $wd = new wdtax_human_wikidata( $wd_id, $props, $types );
      $wd->store_property( $term_id, 'wd_birth_year', $p_map['wd_birth_year'][0] );
    	$wd->store_property( $term_id, 'wd_death_year', $p_map['wd_death_year'][0] );
    	$wd->store_property( $term_id, 'wd_birth_place', $p_map['wd_birth_place'][0] );
    	$wd->store_property( $term_id, 'wd_birth_country', $p_map['wd_birth_country'][0] );
    	$wd->store_property( $term_id, 'wd_death_place', $p_map['wd_death_place'][0] );
      $wd->store_property( $term_id, 'wd_death_country', $p_map['wd_death_country'][0] );
      $wd->store_property( $term_id, 'wd_viaf', $p_map['wd_viaf'][0] );
      $wd->store_property( $term_id, 'wd_isni', $p_map['wd_isni'][0] );
    } else {
    }
  }
  function schema_type( $term_id ) {
    //echo the value of schema type that wd_type maps to as value for @typeof
    $term_meta = get_term_meta( $term_id );
    if ( isset( $term_meta['wd_type'] )
      && isset( $this->type_map[$term_meta['wd_type'][0]] )
      ) {
      $type = $this->type_map[$term_meta['wd_type'][0]];
      return ' typeof="'.$type.'" ';
    } else {
      return ' typeof="Thing" ';
    }
  }
  function schema_text( $term_id, $p, $args=array() ) {
    // echo property $p of a term as schema markup for property to which $p maps
    // in $property_map, in html tag $args['tag'] or span as defaul, with
    // text $args['before'|'after'] before or after $p. $args['class'] can be
    // used for @class of html element
    if ( isset( $args['tag'] ) ) {
      $tag = strtolower( $args['tag'] );
    } else {
      $tag = 'span';
    }
    if ( isset( $args['class'] ) ) {
      $class = $args['class'];
    } else {
      $class = null;
    }
    if ( isset( $args['before'] ) ) {
      $before = $args['before'];
    } else {
      $before = null;
    }
    if ( isset( $args['after'] ) ) {
      $after = $args['after'];
    } else {
      $after = '';
    }
    $property_value = get_term_meta( $term_id, $p, true );
    $schema_property = $this->property_map[$p][2];
    if ( 'meta' == $tag ) {
      if (  isset( $schema_property ) ) {
        $opentag = "<meta property=\"{$schema_property}\" content=\"";
        $closetag = '" />';
      } else {
        $opentag = '<meta content="';
        $closetag = '" />';
      }
    } elseif ( ( 'link' === $tag ) || ( 'a'===$tag ) ) {
      if ( isset( $schema_property ) ) {
        $schema_property = urlencode( $schema_property );
        $opentag = "<{$tag} rel=\"{$schema_property}\" href=\"";
        $closetag = '" />';
      } else {
        $opentag = '<'.$tag.' href="';
        $closetag = '" />';
      }
    } elseif ( isset( $tag ) ) {
      $closetag = '</'.$tag.'>';
      $opentag = '<'.$tag;
      if ( isset( $class ) ) {
        $opentag = $opentag.' class='.$class.' ';
      }
      if ( isset( $schema_property ) ) {
        $opentag = $opentag.' property="'.$schema_property.'" ';
      }
      $opentag = $opentag.' >';
    } else {
      $closetag = ' ';
      $opentag = ' ';
    }
    if (  isset( $property_value ) ) {
      return $opentag.$before.$property_value.$after.$closetag;
    } else {
      return '<'.$tag.' class="'.$class.'" >'.$before.'no data'.$after.'</'.$tag.'>';
    }
  }
  function list_all_schema( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    print_r( '<ul>' );
    foreach ( array_keys( $term_meta ) as $key ) {
      print_r( '<li>'.$this->property_map[$key][1].': ');
      print_r( $this->schema_text( $term_id, $key ) );
      print_r( '</li>' );
      unset( $key );
    }
    print_r( '</ul>' );
  }
  function schema_birth_details ( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    $return_val = '';
    if ( isset( $term_meta['wd_birth_year'] ) ) {
      $schema_dob = $this->schema_text( $term_id, 'wd_birth_year' );
      $return_val = $return_val.' Born '.$schema_dob;
    }
    if ( isset( $term_meta['wd_birth_place'] ) ) {
      $cob = get_term_meta( $term_id, 'wd_birth_country', true);
      $args = array( 'before'=>' ', 'after'=>' ('.$cob.')' );
      $schema_pob = $this->schema_text( $term_id, 'wd_birth_place', $args );
      $return_val = $return_val.' '.$schema_pob.'.';
    }
    return $return_val;
  }
  function schema_death_details ( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    $return_val = '';
    if ( isset( $term_meta['wd_death_year'] ) ) {
      $schema_dod = $this->schema_text( $term_id, 'wd_death_year' );
      $return_val = $return_val.' Died '.$schema_dod;
    }
    if ( isset( $term_meta['wd_death_place'] ) ) {
      $cod = get_term_meta( $term_id, 'wd_death_country', true);
      $args = array( 'before'=>' ', 'after'=>' ('.$cod.')' );
      $schema_pod = $this->schema_text( $term_id, 'wd_death_place', $args );
      $return_val = $return_val.' '.$schema_pod.'.';
    }
    return $return_val;
  }
  function schema_sameas_wd( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    $base_url = 'https://www.wikidata.org/entity/';
    if ( isset( $term_meta['wd_id'] ) ) {
      $args = array(
        'tag'=>'link',
        'before'=>$base_url
      );
      return $this->schema_text( $term_id, 'wd_id', $args );
    } else {
      return '';
    }
  }
  function schema_sameas_viaf( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    $base_url = 'https://viaf.org/viaf/';
    if ( isset( $term_meta['wd_viaf'] ) ) {
      $args = array(
        'tag'=>'link',
        'before'=>$base_url
      );
      return $this->schema_text( $term_id, 'wd_viaf', $args );
    } else {
      return '';
    }
  }
  function schema_sameas_isni( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    $base_url = 'http://www.isni.org/';
    if ( isset( $term_meta['wd_isni'] ) ) {
      $args = array(
        'tag'=>'link',
        'before'=>$base_url
      );
      return $this->schema_text( $term_id, 'wd_isni', $args );
    } else {
      return '';
    }
  }
}
