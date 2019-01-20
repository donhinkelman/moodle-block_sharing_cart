<?php
/**
 * ファイル転送
 *
 * @author VERSION2 Inc.
 * @version $Id: FileTransfer.php 366 2009-10-22 12:18:18Z malu $
 */

class FileTransferException extends Exception {}

class FileTransfer
{
	/**
	 * ソケットを通してファイルをダウンロード
	 * 
	 * ファイル作成またはソケット接続の失敗時のみ例外を投げ、
	 * HTTPレスポンスで "200 OK" 以外が返ってもエラーとはせず生のヘッダを返す
	 * 
	 * @param[in]  string  $filepath
	 * @param[in]  string  $url
	 * @param[in]  array   $params    POSTパラメータ array('name'=>'value')
	 * @return     string             HTTPレスポンスヘッダ
	 * 
	 * @throws  FileTransferException
	 */
	public static function downloadFile($filepath, $url, $params = NULL)
	{
		// parse_url は E_WARNING を発生させるので抑制
		$prev_error_level = error_reporting(error_reporting() & ~E_WARNING);
		{
			$url_components = parse_url($url);
		}
		error_reporting($prev_error_level);
		
		if (!$url_components ||
			empty($url_components['scheme']) || empty($url_components['host']))
		{
			throw new FileTransferException('Invalid URL');
		}
		
		$fp = fopen($filepath, 'wb');
		if (!$fp)
			throw new FileTransferException('File creation failure');
		@flock($fp, LOCK_EX);
		
		if (!empty($url_components['port'])) {
			$port = $url_components['port'];
		} else {
			switch ($url_components['scheme']) {
			default     : $port =  80; break;
			case 'https': $port = 443; break;
			}
		}
		$sock = fsockopen($url_components['host'], $port);
		if (!$sock)
			throw new FileTransferException('Socket open failure');
		
		$post = array();
		if (!empty($params)) {
			foreach ($params as $name => $value) {
				$post[] = urlencode($name).'='.urlencode($value);
			}
		}
		$post = implode('&', $post);
		
		$path = $url_components['path'];
		if (!empty($url_components['query'])) {
			$path .= $url_components['query'];
		}
		$send = array(
			"POST $path HTTP/1.1",
			"User-Agent: PHP/".phpversion(),
			"HOST: ".$_SERVER['HTTP_HOST'],
			"Content-Type: application/x-www-form-urlencoded",
			"Content-Length: ".strlen($post),
			'',
			$post
		);
		$send = implode("\r\n", $send);
		fwrite($sock, $send);
		
		$header          = '';
		$header_cache    = '';
		$header_received = FALSE;
		while (!feof($sock)) {
			$recv = fgets($sock, 1024);
			if ($header_received) {
				fwrite($fp, $recv);
			} else if (($p = strpos($header_cache .= $recv, "\r\n\r\n")) !== FALSE) {
				list ($header, $body) = explode("\r\n\r\n", $header_cache, 2);
				fwrite($fp, $body);
				$header_received = TRUE;
			}
		}
		fclose($sock);
		
		fclose($fp);
		
		return $header;
	}
}

?>