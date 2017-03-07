<?php

//namespace Varl\VolMatch;

//use GSL\ComposeQL\APIUtil;

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
 */
  static function whereNeedIsSetShift() {
    return array('WHERES' => array(
      array( 'field' => '`civicrm_volunteer_need`.`duration`', 'comp' => 'IS NOT NULL'),
      array( 'field' => '`civicrm_volunteer_need`.`end_time`', 'comp' => 'IS NULL')
    ));
  }

/**
 * DAYOFWEEK( n.start_time) NOT IN (1,7) -- weekdays
 */
  static function whereNeedIsWeekday() {
    return array('WHERES' => array(
      'DAYOFWEEK( `civicrm_volunteer_need`.`start_time` ) NOT in (1,7)'
    ));
  }
/**
 * DAYOFWEEK( n.start_time ) in (1,7) -- weekends
 */
  static function whereNeedIsWeekend() {
    return array(
      'DAYOFWEEK( `civicrm_volunteer_need`.`start_time` ) in (1,7)'
    );
  }
/**
 * HOUR (n.start_time) > 4 --evening
 */
  static function whereNeedIsEvening() {
    return array(
      'HOUR( `civicrm_volunteer_need`.`start_time` ) > 4'
    );
  }
  static function whereNeedIsNotPassed() {
    return array(
        array('field' => '`civicrm_volunteer_need`.`is_active`','value' => 1, 'type' => 'Integer'),
        array('paren' => 'AND',
          array('field' => '`civicrm_volunteer_need`.`end_time`', 'comp' => '> NOW()'),
          array('conj' => 'OR', '`civicrm_volunteer_need`.`end_time` IS NULL')
        ),
    );
  }


}
