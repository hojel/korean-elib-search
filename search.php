<?php
// -*- coding: utf-8 -*-
/* search.php
 *	search books over libraries and display in multi-page table
 * Input:
 *	type   : [title|author]
 *	keyword: <string>
 *	page   : <num>
 *	xml    : ?<xml_file>
 *	exclude_[bookcube | kyobo | epyrus | opms | yes24 | booktopia | els21 | nuri] : [on]
 *	filter_mobile : [on]
 *
 * released under GPLv3
 */
/* Input library XML format
 *    library_list::
 *      library::
 *        name     = library name
 *        url      = library address
 *        format   = supported book format
 *        platform = library system platform
 */
/* Search result XML format
 *    result::
 *      book::
 *        title      = book title
 *        author     = 
 *        publisher  = 
 *        pubdate    = publishing date
 *        thumb      = library address
 *        url        = book url
 *        description = 
 *        volume     = 
 *        lend       = 
 *        reserved   = 
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$page = (int)$_GET['page'];
$perpage = 4;	    // libraries per page
$xml_file = 'koelib.xml';
$exclfmt = array();

$format_list = array(
  "bookcube" => "bookcube",
  "kyobo" => "kyobo",
  "epyrus" => "xdf+",
  "opms" => "opms",
  "yes24" => "yes24",
  "booktopia" => "booktopia",
  "els21" => "els21",
  "nuri" => "nuri"
);

$query_platform = array(
  'bookcube' => 'query_bookcube.php',
  'kyobo'    => 'query_kyobo.php',
  'kyobo1'   => 'query_kyobo.php',
  'kyobo2'   => 'query_kyobo.php',
  'woori'    => 'query_woori.php',
  //'woori_a'  => 'query_woori_a.php',
  'type3'    => 'query_type3.php',
  'golibrary' => 'query_golibrary.php',
  'sen'      => 'query_sen.php',
  'gmhlib'   => 'query_gmhlib.php',
  'gangnam'  => 'query_gangnam.php',
  'gangdong' => 'query_gangdong.php'
);

$iconmap = array(
  'bookcube'  => "icon_BC.gif",
  'kyobo'     => "icon_KB.gif",
  'xdf+'      => "icon_WR.gif",
  'opms'      => "icon_OP.gif",
  'yes24'     => "icon_YES.gif",
  'booktopia' => "icon_BT.gif",
  'els21'     => "icon_EL.gif",
  'nuri'      => "icon_NM.gif",
  'pdf'       => "icon_pdf.png"
);

$err_msgs = array(
  "OPEN_FAIL" => "연결에 실패하였습니다"
);

if (array_key_exists('xml', $_GET)) {
  $xml_file = $_GET['xml'];
}

foreach (array_keys($format_list) as $fmt) {
  $key = "exclude_".$fmt;
  if (array_key_exists($key, $_GET))
    $exclfmt[] = $format_list[$fmt];
}

$filter_mobile = array_key_exists('filter_mobile', $_GET);

$debug = array_key_exists('debug', $_GET);
?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta http-equiv="pragma" content="no-cache" />
  <title>전자도서관 통합검색</title>
  <link rel="stylesheet" type="text/css" href="search.css" />
</head>
<body>
<?php
$fields = array(
	'type' => $srchtype,
	'keyword' => $keyword,
	'url' => ''
	);

function curPageURL() {
  $pageURL = 'http';
  //if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
  $pageURL .= "://";
  if ($_SERVER["SERVER_PORT"] != "80") {
    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
  } else {
    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
  }
  return $pageURL;
}

$str = curPageURL();
$curPage = substr($str, 0, strrpos($str,"/")+1);

// lookup each library
$xml = simplexml_load_file($xml_file);
if ($xml == FALSE) {
  exit('Failed to open '.$xml_file);
}
$items = $xml->xpath('/library_list/library');
$numlib = count($items);
$total_page = (int)ceil($numlib/$perpage);

$stidx = ($page-1)*$perpage;
$enidx = $page*$perpage-1;
$enidx2 = ($page >= $total_page) ? ($numlib-1) : $enidx;

$tblhdr = <<<EOD
<tr class="outhdr">
  <th></th>
  <th>제목</th>
  <th>저자</th>
  <th>출판사</th>
  <th>출판일</th>
  <th>지원포맷</th>
  <th>보유</th>
  <th>대출중</th>
  <th>예약중</th>
</tr>
EOD;

echo "<table cellspacing=\"10\">".PHP_EOL;
foreach (range($stidx,$enidx2) as $idx) {
  $info = $items[$idx];
  $fields['url'] = "$info->url";

  // check library provides ebook format to search
  //$valid_fmt = array_diff($info->format, $exclfmt);
  $fmt_list = array();
  foreach ($info->format as $fmt)
    $fmt_list[] = $fmt;
  $valid_fmt = array_diff($fmt_list, $exclfmt);

  if (count($valid_fmt) == 0) {
    echo "<tr><td class=\"library\" colspan=\"9\"><a href=\"".$info->url."\" target=\"_blank\">".$info->name."</a></td></tr>",PHP_EOL;
    echo "<tr><td colspan=\"9\">검색하려는 전자책형식을 지원하지 않습니다</td></tr>".PHP_EOL;
    continue;
  }

  // check if known platform
  $platform = "$info->platform";
  if (array_key_exists($platform,$query_platform)) {
    $srchcmd = $curPage.$query_platform[$platform];
    $url = $srchcmd."?".http_build_query($fields,"&");
    $result = simplexml_load_file($url);

    // print library name
    if ($debug) {
	echo "<tr><td class=\"library\" colspan=\"9\"><a href=\"".$url."\" target=\"_blank\">".$info->name."</a></td></tr>",PHP_EOL;
    } else {
	echo "<tr><td class=\"library\" colspan=\"9\"><a href=\"".$result->search_url[0]."\" target=\"_blank\">".$info->name."</a></td></tr>",PHP_EOL;
    }

    // print result
    $cnt = 0;
    foreach ($result->book as $book) {
      // check mobile device
      if ($filter_mobile && $book->support_mobile != "yes")
	continue;

      // check format
      if (in_array($book->format, $exclfmt))
	  continue;
  
      if ($cnt == 0) {
	echo $tblhdr.PHP_EOL;	// table header
      }

      $cnt++;
      echo "  <tr>".PHP_EOL;
      echo "    <td><img class=\"thumbnail\" src=\"".$book->thumb."\" /></td>".PHP_EOL;
      echo "    <td class=\"title\"><a href=\"".$book->url."\" target=\"_blank\">".$book->title."</a></td>".PHP_EOL;
      echo "    <td>".$book->author."</td>".PHP_EOL;
      echo "    <td>".$book->publisher."</td>".PHP_EOL;
      echo "    <td>".$book->pubdate."</td>".PHP_EOL;
      if (count($book->format) > 0 && array_key_exists("$book->format",$iconmap))
        echo "    <td><img src=\"images/".$iconmap["$book->format"]."\"/></td>".PHP_EOL;
      else
        echo "    <td>".$book->format."</td>".PHP_EOL;
      echo "    <td>".$book->volume."</td>".PHP_EOL;
      echo "    <td>".$book->lend."</td>".PHP_EOL;
      echo "    <td>".$book->reserved."</td>".PHP_EOL;
      echo "  </tr>".PHP_EOL;
    }
    if ($cnt == 0) {
      if ($result->errmsg) {
	$str = "$result->errmsg";
	if (array_key_exists($str,$err_msgs)) {
          echo "<tr><td colspan=\"9\">".$err_msgs[$str]."</td></tr>".PHP_EOL;
        } else {
          echo "<tr><td colspan=\"9\">".$result->errmsg."</td></tr>".PHP_EOL;
        }
      } else {
        echo "<tr><td colspan=\"9\">검색된 목록이 없습니다</td></tr>".PHP_EOL;
      }
    }
  } else {
    echo "<tr><td class=\"library\" colspan=\"9\"><a href=\"".$info->url."\" target=\"_blank\">".$info->name."</a></td></tr>",PHP_EOL;
    if ($platform == "disabled")
      echo "<tr><td class=\"warning\" colspan=\"9\">임시로 검색에서 제외된 도서관입니다</td></tr>".PHP_EOL;
    else
      echo "<tr><td class=\"warning\" colspan=\"9\">지원되는 도서관이 아닙니다</td></tr>".PHP_EOL;
  }
}
echo "</table>".PHP_EOL;
?>
<hr />
<?php
//--- navigation
// home
echo '<a href="index.html"><img width="32" src="images/icon_home.jpg"/></a>'.PHP_EOL;
// previous / next page
$fields = $_GET;
if ($page > 1) {
  $fields['page'] = ($page-1);
  $url = "search.php?".http_build_query($fields,"&");
  echo '<a href="'.$url.'"><img width="32" src="images/left_arrow.png"/></a>'.PHP_EOL;
}
//echo "$page / $total_page".PHP_EOL;
if ($page < $total_page) {
  $fields['page'] = ($page+1);
  $url = "search.php?".http_build_query($fields,"&");
  echo '<a href="'.$url.'"><img width="32" src="images/right_arrow.png"/></a>'.PHP_EOL;
}
// goto page
echo '<form name="input" action="search.php" method="get">'.PHP_EOL;
echo '<select name="page">'.PHP_EOL;
for ($i=1; $i <= $total_page; $i++) {
  if ($i == $page)
    echo '<option value="'.$i.'" selected="selected">'.$i.'</option>'.PHP_EOL;
  else
    echo '<option value="'.$i.'">'.$i.'</option>'.PHP_EOL;
}
echo "</select>".PHP_EOL;
foreach ($fields as $key => $value) {
  if ($key != "page")
    echo '<input type="hidden" name="'.$key.'" value="'.$value.'" />'.PHP_EOL;
}
echo '<input type="submit" value="페이지로 가기" />'.PHP_EOL;
echo '</form>'.PHP_EOL;
?>
</body>
</html>
