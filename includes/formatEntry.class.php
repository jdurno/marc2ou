<?php

  /*Format select MARC record fields for display */

class FormatEntry 
{
  var $title;
  var $author;
  var $subjects;
  var $callNumbers;
  var $location;
  var $pub;
  var $urls;
  var $issn;
  var $isbn;
  var $controlNumber;
  var $field008;
  var $field700;
  var $field773;

  function FormatEntry()
  {
    $this->title = NULL;
    $this->author = NULL;
    $this->callNumber = NULL;
    $this->pub = NULL;
    $this->series = NULL;
    $this->urls = NULL;
    $this->issns = NULL;
    $this->isbns = NULL;
    $this->controlNumber = NULL;
    $this->field008 = NULL;
    $this->field700 = array();
    $this->field773 = array();
  }


  function getControlNumber ()
  {
    if ($this->controlNumber)
      {
	return $this->controlNumber;
      }
    else
      {
	return FALSE;
      }
    
  }

  function getTitle()
  {
    if ($this->title)
      {
	return $this->title;
      }
    else
      {
	return FALSE;
      }

  }

  function getAuthor()
  {
    if ($this->author)
      {
	return $this->author;
      }
    else
      {
	return FALSE;
      }
  }


  function getCallNumbers()
  {
    if ($this->callNumbers)
      {
	return $this->callNumbers;
      }
    else
      {
	return FALSE;
      }
  }

  function getPub()
  {
    if ($this->pub)
      {
	return $this->pub;
      }
    else
      {
	return FALSE;
      }
  }
  
  function getDate() {
  	  if ($this->field008) {
  	  	$date = substr($this->field008, 7, 4);
  	  	return $date;
  	  } else {
  	  	return FALSE;	  
  	  }	  
  	  
  }

    function getISBNs()
  {
    if ($this->isbns)
      {
	return $this->isbns;
      }
    else
      {
	return FALSE;
      }
  }

    function getISSNs()
  {
    if ($this->issns)
      {
	return $this->issns;
      }
    else
      {
	return FALSE;
      }
  }
  
  
  function getSeries()
  {
    if ($this->series)
      {
	return  $this->series;

      }
    else
      {
	return FALSE;
      }
  }


  function getURLs()
  {
    if ($this->urls)
      {
	return  $this->urls;

      }
    else
      {
	return FALSE;
      }
  }

    function getField008() {
  	
  	  if ($this->field008) {
  	  	return $this->field008;	  
  	  } else {
  	  	return FALSE;	  
  	  }
  		  
  	  
  }

    function getField700() {
  	
  	  if ($this->field700) {
  	  	return $this->field700;	  
  	  } else {
  	  	return FALSE;	  
  	  }
  		  
  	  
  }
  
  
  
  function getField773() {
  	
  	  if (count($this->field773 > 0)) {
  	  	return $this->field773;	  
  	  } else {
  	  	return FALSE;	  
  	  }
  		  
  	  
  }
  



  function process($field, $indicator)
  {

    //format fields differently, depending on what they are
    switch ($indicator)
      {
      case ('001'):
	$fieldObj = $field[0];
	$this->controlNumber = $fieldObj->data();
	break;

      case ('008'):
	$fieldObj = $field[0];
	$this->field008 = $fieldObj->data();
	break;	

	case('020'):
	foreach ($field as $fieldObj){
		$subfields = $fieldObj->subfields();
		//non-repeating field
		if (array_key_exists('a', $subfields))
		{
			$isbn = $subfields['a'][0];
			$this->isbns[] = $isbn;
		}
		
	}
		
	break;

	
	case('022'):
	foreach ($field as $fieldObj){
		$subfields = $fieldObj->subfields();
		//non-repeating field
		if (array_key_exists('a', $subfields))
		{
			$issn = $subfields['a'][0];
			$this->issns[] = $issn;
		} elseif (array_key_exists('y', $subfields)) {
		
			$issn = $subfields['y'][0];
			$this->issns[] = $issn;
		}
	}
		
	break;
	
	
      case ('100'):
	$fieldObj = $field[0];
	$subfields = $fieldObj->subfields();
	if (empty($this->author))
	  {
	    $this->author = $subfields['a'][0];
	  }

	break;

      case ('245'):
	$fieldObj = $field[0];
	$subfields = $fieldObj->subfields();
	/*
	foreach ($subfields as $ind=>$var){
	  print "$ind : $var | ";
	}
	*/
	$this->title = $subfields['a'][0];
	if (array_key_exists('b', $subfields))
	  {
	    $this->title .= '&nbsp;' .  $subfields['b'][0];
	  }
	  /*
	if (array_key_exists('c', $subfields))
	  {	
	    $this->author = $subfields['c'][0];
	  }
	if (array_key_exists('h', $subfields))
	{
	  $this->title .= ' ' . $subfields['h'][0];
	}
	*/
	break;

      case ('260'):
	$fieldObj = $field[0];
	$subfields = $fieldObj->subfields();
	
	if (array_key_exists(0, $subfields['a'])) {
		$this->pub['place'] = $subfields['a'][0];
	}
	if (array_key_exists(0, $subfields['b'])) {
		$this->pub['publisher'] = $subfields['b'][0];
	}
	
	if (array_key_exists(0, $subfields['c'])) {
		$this->pub['year'] = $subfields['c'][0];
	}

	break;


      case ('440'):

	foreach ($field as $fieldObj)
	{
	  $subfields = $fieldObj->subfields();
	  
	  $contents = '';
	  foreach ($subfields as $subfield)
	    {
	      foreach ($subfield as $subfieldPart)
		{
		  $contents .= " $subfieldPart";
		}	    
	    }

	  $this->series[] = $contents;
	}      

	break;

      case ('700'):

	foreach ($field as $fieldObj) {
	 $subfields = $fieldObj->subfields();
		
	if (array_key_exists('a', $subfields)){
		$name = $subfields['a'][0];
	      }

	
	  $this->field700[] = $name;
	 // echo $name;
	}      
	//exit;

	break;


	
	case('773'):


       	foreach ($field as $fieldObj)
	  {

	    $title = NULL; //subfield t (NR)
	    $vol = NULL; //subfield g (R)
	    $pub = NULL; //subfield d (NR)


	    $subfields = $fieldObj->subfields();
	    //non-repeating field
	    if (array_key_exists('t', $subfields))
	      {
		$title = $subfields['t'][0];
	      }
	    
	    //repeating field
	    if (array_key_exists('g', $subfields))
	      {

		foreach ($subfields['g'] as $i)
		  {
		    $vol .= '&nbsp;' . $i;
		  }
		
	      }

	      
	     if (array_key_exists('d', $subfields))
	      {
		$pub = $subfields['d'][0];
	      }



	    $this->field773[] = array('title' => $title,
					 'vol' => $vol,
					 'pub' => $pub);

      	}	
	
      	break;

      case ('856'):

	foreach ($field as $fieldObj)
	{
	  $url = $fieldObj->subfield('u');
	  $notes = $fieldObj->subfield('z');
	  $this->urls[] = array('url' => $url[0], 'notes' => $notes[0]);
	}      


	break;


      case ('990'):

       	foreach ($field as $fieldObj)
	  {

	    $call = NULL;
	    $loc = NULL;
	    $notes = NULL;


	    $subfields = $fieldObj->subfields();
	    //non-repeating field
	    if (array_key_exists('h', $subfields))
	      {
		$call = $subfields['h'][0];
	      }
	    
	    //repeating field
	    if (array_key_exists('i', $subfields))
	      {

		foreach ($subfields['i'] as $i)
		  {
		    $call .= ' ' . $i;
		  }
		
	      }
	    
	    //repeating field
	    $loc = array();
	    if (array_key_exists('b', $subfields))
	      {

		$loc = $subfields['b'];
	      }
	    
	    //repeating field
	    if (array_key_exists('z', $subfields))
	      {
		foreach ($subfields['z'] as $z)
		  {
		    $notes .= $z . ' ';
		  }
	      }
	


	    $this->callNumbers[] = array('call' => $call,
					 'loc' => $loc,
					 'notes' => $notes);
	  }      
	
	break;




      }





  }
  

}



?>