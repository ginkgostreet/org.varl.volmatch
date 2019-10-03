<?php

/**
 * Need Description truncate length in tokens.
 */
if (!defined('SUMMARY_LENGTH')) {
  define('SUMMARY_LENGTH', 150);
}

/**
 * Implements hook_civicrm_tokenValues
 * to render volunteer match recommendations.
 * @param  array &$values container for rendered tokens
 * @param  [type] $cids   contact ID's
 * @param  [type] $job    job
 * @param  array  $tokens Tokens to render
 */
function volmatch_civicrm_tokenValues(&$values, $cids, $job=null, $tokens=array(), $context=null) {
  if (!array_key_exists('volmatch', $tokens)) {
    return;
  }
  foreach ($cids as $cid) {
    global $volmatch_profile_checksum_qs;
    global $volmatch_contact_checksum;
    $cs = $volmatch_contact_checksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($cid);
    $volmatch_profile_checksum_qs = sprintf('&cs=%s&id=%s', $cs, $cid);

    foreach ($tokens['volmatch'] as $token => $nunya) {
      unset($type);
      switch ($token) {
        case 'MatchAppeal':
          $type = 'MATCH_APPEAL';
          break;
        case 'ThisWeek':
          $type = 'THIS_WEEK';
          break;
        case 'AnyTime':
          $type = 'ANY_TIME';
          break;
        case 'Interest':
        default:
          $type = 'INTEREST';
          break;
        case 'ProfileUrl':
        case 'SkillsUrl':
          $links = _volmatch_volunteerLinks();
          if ($token == 'ProfileUrl') {
            $values[$cid]["volmatch.{$token}"] = $links['profile_href'];
          } else if ($token =='SkillsUrl') {
            $values[$cid]["volmatch.{$token}"] = $links['skills_href'];
          }
          // break switch and continue next token:
          continue 2;
        case 'ProfileLink':
        case 'SkillsLink':
          $links = _volmatch_volunteerLinks();
          if ($token == 'ProfileLink') {
            $values[$cid]["volmatch.{$token}"] = $links['profile'];
          } else if ($token =='SkillsLink') {
            $values[$cid]["volmatch.{$token}"] = $links['skills'];
          }
          // break switch and continue next token:
          continue 2;
      }

      if (isset($type)) {
        $entity = ($type == "MATCH_APPEAL") ? 
          'VolunteerAppeal' : 'VolunteerNeed';
        
        $recommends = civicrm_api3($entity, 'getrecommended',
          array(
            'contact_id' => $cid,
            'type' => $type,
            'options' => array('limit' => 6)
          )
        );
      }

      if  ( !count($recommends) || $recommends['is_error'] == 1 || $recommends['count'] == 0 ) {
        $output = _volmatch_noResultsMessage($type);
      } else {
        _volmatch_formatDescriptions($recommends['values']);
        _volmatch_formatInfo($recommends['values']);

        $fields = array(
          'info',
          'description',
        );

        $output = _volmatch_formatNeedsAsHtmlTable($recommends['values'], $fields);
      }
      $values[$cid]["volmatch.{$token}"] = $output;
    }
  }
}

function _volmatch_noResultsMessage($type) {
  $fmtList = '<ul %s>%s</ul>';
  $fmtListItem = '<li %s>%s</li>';
  $links = _volmatch_volunteerLinks();

  if ($type == 'THIS_WEEK') {
    $msg = "Nothing matched. For better recommendations, try updating your availability on your {$links['profile']}";
  } else if ($type == 'ANY_TIME') {
    $msg = "Nothing matched. For better recommendations, try updating your {$links['skills']}";
  } else {
    $msg = 'Nothing matched. For better recommendations:'
      . sprintf($fmtList, '',
        sprintf($fmtListItem, '', "Update your {$links['skills']}.")
        . sprintf($fmtListItem, '', "Update your availability on your {$links['profile']}")
      )
    ;
  }

  return $msg;
}

/**
 * Sets description along with title and sign-up
 *
 * @param Array $opps by reference
 * @return void
 */
function _volmatch_formatDescriptions(&$opps) {

  foreach($opps as &$opp) {
    if( !empty($opp['appeal_teaser']) ) {
      $description = $opp['appeal_teaser'];
    } else {
      $description = $opp['description'];
    }

    if (isset($opp['appeal_title'])) {
      $opp['title'] = $opp['appeal_title'];
      $signUpUrl = _volmatch_opportunitySignUpUrl($opp);
    } else {
      $signUpUrl = _volmatch_volunteerNeedSignUpUrl($opp);
    }
    
    $opp['sign-up'] = sprintf( '<a href="%s" %s>%s</a>', 
      $signUpUrl, 'class="need_link"', 'Learn More'
    );

    _volmatch_formatTitle($opp);

    // remove html and truncate:
    $description = substr(strip_tags($description), 0, SUMMARY_LENGTH);     
    $opp['description'] = $opp['title'].$description.'<br />'.$opp['sign-up'];
  }
}

function _volmatch_formatTitle(&$opp) {
  $fmtAhref = '<a href="%s" %s>%s</a>';
  if(isset($opp['appeal_title'])) {
    $opp['title'] = $opp['appeal_title'];
    $signUpUrl = _volmatch_opportunitySignUpUrl($opp);
  } else { // Needs
    $signUpUrl = _volmatch_volunteerNeedSignUpUrl($opp);
  }
  
  $opp['title'] = sprintf('<h2 class="need_title">%s</h2>', $opp['title']);
  $opp['title'] = sprintf( $fmtAhref, 
    $signUpUrl, 'class="need_link"', $opp['title']
  );
}

function _volmatch_formatInfo(&$needs) {
  $emailStyles = array(
    'info_header' => 'font-size:0.75em; font-weight:bold; font-style:italic;',
    'info_text' => 'font-size:1em;',
  );

  $fmtHeading = '<span style="'.$emailStyles['info_header'].'">%s</span><br />';
  $fmtInfo = '<span style="'.$emailStyles['info_text'].'">%s</span><br />';

  foreach($needs as &$need) {
    if (!isset($need['info'])) {
      $need['info'] = '';
    }
    $need['info'] .= sprintf($fmtHeading, 'With:');
    $need['info'] .= sprintf($fmtInfo, CRM_Utils_Array::value('beneficiary', $need));

    $flex = _volmatch_flexibility($need);
    if ( isset($need['appeal_id'])) {
      $flex = 'appeal'; // disable flexiblity display
    }
    switch ($flex) {
      case 'set':
        $start = date_create($need['start_time']);
        $duration = date_interval_create_from_date_string($need['duration'].' minutes');

        $date = $start->format('M j, Y');
        $start_time = $start->format('g:i A');
        $end_time = date_add($start, $duration)->format('g:i A');

        $need['info'] .= sprintf($fmtHeading, 'When:')
        . sprintf($fmtInfo, $date)
        . sprintf($fmtInfo, "{$start_time} - {$end_time}")
        ;
        break;
      case 'time-frame':
        $start = date_create($need['start_time']);
        $end = date_create($need['end_time']);
        $hours = intval($need['duration']/60);
        $minutes = $need['duration'] - ($hours * 60);
        $minutes = ($minutes < 10) ? '0'.$minutes : $minutes;
        $duration = "{$hours}:{$minutes}";

        $need['info'] .= sprintf($fmtHeading, 'Shift-Length:')
        . sprintf($fmtInfo, $duration)
        ;
        $need['info'] .= sprintf($fmtHeading, 'Anytime:')
        . sprintf($fmtInfo, $start->format('M j, Y').' - '.$end->format('M j, Y'))
        ;
        break;
      case 'flexible':
        $start = ''; $end = '';

        if (!empty($need['start_time'])) {
          $start = date_create($need['start_time']);
          $start = 'after '.$start->format('M j, Y');
        }
        if (!empty($need['end_time'])) {
          $end = date_create($need['end_time']);
          $end = ' until '.$end->format('M j, Y');
        }

        $need['info'] .= sprintf($fmtHeading, 'Anytime:')
        . sprintf($fmtInfo, $start.$end)
        ;

        if (!empty($need['duration'])) {
          $hours = intval($need['duration']/60);
          $minutes = $need['duration'] - ($hours * 60);
          $minutes = ($minutes < 10) ? '0'.$minutes : $minutes;
          $duration = "{$hours}:{$minutes}";

          $need['info'] .= sprintf($fmtHeading, 'Shift-Length:')
          . sprintf($fmtInfo, $duration)
          ;
        }
        break;
    }
  }
}

/**
 * Apply HTML table markup to array of needs.
 * Displays all fields, or subset specified in $fields parameter.
 * @param  array $needs  volunteer needs
 * @param  array $fields fields to include in output
 * @return string         HTML Table
 */
function _volmatch_formatNeedsAsHtmlTable($needs, $fields=NULL) {
  $body = '';
  $fmtTable = '<table border="0" cellpadding="3px" cellspacing="3px">%s</table>';

  $fmtRow = '<tr>%s</tr>';
  $fmtCell = '<td>%s</td>';
  foreach ($needs as $need) {
    $cells = '';
    if ($fields) {
      foreach ($fields as $field) {
        $cells .= sprintf($fmtCell, $need[$field]);
      }
    } else {
      foreach($need as $field) {
        $cells .= sprintf($fmtCell, $field);
      }
    }
    $body .= sprintf($fmtRow, $cells);
  }

  return sprintf($fmtTable, $body);
}

/**
 * Generate email-able links for a volunteer to manage their personal info.
 * Depends on globally declared checksum.
 * @return array  array('profile' => .., 'profile_href' => ..,
 *                      'skills' => .., 'skills_href' => ..)
 */
function _volmatch_volunteerLinks() {
  global $volmatch_profile_checksum_qs;
  $checksum = $volmatch_profile_checksum_qs;

  $fmtAhref = '<a href="%s" %s>%s</a>';

  $skillsQS = '&reset=1&gid=17'.$checksum;

  $skillsHref = CRM_Utils_System::url('civicrm/profile/edit', $skillsQS, TRUE);
  $skillsLink = sprintf($fmtAhref, $skillsHref, '', 'skills and interests');

  $profileQS = '&reset=1&gid=18'.$checksum;
  $profileHref = CRM_Utils_System::url('civicrm/profile/edit', $profileQS, TRUE);;
  $profileLink = sprintf($fmtAhref, $profileHref, '', 'volunteer profile');

  return array(
    'profile' => $profileLink, 'profile_href' => $profileHref,
    'skills' => $skillsLink, 'skills_href' => $skillsHref,
  );
}

function _volmatch_urlWithLoginRedirect($request) {
  $destination = 'destination='.urlencode($request);
  return CRM_Utils_System::url('user/login', $destination, TRUE);
}

/**
 * Emailable URL to sign-up for a particular need.
 * Ensures user is logged-in by _volmatch_urlWithLoginRedirect().
 * @param  array $need volunteer need
 * @return string       fully-qualified url
 */
function _volmatch_volunteerNeedSignUpUrl($need) {
  $needIDQS = 'reset=1&needs%5B%5D=';
  $needLinkQs = $needIDQS.$need['id'];
  return _volmatch_urlWithLoginRedirect(
    CRM_Utils_System::url('civicrm/volunteer/signup', $needLinkQs, FALSE)
  );
}

/**
 * Emailable URL to sign-up for a particular Appeal.
 * Ensures user is logged-in by _volmatch_volunteerNeedSignUpUrl().
 * @param  array $need volunteer need
 * @return string       fully-qualified url
 */
function _volmatch_opportunitySignUpUrl($opp) {
  return _volmatch_urlWithLoginRedirect(
    _volmatch_volunteerAppealUrl($opp)
  );
}

/**
 * Emailable URL to appeal detail page for particular appeal.
 * @param  array $need volunteer need
 * @return string fully-qualified url
 */
function _volmatch_volunteerAppealUrl($opp) {
  return  CRM_Utils_System::url('civicrm/vol/',
    NULL, // query string
    FALSE, // absolute?
    "/volunteer/appeal/{$opp['appeal_id']}", // fragment
    TRUE, // htmlize?
    TRUE // is frontend?
  );
}

/**
 * Determine the flexibility of a need for display purposes.
 *
 * @param  array $need a volunteer need
 * @return string       one of: set, flexible, or time-frame
 */
function _volmatch_flexibility($need) {
  //  SET SHIFT
  // duration IS NOT NULL AND end_time IS NULL
  $type = 'set';

  //-- OPEN-ENDED
  //duration IS NULL
  if (empty($need['duration'])) {
    $type = 'flexible';
  }

  //-- FLEXIBLE Time-Frame
  //duration IS NOT NULL AND end_time IS NOT NULL
  if ($need['duration'] && $need['end_time']) {
    $type = 'time-frame';
  }

  return $type;
}
