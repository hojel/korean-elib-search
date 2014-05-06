<?php
// -*- coding: utf-8 -*-
/* query_kyobo.php
 *	search book from a library based on Kyobo(?) platform
 * Input:
 *	type   : [title|author]
 *	keyword: <string>
 *	url    : library address
 */
/*
 * search URL: /Content/search_list.asp
 * Input: GET method
 *	S_key     : 001 ?
 *	s_value   : keyword
 *	order_key : PRODUCT_NM_KR(title), TEXT_AUTHOR_NM(author)
 * Output: HTML output
 *   [type1: kyobo]
 *	<!-- 검색 리스트 시작 -->
 *	...
 *	<!-- 검색 리스트 끝 -->
 *   [type2: kyobo1]
 *	<!-- 리스트 시작 -->
 *	...
 *	<!-- 리스트 끝 -->
 *	<div>
 *   [type3: kyobo2]
 *	<!-- 리스트 시작 -->
 *	...
 *	<!-- 리스트 끝 -->
 *	<div>
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$home_url = $_GET['url'];
$alt_type = false;

$type_map = array('title'=>'PRODUCT_NM_KR',
		  'author'=>'TEXT_AUTHOR_NM');
$fields = array(
	    'S_key' => '001',
	    'order_key' => $type_map[$srchtype],
	    's_value' => mb_convert_encoding($keyword,"EUC-KR","UTF-8")
	    );
$url = $home_url."/Content/search_list.asp?".http_build_query($fields,"&");
//echo $url.PHP_EOL;

$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"User-Agent: Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)\r\n"
  )
);
$context = stream_context_create($opts);
$html = @file_get_contents($url, false, $context);
if (strpos($http_response_header[0], "200") == FALSE) { 
  // error message
  $dom = new DOMDocument("1.0", "UTF-8");
  $root = $dom->appendChild( $dom->createElement("result") );
  $node = $root->appendChild( $dom->createElement("errmsg", "OPEN_FAIL") );

} else {
  // normal processing
  $html = mb_convert_encoding($html, "UTF-8", "EUC-KR");

  $sep1 = "<!-- 검색 리스트 시작 -->";
  $sep2 = "<!-- 검색 리스트 끝 -->";
  $pos1 = strpos($html, $sep1);
  $pos2 = strpos($html, $sep2);
  if ($pos1 > 0 && $pos2 > 0) {
    $html2 = substr($html, $pos1+strlen($sep1), $pos2-$pos1);
    $sitetype = "kyobo";
  } else {
    $sep1 = "<!-- 리스트 시작 -->";
    $sep2 = "<!-- 리스트 끝 -->";
    $pos1 = strpos($html, $sep1);
    $pos2 = strpos($html, $sep2);
    if ($pos1 > 0 && $pos2 > 0) {
      $html2 = substr($html, $pos1+strlen($sep1), $pos2-$pos1);
      $sitetype = "kyobo1";
    } else {
      $sitetype = "notfound";
    }
  }
  $html2 = str_replace("=\"/", "=\"".$home_url."/", $html2);
  //echo $html2;

  switch($sitetype) {
    case "kyobo" :
      include "parse_kyobo.php";
      $dom = parse_kyobo($html2);
      break;
    case "kyobo1":
      include "parse_kyobo1.php";
      $dom = parse_kyobo1($html2);
      break;
    case "kyobo2":
      include "parse_kyobo2.php";
      $dom = parse_kyobo2($html2);
      break;
    default:
      echo "<p class=\"errmsg\">알수없는 구조</p>".PHP_EOL;
      exit(2);
  }
}

$node = $dom->documentElement->appendChild( $dom->createElement("search_url") );
$node->appendChild( $dom->createTextNode( $url ) );

header("Content-type: text/xml");
$dom->formatOutput = true;
echo $dom->saveXML();
?>
