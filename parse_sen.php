<?php
// -*- coding: utf-8 -*-
/* parse_golibrary.php
 *	parse result page from 서울교육청 전자 library
 *
 * released under GPLv3
 */
function parse_sen($html)
{
  $odom = new DOMDocument("1.0", "UTF-8");
  $root = $odom->appendChild( $odom->createElement("result") );

  $doc = new DOMDocument;
  $doc->preserveWhiteSpace = false;
  $html2 = '<?xml version="1.0" encoding="utf-8"?>'.$html;
  @$doc->loadHTML($html2);
  $xpath = new DOMXpath($doc);
  $items = $xpath->query("//ol[@class='body list-webzine2']/li");

  foreach ($items as $item) {
    $book = $root->appendChild( $odom->createElement("book") );

    //-- 1: {thumb}
    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $item->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node->appendChild( $odom->createTextNode( $url ) );

    //-- 2: {url}, {title}
    $node = $book->appendChild( $odom->createElement("url") );
    $a_node = $item->getElementsByTagName("a")->item(0);
    $url = $a_node->getAttribute("href");
    $node->appendChild( $odom->createTextNode( $url ) );

    $node = $book->appendChild( $odom->createElement("title") );
    $img_node = $item->getElementsByTagName("img")->item(0);
    $text = $item->getElementsByTagName("img")->item(0)->getAttribute("title");
    $node->appendChild( $odom->createCDATASection( $text ) );

    //-- 3: {author}
    $dds = $item->getElementsByTagName("dd");

    $node = $book->appendChild( $odom->createElement("author") );
    $text = $dds->item(0)->nodeValue;
    $node->appendChild( $odom->createCDATASection( $text ) );

    //-- 4: {publisher}
    $node = $book->appendChild( $odom->createElement("publisher") );
    $text = $dds->item(1)->nodeValue;
    $node->appendChild( $odom->createCDATASection( $text ) );

    //-- 5: {pubdate}
    $book->appendChild( $odom->createElement("pubdate", $dds->item(2)->nodeValue) );

    //-- 6: {volume}/{lend} -> commented out
    //-- 6: {format}
    $node = $book->appendChild( $odom->createElement("description") );
    $node->appendChild( $odom->createCDATASection( $text = $dds->item(3)->nodeValue) );

    // no information on mobile support
    $book->appendChild( $odom->createElement("support_mobile", "yes") );
  }
  return $odom;
}
?>
