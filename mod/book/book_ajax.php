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
 * Ajax responder page.
 *
 * @package    report_loglive
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once('locallib.php');

$bookid = required_param('id', PARAM_INT);
$key = required_param('sesskey', PARAM_ALPHANUM);

//confirm_sesskey($key);

$cm = get_coursemodule_from_instance('book', $bookid, 0, false, MUST_EXIST);
$book = $DB->get_record('book', array('id' => $bookid), '*', MUST_EXIST);

// Capability checks.
require_login($cm->course);
$context = context_module::instance($cm->id);
//require_capability('report/loglive:view', $context);
$chapters = book_preload_chapters($book);
$contents = $DB->get_records('book_chapters', array('bookid' => $bookid));
$fullchapters = array();
$topchapters = array();
foreach ($chapters as $chapter) {
    $fullchapters[$chapter->pagenum] = $chapter;
    $fullchapters[$chapter->pagenum]->title = book_get_chapter_title($chapter->id, $chapters, $book, $context);
    $chaptertext = file_rewrite_pluginfile_urls($contents[$chapter->id]->content, 'pluginfile.php', $context->id,
        'mod_book', 'chapter', $chapter->id);
    $fullchapters[$chapter->pagenum]->content = format_text($chaptertext, $contents[$chapter->id]->contentformat,
            array('noclean' => true, 'overflowdiv' => true, 'context' => $context));
    if (!$chapter->subchapter) {
        $topchapters[$chapter->id] = $chapter->id;
    }
}
echo json_encode(array('toc' => $topchapters, 'chapters' => $chapters));