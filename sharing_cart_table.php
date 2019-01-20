<?php
/**
 *  sharing_cart テーブル操作クラス
 */
class sharing_cart_table
{
	const NAME = 'sharing_cart';
	
	/**
	 *	ユニーク名(=バックアップ作成時刻)からZIPファイル名を生成
	 */
	public static function gen_zipname($time)
	{
		return 'shared-'.date('Ymd-His', $time).'.zip';
	}
	
	/**
	 *	レコード取得 + 以前のバージョンのデータで足りない値があれば補完
	 */
	public static function get_record_by_id($id)
	{
		$record = get_record(self::NAME, 'id', $id);
		if (!$record)
			return null;
		if (empty($record->filename)) {
			// `filename`フィールドが空なら自動生成
			$record->filename = self::gen_zipname($record->ctime);
			set_field(self::NAME, 'filename', $record->filename, 'id', $id);
		}
		return $record;
	}
	
	/**
	 *	レコード挿入 + 表示順を再構築
	 */
	public static function insert_record($record)
	{
		if (empty($record->filename)) {
			// `file`フィールドが空なら自動生成
			$record->filename = self::gen_zipname($record->ctime);
		}
		if (!insert_record(self::NAME, $record))
			return FALSE;
		self::renumber($record->userid);
		return TRUE;
	}
	
	/**
	 *	レコード更新 + 表示順を再構築
	 */
	public static function update_record($record)
	{
		if (!update_record(self::NAME, $record))
			return FALSE;
		self::renumber($record->userid);
		return TRUE;
	}
	
	/**
	 *	レコード削除 + 表示順を再構築
	 */
	public static function delete_record($record)
	{
		if (!delete_records(self::NAME, 'id', $record->id))
			return FALSE;
		self::renumber($record->userid);
		return TRUE;
	}
	
	/**
	 *	Sharing Cart ブロック内でのアイテム表示順の通し番号を振りなおす
	 */
	public static function renumber($userid = NULL)
	{
		if (empty($userid)) {
			$userid = $GLOBALS['USER']->id;
		}
		if ($records = get_records(self::NAME, 'userid', $userid)) {
			$tree = array();
			foreach ($records as $record) {
				if (!isset($tree[$record->tree]))
					$tree[$record->tree] = array();
				$tree[$record->tree][] = $record;
			}
			foreach ($tree as $items) {
				usort($items, array(__CLASS__, 'renumber_cmp'));
				foreach ($items as $i => $item) {
					$item->weight  = 1 + $i;
					$item->modtext = addslashes($item->modtext);
					if (!update_record(self::NAME, $item))
					    return FALSE;
				}
			}
		}
		return TRUE;
	}
	protected static function renumber_cmp($a, $b)
	{
		// 既に振られていればそれに従う
		if ($a->weight < $b->weight) return -1;
		if ($a->weight > $b->weight) return +1;
		// 番号が重複していた場合は文字順に並べ替え
		return strnatcasecmp($a->modtext, $b->modtext);
	}
}
