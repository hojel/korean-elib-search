<?php
// -*- coding: utf-8 -*-
/* kolisnet.php
 *	search kolisnet & return ebook
 * Input:
 *	type   : [book|author]
 *	keyword: <string>
 *	page   : <num>
 *
 * released under GPLv3
 */
/* KOLIS-NET API
 * Query URL: http://nl.go.kr/kolisnet/openApi/open.php
 *	collection_set : 1(단행본)
 *	page           : 1
 *	search_field1  : [total_field | title | author]
 *	value1         : 검색어
 *	per_page       : 10
 *
 * Search Result: XML format
 *    METADATA::
 *      TOTAL::
 *      RECORD::
 *        NUMBER     = 
 *        TITLE      = 제목
 *        AUTHOR     = 저자
 *        PUBLISHER  = 출판사
 *        PUBYEAR    = 출판연도
 *        TYPE       = [일반도서 | 컴퓨터파일 | 아동청소년컴퓨터파일]
 *        CONTENTS   = 
 *        LIB_NAME   = 도서관명
 *        LIB_CODE   = 도서관부호
 *        REC_KEY    = 고유키
 *
 * Detail URL: http://nl.go.kr/kolisnet/openApi/open.php
 *	rec_key : 고유키
 *
 * Detail Result: XML format
 *    METADATA::
 *      BIBINFO::
 *        TITLE_INFO  = 제목 / 저자
 *        SERIES_INFO = 
 *        ISBN        = ISBN코드
 *      HOLDINFO::
 *        NUMBER     = 
 *        LOCAL      = 지역정보
 *        LIB_NAME   = 도서관명
 *        LIB_CODE   = 도서관부호
 */
$srchtype = $_GET['type'];
$keyword  = $_GET['keyword'];
$page = (int)$_GET['page'];
?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta http-equiv="pragma" content="no-cache" />
  <title>국가자료공동목록 전자책 검색</title>
  <link rel="stylesheet" type="text/css" href="search.css" />
</head>
<body>
<?php
$tblhdr = <<<EOD
  <tr class="outhdr">
    <th>제목</th>
    <th>저자</th>
    <th>출판사</th>
    <th>출판년도</th>
    <th>도서관</th>
  </tr>
EOD;

echo "<table cellspacing=\"10\">".PHP_EOL;

$root_url = "http://nl.go.kr/kolisnet/openApi/open.php";
$fields = array(
	'collection_set' => 1,
	'page' => $page,
	'search_field1' => $srchtype,
	'value1' => $keyword,
	'per_page' => 100
	);

$url = $root_url."?".http_build_query($fields,"&");
$result = simplexml_load_file($url);

if ($result->TOTAL == '0') {
  echo "<tr><td colspan=\"5\">검색된 목록이 없습니다</td></tr>".PHP_EOL;
} else {
  echo $tblhdr.PHP_EOL;
  foreach ($result->RECORD as $record) {
    if ($record->TYPE != "컴퓨터파일")
      continue;
    echo "  <tr>".PHP_EOL;
    echo "    <td class=\"title\">".$record->TITLE."</td>".PHP_EOL;
    echo "    <td>".$record->AUTHOR."</td>".PHP_EOL;
    echo "    <td>".$record->PUBLISHER."</td>".PHP_EOL;
    echo "    <td>".$record->PUBYEAR."</td>".PHP_EOL;
    echo "    <td>".$record->LIB_NAME."</td>".PHP_EOL;
    echo "  </tr>".PHP_EOL;
  }
}
echo "</table>".PHP_EOL;
?>
</body>
</html>
