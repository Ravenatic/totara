<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package totara
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/totara/plan/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');


//
// Load parameters
//
$id = required_param('id', PARAM_INT); // plan id
$componentname = required_param('c', PARAM_ALPHA); // component type
$submitted = optional_param('submitbutton', null, PARAM_TEXT); // form submitted
$ajax = optional_param('ajax', false, PARAM_BOOL); // ajax call

require_login();

//
// Load plan, component and check permissions
//
$plan = new development_plan($id);

$systemcontext = get_system_context();
if(!has_capability('totara/plan:accessanyplan', $systemcontext) && ($plan->get_setting('view') < DP_PERMISSION_ALLOW)) {
        print_error('error:nopermissions', 'local_plan');
}


// Check for valid component, before proceeding
// Check against active components to prevent hackery
if (!in_array($componentname, array_keys($plan->get_components()))) {
    print_error('error:invalidcomponent', 'local_plan');
}

$component = $plan->get_component($componentname);


//
// Perform actions
//
if ($submitted && confirm_sesskey()) {
    $component->process_settings_update($ajax);

    // If ajax, no need to recreate entire page
    if ($ajax) {
        // Print list and message box so page can be updated
        echo $component->display_list();
        echo $plan->display_plan_message_box();
        die();
    }
}

$component->process_action_hook();


//
// Display header
//
$component->setup_picker();

$navlinks = array(
    array('name' => get_string($component->component.'plural', 'local_plan'), 'link' => '', 'type' => 'title')
);
$plan->print_header($componentname, $navlinks);

print $component->display_picker();

print '<form id="dp-component-update" action="'.$component->get_url().'" method="POST">';
print '<input type="hidden" id="sesskey" name="sesskey" value="'.sesskey().'" />';

print '<div id="dp-component-update-table">'.$component->display_list().'</div>';

if ($component->can_update_settings(false)) {
    if (!$component->get_assigned_items()) {
        $display = 'none';
    } else {
        $display = 'block';
    }

    print '<noscript><div id="dp-component-update-submit" style="display: '.$display.';"><input type="submit" name="submitbutton" value="'.get_string('updatesettings', 'local_plan').'" /></div></noscript>';
}

print '</form>';
print_container_end();

echo build_datepicker_js("[id^=duedate_{$componentname}]", true);

print_footer();