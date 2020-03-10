<?php

namespace block_sharing_cart\exceptions;

/**
 * Class no_backup_support_exception
 *
 * @package block_sharing_cart\exceptions
 */
class no_backup_support_exception extends \moodle_exception {

    /**
     *  Constructor
     *
     * @param $errorcode
     * @param null $debuginfo
     */
    public function __construct($errorcode, $debuginfo = null) {
        parent::__construct($errorcode, 'block_sharing_cart', '', null, $debuginfo);
    }
}
