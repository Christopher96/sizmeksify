<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

/*

CHECKS

HTML Document

EBLoader/Adkit is included
	Local or External
	Secure or Unsecure
		Adkit
			adkit.onReady
		EBloader
			EB.Initialized

Fallback detection fallback/backup/default (png,gif,jpg)
	Dimensions



Clickthrough implemented
	EB.clickthrough()
	with or without parameters


*/

// Clean input arrays
$clean = array();
$_REQUEST = cleanArray( $_REQUEST );
$_POST = cleanArray( $_POST );
$_GET = cleanArray( $_GET );

function cleanArray($array){
  global $clean;
  $clean = array();

  array_walk_recursive($array, function($value, $key){
    global $conn, $clean;

    $value = trim($value);
    $value = stripslashes($value);
    $value = htmlspecialchars($value);
    $value = htmlentities($value);
    $value = strip_tags($value);
    $value = str_replace(array("\r\n", "\n", "\r"), ' ', $value);

    $clean[$key] = $value;
  });

  return $clean;
}


// INIT DATA ARRAYS
$return = $checks = $errors = $warnings = $input = array();

init();

function init() {
	global $errors;

	if(isset($_FILES['zip'])) {
	
		$zip_file = $_FILES['zip'];
	
		if( $zip_file['error'] !== UPLOAD_ERR_OK ){
			include "file_error.php";
			$errors[] = "File error: " . file_error( $zip_file["error"] );
		}
	
		switch ($zip_file['type']) {
			case 'application/zip':
			case 'application/x-zip-compressed':
			break;
			default:
				$errors[] = "The workspace must be a zipfile";
			break;
		}
	} else {
		$errors[] = "No file was uploaded.";
	}
	
	if(empty($errors)){
		if( isset($_POST["width"]) && isset($_POST["height"]) && isset($_POST["format"]) ) {
			handleInput($zip_file);
			validateZip($zip_file);
		} else {
			$errors[] = "Fill in all fields.";
		}
	}
}

function handleInput($zip_file) {
	global $input;
	
	switch ($_POST['format']) {
		case "rich":
			$max_size = 10000;
		break;
		default:
			$max_size = 200;
		break;
	}

	$size = round( $zip_file['size']/1000, 2 );
	$input['progress'] = min((($size / $max_size)*100), 100)."%";
	$input['size'] = $size." / ".$max_size." kb";
	$input['name'] = $zip_file['name'];
	$input['pass'] = ($size < $max_size);
	$input['type'] = "zip";
}

function validateZip($zip_file) {
	global $errors, $checks;
	

	if($zip = zip_open($zip_file['tmp_name'])){

		$files = scanZip( $zip );

		$images = array();
	
		foreach ( $files as $file ) {

			$ext = $file['extension'];

			switch($ext) {
				case "html":
					if( $html = isValidHTML( $file ) ){
						$checks['document'] = array("path" => $html['path']);
			
						scanHTMLForLibrary( $html );
						scanForImplementation( $html );
					}
				break;
				case "js":
					scanForImplementation( $file );
				break;
				case "jpg":
				case "png":
				case "gif":
					if( $image = @getimagesizefromstring( $file['contents'] ) ){
						if($_POST['width'] == $image[0] && $_POST['height'] == $image[1]) 
							$images[] = array( "path" => $file['path'] );
					}
				break;
			}
		}

		if(!empty($images)) {
			$checks['fallback'] = array( "images" => $images, "width" => $_POST['width'], "height" => $_POST['height'] );
		}
	
		zip_close($zip);
	
	} else {
		$errors[] = "Failed to open zipfile";
	}
}


function scanZip( $zip ){
	$files = array();

	while ( $zip_entry = zip_read( $zip ) ) {

		if ( zip_entry_open( $zip, $zip_entry, "r" ) ) {

			$path = zip_entry_name( $zip_entry );
			$extension = pathinfo( $path, PATHINFO_EXTENSION);

	    	$size = zip_entry_filesize( $zip_entry );
	        $contents = zip_entry_read( $zip_entry, $size );

	        zip_entry_close( $zip_entry );

	       	$files[] = array(
	       		"path" => $path,
	       		"extension" => $extension,
	       		"contents" => $contents
	       	);

	    }
    }
	
    return $files;
}


function isValidHTML( $file ){

	if( $file['extension'] == 'html' && substr_count( $file['path'], "/") <= 2 ){
		return $file;
	}

	return false;
}

function scanForImplementation( $file ){
	global $checks;

	$contents = $file['contents'];
	$lines = preg_split("/((\r?\n)|(\r\n?))/", $contents);

	$line_num = 0;

	foreach( $lines as $line ){
		$line_num++;

		$info = array( "line" => trim( $line ), "line_num" => $line_num, "path" => $file['path'] );

		   if( $type = matchLineForInitialize( $line ) ){
			   $checks['initialize'] = $info;
			$checks['initialize']['type'] = $type;
		}

		if( $click = matchLineForClickthrough( $line ) ){
			$checks['clickthrough'] = $info;
		}
	}
}

function matchLineForInitialize( $line ){

	// adkit.onReady( startAd )
    if( preg_match( "/.*(adkit.onReady\(\s?[A-Za-z]+\s?\)).*/", $line ) ){
    	return "Adkit";
    }

    // EB.addEventListener(EBG.EventName.EB_INITIALIZED, startAd)
    if( preg_match( "/\.*(EB.addEventListener\s?\(\s?EBG.EventName.EB_INITIALIZED\s?,\s?[A-Za-z]+\s?\))\.*/", $line ) ){
    	return "EBLoader";
    }

    return false;
}

function matchLineForClickthrough( $line ){

	// adkit.onReady( startAd )
    if( preg_match( "/\w*(EB.clickthrough\s?\(\s?\));?\w*/", $line ) ){
    	return true;
    }

    return false;
}

function scanHTMLForLibrary( $html ){
	global $checks;

	$DOM = new DOMDocument;
	@$DOM->loadHTML( $html['contents'] );

	$scripts = $DOM->getElementsByTagName('script');
	
	foreach ($scripts as $script){

		if( $script->hasAttribute("src") ){

			$source = $script->getAttribute("src");
			$urlobj = parse_url( $source );

			if( isset( $urlobj['path'] ) ){

				$library = basename( $urlobj['path'] );

				if( $library == "adkit.js" || $library == "EBLoader.js" ){

					$type = "Local";

					if( isset( $urlobj['scheme'] ) ){
						if( $urlobj['host'] == "secure-ds.serving-sys.com" && $urlobj['scheme'] === "https" ){
							$type = "Secure External ( HTTPS )";
						} else {

						 	$type = "Unsecure External ( HTTP )";
						}
					}

					$checks['library'] = array( "type" => $type, "library" => $library, "source" => $source );
				}
			}
		}
	}

	return false;
}

if( !empty($errors) ){
	$return['errors'] = $errors;
}

if( !empty($warnings) ){
	$return['warnings'] = $warnings;
}

$return['input'] = $input;
$return['checks'] = $checks;

echo json_encode($return);

?>
