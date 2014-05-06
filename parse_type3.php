<?php
// -*- coding: utf-8 -*-
/* parse_type3.php
 *	parse result page from named Type-3 platform
 *
 * released under GPLv3
 */
/*
 *	<ul class="product_list">
 *	  <li><div class="book_con">
 *	    <h4>{title}
 *	    <p class="txt">{author} 저/ {publisher} / {date}
 *	    <div class="etc">{volume} 권
 *	    <p class="txt_body">{description}
 */
function parse_type3($html)
{
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
  @$doc->loadHTML($html2);
  $xpath = new DOMXpath($doc);
  $secs = $xpath->query("//ul[@class='product_list']/li");

  foreach ($secs as $sec) {
    //=== (1): <span>
    $part = $xpath->query($sec->getNodePath()."/span[@class='tit_img']/a");
    if ($part->length == 0) {
      // maybe no result
      continue;
    }

    $book = $root->appendChild( $odom->createElement("book") );

    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $part->item(0)->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node->appendChild( $odom->createTextNode( $url ) );

    $node = $book->appendChild( $odom->createElement("url") );
    $url = $part->item(0)->getAttribute("href");
    $node->appendChild( $odom->createTextNode( $url ) );

    //=== (2): <div>
    $secPath = $sec->getNodePath()."/div[@class='book_con']";
    //-- {title}
    $part = $xpath->query($secPath."/h4");
    $node = $book->appendChild( $odom->createElement("title") );
    $node->appendChild( $odom->createCDATASection( trim($part->item(0)->nodeValue) ) );

    //-- {author} 저/ {publisher} / {pubdate}
    $part = $xpath->query($secPath."/p[@class='txt']");
    $num = preg_match("#^\s*(.*\S) 저\s*/\s*(.*\S)\s*/\s*((\d{4})\w (\d{2})\w (\d{2})\w)#u", $part->item(0)->nodeValue, $match);
    if ($num > 0) {
      $node = $book->appendChild( $odom->createElement("author") );
      $node->appendChild( $odom->createCDATASection( $match[1] ) );

      $node = $book->appendChild( $odom->createElement("publisher") );
      $node->appendChild( $odom->createCDATASection( $match[2] ) );

      $book->appendChild( $odom->createElement("pubdate", "$match[4]-$match[5]-$match[6]") );
    }

    //-- {description}
    $part = $xpath->query($secPath."/p[@class='txt_body']");
    $node = $book->appendChild( $odom->createElement("description") );
    $node->appendChild( $odom->createCDATASection( trim($part->item(0)->nodeValue) ) );

    //-- {volume}권
    $part = $xpath->query($secPath."/div[@class='etc']");
    $num = preg_match("/(\d+)\s*권\s/", $part->item(0)->nodeValue, $match);
    if ($num > 0)
      $book->appendChild( $odom->createElement("volume", $match[1]) );

    $book->appendChild( $odom->createElement("support_mobile", "yes") );

    // no info about book format in search result page
  }
  return $odom;
}
?>
