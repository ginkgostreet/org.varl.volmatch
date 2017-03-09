<?php

/**
 * NeedSearch.GetRecommended API specification
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_volunteer_need_getrecommended_spec(&$spec) {
  $spec['contact_id']['api.required'] = 1;
  $spec['contact_id']['api.aliases'] = array('cid');
  $spec['start_date']['api.default'] = 0;
  $spec['end_date']['api.default'] = 0;
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
function civicrm_api3_volunteer_need_getrecommended($params) {

  $returnValues = CRM_VolMatch_Recommend::recommendedNeeds(
    $params['contact_id'],
    array(CRM_Utils_Array::value('start_date', $params), CRM_Utils_Array::value('end_date', $params))
  );
   // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
  return civicrm_api3_create_success($returnValues, $params, 'NeedSearch', 'getRecommended');

}