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
 * Log live report renderer.
 *
 * @package    report_loglive
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Log live report renderer.
 *
 * @package    report_loglive
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_loglive_renderer extends plugin_renderer_base {

    /**
     * Render log report page.
     *
     * @param report_loglive_renderable $reportlog object of report_log.
     */
    public function render_report_loglive_renderable(report_loglive_renderable $reportlog) {
        global $OUTPUT;
        if (empty($reportlog->selectedreader)) {
            echo $OUTPUT->notification(get_string('noreaderenabled', 'moodle'), 'notifyproblem');
            return;
        }
        $this->log_report($reportlog);
    }

    /**
     * Print report log.
     *
     * @param report_loglive_renderable $reportlog object of report_log.
     */
    public function log_report(report_loglive_renderable $reportlog) {
        // Create table.
        $table = $this->define_table($reportlog);
        $table->setup();

        $logdata = $reportlog->get_logs();

        // Display total record count and paging bar.
        echo html_writer::tag('div', get_string("displayingrecords", "", $logdata['totalcount']), array('class' => 'info'));
        echo $this->output->paging_bar($logdata['totalcount'], $reportlog->page, $reportlog->perpage, $reportlog->url.
                "&perpage=" . $reportlog->perpage);

        // Print log data.
        foreach ($logdata['events'] as $row) {
            $table->add_data($row);
        }

        $table->finish_output();
        echo $this->output->paging_bar($logdata['totalcount'], $reportlog->page, $reportlog->perpage, $reportlog->url. "&perpage=" .
                $reportlog->perpage);
    }

    /**
     * Setup a flexible table.
     *
     * @param report_loglive_renderable $reportlog
     * @param array table attributes if any
     * @return flexible_table
     */
    protected function define_table(report_loglive_renderable $reportlog, $attr = array()) {
        $table = new \flexible_table('reportlog');
        $tablefields = $reportlog->get_table_fields();
        $table->define_baseurl($reportlog->url);
        foreach ($attr as $key => $value) {
            $table->set_attribute($key, $value);
        }
        $table->define_columns(array_keys($tablefields));
        $table->define_headers(array_values($tablefields));
        $table->sortable(false);
        $table->collapsible(false);
        $table->pageable(true);
        return $table;
    }
}