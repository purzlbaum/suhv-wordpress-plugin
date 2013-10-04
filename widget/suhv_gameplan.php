<?php

if ($games == 'on') {
  $gamePlan = $team->getGames();

  $optionResultsChampionsshipCountSaved = $instance['resultschampionsshipcount'];

  $gamePlanHtml = '<div class="gameplan-championship">
    <h4>Spielplan</h4>
    <div class="gameplan">
    <table border="0" cellpadding="0" cellspacing="0">
  ';
  $counter = 0;
  foreach ($gamePlan as $game) {
    $counter++;

    if ($counter < $optionResultsChampionsshipCountSaved) {
      if ($game['played'] == 'false' && $game['leaguetype'] == 'Meisterschaft') {
        $gamePlanHtml .= ''
          .   '<tr class="game-information">'
          .     '<td class="date">'
          .       $game['date'] . ', ' . $game['time']
          .     '</td>'
          .     '<td colspan="2" class="gym">'
          .       $game['gym']
          .     '</td>'
          .   '</tr>'
          .   '<tr class="hometeam">'
          .     '<td class="hometeamname">'
          .       $game['hometeamname']
          .     '</td>'
          .     '<td class="separator">'
          .       '-'
          .     '</td>'
          .     '<td class="awayteamname">'
          .       $game['awayteamname']
          .     '</td>'

          .   '</tr>'
          .   '<tr class="awayteam">'
          .   '</tr>';
      }
    } else {
      break;
    }
  }
  $gamePlanHtml .= '</table></div></div>';
  echo $gamePlanHtml;
}
