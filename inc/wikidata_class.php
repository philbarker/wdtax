<?php
/*
 * Package Name: wdtax
 * Description: functions to get data for taxons from wikidata
 * Version: 0
 * Author: Phil Barker
 * Author URI: http://people.pjjk.net/phil
 * @license GPL 2.0+
*/
defined( 'ABSPATH' ) or die( 'Be good. If you can\'t be good be careful' );

abstract class wdtax_wikidata {
	/* cannot be instantiated except when extended to define
	 * $sparqlQuery
	 *
	 * method naming:
	 * fetch_ data from wikidata
	 * set_ value from wikidata as property
	 * get_ value of a property
	 * store_ value of property as metadata
	 !!maybe the store_ methods ought to be in the taxonomy class.
	 *
	 * wikidate is fetched and properties set on initiation
	 * properties need to be stored explicitly
	 */
	public $id;
	public $label;
	public $description;
	public $type;
//	public $properties; //not used? object properties set w/o declaring property
	public $wikidata;
	public $endpointUrl = 'https://query.wikidata.org/sparql';
	public $sparqlQuery = '';

	public function __construct( $wd_id ) {
		$this->set_id( $wd_id );
		$this->fetch_wikidata();
		$this->set_text_property( 'label' );
		$this->set_text_property( 'description' );
		$this->properties = array();
	}
	protected function set_id( $new_id ) {
		$this->id = $new_id;
	}
	function get_id( ) {
		if ( $this->id ) {
			return $this->id;
		} else {
			return false;
		}
	}
	function store_term_data( $term_id, $taxonomy ) {
		$args = array(
				'description' => $this->description,
				'name' => $this->label
			);
		wp_update_term( $term_id, $taxonomy, $args );
	}
	function set_property( $p, $type ) {
		if ('Year'===$type) {
			$this->set_year_property( $p );
		} elseif ('Label'===$type) {
			$this->set_text_property( $p, $type );
		} else {
			$this->set_text_property( $p );
		}
	}
	function set_text_property( $p, $label='' ) {
		if ( !in_array( $p, array_keys( get_object_vars( $this ) ) ) ) {
			return false;
		} else {
			$tag = $p.$label;
		}
		if ( isset( $this->wikidata->results->bindings[0]->$tag->value ) ) {
			$this->$p = $this->wikidata->results->bindings[0]->$tag->value;
			return true;
		} else {
			$this->$p = false;
			return false;
		}
	}
	function set_year_property( $p ) {
		if ( !in_array( $p, array_keys( get_object_vars( $this ) ) ) ) {
			return false;
		}
		if (isset($this->wikidata->results->bindings[0]->$p->value) ) {
			$value = $this->wikidata->results->bindings[0]->$p->value;
			$year = $this->wd_year( $value );
			if ( $year ){
				$this->$p = $year;
				return true;
			} else {
				$this->$p = false;
				return false;
			}
		} else {
			$this->$p = false;
			return false;
		}
	}

	protected function strip_zero($year_in) {
		if ('0' !== substr( $year_in, 0, 1 ) ) {
			return $year_in;
		} else {
			$year = substr( $year_in, 1, strlen($year_in) );
			return $this->strip_zero( $year) ;
		}
	}
	protected function wd_year( $xml_time ) {
		$BCE = '';
		$date = explode('T', $xml_time)[0];
		if ( substr($date, 0, 1) == '-' ) {
			$BCE = ' BCE';
			$year = explode('-', $date)[1];
		} else {
			$year = explode('-', $date)[0];
		}
		$year = $this->strip_zero(substr($year, 0, 4) );
		return $year.$BCE;
	}

	function get_property( $property ) {
		if ( $this->$property ) {
			return $this->$property;
		} else {
			return false;
		}
	}
	function store_property( $term_id, $p, $meta_key) {
		//$term_id, the id of an existing taxonomy term
		//$p a property, hopefully one of the proerties of an object of this class
		//$meta_key, the key for storing the value of the property in term metadata
		if ( !in_array( $p, array_keys( get_object_vars( $this ) ) ) ) {
			return false;
		}
		if ( $this->$p ) {
			update_term_meta( $term_id, $meta_key,  $this->$p );
		} else {
			return false;
		}
	}

	function fetch_wikidata() {
		$query = urlencode($this->sparqlQuery);
		$format = 'json';
		$queryUrl = $this->endpointUrl.'?query='.$query.'&format='.$format;
		$response = wp_remote_get( $queryUrl );
		if ( is_array( $response ) ) {
				$this->wikidata = json_decode( $response['body'] );
		} else {
			echo 'sorry no wikidata for you';
		}
	}
}

class wdtax_basic_wikidata extends wdtax_wikidata {
  public function __construct( $wd_id ) {
		// what type of object do we expect for each wikidata property
    $property_types = array(
          'label'=>'',
          'description'=>'',
					'type'=>'Label'
        );
    $where = array( 'wd:'.$wd_id.' rdfs:label ?label',
    						  'wd:'.$wd_id.' schema:description ?description',
									'wd:'.$wd_id.' wdt:P31 ?type'
                );  //for sparql WHERE clause
    $select = '';   //for sparql SELECT clause
		foreach ( array_keys( $property_types ) as $property ) {
			if ('Label'===$property_types[$property]) {
				$select = $select.'?'.$property.$property_types[$property].' ';
			} else {
				$select = $select.'?'.$property.' ';
			}
		}
    $this->sparqlQuery =
      'SELECT '.$select.' '.
      'WHERE {'.implode(' .', $where ).' '.
      'FILTER(LANG(?label) = "en").'.
      'FILTER(LANG(?description) = "en").'.
      'SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }'.
      '}';
    parent::__construct( $wd_id );
		$this->set_property( 'type', 'Label' );

  }
}

class wdtax_person_wikidata extends wdtax_wikidata {
	protected $dob; // date of birth
	protected $pob; // place of birth
	protected $cob; // country of birth
	protected $dod; // date of death
	protected $pod; // place of death
	protected $cod; // country of death

  public function __construct( $wd_id ) {
		$property_types = array(
						'label'=>'',
						'description'=>'',
						'dob'=>'Year',
						'pob'=>'Label',
						'cob'=>'Label',
						'dod'=>'Year',
						'pod'=>'Label',
						'cod'=>'Label'
					);
		$where = array( 'wd:'.$wd_id.' rdfs:label ?label',
						'wd:'.$wd_id.' schema:description ?description',
						'wd:'.$wd_id.' wdt:P569 ?dob',
						'wd:'.$wd_id.' wdt:P19 ?pob',
						'?pob wdt:P17 ?cob',
						'wd:'.$wd_id.' wdt:P570 ?dod',
						'wd:'.$wd_id.' wdt:P20 ?pod',
						'?pod wdt:P17 ?cod'
					);
		$select = '';
		foreach ( array_keys( $property_types ) as $property ) {
			if ('Label'===$property_types[$property]) {
				$select = $select.'?'.$property.$property_types[$property].' ';
			} else {
				$select = $select.'?'.$property.' ';
			}
		}
		$this->sparqlQuery =
			'SELECT '.$select.' '.
			'WHERE {'.implode(' .', $where ).' '.
				'FILTER(LANG(?label) = "en").'.
				'FILTER(LANG(?description) = "en").'.
				'SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }'.
			'}';
  	parent::__construct( $wd_id );
		$this->set_property( 'dob', 'Year' );
		$this->set_property( 'dod', 'Year' );
		$this->set_property( 'pob', 'Label' );
		$this->set_property( 'cob', 'Label' );
		$this->set_property( 'pod', 'Label' );
		$this->set_property( 'cod', 'Label' );
	}
}
