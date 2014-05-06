<?php
// -*- coding: utf-8 -*-
/* parse_gangnam.php
 *	parse result page from 강남구전자 library
 *
 * released under GPLv3
 */
function parse_gangnam($html)
{
  $odom = new DOMDocument("1.0", "UTF-8");
  $root = $odom->appendChild( $odom->createElement("result") );

  $doc = new DOMDocument;
  $doc->preserveWhiteSpace = false;
  $html2 = '<?xml version="1.0" encoding="utf-8"?>'.$html;
  @$doc->loadHTML($html2);
  $xpath = new DOMXpath($doc);
  $row_tmpl = "//table[@summary='도서 목록']/tr";
  $rows = $xpath->query($row_tmpl);

  $rowcnt = 1;
  $book = NULL;
  $maxrowcnt = $rows->length - 3;	// drop last 2 rows
  foreach ($rows as $row) {
    if ($rowcnt > $maxrowcnt)
      continue;
    switch ($rowcnt % 5) {
      case 1:
	$book = $root->appendChild( $odom->createElement("book") );

	//-- row 1/col 1: {url},{thumb}
	$a_node = $xpath->query($row_tmpl."[$rowcnt]//p[@class='bil_8']/a")->item(0);
	$url = $a_node->getAttribute("href");
	$node = $book->appendChild( $odom->createElement("url") );
	$node->appendChild( $odom->createTextNode( $url ) );

	$url = $a_node->getElementsByTagName("img")->item(0)->getAttribute("src");
	$node = $book->appendChild( $odom->createElement("thumb") );
	$node->appendChild( $odom->createTextNode( $url ) );

	//-- row 1/col 2: {title}
	$text = $xpath->query($row_tmpl."[$rowcnt]//p[@class='bil_9']//font")->item(0)->nodeValue;
	$node = $book->appendChild( $odom->createElement("title") );
	$node->appendChild( $odom->createCDATASection( $text ) );

	break;

      case 2:
	//-- row 2/dd[bil_11]: {author}, {publisher}, {pubdate}
	$dd_node = $xpath->query($row_tmpl."[$rowcnt]//dd[@class='bil_11']")->item(0);

	if (preg_match("@(.*) 지음@", $dd_node->childNodes->item(0)->nodeValue, $match)) {
	  $node = $book->appendChild( $odom->createElement("author") );
	  $node->appendChild( $odom->createCDATASection( trim($match[1]) ) );
	}

	$node = $book->appendChild( $odom->createElement("publisher") );
	$node->appendChild( $odom->createCDATASection( trim($dd_node->childNodes->item(2)->nodeValue) ));

	if (preg_match("@\d{4}-\d{1,2}-\d{1,2}@", $dd_node->childNodes->item(4)->nodeValue, $match)) {
	  $book->appendChild( $odom->createElement("pubdate", $match[0] ));
	}

	//-- row 2/dd[bil_12]: {format}
	break;

      case 3:
	//-- row 3: {description}
	$text = $xpath->query($row_tmpl."[$rowcnt]//p[@class='bil_13']")->item(0)->nodeValue;
	$node = $book->appendChild( $odom->createElement("description") );
	$node->appendChild( $odom->createCDATASection( $text ));
	break;

      case 4:
	// no information on mobile support
	$book->appendChild( $odom->createElement("support_mobile", "yes") );
	break;
    }
    $rowcnt++;
  }

  return $odom;
}
?>
