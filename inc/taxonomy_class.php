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
  * After instatiating, the init  method should be called to
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
  //to key in $taxonomy->properties array, human label, schema id
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
    'wd_place' => ['place', 'Place', 'location'],
    'wd_country' => ['country', 'Country', 'location'],
    'wd_date' => ['date', 'date', 'startDate'],
    'wd_viaf' => ['viaf', 'VIAF', 'sameAs'],
    'wd_isni' => ['isni', 'ISNI', 'sameAs'],
    'wd_pub_date' => ['pubdate', 'Year of publication', 'datePublished'],
    'wd_author' => ['author', 'Author', 'author'],
    'wd_creator' => ['creator', 'Creator', 'creator'],
    'wd_place_country'=> ['p_country', 'In country', 'containedInPlace'],
    'wd_inception'=> ['inception', 'Founded', 'foundingDate'],
    'wd_dissolution'=> ['dissolution', 'Dissolution', 'dissolutionDate'],
    'wd_geoname' => ['geoname', 'GeoNames ID', 'sameAs']
  );
  // $type map maps wikidata class labels to schema Types, most specific first
  public $type_map = array(
    'book' => 'Book',
    'literary work' => 'Book',
    'creative work' => 'CreativeWork',
    'human' => 'Person',
    'fictional human' => 'Person',
    'country' => 'Place',
    'city' => 'Place',
    'battle' => 'Event',
    'occurrence' => 'Event',
    'location' => 'Place',
    'organization' => 'Organization',
    'abstract object' => 'Intangible',
    'object' => 'Thing'
  );
  public $generic_property_types = array(
    'label'=>'',
    'description'=>'',
    'type'=>'Label',
  );
  public $person_property_types = array(
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
  public $organization_property_types = array(
    'label'=>'',
    'description'=>'',
    'type'=>'Label',
    'inception'=>'Year',
    'dissolution'=>'Year',
    'country'=>'Label',
    'viaf' => '',
    'isni' => ''
  );
  public $book_property_types = array(
    'label'=>'',
    'description'=>'',
    'type'=>'Label',
    'pubdate'=>'Year',
    'author'=>'Label',
    'viaf' => '',
    'isni' => ''
  );
  public $creativework_property_types = array(
    'label'=>'',
    'description'=>'',
    'type'=>'Label',
    'pubdate'=>'Year',
    'creator'=>'Label',
    'viaf' => '',
    'isni' => ''
  );
  public $place_property_types = array(
    'label'=>'',
    'description'=>'',
    'type'=>'Label',
    'p_country'=>'Label',
    'viaf' => '',
    'isni' => '',
    'geoname' => ''
  );
  public $event_property_types = array(
    'label'=>'',
    'description'=>'',
    'type'=>'Label',
    'date'=>'Year',
    'place'=>'Label',
    'country'=>'Label',
    'viaf' => '',
    'isni' => '',
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
	    'rewrite'      => array( 'slug' => str_replace(' ','-',$p_name) ),
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
    $term_id = $term->term_id ;
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
    $p_map = $this->property_map;
    $types = $this->generic_property_types;
    $this->delete_term_metadata( $term_id );
    $wd = new wdtax_generic_wikidata( $wd_id, $types );
    $wd->store_term_data( $term_id, $this->id ); //update term name and descr
    $wd->set_known_wd_type( $this->type_map );
    $wd_type = $wd->properties['type'];
    if ( 'Person' === $this->type_map[ $wd_type ] ) {
      $types = $this->person_property_types;
      $where = "wd:{$wd_id} rdfs:label ?label .
                wd:{$wd_id} schema:description ?description .
                OPTIONAL { wd:{$wd_id} wdt:P31 ?type }
                OPTIONAL { wd:{$wd_id} wdt:P569 ?dob }
                OPTIONAL { wd:{$wd_id} wdt:P19 ?pob .
                           ?pob wdt:P17 ?cob }
                OPTIONAL { wd:{$wd_id} wdt:P570 ?dod }
                OPTIONAL { wd:{$wd_id} wdt:P20 ?pod .
                           ?pod wdt:P17 ?cod }
                OPTIONAL { wd:{$wd_id} wdt:P214 ?viaf }
                OPTIONAL { wd:{$wd_id} wdt:P213 ?isni }";
      $wd = new wdtax_wikidata( $wd_id, $types, $where );
      update_term_meta( $term_id, 'schema_type',  'Person' );
    } elseif (  'Organization' === $this->type_map[ $wd_type ] ) {
        $types = $this->organization_property_types;
        $where = "wd:{$wd_id} rdfs:label ?label .
                  wd:{$wd_id} schema:description ?description .
                  OPTIONAL { wd:{$wd_id} wdt:P31 ?type }
                  OPTIONAL { wd:{$wd_id} wdt:P571 ?inception }
                  OPTIONAL { wd:{$wd_id} wdt:P576 ?dissolution }
                  OPTIONAL { wd:{$wd_id} wdt:P17 ?country }
                  OPTIONAL { wd:{$wd_id} wdt:P159 ?place .
                             ?place wdt:P17 ?country }
                  OPTIONAL { wd:{$wd_id} wdt:P214 ?viaf }
                  OPTIONAL { wd:{$wd_id} wdt:P213 ?isni }";
        $wd = new wdtax_wikidata( $wd_id, $types, $where );
        update_term_meta( $term_id, 'schema_type',  'Organization' );
    } elseif ( 'Book' === $this->type_map[ $wd_type ] ) {
      $types = $this->book_property_types;
      $where = "wd:{$wd_id} rdfs:label ?label .
                wd:{$wd_id} schema:description ?description .
                OPTIONAL { wd:{$wd_id} wdt:P31 ?type }
                OPTIONAL { wd:{$wd_id} wdt:P577 ?pubdate }
                OPTIONAL { wd:{$wd_id} wdt:P50 ?author }
                OPTIONAL { wd:{$wd_id} wdt:P214 ?viaf }";
      $wd = new wdtax_wikidata( $wd_id, $types, $where );
      update_term_meta( $term_id, 'schema_type',  'Book' );
    } elseif ( 'CreativeWork' === $this->type_map[ $wd_type ] ) {
      $types = $this->creativework_property_types;
      $where = "wd:{$wd_id} rdfs:label ?label .
                wd:{$wd_id} schema:description ?description .
                OPTIONAL { wd:{$wd_id} wdt:P31 ?type }
                OPTIONAL { wd:{$wd_id} wdt:P577 ?pubdate }
                OPTIONAL { wd:{$wd_id} wdt:P170 ?creator }
                OPTIONAL { wd:{$wd_id} wdt:P214 ?viaf }";
      $wd = new wdtax_wikidata( $wd_id, $types, $where );
      update_term_meta( $term_id, 'schema_type',  'CreativeWork' );
    } elseif ( 'Place' === $this->type_map[ $wd_type ]) {
      $types = $this->place_property_types;
      $where = "wd:{$wd_id} rdfs:label ?label .
                wd:{$wd_id} schema:description ?description .
                OPTIONAL { wd:{$wd_id} wdt:P31 ?type }
                OPTIONAL { wd:{$wd_id} wdt:P17 ?p_country }
                OPTIONAL { wd:{$wd_id} wdt:P214 ?viaf }
                OPTIONAL { wd:{$wd_id} wdt:P1566 ?geoname }
                OPTIONAL { wd:{$wd_id} wdt:P213 ?isni }";
      $wd = new wdtax_wikidata( $wd_id, $types, $where );
      update_term_meta( $term_id, 'schema_type',  'Place' );
    } elseif ( 'Event' === $this->type_map[ $wd_type ]) {
      $types = $this->event_property_types;
      $where = "wd:{$wd_id} rdfs:label ?label .
                wd:{$wd_id} schema:description ?description .
                OPTIONAL { wd:{$wd_id} wdt:P31 ?type }
                OPTIONAL { wd:{$wd_id} wdt:P585 ?date }
                OPTIONAL { wd:{$wd_id} wdt:P276 ?place .
                           ?place wdt:P17 ?country }
                OPTIONAL { wd:{$wd_id} wdt:P214 ?viaf }
                OPTIONAL { wd:{$wd_id} wdt:P213 ?isni }";
      $wd = new wdtax_wikidata( $wd_id, $types, $where );
      update_term_meta( $term_id, 'schema_type',  'Event' );
    } else {
      update_term_meta( $term_id, 'schema_type',  'Thing' );
    }
    //iterate over every property we know about and if the wikidata object
    //has a value for it in its $properties array, save it as metadata
    //for this term
    foreach ( array_keys( $p_map ) as $key ) {
      if ( isset($wd->properties[$p_map[$key][0]] )
          && '' !== $wd->properties[$p_map[$key][0]] ) {
            $wd->store_property( $term_id, $key, $p_map[$key][0]);
      }
    }
  }
  function schema_text( $term_id, $p, $args=array() ) {
    // echo property $p of a term as schema markup for property to which $p maps
    // in $property_map, in html tag $args['tag'] or span as default, with
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
  function schema_person_details ( $term_id ) {
    $args = array('after'=>'. ');
    $descr = $this->schema_text( $term_id, 'wd_description', $args);
    $birth =  $this->schema_birth_details( $term_id );
    $death = $this->schema_death_details( $term_id );
    return $descr.$birth.$death;
  }
  function schema_sameas_wd( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    $base_url = 'http://www.wikidata.org/entity/';
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
  function schema_sameas_geoname( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    $base_url = 'http://geonames.org/';
    if ( isset( $term_meta['wd_geoname'] ) ) {
      $args = array(
        'tag'=>'link',
        'before'=>$base_url
      );
      return $this->schema_text( $term_id, 'wd_geoname', $args );
    } else {
      return '';
    }
  }
  function schema_sameas_all( $term_id ) {
    $wd = $this->schema_sameas_wd( $term_id );
    $isni = $this->schema_sameas_isni( $term_id );
    $viaf = $this->schema_sameas_viaf( $term_id );
    $geoname = $this->schema_sameas_geoname( $term_id );
    return $wd.$isni.$viaf.$geoname;
  }
  function schema_author( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    if ( isset( $term_meta['wd_author'] ) ) {
      return ' by '.$this->schema_text( $term_id, 'wd_author' );
    } else {
      return '';
    }
  }
  function schema_creator( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    if ( isset( $term_meta['wd_creator'] ) ) {
      return ' by '.$this->schema_text( $term_id, 'wd_creator' );
    } else {
      return '';
    }
  }
  function schema_country( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    if ( isset( $term_meta['wd_place_country'] ) ) {
      return ' in '.$this->schema_text( $term_id, 'wd_place_country' );
    } else {
      return '';
    }
  }
  function schema_publication_date( $term_id ) {
    $term_meta = get_term_meta( $term_id );
    if ( isset( $term_meta['wd_pub_date'] ) ) {
      return ' published '.$this->schema_text( $term_id, 'wd_pub_date' );
    } else {
      return '';
    }
  }
  function schema_book_details( $term_id ) {
    $auth = $this->schema_author( $term_id );
    $publ = $this->schema_publication_date( $term_id );
    return 'Book '.$auth.$publ.'. ';
  }
  function schema_creativework_details( $term_id ) {
    $type = ' A '.get_term_meta( $term_id, 'wd_type', True );
    $creator = $this->schema_creator( $term_id );
    return $type.$creator;
  }
  function schema_place_details( $term_id ) {
    $descr = $this->schema_text( $term_id, 'wd_description' );
    $type = ' A '.get_term_meta( $term_id, 'wd_type', True );
    $country = $this->schema_country( $term_id );
    return $descr.'.'.$type.$country;
  }
  function schema_event_details( $term_id ) {
    $type = ' A '.get_term_meta( $term_id, 'wd_type', True );
    $date = ' which happened in '.$this->schema_text( $term_id, 'wd_date' );
    $place = ' at '.$this->schema_text( $term_id, 'wd_place' );
    $country = $this->schema_text( $term_id, 'wd_country' );
    return $type.$date.$place.''.$country;
  }
  function schema_organization_details( $term_id ) {
    $descr = $this->schema_text( $term_id, 'wd_description' );
    $country = 'Location or headquarters'.
                $this->schema_text( $term_id, 'wd_country' );
    $founded = ' founded '.$this->schema_text( $term_id, 'wd_inception' );
    $dissolved = ' dissolved '.$this->schema_text( $term_id, 'wd_dissolution' );
    $text = ' located or headquarters in ';
    $country = $this->schema_text( $term_id, 'wd_country' );
    return $descr.$founded.$dissolved.$text.$country;
  }
}
