<?php
// -*- coding: utf-8 -*-
/* parse_golibrary.php
 *	parse result page from 경기도 cyber library
 *
 * released under GPLv3
 */
function parse_golibrary($html)
{
  $odom = new DOMDocument("1.0", "UTF-8");
  $root = $odom->appendChild( $odom->createElement("result") );

// parsing
  $doc = new DOMDocument;
  $doc->preserveWhiteSpace = false;
  @$doc->loadHTML($html);
  $xpath = new DOMXpath($doc);
  $items = $xpath->query("//div[@class='bookList first']");

  foreach ($items as $item) {
    $book = $root->appendChild( $odom->createElement("book") );

    //-- 1: {thumb}
    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $item->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node->appendChild( $odom->createTextNode( $url ) );

    //-- 2: {url}, {title}
    $dds = $item->getElementsByTagName("dd");

    $node = $book->appendChild( $odom->createElement("url") );
    $a_node = $dds->item(0)->getElementsByTagName("a")->item(0);
    $url = $a_node->getAttribute("href");
    $node->appendChild( $odom->createTextNode( $url ) );

    $node = $book->appendChild( $odom->createElement("title") );
    $node->appendChild( $odom->createCDATASection( $a_node->nodeValue ) );

    //-- 3: {author}
    $node = $book->appendChild( $odom->createElement("author") );
    $text = $dds->item(1)->getElementsByTagName("a")->item(0)->nodeValue;
    $node->appendChild( $odom->createCDATASection( $text ) );

    //-- 4: {publisher}
    $node = $book->appendChild( $odom->createElement("publisher") );
    $text = $dds->item(2)->getElementsByTagName("a")->item(0)->nodeValue;
    $node->appendChild( $odom->createCDATASection( $text ) );

    //-- 5: {pubdate}
    $book->appendChild( $odom->createElement("pubdate", $dds->item(3)->nodeValue) );

    //-- 6: {volume}/{lend} -> commented out
    //-- 6: {format}
    $node = $book->appendChild( $odom->createElement("description") );
    $node->appendChild( $odom->createCDATASection( $text = $dds->item(4)->nodeValue) );

    // no information on mobile support
    $book->appendChild( $odom->createElement("support_mobile", "yes") );
  }
  return $odom;
}
?>
