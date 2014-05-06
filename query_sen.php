<?php
// -*- coding: utf-8 -*-
/* query_woori.php
 *	search book from a library based on Woori eBook platform
 * Input:
 *	type   : [title|author]
 *	keyword: <string>
 *	url    : library address
 */
/*
 * search URL: /12_search/search_result.php
 * Input: GET method (EUC-KR)
 *	ct : [Cont_name|Cont_author]
 *	keyword[] : keyword
 * Output: HTML output
 *	<!--콘텐츠이름검색 결과 화면-->
 *	...
 *	<!-- content_body : End //-->
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$home_url = $_GET['url'];
$alt_type = false;

$type_map = array('title'=>'Cont_name',
		  'author'=>'Cont_author');
$fields = array(
	    'ct' => $type_map[$srchtype],
	    'keyword[]' => mb_convert_encoding($keyword,"EUC-KR","UTF-8")
	    );
$url = $home_url."/12_search/search_result.php?".http_build_query($fields,"&");

$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"User-Agent: Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)\r\n"
  )
);
$context = stream_context_create($opts);
$html = file_get_contents($url, false, $context);
$html = mb_convert_encoding($html, "UTF-8", "EUC-KR");

$pos1 = strpos($html, "<!--콘텐츠이름검색 결과 화면-->");
$pos2 = strpos($html, "<!-- content_body : End //-->");
$html2 = substr($html, $pos1, $pos2-$pos1);
$html2 = str_replace('="/', '="'.$home_url.'/', $html2);

include "parse_sen.php";
$dom = parse_sen($html2);

$node = $dom->documentElement->appendChild( $dom->createElement("search_url") );
$node->appendChild( $dom->createTextNode( $url ) );

header("Content-type: text/xml");
$dom->formatOutput = true;
echo $dom->saveXML();
?>
