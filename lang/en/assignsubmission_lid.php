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
 * Language strings for Assignment LID plugin.
 *
 * @package    assignsubmission_lid
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname'] = 'Learning Intelligence Dashboard (LID)';

// Settings.
$string['settings'] = 'LID Settings';
$string['apikey'] = 'API Key';
$string['apikey_help'] = 'Google AI Studio API key for accessing Gemini models. Get a free key at https://aistudio.google.com/app/apikey';
$string['endpoint'] = 'API Endpoint';
$string['endpoint_help'] = 'The LLM API endpoint URL. Default is Google AI Studio\'s OpenAI-compatible endpoint.';
$string['model'] = 'Model';
$string['model_help'] = 'The AI model to use for analysis. Gemini 2.5 Flash is recommended for cost-effectiveness.';
$string['maxoutputtokens'] = 'Max Output Tokens';
$string['maxoutputtokens_help'] = 'Maximum tokens for LLM response. Default is 16384.';
$string['queueinterval'] = 'Queue Processing Interval';
$string['queueinterval_help'] = 'How often (in seconds) the queue processor runs. Default is 300 (5 minutes).';
$string['maxretries'] = 'Max Retries';
$string['maxretries_help'] = 'Maximum retry attempts for failed analyses. Default is 3.';
$string['staleclaimseconds'] = 'Stale Claim Threshold';
$string['staleclaimseconds_help'] = 'Seconds before a claimed job is considered stale and released. Default is 600 (10 minutes).';
$string['enablecosttracking'] = 'Enable Cost Tracking';
$string['enablecosttracking_help'] = 'Track and display API usage costs in dashboards.';
$string['costper1minputtokens'] = 'Cost per 1M Input Tokens (USD)';
$string['costper1minputtokens_help'] = 'Pricing for input tokens. Default is $0.075 for Gemini 2.5 Flash.';
$string['costper1moutputtokens'] = 'Cost per 1M Output Tokens (USD)';
$string['costper1moutputtokens_help'] = 'Pricing for output tokens. Default is $0.30 for Gemini 2.5 Flash.';
$string['costper1mthoughttokens'] = 'Cost per 1M Thought Tokens (USD)';
$string['costper1mthoughttokens_help'] = 'Pricing for thinking tokens. Default is $0.30 for Gemini 2.5 Flash.';

// UI Settings.
$string['uisettings'] = 'User Interface Settings';
$string['uisettings_desc'] = 'Configure the appearance and style of LID dashboards and components.';
$string['futuristicui'] = 'Enable Futuristic UI Mode';
$string['futuristicui_help'] = 'Enable enhanced visual effects including gradients, animations, and glass-morphism effects. This gives LID a modern, data-visualization aesthetic while still respecting your theme colors. Disable for a more conservative, traditional look that purely inherits from your theme.';

// Assignment settings.
$string['enabled'] = 'Enable LID Analysis';
$string['enabled_help'] = 'Enable Learning Intelligence Dashboard analysis for this assignment.';
$string['includecompetencies'] = 'Include Competency Analysis';
$string['includecompetencies_help'] = 'Analyze submissions against course competencies. Requires course competencies to be configured.';
$string['generaterubricscores'] = 'Generate Rubric Score Suggestions';
$string['generaterubricscores_help'] = 'LLM will suggest scores for each rubric criterion. Requires advanced grading method (rubric or marking guide).';
$string['autoanalyze'] = 'Auto-analyze on Submission';
$string['autoanalyze_help'] = 'Automatically queue analysis when students submit. (Coming in v0.2.0)';

// UI strings.
$string['analyze'] = 'Analyze with LID';
$string['reanalyze'] = 'Re-analyze';
$string['batchanalyze'] = 'Batch Analyze';
$string['viewanalysis'] = 'View Full Analysis';
$string['viewdashboard'] = 'LID Dashboard';
$string['analysispending'] = 'Analysis queued. Refresh in 2-3 minutes.';
$string['analysiscompleted'] = 'Analysis complete.';
$string['analysisfailed'] = 'Analysis failed: {$a}';
$string['noanalysis'] = 'No analysis available for this submission.';
$string['analysisunavailable'] = 'Analysis unavailable.';

// Dashboard strings.
$string['dashboardassignment'] = 'Assignment Analysis Dashboard';
$string['dashboardcourse'] = 'Course Analysis Dashboard';
$string['dashboardstudent'] = 'Student Analysis Dashboard';
$string['summarystatistics'] = 'Summary Statistics';
$string['totalsubmissions'] = 'Total Submissions';
$string['analyzed'] = 'Analyzed';
$string['pending'] = 'Pending';
$string['failed'] = 'Failed';
$string['notsubmitted'] = 'Not Submitted';
$string['averagequality'] = 'Average Quality Score';
$string['averagedepth'] = 'Average Cognitive Depth';
$string['topcompetencies'] = 'Top Competencies';
$string['apicost'] = 'API Cost';
$string['totalcost'] = 'Total Cost';

// Analysis result strings.
$string['overallquality'] = 'Overall Quality';
$string['cognitivedepth'] = 'Cognitive Depth';
$string['coherence'] = 'Coherence';
$string['evidencequality'] = 'Evidence Quality';
$string['keystrengths'] = 'Key Strengths';
$string['developmentpriorities'] = 'Development Priorities';
$string['nextsteps'] = 'Next Steps';
$string['rubricscores'] = 'Rubric Score Suggestions';
$string['competencies'] = 'Competency Demonstration';
$string['bloomlevel'] = 'Bloom\'s Level';
$string['confidence'] = 'Confidence';
$string['evidence'] = 'Evidence';

// Error messages.
$string['error:emptysubmission'] = 'Cannot analyze empty submission.';
$string['error:pdfextraction'] = 'PDF text extraction failed. Please upload a text-based PDF.';
$string['error:apikey'] = 'API key not configured. Contact site administrator.';
$string['error:apitimeout'] = 'API request timed out. Try again later.';
$string['error:invalidjson'] = 'Invalid response from AI service. Try again or contact administrator.';
$string['error:ratelimit'] = 'API rate limit exceeded. Try again in a few minutes.';
$string['error:unknown'] = 'An unknown error occurred. Contact administrator if this persists.';

// Capabilities.
$string['lid:analyze'] = 'Analyze student submissions with LID';
$string['lid:viewreports'] = 'View LID dashboards and reports';
$string['lid:viewcosts'] = 'View API cost data';
$string['lid:managesettings'] = 'Configure LID settings for assignments';

// Privacy.
$string['privacy:metadata:assignsubmission_lid_queue'] = 'Queue of pending analyses';
$string['privacy:metadata:assignsubmission_lid_queue:userid'] = 'The ID of the student whose submission is being analyzed';
$string['privacy:metadata:assignsubmission_lid_queue:status'] = 'Analysis status';
$string['privacy:metadata:assignsubmission_lid_queue:created_at'] = 'When the analysis was queued';
$string['privacy:metadata:assignsubmission_lid_analysis'] = 'Stored analysis results';
$string['privacy:metadata:assignsubmission_lid_analysis:userid'] = 'The ID of the student whose submission was analyzed';
$string['privacy:metadata:assignsubmission_lid_analysis:analysis_json'] = 'The complete analysis data generated by the AI';
$string['privacy:metadata:assignsubmission_lid_analysis:analyzed_at'] = 'When the analysis was completed';
$string['privacy:metadata:assignsubmission_lid_analysis:analyzed_by_userid'] = 'The ID of the instructor who triggered the analysis';
$string['privacy:metadata:googleaistudio'] = 'Assignment LID sends submission text to Google AI Studio for analysis';
$string['privacy:metadata:googleaistudio:submissiontext'] = 'The text content of the student submission';
$string['privacy:metadata:googleaistudio:userid'] = 'The student user ID (integer only, not name)';
$string['privacy:path:analyses'] = 'LID Analyses';

// Task names.
$string['processqueue'] = 'Process LID Analysis Queue';

// Help links.
$string['privacypolicy'] = 'Google AI Studio Privacy Policy';
$string['privacypolicy_link'] = 'https://ai.google.dev/gemini-api/terms';
$string['documentation'] = 'Documentation';
$string['documentation_link'] = 'https://learning-intelligence.dev/docs';
