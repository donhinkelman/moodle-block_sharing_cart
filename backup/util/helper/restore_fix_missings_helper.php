<?php
/**
 *  Sharing Cart
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: restore_fix_missings_helper.php 882 2012-11-01 05:06:21Z malu $
 */

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../../moodle2/restore_root_task_fix_missings.php';

/**
 *  The helper class that fixes restore plan
 */
final class restore_fix_missings_helper
{
    /**
     *  Fixes a restore plan to perform a workaround for question bank restore issue
     *  
     *  @param restore_plan $plan
     */
    public static function fix_plan(restore_plan $plan)
    {
        // replaces an existing restore_root_task with a restore_root_task_fix_missings
        $tasks = $plan->get_tasks();
        foreach ($tasks as $i => $task) {
            if ($task instanceof restore_root_task) {
                $task = new restore_root_task_fix_missings('root_task');
                // since the task settings already defined by restore_root_task,
                // we need to inject the plan instead of calling set_plan(),
                // to avoid 'error/multiple_settings_by_name_found' error
                self::set_protected_property($task, 'plan', $plan);
                $tasks[$i] = $task;
                break;
            }
        }
        self::set_protected_property($plan, 'tasks', $tasks);
    }

    /**
     *  Sets a protected/private property
     *  
     *  @param object $obj
     *  @param string $prop
     *  @param mixed $value
     */
    private static function set_protected_property($obj, $prop, $value)
    {
        $reflector = new ReflectionProperty(get_class($obj), $prop);
        $reflector->setAccessible(true);
        $reflector->setValue($obj, $value);
    }
}
