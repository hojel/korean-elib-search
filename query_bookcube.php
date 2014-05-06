<?php
// -*- coding: utf-8 -*-
/* query_bookcube.php
 *	search book from a library based on Dasan Fx platform
 * Input:
 *	type   : [title|author]
 *	keyword: <string>
 *	url    : library address
 *
 * released under GPLv3
 */
/*
 * search URL: /FxLibrary/xml/productSearch/
 * Input: GET method
 *	searchoption  : 1(AND), 2(OR), 3(전방일치)
 *	keyoption2    : 1(title), 2(author)    
 *	category_type : book
 *	contextRoot   : /FxLibrary
 *	x             : 38 ?
 *	y             : 10 ?
 *	pageCount     : 10 ?
 *	itemdv        : 1 ?
 *	keyword       : query string
 *	itemCount     : maximum #items in result
 * Output: XML output
 *	Products
 *	  ProductListParam
 *	    keyword <CDATA>
 *	    keyoption
 *	    keyoption2
 *	    SortList
 *	      sort id="<num>" value="<str>"
 *	    sort <num>
 *	    page <num>
 *	    totalItem <num>
 *	    totalVolume <num>
 *	    itemCount <num>
 *	    pageCount <num>
 *	    categoryList
 *	    category
 *	    itemdv <num>
 *	  Product...
 *	    num <id>
 *	    legacyCode <num>
 *	    name <CDATA>
 *	    publisher <CDATA>
 *	    author <CDATA>
 *	    supplier <CDATA>
 *	    category <CDATA>
 *	    category_type [book]
 *	    publisherDate <date>
 *	    buyDate <date>
 *	    listImage <url>
 *	    detailImage <url>
 *	    largeImage <url>
 *	    thumbnailImage <url>
 *	    longExplain <str>
 *	    shortExplain <str>
 *	    contents <str>
 *	    volume <num>		// available
 *	    nowLendCount <num>		// rent
 *	    nowReserveCount <num>	// reserved
 *	    format [xml | pdf]
 *	    isReservable   [true | false]
 *	    isMaxLendLimit [true | false]
 *	    Terminals
 *	      Terminal <@groupNum=[smartphone]>
 *	        modelNum    [B-612 | B-815 | IPHONE | IPOD | IPAD | ANDROID]
 *	        name
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$home_url = $_GET['url'];

$type_map = array('title'=>'1', 'author'=>'2');
$fields = array(
	    'searchoption' => '1',	    // 1(AND),2(OR),3(전방일치)
	    'keyoption2'   => $type_map[$srchtype],
	    'category_type' => 'book',
	    'contextRoot' => '/FxLibrary',
	    'pageCount' => '1',
	    'itemCount' => '10',
	    //'itemdv' => '1',
	    //'x' => '38',
	    //'y' => '10',
	    'keyword' => $keyword
	    );

// output frame
$odom = new DOMDocument("1.0", "UTF-8");
$root = $odom->appendChild( $odom->createElement("result") );

$node = $root->appendChild( $odom->createElement("search_url") );
$url = $home_url."/product/list/?".http_build_query($fields,"&");
$node->appendChild( $odom->createTextNode( $url ) );

// XML query
$url = $home_url."/xml/productSearch/?".http_build_query($fields,"&");

//$result = simplexml_load_file($url);
//-- handle redirection with access key
$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"User-Agent: Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)\r\n"
  )
);
$context = stream_context_create($opts);
$xmlstr = @file_get_contents($url, false, $context);

// error handling
if (strpos($http_response_header[0], "200") == FALSE) { 
  $node = $root->appendChild( $odom->createElement("errmsg", "OPEN_FAIL") );
} else {
  // handling redirection
  if (substr($xmlstr, 0, 6) == "<html>") {
    preg_match("/document\.cookie\s*=\s*'(.*?)'/", $xmlstr, $match);
    $opts['http']['header'] = $opts['http']['header']."Cookie: $match[1]\r\n";
    $context = stream_context_create($opts);
    $xmlstr = file_get_contents($url, false, $context);	// reload
  }
  $result = simplexml_load_string($xmlstr);

  // normal XML parsing
  foreach ($result->xpath('/Products/Product') as $binfo) {
    $book = $root->appendChild( $odom->createElement("book") );

    $url = $binfo->listImage;
    if (substr($url,0,1) == "/") $url = $home_url.$url;
    $node = $book->appendChild( $odom->createElement("thumb") );
    $node->appendChild( $odom->createTextNode( $url ) );

    $node = $book->appendChild( $odom->createElement("title") );
    $node->appendChild( $odom->createCDATASection( $binfo->name ) );

    $url = $home_url."/product/view/?num=".$binfo->num;
    $node = $book->appendChild( $odom->createElement("url") );
    $node->appendChild( $odom->createTextNode( $url ) );

    $node = $book->appendChild( $odom->createElement("author") );
    $node->appendChild( $odom->createCDATASection( $binfo->author ) );

    $node = $book->appendChild( $odom->createElement("publisher") );
    $node->appendChild( $odom->createCDATASection( $binfo->publisher ) );

    $book->appendChild( $odom->createElement("pubdate", $binfo->publisherDate) );

    $book->appendChild( $odom->createElement("volume", $binfo->volume) );
    $book->appendChild( $odom->createElement("lend", $binfo->nowLendCount) );
    $book->appendChild( $odom->createElement("reserved", $binfo->nowReserveCount) );

    switch ($binfo->format) {
      case 'xml':
	$book->appendChild( $odom->createElement("format", "bookcube") );
	break;
      case 'pdf':
	$book->appendChild( $odom->createElement("format", "pdf") );
	break;
    }

    $term = $binfo->xpath("//Terminal[@groupNum='smartphone']");
    if (count($term) > 0)
      $book->appendChild( $odom->createElement("support_mobile", "yes") );

  }
}

header("Content-type: text/xml");
$odom->formatOutput = true;
echo $odom->saveXML();
?>
