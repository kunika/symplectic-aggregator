<?php
	
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
	
	require_once( 'settings.php' );
	require_once( 'classes/database.class.php' );
	
	if ( isset( $_GET['username'] ) ) {
		$username = trim( $_GET['username'] );
	} else {
		if ( $querystring = parse_url( "$_SERVER[REQUEST_URI]", PHP_URL_QUERY ) ) {
			$querystring .= '&foo=true'; // a dummy query var used for detecting ampersands in query values
			$querystring = str_replace( '|', '%7C', $querystring );
			$querystring = str_replace( '+', '%2B', $querystring );
			$querystring = preg_replace( '/&(?=[^=]*&)/', '%26', $querystring );
			parse_str( $querystring, $query );
			if ( isset( $query['keywords'] ) ) {
				$keywords = preg_split( '/(\+|\|)/', $query['keywords'] );
				$keywords = str_replace( '&amp;', '&', $keywords );
			}
		}
	}
	
	$format = 'json';
	if ( isset( $_GET['format'] ) ) {
		$format = trim( $_GET['format'] );
	}
	
	$publications = array();
	if ( isset( $username ) ) {
		$db_author = new Database();
		$db_author->prepare('
			SELECT * 
			FROM `author` 
			WHERE `username`=:username
		');
		$db_author->bind( ':username', $username );
		$author = $db_author->single();
		$db_author->close();
		if ( !empty($author) ) {
			$db_publication = new Database();
			$db_publication->prepare('
				SELECT `publication`.*, `author_publication`.`favourite`
				FROM `publication`
				LEFT JOIN `author_publication` ON `publication`.`id` = `author_publication`.`publication`
				WHERE `author_publication`.`author`=:author AND `author_publication`.`visible` = TRUE
				ORDER BY `publication`.`published` DESC, `publication`.`id` DESC
			');
			$db_publication->bind( ':author', $author['id'] );
			$publications = $db_publication->resultset();
			$db_publication->close();
			$favourites = getFavourites( $publications );
		}		
	} elseif ( isset( $keywords ) ) {
		$db_publication = new Database();
		$db_publication->prepare('
			SELECT `publication`.*
			FROM `publication_tag`, `publication`, `tag`
			WHERE `publication_tag`.`tag` = `tag`.`id`
			AND (`tag`.`name` IN ("' . implode('","', $keywords) . '"))
			AND `publication`.`id` = `publication_tag`.`publication`
			GROUP BY `publication`.`id`
			' . (strpos( $query['keywords'], '|' ) !== false ? '' : ' HAVING COUNT( `publication`.`id` )=' . count( $keywords ) ) . '
			ORDER BY `publication`.`published` DESC, `publication`.`id` DESC;
		');
		$publications = $db_publication->resultset();
		$db_publication->close();
	}
	
	if ( $format == 'html' ) {
		$db_type = new Database();
		$db_type->prepare('
			SELECT * 
			FROM type 
			ORDER BY id;
		');
		$types = $db_type->resultset();
		$db_type->close();
		foreach ( $types as $key=>$value ) {
			$related = getPublicationByType( $publications, $value['id'] );
			if ( !empty( $related ) ) $types[$key]['publications'] = $related;
			else unset( $types[$key] );
		}
	}
	
	$error = null;
	if ( empty( $publications ) ) {
		if ( isset( $username ) && isset( $author ) ) $error = 'The author with username "' . $username . '" was not found.';
		else $error = 'No publications found.';
	}
	
	function getFavourites( $publications ) {
		$result = array();
		foreach( $publications as $publication ) {
			if ( $publication['favourite'] == true ) array_push( $result, $publication );
		}
		return $result;
	}
	
	function getPublicationByType( $publications, $type ) {
		$result = array();
		foreach ( $publications as $publication ) if ( $publication['type'] === $type) array_push($result, $publication );
		return $result;
	}
	
	if ( $format == 'html' ) {
		header('Content-Type: text/html; charset=utf-8');
	} else {
		header('Content-Type: application/json; charset=utf-8');
	}

?>

<?php if ( $format == 'html' ): ?>
	<?php if ( $error ): ?>
		<?php echo '<!--  Symplectic: ' . $error . ' -->'; ?>
	<?php else: ?>
		<?php echo '<!--  Symplectic: ' . count( $publications ) . ' publications found. -->'; ?>
		<?php if ( isset( $favourites ) && count( $favourites ) > 0 ) : ?>
		<div id="symplectic-featured" class="symplectic-block">
			<h3>Featured</h3>
			<ul>
				<?php foreach ( $favourites as $favourite ): ?>
				<li><?php echo $favourite['citation']; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		<?php if ( isset( $types ) && count( $types ) > 0 ) : ?>
			<div id="symplectic-menu" class="symplectic-block">
				<h3>Publications</h3>
				<ul>
				<?php foreach ( $types as $type ): ?>
					<?php if ( isset( $type['publications'] ) ): ?>
						<li><a href="#symplectic-<?php echo $type['name']; ?>"><?php echo $type['plural']; ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
				</ul>
			</div>
			<?php foreach ( $types as $type ): ?>
				<?php if ( isset( $type['publications'] ) ): ?>
					<div id="symplectic-<?php echo $type['name']; ?>" class="symplectic-block">
						<h3><?php echo $type['plural']; ?></h3>
						<ul>
							<?php foreach ( $type['publications'] as $publication ): ?>
							<li><?php echo $publication['citation']; ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>	
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endif; ?>
<?php else: ?>
	<?php if ( $error ): ?>
		<?php echo json_encode( array( 'error'=>$error ) ); ?>
	<?php else: ?>
		<?php echo json_encode( $publications ); ?>
	<?php endif; ?>
<?php endif; ?>
