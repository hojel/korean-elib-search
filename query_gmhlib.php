<?php
// -*- coding: utf-8 -*-
/* query_gmhlib.php
 *	search book from 광명중앙 library
 * Input:
 *	type   : [title|author|publisher]
 *	keyword: <string>
 *	url    : library address
 */
/*
 * search URL: /code/user/book/index.php
 * Input: POST method (EUC-KR)
 *	string_fild : [cont_name|cont_author|cont_pubname]
 *	string_book : keyword
 * Output: HTML output
 *	<!--테이블시작-->
 *	...
 *	<!--테이블끝-->
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$home_url = $_GET['url'];
$alt_type = false;

$type_map = array('title'=>'cont_name',
		  'author'=>'cont_author',
		  'publisher'=>'cont_pubname');
$fields = array(
	    'string_fild' => $type_map[$srchtype],
	    'string_book' => mb_convert_encoding($keyword,"EUC-KR","UTF-8")
	    );
$url = $home_url."/code/user/book/index.php";
$data_url = http_build_query($fields,"&");
$data_len = strlen( $data_url );

$opts = array(
  'http'=>array(
    'method'=>"POST",
    'header'=>"User-Agent: Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)\r\nContent-Length: $data_len\r\nContent-Type: application/x-www-form-urlencoded\r\n",
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
  $html = mb_convert_encoding($html, "UTF-8", "EUC-KR");
  $pos1 = strpos($html, "<!--테이블시작-->");
  $pos2 = strpos($html, "<!--테이블끝-->");
  $html2 = substr($html, $pos1, $pos2-$pos1);
  $html2 = str_replace('="/', '="'.$home_url."/", $html2);
  $html2 = str_replace("='/", "='".$home_url."/", $html2);
  //echo $html2;

  include "parse_gmhlib.php";
  $dom = parse_gmhlib($html2);
}

$node = $dom->documentElement->appendChild( $dom->createElement("search_url") );
$node->appendChild( $dom->createTextNode( $url ) );

header("Content-type: text/xml");
$dom->formatOutput = true;
echo $dom->saveXML();
?>
