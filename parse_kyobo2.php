<?php
// -*- coding: utf-8 -*-
/* parse_kyobo2.php
 *	parse result page from Kyobo(variation 2) platform
 *
 * released under GPLv3
 */
function parse_kyobo2($html)
{
  $odom = new DOMDocument("1.0", "UTF-8");
  $root = $odom->appendChild( $odom->createElement("result") );

  // parsing
  $html = <<<EOD
<table>
<tr>
$html
</table>
EOD;
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
  @$doc->loadHTML($html2);
  $xpath = new DOMXpath($doc);
  $rows = $xpath->query("/html/body/table/tr/td/table/../..");
  foreach ($rows as $row) {
    $book = $root->appendChild( $odom->createElement("book") );

    $xpath = new DOMXpath($row->ownerDocument);
    $cols = $xpath->query($row->getNodePath()."/td/table/tr/td");

    //-- col 1: {thumb}
    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $cols->item(0)->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node->appendChild( $odom->createTextNode( $url ) );

    //-- col 2: {url},{title}...
    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $cols->item(1)->getElementsByTagName("a")->item(0)->getAttribute("href");
    $node->appendChild( $odom->createTextNode( $url ) );

    // {title}<br>{author} / [{publisher}/{date}]<br><br>{description}
    $node = $book->appendChild( $odom->createElement("title") );
    $str = $cols->item(1)->getElementsByTagName("a")->item(0)->nodeValue;
    $node->appendChild( $odom->createCDATASection( $str );

    $cols->item(1)->normalize();
    $items = explode("<br></br>", $cols->item(1)->C14N());
    $num = preg_match("#^\s*(.*\S)\s*/\s*\[\s*(.*\S)\s*/\s*(\d{4}\.\d{2}\.\d{2})\s*\]#", strip_tags($items[3]), $match);
    if ($num > 0) {
      $node = $book->appendChild( $odom->createElement("author") );
      $node->appendChild( $odom->createCDATASection( $match[1] ) );

      $node = $book->appendChild( $odom->createElement("publisher") );
      $node->appendChild( $odom->createCDATASection( $match[2] ) );

      $book->appendChild( $odom->createElement("pubdate", $match[3]) );
    }

    $node = $book->appendChild( $odom->createElement("description") );
    $node->appendChild( $odom->createCDATASection( strip_tags($items[5]) ) );

    //-- col 3: 대출 : {lend}/{volume}<br>예약 : {reserved}
    $cols->item(2)->normalize();
    $items = explode("<br></br>", $cols->item(2)->C14N());

    preg_match("#(\d+)\s*/\s*(\d+)#", strip_tags($items[0]), $match);
    $book->appendChild( $odom->createElement("lend", $match[1]) );
    $book->appendChild( $odom->createElement("volume", $match[2]) );

    preg_match("#(\d+)#", strip_tags($items[1]), $match);
    $book->appendChild( $odom->createElement("reserved", $match[1]) );

    $book->appendChild( $odom->createElement("support_mobile", "yes") );

    // no info about book format in search result page
  }
?>
