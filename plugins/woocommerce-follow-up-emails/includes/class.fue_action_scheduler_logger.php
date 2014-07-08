<?php

/**
 * Class FUE_ActionScheduler_Logger
 *
 * Stops Action Scheduler from saving logs as WP Comments
 */
if (! class_exists('ActionScheduler_Logger') )
    require_once 'action-scheduler/classes/ActionScheduler_Logger.php';

class FUE_ActionScheduler_Logger extends ActionScheduler_Logger {

    public function init() {}

    public function log( $action_id, $message, DateTime $date = null ) {}

    public function get_entry( $entry_id ) {
        return new ActionScheduler_NullLogEntry();
    }

    public function get_logs( $action_id ) {
        return array();
    }

}