<?php

namespace block_sharing_cart;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../lib/formslib.php');

class section_title_form extends \moodleform {
    /** @var array */
    private $sections;

    /** @var bool */
    private $directory;

    /** @var string */
    private $path;

    /** @var int */
    private $courseid;

    /** @var int */
    private $sectionnumber;

    /** @var int */
    private $items_count;

    /**
     * section_title_form constructor.
     *
     * @param bool $directory
     * @param string $path
     * @param int $courseid
     * @param int $sectionnumber
     * @param array $eligible_sections
     * @param int $items_count
     */
    public function __construct(bool $directory, string $path, int $courseid, int $sectionnumber, array $eligible_sections, int $items_count = 0) {
        $this->directory = $directory;
        $this->path = $path;
        $this->courseid = $courseid;
        $this->sectionnumber = $sectionnumber;
        $this->sections = $eligible_sections;
        $this->items_count = $items_count;
        parent::__construct();
    }

    public function definition(): void {
        $current_section_name = get_section_name($this->courseid, $this->sectionnumber);

        $mform =& $this->_form;

        if ($this->items_count > 9) {
            $mform->addElement('static', 'restore_heavy_load_warning_message', '',
                '<p class="alert alert-danger" role="alert">
                '.
                get_string('restore_heavy_load_warning_message', 'block_sharing_cart')
                .'
                </p>');
        }

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
