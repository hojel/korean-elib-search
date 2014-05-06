<?php
// -*- coding: utf-8 -*-
/* parse_gmhlib.php
 *	parse result page from 광명중앙 library
 *
 * released under GPLv3
 */
function parse_gmhlib($html)
{
  $fmttext2fmt_map = array(
      "교보"   => "kyobo",
      "OPMS"    => "opms", 	  // 웅진북센=OPMS
      "북토피아 / 바로북" => "booktopia"
  );        

  $odom = new DOMDocument("1.0", "UTF-8");
  $root = $odom->appendChild( $odom->createElement("result") );

// parsing
  $html2 = <<<EOD
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
$html
</body>
</html>
EOD;
  $doc = new DOMDocument;
  $doc->preserveWhiteSpace = false;
  @$doc->loadHTML($html2);
  $xpath = new DOMXpath($doc);
  //$rows = $xpath->query("/html/body/table/tr[@bgcolor='#FFFFFF']");
  $rows = $xpath->query("//tr[@bgcolor='#FFFFFF']");

  foreach ($rows as $row) {
    $book = $root->appendChild( $odom->createElement("book") );

    //$cols = $row->childNodes;
    $cols = $row->getElementsByTagName("td");

    //-- column 1: {thumb}
    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $cols->item(0)->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node->appendChild( $odom->createTextNode( $url ) );

    //-- column 2: {url}, {title}
    $node = $book->appendChild( $odom->createElement("url") );
    $a_node = $cols->item(1)->getElementsByTagName("a")->item(0);
    $url = preg_replace("/.*'(\d+)','(\d+)'.*/", "http://ebook.gmhlib.or.kr/code/user/book/detail.php?cont_no=$1&code=$2", $a_node->getAttribute("href"));
    $node->appendChild( $odom->createTextNode( $url ) );

    $node = $book->appendChild( $odom->createElement("title") );
    $node->appendChild( $odom->createCDATASection( $a_node->nodeValue ) );

    //-- column 3: {author}
    $node = $book->appendChild( $odom->createElement("author") );
    $text = $cols->item(2)->getElementsByTagName("div")->item(0)->nodeValue;
    $node->appendChild( $odom->createCDATASection( $text ) );

    //-- column 4: {publisher}
    $node = $book->appendChild( $odom->createElement("publisher") );
    $text = $cols->item(3)->getElementsByTagName("div")->item(0)->nodeValue;
    $node->appendChild( $odom->createCDATASection( $text ) );

    //-- column 6: {format}
    $node = $book->appendChild( $odom->createElement("format") );
    $text = $cols->item(5)->getElementsByTagName("div")->item(0)->nodeValue;
    if (array_key_exists($text, $fmttext2fmt_map))
      $text = $fmttext2fmt_map[ $text ];
    $node->appendChild( $odom->createCDATASection( $text ) );

    // no information on mobile support
    $book->appendChild( $odom->createElement("support_mobile", "yes") );
  }
  return $odom;
}
?>
