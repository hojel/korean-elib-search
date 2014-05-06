<?php
// -*- coding: utf-8 -*-
/* parse_woori.php
 *	parse result page from Woori eBook platform
 *
 * released under GPLv3
 */
function parse_woori($html)
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
  $doc->preserveWhiteSpace = false;
  @$doc->loadHTML($html2);
  $xpath = new DOMXpath($doc);
  $tables = $xpath->query("/html/body/table/tr[2]/td/table");

  foreach ($tables as $table) {
    $book = $root->appendChild( $odom->createElement("book") );

    $rows = $table->childNodes;

    //-- row 1: {thumb},{url},{title}
    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $rows->item(1)->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node->appendChild( $odom->createTextNode( $url ) );

    $node = $book->appendChild( $odom->createElement("url") );
    $url = $rows->item(1)->getElementsByTagName("a")->item(0)->getAttribute("href");
    $node->appendChild( $odom->createTextNode( $url ) );

    //-- row 2: {title}
    $node = $book->appendChild( $odom->createElement("title") );
    $node->appendChild( $odom->createCDATASection( trim($rows->item(1)->nodeValue) ) );

    //-- row 3: <author> 지음 | <publisher>| <date>, <formats>
    $num = preg_match("/^\s*(.*\S) 지음.*\|\s*(.*\S)\s*\|\s*(\d{4}-\d{2}-\d{2})/s", $rows->item(2)->nodeValue, $match);
    if ($num > 0) {
      $node = $book->appendChild( $odom->createElement("author") );
      $node->appendChild( $odom->createCDATASection( $match[1] ) );

      $node = $book->appendChild( $odom->createElement("publisher") );
      $node->appendChild( $odom->createCDATASection( $match[2] ) );

      $book->appendChild( $odom->createElement("pubdate", $match[3]) );
    }

    // supported device from icon
    foreach ($rows->item(2)->getElementsByTagName("img") as $img) {
      $cnt = preg_match("/(\w*)\.gif/", $img->getAttribute("src"), $match);
      if ($cnt > 0 && $match[1] == 'smart') {
	$book->appendChild( $odom->createElement("support_mobile", "yes") );
      }
    }
    //-- row 4: {description}
    $node = $book->appendChild( $odom->createElement("description") );
    $node->appendChild( $odom->createCDATASection( trim($rows->item(3)->nodeValue) ) );

    // no info about book format in search result page
  }
  return $odom;
}
?>
