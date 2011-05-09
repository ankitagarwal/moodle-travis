<?php

    if (!defined('MOODLE_INTERNAL')) {
        echo "am gng to die";
		die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }
    if (has_capability('mod/scorm:test', $context)) {
            echo '<p>';
            echo '<a href="'.$CFG->wwwroot.'/mod/scorm/report.php?mode=test&id='.$id.'">TEST</a>';
            echo '</p>';
    }