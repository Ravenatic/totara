<?php

require_once('../config.php');

require_once($CFG->dirroot.'/local/reportbuilder/lib.php');

$format = optional_param('format','', PARAM_TEXT); // export format


$strheading = get_string('findcourses', 'local');
$shortname = 'findcourses';
$embed = new object();
$embed->source = 'courses';
$embed->fullname = $strheading;
$embed->filters = array(
    array(
        'type' => 'course',
        'value' => 'fullname',
        'advanced' => 0,
    ),
    array(
        'type' => 'course_category',
        'value' => 'id',
        'advanced' => 1,
    ),
    array(
        'type' => 'course',
        'value' => 'startdate',
        'advanced' => 1,
    ),
);
$embed->columns = array(
    array(
        'type' => 'course',
        'value' => 'courselinkicon',
        'heading' => 'Course Name',
    ),
    array(
        'type' => 'course_category',
        'value' => 'namelinkicon',
        'heading' => 'Category',
    ),
    array(
        'type' => 'course',
        'value' => 'startdate',
        'heading' => 'Start date',
    ),
    array(
        'type' => 'course',
        'value' => 'mods',
        'heading' => 'Content',
    ),
);

$embed->contentmode = 0; // no restrictions

// hide hidden courses using embedded params
$embed->embeddedparams = array(
    'visible' => 1,
);

$report = new reportbuilder(null, $shortname, $embed);

if($format!='') {
    $report->export_data($format);
    die;
}
$report->include_js();

$fullname = $report->fullname;
$pagetitle = format_string(get_string('report','local').': '.$fullname);
$navlinks[] = array('name' => $fullname, 'link'=> '', 'type'=>'title');

$navigation = build_navigation($navlinks);

print_header_simple($pagetitle, '', $navigation, '', null, true, null);

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

// display heading including filtering stats
if($countfiltered == $countall) {
    print_heading("$strheading: $countall results shown");
} else {
    print_heading("$strheading: $countfiltered/$countall results shown");
}

print $report->print_description();

$report->display_search();

// print saved search buttons if appropriate
print '<table align="right" border="0"><tr><td>';
print $report->save_button();
print '</td><td>';
print $report->view_saved_menu();
print '</td></tr></table>';
print "<br /><br />";

if($countfiltered>0) {
    print $report->showhide_button();
    $report->display_table();
    print $report->edit_button();
    // export button
    $report->export_select();
}

print_footer();

?>