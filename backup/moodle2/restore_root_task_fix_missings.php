<?php
/**
 *  Sharing Cart
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: restore_root_task_fix_missings.php 882 2012-11-01 05:06:21Z malu $
 */

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/restore_fix_missing_questions.php';

/**
 *  The root task that fixes missings before execution
 */
class restore_root_task_fix_missings extends restore_root_task
{
    public function build()
    {
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
