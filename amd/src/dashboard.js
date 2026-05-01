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
 * JavaScript for Assignment LID plugin.
 *
 * @module     assignsubmission_lid/dashboard
 * @copyright  2026 Sean Rugg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    /**
     * Initialize the LID dashboard functionality.
     */
    var init = function() {
        
        // Handle analyze button clicks.
        $(document).on('click', '.lid-analyze-button', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var submissionId = button.data('submissionid');
            
            if (!submissionId) {
                Notification.alert('Error', 'Invalid submission ID', 'OK');
                return;
            }
            
            // Disable button and show loading state.
            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin"></i> Queuing...');
            
            // Call web service to queue analysis.
            queueAnalysis(submissionId).then(function(result) {
                if (result.success) {
                    button.html('<i class="fa fa-check"></i> Queued');
                    Notification.alert(
                        'Analysis Queued',
                        'Analysis has been queued. Refresh the page in 2-3 minutes to see results.',
                        'OK'
                    );
                } else {
                    button.prop('disabled', false);
                    button.html('Analyze with LID');
                    Notification.alert('Error', result.message || 'Failed to queue analysis', 'OK');
                }
            }).catch(function(error) {
                button.prop('disabled', false);
                button.html('Analyze with LID');
                Notification.exception(error);
            });
        });
        
        // Handle re-analyze button clicks.
        $(document).on('click', '.lid-reanalyze-button', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var submissionId = button.data('submissionid');
            
            if (!submissionId) {
                Notification.alert('Error', 'Invalid submission ID', 'OK');
                return;
            }
            
            // Confirm re-analysis.
            Notification.confirm(
                'Re-analyze Submission',
                'This will create a new analysis for the current submission version. Continue?',
                'Yes, Re-analyze',
                'Cancel',
                function() {
                    // Disable button and show loading state.
                    button.prop('disabled', true);
                    button.html('<i class="fa fa-spinner fa-spin"></i> Queuing...');
                    
                    // Call web service to queue analysis.
                    queueAnalysis(submissionId, 5).then(function(result) {
                        if (result.success) {
                            button.html('<i class="fa fa-check"></i> Queued');
                            Notification.alert(
                                'Re-analysis Queued',
                                'Analysis has been queued with priority. Refresh the page in 2-3 minutes.',
                                'OK'
                            );
                        } else {
                            button.prop('disabled', false);
                            button.html('Re-analyze');
                            Notification.alert('Error', result.message || 'Failed to queue analysis', 'OK');
                        }
                    }).catch(function(error) {
                        button.prop('disabled', false);
                        button.html('Re-analyze');
                        Notification.exception(error);
                    });
                }
            );
        });
    };
    
    /**
     * Queue an analysis via AJAX (placeholder - will need web service in Phase 3).
     *
     * @param {int} submissionId Submission ID
     * @param {int} priority Priority (0-10)
     * @return {Promise} Promise resolving to result object
     */
    var queueAnalysis = function(submissionId, priority) {
        priority = priority || 0;
        
        // For now, return a simulated promise.
        // In Phase 3, this will be replaced with actual web service call:
        // return Ajax.call([{
        //     methodname: 'assignsubmission_lid_queue_analysis',
        //     args: {submissionid: submissionId, priority: priority}
        // }])[0];
        
        return new Promise(function(resolve) {
            setTimeout(function() {
                resolve({success: true, message: 'Queued successfully'});
            }, 500);
        });
    };

    return {
        init: init
    };
});
