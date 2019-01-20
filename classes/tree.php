<?php
/**
 *  Sharing Cart - Tree Builder
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: tree.php 536 2011-11-13 14:19:55Z malu $
 */

namespace sharing_cart;

interface tree_renderer
{
	function open($name);
	function write($item);
	function close();
}

class tree
{
	public function __construct(array $items = array())
	{
		$this->root = array();
		
		foreach ($items as $it)
			$this->add($it);
	}
	private $root;
	
	public function add($item)
	{
		$path = explode('/', $item->tree);
		$node =& $this->root;
		do {
			$dir = (string)array_shift($path);
			isset($node[$dir]) or $node[$dir] = array();
			$node =& $node[$dir];
		} while ($dir != '');
		$node[] = $item;
	}
	
	public function render(tree_renderer $renderer)
	{
		return self::render_tree($renderer, $this->root);
	}
	
	private static function render_tree(tree_renderer $renderer, array & $node)
	{
		$html = '';
		self::sort_node($node);
		foreach ($node as $name => & $leaf) {
			if ($name != '') {
				$html .= $renderer->open($name);
				$html .= self::render_tree($renderer, $leaf);
				$html .= $renderer->close();
			} else {
				self::sort_leaf($leaf);
				foreach ($leaf as $item)
					$html .= $renderer->write($item);
			}
		}
		return $html;
	}
	private static function sort_node(array & $node)
	{
		uksort($node, function ($lhs, $rhs)
		{
			// directory first
			if ($lhs == '') return +1;
			if ($rhs == '') return -1;
			return strnatcasecmp($lhs, $rhs);
		});
	}
	private static function sort_leaf(array & $leaf)
	{
		usort($leaf, function ($lhs, $rhs)
		{
			// by sharing_cart->weight field
			if ($lhs->weight < $rhs->weight) return -1;
			if ($lhs->weight > $rhs->weight) return +1;
			// or by modtext
			return strnatcasecmp($lhs->modtext, $rhs->modtext);
		});
	}
}
