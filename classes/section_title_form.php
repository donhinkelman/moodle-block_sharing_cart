<?php

namespace block_sharing_cart;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../lib/formslib.php');

class section_title_form extends \moodleform {
    /**
     * @var array $sections
     */
    private $sections;
    /**
     * @var string $directory
     */
    private $directory;
    /**
     * @var string $path
     */
    private $path;
    /**
     * @var string $courseid
     */
    private $courseid;
    /**
     * @var string $sectionnumber
     */
    private $sectionnumber;

    /**
     * section_title_form constructor.
     *
     * @param array $eligible_sections
     */
    public function __construct($directory, $path, $courseid, $sectionnumber, $eligible_sections) {
        $this->directory = $directory;
        $this->path = $path;
        $this->courseid = $courseid;
        $this->sectionnumber = $sectionnumber;
        $this->sections = $eligible_sections;
        parent::__construct();
    }

    public function definition() {
        global $PAGE, $USER, $DB;

        $current_section_name = get_section_name($this->courseid, $this->sectionnumber);

        $mform =& $this->_form;

        $mform->addElement('static', 'description', '', get_string('conflict_description', 'block_sharing_cart'));

        $mform->addElement('radio', 'sharing_cart_section',
                get_string('conflict_no_overwrite', 'block_sharing_cart', $current_section_name), null, 0);
        foreach ($this->sections as $section) {
            $option_title = get_string('conflict_overwrite_title', 'block_sharing_cart', $section->name);
            if ($section->summary != null) {
                $option_title .= '<br><div class="small"><strong>' . get_string('summary') . ':</strong> ' .
                        strip_tags($section->summary) . '</div>';
            }

            $mform->addElement('radio', 'sharing_cart_section', $option_title, null, $section->id);
        }
        $mform->setDefault('section_title', 0);
        $mform->addElement('hidden', 'directory', $this->directory);
        $mform->setType('directory', PARAM_BOOL);
        $mform->addElement('hidden', 'path', $this->path);
        $mform->setType('path', PARAM_TEXT);
        $mform->addElement('hidden', 'course', $this->courseid);
        $mform->setType('course', PARAM_INT);
        $mform->addElement('hidden', 'section', $this->sectionnumber);
        $mform->setType('section', PARAM_INT);

        $mform->addElement('static', 'description_note', '',
                '<div class="small">' . get_string('conflict_description_note', 'block_sharing_cart') . '</div>');

        $this->add_action_buttons(true, get_string('conflict_submit', 'block_sharing_cart'));
    }
}
