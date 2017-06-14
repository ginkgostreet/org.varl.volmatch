<?php

/**
 * MatchMail.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec
 *   Description of fields supported by this API call.
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_match_mail_Create_spec(&$spec) {
  $spec['created_id'] = array(
    'api.default' => 6964, // the contact ID of the cron user
    'description' => 'FK to Contact ID who first created this mailing',
    'title' => 'Mailing Creator',
    'type' => CRM_Utils_Type::T_INT,
    'FKApiName' => 'Contact',
  );
  $spec['name'] = array(
    'api.default' => 'Weekly Volunteer Match ' . date('Y-m-d'),
    'description' => 'Mailing Name (used in backend interfaces only)',
    'title' => 'Mailing Name',
  );
  $spec['from_name'] = array(
    'api.default' => 'Volunteer Arlington',
    'description' => 'From Header of mailing',
    'title' => 'Mailing From Name',
  );
  $spec['from_email'] = array(
    'api.default' => 'volarl@volunteerarlington.org',
    'description' => 'From Email of mailing',
    'title' => 'Mailing From Email',
  );
  $spec['subject'] = array(
    'api.default' => 'Volunteer opportunities in and around Arlington this week',
    'description' => 'Subject of mailing',
    'title' => 'Subject',
  );
  $spec['msg_template_id'] = array(
    'api.default' => /* TODO API lookup */70,
    'description' => 'FK to the message template',
    'title' => 'Mailing Message Template',
  );
  $spec['target_group'] = array(
    'api.required' => TRUE,
    'description' => 'FK to Group ID to which the mailing should be sent',
    'title' => 'Target Group',
    'type' => CRM_Utils_Type::T_INT,
    'FKApiName' => 'Group',
  );
}

/**
 * MatchMail.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_match_mail_Create($params) {
  civicrm_api3('Mailing', 'create', array(
    'created_id' => $params['created_id'],
    'name' => $params['name'],
    'mailing_type' => 'standalone',
    'from_name' => $params['from_name'],
    'from_email' => $params['from_email'],
    'subject' => $params['subject'],
//    'body_html' => "Maybe unnecessary if the template does the lifting?",
    'url_tracking' => 1,
    'auto_responder' => 0,
    'open_tracking' => 1,
    'msg_template_id' => $params['msg_template_id'],
    'visibility' => 'User and User Admin Only',
    'dedupe_email' => 1,
    'email_selection_method' => 'automatic',
    'api.MailingGroup.create' => array(
      'entity_id' => $params['target_group'],
      'entity_table' => 'civicrm_group',
      'group_type' => 'Include',
    ),
  ));
}
