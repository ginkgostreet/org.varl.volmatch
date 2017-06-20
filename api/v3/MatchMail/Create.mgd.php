<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'Cron:MatchMail.Create',
    'entity' => 'Job',
    'params' => 
    array (
      'version' => 3,
      'is_active' => 0,
      'name' => 'Queue Match Notification Mailings',
      'description' => 'Schedules match notification emails. Queued mailings will not be
              delivered until Send Scheduled Mailings job runs. Recommended: leave this
              job disabled to keep the parameter doc handy; create new jobs using the
              same API to accommodate whatever schedules are needed.',
      'run_frequency' => 'Daily',
      'api_entity' => 'MatchMail',
      'api_action' => 'Create',
      'parameters' => "created_id=[contact ID] - optional (defaults to cron user)\n"
              . "name=[mailing name used in backend UIs] - optional (defaults to 'Weekly Volunteer Match')\n"
              . "from_name=[from header] - optional (defaults to 'Volunteer Arlington')\n"
              . "from_email=[from email] - optional (defaults to 'volarl@volunteerarlington.org')\n"
              . "send_date=[strtotime()-parseable time to schedule mailing] - optional (defaults to 'now')\n"
              . "subject=[email subject] - optional (defaults to 'Volunteer opportunities in and around Arlington this week')\n"
              . "msg_template_id=[ID of messageTemplate to use as msg body] - optional (defaults to 70)\n"
              . "target_group=[ID of group to send mail to] - required\n",
    ),
  ),
);
