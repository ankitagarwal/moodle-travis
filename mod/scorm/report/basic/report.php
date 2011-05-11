<?php

class scorm_basic_report extends scorm_default_report {
    /**
     * Displays the report.
     */
    function display($scorm, $cm, $course) {
	echo "Basic reporting";
	return 1;
	}
}