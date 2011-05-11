<?php

////////////////////////////////////////////////////////////////////
/// Default class for Scorm plugins
///
/// Doesn't do anything on it's own -- it needs to be extended.
/// This class displays scorm reports.  Because it is called from
/// within /mod/scorm/report.php you can assume that the page header
/// and footer are taken care of.
///
/// This file can refer to itself as report.php to pass variables
/// to itself - all these will also be globally available.
////////////////////////////////////////////////////////////////////

// Included by ../report.php

class scorm_default_report {

    function display($cm, $course, $quiz) {     /// This function just displays the report
        return true;
    }
    function settings($cm, $course, $quiz) {     /// This function just displays the settings
        return true;
    }
}


