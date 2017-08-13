<?php
	
/* -------------------------------------------------------------------
 * Class used to connect to Symplectc and fetch records
 * @author Kunika Kono <kono.kunika@gmail.com>
 * @version 1.0
 * Copyright 2015-2017 Kunika Kono
 * Licensed under MIT 
----------------------------------------------------------------------*/

	class SymplecticAPI 
	{
		
		private $endpoint = SYMPLECTIC_ENDPOINT;
		private $user = SYMPLECTIC_USER;
		private $pass = SYMPLECTIC_PASS;
		private $cachepath = SYMPLECTIC_CACHE_PATH;
		private $cachetime = SYMPLECTIC_CACHE_TIME;
		private $debug = DEBUG;
		
		public function __construct() {
			return;
		}
		
		public function getUser( $username ) {
			$url = '/users?username=%s';
			$cache = '/%s/user.xml';
			$result = array();
			if ( $xml = $this->fetch( sprintf( $url, $username ), sprintf( $cache, $username ) ) ) {
				foreach ( $xml->xpath( 'atom:entry/api:object[@category="user" and @type="person"]' ) as $object ) {
					array_push( $result, new SymplecticUser( $object ) );
				}
			}
			return $result;
		}
		
		public function getUserPublications( $username ) {
			$url = '/users/%d/publications?detail=full';
			$cache = '/%s/%d/publications.xml';
			$result = array();
			if ( $user = $this->getUser( $username ) ) {
				if ( $xml = $this->fetch( sprintf( $url, $user[0]->id() ), sprintf( $cache, $username, $user[0]->id() ) ) ) {
					foreach ( $xml->xpath( 'atom:entry/api:relationship[@type="publication-user-authorship"]' ) as $relationship ) {
						array_push( $result, new SymplecticPublication( $relationship ) );
					}
				}				
			}
			return $result;
		}
		
		public function getUsersInGroup( $id ) {
			$url = '/users?groups=%d&detail=full';
			$cache = '/group/%d.xml';
			$result = array();
			if ( $xml = $this->fetch( sprintf( $url, $id ), sprintf( $cache, $id ) ) ) {
				foreach ( $xml->xpath( 'atom:entry/api:object[@category="user" and @type="person"]' ) as $object ) {
					array_push( $result, new SymplecticUser( $object ) );
				}
			}
			return $result;
		}
		
		public function getPublicationTypes() {
			$url = '/publication/types?detail=full';
			$cache = '/types.xml';
			$result = array();
			if ( $xml = $this->fetch( $url, $cache ) ) {
				foreach ( $xml->xpath( 'atom:entry/api:type' ) as $object ) {
					array_push( $result, new SymplecticPublicationType( $object ) );
				}
			}
			return $result;
		}
		
		private function fetch( $url, $cache ) {
			$result = null;
			if ( $this->isXMLCacheValid( $cache ) ) {
				$result = $this->loadXMLCache( $cache );
			} else {
				$url = $this->endpoint . $url;
				while ( $url !== null ) {
					$response = $this->curl( $url );
					if ( $result ) {
						$dom = dom_import_simplexml( $result );
						foreach ( $response->entry as $entry ) {
							$node = dom_import_simplexml( $entry );
							$dom->appendChild( $dom->ownerDocument->importNode( $node, true ) );
						}
					} else {
						$result = $response;
					}
					$next = $response->xpath( 'api:pagination/api:page[@position="next"]' );
					$url = ( count( $next ) > 0 ) ? (string) $next[0] -> attributes() -> href : null;
				}
				if ( $result ) {
					//if ( $result->xpath( 'api:pagination' ) ) unset( $result->xpath('api:pagination')[0]->{0} );
					$this->saveXMLCache( $result, $cache );
				}
			}
			return $result;
		}
	
		private function curl( $url ) {
			$curl = curl_init();	
			curl_setopt( $curl, CURLOPT_URL, $url);
			curl_setopt( $curl, CURLOPT_USERPWD, $this->user . ':' . $this->pass );
			curl_setopt( $curl, CURLOPT_HEADER, 0 );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 30 );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
			$result = curl_exec( $curl );
			if ( curl_getinfo( $curl, CURLINFO_HTTP_CODE ) == "200" ) {
				$result = $this->parseXML( $result );
				if ( $error = $result->xpath( 'atom:entry[1]/api:error' ) ) {
					throw new Exception( ucwords( (string) $error[0]->attributes()->code ) . ': ' . (string) $error[0] );
				} else return $result;
    		} else {
	    		throw new Exception( ': Unable to connect to Symplectic API at "' . $url . '"' . ' (HTTP Error ' . curl_getinfo( $curl, CURLINFO_HTTP_CODE ) . ')' );
    		}
			curl_close( $curl );		
			return;
		}
		
		private function saveXMLCache( $xml, $path ) {
			$file = $this->cachepath . $path;
			if( !is_dir( dirname( $file ) ) ) mkdir( dirname( $file ), 0755 );
			$xml->savexml($file);
		}
		
		private function loadXMLCache( $path ) {
			$file = $this->cachepath . $path;
			if ( is_file( $file ) ) return $this->parseXML( file_get_contents( $file ) );
			else throw new Exception( 'Cache "' . $file . '" does not exist.' );
			return;
		}
		
		private function isXMLCacheValid( $cache ) {
			if ( $this->debug ) return true;
 			$cache = realpath( $this->cachepath . $cache );
			if ( is_file( $cache ) ) {
				if ( time()-filemtime( $cache ) < $this->cachetime ) return true;
			}
			return false;
		}
		
		private function parseXML($xml) {
			$xml = new SimpleXMLElement($xml);
			$xml = $this->registerXPathNameSpace($xml);
			return $xml;
		}
		
		private function registerXPathNameSpace( $xml ) {
			foreach( $xml->getNamespaces( true ) as $prefix=>$namespace ) {
				if (trim( $prefix ) === '') $prefix = strtolower( basename( $namespace ) );
				$xml->registerXPathNamespace( $prefix, $namespace );
			}
			return $xml;
		}
		
	}
	
	abstract class SymplecticAPIResponse
	{
		
		abstract protected function parse();
		
		protected $xml;
		protected $properties = array();
		
		public function __construct( $xml ) {
			$this->xml = $xml;
			$this->parse();
			return;
		}
		
		public function __call( $method, $arguments ) {
			return array_key_exists( $method, $this->properties ) ? $this->properties[$method] : NULL;
		}
		
		protected function field( $field ) {
			if ( $type = $field->attributes()->type ) {
				switch ( $type ) {
					case 'text':
						if ( $field->xpath( 'api:text' ) ) return (string) $field->xpath( 'api:text' )[0];
						break;
					case 'integer':
						if ( $field->xpath( 'api:integer' ) ) return (integer) $field->xpath( 'api:integer' )[0];
						break;
					case 'date':
						$result = array();
						if ( $field->xpath('api:date/api:year') ) $result['year'] = (string) $field->xpath('api:date/api:year')[0];
						if ( $field->xpath('api:date/api:month') ) $result['month'] = (string) $field->xpath('api:date/api:month')[0];
						if ( $field->xpath('api:date/api:day') ) $result['day'] = (string) $field->xpath('api:date/api:day')[0];
						if ( !empty( $result ) ) return $result;
						break;
					case 'pagination':
						$result = array();
						if ( $field->xpath('api:pagination/api:begin-page') ) $result['begin'] = (string) $field->xpath('api:pagination/api:begin-page')[0];
						if ( $field->xpath('api:pagination/api:end-page') ) $result['end'] = (string) $field->xpath('api:pagination/api:end-page')[0];
						if ( !empty( $result ) ) return $result;
						break;
					case 'person-list':
						$result = array();
						foreach ( $field->xpath('api:people/api:person') as $person ) {
							array_push($result, array(
								'lastname' => ( $person->xpath( 'api:last-name' ) ) ? (string) $person->xpath( 'api:last-name' )[0] : '',
								'initials' => ( $person->xpath( 'api:initials' ) ) ? (string) $person->xpath( 'api:initials' )[0] : '',
								'firstnames' => ( $person->xpath( 'api:first-names' ) ) ? (string) $person->xpath( 'api:first-names' )[0] : '',
							));
						}
						if ( !empty( $result ) ) return $result;		
						break;
					case 'keyword-list':
						$result = array();
						foreach( $field->xpath( 'api:keywords/api:keyword' ) as $keyword ) {
							array_push( $result, (string) $keyword );
						}
						if ( !empty( $result ) ) return $result;	
						break;
				}
			}
			return;
		}
		
	}
	
	class SymplecticUser extends SymplecticAPIResponse
	{
				
		protected function parse() {
			foreach ( $this->xml->attributes() as $key=>$value) {
				$name = str_replace('-', '', $key);
				$this->properties[$name] = (string) $value;
			}
			if ( $this->xml->xpath( 'api:title' ) ) $this->properties['title'] = (string) $this->xml->xpath( 'api:title' )[0];
			if ( $this->xml->xpath( 'api:first-name' ) ) $this->properties['firstname'] = (string) $this->xml->xpath( 'api:first-name' )[0];
			if ( $this->xml->xpath( 'api:last-name' ) ) $this->properties['lastname'] = (string) $this->xml->xpath( 'api:last-name' )[0];
			if ( $this->xml->xpath( 'api:initials' ) ) $this->properties['initials'] = (string) $this->xml->xpath( 'api:initials' )[0];
			if ( $this->xml->xpath( 'api:is-current-staff' ) ) $this->properties['iscurrentstaff'] = (string) $this->xml->xpath( 'api:is-current-staff' )[0];
			if ( $this->xml->xpath( 'api:is-academic' ) ) $this->properties['isacademic'] = (string) $this->xml->xpath( 'api:is-academic' )[0];
			if ( $this->xml->xpath( 'api:relationships' ) ) $properties['relationships'] = (string) $this->xml->xpath( 'api:relationships' )[0]->attributes()->href;
			return;
		}
		
	}
	
	class SymplecticPublication extends SymplecticAPIResponse
	{	
		
		public function publicationdate() {
			if ( isset( $this->properties['publicationdate'] ) ) return $this->properties['publicationdate'];
			elseif ( $this->onlinepublicationdate() ) return $this->onlinepublicationdate();
			elseif ( $this->acceptancedate() ) return $this->acceptancedate();
			elseif ( $this->recordmadepublicatsourcedate() ) return $this->recordmadepublicatsourcedate();
			elseif ( $this->recordcreatedatsource() ) return $this->recordcreatedatsource();
			return;
		}
		
		protected function parse() {
			if ( $this->xml->xpath('api:is-visible') ) $this->properties['isvisible'] = (string) $this->xml->xpath('api:is-visible')[0];
			if ( $this->xml->xpath('api:is-favourite') ) $this->properties['isfavourite'] = (string) $this->xml->xpath('api:is-favourite')[0];
			if ( $object = $this->xml->xpath( 'api:related/api:object' ) ) {
				$object = $object[0];
				foreach ($object->attributes() as $key=>$value) {
					$name = str_replace('-', '', $key);
					$this->properties[$name] = (string) $value;
				}
				foreach ( $this->xml->xpath( 'api:related/api:object/api:records/api:record[@is-preferred-record="true"]/api:native/api:field | api:related/api:object/api:fields/api:field' ) as $field ) {
					$name = str_replace('-', '', (string) $field->attributes()->name);
					$this->properties[$name] = parent::field( $field );
				}
				foreach ( $this->xml->xpath( 'api:related/api:object/api:all-labels' ) as $field ) {
					$this->properties['labels'] = parent::field( $field );
				}
			}
			return;
		}
		
	}
	
	class SymplecticPublicationType extends SymplecticAPIResponse
	{
		
		protected function parse() {
			if ( $this->xml->attributes()->id ) $this->properties['id'] = (integer) $this->xml->attributes()->id;
			if ( $this->xml->attributes()->name ) $this->properties['name'] = (string) $this->xml->attributes()->name;
			if ( $this->xml->xpath( 'api:heading-plural' ) ) $this->properties['displayname']['plural'] = (string) $this->xml->xpath( 'api:heading-plural' )[0];
			if ( $this->xml->xpath( 'api:heading-singular' ) ) $this->properties['displayname']['singular'] = (string) $this->xml->xpath( 'api:heading-singular' )[0];
			foreach ( $this->xml->xpath('api:fields/api:field') as $node ) {
				$this->properties['fields'][(string) $node->xpath('api:name')[0]] = array(
					'displayname' => (string) $node->xpath('api:display-name')[0],
					'type' => (string) $node->xpath('api:type')[0],
					'is-mandatory' => (string) $node->xpath('api:is-mandatory')[0],
				);
			}
			return;			
		}
		
	}
	
?>