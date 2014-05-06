<?php
// -*- coding: utf-8 -*-
/* parse_kyobo1.php
 *	parse result page from Kyobo(variation 1) platform
 *
 * released under GPLv3
 */
function parse_kyobo1($html)
{
  $odom = new DOMDocument("1.0", "UTF-8");
  $root = $odom->appendChild( $odom->createElement("result") );

  $icon2fmt_map = array(
      'KB'  => "kyobo",
      'OP'  => "opms",	  // 웅진북센=OPMS
      'WR'  => "xdf+",	  // 우리전자책
      'YES' => "yes24",
      'BT'  => "booktopia",
      'EL'  => "els21",
      'NM'  => "nuri"	  // 누리미디어=DBpia
  );        

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
  $tables = $xpath->query("/html/body/table/tbody/..");
  foreach ($tables as $table) {
    $book = $root->appendChild( $odom->createElement("book") );

    $xpath = new DOMXpath($table->ownerDocument);
    $cols = $xpath->query($table->getNodePath()."/tbody/tr/td");

    $node = $book->appendChild( $odom->createElement("thumb") );
    $url = $cols->item(0)->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node->appendChild( $odom->createTextNode( $url ) );

    //$xpath->query("/html/body/table[$i+1]/tbody/tr[$i]/td[2]/table/tbody/tr")
    $rows2 = $cols->item(1)->childNodes->item(0)->childNodes->item(0)->childNodes;
    //=== row 1: title...
    $cols2 = $rows2->item(0)->getElementsByTagName("td");
    //-- col 1: {thumb},{url}
    $node = $book->appendChild( $odom->createElement("url") );
    $url = $cols2->item(1)->getElementsByTagName("a")->item(0)->getAttribute("href");
    $node->appendChild( $odom->createTextNode($url) );

    //-- col 1: {url},supported formats
    $node = $book->appendChild( $odom->createElement("title") );
    $node->appendChild( $odom->createCDATASection( $cols2->item(1)->nodeValue ) );
    // supported format from icon
    foreach ($cols2->item(1)->getElementsByTagName("img") as $img) {
      preg_match("/(\w*)_(\w*)\.gif/", $img->getAttribute("src"), $match);
      if ($match[1] == 'icon') {
      	if (array_key_exists($match[2], $icon2fmt_map))
	  $book->appendChild( $odom->createElement("format", $icon2fmt_map[ $match[2] ]) );
      } elseif ($match[1] == 'btn') {
      	if ($match[2] == 'smt')
	  $book->appendChild( $odom->createElement("support_mobile", "yes") );
      }
    }

    // col 3: {lend} / {volume}
    preg_match("#(\d+)\s*/\s*(\d+)#", $cols2->item(2)->nodeValue, $match);
    $book->appendChild( $odom->createElement("lend", $match[1]) );
    $book->appendChild( $odom->createElement("volume", $match[2]) );
    // col 4: {reserve}명
    preg_match("/(\d+)\s*명/", $cols2->item(3)->nodeValue, $match);
    $book->appendChild( $odom->createElement("reserved", $match[1]) );

    //=== 2nd row: {author} / [{publisher} / {date}]
    $num = preg_match("#^\s*(.*\S)\s*/\s*\[\s*([^/]*\S)\s*(/\s*(\d{4}\.\d{2}\.\d{2})|)\s*\]#", $rows2->item(1)->nodeValue, $match);
    if ($num > 0) {
      $node = $book->appendChild( $odom->createElement("author") );
      $node->appendChild( $odom->createCDATASection( $match[1] ) );

      $node = $book->appendChild( $odom->createElement("publisher") );
      $node->appendChild( $odom->createCDATASection( $match[2] ) );

      if (strlen($match[3]) > 0)
	$book->appendChild( $odom->createElement("pubdate", $match[4]) );
    }

    $book->appendChild( $odom->createElement("support_mobile", "yes") );
  }
  return $odom;
}
?>
