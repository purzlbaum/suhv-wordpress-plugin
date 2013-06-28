<?php
/*
 * Displays the ranking table
 */
if ($rankings == 'on') {
  $league = $team->getSuhvLeague();
  $leagueTable = $league->getTable();

  $rankingsHtml = ''
    . '<div class="leaguetable"><h4>Tabelle</h4>'
    .   '<table border="0" cellpadding="0" cellspacing="0">'
    .     '<tr>'
    .       '<td class="place">'
    .         'Rg'
    .       '</td>'
    .       '<td class="teamname">'
    .         'Team'
    .       '</td>'
    .       '<td class="gamesplayed">'
    .         'Sp'
    .       '</td>'
    .       '<td class="goalsplusminus">'
    .         '+/-'
    .       '</td>'
    .       '<td class="points">'
    .         'P'
    .       '</td>'
    .     '</tr>';
  $i = 0;
  foreach ($leagueTable as $tableEntry) {
    $teamName = $tableEntry['teamname'];
    $goalsScored = $tableEntry['goals-scored'];
    $goalsReceived = $tableEntry['goals-received'];
    $goalsPlusMinus = $goalsScored - $goalsReceived;

    // empty classes for table
    $myTeam = '';
    $alt = '';
    $props = '';

    $tableProps = $league->getTableProps();

    if ($teamName == $clubNameSaved) {
      $myTeam = ' my-club';
    }

    if ($i % 2 == 1) {
      $alt = ' alt';
    }

    if ($i == $tableProps['bar1'] || $i == $tableProps['bar2'] || $i == $tableProps['bar3']) {
      $props = ' props';
    }

    $rankingsHtml .= ''
      . '<tr class="tablerow'.$alt.$myTeam.$props.'">'
      .   '<td class="place">'
      .      $tableEntry['place']
      .   '</td>'
      .   '<td class="teamname">'
      .      $teamName
      .   '</td>'
      .   '<td class="gamesplayed">'
      .      $tableEntry['games']
      .   '</td>'
      .   '<td class="goalsplusminus">'
      .      $goalsPlusMinus
      .   '</td>'
      .   '<td class="points">'
      .      $tableEntry['points']
      .   '</td>'
      . '</tr>';

    $i++;
  }
  $rankingsHtml .= '</table>';
  $rankingsHtml .= '</div>';

  echo $rankingsHtml;
}