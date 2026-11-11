<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat tests for the Sharing Cart block.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_sharing_cart extends behat_base
{
    /**
     *
     * @Given /^I enable the sharing cart plugin$/
     */
    public function enable_sharing_cart_plugin() {
        $this->get_selected_node("xpath_element", "//a[@data-key='addblock']")->click();
        // Wait until the add block menu is ready.
        $this->get_selected_node("xpath_element", "//a[@data-blockname='sharing_cart']")->click();
    }

    /**
     * Queues a section backup for a restore scenario; the browser flow under test starts at the import dialog.
     *
     * @When /^I queue section number "(?P<sectionnumber>\d+)" in the Sharing Cart$/
     * @param int $sectionnumber
     * @return void
     */
    public function queue_section_in_sharing_cart(int $sectionnumber): void {
        $section = $this->get_selected_node(
            'css_element',
            '.course-content li[data-for="section"][data-number="' . $sectionnumber . '"]'
        );

        \block_sharing_cart\external\backup\section_into_sharing_cart::execute(
            (int)$section->getAttribute('data-id'),
            ['users' => false, 'anonymize' => false]
        );
    }
}
