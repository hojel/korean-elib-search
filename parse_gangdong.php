<?php
// -*- coding: utf-8 -*-
/* parse_gangdong.php
 *	parse result page from 강동구전자 library
 *
 * released under GPLv3
 */
function parse_gangdong($html)
{
  $odom = new DOMDocument("1.0", "UTF-8");
  $root = $odom->appendChild( $odom->createElement("result") );

  $doc = new DOMDocument;
  $doc->preserveWhiteSpace = false;
  $html2 = '<?xml version="1.0" encoding="utf-8"?>'.$html;
  @$doc->loadHTML($html2);
  $xpath = new DOMXpath($doc);
  $rows = $xpath->query("//td[@height='119']/table");

  foreach ($rows as $row) {
    $book = $root->appendChild( $odom->createElement("book") );

    $cols = $row->getElementsByTagName("td");

    //-- column 2: {thumb}
    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $cols->item(1)->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node->appendChild( $odom->createTextNode( $url ) );

    //-- column 3: {url}, {title}, {format}
    $node = $book->appendChild( $odom->createElement("url") );
    $a_node = $cols->item(2)->getElementsByTagName("a")->item(0);
    $url = $a_node->getAttribute("href");
    $node->appendChild( $odom->createTextNode( $url ) );

    $node = $book->appendChild( $odom->createElement("title") );
    $text = $a_node->getElementsByTagName("b")->item(0)->nodeValue;
    $node->appendChild( $odom->createCDATASection( $text ) );

    foreach ($a_node->getElementsByTagName("img") as $img) {
      preg_match("/icon_(\w*)\.gif/", $img->getAttribute("src"), $match);
			$book->appendChild( $odom->createElement("format", $match[1] ) );
    }

    //-- row 9: 예약: {result}
    preg_match("#(\d+)#", $cols->item(8)->getElementsByTagName('font')->item(0)->nodeValue, $match);
    $book->appendChild( $odom->createElement("reserved", $match[1]) );

    //-- column 12: {author},{publisher},{pubdate}
    $text = $cols->item(11)->getElementsByTagName("b")->item(0)->nodeValue;
    if (preg_match("@(.*) 지음\s*/(.*)/(.*)@", $text, $match)) {
      $node = $book->appendChild( $odom->createElement("author") );
      $node->appendChild( $odom->createCDATASection( trim($match[1]) ) );

      $node = $book->appendChild( $odom->createElement("publisher") );
      $node->appendChild( $odom->createCDATASection( trim($match[2]) ) );

      $book->appendChild( $odom->createElement("pubdate", trim($match[3]) ));
    }

    // no information on mobile support
    $book->appendChild( $odom->createElement("support_mobile", "yes") );
  }
  return $odom;
}
?>
