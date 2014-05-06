<?php
// -*- coding: utf-8 -*-
/* parse_kyobo.php
 *	parse result page from Kyobo(standard) platform
 *
 * released under GPLv3
 */
function parse_kyobo($html)
{
  $odom = new DOMDocument("1.0", "UTF-8");
  $root = $odom->appendChild( $odom->createElement("result") );

  $icon2fmt_map = array(
      'BQ'  => "bookcube",
      'KB'  => "kyobo",
      'WR'  => "xdf+",	  // 우리전자책
      'YES' => "yes24",
      'OP'  => "opms",	  // 웅진북센=OPMS
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
  $tables = $xpath->query("/html/body/table[2]/tr/td/table");

  foreach ($tables as $table) {
    $book = $root->appendChild( $odom->createElement("book") );

    $rows = $table->childNodes->item(0)->getElementsByTagName("tr");

    //--- row 1: {thumb},{url}
    $url = $rows->item(0)->getElementsByTagName("img")->item(0)->getAttribute("src");
    $node = $book->appendChild( $odom->createElement("thumb") );
    $node->appendChild( $odom->createTextNode( $url ) );

    $url = $rows->item(0)->getElementsByTagName("a")->item(0)->getAttribute("href");
    $node = $book->appendChild( $odom->createElement("url") );
    $node->appendChild( $odom->createTextNode( $url ) );

    //--- row 2: formats(in icon)
    foreach ($rows->item(1)->getElementsByTagName("img") as $img) {
      preg_match("/(\w*)_(\w*)\.gif/", $img->getAttribute("src"), $match);
      if ($match[1] == 'icon') {
      	if (array_key_exists($match[2], $icon2fmt_map))
	  $book->appendChild( $odom->createElement("format", $icon2fmt_map[ $match[2] ]) );
      } elseif ($match[1] == 'btn') {
      	if ($match[2] == 'smt')
	  $book->appendChild( $odom->createElement("support_mobile", "yes") );
      }
    }

    //-- row 3: {title}, supported format list as icon
    $node = $book->appendChild( $odom->createElement("title") );
    $node->appendChild( $odom->createCDATASection( $rows->item(2)->nodeValue ) );

    //-- row 4: {author} / [{publisher} / {date}]
    $num = preg_match("#^\s*(.*\S)\s*/\s*\[\s*(.*\S)\s*/\s*(\d{4}\.\d{2}\.\d{2})\s*\]#", $rows->item(3)->nodeValue, $match);
    if ($num > 0) {
      $node = $book->appendChild( $odom->createElement("author") );
      $node->appendChild( $odom->createCDATASection( $match[1] ) );

      $node = $book->appendChild( $odom->createElement("publisher") );
      $node->appendChild( $odom->createCDATASection( $match[2] ) );

      $book->appendChild( $odom->createElement("pubdate", $match[3]) );
    }

    //-- row 5: {description}
    $node = $book->appendChild( $odom->createElement("description") );
    $node->appendChild( $odom->createCDATASection( $rows->item(4)->nodeValue ) );

    //-- row 6: {lend}/{volume}
    preg_match("#(\d+)\s*/\s*(\d+)#", $rows->item(5)->nodeValue, $match);
    $book->appendChild( $odom->createElement("lend", $match[1]) );
    $book->appendChild( $odom->createElement("volume", $match[2]) );

    //-- row 7: 예약: {result}
    preg_match("#(\d+)#", $rows->item(6)->nodeValue, $match);
    $book->appendChild( $odom->createElement("reserved", $match[1]) );
  }
  return $odom;
}
?>
