<?php

//namespace Civi\VolMatch;

//use GSL\ComposeQL\SQLUtil;
//use GSL\ComposeQL\APIUtil;
//use GSL\ComposeQL\DAO;

class CRM_VolMatch_RecommendAppeal {

  /**
   * Volunteer Appeal filtered by Impact Area
   * @param  array  $areas area values
   * @return array  ComposeQL Query Array
   */
  static function getAppealsByImpactAreaSQL($areas=array()) {
    if (!is_array($areas)) {
      $areas = array();
    }
    $impactSchema = CRM_ComposeQL_APIUtil::getCustomFieldSchema('Opportunity_Impact', 'Area_of_Impact');
    
    $customTable = $impactSchema['custom_group']['table_name'];
    $impactField = $impactSchema['column_name'];
    
    $beneficiaryRelationshipType = civicrm_api3('OptionValue', 'getValue',
      array('name' => 'volunteer_beneficiary', 'return' => 'value')
    );
    
    $select = array('appeals' => array('`id` as `appeal_id`', '`title` as `appeal_title`', '`appeal_teaser`', '`appeal_description` as `description`'), 'orgs' => array('`id` as `beneficiary_id`', '`display_name` as `beneficiary`'), 'civicrm_volunteer_project_contact' => array('project_id'));

    $joins = array();

    $joins[] = array(
      'join' => 'INNER JOIN',
      'left' => '`civicrm_volunteer_appeal` appeals',
      'right' => "`$customTable` impact",
      'on' => "`appeals`.`id` = `impact`.`entity_id`"
    );

    $joins[] = array(
      'join' => 'INNER JOIN',
      'right' => '`civicrm_volunteer_project` projects',
      'on' => '`appeals`.`project_id` = `projects`.`id`'
    );

    $joins[] = array(
      'join' => 'INNER JOIN',
      'right' => '`civicrm_volunteer_project_contact`',
      'on' => '`projects`.`id` = `civicrm_volunteer_project_contact`.`project_id` '
    );

    $joins[] = array(
      'join' => 'INNER JOIN',
      'right' => '`civicrm_contact` orgs',
      'on' => '`civicrm_volunteer_project_contact`.`contact_id` = `orgs`.`id` '
      . ' AND `civicrm_volunteer_project_contact`.`relationship_type_id` = '. $beneficiaryRelationshipType
    );

    $where = array(array(
      'field' => '`projects`.`is_active`',
      'value' => 1,
    ));

    $areaWheres = array();
    foreach ($areas as $area) {
      $areaWheres[] = array('conj' => 'OR',
        'field' => "`impact`.`{$impactField}`", 'value' => '%'.CRM_Core_DAO::VALUE_SEPARATOR.$area.CRM_Core_DAO::VALUE_SEPARATOR.'%', 'comp' => 'LIKE');
    }

    $where[] = array_merge(array('paren' => 'AND'), $areaWheres);

    $sql = array(
      'SELECTS' => $select,
      'JOINS' => $joins,
      'WHERES' => $where,
    );

    return $sql;
  }

  /**
   * Appeals recommended by Impact Area that match the contact's Interests
   * 
   * @param  int $cid   Contact ID
   * @param  int $limit number to return
   * @return array        ComposeQL DAO result array
   */
  public static function recommendedInterests($cid, $limit=NULL) {
    $interestsSQL = NULL;
    $interests = array();

    $schemaInterests = CRM_ComposeQL_APIUtil::getCustomFieldSchema('volunteer_information', 'Interests');

    $interests = civicrm_api3('Contact', 'getValue', array(
      'return' => 'custom_'.$schemaInterests['id'],
      'contact_id' => $cid,
    ));

    $interestsSQL = self::getAppealsByImpactAreaSQL($interests);

    if (isset($limit)) {
      $interestsSQL['APPEND'] = "LIMIT $limit";
    }

    // var_dump(CRM_ComposeQL_SQLUtil::debugComposeQLQuery($interestsSQL));
    return CRM_ComposeQL_DAO::fetchSelectQuery($interestsSQL);
  }
}
