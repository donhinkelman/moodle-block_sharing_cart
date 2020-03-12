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

/**
 *  The execution step that fixes missing questions
 *
 *  This step must be inserted between restore_process_categories_and_questions
 *  and restore_create_categories_and_questions of restore_root_task
 */
class restore_fix_missing_questions extends restore_execution_step {
    /**
     *  Checks if mapped questions are exact valid, or marks them to be created
     *
     * @throws moodle_exception
     * @global $DB
     */
    protected function define_execution() {
        global $DB;

        $restoreid = $this->get_restoreid();
        $courseid = $this->get_courseid();
        $userid = $this->task->get_userid();

        $workaround_qtypes = explode(',', get_config('block_sharing_cart', 'workaround_qtypes'));

        // @see /backup/util/dbops/restore_dbops.class.php#prechek_precheck_qbanks_by_level
        $contexts = restore_dbops::restore_get_question_banks($restoreid);
        foreach ($contexts as $contextid => $contextlevel) {
            $categories = restore_dbops::restore_get_question_categories($restoreid, $contextid);
            $canadd = false;
            if ($targetcontext = restore_dbops::restore_find_best_target_context($categories, $courseid, $contextlevel)) {
                $canadd = has_capability('moodle/question:add', $targetcontext, $userid);
            }
            foreach ($categories as $category) {
                $questions = restore_dbops::restore_get_questions($restoreid, $category->id);
                foreach ($questions as $question) {
                    if (!in_array($question->qtype, $workaround_qtypes)) {
                        continue;
                    }
                    $mapping = restore_dbops::get_backup_ids_record($restoreid, 'question', $question->id);
                    if ($mapping && $mapping->newitemid &&
                            !self::is_question_valid($question->qtype, $mapping->newitemid)) {
                        if (!$canadd) {
                            throw new moodle_exception('questioncannotberestored', 'backup', '', $question);
                        }
                        $catmapping = restore_dbops::get_backup_ids_record($restoreid, 'question_category', $category->id);
                        $matchquestions = $DB->get_records('question', array(
                                'category' => $catmapping->newitemid,
                                'qtype' => $question->qtype,
                                'stamp' => $question->stamp,
                                'version' => $question->version
                        ));
                        $newitemid = 0; // to be created if no valid duplicate exists
                        foreach ($matchquestions as $q) {
                            if ($q->id == $mapping->newitemid) {
                                continue;
                            }
                            if (self::is_question_valid($question->qtype, $q->id)) {
                                $newitemid = $q->id; // updates mapping if a valid one found
                                break;
                            }
                        }
                        $this->update_mapping($mapping, $newitemid);
                    }
                }
            }
        }
    }

    /**
     *  Updates existing mapping
     *
     * @param object $record
     * @param int $newitemid
     */
    private function update_mapping($record, $newitemid) {
        $restoreid = $this->get_restoreid();
        $key = "{$record->itemid} {$record->itemname} {$restoreid}";
        $extrarecord = array('newitemid' => $newitemid);

        // restore_dbops::update_backup_cached_record($record, $extrarecord, $key, $existingrecord = null);
        $reflector = new ReflectionMethod('restore_dbops', 'update_backup_cached_record');
        $reflector->setAccessible(true);
        $reflector->invoke(null, $record, $extrarecord, $key, $record);
    }

    /**
     *  Checks if a question is valid
     *
     * @param string $qtypename
     * @param int $questionid
     * @return boolean
     * @global $DB
     */
    private static function is_question_valid($qtypename, $questionid) {
        global $DB;

        // checks if the question exists by question_type->get_question_options()
        $question = (object) array('id' => $questionid);
        try {
            // qtype_multianswer expects that options property is an object instead of undefined
            $question->options = new stdClass;
            $oldhandler = set_error_handler(function($n, $s, $f, $l) {
                return true;
            });
            question_bank::get_qtype($qtypename)->get_question_options($question);
            isset($oldhandler) && set_error_handler($oldhandler);
            if (count(get_object_vars($question->options)) == 0) {
                if ($qtypename === 'random') {
                    // qtype_random does nothing, but is valid
                } else {
                    return false;
                }
            }
        } catch (moodle_exception $ex) {
            isset($oldhandler) && set_error_handler($oldhandler);
            return false;
        }
        // somehow, subquestions might go away, but inconsistency of them causes restore interruption
        // @see /question/type/match/backup/moodle2/restore_qtype_match_plugin.class.php#process_match
        if (property_exists($question->options, 'subquestions')) {
            if (empty($question->options->subquestions)) {
                return false;
            }
            // so, let's check deep -- is there any faster way??
            $dbman = $DB->get_manager();
            if ($dbman->table_exists("question_{$qtypename}") &&
                    $dbman->field_exists("question_{$qtypename}", 'question') &&
                    $dbman->table_exists("question_{$qtypename}_sub") &&
                    $dbman->field_exists("question_{$qtypename}_sub", 'question')) {
                // checks if all the subquestions exist
                $q = $DB->get_record("question_{$qtypename}", array('question' => $question->id));
                if (!$q || empty($q->subquestions)) {
                    return false;
                }
                $subquestionids = explode(',', $q->subquestions);
                list ($sql, $params) = $DB->get_in_or_equal($subquestionids);
                $sql .= ' AND question = ?';
                $params[] = $question->id;
                $count = $DB->get_field_select("question_{$qtypename}_sub", 'COUNT(*)', "id $sql", $params);
                if ($count != count($subquestionids)) {
                    return false;
                }
            }
        }
        return true;
    }
}
