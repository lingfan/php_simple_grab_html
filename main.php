<?php

$dir  = dirname(__FILE__);
$path = $dir . '/lib/';
require $path . 'simple_html_dom.php';

$start = $argv[1];
$end   = $argv[2];
var_dump($start, $end);
run($start, $end);

function run($start, $end) {
	$baseDir = dirname(__FILE__) . '/data/';
	$n       = 1;
	for ($i = $start; $i <= $end; $i++) {
		$url = sprintf('http://news.cps.com.cn/list/zhcx/pageid/%s', $i);
		echo $url . "\n";
		$file = $baseDir . 'list/html/' . $i . '.html';
		down($file, $url);
		$html = file_get_html($file);
		foreach ($html->find('div.list ul') as $ul) {
			foreach ($ul->find('li') as $li) {
				//$tmpTxt = iconv('utf-8', 'gbk', $li->innertext);
				$tmpTxt = $li->innertext;
				if (empty($tmpTxt)) {
					continue;
				}

				$id          = $n;
				$txtFilename = "{$baseDir}txt/{$id}.txt";
				$pathurl     = pathinfo($txtFilename);
				if (!file_exists($pathurl['dirname'])) {
					mkdir($pathurl['dirname'], 0777, true);
				}
				if (!file_exists($txtFilename)) {

					$tmp       = str_get_html($tmpTxt);
					$detailUrl = $tmp->find('a', 0)->href;
					$title     = $tmp->find('a', 0)->innertext;

					$detailFilename = $baseDir . '/detail/html/' . $n . '.html';
					down($detailFilename, $detailUrl);

					$html = file_get_html($detailFilename);

					$content = str_ireplace('<strong>    【CPS<a target="_blank" href="http://www.cps.com.cn/">中安网</a> cps.com.cn】</strong>', '', $html->find('div.news-t4', 0)->innertext);

					$desc = $html->find('div.zhaiyao', 0)->innertext;
					$t    = $html->find('div.news-t3 span', 0)->innertext;
					$from = $html->find('div.news-t3 span', 1)->innertext;


					$hhh = str_ireplace('src="/static/upload/', 'src="http://smartcity.cps.com.cn/static/upload/', $content);
					$imgHtml = str_get_html($hhh);
					$iimg = 1;
					foreach ($imgHtml->find('img') as $element) {
						$_img_url    = $element->src;
						$pathimg = pathinfo($_img_url);
						$_imgext = !empty($pathimg['extension'])?$pathimg['extension']:'jpg';

						$path_file = "/img/{$id}/{$iimg}.{$_imgext}";
						$imgFilename = $baseDir . $path_file;
						down($imgFilename, $_img_url);
						$a[$iimg] = $_img_url;
						$b[$iimg] = $path_file;
						$iimg++;
					}
					$content = str_ireplace($a, $b, $hhh);

					$id  = $n;
					$arr = array(
						'id'         => $id,
						'title'      => $title,
						'created_at' => $t,
						'from'       => str_ireplace('来源:', '', $from),
						'desc'       => str_ireplace('摘要:', '', $desc),
						'content'    => $content,
					);

					$str    = serialize($arr) . "\n";
					$outstr = iconv('utf-8', 'GB18030', $str);

					echo "{$start}-{$end}:{$i}:{$n}:{$outstr}";
					file_put_contents($txtFilename, $str);
					$n++;
				}

			}

		}

	}
}


function down($file, $url) {
	$pathurl = pathinfo($file);
	if (!file_exists($pathurl['dirname'])) {
		mkdir($pathurl['dirname'], 0777, true);
	}

	if (!file_exists($file)) {
		$content = getUrlContent($url);
		$len     = mb_strlen($content);
		if ($len > 0) {
			file_put_contents($file, $content);
		}
		echo $file . "\n";
		echo sprintf("%s(%d)\n", $url, $len) . "\n";
	}

}


function getUrlContent($url, $type = 1) {
	if ($type) {
		$ch      = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);

		$header = FormatHeader($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  //设置头信息的地方
		curl_setopt($ch, CURLOPT_HEADER, 0);    //不取得返回头信息
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$file = curl_exec($ch);
		curl_close($ch);
	} else {
		ob_start();
		readfile($url);
		$file = ob_get_contents();
		ob_end_clean();
	}
	return $file;
}


function FormatHeader($url, $myIp = null, $xml = null) {
	// 解悉url
	$temp  = parse_url($url);
	$query = isset($temp['query']) ? $temp['query'] : '';
	$path  = isset($temp['path']) ? $temp['path'] : '/';

	$header = array(
		"POST {$path}?{$query} HTTP/1.1",
		"Host: {$temp['host']}",
		'Accept: */*',
		"Referer: http://{$temp['host']}/",
		'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53',
	);

	return $header;
}

?>