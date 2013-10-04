<?php
/**
 * Author: Claudio Schwarz
 * Date: 27.06.13
 * Time: 14:00
 * Displays the cup results of the swiss unihockey
 */

if ($resultsCup == 'on') {

  $playedCupGamesAllHtml = $team->getGames();

  $playedCupGamesAllHtmlHtml = '
    <div class="results-cup">
    <h4>Resultate Cup</h4>
    <div class="cupresults">
    <table border="0" cellpadding="0" cellspacing="0">
  ';

  foreach ($playedCupGamesAllHtml as $game) {
    if ($game['played'] == 'true' && $game['leaguetype'] == 'Cup') {
      $playedCupGamesAllHtmlHtml .= ''
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
        .   '</tr>';

    }
  }
  $playedCupGamesAllHtmlHtml .= '</table></div></div>';
  echo $playedCupGamesAllHtmlHtml;
}
