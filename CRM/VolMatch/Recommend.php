<?php

//namespace Civi\VolMatch;

//use GSL\ComposeQL\SQLUtil;
//use GSL\ComposeQL\APIUtil;
//use GSL\ComposeQL\DAO;

class CRM_VolMatch_Recommend {

  static function fieldsToRecommendOn() {
    return array(
      'Interests',
      'Primary_Impact_Area',
      'Background_Check_Opt_In',
      'Spoken_Languages',
      'Agreed_to_Waiver',
      'Group_Volunteer_Interest',
      'Availability',
      'Board_Service_Opt_In',
      'How_Often',
      'Volunteer_Emergency_Support_Team_Opt_In',
      'Other_Skills',
      'Local_Arlington_Civic_Association_Opt_In',
      'Spoken_Languages_Other_',
    );
  }

  public static function matchingCustomFieldsMap() {
    return array(
      'Interests' => 'Primary_Impact_Area',
      'Background_Check_Opt_In' => 'Background_Check_Opt_In',
      'Spoken_Languages' => 'Spoken_Languages',
      'Agreed_to_Waiver' => 'Agreed_to_Waiver',
      'Group_Volunteer_Interest' => 'Group_Volunteer_Interest',
      'Availability' => 'Availability',
      'Board_Service_Opt_In' => 'Board_Service_Opt_In',
      'How_Often' => 'How_Often',
      'Volunteer_Emergency_Support_Team_Opt_In' => 'Volunteer_Emergency_Support_Team_Opt_In',
      'Other_Skills' => 'Other_Skills',
      'Local_Arlington_Civic_Association_Opt_In' => 'Local_Arlington_Civic_Association_Opt_In',
      'Spoken_Languages_Other_' => 'Spoken_Languages_Other_',
    );
  }

  static function getNeedsByAvailabilitySQL($availability) {
    $select = array();
    $select['civicrm_volunteer_need'] = array(
      'start_time', 'end_time', 'duration', 'is_flexible'
    );
    $select['civicrm_volunteer_project'] = array(
      'title', 'id as `project_id`'
    );

    $joins[] = array(
      'left' => 'civicrm_volunteer_need',
      'join' => 'INNER JOIN',
      'right' => 'civicrm_volunteer_project',
      'on' => 'civicrm_volunteer_need.project_id = civicrm_volunteer_project.id'
    );

    $where = array();
    $where = CRM_VolMatch_Util::whereNeedIsNotPassed();
    if (in_array('Weekdays', $availability)) {
      $where = CRM_ComposeQL_SQLUtil::composeWhereClauses($where, CRM_VolMatch_Util::whereNeedIsWeekday(), 'OR');
    }
    if (in_array('Weekday_Evenings', $availability)) {
      $weekdayEvenings = CRM_ComposeQL_SQLUtil::composeWhereClauses(CRM_VolMatch_Util::whereNeedIsWeekday(), CRM_VolMatch_Util::whereNeedIsEvening());
      $where = CRM_ComposeQL_SQLUtil::composeWhereClauses($where, $weekdayEvenings, 'OR');
    }

    if (in_array('Weekends', $availability)) {
      $weekends = CRM_VolMatch_Util::whereNeedIsWeekend();
      $where = CRM_ComposeQL_SQLUtil::composeWhereClauses($where, $weekends, 'OR');
    }

    return array(
      'SELECTS' => $select,
      'JOINS' => $joins,
      'WHERES' => $where,
    );
  }

  static function getProjectsByImpactAreaSQL($areas) {
    $impactSchema = CRM_ComposeQL_APIUtil::getCustomFieldSchema('Primary_Impact_Area');
    $tblOrgInformation = $impactSchema['custom_group']['table_name'];
    $fldImpactArea = $impactSchema['column_name'];
    $beneficiaryRelationshipType = civicrm_api3('OptionValue', 'getValue',
      array('name' => 'volunteer_beneficiary', 'return' => 'value')
      );

    $select = array('orgs' => array('id'), 'civicrm_volunteer_project_contact' => array('project_id'));

    $joins = array();
    $joins[] = array(
      'left' => 'civicrm_contact orgs',
      'join' => 'INNER JOIN',
      'right' => $tblOrgInformation ,
      'on' => "orgs.id = {$tblOrgInformation}.entity_id"
    );

    $joins[] = array(
//      'left' => 'civicrm_contact orgs',
      'join' => 'INNER JOIN',
      'right' => 'civicrm_volunteer_project_contact',
      'on' => 'orgs.id = civicrm_volunteer_project_contact.contact_id'
      . ' AND civicrm_volunteer_project_contact.relationship_type_id = '. $beneficiaryRelationshipType
    );

    $where = array();
    foreach ($areas as $area) {
      $where[] = array('conj' => 'OR',
        'field' => "{$tblOrgInformation}.{$fldImpactArea}", 'value' => $area);
    }

    return array(
        'SELECTS' => $select,
        'JOINS' => $joins,
        'WHERES' => $where,
      );
  }

  /**
   * Recommend Needs for Contact
   */
  public static function recommendedNeeds($cid, $dates = NULL) {

//   * filter Orgs by Interests/Impacts
//    $schemaImpact = APIUtil::getCustomFieldSchema('Primary_Impact_Area');
    $schemaInterests = CRM_ComposeQL_APIUtil::getCustomFieldSchema('Interests');

    $interests = civicrm_api3('Contact', 'getValue', array(
      'return' => 'custom_'.$schemaInterests['id'],
      'contact_id' => $cid,
    ));

    $lkpAvailability = CRM_ComposeQL_APIUtil::getCustomFieldSchema('availability');

    $result = civicrm_api3('Contact', 'getValue', array(
      'sequential' => 0,
      'return' => $lkpAvailability['api_column_name'],
      'contact_id' => $cid
    ));

    foreach($result as $avail) {
      $availability[$avail] = $lkpAvailability['option_group']['options'][$avail];
    }

    $availabilitySql = self::getNeedsByAvailabilitySQL($availability);

    $interestsSQL = self::getProjectsByImpactAreaSQL($interests);

    $needSQL['SELECTS'] = array_merge_recursive($availabilitySql['SELECTS'], $interestsSQL['SELECTS']);

    $needSQL['JOINS'] = array_merge(
      $interestsSQL['JOINS'],
      array(array(
          'join' => 'INNER JOIN',
          'right' => 'civicrm_volunteer_project',
          'on' => '`civicrm_volunteer_project`.`id` = `civicrm_volunteer_project_contact`.`project_id`'
      )),
      array(array(
        'join' => 'INNER JOIN',
        'right' => 'civicrm_volunteer_need',
        'on' => 'civicrm_volunteer_need.project_id = civicrm_volunteer_project.id',
      ))
      );

    $needSQL['WHERES'] = CRM_ComposeQL_SQLUtil::composeWhereClauses($availabilitySql['WHERES'], $interestsSQL['WHERES'], 'AND');

    return CRM_ComposeQL_DAO::fetchSelectQuery($needSQL);
  }

}