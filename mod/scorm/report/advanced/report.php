<?php

class scorm_basic_report extends scorm_default_report {
    /**
     * Displays the report.
     */
    function display($scorm, $cm, $course, $a, $b, $attempt, $action, $download) {
        global $CFG,$DB,$OUTPUT;
        $contextmodule= get_context_instance(CONTEXT_MODULE,$cm->id);
        
        echo "ADVANCED REPORTING";
        return 1;
    }
}