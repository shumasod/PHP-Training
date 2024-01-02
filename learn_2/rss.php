<div id="rss">
<?php

//表示記事数
$hyojiNum = 30;

//フィード登録;
$data['feedurl'][] = 'http://feeds.japan.cnet.com/rss/cnet/all.rdf';
$data['feedurl'][] = 'http://www.vector.co.jp/rss/softnews.xml';
$data['feedurl'][] = 'https://srad.jp/slashdot.rdf';
$data['feedurl'][] = 'https://rss.itmedia.co.jp/rss/1.0/ait.xml';
$data['feedurl'][] = 'https://k-tai.watch.impress.co.jp/data/rss/1.0/ktw/feed.rdf';

$rssList = $data['feedurl'];

//同時呼び出し
$rssdataRaw = multiRequest($rssList);
for($n=0;$n<count($rssdataRaw);$n++)
{
    //URL設定
    $rssdata = simplexml_load_string($rssdataRaw[$n], 'SimpleXMLElement', LIBXML_NOCDATA);
    if($rssdata->channel->item) $rssdata = $rssdata->channel;
    if($rssdata->item)
    {
        $b_title=$rssdata->title;
        foreach($rssdata->item as $myEntry)
        {
            $rssDate = $myEntry->pubDate;
            if(!$rssDate) $rssDate = $myEntry->children("http://purl.org/dc/elements/1.1/")->date;
            date_default_timezone_set('Asia/Tokyo');
            $myDateGNU = strtotime($rssDate);
            $myTitle = mb_strimwidth($myEntry->title, 0,140, "…", "utf-8"); //タイトル取得
            $myLink = $myEntry->link; //リンクURL取得
            $outdata[$myDateGNU] ='<p>■<a href="' . $myLink . '" target="_blank">' . $myTitle .'</a>';

        }
    }
}

//ソート
krsort($outdata);

$nn = 0;
$html = '';

foreach($outdata as $outdata)
{
    $nn++;
    $html.= $outdata;
    if($nn == $hyojiNum) break;
}

echo $html;

//ここから同時呼び出し関数
function multiRequest($data, $options = array()) {

  // 配列を用意します。
  $curly = array();
  // data to be returned
  $result = array();

  //並列ファンクション
  $mh = curl_multi_init();

  // loop through $data and create curl handles
  // then add them to the multi-handle
  foreach ($data as $id => $d) {

    $curly[$id] = curl_init();

    $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
    curl_setopt($curly[$id], CURLOPT_URL,            $url);
    curl_setopt($curly[$id], CURLOPT_HEADER,         0);
    curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);

    // 投稿記事があるかどうか
    if (is_array($d)) {
      if (!empty($d['post'])) {
        curl_setopt($curly[$id], CURLOPT_POST, 1);
        curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
      }
    }

    if (!empty($options)) { curl_setopt_array($curly[$id], $options);}
    curl_multi_add_handle($mh, $curly[$id]);
  }

  $running = null;
// ハンドルを実行
  do {
    curl_multi_exec($mh, $running);
  } while($running > 0);

  foreach($curly as $id => $c) {
    $result[$id] = curl_multi_getcontent($c);
    curl_multi_remove_handle($mh, $c);
  }

  // ハンドルを閉じる
  curl_multi_close($mh);

  return $result;
}

?>
</div>
