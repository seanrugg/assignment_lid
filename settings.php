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
 * Admin settings for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // API Configuration Section.
    $settings->add(new admin_setting_heading(
        'assignsubmission_lid/apiheading',
        get_string('settings', 'assignsubmission_lid'),
        ''
    ));

    // API Key.
    $settings->add(new admin_setting_configpasswordunmask(
        'assignsubmission_lid/apikey',
        get_string('apikey', 'assignsubmission_lid'),
        get_string('apikey_help', 'assignsubmission_lid'),
        ''
    ));

    // API Endpoint.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/endpoint',
        get_string('endpoint', 'assignsubmission_lid'),
        get_string('endpoint_help', 'assignsubmission_lid'),
        'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
        PARAM_URL
    ));

    // Model — free text, model-agnostic. Cast to string at read time.
    // Examples: gemini-2.5-flash, gemini-2.5-pro, gpt-4o, claude-sonnet-4-6
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/model',
        get_string('model', 'assignsubmission_lid'),
        get_string('model_help', 'assignsubmission_lid'),
        'gemini-2.5-flash',
        PARAM_TEXT
    ));

    // Max Output Tokens.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/maxoutputtokens',
        get_string('maxoutputtokens', 'assignsubmission_lid'),
        get_string('maxoutputtokens_help', 'assignsubmission_lid'),
        '16384',
        PARAM_INT
    ));

    // UI Appearance Section.
    $settings->add(new admin_setting_heading(
        'assignsubmission_lid/uiheading',
        get_string('uisettings', 'assignsubmission_lid'),
        get_string('uisettings_desc', 'assignsubmission_lid')
    ));

    // Enable LID Branding Mode.
    $settings->add(new admin_setting_configcheckbox(
        'assignsubmission_lid/futuristicui',
        get_string('futuristicui', 'assignsubmission_lid'),
        get_string('futuristicui_help', 'assignsubmission_lid'),
        '0'
    ));

    // Queue Processing Section.
    $settings->add(new admin_setting_heading(
        'assignsubmission_lid/queueheading',
        get_string('queueinterval', 'assignsubmission_lid'),
        ''
    ));

    // Queue Processing Interval.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/queueinterval',
        get_string('queueinterval', 'assignsubmission_lid'),
        get_string('queueinterval_help', 'assignsubmission_lid'),
        '300',
        PARAM_INT
    ));

    // Max Retries.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/maxretries',
        get_string('maxretries', 'assignsubmission_lid'),
        get_string('maxretries_help', 'assignsubmission_lid'),
        '3',
        PARAM_INT
    ));

    // Stale Claim Threshold.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/staleclaimseconds',
        get_string('staleclaimseconds', 'assignsubmission_lid'),
        get_string('staleclaimseconds_help', 'assignsubmission_lid'),
        '600',
        PARAM_INT
    ));

    // Cost Tracking Section.
    $settings->add(new admin_setting_heading(
        'assignsubmission_lid/costheading',
        get_string('enablecosttracking', 'assignsubmission_lid'),
        ''
    ));

    // Enable Cost Tracking.
    $settings->add(new admin_setting_configcheckbox(
        'assignsubmission_lid/enablecosttracking',
        get_string('enablecosttracking', 'assignsubmission_lid'),
        get_string('enablecosttracking_help', 'assignsubmission_lid'),
        '1'
    ));

    // Cost per 1M Input Tokens.
    // Note: PARAM_RAW used instead of PARAM_FLOAT — Moodle's PARAM_FLOAT rejects
    // decimal values on servers using non-English locales. Values are cast to (float)
    // at read time in gemini_client.php.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/costper1minputtokens',
        get_string('costper1minputtokens', 'assignsubmission_lid'),
        get_string('costper1minputtokens_help', 'assignsubmission_lid'),
        '0.075',
        PARAM_RAW
    ));

    // Cost per 1M Output Tokens.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/costper1moutputtokens',
        get_string('costper1moutputtokens', 'assignsubmission_lid'),
        get_string('costper1moutputtokens_help', 'assignsubmission_lid'),
        '0.30',
        PARAM_RAW
    ));

    // Cost per 1M Thought Tokens.
    $settings->add(new admin_setting_configtext(
        'assignsubmission_lid/costper1mthoughttokens',
        get_string('costper1mthoughttokens', 'assignsubmission_lid'),
        get_string('costper1mthoughttokens_help', 'assignsubmission_lid'),
        '0.30',
        PARAM_RAW
    ));

    // Privacy & Documentation Links.
    $settings->add(new admin_setting_heading(
        'assignsubmission_lid/linksheading',
        get_string('documentation', 'assignsubmission_lid'),
        html_writer::link(
            get_string('privacypolicy_link', 'assignsubmission_lid'),
            get_string('privacypolicy', 'assignsubmission_lid'),
            ['target' => '_blank']
        ) . '<br>' .
        html_writer::link(
            get_string('documentation_link', 'assignsubmission_lid'),
            get_string('documentation', 'assignsubmission_lid'),
            ['target' => '_blank']
        )
    ));
}
