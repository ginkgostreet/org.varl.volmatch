<?php

class CRM_VolMatch_Util {
  /**
   * Given an array of field names,
   * use metadata to pair fields that share the same option group.
   *
   * @param array $fields
   * @return array map
   */
  static function autoMatchFieldByOptionGroup($fields=array()) {
    foreach ($fields as $field) {
      $schema = CRM_ComposeQL_APIUtil::getCustomFieldSchema($field);
      if (array_key_exists('option_group_id', $schema)) {
        $meta[$field] = $schema['option_group']['name'];
      }
    }

    $matches = array();
    foreach ($meta as $field => $opt_grp) {
      $match = array_intersect($meta,array($opt_grp));
      if (count($match) > 1) {
        $a = array_keys($match);
        $b = array_reverse($a);
        $fields = array_combine($a, $b);
      }

      end($fields);
      do  {
        $field_b = current($fields);
        $field_a = key($fields);
        if (array_key_exists($field_b, $fields) === true) {
          // de-dupe:
          unset($fields[$field_a]);
        } else {
          $matches[$field_a] = $field_b;
        }
        ;
      } while (prev($fields) !== false);
    }

    return $fields;
  }


/**
  * Flexible Need Key

//-- OPEN-ENDED
//duration IS NULL

//-- FLEXIBLE Time-Frame
//duration IS NOT NULL AND end_time IS NOT NULL

//  SET SHIFT
// duration IS NOT NULL AND end_time IS NULL

*/

  /**
   * SET SHIFT
   * duration IS NOT NULL AND end_time IS NULL
   * @return Array SQL-Where Fragment
   */
  static function whereNeedIsSetShift() {
    return array( 'paren' => 'AND',
      array( 'field' => '`civicrm_volunteer_need`.`duration`', 'comp' => 'IS NOT NULL'),
      array( 'field' => '`civicrm_volunteer_need`.`end_time`', 'comp' => 'IS NULL')
    );
  }

  /**
   * Exclude Set-Shifts
   * @return Array SQL-Where Fragment
   */
  static function whereNeedIsNotSetShift() {
    return array( 'field' => '`civicrm_volunteer_need`.`duration`', 'comp' => 'IS NULL');
  }

  /**
   * Weekdays :: DAYOFWEEK( n.start_time) NOT IN (1,7)
   * @return Array SQL-Where Fragment
   */
  static function whereNeedIsWeekday() {
    return array(array(
      'DAYOFWEEK( `civicrm_volunteer_need`.`start_time` ) NOT in (1,7)'
    ));
  }

  /**
   * Weekends :: DAYOFWEEK( n.start_time ) in (1,7)
   * @return Array SQL-Where Fragment
   */
  static function whereNeedIsWeekend() {
    return array(array(
      'DAYOFWEEK( `civicrm_volunteer_need`.`start_time` ) in (1,7)'
    ));
  }
  /**
   * Evenings :: HOUR (n.start_time) > 4
   * @return Array SQL-Where Fragment
   */
  static function whereNeedIsEvening() {
    return array(array(
      'HOUR( `civicrm_volunteer_need`.`start_time` ) > 4'
    ));
  }

  /**
   * Needs that have not ended.
   * TODO: WARNING: apparently this causes errors if not utilized with CRM_ComposeQL_SQLUtil::composeWhereClauses()
   * e.g. - $SQL['WHERES'] = CRM_ComposeQL_SQLUtil::composeWhereClauses($needSQL['WHERES'], CRM_VolMatch_Util::whereNeedIsNotPast(), 'AND');
   *
   * @return Array SQL-Where Fragment
   */
  static function whereNeedIsNotPast() {
    return
      array(
        array('field' => '`civicrm_volunteer_need`.`is_active`','value' => 1, 'type' => 'Integer'),
        array('paren' => 'AND',
          // FLEX TIME
          array('field' => '`civicrm_volunteer_need`.`end_time`', 'comp' => '> NOW()'),
          // FLEXIBLE NEED
          array('conj' => 'OR', 'field' => '`civicrm_volunteer_need`.`is_flexible`', 'value'=> 1),
          //  SET SHIFT
          array('conj' => 'OR', 'DATE(`civicrm_volunteer_need`.`start_time`) >= CURRENT_DATE()'),
          array('paren' => 'OR',
             // OPEN-ENDED
            array('`civicrm_volunteer_need`.`duration` IS NULL'),
            array('DATE(`civicrm_volunteer_need`.`start_time`) < CURRENT_DATE()'),
            array('paren' => 'AND',
            // filter case that should not have existed: start, end but duration NULL
              array('field' => '`civicrm_volunteer_need`.`end_time`', 'comp' => 'IS NULL'),
              array('conj' => 'OR', 'field' => '`civicrm_volunteer_need`.`end_time`', 'comp' => '> NOW()'),
            ),
          ),
        ),
      );
  }
  /**
   * Needs with start-time this week (8 days)
   * @return Array SQL-Where Fragment
   */
  static function whereNeedIsThisWeek() {
    return array('field' => '`civicrm_volunteer_need`.`start_time`', 'comp' =>'< DATE_ADD(NOW(), INTERVAL 8 DAY)');
  }
}
