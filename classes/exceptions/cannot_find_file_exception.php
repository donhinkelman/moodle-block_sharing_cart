<?php
/**
 * @package     block_sharing_cart\exceptions
 * @author      Sam Møller
 */

namespace block_sharing_cart\exceptions;


defined('MOODLE_INTERNAL') || die();

class cannot_find_file_exception extends \coding_exception
{
    /** @var string */
    private $filename;

    /** @var int */
    private $context_id;

    /** @var int */
    private $item_id;

    /** @var string */
    private $component;

    /** @var string|null */
    private $filearea;

    /** @var string|null */
    private $filepath;

    public function __construct(
        string $filename,
        int $context_id = 1,
        int $item_id = 0,
        string $component = 'user',
        ?string $filearea = null,
        ?string $filepath = null
    ) {
        $this->filename = $filename;
        $this->context_id = $context_id;
        $this->item_id = $item_id;
        $this->component = $component;
        $this->filearea = $filearea;
        $this->filepath = $filepath;

        parent::__construct(
            "Cannot find file: $filename",
            $this->get_file_info()
        );
    }

    /**
     * @return string
     */
    private function get_file_info(): string {
        return "File record as JSON: " . json_encode(
            $this->get_file_record(),
            JSON_PRETTY_PRINT
            );
    }

    /**
     * @return object
     */
    private function get_file_record(): object {
        return (object)[
            'filename' => $this->filename,
            'component' => $this->component,
            'contextid' => $this->context_id,
            'itemid' => $this->item_id,
            'filearea' => $this->filearea,
            'filepath' => $this->filepath,
        ];
    }

}
