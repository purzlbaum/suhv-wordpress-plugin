<?php

if ($games == 'on') {
  $gamePlan = $team->getGames();

  var_dump($gamePlan);

  $gamePlanHtml = '<div class="results-championship"><h4>Spielplan</h4>';
  foreach ($gamePlan as $game) {
    $gamePlanHtml .= ''
      .'<div class="gameplan">'
      . '<table border="0" cellpadding="0" cellspacing="0">'
      .   '<tr class="hometeam">'
      .     '<td>'
      .       $game['round']
      .     '</td>'
      .     '<td class="hometeamname">'
      .       $game['hometeamname']
      .     '</td>'
      .   '</tr>'
      .   '<tr class="awayteam">'
      .     '<td class="awayteamname">'
      .       $game['awayteamname']
      .     '</td>'
      .   '</tr>'
      . '</table>'
      .'</div>';

  }
  $gamePlanHtml .= '</div>';
  echo $gamePlanHtml;
}
