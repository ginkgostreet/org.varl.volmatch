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
      // If Match Appeal Token is set then get recommended appeal for that specific contact.
      if(isset($type) && $type="MATCH_APPEAL") {
        $recommends = civicrm_api3('VolunteerAppeal', 'getrecommended',
          array(
            'contact_id' => $cid,
            'type' => $type,
            'options' => array('limit' => 5)
          )
        );
      } else {
        $recommends = civicrm_api3('VolunteerNeed', 'getrecommended',
          array(
            'contact_id' => $cid,
            'type' => $type,
            'options' => array('limit' => 5)
          )
        );
      }
      $values[$cid]["volmatch.{$token}"] =
        ( !count($recommends) || $recommends['is_error'] == 1 || $recommends['count'] == 0 )
        ? _volmatch_noResultsMessage($type)
        : _volmatch_constructRecommendations($recommends['values'])
      ;
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

function _volmatch_constructRecommendations($values) {
  _volmatch_formatDescription($values);
  _volmatch_formatInfo($values);

  $fields = array(
    'info',
    'description',
  );

  return _volmatch_formatNeedsAsHtmlTable($values, $fields);
}

function _volmatch_formatDescription(&$needs) {
  $fmtAhref = '<a href="%s" %s>%s</a>';

  foreach($needs as &$need) {
    $description = strip_tags($need['description']); // remove html
    $description = substr($description, 0, SUMMARY_LENGTH);
    $signUpUrl = _volmatch_volunteerNeedSignUpUrl($need);
    // If Appeal data comes then use appeal data instead of need data.
    // Below code executes when "MatchAppeal" token used.
    if(isset($need['appeal_title'])) {
      // Set Appeal title.
      $need['title'] = $need['appeal_title'];
      // Prepare Appeal redirect url.
      $signUpUrl = _volmatch_volunteerAppealUrl($need);
    }
    $title = sprintf( $fmtAhref,
      $signUpUrl,
      'class="need_link"',
      sprintf('<h2 class="need_title">%s</h2>', $need['title'])
    );
    $sign_up = sprintf($fmtAhref,
      $signUpUrl,
      'class="need_link"',
      'Learn More'
    );
    $need['description'] = $title;
    $need['description'] .= $description.'<br />'.$sign_up;
  }
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
    $flex = _volmatch_flexibility($need);
    $need['info'] .= sprintf($fmtHeading, 'With:');
    $need['info'] .= sprintf($fmtInfo, CRM_Utils_Array::value('beneficiary', $need));

    switch (_volmatch_flexibility($need)) {
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
      default:
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
 * Ensures user is logged-in by _volmatch_volunteerNeedSignUpUrl().
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
 * Emailable URL to appeal detail page for particular appeal.
 * @param  array $need volunteer need
 * @return string fully-qualified url
 */
function _volmatch_volunteerAppealUrl($need) {
  $appeal_id = $need['appeal_id'];

  $appeal_url = CRM_Utils_System::url('civicrm/vol/',
    NULL, // query string
    FALSE, // absolute?
    "/volunteer/appeal/{$appeal_id}", // fragment
    TRUE, // htmlize?
    TRUE // is frontend?
  );

  return $appeal_url;
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
