<?php

namespace block_sharing_cart\app\moodle;

use block_sharing_cart\app\factory as base_factory;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

global $CFG;
require_once $CFG->dirroot . '/user/profile/lib.php';

class factory
{

    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    public function page(): \moodle_page
    {
        global $PAGE;
        return $PAGE;
    }

    public function db(): \moodle_database
    {
        global $DB;
        return $DB;
    }

    public function output(): mixed
    {
        global $OUTPUT;
        return $OUTPUT;
    }


    public function cfg(): object
    {
        global $CFG;
        return $CFG;
    }

    public function script(): string
    {
        global $SCRIPT;
        return $SCRIPT;
    }

    public function session(): object
    {
        global $SESSION;
        return $SESSION;
    }

    public function user(): object
    {
        global $USER;
        return $USER;
    }

    public function course(): object
    {
        global $COURSE;
        return $COURSE;
    }
}