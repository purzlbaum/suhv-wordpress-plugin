<?php
/**
 * User: Claudio Schwarz
 * Date: 27.06.13
 * Time: 14:01
 * Displays the championship results of the swiss unihockey
 */

if ($resultsChampionsship == 'on') {

  $playedGamesAll = $team->getGames();

  $playedGamesAllHtml = '<div class="results-championship"><h4>Resultate Meisterschaft</h4>';
  foreach ($playedGamesAll as $game) {
    $playedGamesAllHtml .= ''
      .'<div class="leagueresults">'
      . '<table border="0" cellpadding="0" cellspacing="0">'
      .   '<tr class="hometeam">'
      .     '<td class="hometeamname">'
      .       $game['hometeamname']
      .     '</td>'
      .     '<td class="goalshome">'
      .       $game['goalshome']
      .     '</td>'
      .   '</tr>'
      .   '<tr class="awayteam">'
      .     '<td class="awayteamname">'
      .       $game['awayteamname']
      .     '</td>'
      .     '<td class="goalsaway">'
      .       $game['goalsaway']
      .     '</td>'
      .   '</tr>'
      . '</table>'
      .'</div>';

    if ($game['goalsaway'] <= '-1') { continue; }
    if ($game['goalshome'] <= '-1') { continue; }
    if ($game['leaguetype'] == 'Cup') { continue; }

  }
  $playedGamesAllHtml .= '</div>';
  echo $playedGamesAllHtml;
}
