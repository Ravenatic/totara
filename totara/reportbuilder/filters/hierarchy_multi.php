<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 - 2012 Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');

/**
 * Generic filter based on selecting multiple items from a hierarchy.
 */
class filter_hierarchy_multi extends filter_type {

    /**
     * Hierarchy type
     * Refers to the name of the main table e.g. 'pos', 'org' or 'comp'
     */
    var $_type;

    /**
     * Constructor
     * @param object $filter rb_filter object for this filter
     * @param string $sessionname Unique name for the report for storing sessions
     */
    function __construct($filter, $sessionname, $type) {
        // hierarchy type
        $this->_type = substr($type, 0, -5); // strip off 'multi'
        parent::filter_type($filter, $sessionname);
    }

    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    function get_operators() {
        return array(0 => get_string('isanyvalue', 'filters'),
                     1 => get_string('isequalto', 'filters'),
                     2 => get_string('isnotequalto', 'filters'));
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform) {
        global $SESSION;
        $sessionname = $this->_sessionname;
        $label = $this->_filter->label;
        $advanced = $this->_filter->advanced;
        $type = $this->_type;

        // container for currently selected items
        $content = html_writer::empty_tag('div', array('class' => 'list-' . $this->_name )) . display_choose_hierarchy_items_link($this->_name, $type);
        $mform->addElement('static', $this->_name.'_list', $label, $content);

        if ($advanced) {
            $mform->setAdvanced($this->_name.'_grp');
        }

        $mform->addElement('hidden', $this->_name);
        $mform->setType($this->_name, PARAM_SEQUENCE);

        if (array_key_exists($this->_name, $SESSION->{$sessionname})) {
            $defaults = $SESSION->{$sessionname}[$this->_name];
        }

        if (isset($defaults[0]['value'])) {
            $mform->setDefault($this->_name, $defaults[0]['value']);
        }

    }

    function definition_after_data(&$mform) {
        global $DB;

        if ($ids = $mform->getElementValue($this->_name)) {
            list($isql, $iparams) = $DB->get_in_or_equal(explode(',', $ids));
            $items = $DB->get_records_select($this->_type, "id {$isql}", $iparams);
            if (!empty($items)) {
                $out = html_writer::start_tag('div', array('class' => 'list-' . $this->_name ));
                foreach ($items as $item) {
                    $out .= display_selected_hierarchy_item($item, $this->_name);
                }
                $out .= html_writer::end_tag('div');

                // link to add items
                $out .= display_choose_hierarchy_items_link($this->_name, $this->_type);

                $mform->setDefault($this->_name.'_list', $out);
            }
        }

    }


    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        $field    = $this->_name;

        if (array_key_exists($field, $formdata) && !empty($formdata->$field) ) {
            return array('value'    => $formdata->$field);
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where
     *
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {
        global $DB;

        $items    = explode(',', $data['value']);
        $query    = $this->_filter->get_field();

        // don't filter if none selected
        if (empty($items)) {
            // return 1=1 instead of TRUE for MSSQL support
            return array(' 1=1 ', array());
        }
        list($insql, $inparams) = $DB->get_in_or_equal($items, SQL_PARAMS_NAMED,
            rb_unique_param('fhm').'_');

        return array("{$query} {$insql}", $inparams);
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data) {
        global $DB;

        $value     = explode(',', $data['value']);
        $label = $this->_filter->label;

        if (empty($value)) {
            return '';
        }

        $a = new stdClass();
        $a->label    = $label;

        $selected = array();
        list($isql, $iparams) = $DB->get_in_or_equal($value);
        $items = $DB->get_records_select($this->_type, "id {$isql}", $params);
        foreach ($items as $item) {
            $selected[] = '"' . format_string($item->fullname) . '"';
        }

        $orstring = get_string('or', 'totara_reportbuilder');
        $a->value    = implode($orstring, $selected);

        return get_string('selectlabelnoop', 'filters', $a);
    }
}


/**
 * Given a hiearchy item object returns the HTML to display it as a filter selection
 *
 * @param object $item A hierarchy object containing id and name properties
 * @param string $filtername The identifying name of the current filter
 *
 * @return string HTML to display a selected item
 */
function display_selected_hierarchy_item($item, $filtername) {
    global $OUTPUT;

    $deletestr = get_string('delete');

    $out = html_writer::start_tag('div', array('data-filtername' =>  $filtername,
        'data-id' => $item->id, 'class' => 'multiselect-selected-item'));
    $out .= format_string($item->fullname);
    $out .= html_writer::link('#', html_writer::empty_tag('img', array('class' => 'delete-icon',
        'alt' => $deletestr, 'src' => $OUTPUT->pix_url('/t/delete'))));
    $out .= html_writer::end_tag('div');
    return $out;
}

/**
 * Helper function to display the 'add item' link to the filter
 *
 * @param string $filtername Name of the form element
 *
 * @return string HTML to display the link
 */
function display_choose_hierarchy_items_link($filtername, $type) {
    return html_writer::tag('div', html_writer::link('#', get_string("choose{$type}plural", 'totara_reportbuilder'),
        array('id' => "show-{$filtername}-dialog")),
        array('class' => "rb-{$type}-add-link"));
}