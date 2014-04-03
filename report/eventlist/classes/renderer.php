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
 * Event report renderer.
 *
 * @package    report_eventlist
 * @copyright  2014 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for event report.
 *
 * @package    report_eventlist
 * @copyright  2014 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_eventlist_renderer extends plugin_renderer_base {

    /**
     * Renders the event list page with filter form and datatable.
     *
     * @param eventfilter_form $form Event filter form.
     * @param array $tabledata An array of event data to be used by the datatable.
     * @return string HTML to be displayed.
     */
    public function render_event_list($form, $tabledata) {
        global $PAGE;

        $title = get_string('pluginname', 'report_eventlist');

        // Header.
        $html = $this->output->header();
        $html .= $this->output->heading($title);

        // Form.
        ob_start();
        $form->display();
        $html .= ob_get_contents();
        ob_end_clean();

        $PAGE->requires->yui_module('moodle-report_eventlist-eventfilter', 'Y.M.report_eventlist.EventFilter.init',
                array(array('tabledata' => $tabledata)));
        $PAGE->requires->strings_for_js(array(
            'eventname',
            'component',
            'action',
            'crud',
            'edulevel',
            'objecttable',
            'dname',
            'devdetail'
            ), 'report_eventlist');
        $html .= html_writer::start_div('path-admin-tool-eventlist-data-table', array('id' => 'path-admin-tool-eventlist-table'));
        $html .= html_writer::end_div();

        $html .= $this->output->footer();
        return $html;
    }

    /**
     * Event detail renderer.
     *
     * @param array $observerlist A list of observers that consume this event.
     * @param array $eventinformation A list of information about the event.
     * @return string HTML to be displayed.
     */
    public function render_event_detail($observerlist, $eventinformation) {
        global $PAGE;

        $PAGE->requires->yui_module('moodle-report_eventlist-sectionhide', 'Y.M.report_eventlist.SectionHide.init');

        $titlehtml = $this->output->header();
        $titlehtml .= $this->output->heading($eventinformation['title']);

        $html = null;

        if (isset($eventinformation['crud'])) {
            $html .= html_writer::span(get_string('databasequerytype', 'report_eventlist'), 'path-admin-tool-eventlist-detail-label');
            $html .= html_writer::span($eventinformation['crud']);
            $html .= html_writer::empty_tag('br');
        }

        if (isset($eventinformation['edulevel'])) {
            $html .= html_writer::span(get_string('educationlevel', 'report_eventlist'), 'path-admin-tool-eventlist-detail-label');
            $html .= html_writer::span($eventinformation['edulevel']);
            $html .= html_writer::empty_tag('br');
        }

        if (isset($eventinformation['objecttable'])) {
            $html .= html_writer::span(get_string('affectedtable', 'report_eventlist'), 'path-admin-tool-eventlist-detail-label');
            $html .= html_writer::span($eventinformation['objecttable']);
            $html .= html_writer::empty_tag('br');
        }

        if (isset($eventinformation['parentclass'])) {
            $url = new moodle_url('eventdetail.php', array('eventname' => $eventinformation['parentclass']));
            $html .= html_writer::span(get_string('parentevent', 'report_eventlist'), 'path-admin-tool-eventlist-detail-label');
            $html .= html_writer::link($url, $eventinformation['parentclass']);
        }

        // Other information.
        $html .= html_writer::empty_tag('br');
        $html .= html_writer::span(get_string('otherinformation', 'report_eventlist'), 'path-admin-tool-eventlist-detail-label');
        $html .= html_writer::empty_tag('br');
        $html .= html_writer::start_div();

        if (isset($eventinformation['abstract'])) {
            $html .= html_writer::span(get_string('abstractclass', 'report_eventlist'));
            $html .= html_writer::empty_tag('br');
        }

        if (isset($eventinformation['typeparameter'])) {
            $html .= html_writer::span(get_string('typedeclaration', 'report_eventlist'), 'path-admin-tool-eventlist-type-dec');
            $html .= html_writer::empty_tag('br');
            foreach ($eventinformation['typeparameter'] as $typeparameter) {
                $html .= html_writer::span($typeparameter, 'path-admin-tool-eventlist-type-parameter');
                $html .= html_writer::empty_tag('br');
            }
        }

        if (isset($eventinformation['otherparameter'])) {
            $html .= html_writer::span(get_string('othereventparameters', 'report_eventlist'), 'path-admin-tool-eventlist-detail-label');
            $html .= html_writer::empty_tag('br');
            foreach ($eventinformation['otherparameter'] as $otherparameter) {
                $html .= html_writer::span($otherparameter, 'path-admin-tool-eventlist-type-parameter');
                $html .= html_writer::empty_tag('br');
            }
        }

        // List observers consuming this event if there are any.
        if (!empty($observerlist)) {
            $html .= html_writer::span(get_string('relatedobservers', 'report_eventlist'), 'path-admin-tool-eventlist-detail-label');
            $html .= html_writer::empty_tag('br');
            $html .= html_writer::start_tag('pre');
            $html .= var_export($observerlist, true);
            $html .= html_writer::end_tag('pre');

            $html .= html_writer::empty_tag('br');
        }
        $html .= html_writer::end_div();

        $html .= html_writer::empty_tag('br');
        $html .= html_writer::link('#', get_string('eventcode', 'report_eventlist'), array('id' => 'event-show-more'));
        $html .= html_writer::empty_tag('br');
        $html .= html_writer::empty_tag('br');
        $html .= html_writer::start_div('path-admin-tool-eventlist-code-hidden', array('id' => 'id_event-code'));
        $html .= html_writer::start_tag('pre', array('id' => 'code'));
        $html .= $eventinformation['filecontents'];
        $html .= html_writer::end_tag('pre');
        $html .= html_writer::end_div();

        $pagecontent = new html_table();
        $pagecontent->data = array(array($html));
        $pagehtml = $titlehtml . html_writer::table($pagecontent);
        $pagehtml .= $this->output->footer();

        return $pagehtml;
    }
}
