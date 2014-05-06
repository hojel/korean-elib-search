<?php
// -*- coding: utf-8 -*-
/* query_type3.php
 *	search book from a library based on named Type-3 platform
 * Input:
 *	type   : [title|author]
 *	keyword: <string>
 *	url    : library address
 */
/*
 * search URL: /contents/ebook/nd_search_list.asp?viewtype=list&CF=BX01
 * Input: POST method
 *	Sear_value : keyword
 * Output: HTML output
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$home_url = $_GET['url'];
$alt_type = false;

$fields1 = array(
	    'viewtype' => 'list',
	    'CF' => 'BX01'
	    );
$fields2 = array(
	    'Sear_value' => $keyword
	    );
$url = $home_url."/contents/ebook/nd_search_list.asp?".http_build_query($fields1,"&");

$data_url = http_build_query($fields2);
$data_len = strlen($data_url);

$opts = array(
  'http'=>array(
    'method'=>"POST",
    'header'=>"User-Agent: Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)\r\n".
	    "Content-Length: $data_len\r\n".
	    "Content-Type: application/x-www-form-urlencoded\r\n",
    'content'=>$data_url
  )
);
$context = stream_context_create($opts);
$html = file_get_contents($url, false, $context);
$html2 = str_replace("=\"/", "=\"".$home_url."/", $html);
//echo $html2;

include "parse_type3.php";
$dom = parse_type3($html2);

$node = $dom->documentElement->appendChild( $dom->createElement("search_url") );
$node->appendChild( $dom->createTextNode( $url ) );

header("Content-type: text/xml");
$dom->formatOutput = true;
echo $dom->saveXML();
?>
