<?php
/**
 *  SharingCart_DebugTrace
 */

class SharingCart_DebugTrace
{
	/**
	 *  デバッグ有効フラグ
	 */
	protected $enabled = FALSE;
	
	/**
	 *  出力
	 *  
	 *  @param[in]  string $s  出力文字列
	 */
	protected /*abstract*/ function output($s)
	{
		// 出力方法は継承先のサブクラスで定義する
	}
	
	
	private $prevCall = NULL;
	private $vdepth   = 0;
	
	/**
	 *  変数を縦に書式化する階層の深さを設定
	 */
	public function setVerticalDepth($vdepth = 0)
	{
		$this->vdepth = $vdepth;
	}
	
	/**
	 *  書式化してトレース出力
	 */
	public function printf(/* $format, $args... */)
	{
		if (!$this->enabled)
			return;
		
		$args = func_get_args();
		$format = array_shift($args);
		
		$dump = array();
		
		$bt = debug_backtrace();
		if (($call = self::formatCall($bt[1])) != $this->prevCall) {
			$dump[] = $this->prevCall = $call;
		}
		$dump[] = self::addIndent(vsprintf($format, $args));
		
		$this->output(implode("\n", $dump)."\n");
	}
	
	/**
	 *  変数をトレース
	 */
	public function trace(/* $args... */)
	{
		if (!$this->enabled)
			return;
		
		$dump = array();
		
		$bt = debug_backtrace();
		if (($call = self::formatCall($bt[1])) != $this->prevCall) {
			$dump[] = $this->prevCall = $call;
		}
		if ($args = func_get_args()) {
			$dump[] = self::addIndent(self::formatArgs($args, $this->vdepth));
		}
		$this->output(implode("\n", $dump)."\n");
	}
	
	private static function formatCall($call)
	{
		return $call['class'].'::'.$call['function']
		     . '('.self::formatArgs($call['args']).')';
	}
	
	private static function formatArgs($args, $vdepth = 0)
	{
		$r = array();
		foreach ($args as $arg)
			$r[] = self::formatVar($arg, $vdepth);
		return implode(', ', $r);
	}
	
	private static function formatVar($var, $vdepth = 0)
	{
		if (is_array($var)) {
			$var = self::formatArray($var, $vdepth);
			if ($vdepth > 0) {
				return "[\n".self::addIndent($var)."\n]";
			}
			return '[ '.$var.' ]';
		} elseif (is_object($var)) {
			$var = self::formatArray(get_object_vars($var), $vdepth);
			if ($vdepth > 0) {
				return "{\n".self::addIndent($var)."\n}";
			}
			return '{ '.$var.' }';
		} elseif (is_string($var)) {
			return "'$var'";
		} elseif (is_bool($var)) {
			return $var ? 'TRUE' : 'FALSE';
		} elseif (is_null($var)) {
			return 'NULL';
		} elseif (is_resource($var)) {
			return '#'.get_resource_type($var);
		}
		return (string)$var;
	}
	
	private static function formatArray($arr, $vdepth = 0)
	{
		$r = array();
		if ($vdepth > 0) {
			$n = max(array_map('strlen', array_keys($arr)));
			foreach ($arr as $k => $v) {
				$r[] = $k.str_repeat(' ', $n - strlen($k))
				     . ': '.self::formatVar($v, $vdepth - 1);
			}
			return implode(",\n", $r);
		}
		foreach ($arr as $k => $v)
			$r[] = $k.':'.self::formatVar($v);
		return implode(', ', $r);
	}
	
	private static function addIndent($s, $n = 1, $t = "\t")
	{
		$lines = explode("\n", $s);
		foreach ($lines as & $line)
			$line = str_repeat($t, $n) . $line;
		return implode("\n", $lines);
	}
}

?>