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

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/restore_fix_missing_questions.php';

/**
 *  The root task that fixes missings before execution
 */
class restore_root_task_fix_missings extends restore_root_task {
    public function build() {
        parent::build();

        // inserts a restore_fix_missing_questions step
        // before restore_create_categories_and_questions
        $fix_missing_questions = new restore_fix_missing_questions('fix_missing_questions');
        $fix_missing_questions->set_task($this);
        foreach ($this->steps as $i => $step) {
            if ($step instanceof restore_create_categories_and_questions) {
                array_splice($this->steps, $i, 0, array($fix_missing_questions));
                break;
            }
        }
    }
}
