<?php
// This file is part of local_automatic_badges - https://moodle.org/.
//
// local_automatic_badges is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// local_automatic_badges is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with local_automatic_badges.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Hook callbacks for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_automatic_badges;

class hook_callbacks {
    /**
     * Inject a "Return to Automatic Badges" button on Moodle badge pages.
     *
     * @param \core\hook\output\before_footer_html_generation $hook The hook object.
     */
    public static function before_footer(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE;

        // Only act on badge pages within a course context.
        $pagepath = $PAGE->url->get_path();
        if (strpos($pagepath, '/badges/') === false) {
            return;
        }

        $courseid = $PAGE->url->get_param('courseid');
        if (!$courseid && $PAGE->context && $PAGE->context->contextlevel == CONTEXT_COURSE) {
            $courseid = $PAGE->context->instanceid;
        }
        if (!$courseid) {
            return;
        }

        $returnurl = new \moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'tab' => 'badges']);

        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                var returnUrl = '" . $returnurl->out(false) . "';
                var actionArea = $('#fitem_id_buttonar, .form-group .fsubmit, [data-groupname=\"buttonar\"]');

                if (actionArea.length) {
                    var backBtn = $('<a>')
                        .attr('href', returnUrl)
                        .addClass('btn btn-outline-secondary ml-2')
                        .html('<i class=\"fa fa-arrow-left mr-1\"></i> Volver a Automatic Badges');
                    actionArea.find('.form-inline, .felement, .fdefaultcustom, div').last().append(backBtn);
                }

                var heading = $('#region-main h2, #region-main .h2').first();
                if (heading.length) {
                    var topLink = $('<a>')
                        .attr('href', returnUrl)
                        .addClass('btn btn-sm btn-outline-secondary mb-3 d-inline-block')
                        .html('<i class=\"fa fa-arrow-left mr-1\"></i> Volver a Automatic Badges');
                    heading.before(topLink);
                }
            });
        ");
    }
}
