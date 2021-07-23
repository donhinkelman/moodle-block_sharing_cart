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

use core_renderer;
use html_writer;

/**
 *  Sharing Cart item tree renderer
 */
class renderer {
    /**
     *  Render an item tree
     *
     * @param array & $tree
     * @return string
     * @throws \coding_exception
     */
    public static function render_tree(array &$tree): string {
        $html = html_writer::start_tag('ul', ['class' => 'tree list']);

        $requirede_capabilities = required_capabilities::init([
            'moodle/restore:restorecourse',
            'moodle/restore:restoreactivity'
        ]);
        if (!empty($requirede_capabilities->get_disallowed_actions())) {
            $html .= html_writer::start_div('alert alert-danger', [
                'role' => 'alert',
                'id' => 'alert-disallow',
                'data-disallowed-actions' => implode(',', $requirede_capabilities->get_disallowed_actions())
            ]);
            $missing_key = $requirede_capabilities->total_capabilities_missing() > 1
                ? 'missing_capabilities'
                : 'missing_capability';
            $html .= get_string(
                $missing_key,
                'block_sharing_cart',
                implode(', ', $requirede_capabilities->get_missing_capabilities())
            );
            $html .= html_writer::end_div();
        }

        $html .= self::render_node($tree, '/');
        $html .= html_writer::end_tag('ul');

        return $html;
    }

    /**
     *  Render a node of item tree
     *
     * @param array & $node
     * @param string $path
     * @return string
     * @throws \coding_exception
     */
    private static function render_node(array &$node, string $path): string {
        $html = '';
        foreach ($node as $name => & $leaf) {
            if ($name !== '') {
                $next = rtrim($path, '/') . '/' . $name;
                $html .= self::render_dir_open($next, $leaf);
                $html .= self::render_node($leaf, $next);
                $html .= self::render_dir_close();
            } else {
                foreach ($leaf as $item) {
                    if (!$item->modname) { // issue-83: skip rendering empty item in empty section (wnat to render only the folder)
                        continue;
                    }
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
     * @param array $leaf
     * @return string
     * @throws \coding_exception
     * @global core_renderer $OUTPUT
     */
    private static function render_dir_open(string $path, array $leaf): string {
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
     * @param \stdClass $item
     * @return string
     * @throws \coding_exception
     */
    private static function render_item(string $path, \stdClass $item): string {
        $components = array_filter(explode('/', trim($path, '/')), 'strlen');
        $depth = count($components);
        $class = $item->modname . ' ' . "modtype_{$item->modname}" . ($item->uninstalled_plugin ? ' disabled' : '');

        $coursename = '';
        if ($item->coursefullname != null) {
            $coursename = " [{$item->coursefullname}]";
        }

        $title = html_to_text($item->modtext) . $coursename;

        if ($item->modname === 'label') {
            $item->modtext = self::strip_label($item->modtext);
            $item->modtext = self::replace_image_with_string($item->modtext);
        }

        return '
				<li class="activity ' . $class . '" id="block_sharing_cart-item-' . $item->id . '" data-disable-copy="'. ($item->uninstalled_plugin ?? 0) .'">
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
    private static function render_dir_close(): string {
        return '
			</ul>
		</li>';
    }

    /**
     *  Render a module icon
     *
     * @param \stdClass $item
     * @return string
     * @throws \coding_exception
     * @global core_renderer $OUTPUT
     */
    public static function render_modicon(\stdClass $item): string {
        global $OUTPUT;

        if(isset($item->uninstalled_plugin) && $item->uninstalled_plugin) {
            return '<i class="icon fa fa-fw fa-exclamation text-danger align-self-center" title="'.get_string('uninstalled_plugin_warning_title', 'block_sharing_cart', 'mod_'.$item->modname).'"></i>';
        }

        $src = '<img class="activityicon iconsmall iconcustom" src="' . $OUTPUT->image_url('icon', $item->modname) . '" alt="" />';
        if (!empty($item->modicon)) {
            // @see /lib/modinfolib.php#get_icon_url()
            if (strncmp($item->modicon, 'mod/', 4) == 0) {
                [$modname, $iconname] = explode('/', substr($item->modicon, 4), 2);
                $src = $OUTPUT->image_icon($iconname, $modname);
            } else {
                $src = $OUTPUT->image_icon($item->modicon, 'modicon');
            }
        }
        return $src;
    }

    /**
     * @param string $modtext
     * @return string
     */
    private static function strip_label(string $modtext): string {
        return strip_tags($modtext, '<img>');
    }

    /**
     * @param string $modtext
     * @return string
     * @throws \coding_exception
     */
    private static function replace_image_with_string(string $modtext): string {
        if (strpos($modtext, '<img') !== false) {
            $modtext = preg_replace('/<img[^>]+>/i', get_string('label_image_replaced_text', 'block_sharing_cart'), $modtext);
        }
        return $modtext;
    }
}
