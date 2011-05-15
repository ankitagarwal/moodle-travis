<?php

    if (!defined('MOODLE_INTERNAL')) {
		die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }
    if (has_capability('mod/scorm:viewreports', $contextmodule)) {
            echo '<p>';
            echo '<a href="'.$CFG->wwwroot.'/mod/scorm/report.php?mode=advanced&id='.$id.'">'.get_string('advanced','scorm_advanced').'</a>';
            echo '</p>';
    }