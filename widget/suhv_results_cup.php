<?php
/**
 * Author: Claudio Schwarz
 * Date: 27.06.13
 * Time: 14:00
 * Displays the cup results of the swiss unihockey
 */

if ($resultsCup == 'on') {

  $playedGamesAll = $team->getGames();

  $playedCupGamesAllHtml = '<div class="results-cup"><h4>Resultate Cup</h4>';
  if ($playedGamesAll['goalsaway'] <= -1 || $playedGamesAll['goalshome'] <= -1 ) {
    $playedCupGamesAllHtml .= ''
      . '<p>Es sind keine Cup-Resultate vorhanden.</p>';
  } else {
    foreach ($playedGamesAll as $game) {
      $playedCupGamesAllHtml .= ''
        .'<div class="cupresults">'
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
      if ($game['leaguetype'] == 'Meisterschaft') { continue; }
    }

  }
  $playedCupGamesAllHtml .= '</div>';
  echo $playedCupGamesAllHtml;
}
