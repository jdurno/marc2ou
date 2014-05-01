<?php
/*
Take a bib ID from Voyager, get details via Z39.50, create an OpenURL string, and forward to Ares.
Reference: http://raymondyee.net/wiki/MarcXmlToOpenUrlCrosswalk
Last updated: August 13, 2012, JD
*/

require_once ("includes/php-marc.php");
require_once ("includes/formatEntry.class.php");
include_once ("includes/locations.inc.php");

$host = 'europa.library.uvic.ca:2100/mcpherson';

$bibid = isset($_REQUEST['bibid'])? $_REQUEST['bibid'] : NULL;

if ($bibid) {
	$query = "@attr 1=12 $bibid";
} else {
	echo "Did not get Bib ID. Cannot continue.";
	exit;	
}

//set up the search
$id = yaz_connect($host);
yaz_syntax($id, "usmarc");
yaz_search($id, "rpn", $query);
yaz_wait();

$hits = yaz_hits($id);

if ($hits < 1) {
	echo "Got no hits, cannot continue.";
	exit;	
}

//should have only got one record
$rec = yaz_record($id, 1, "raw");

$file = new USMARC($rec);

$record = $file->next();

$leader = $record->leader();

//prepare the array of OpenURL parameters
$openURLParams = array();

/*
/* FORMAT
*/

//Lots of formats in the catalogue that OpenURL doesn't know about
$openURLParams['format'] = NULL; 
$openURLParams['genre'] = NULL;

/*seventh character can be:
    a - Monographic component part (format:book, genre: bookitem)
    b - Serial component part (format: journal, genre: article)
    c - Collection (format: book)
    d - Subunit (format: book)
    i - Integrating resource (format: book)
    m - Monograph/Item (format: book)
    s - Serial (format: journal)
*/

$formatMap = array (
			'am' => 'book',
			'as' => 'journal',
			'aa' => 'book',
			'ab' => 'journal'
	);

$formatKey = substr($leader, 6, 2);

/*
//Just for testing
echo '<b>Bib data</b><br />';

echo $leader . '<br />';

echo 'Format index: ' .  $formatKey . '<br />';
*/





if (array_key_exists($formatKey, $formatMap)) {

	//echo 'Format : ' . $formatMap[$formatKey] . '<br />';
	
	$openURLParams['format'] = $formatMap[$formatKey];
	$openURLParams['genre'] = $formatMap[$formatKey];
	
}


//Parse the record

$fields = $record->fields();

$formatter = new FormatEntry;

 foreach ($fields as $ind => $field)
 {
      $formatter->process($field, $ind);
 }

/*
/* FIELD 773
/* Special case, means the item is part of a larger work, typically either
/* a book chapter or journal article
*/
  
 //bibid=1803393 for testing
 if($formatter->getField773()) {
 	$field773 = $formatter->getField773();
 	//echo $field773[0]['title'] . '<br />';
 	//echo $field773[0]['vol'] . '<br />';
 	//echo $field773[0]['pub'] . '<br />';
 	
 	//genre changes to bookitem or article, depending on format
 	if ($openURLParams['format'] == 'book') {
 		$openURLParams['genre'] = 'bookitem';	
 	} else {
 		$openURLParams['genre'] = 'article';	
 	}
 	
 	$openURLParams['rft.title'] = trimMARCJunk($field773[0]['title']);
 	
 	
 }
 
 

/*
/* DATE
/* Typically year for books
/* Only valid for books. Date for journals refers to a specific issue
*/
 
if ($openURLParams['format'] !== 'journal') {
 
	 if ($formatter->getField008()) {
		//echo $formatter->getField008() . '<br />';	 
	 }
	 
	 
	 if ($formatter->getDate()) {
		//echo $formatter->getDate() . '<br />';	
		$openURLParams['rft.date'] = $formatter->getDate();
	 }
} 
/*
/* ISSN
/* Can only have one in Open URL
*/
 
 
 
 if ($formatter->getISSNs()) {
 	$issns = $formatter->getISSNs();
 	
 	foreach ($issns as $issn) {
 		//echo $issn . '<br />';	
 	}
 	
 	$openURLParams['rft.issn'] = $issns[0];
 	
 	
 	
 	
 }
 
 /*
/* ISBN
/* Can only have one in Open URL
*/
 
 
 
  if ($formatter->getISBNs()) {
  	  
 	$isbns = $formatter->getISBNs();
 	
 	/*
 	foreach ($isbns as $isbn) {
 		echo $isbn . ' : ' . cleanISBN($isbn) . '<br />';	
 	}
 	*/
 	
 	if (cleanISBN($isbns[0])) {
 		$openURLParams['rft.isbn'] = cleanISBN($isbns[0]); 	
 	}
 	
 }
 
/*
/* AUTHOR
/* 
*/

//note, if 773 exists, we should try to derive this from the 700$a field instead
$multiAuthors = array();

if ($formatter->getField773()) {
	
	if ($formatter->getField700()) {
		$authors = $formatter->getField700();
		foreach ($authors as $author) {
			$multiAuthors[] = trimMARCJunk($author);	
		}
	}

} elseif ($formatter->getAuthor()) {
 	//echo trimMARCJunk($formatter->getAuthor()) . '<br />'; 
 	
 	$openURLParams['rft.au'] = trimMARCJunk($formatter->getAuthor());	
 }
 
 
/*
/* TITLE
/* 
*/
 
//note, if 773 exists, this becomes the 'rft.atitle' parameter
 
 if ($formatter->getTitle()) {
 	//echo trimMARCJunk( $formatter->getTitle() ) . '<br />'; 
 	
 	$key = 'rft.title';
 	if ($formatter->getField773()) {
 		$key = 'rft.atitle';	
 	}
 	
 	$openURLParams[$key] = trimMARCJunk($formatter->getTitle());
 }

/*
/* PUBLISHER AND PLACE
/* ONLY NEEDED FOR BOOKS
*/

if ($openURLParams['format'] !== 'journal') {

	  if ($formatter->getPub()) {
		$pub = $formatter->getPub();
		if (array_key_exists('publisher', $pub)) {
			///echo trimMARCJunk($pub['publisher']) . '<br />';
			$openURLParams['rft.pub'] = trimMARCJunk($pub['publisher']);
			
		}
		
		if (array_key_exists('place', $pub)) {
			//echo trimMARCJunk($pub['place']) . '<br />';
			$openURLParams['rft.place'] = trimMARCJunk($pub['place']);
		}
		
		/* Don't need this, get it from the 008 field
		if (array_key_exists('year', $pub)) {
			echo $pub['year'] . '<br />';	
		}
		*/	
		
	 }
 
}
 
/*
/* CALL NUMBERS
/* 
*/
 
 
 if ($formatter->getCallNumbers()) {
 //an array of call, loc, notes	 
 	$callnumbers = $formatter->getCallNumbers();
 	$preferred = array();
 	$others = array();
 	
 	foreach ($callnumbers as $cnum) {
 		
 		//echo $cnum['call'] . ' ';
 		
 		foreach ($cnum['loc'] as $loc) {
 			//echo '[' . $loc . ':';
 			//echo $locationCodes[$loc] . '] ';
  			
 			if ($loc == 'main') {			
 				$preferred[] = 	$cnum['call'] . '[McPherson Library]';
 			} else {
 				
 				if (array_key_exists($loc, $locationCodes)) {
 					$loc = $locationCodes[$loc];
 				}
 				
 				$others[] = $cnum['call'] . '[' . $loc . ']';
 				
 			}

 		}
 		
 		if (count($preferred) > 0) {
 			$openURLParams['cnum'] = $preferred[0];	
 		} elseif (count($others) > 0) {
 			$openURLParams['cnum'] = $others[0];
 		}
 		
 		
 		
 		//echo '<br />';	
 	}
 	 
 }
 
/*
/* URLS
/* 
*/
 
 if ($formatter->getURLs()) {
 	$urls = $formatter->getURLs(); 
 	/*
 	foreach ($urls as $url) {
 		echo $url['url'] . '<br />';	
 	}
 	*/
 		 
 }

 
/*
/* GENERATE THE OPENURL
*/
/*
echo '<br />';
echo '<br />';
echo '<b>OpenURL</b><br />';
*/


 
$openURL = '?ctx_ver=Z39.88-2004';
$openURL .= '&ctx_enc=info:ofi/enc:UTF-8';
$openURL .= '&rfr_id=info:sid/voyager.library.uvic.ca';

if ($openURLParams['format']) {
	$openURL .= '&rft_val_fmt=info:ofi/fmt:kev:mtx:' . $openURLParams['format'];
}
unset ($openURLParams['format']);

if ($openURLParams['genre']) {
	$openURL .= '&rft.genre=' . $openURLParams['genre'];
}
unset ($openURLParams['genre']);


if (count($multiAuthors) > 0) {
	foreach ($multiAuthors as $author) {
		$openURL .= '&rft.au=' . urlencode($author);	
	}
}

foreach ($openURLParams as $key => $value) {
	$openURL .= '&' . $key . '=' . urlencode($value);	
}


/*
echo $openURL;

echo '<br />';

echo '<br />';
echo '<br />';

echo '<b>Links</b><br />';

echo '<a href="' . $ouParserHost . $openURL . '">Link to OpenURL parser</a>' . '<br />';
echo '<br />';

echo '<a href="' . $aresHost . $openURL . '">Link to ARES</a>' . '<br />';
*/


$ouParserHost = 'http://library.uvic.ca/extfiles/360Link/index.php';

//$aresHost = 'https://coursematerials.library.uvic.ca/ares/ares.dll/OpenURL?';

header('location: ' . $ouParserHost . $openURL);



function cleanISBN($isbn) {
	//isbns typically have lots of junk in the field
	//look for a string of 10 or 13 digits and ignore everything else
	
	$longISBN = "/\b\d{13}\b/";
	$shortISBN = "/\b\d{10}\b/";
	
	if (preg_match($longISBN, $isbn, $matches)) {
		return $matches[0];	
	} elseif (preg_match($shortISBN, $isbn, $matches)) {
		return $matches[0];	
	} else {
		return FALSE;	
	}
	
}
 

function trimMARCJunk($data) {
	//MARC fields not atypically end in junk like / , . : plus spaces
	//get rid of this stuff
	
	$data = rtrim ($data);
	$data = rtrim($data, '.');
	$data = rtrim($data, '/');
	$data = rtrim($data, ',');
	$data = rtrim($data, ':');
	$data = rtrim($data, ';');	
	$data = rtrim ($data);
	return $data;
	
}

 
 
 
?>
