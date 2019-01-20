<?php
/**
 *  SharingCart_DebugTrace_File
 */

require_once dirname(__FILE__).'/SharingCart_DebugTrace.php';

class SharingCart_DebugTrace_File extends SharingCart_DebugTrace
{
	/**
	 *  コンストラクタ
	 *  
	 *  @param[in]  string $logPath  ログファイルパス (NULL でトレースOFF)
	 */
	public function __construct($logPath = NULL)
	{
		$this->logPath = $logPath;
		$this->enabled = !empty($logPath);
	}
	private $logPath = NULL;
	
	/**
	 *  出力
	 *  
	 *  @param[in]  string $s  出力文字列
	 */
	protected /*override*/ function output($s)
	{
		file_put_contents($this->logPath, $s, FILE_APPEND);
	}
}

?>