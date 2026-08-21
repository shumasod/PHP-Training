<?php

declare(strict_types=1);

// 注意: declare(strict_types=1) はファイルの先頭でしか使えないため、
// 従来ここより前に置かれていた <div id="rss"> は下の出力側へ移した。

//表示記事数
const HYOJI_NUM = 30;

// 1 フィードあたりのタイムアウト（秒）
const FEED_CONNECT_TIMEOUT = 5;
const FEED_TIMEOUT = 10;

// レスポンスサイズの上限（バイト）
// 巨大なレスポンスでメモリを食い潰されないようにする。
const FEED_MAX_BYTES = 2 * 1024 * 1024;

//フィード登録
// http:// は中間者に書き換えられるため https:// を使う。
// 取得した内容はこのページの HTML に埋め込まれるので、
// 平文で取ると経路上で任意の HTML を差し込まれる。
$feedUrls = [
    'https://feeds.japan.cnet.com/rss/cnet/all.rdf',
    'https://www.vector.co.jp/rss/softnews.xml',
    'https://srad.jp/slashdot.rdf',
    'https://rss.itmedia.co.jp/rss/1.0/ait.xml',
    'https://k-tai.watch.impress.co.jp/data/rss/1.0/ktw/feed.rdf',
];

date_default_timezone_set('Asia/Tokyo');

/**
 * HTML エスケープ。
 *
 * フィードの中身は「外部サイトが自由に決められる文字列」なので、
 * 自サイトのユーザー入力と同じ扱いで必ずエスケープする。
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * href に埋め込んでよい URL かどうかを判定する。
 *
 * htmlspecialchars() は javascript:alert(1) のような文字列を素通しする
 * （エスケープ対象の文字を含まないため）。
 * href に入った値はクリック時に URL として解釈されるので、
 * スキームを http/https に限定する必要がある。
 */
function isSafeFeedUrl(string $url): bool
{
    $scheme = parse_url(trim($url), PHP_URL_SCHEME);

    return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
}

/**
 * 複数の URL を並列に取得する。
 *
 * @param list<string> $urls
 * @return array<int, string|null> 取得できなかったものは null
 */
function multiRequest(array $urls): array
{
    $handles = [];
    $results = [];

    $mh = curl_multi_init();

    foreach ($urls as $id => $url) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,

            // タイムアウトの指定。
            // 修正前は無指定だったため、応答しないフィードが 1 つでもあると
            // ページ全体が返らなくなる（PHP の max_execution_time まで待つ）。
            CURLOPT_CONNECTTIMEOUT => FEED_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => FEED_TIMEOUT,

            // 証明書の検証は必ず有効にする（既定値だが明示する）。
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,

            // リダイレクトは追うが、無限ループしないよう上限を設ける。
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,

            // http/https 以外のスキームへリダイレクトさせない
            // （file:// へ飛ばされるとローカルファイルを読み込んでしまう）。
            CURLOPT_PROTOCOLS         => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS   => CURLPROTO_HTTP | CURLPROTO_HTTPS,

            // レスポンスサイズの上限。
            CURLOPT_BUFFERSIZE  => 16 * 1024,
            CURLOPT_NOPROGRESS  => false,
            CURLOPT_PROGRESSFUNCTION => static function ($ch, $dlTotal, $dlNow) {
                // 1 を返すと転送を中断する
                return $dlNow > FEED_MAX_BYTES ? 1 : 0;
            },
        ]);

        $handles[$id] = $ch;
        curl_multi_add_handle($mh, $ch);
    }

    // 転送の実行。
    //
    // 修正前は
    //     do { curl_multi_exec($mh, $running); } while ($running > 0);
    // という書き方だった。curl_multi_exec() は即座に返るため、
    // これは通信の完了を CPU 100% で待ち続けるビジーループになる。
    // curl_multi_select() でソケットが動くまで寝かせる。
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running > 0) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running > 0 && $status === CURLM_OK);

    foreach ($handles as $id => $ch) {
        $body = curl_multi_getcontent($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        // 取得に失敗したフィードは黙って捨てる。
        // 修正前は失敗時の false をそのまま simplexml_load_string() へ
        // 渡していたため、Warning が出たうえで後続の判定が崩れていた。
        $results[$id] = ($body !== false && $body !== '' && $httpCode === 200) ? $body : null;

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);

    return $results;
}

$rssdataRaw = multiRequest($feedUrls);

// 記事の一覧。
//
// 修正前は $outdata[$myDateGNU] のように「日時」をキーにしていたため、
// 同じ秒に公開された記事が互いを上書きして消えていた。
// また 1 件も取得できなかった場合は $outdata が未定義のまま
// krsort($outdata) に渡されて Fatal error になっていた。
$items = [];

foreach ($rssdataRaw as $raw) {
    if ($raw === null) {
        continue;
    }

    // 壊れた XML でも Warning を出さずに扱えるようにする。
    $previous = libxml_use_internal_errors(true);
    $rssdata = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if ($rssdata === false) {
        continue;
    }

    // RSS 2.0 は channel の下、RSS 1.0 (RDF) は直下に item がある
    if (isset($rssdata->channel->item)) {
        $rssdata = $rssdata->channel;
    }

    if (!isset($rssdata->item)) {
        continue;
    }

    foreach ($rssdata->item as $entry) {
        $rssDate = (string) $entry->pubDate;
        if ($rssDate === '') {
            $rssDate = (string) $entry->children('http://purl.org/dc/elements/1.1/')->date;
        }

        $timestamp = strtotime($rssDate);
        if ($timestamp === false) {
            $timestamp = 0;
        }

        $link = trim((string) $entry->link);
        if (!isSafeFeedUrl($link)) {
            // http/https 以外のリンクは表示しない
            continue;
        }

        $items[] = [
            'timestamp' => $timestamp,
            'title'     => mb_strimwidth((string) $entry->title, 0, 140, '…', 'UTF-8'),
            'link'      => $link,
        ];
    }
}

// 新しい順に並べる
usort($items, static fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

$html = '';
foreach (array_slice($items, 0, HYOJI_NUM) as $item) {
    // タイトルもリンクも外部サイトが決めた文字列なので必ずエスケープする。
    // rel="noopener noreferrer" を付けて、開いた先から window.opener 経由で
    // このページを操作されないようにする。
    $html .= '<p>■<a href="' . h($item['link']) . '" target="_blank" rel="noopener noreferrer">'
        . h($item['title']) . '</a></p>';
}

?>
<div id="rss">
<?= $html !== '' ? $html : '<p>記事を取得できませんでした。</p>' ?>
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
