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
 *  Sharing Cart block
 *
 *  @package    block_sharing_cart
 *  @copyright  2017 (C) VERSION2, INC.
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__.'/classes/controller.php';

class block_sharing_cart extends block_base
{
	public function init()
	{
		$this->title   = get_string('pluginname', __CLASS__);
		$this->version = 2015012700;
	}

	public function applicable_formats()
	{
		return array(
			'site'            => true,
			'course'          => true,
			'course-category' => false,
			'mod'             => false,
			'my'              => false,
			'tag'             => false,
			'admin'           => false,
			);
	}

	public function instance_can_be_docked()
	{
		return false; // AJAX won't work with Dock
	}

	public function has_config()
	{
		return true;
	}

	/**
	 *  Get the block content
	 *  
	 *  @global object $CFG
	 *  @global object $USER
	 *  @return object|string
	 */
	public function get_content()
	{
		global $CFG, $USER;
		
		if ($this->content !== null)
			return $this->content;
		
		if (!$this->page->user_is_editing())
			return $this->content = '';
		
		$context = context_course::instance($this->page->course->id);
		if (!has_capability('moodle/backup:backupactivity', $context))
			return $this->content = '';
		
		$controller = new sharing_cart\controller();
		$html = $controller->render_tree($USER->id);
		
        /* Place the <noscript> tag to give out an error message if JavaScript is not enabled in the browser.
         * Adding bootstrap classes to show colored info in bootstrap based themes. */
        $noscript = html_writer::tag('noscript',
            html_writer::tag('div', get_string('requirejs', __CLASS__), array('class' => 'error alert alert-danger'))
            );
        $html = $noscript . $html;

        $html .= '<div class="modal-bg" style="display: none;" id="sharing-cart-spinner-modal"><div class="spinner-container"><div style="margin-top: 10px;">' . get_string('inprogess_pleasewait', 'block_sharing_cart') . '</div></div></div>';
		
		$this->page->requires->css('/blocks/sharing_cart/styles.css');
		if ($this->is_special_version()) {
			$this->page->requires->css('/blocks/sharing_cart/custom.css');
		}
//		$this->page->requires->js('/blocks/sharing_cart/module.js');
//		$this->page->requires->yui_module('block_sharing_cart', 'M.block_sharing_cart.init', array(), null, true);
        $this->page->requires->jquery();
		$this->page->requires->js('/blocks/sharing_cart/script.js');
        $this->page->requires->strings_for_js(
			array('yes', 'no', 'ok', 'cancel', 'error', 'edit', 'move', 'delete', 'movehere'),
			'moodle'
			);
		$this->page->requires->strings_for_js(
			array('copyhere', 'notarget', 'backup', 'restore', 'movedir', 'clipboard',
					'confirm_backup', 'confirm_backup_section', 'confirm_userdata', 'confirm_userdata_section', 'confirm_delete'),
			__CLASS__
			);
		
		$footer = '<div style="display:none;">'
				. '<div class="header-commands">' . $this->get_header() . '</div>'
				. '</div>';
		return $this->content = (object)array('text' => $html, 'footer' => $footer);
	}

	/**
	 *  Get the block header
	 *  
	 *  @global core_renderer $OUTPUT
	 *  @return string
	 */
	private function get_header()
	{
		global $OUTPUT;
		// link to bulkdelete
		$alt = get_string('bulkdelete', __CLASS__);
		$src = $OUTPUT->image_url('bulkdelete', __CLASS__);
		$url = new moodle_url('/blocks/sharing_cart/bulkdelete.php', array('course' => $this->page->course->id));
		
		return $this->get_bulk_delete($src, $alt, $url) . $this->get_help_icon();
	}
	
	/**
	 *  Get bulk delete
	 *  
	 *  @param string $src
	 *  @param string $alt
	 *  @param moodle_url $url
	 *  @return string
	 */
	private function get_bulk_delete($src, $alt, $url)
	{	
		$bulkdelete = '<a class="editing_bulkdelete" title="' . s($alt) . '" href="' . s($url) . '">'
		        . '<img src="' . s($src) . '" alt="' . s($alt) . '" />'
		                . '</a>';
		
		return $bulkdelete;
	}
	
	/**
	 *  Get help icon
	 *  
	 *  @return string
	 */
	private function get_help_icon()
	{
		global $OUTPUT;
		$helpicon = $OUTPUT->help_icon('sharing_cart', __CLASS__);
		$helpicon = str_replace('class="', 'class="help-icon ', $helpicon);
		return $helpicon;
	}
	
	/**
	 *  Check Moodle 3.2 or later
	 * 
	 *  @return boolean
	 */
	private function is_special_version()
	{
		return moodle_major_version() >= 3.2;
	}

	/**
	 *  Get the block content for no-AJAX
	 *  
	 *  @global core_renderer $OUTPUT
	 *  @return string
	 */
	private function get_content_noajax()
	{
		global $OUTPUT;
		
		$html = '<div class="error">' . get_string('requireajax', __CLASS__) . '</div>';
		if (has_capability('moodle/site:config', context_system::instance())) {
			$url = new moodle_url('/admin/settings.php?section=ajax');
			$link = '<a href="' . s($url) . '">' . get_string('ajaxuse') . '</a>';
			$html .= '<div>' . $OUTPUT->rarrow() . ' ' . $link . '</div>';
		}
		return $html;
	}
}
