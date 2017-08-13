<?php
	
/* -------------------------------------------------------------------
 * Class used to parse Symplectic data into citation formats
----------------------------------------------------------------------*/
	
	abstract class SymplecticPublicationFormat
	{
		
		abstract protected function format_artefact();
		abstract protected function format_book();
		abstract protected function format_chapter();
		abstract protected function format_composition();
		abstract protected function format_conference();
		abstract protected function format_design();
		abstract protected function format_internet_publication();
		abstract protected function format_journal();
		abstract protected function format_other();
		abstract protected function format_performance();
		abstract protected function format_report();
		abstract protected function format_scholarly_edition();
		abstract protected function format_thesis();
		
		protected $publication;
		protected $options = array(
			'display' => array(),
		);
		
		public function __construct( SymplecticPublication $publication ) {
			$this->publication = $publication;
			return;
		}
		
		public function citation() {
			// Types not catered (yet): 
			// dataset, exhibition, figure, fileset, media, patent, poster, software
			if ( $type = $this->publication->type() ) {
				switch ( $type ) {
					case 'artefact':
						return $this->format_artefact();
						break;
					case 'book':
						return $this->format_book();
						break;
					case 'chapter':
						return $this->format_chapter();
						break;
					case 'composition':
						return $this->format_composition();
						break;
					case 'conference':
					case 'poster':
					case 'presentation':
						return $this->format_conference();
						break;
					case 'design':
						return $this->format_design();
						break;
					case 'internet-publication':
						return $this->format_internet_publication();
						break;
					case 'journal-article':
						return $this->format_journal();
						break;					
					case 'other':
						return $this->format_other();
						break;
					case 'performance':
						return $this->format_performance();
						break;
					case 'report':
						return $this->format_report();
						break;
					case 'scholarly-edition':
						return $this->format_scholarly_edition();
						break;
					case 'thesis-dissertation':
						return $this->format_thesis();
						break;
				}
			}
			return;
		}
		
		protected function format_person_list( $list ) {
			$result = '';
			$names = array();
			if ( is_array( $list ) || is_object( $list ) ) {
				foreach ( $list as $person ) {
					$name = '';
					if ( isset( $person['lastname'] ) ) {
						$name .= trim( $person['lastname'] );
					}
					if ( isset( $person['initials'] ) && trim( $person['initials'] ) !== '') {
						$name .= ($name !== '') ? ', ' : '';
						$name .= chunk_split( trim( $person['initials'] ), 1, '.' );
					}
					if ( $name !== '' ) array_push( $names, $name );
				}
				foreach ( $names as $n =>$name ) {
					if ( $n > 0 ) {
						if ( $n === count( $names ) - 1 ) $result .= ' and ';
						else $result .= ', ';
					}
					$result .= $name;
				}
			}
			return $result;
		}
		
		protected function format_date( $date ) {
			if ( isset( $date['year'] ) && isset( $date['month'] ) && isset( $date['day'] ) ) {
				return date( 'j F Y', strtotime( $date['year'] . '-' . $date['month'] . '-' . $date['day'] ) );
			}
			if ( isset( $date['year'] ) && isset( $date['month'] ) ) {
				return date( 'F Y', strtotime( $date['year'] . '-' . $date['month'] . '-01' ) );
			}
			return $date['year'];
		}
		
		protected function format_date_range ( $start, $end=null ) {
			$result = '';
			foreach ( array( 'year', 'month', 'day' ) as $part ) {
				if ( !isset( $start[$part] ) || $start[$part] === 0 ) {
					if ( isset( $end[$part] ) && $end[$part] > 0 ) $start[$part] = $end[$part];
				}
				if ( !isset( $end[$part] ) || $end[$part] === 0 ) $end[$part] = $start[$part];
			}
			$startdate = strtotime($start['year'] . '-' . $start['month'] . '-' . $start['day']);
			$enddate = strtotime($end['year'] . '-' . $end['month'] . '-' . $end['day']);
			if ( date( 'Y-m-d', $startdate ) === date ( 'Y-m-d', $enddate ) ) {
				$result = date( 'j F Y', $startdate );
			} elseif ( date( 'Y-m', $startdate ) === date ( 'Y-m', $enddate ) ) {
				$result = date( 'j', $startdate ) . date( ' – j F Y', $enddate );
			} elseif ( date( 'Y', $startdate ) === date( 'Y', $enddate ) ) {
				$result = date( 'j F', $startdate ) . date( ' – j F Y', $enddate );
			} else {
				$result = date( 'j F Y', $startdate ) . date( ' - j F Y', $enddate );
			}
			return $result;
		}
		
	}
	
	class HarvardFormat extends SymplecticPublicationFormat
	{
		
		protected function format_artefact() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$medium = trim( $this->publication->medium() );
			if ( $medium !== '' ) {
				$medium = sprintf( '<span class="medium">%s</span>', $medium );
			}
			$location = trim( $this->publication->location() );
			$url = trim( $this->publication->publisherurl() );
			if ( $location !== '' ) {
				if ( $url !== '' ) {
					$location = sprintf( '<span class="publisher"><a href="%s">%s</a></span>', $url, $location );
				} else {
					$location = sprintf( '<span class="publisher">%s</span>', $location );
				}
			}
			if ( $medium !== '' ) {
				$result .= $medium;
			}
			if ( $location !== '' ) {
				if ( $medium !== '' ) {
					$result .= ':';	
				}
				$result .= $location;
			}
			if ( $medium !== '' || $location !== '' ) {
				$result .= '. ';
			}
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}

		protected function format_book() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}
		
		protected function format_chapter() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$result .= $this->format_pages() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}
		
		protected function format_composition() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$medium = trim( $this->publication->medium() );
			if ( $medium !== '' ) {
				$medium = sprintf( '<span class="medium">%s</span>', $medium );
				$result .= $medium . '. ';
			}
			if ( $this->publication->startdate() ) {
				$date = $this->format_date( $this->publication->startdate() );
				if ( $date !== '' ) {
					$date = sprintf( '<span class="startdate">%s</span>', $date );
					$result .= $date . '. ';
				}
			}
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';			
			return trim( $result );
		}
		
		protected function format_conference() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$name = trim( $this->publication->nameofconference() );
			if ( trim( $name ) !== '' ) {
				$name = sprintf( '<span class="conference-name">%s</span>', $name );
			}
			$location = trim( $this->publication->location() );
			if ( trim( $location ) !== '' ) {
				$location = sprintf( '<span class="conference-location">%s</span>', $location );
			}
			if ( $this->publication->startdate() || $this->publication->finishdate() ) {
				$date = $this->format_date_range( $this->publication->startdate(), $this->publication->finishdate() );
			} else $date = trim( null );
			if ( $date !== '' ) {
				$date = sprintf( '<span class="conference-date">%s</span>', $date );
			}
			// [name], [location], [date]
			if ( $name !== '' ) $result .= $name;
			if ( $location !== '' ) {
				if ( $name !== '' ) {
					$result .= ', ';
				}
				$result .= $location;
			}
			if ( $date !== '') {
				if ( $name !== '' || $location !== '' ) {
					$result .= ', ';
				}
				$result .= $date;
			}
			if ( $name !== '' || $location !== '' || $date !== '' ) {
				$result .= '. ';
			}
			$result .= $this->format_publisher() . ' ';
			$result .= $this->format_issue() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			$result .= $this->format_notes() . ' ';
			return trim( $result );
		}
		
		protected function format_design() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}
		
		protected function format_journal() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_issue() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			//$result .= $this->format_notes() . ' ';
			return trim( $result );
		}
		
		protected function format_internet_publication() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}
		
		protected function format_other() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$result .= $this->format_issue() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}
		
		protected function format_performance() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$medium = trim( $this->publication->medium() );
			if ( $medium !== '' ) {
				$medium = sprintf( '<span class="medium">%s</span>', $medium );
				$result .= $medium . '. ';
			}
			if ( $this->publication->startdate() ) {
				$date = $this->format_date( $this->publication->startdate() );
				if ( $date !== '' ) {
					$date = sprintf( '<span class="startdate">%s</span>', $date );
					$result .= $date . '. ';
				}
			}
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';			
			return trim( $result );
		}
		
		protected function format_report() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}
		
		protected function format_scholarly_edition() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			$result .= $this->format_publisher() . ' ';
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}
		
		protected function format_thesis() {
			$result = '';
			$result .= $this->format_authors() . ' ';
			$result .= $this->format_publication_date() . ' ';
			$result .= $this->format_title() . ' ';
			if ( $this->publication->fileddate() ) {
				$date = $this->format_date( $this->publication->fileddate() );
				if ( $date !== '' ) {
					$date = sprintf( '<span class="filed-date">%s</span>', $date );
					$result .= $date . '. ';
				}
			}
			$result .= $this->format_status() . ' ';
			//$result .= $this->format_links() . ' ';
			return trim( $result );
		}		
		
		//* Partials
		
		private function format_authors() {
			$result = '';
			if ( $this->publication->authors() ) {
				$authors = $this->format_person_list( $this->publication->authors() );
			} elseif ( $this->publication->editors() ) {
				$authors = $this->format_person_list( $this->publication->editors() );
			}
			if ( $authors !== '' ) $result = sprintf( '<span class="authors">%s</span>', $authors );
			return $result;
		}
		
		private function format_editors() {
			$result = '';
			$editors = $this->format_person_list( $this->publication->editors() );
			if ( $editors !== '' ) $result = sprintf( '<span class="editors">%s (eds.)</span>', $editors );
			return $result;
		}
		
		protected function format_publication_date() {
			$result = '';
			$date = $this->publication->publicationdate();
			if ( isset( $date['year'] ) && $date['year'] !== '' ) {
				$result = sprintf( '<span class="publication-date">(%s)<span> ', $date['year'] );
			}
			return $result;
		}
		
		protected function format_title() {
			// title In: editors (eds.) parenttitle. journal editors (eds.). edition.series. 
			$result = '';
			$title = trim( $this->publication->title() );
			$doi = trim( $this->publication->doi() );
			$parenttitle = trim( $this->publication->parenttitle() );
			$journal = trim( $this->publication->journal() );
			if ( $title !== '' ) {
				if ( $doi !== '' ) {
					$title = sprintf( '<a href="http://dx.doi.org/%s">%s</a>', $doi, $title ); 
				}
				if ( $parenttitle !== '' || $journal !== '' ) {
					$title = '"' . $title . '"';
				}
				$title = sprintf( '<span class="title">%s</span>', $title );
			}
			if ( $parenttitle !== '' ) {
				$editors = $this->format_editors();
				if ( $editors !== '' ) {
					$parenttitle .= ' ' . $editors;
				}
				$parenttitle = sprintf( '<span class="title-parent">%s</span>', $parenttitle );
			}
			if ( $journal !== '' ) {
				$editors = $this->format_editors();
				if ( $editors !== '' ) {
					$journal .= ' ' . $editors;
				}
				$journal = sprintf( '<span class="journal">%s</span>', $journal );
			}
			$edition = trim( $this->publication->edition() );
			if ( $edition !== '' ) {
				$edition = sprintf( '<span class="edition">%s</span>', $edition );
			}
			$series = trim( $this->publication->series() );
			if ( $series !== '' ) {
				$series = sprintf( '<span class="series">%s</span>', $series );
			}
			if ( $title !== '') $result .= $title;
			if ( $parenttitle !=='' ) $result .= ' In: ' . $parenttitle;
			if ( $journal !=='' ) $result .= ', ' . $journal;
			if ( $edition !=='' ) $result .= ' ' . $edition;
			if ($series !== '' ) {
				if ( $edition !=='' ) $result .= '.';
				$result .= $series;
			}
			if ( $result !== '' && substr( $result, -1 ) !== '.' ) $result .= '.';
			return $result;
		}
		
		private function format_issue() {
			// volume issue pages.
			$result = '';
			$volume = trim( $this->publication->volume() );
			if ( $volume !== '' ) {
				$volume = sprintf( '<span class="volume">%s</span>', $volume );
			}
			$issue = trim( $this->publication->issue() );
			if ( $issue !== '' ) {
				$issue = sprintf( '<span class="issue">(%s)</span>', $issue );
			}
			$pages = $this->format_pages(); 
			$result = trim( $volume . ' ' . $issue . ' ' . $pages );
			if ( $result !== '' && substr( $result, -1 ) !== '.' ) $result .= '.';
			return $result;
		}
		
		private function format_pages() {
			// start-end
			$result = '';
			if ( $pagination = $this->publication->pagination() ) {
				if ( isset( $pagination['begin'] ) && trim( $pagination['begin'] ) !== '' ) {
					$pages = trim( $pagination['begin'] );
					if ( isset($pagination['end']) && trim( $pagination['end'] ) !== '' ) {
						if ( trim( $pagination['begin'] ) !== trim( $pagination['end'] ) ) {
							$pages .= '-' . trim( $pagination['end'] );
						}
					}
					$result = sprintf( '<span class="pages">%s</span>', $pages );
				}
			}
			if ( $result !== '' && substr( $result, -1 ) !== '.' ) $result .= '.';
			return $result;			
		}
		
		private function format_publisher() {
			// place:publisher.
			$result = '';
			$publisher = trim( $this->publication->publisher() );
			$url = trim( $this->publication->publisherurl() );
			if ( $publisher !== '' ) {
				if ( $url !== '' ) {
					$publisher = sprintf( '<span class="publisher"><a href="%s">%s</a></span>', $url, $publisher );
				} else {
					$publisher = sprintf( '<span class="publisher">%s</span>', $publisher );
				}
			}
			$place = trim( $this->publication->placeofpublication() );
			if ( $place !== '' ) {
				$place = sprintf( '<span class="publisher-place">%s</span>', $place );
			}
			if ( $place !== '') $result .= $place;
			if ( $publisher !== '' ) {
				if ( $place !== '' ) $result .= ': ';
				$result .= $publisher;
			}
			if ( $result !== '' && substr( $result, -1 ) !== '.' ) $result .= '.';
			return $result;
		}
		
		private function format_status() {
			// [status]
			// Does not display "Published" or items without a publication status.
			$result = '';
			$status = trim( $this->publication->status() );
			if ( $status !== '' ) {
				if ( trim( $status() ) !== 'Published' ) {
					$result = sprintf( '[<span class="status">%s</span>]', $status );
				}
			}
			return $result;
		}
		
		private function format_notes() {
			$result = '';
			$notes = trim( $this->publication->notes() );
			if ( $notes !== '' ) {
				$result = sprintf( '(<span class="notes">%s</span>)', $notes );
			}
			return $result;
		}
				
	}
	
?>