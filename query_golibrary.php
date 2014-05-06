<?php
// -*- coding: utf-8 -*-
/* query_golibrary.php
 *	search book from 경기사이버 library
 * Input:
 *	type   : [title|author|publisher]
 *	keyword: <string>
 *	url    : library address
 */
/*
 * search URL: /elec/ebook/ebook_list.jsp
 * Input: POST method (UTF-8)
 *	select1 : 1(제목), 2(저자), 3(출판사)
 *	search1 : keyword
 * Output: HTML output
 *	<div class="bookList first">
 *	</div>
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$home_url = $_GET['url'];
$alt_type = false;

$type_map = array('title'=>'1',
		  'author'=>'2',
		  'publisher'=>'3');
$fields = array(
	    'select1' => $type_map[$srchtype],
	    'search1' => $keyword
	    );
$url = $home_url."/elec/ebook/ebook_list.jsp";
$data_url = http_build_query($fields,"&");
$data_len = strlen( $data_url );

$opts = array(
  'http'=>array(
    'method'=>"POST",
    'header'=>"User-Agent: Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)\r\n"
	     ."Content-Length: $data_len\r\n"
	     ."Content-Type: application/x-www-form-urlencoded\r\n"
	     ."Origin: $home_url\r\n"
	     ."Referer: $home_url/elec/ebook/ebook_main.jsp\r\n",
    'content'=>$data_url
  )
);
$context = stream_context_create($opts);
$html = @file_get_contents($url, false, $context);

// error handling
if (strpos($http_response_header[0], "200") == FALSE) { 
  // error message
  $dom = new DOMDocument("1.0", "UTF-8");
  $root = $dom->appendChild( $dom->createElement("result") );
  $node = $root->appendChild( $dom->createElement("errmsg", "OPEN_FAIL") );

} else {
  // handling redirection
  if (substr($html, 0, 14) == "<html><script ") {
    preg_match("/document\.cookie\s*=\s*'(.*?)'/", $html, $match);
    $opts['http']['header'] = $opts['http']['header']."Cookie: $match[1]\r\n";
    $context = stream_context_create($opts);
    $html = file_get_contents($url, false, $context);	// reload
  }
  $html2 = str_replace('="/', '="'.$home_url."/", $html);
  //$html2 = str_replace("='/", "='".$home_url."/", $html2);
  //echo $html2;

  include "parse_golibrary.php";
  $dom = parse_golibrary($html2);
}

$node = $dom->documentElement->appendChild( $dom->createElement("search_url") );
$node->appendChild( $dom->createTextNode( $url ) );

header("Content-type: text/xml");
$dom->formatOutput = true;
echo $dom->saveXML();
?>
