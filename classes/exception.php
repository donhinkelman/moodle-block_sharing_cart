<?php
/**
 *  Sharing Cart
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: exception.php 776 2012-09-05 10:16:15Z malu $
 */
namespace sharing_cart;

/**
 *  Sharing Cart exception
 */
class exception extends \moodle_exception
{
    /**
     *  Constructor
     *  
     *  @param string $errcode  The error string ID
     *  @param mixed  $a        (Optional) Additional parameter
     */
    public function __construct($errcode, $a = null)
    {
        parent::__construct($errcode, 'block_sharing_cart', '', $a);
    }
}
