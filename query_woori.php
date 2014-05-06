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
 * search URL: /main/list.asp
 * Input: GET method (EUC-KR)
 *	SearchOption : [도서명|저자명|출판사명]
 *	strSearch    : keyword
 * Output: HTML output
 *	<!-- 리스트 시작-->
 *	...
 *	<!-- 리스트 끝-->
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$home_url = $_GET['url'];
$alt_type = false;

$type_map = array('title'=>'도서명',
		  'author'=>'저자명');
$fields = array(
	    'SearchOption' => mb_convert_encoding($type_map[$srchtype],"EUC-KR","UTF-8"),
	    'strSearch' => mb_convert_encoding($keyword,"EUC-KR","UTF-8")
	    );
$url = $home_url."/main/list.asp?".http_build_query($fields,"&");

$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"User-Agent: Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)\r\n"
  )
);
$context = stream_context_create($opts);
$html = file_get_contents($url, false, $context);
$html = mb_convert_encoding($html, "UTF-8", "EUC-KR");
$pos1 = strpos($html, "<!-- 리스트 시작-->");
$pos2 = strpos($html, "<!-- 리스트 종료-->");
$html2 = substr($html, $pos1, $pos2-$pos1);
$html2 = str_replace("='/", "='".$home_url."/", $html2);
$html2 = str_replace("=\"../", "=\"".$home_url."/", $html2);
//echo $html2;

include "parse_woori.php";
$dom = parse_woori($html2);

$node = $dom->documentElement->appendChild( $dom->createElement("search_url") );
$node->appendChild( $dom->createTextNode( $url ) );

header("Content-type: text/xml");
$dom->formatOutput = true;
echo $dom->saveXML();
?>
