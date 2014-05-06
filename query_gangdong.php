<?php
// -*- coding: utf-8 -*-
/* query_gangdong.php
 *	search book from 강동구전자 library
 * Input:
 *	type   : [title|author|publisher]
 *	keyword: <string>
 *	url    : library address
 */
/*
 * search URL: /main/list2.asp
 * Input: POST method (EUC-KR)
 *	Search_Option_1 : [도서]
 *	Search_Option_2 : [도서명|저자명|출판사명]
 *	strSearch       : keyword
 * Output: HTML output
 *	<!----본문 내용 채우기 시작 //--->
 *	...
 *	<!----본문 내용 채우기 끝 //--->
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$home_url = $_GET['url'];
$alt_type = false;

$type_map = array('title'=>"도서명",
		  'author'=>"저자명",
		  'publisher'=>"출판사명");
$fields = array(
	    'Search_Option_1' => mb_convert_encoding("도서","EUC-KR","UTF-8"),
	    'Search_Option_2' => mb_convert_encoding($type_map[$srchtype],"EUC-KR","UTF-8"),
	    'strSearch' => mb_convert_encoding($keyword,"EUC-KR","UTF-8")
	    );
$url = $home_url."/main/list2.asp";
$data_url = http_build_query($fields,"&");
$data_len = strlen( $data_url );

$opts = array(
  'http'=>array(
    'method'=>"POST",
    'header'=>"User-Agent: Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)\r\n"
	     ."Content-Length: $data_len\r\n"
	     ."Content-Type: application/x-www-form-urlencoded\r\n"
	     ."Origin: $home_url\r\n"
	     ."Referer: $home_url/main/main.asp\r\n",
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
  $pos1 = strpos($html, "<!----본문 내용 채우기 시작 //--->");
  $pos2 = strpos($html, "<!----본문 내용 채우기 끝 //--->");
  $html2 = substr($html, $pos1, $pos2-$pos1);
  $html2 = str_replace('="/', '="'.$home_url."/", $html2);
  $html2 = str_replace('="../', '="'.$home_url."/../", $html2);
  //echo $html2;

  include "parse_gangdong.php";
  $dom = parse_gangdong($html2);
}

$node = $dom->documentElement->appendChild( $dom->createElement("search_url") );
$node->appendChild( $dom->createTextNode( $url ) );

header("Content-type: text/xml");
$dom->formatOutput = true;
echo $dom->saveXML();
?>
