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

abstract class wdtax_wikidata_basics {
	/* cannot be instantiated except when extended to define
	 * $sparqlQuery
	 *
	 * method naming:
	 * fetch_ data from wikidata
	 * set_ value from wikidata as property
	 * get_ value of a property
	 * store_ value of property as metadata
	 *
	 * wikidata is fetched and properties set on initiation
	 * properties need to be stored explicitly
	 */
	public $properties = array( 'id' =>'',
															'label' =>'',
                              'description' => '',
															'image' => '',
															'type' =>''
														);
	public $endpointUrl = 'https://query.wikidata.org/sparql';
	public $sparqlQuery = '';
	public $wikidata;

	public function __construct( $wd_id, $properties ) {
		$this->properties = array_merge($this->properties, $properties);
		$this->properties['id'] = $wd_id;
		$this->fetch_wikidata();
		$this->set_text_property( 'label' );
		$this->set_text_property( 'description' );
		$this->set_text_property( 'image' );
		$this->set_property( 'type', 'Label' );
	}
	function store_term_data( $term_id, $taxonomy ) {
		$args = array(
				'description' => $this->properties['description'],
				'name' => $this->properties['label']
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
		if ( !in_array( $p, array_keys( $this->properties ) ) ) {
			return false;
		} else {
			$tag = $p.$label;
		}
		if ( isset( $this->wikidata->results->bindings[0]->$tag->value ) ) {
			$this->properties[$p] = $this->wikidata->results->bindings[0]->$tag->value;
			return true;
		} else {
			$this->$p = false;
			return false;
		}
	}
	function set_year_property( $p ) {
		if ( !in_array( $p, array_keys( $this->properties ) ) ) {
			return false;
		}
		if (isset($this->wikidata->results->bindings[0]->$p->value) ) {
			$value = $this->wikidata->results->bindings[0]->$p->value;
			$year = $this->wd_year( $value );
			if ( $year ){
				$this->properties[$p] = $year;
				return true;
			} else {
				$this->properties[$p] = false;
				return false;
			}
		} else {
			$this->properties[$p] = false;
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
	function store_property( $term_id, $meta_key, $p ) {
		//$term_id, the id of an existing taxonomy term
		//$p a property, hopefully one of the proerties of an object of this class
		//$meta_key, the key for storing the value of the property in term metadata
		if ( !in_array( $p, array_keys(  $this->properties  ) ) ) {
			return new WP_Error( 'Error', 'Tried to store an unknown property: '.$p );
		}
		if ( $this->properties[$p] ) {
			update_term_meta( $term_id, $meta_key,  $this->properties[$p] );
			return true;
		} else {
			return new WP_Error( 'Error', 'No data to store for property: '.$p );
		}
	}
	function set_known_wd_type( $type_map ) {
		//once the wikidata id (Q#) for an object is set, this looks at the
		//wikidata type hierarchy going up from the type of the object until
		//it finds the first matching type in $type_map
		if ( !empty($this->properties['id'] ) )  {
			$wd_id = $this->properties['id'];
		} else {
			return new WP_Error( 'Error', 'No id for wikidata' );
		}
		$format = 'json';
		$type_query = "SELECT  ?classLabel
	    WHERE {
	      wd:$wd_id (wdt:P31|wdt:P279)* ?class .
	      SERVICE wikibase:label {
					bd:serviceParam wikibase:language '[AUTO_LANGUAGE],en'.
				}
		  }";
		$queryUrl = $this->endpointUrl.'?query='.$type_query.'&format='.$format;
		$response = wp_remote_get( $queryUrl );
		$results = json_decode( $response['body'] );
		if  ( is_array( $response ) ) {
			foreach ( $results->results->bindings as $r ){
				foreach ( array_keys( $type_map ) as $wd_type ) {
//					echo 'returned value: '.$r->classLabel->value;
//					echo ' checking value: '.$type_map[$wd_type].'<br>';

					if ( $r->classLabel->value === $wd_type ) {
//						echo 'returned value: '.$r->classLabel->value;
//						echo '<br>';
//						echo 'matched value: '.$type_map[$wd_type];
						$this->properties['type'] = $wd_type;
						return $wd_type;
					}
				}
			} //got through both arrays without matching anything more specific
			$this->properties['type'] = 'Thing';
			return 'Thing';
		} else {
			echo 'sorry nothing from wikidata for you';
			echo '<br>Query: '.$this->type_query ;
			echo '<br>Endpoint: '.$this->endpointUrl ;
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
			echo 'sorry nothing from wikidata for you';
			echo '<br>Query: '.$this->sparqlQuery ;
			echo '<br>Endpoint: '.$this->endpointUrl ;
		}
	}
}

class wdtax_generic_wikidata extends wdtax_wikidata_basics {
  public function __construct( $wd_id, $property_types=array() ) {
		$properties = array_keys( $property_types );
    $where = "wd:{$wd_id} rdfs:label ?label.
    				  wd:{$wd_id} schema:description ?description.
							OPTIONAL { wd:{$wd_id} wdt:P31 ?type }
							OPTIONAL { wd:{$wd_id} wdt:P18 ?image } ";
              //for sparql WHERE clause
    $select = '';   //for sparql SELECT clause
		foreach ( array_keys( $property_types ) as $property ) {
			if ('Label'===$property_types[$property]) {
				$select = $select.'?'.$property.$property_types[$property].' ';
			} else {
				$select = $select.'?'.$property.' ';
			}
			unset( $property );
		}
    $this->sparqlQuery =
      'SELECT '.$select.' '.
      'WHERE {'.$where.' '.
      'FILTER(LANG(?label) = "en").'.
      'FILTER(LANG(?description) = "en").'.
      'SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }'.
      '}';
    parent::__construct( $wd_id, $properties );
  }
}
class wdtax_wikidata extends wdtax_wikidata_basics {
  public function __construct( $wd_id, $property_types=array(), $where ) {
		$properties = array_keys( $property_types );
		$select = '';
		foreach ( array_keys( $property_types ) as $property ) {
			if ('Label'===$property_types[$property]) {
				$select = $select.'?'.$property.$property_types[$property].' ';
			} else {
				$select = $select.'?'.$property.' ';
			}
			unset( $property );
		}
		$this->sparqlQuery =
			'SELECT '.$select.' '.
			'WHERE {'.$where.' '.
				'FILTER(LANG(?label) = "en").'.
				'FILTER(LANG(?description) = "en").'.
				'SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }'.
			'}';
		parent::__construct( $wd_id, $properties );
		foreach ( array_keys( $property_types ) as $property ) {
			$this->set_property( $property, $property_types[$property] );
			unset( $property );
		}
	}
}
