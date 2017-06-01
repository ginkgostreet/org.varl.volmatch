<?php

require_once 'volmatch.civix.php';

require_once 'tokens/VolMatch_Availability_Interests.php';

function volmatch_civicrm_tokens(&$tokens) {
  $tokens['volmatch'] = array(
    'volmatch.Interest' => 'VolMatch: Match on Interests AND Availability',
    'volmatch.ThisWeek' => 'VolMatch: Set-Shifts This Week',
    'volmatch.AnyTime' => 'VolMatch: Non-Set-Shifts',
    'volmatch.ProfileUrl' => 'VolMatch: Volunteer Profile URL',
    'volmatch.SkillsUrl' => 'VolMatch: Volunteer Skills Etc URL',
    'volmatch.ProfileLink' => 'VolMatch: Volunteer Profile Link',
    'volmatch.SkillsLink' => 'VolMatch: Volunteer Skills Etc Link',
  );
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function volmatch_civicrm_config(&$config) {
  _volmatch_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function volmatch_civicrm_xmlMenu(&$files) {
  _volmatch_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function volmatch_civicrm_install() {
  _volmatch_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function volmatch_civicrm_postInstall() {
  _volmatch_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function volmatch_civicrm_uninstall() {
  _volmatch_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function volmatch_civicrm_enable() {
  _volmatch_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function volmatch_civicrm_disable() {
  _volmatch_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function volmatch_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _volmatch_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function volmatch_civicrm_managed(&$entities) {
  _volmatch_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function volmatch_civicrm_caseTypes(&$caseTypes) {
  _volmatch_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function volmatch_civicrm_angularModules(&$angularModules) {
  _volmatch_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function volmatch_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _volmatch_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function volmatch_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function volmatch_civicrm_navigationMenu(&$menu) {
  _volmatch_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'org.varl.volmatch')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _volmatch_civix_navigationMenu($menu);
} // */
