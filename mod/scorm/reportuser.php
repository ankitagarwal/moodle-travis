 <?php
 
 // Includes and parameters from old scorm report file

    require_once("../../config.php");
    require_once($CFG->dirroot.'/mod/scorm/report/reportlib.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->dirroot.'/mod/scorm/locallib.php');
    require_once($CFG->dirroot.'/mod/scorm/reportsettings_form.php');
    require_once($CFG->libdir.'/formslib.php');

    $id = optional_param('id', '', PARAM_INT);    // Course Module ID, or
    $a = optional_param('a', '', PARAM_INT);     // SCORM ID
    $b = optional_param('b', '', PARAM_INT);     // SCO ID
    $user = optional_param('user', '', PARAM_INT);  // User ID
    $attempt = optional_param('attempt', '1', PARAM_INT);  // attempt number
    $action     = optional_param('action', '', PARAM_ALPHA);
    $attemptids = optional_param('attemptid', array(), PARAM_RAW);
    
// Building the url to use for links.+ data details buildup
    $url = new moodle_url('/mod/scorm/report.php');
    if ($user !== '') {
        $url->param('user', $user);
    }
    if ($attempt !== '1') {
        $url->param('attempt', $attempt);
    }
    if ($action !== '') {
        $url->param('action', $action);
    }

    if (!empty($id)) {
        $url->param('id', $id);
        if (! $cm = get_coursemodule_from_id('scorm', $id)) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
            print_error('coursemisconf');
        }
        if (! $scorm = $DB->get_record('scorm', array('id'=>$cm->instance))) {
            print_error('invalidcoursemodule');
        }
    } else {
        if (!empty($b)) {
            $url->param('b', $b);
            if (! $sco = $DB->get_record('scorm_scoes', array('id'=>$b))) {
                print_error('invalidactivity', 'scorm');
            }
            $a = $sco->scorm;
        }
        if (!empty($a)) {
            $url->param('a', $a);
            if (! $scorm = $DB->get_record('scorm', array('id'=>$a))) {
                print_error('invalidcoursemodule');
            }
            if (! $course = $DB->get_record('course', array('id'=>$scorm->course))) {
                print_error('coursemisconf');
            }
            if (! $cm = get_coursemodule_from_instance('scorm', $scorm->id, $course->id)) {
                print_error('invalidcoursemodule');
            }
        }
    }
    $PAGE->set_url($url);
//END of url setting + data buildup
    
// checking login +logging +getting context
    require_login($course->id, false, $cm);
    $contextmodule= get_context_instance(CONTEXT_MODULE,$cm->id);
    add_to_log($course->id, 'scorm', 'userreport', 'reportuser.php?id='.$cm->id, $scorm->id, $cm->id);
    if (!empty($user)) {
        $userdata = scorm_get_user_data($user);
    } else {
        $userdata = null;
    }
// END of checking login +logging +getting context
// Print the page header
if (empty($noheader)) {
    $strscorms = get_string('modulenameplural', 'scorm');
    $strscorm  = get_string('modulename', 'scorm');
    $strreport  = get_string('report', 'scorm');
    $strattempt  = get_string('attempt', 'scorm');
    $strname  = get_string('name');

    $PAGE->set_title("$course->shortname: ".format_string($scorm->name));
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strreport, new moodle_url('/mod/scorm/report.php', array('id'=>$cm->id)));

    if (empty($b)) {
        if (!empty($a)) {
            $PAGE->navbar->add("$strattempt $attempt - ".fullname($userdata));
        }
    } else {
        $PAGE->navbar->add("$strattempt $attempt - ".fullname($userdata), new moodle_url('/mod/scorm/report.php', array('a'=>$a, 'user'=>$user, 'attempt'=>$attempt)));
        $PAGE->navbar->add($sco->title);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($scorm->name));
}
// End of Print the page header

//Capabality Check + Can be moded to add ability for sudends to check there own attempts
require_capability('mod/scorm:viewreports', $contextmodule);

// User SCORM report
    if (!empty($user)) {
        if ($scoes = $DB->get_records_select('scorm_scoes',"scorm=? ORDER BY id", array($scorm->id))) {
            if (!empty($userdata)) {
                echo $OUTPUT->box_start('generalbox boxaligncenter');
                echo '<div class="mdl-align">'."\n";
                echo $OUTPUT->user_picture($userdata, array('courseid'=>$course->id));
                echo "<a href=\"$CFG->wwwroot/user/view.php?id=$user&amp;course=$course->id\">".
     				"$userdata->firstname $userdata->lastname</a><br />";
                echo get_string('attempt','scorm').': '.$attempt;
                echo '</div>'."\n";
                echo $OUTPUT->box_end();

                // Print general score data
                $table = new html_table();
                $table->head = array(get_string('title','scorm'),
                                     get_string('status','scorm'),
                                     get_string('time','scorm'),
                                     get_string('score','scorm'),
                					'');
                $table->align = array('left', 'center','center','right','left');
                $table->wrap = array('nowrap', 'nowrap','nowrap','nowrap','nowrap');
                $table->width = '80%';
                $table->size = array('*', '*', '*', '*', '*');
                foreach ($scoes as $sco) {
                    if ($sco->launch!='') {
                        $row = array();
                        $score = '&nbsp;';
                        if ($trackdata = scorm_get_tracks($sco->id,$user,$attempt)) {
                            if ($trackdata->score_raw != '') {
                                $score = $trackdata->score_raw;
                            }
                            if ($trackdata->status == '') {
                                $trackdata->status = 'notattempted';
                            }
                        } else {
                            $trackdata->status = 'notattempted';
                            $trackdata->total_time = '&nbsp;';
                            $detailslink = '&nbsp;';
                        }
                        $strstatus = get_string($trackdata->status,'scorm');
                        $row[] = '<img src="'.$OUTPUT->pix_url($trackdata->status, 'scorm').'" alt="'.$strstatus.'" title="'.
                        $strstatus.'" />&nbsp;'.format_string($sco->title);
                        $row[] = get_string($trackdata->status,'scorm');
                        $row[] = scorm_format_duration($trackdata->total_time);
                        $row[] = $score;
                    } else {
                        $row = array(format_string($sco->title), '&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;');
                    }
                    $table->data[] = $row;
                }
                echo html_writer::table($table);
            }
        }
    }
    
// Print footer

    echo $OUTPUT->footer();