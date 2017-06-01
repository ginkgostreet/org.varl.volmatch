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

  /**
   * Volunteer Needs by availability
   * @param  array  $availability availability values
   * @return array               Compose QL Query Object
   */
  static function getNeedsByAvailabilitySQL($availability=array()) {
    $select = array();
    $select['civicrm_volunteer_need'] = array(
      'start_time', 'end_time', 'duration', 'id'
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

    $where = array(array(
      'field' => '`civicrm_volunteer_project`.`is_active`',
      'value' => 1,
    ));

    $availableWheres = array( 'paren' => 'AND');
    if (in_array('Weekdays', $availability)) {
      $availableWheres = CRM_ComposeQL_SQLUtil::composeWhereClauses($availableWheres, CRM_VolMatch_Util::whereNeedIsWeekday(), 'OR');
    }
    if (in_array('Weekday_Evenings', $availability)) {
      $weekdayEvenings = CRM_ComposeQL_SQLUtil::composeWhereClauses(CRM_VolMatch_Util::whereNeedIsWeekday(), CRM_VolMatch_Util::whereNeedIsEvening());
      $availableWheres = CRM_ComposeQL_SQLUtil::composeWhereClauses($availableWheres, $weekdayEvenings, 'OR');
    }

    if (in_array('Weekends', $availability)) {
      $weekends = CRM_VolMatch_Util::whereNeedIsWeekend();
      $availableWheres = CRM_ComposeQL_SQLUtil::composeWhereClauses($availableWheres, $weekends, 'OR');
    }

    $where = CRM_ComposeQL_SQLUtil::composeWhereClauses($where, $availableWheres, 'AND');

    $where = CRM_ComposeQL_SQLUtil::composeWhereClauses($where, CRM_VolMatch_Util::whereNeedIsNotPast(), 'AND');

    $sql = array(
      'SELECTS' => $select,
      'JOINS' => $joins,
      'WHERES' => $where,
    );

    return $sql;
  }

  /**
   * Volunteer Projects filtered by Impact Area
   * @param  array  $areas area values
   * @return array        ComposeQL Query Array
   */
  static function getProjectsByImpactAreaSQL($areas=array()) {
    if (is_null($areas)) {
      throw new Exception('no areas provided to CRM_VolMatch_Recommend::getProjectsByImpactAreaSQL()');
    }

    $impactSchema = CRM_ComposeQL_APIUtil::getCustomFieldSchema('organization_information', 'Primary_Impact_Area');
    $tblOrgInformation = $impactSchema['custom_group']['table_name'];
    $fldImpactArea = $impactSchema['column_name'];
    $beneficiaryRelationshipType = civicrm_api3('OptionValue', 'getValue',
      array('name' => 'volunteer_beneficiary', 'return' => 'value')
      );

    $select = array('orgs' => array('id as `beneficiary_id`', '`display_name` as `beneficiary`'), 'civicrm_volunteer_project_contact' => array('project_id'));

    $joins = array();
    $joins[] = array(
      'left' => 'civicrm_contact orgs',
      'join' => 'INNER JOIN',
      'right' => $tblOrgInformation ,
      'on' => "orgs.id = {$tblOrgInformation}.entity_id"
    );

    $joins[] = array(
      'join' => 'INNER JOIN',
      'right' => 'civicrm_volunteer_project_contact',
      'on' => 'orgs.id = civicrm_volunteer_project_contact.contact_id'
      . ' AND civicrm_volunteer_project_contact.relationship_type_id = '. $beneficiaryRelationshipType
    );

    $where = array(array(
      'field' => '`civicrm_volunteer_project`.`is_active`',
      'value' => 1,
    ));
    $areaWheres = array();
    foreach ($areas as $area) {
      $areaWheres[] = array('conj' => 'OR',
        'field' => "{$tblOrgInformation}.{$fldImpactArea}", 'value' => $area);
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
   * Recommend Needs for Contact SQL
   * @param  int $cid   contact ID
   * @param  int $limit number to return
   * @param  array $dates array('start_date'=> ... 'end_date'=> ...)
   * @return array        ComposeQL Query Array
   */
  public static function recommendedNeedsSQL($cid, $limit=NULL, $dates=NULL) {

    if (isset($dates['start_date']) || isset($dates['end_date'])){
      throw new CRM_Exception('recommendedNeeds, filter by date not implemented yet.');
    }

    $availabilitySQL = NULL;
    $interestsSQL = NULL;
    $availability = NULL;
    $interests = NULL;

//   * filter Orgs by Interests/Impacts
    $schemaInterests = CRM_ComposeQL_APIUtil::getCustomFieldSchema('volunteer_information', 'Interests');

    $interests = civicrm_api3('Contact', 'getValue', array(
      'return' => 'custom_'.$schemaInterests['id'],
      'contact_id' => $cid,
    ));

    $lkpAvailability = CRM_ComposeQL_APIUtil::getCustomFieldSchema('volunteer_information', 'availability');

    $result = civicrm_api3('Contact', 'getValue', array(
      'sequential' => 0,
      'return' => $lkpAvailability['api_column_name'],
      'contact_id' => $cid
    ));

    if (!empty($result)) {
      foreach($result as $avail) {
        $availability[$avail] = $lkpAvailability['option_group']['options'][$avail];
      }
    }

    $availabilitySQL = self::getNeedsByAvailabilitySQL($availability);
    $interestsSQL = self::getProjectsByImpactAreaSQL($interests);

    $needSQL['SELECTS'] = array_merge_recursive(
      $availabilitySQL['SELECTS'],
      $interestsSQL['SELECTS'],
      array('civicrm_volunteer_project' => array('description'))
     );

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

    if (!isset($interestsSQL['WHERES'])) {
      $interestsSQL['WHERES'] = array();
    }

    $needSQL['WHERES'] = CRM_ComposeQL_SQLUtil::composeWhereClauses(
      $availabilitySQL['WHERES'],
      $interestsSQL['WHERES'],
      'AND');

    $needSQL['WHERES'] += array('civicrm_volunteer_project.is_active = 1');
    $needSQL['ORDER_BYS'] = array('civicrm_volunteer_need.last_updated DESC');

    if (isset($limit)) {
      $needSQL['APPEND'] = "LIMIT $limit";
    }
    return $needSQL;
  }

  /**
   * All Volunteer Needs by interest and availability of this contact.
   * @param  int $cid   contact_id
   * @param  int $limit number to return
   * @param  array  $dates array('start_date' => .. 'end_date' => ..)
   * @return array    ComposeQL DAO result array
   */
  public static function recommendedNeeds($cid, $limit=NULL, $dates=array()) {
    $needSQL = self::recommendedNeedsSQL($cid, $limit, $dates);
    Civi::log()->debug('recommendedNeeds::SQL::'.CRM_ComposeQL_SQLUtil::debugComposeQLQuery($needSQL));
    return CRM_ComposeQL_DAO::fetchSelectQuery($needSQL);
  }

  /**
   * Set-Shift Volunteer Needs recommended for this contact.
   * @param  int  $cid   contact_id
   * @param  int  $limit number to return
   * @return array        ComposeQL DAO result array
   */
  public static function recommendedNeedsThisWeek($cid, $limit=NULL) {
    $needSQL = self::recommendedNeedsSQL($cid, $limit);

    $needSQL['WHERES'][] = CRM_VolMatch_Util::whereNeedIsSetShift();
    $needSQL['WHERES'][] = CRM_VolMatch_Util::whereNeedIsThisWeek();

    return CRM_ComposeQL_DAO::fetchSelectQuery($needSQL);
  }

  /**
   * Non-Set-Shift Volunteer Needs recommended for this contact.
   * @param  int $cid   Contact ID
   * @param  int $limit number to return
   * @return array        ComposeQL DAO result array
   */
  public static function recommendedNeedsAnyTime($cid, $limit=NULL) {
    $needSQL = self::recommendedNeedsSQL($cid, $limit);

    $needSQL['WHERES'][] = CRM_VolMatch_Util::whereNeedIsNotSetShift();

    Civi::log()->debug('recommendedNeedsAnyTime::SQL::'.CRM_ComposeQL_SQLUtil::debugComposeQLQuery($needSQL));
    return CRM_ComposeQL_DAO::fetchSelectQuery($needSQL);
  }
