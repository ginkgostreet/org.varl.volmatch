<?php

/**
 * NeedSearch.GetRecommended API specification
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_volunteer_appeal_getrecommended_spec(&$spec) {
  $spec['contact_id'] = array(
    'title' => 'Contact Id',
    'api.required' => 1,
    'api.aliases' => array('cid', 'id'),
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['start_date'] = array(
    'title' => 'Start Date',
    'description' => 'NOT IMPLEMENTED. include needs occuring not before...',
    'type' => CRM_Utils_Type::T_DATE,
  );
  $spec['end_date'] = array(
    'title' => 'End Date',
    'description' => 'NOT IMPLEMENTED. include needs occuring not after...',
    'type' => CRM_Utils_Type::T_DATE,
  );
  $spec['limit'] = array(
    'title' => 'Limit',
    'description' => 'Number of recommendations to return',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['type'] = array(
    'title' => 'Recommendation Type',
    'description' => 'Recommendation Algorithm. MATCH_APPEAL : Retunr your matched appeal.',
    'type' => CRM_Utils_Type::T_STRING,
  );
}

/**
 * NeedSearch.GetRecommended API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_volunteer_appeal_getrecommended($params) {
  $dates = array();
  if (isset($params['start_date'])) {
    $dates['start_date'] = CRM_Utils_Array::value('start_date', $params);
  }
  if (isset($params['end_date'])) {
    $dates['end_date'] = CRM_Utils_Array::value('end_date', $params);
  }

  $limit = (empty($params['options'])) ? NULL : CRM_Utils_Array::value('limit', $params['options']);

  $returnValues = CRM_VolMatch_RecommendAppeal::recommendedInterests($params['contact_id'], $limit, $dates);

  // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
  return civicrm_api3_create_success($returnValues, $params, 'NeedSearch', 'getRecommended');

}
