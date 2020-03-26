<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Sharing Cart
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_sharing_cart;

defined('MOODLE_INTERNAL') || die();

use tool_monitor\output\managesubs\subs;

/**
 *  Sharing Cart item tree renderer
 */
class renderer {
    /**
     *  Render an item tree
     *
     * @param array & $tree
     * @return string
     */
    public static function render_tree(array &$tree) {
        return '<ul class="tree list" style="font-size:90%;">'
                . self::render_node($tree, '/')
                . '</ul>';
    }

    /**
     *  Render a node of item tree
     *
     * @param array & $node
     * @param string $path
     * @return string
     */
    private static function render_node(array &$node, $path) {
        $html = '';
        foreach ($node as $name => & $leaf) {
            if ($name !== '') {
                $next = rtrim($path, '/') . '/' . $name;
                $html .= self::render_dir_open($next, $leaf);
                $html .= self::render_node($leaf, $next);
                $html .= self::render_dir_close();
            } else {
                foreach ($leaf as $item) {
                    $html .= self::render_item($path, $item);
                }
            }
        }
        return $html;
    }

    /**
     *  Render a directory open
     *
     * @param string $path
     * @param $leaf
     * @return string
     * @global \core_renderer $OUTPUT
     */
    private static function render_dir_open($path, $leaf) {
        global $OUTPUT, $DB;

        $coursename = '';

        $coursefullnames = array();
        if (isset($leaf[''])) {
            foreach ($leaf[''] as $item) {
                $coursefullnames[] = $item->coursefullname;
            }
        }
        $coursefullnames = array_unique($coursefullnames);
        if (count($coursefullnames) == 1 && $coursefullnames[0] != '') {
            $coursename = " [{$coursefullnames[0]}]";
        } else if (count($coursefullnames) > 1) {
            $coursename = ' [' . get_string("variouscourse", "block_sharing_cart") . ']';
        }
        $components = explode('/', trim($path, '/'));
        $depth = count($components) - 1;
        return '
		<li class="directory" directory-path="' . htmlentities($path) . '">
			<div class="sc-indent-' . $depth . '" title="' . htmlentities($path . $coursename) . '">
			    <div class="toggle-wrapper">
                    <i class="icon fa fa-folder-o" alt=""></i>
                    <span class="instancename">' . format_string(end($components)) . '</span>			    
                </div>
                <span class="commands"></span>
			</div>
			<ul class="list" style="display:none;">';
    }

    /**
     *  Render an item
     *
     * @param string $path
     * @param record $item
     * @return string
     */
    private static function render_item($path, $item) {
        $components = array_filter(explode('/', trim($path, '/')), 'strlen');
        $depth = count($components);
        $class = $item->modname . ' ' . "modtype_{$item->modname}";

        $coursename = '';
        if ($item->coursefullname != null) {
            $coursename = " [{$item->coursefullname}]";
        }

        $title = html_to_text($item->modtext) . $coursename;

        return '
				<li class="activity ' . $class . '" id="block_sharing_cart-item-' . $item->id . '">
					<div class="sc-indent-' . $depth . '" title="' . $title . '">
						' . self::render_modicon($item) . '
						<span class="instancename">' . $item->modtext . '</span>
						<span class="commands"></span>
					</div>
				</li>';
    }

    /**
     *  Render a directory close
     *
     * @return string
     */
    private static function render_dir_close() {
        return '
			</ul>
		</li>';
    }

    /**
     *  Render a module icon
     *
     * @param object $item
     * @return string
     * @global \core_renderer $OUTPUT
     */
    public static function render_modicon($item) {
        global $OUTPUT;

        $src = '<img class="activityicon iconsmall iconcustom" src="' . $OUTPUT->image_url('icon', $item->modname) . '" alt="" />';
        if (!empty($item->modicon)) {
            // @see /lib/modinfolib.php#get_icon_url()
            if (strncmp($item->modicon, 'mod/', 4) == 0) {
                list ($modname, $iconname) = explode('/', substr($item->modicon, 4), 2);
                $src = $OUTPUT->image_icon($iconname, $modname);
            } else {
                $src = $OUTPUT->image_icon($item->modicon, 'modicon');
            }
        }
        return $src;
    }

    public static function render_label($modtext) {
        $modtext = get_string('pluginname', 'label') .
                ':<div style="font-size: 0.8em; width: 100%; max-height: 10em; white-space: nowrap; overflow: auto;">' . $modtext .
                '</div>';

        return $modtext;
    }
}
