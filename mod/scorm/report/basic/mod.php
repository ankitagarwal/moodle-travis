<?php

    if (!defined('MOODLE_INTERNAL')) {
		die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }
    if (has_capability('mod/scorm:viewreports', $contextmodule)) {
            echo '<p>';
            echo '<a href="'.$CFG->wwwroot.'/mod/scorm/report.php?mode=basic&id='.$id.'">'.get_string('basic','scorm_basic').'</a>';
            echo '</p>';
    }