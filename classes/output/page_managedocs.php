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
 * Provides {@link tool_policy\output\renderer} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;

use tool_policy\api;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use renderable;
use renderer_base;
use single_button;
use templatable;

/**
 * Renderer for the policies plugin.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_managedocs implements renderable, templatable {

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = (object) [];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(true);
        $data->navigation = [
            $output->single_button(
                new moodle_url('/admin/tool/policy/editpolicydoc.php'),
                get_string('addpolicydocument', 'tool_policy'),
                'get'
            ),
        ];

        $policies = api::list_policies();

        // All versions older than the current one are considered as archived. All
        // versions newer than the current one are considered as drafts.

        foreach ($policies as $policy) {
            $policy->currentversiontimecreated = null;
            $policy->actions = [];
            foreach ($policy->versions as $version) {
                srand(); // TODO this will be actually fetched via the API
                $version->usersaccepted = rand(0, 100).' %';

                if ($version->id == $policy->currentversionid) {
                    $policy->currentversiontimecreated = $version->timecreated;
                    $policy->usersaccepted = $version->usersaccepted;
                }
            }
        }

        foreach ($policies as $policy) {
            foreach ($policy->versions as $version) {
                if ($version->id == $policy->currentversionid) {
                    $version->iscurrent = true;

                } else if ($version->timecreated <= $policy->currentversiontimecreated) {
                    $version->isarchive = true;

                } else {
                    $version->isdraft = true;
                    $version->actions[] = $output->single_button(
                        new moodle_url('/admin/tool/policy/editpolicydoc.php', [
                            'policyid' => $policy->id,
                            'makecurrent' => $version->id,
                        ]),
                        get_string($policy->currentversionid ? 'makecurrent' : 'makeactive', 'tool_policy')
                    );
                }

                if (!empty($version->iscurrent) || !empty($version->isdraft)) {
                    $version->actions[] = $output->single_button(
                        new moodle_url('/admin/tool/policy/editpolicydoc.php', [
                            'policyid' => $policy->id,
                            'versionid' => $version->id,
                        ]),
                        get_string('edit')
                    );
                }
            }
        }

        $data->policies = array_values($policies);

        return $data;
    }
}
