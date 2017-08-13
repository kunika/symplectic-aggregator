<?php
	
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
	
	require_once( 'settings.php' );
	require_once( 'classes/database.class.php' );
	require_once( 'classes/symplectic_api.class.php' );
	require_once( 'classes/symplectic_format.class.php' );
	
	$errors = array();
	
	$symplectic = new SymplecticAPI();
	
	try {
		if ( isset( $_GET['username'] ) && trim( $_GET['username'] ) !== '' ) {
			$authors = $symplectic->getUser( trim( $_GET['username'] ) );
		} else {
			$types = $symplectic->getPublicationTypes();
			$authors = $symplectic->getUsersInGroup( SYMPLECTIC_GROUP_ID );
		}		
	} catch ( Exception $exception ) {
		array_push( $errors, $exception );
	}

	/* --------------------
		Types
	   ---------------------*/
	
	if ( isset( $types ) && !empty( $types ) ) {
		$db_type = new Database();
		$db_type->prepare('
			INSERT INTO type (
				`id`,
				`name`,
				`singular`,
				`plural`,
				`fields`
			)
			VALUES (
				:id,
				:name,
				:singular,
				:plural,
				:fields
			)
			ON DUPLICATE KEY UPDATE
				`name` = VALUES(`name`),
				`singular` = VALUES(`singular`),
				`plural` = VALUES(`plural`),
				`fields` = VALUES(`fields`);
		');
		foreach ( $types as $type ) {
			$db_type->bind( ':id', $type->id() );
			$db_type->bind( ':name', $type->name() );
			$db_type->bind( ':singular', $type->displayname()['singular'] );
			$db_type->bind( ':plural', $type->displayname()['plural'] );
			$db_type->bind( ':fields', implode( ',', array_keys( $type->fields() ) ) );
			$db_type->beginTransaction();
			$db_type->execute();
			$db_type->endTransaction();
		}
		$db_type->close();
	}
	
	/* --------------------
		Authors and Publications
	   ---------------------*/
	
	if ( isset( $authors ) && !empty( $authors ) ) {
		$db_author = new Database();
		$db_author->prepare('
			INSERT INTO author (
				`id`,
				`proprietary-id`,
				`username`,
				`title`,
				`initials`,
				`firstname`,
				`lastname`,
				`academic`,
				`currentstaff`,
				`created`,
				`modified`
			)
			VALUES (
				:id,
				:pid,
				:username,
				:title,
				:initials,
				:firstname,
				:lastname,
				:academic,
				:currentstaff,
				:created,
				:modified
			)
			ON DUPLICATE KEY UPDATE
				`proprietary-id` = VALUES(`proprietary-id`),
				`username` = VALUES(`username`),
				`title` = VALUES(`title`),
				`initials` = VALUES(`initials`),
				`firstname` = VALUES(`firstname`),
				`lastname` = VALUES(`lastname`),
				`academic` = VALUES(`academic`),
				`currentstaff` = VALUES(`currentstaff`),
				`created` = VALUES(`created`),
				`modified` = VALUES(`modified`);
		');
		$db_publication = new Database();
		$db_publication->prepare('
			INSERT INTO publication (
				`id`,
				`type`,
				`title`,
				`published`,
				`citation`,
				`abstract`,
				`doi`,
				`status`,
				`labels`,
				`keywords`,
				`created`,
				`modified`
			)
			VALUES (
				:id,
				:type,
				:title,
				:published,
				:citation,
				:abstract,
				:doi,
				:status,
				:labels,
				:keywords,
				:created,
				:modified
			)
			ON DUPLICATE KEY UPDATE
				`type` = VALUES(`type`),
				`title` = VALUES(`title`),
				`published` = VALUES(`published`),
				`citation` = VALUES(`citation`),
				`abstract` = VALUES(`abstract`),
				`doi` = VALUES(`doi`),
				`status` = VALUES(`status`),
				`labels` = VALUES(`labels`),
				`keywords` = VALUES(`keywords`),
				`created` = VALUES(`created`),
				`modified` = VALUES(`modified`);
		');
		$db_author_publication = new Database();
		$db_author_publication->prepare('
			INSERT INTO author_publication (
				`author`,
				`publication`,
				`visible`,
				`favourite`
			)
			VALUES (
				:author,
				:publication,
				:visible,
				:favourite
			)
			ON DUPLICATE KEY UPDATE
				`visible` = VALUES(`visible`),
				`favourite` = VALUES(`favourite`);
		');
		$db_tag = new Database();
		$db_tag->prepare('
			INSERT INTO tag (
				`name`,
				`slug`
			)
			VALUES (
				:name,
				:slug
			);
		');
		$db_tag_select = new Database();
		$db_tag_select->prepare('
			SELECT id
			FROM tag
			WHERE LOWER(name)=LOWER(:name);
		');
		$db_publication_tag = new Database();
		$db_publication_tag->prepare('
			INSERT INTO publication_tag (
				`publication`,
				`tag`
			)
			VALUES (
				:publication,
				:tag
			)
			ON DUPLICATE KEY UPDATE
				`publication` = VALUES(`publication`),
				`tag` = VALUES(`tag`);
		');
		foreach ( $authors as $author ) {
			try {
				// author
				$db_author->bind( ':id', $author->id() );
				$db_author->bind( ':pid', $author->proprietaryid() );
				$db_author->bind( ':username', $author->username() );
				$db_author->bind( ':title', $author->title() );
				$db_author->bind( ':initials', $author->initials() );
				$db_author->bind( ':firstname', $author->firstname() );
				$db_author->bind( ':lastname', $author->lastname() );
				$db_author->bind( ':academic', boolean( $author->isacademic() ) );
				$db_author->bind( ':currentstaff', boolean( $author->iscurrentstaff() ) );
				$db_author->bind( ':created', date( 'Y-m-d H:i:s', strtotime( $author->createdwhen() ) ) );
				$db_author->bind( ':modified', date( 'Y-m-d H:i:s', strtotime( $author->lastmodifiedwhen() ) ) );
				$db_author->beginTransaction();
				$db_author->execute();
				$db_author->endTransaction();
				// publications
				$publications = $symplectic->getUserPublications( $author->username() );
				foreach ( $publications as $publication ) {
					$format = new HarvardFormat( $publication );
					$db_publication->bind( ':id', $publication->id() );
					$db_publication->bind( ':type', $publication->typeid() );
					$db_publication->bind( ':title', $publication->title() );
					$date = $publication->publicationdate();
					if ( $date ) $date = ( isset( $date['year'] ) ? $date['year'] : 0 ) . '-' . ( isset( $date['month'] ) ? $date['month'] : 0 ) . '-' . ( isset( $date['day'] ) ? $date['day'] : 0 );
					$db_publication->bind( ':published', $date );			
					$db_publication->bind( ':citation', $format->citation() );
					$db_publication->bind( ':abstract', $publication->abstract() );
					$db_publication->bind( ':doi', $publication->doi() );
					$db_publication->bind( ':status', $publication->status() );
					if ( $publication->labels() ) $db_publication->bind( ':labels', '"' . implode( '","', $publication->labels() ) . '"' );
					else $db_publication->bind( ':labels', '');
					if ( $publication->keywords() ) $db_publication->bind( ':keywords', '"' . implode( '","', $publication->keywords() ) . '"' );
					else $db_publication->bind( ':keywords', '');
					$db_publication->bind( ':created', date( 'Y-m-d H:i:s', strtotime( $publication->createdwhen() ) ) );
					$db_publication->bind( ':modified', date( 'Y-m-d H:i:s', strtotime( $publication->lastmodifiedwhen() ) ) );
					$db_publication->beginTransaction();
					$db_publication->execute();
					$db_publication->endTransaction();			
					// author-publications
					$db_author_publication->bind( ':author', $author->id() );
					$db_author_publication->bind( ':publication', $publication->id() );
					$db_author_publication->bind( ':visible', boolean( $publication->isvisible() ) );
					$db_author_publication->bind( ':favourite', boolean( $publication->isfavourite() ) );
					$db_author_publication->beginTransaction();
					$db_author_publication->execute();
					$db_author_publication->endTransaction();
					// labels
					if ( $labels = $publication->labels() ) {
						foreach ( $labels as $label ) {
							$db_tag_select->bind( ':name', $label );
							$term = $db_tag_select->single();
							if ( empty( $term ) ) {
								$db_tag->bind( ':name', $label );
								$db_tag->bind( ':slug', slugfy( $label ) );
								$db_tag->beginTransaction();
								$db_tag->execute();
								$term['id'] = $db_tag->lastInsertId();
								$db_tag->endTransaction();
							}
							$db_publication_tag->bind( ':publication', $publication->id() );
							$db_publication_tag->bind( ':tag', $term['id'] );
							$db_publication_tag->beginTransaction();
							$db_publication_tag->execute();
							$db_publication_tag->endTransaction();
						}
					}
				}
			} catch ( Exception $exception ) {
				array_push( $errors, $exception );
			}
		}
		$db_author->close();
		$db_publication->close();
		$db_author_publication->close();
		$db_tag->close();
		$db_tag_select->close();
		$db_publication_tag->close();
	}
	
	if ( !empty( $errors ) ) {
		echo 'Cron job encountered errors.' . PHP_EOL . PHP_EOL;
		foreach ( $errors as $error ) {
			echo 'Message: ' . $error->getMessage() . PHP_EOL;
			echo 'File: ' . $error->getFile() . '(' . $error->getLine() . ')' . PHP_EOL;
			echo 'Stack trace: ' . $error->getTraceAsString() . PHP_EOL . PHP_EOL;
		}
	} else {
		echo 'Cron job completed successfully.';
	}
	
	function boolean ($string) {
		if ( $string === 'true' ) return true;
		return false;
	}
	
	function slugfy ( $tag ) {
		$result = $tag;
		$result = str_replace( '&', 'and', $result );
		$result = preg_replace( '/[^A-Za-z0-9 ]/', '', $result );
		$result = preg_replace( '/\s{2,}/', ' ', $result );
		$result = strtolower( str_replace(' ', '-', $result ) );
		return $result;
	}	
	
?>