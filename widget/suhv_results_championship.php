<?php
/**
 * User: Claudio Schwarz
 * Date: 27.06.13
 * Time: 14:01
 * Displays the championship results of the swiss unihockey
 */

if ($resultsChampionsship == 'on') {

  $playedGamesAll = $team->getGames();

  //var_dump($playedGamesAll);

  $playedGamesAllHtml = '
    <div class="results-championship">
    <h4>Resultate Meisterschaft</h4>
    <div class="leagueresults">
    <table border="0" cellpadding="0" cellspacing="0">
  ';

  foreach ($playedGamesAll as $game) {
    if ($game['played'] == 'true' && $game['leaguetype'] == 'Meisterschaft') {
      $playedGamesAllHtml .= ''
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
  $playedGamesAllHtml .= '</table></div></div>';
  echo $playedGamesAllHtml;
}
