<?php
/**
 * Classes that return HTML Code from SUHV Classes like SuhvClub odr SuhvTeam
 * The content of the methods of this class can be adapted
 * 
 * @author Bert Hofmänner, Hofmänner New Media
 * @version 26.08.2011
 */

class SuhvHtmlBuilder {
	protected static function getPlayedGameTableRow($game, $teamName = null, $trClass = null) {
		$isHomeTeam = ($teamName == $game['hometeamname']) ? true : false;
	
		$html = "<tr" . ($trClass ? ' class="' . $trClass . '"' : '') . ">\n"
			. "<td>{$game['date']}, {$game['time']}</td>\n"
			. "<td>{$game['hometeamname']}</td>\n"
			. "<td>{$game['awayteamname']}</td>\n";
		
		if ($game['played'] == 'true' || $game['forfait'] == 'true') {
			// game has a result
				
			// add additional information to the result
			$additional = "";
			if ($game['overtime'] == 'true') $additional = " n.V.";
			if ($game['penaltyshooting'] == 'true') $additional = " n.P.";
			if ($game['forfait'] == 'true') $additional = " ff";
				
			// get class
			if ($isHomeTeam || $game['awayteamname'] == $teamName) {
				$resultClass = 'suhv-draw';
				if ($isHomeTeam) {
					// your team is home team
					if ($game['goalshome'] > $game['goalsaway']) {
						$resultClass = 'suhv-win';
					} else if ($game['goalshome'] < $game['goalsaway']) {
						$resultClass = 'suhv-lose';
					}
				} else {
					if ($game['goalshome'] < $game['goalsaway']) {
						$resultClass = 'suhv-win';
					} else if ($game['goalshome'] > $game['goalsaway']) {
						$resultClass = 'suhv-lose';
					}
				}
			} else {
				$resultClass = 'suhv-result';
			}
			$html .= "<td class=\"{$resultClass}\">{$game['goalshome']} : {$game['goalsaway']}{$additional}</td>\n";
		} else {
			// game has no result
			$html .= "<td></td>\n";
		}
		
		$html .= "</tr>\n";

		return $html;
	}
	
	protected static function getPlannedGamesTableHeader() {
		return "<table class=\"suhv-table suhv-planned-games\">\n"
			. "<thead>\n"
			. "<tr>\n"
			. "\t<th class=\"suhv-date\">Datum, Zeit</th>\n"
			. "\t<th class=\"suhv-opponent\">Gegner</th>\n"
			. "\t<th class=\"suhv-place\">Spielort</th>\n"
			. "</tr>\n"
			. "</thead>\n"
			. "<tbody>\n";
	}
	
	protected static function getPlannedGameTableRow($game, $teamName, $trClass = null) {
		$isHomeTeam = ($teamName == $game['hometeamname']) ? true : false;

		$html = "<tr" . ($trClass ? ' class="' . $trClass . '"' : '') . ">\n"
			. "<td>{$game['date']}, {$game['time']}</td>\n"
			. "<td>" . ($isHomeTeam ? $game['awayteamname'] : $game['hometeamname']) . "</td>\n"
			. "<td>";
		if ($game['gym_id'] > 0) {
			$html .= "<a href=\"http://www.swissunihockey.ch/spielbetrieb/hallen?id={$game['gym_id']}\" target=\"_blank\">{$game['gym']}</a>";
		} else {
			$html .= "-";
		}
		$html .= "</td>\n";
		$html .= "</tr>\n";
		
		return $html;
	}
	
	protected static function getPlannedGamesFullTableHeader() {
		return "<table class=\"suhv-table suhv-planned-games-full\">\n"
		. "<thead>\n"
		. "<tr>\n"
		. "\t<th class=\"suhv-date\">Datum, Zeit</th>\n"
		. "\t<th class=\"suhv-home\">Heimteam</th>\n"
		. "\t<th class=\"suhv-away\">Gastteam</th>\n"
		. "\t<th class=\"suhv-place\">Spielort</th>\n"
		. "</tr>\n"
		. "</thead>\n"
		. "<tbody>\n";
	}
	
	protected static function getPlannedGameFullTableRow($game, $teamName, $trClass = null) {
		$html = "<tr" . ($trClass ? ' class="' . $trClass . '"' : '') . ">\n"
			. "<td>{$game['date']}, {$game['time']}</td>\n"
			. "<td>{$game['hometeamname']}</td>\n"
			. "<td>{$game['awayteamname']}</td>\n";
		$html .= "<td>";
		if ($game['gym_id'] > 0) {
			$html .= "<a href=\"http://www.swissunihockey.ch/spielbetrieb/hallen?id={$game['gym_id']}\" target=\"_blank\">{$game['gym']}</a>";
		} else {
			$html .= '-';
		}
		$html .= "</td>\n";
			
		$html .= "</tr>\n";
			
		return $html;
	}
	
	protected static function getPlayedGamesTableHeader() {
		return "<table class=\"suhv-table suhv-played-games\">\n"
			. "<thead>\n"
			. "<tr>\n"
			. "\t<th class=\"suhv-date\">Datum, Zeit</th>\n"
			. "\t<th class=\"suhv-home\">Heim</th>\n"
			. "\t<th class=\"suhv-away\">Gast</th>\n"
			. "\t<th class=\"suhv-result\">Resultat</th>\n"
			. "</tr>\n"
			. "</thead>\n"
			. "<tbody>\n";
	}
	
	protected static function getTableHeader() {
		return "<table class=\"suhv-table\">\n"
			. "<tr>\n"
			. "\t<th class=\"suhv-rank\"><abbr title=\"Rang\">R.</abbr></th>\n"
			. "\t<th class=\"suhv-team\">Team</th>\n"
			. "\t<th class=\"suhv-games\"><abbr title=\"Spiele\">S</abbr></th>\n"
			. "\t<th class=\"suhv-wins\"><abbr title=\"Spiele Gewonnen\">G</abbr></th>\n"
			. "\t<th class=\"suhv-ties\"><abbr title=\"Spiele Unentschieden\">U</abbr></th>\n"
			. "\t<th class=\"suhv-defeats\"><abbr title=\"Spiele verloren\">V</abbr></th>\n"
			. "\t<th class=\"suhv-scored\"><abbr title=\"Erzielte Tore\">+</abbr></th>\n"
			. "\t<th class=\"suhv-recived\"><abbr title=\"Anzahl Gegentore\">-</abbr></th>\n"
			. "\t<th class=\"suhv-diff\"><abbr title=\"Torverhältnis\">+/-</abbr></th>\n"
			. "\t<th class=\"suhv-points\"><abbr title=\"Punkte\">P</abbr></th>\n"
			. "</tr>\n";
	}
	
	protected static function getTableRow(array $rank, $trClass) {
		$html = "<tr class=\"{$trClass}\">\n"
			. "\t<td class=\"suhv-rank\">{$rank['place']}</td>\n"
			. "\t<td class=\"suhv-team\">{$rank['teamname']}</td>\n"
			. "\t<td class=\"suhv-games\">{$rank['games']}</td>\n"
			. "\t<td class=\"suhv-wins\">{$rank['wins']}" . ($rank['wins-overtime'] > 0 ? " ({$rank['wins-overtime']})" : '') . "</td>\n"
			. "\t<td class=\"suhv-ties\">{$rank['ties']}</td>\n"
			. "\t<td class=\"suhv-defeats\">{$rank['defeats']}" . ($rank['defeats-overtime'] > 0 ? " ({$rank['defeats-overtime']})" : '') . "</td>\n"
			. "\t<td class=\"suhv-scored\">{$rank['goals-scored']}</td>\n"
			. "\t<td class=\"suhv-recived\">{$rank['goals-received']}</td>\n"
			. "\t<td class=\"suhv-diff\">" . ($rank['goals-scored'] - $rank['goals-received']) . "</td>\n"
			. "\t<td class=\"suhv-points\">{$rank['points']}</td>\n"
			. "</tr>\n";
		return $html;
	}
	
	protected static function getTableFooter() {
		return "</tbody>\n</table>\n";
	}
}

class SuhvClubHtmlBuilder extends SuhvHtmlBuilder {
	public static function getTeams(SuhvClub $club){
		$teams = $club->getTeams();
		$html = "<table class=\"suhv-table\">\n"
			. "<thead>\n"
			. "<tr>\n"
			. "<th>ID</th>\n"
			. "<th>Team</th>\n"
			. "<th>Teamname</th>\n"
			. "<th>Ligacode</th>\n"
			. "<th>Gruppe</th>\n"
			. "</tr>\n"
			. "</thead>\n";
		$i = 0;
		$html ."<tbody>\n";
		foreach ($club->getTeams() as $team) {
			$team instanceof SuhvTeam;
			$html .= "<tr" . ($i % 2 == 1 ? ' class="alt"' : '') . ">\n"
				. "<td>{$team->getId()}</td>\n"
				. "<td>{$team->getTeam()}</td>\n"
				. "<td>{$team->getTeamName()}</td>\n"
				. "<td>{$team->getLeague()}</td>\n"
				. "<td>{$team->getGroup()}</td>\n"
				. "</tr>\n";
			$i++;
		}
		$html .= "</tbody>\n"
			. "</table>\n";
		
		return $html;
	}
	
	public static function outputTeams(SuhvClub $club) {
		echo self::getTeams($club);
	}
	
	public static function getPlayedGames(SuhvClub $club, $forceLoad = false, $limit = 15) {
		try {
			$games = $club->getPlayedGames($forceLoad, $limit);
		} catch (SuhvNotFoundException $ex) {
			return "<p>Im Moment sind keine Spieldaten erfasst</p>";
		} catch (SuhvApiException $ex) {
			return "<p>Im Moment leider keine Verbindung zum Resultatservice von swiss unihockey</p>\n";
		}
		
		if (count($games) == 0) return '';
		
		$html = self::getPlayedGamesTableHeader();
		$i = 0;
		foreach ($games as $game) {
			$class = $i % 2 == 1 ? 'alt' : null;
			if (stripos($game['hometeamname'], $club->getName()) === false) {
				// club team is awayteam
				$team = $club->getTeamById($game['awayteamid']);
				$game['awayteamname'] = $team->getTeam();
			} else {
				$team = $club->getTeamById($game['hometeamid']);
				$game['hometeamname'] = $team->getTeam();
			}
			$html .= self::getPlayedGameTableRow($game, $team->getTeam(), $class);
			$i++;
		}
		
		$html .= self::getTableFooter();
		
		return $html;
	}
	
	public static function outputPlayedGames(SuhvClub $club, $forceLoad = false, $limit = 15) {
		echo self::getPlayedGames($club, $forceLoad, $limit);
	}
	
	public static function getPlannedGames(SuhvClub $club, $forceLoad = false, $limit = 15) {
		try {
			$games = $club->getPlannedGames($forceLoad, $limit);
		} catch (SuhvNotFoundException $ex) {
			return "<p>Keine Spiele Gefunden</p>\n";
		} catch (SuhvApiException $ex) {
			return "<p>Im Moment leider keine Verbindung zum Resultatservice von swiss unihockey</p>\n";
		}
		if (count($games) == 0) {
			return "<p>Die (reguläre) Saison ist für unsere Teams beendet.</p>\n";
		}
		
		$html = self::getPlannedGamesFullTableHeader();
		
		$i = 0;
		foreach ($games as $game) {
			$class = $i % 2 == 1 ? 'alt' : null;
			if (stripos($game['hometeamname'], $club->getName()) === false) {
				// club team is awayteam
				$team = $club->getTeamById($game['awayteamid']);
				$game['awayteamname'] = $team->getTeam();
			} else {
				$team = $club->getTeamById($game['hometeamid']);
				$game['hometeamname'] = $team->getTeam();
			}
			$html .= self::getPlannedGameFullTableRow($game, $team->getTeam(), $class);
			$i++;
		}
		$html .= self::getTableFooter();
		
		return $html;
	}
	
	public static function outputPlannedGames(SuhvClub $club, $forceLoad = false, $limit = 15) {
		echo self::getPlannedGames($club, $forceLoad, $limit);
	}

}


class SuhvTeamHtmlBuilder extends SuhvHtmlBuilder {
	
	
	public static function getTeamOverview(SuhvTeam $team, $forceLoad = false) {
		$html = '';
		
		if ($cupHtml = self::getCupGames($team, $forceLoad)) {
			$html .= "<h2 id=\"teamCup\">Cup</h2>\n" . $cupHtml;
		} 
		
		if (!$team->getGroup()) {
			$html .= "<p>Dieses Team ist nicht an die Meisterschaft angemeldet.</p>\n";
			return $html;
		}
		
		$html .= "<h2 id=\"teamPlayedGames\">" . $team->getTeam() . "</h2>\n";
		
		if ($playedHtml = self::getPlayedGames($team, $forceLoad)) {
			$html .= $playedHtml;
		} else {
			$html .= "<p>Die Saison hat noch nicht begonnen.</p>\n";
		}
		
		$html .= "<h2 id=\"teamTable\">Tabelle</h2>\n";
		$html .= self::getTable($team, $forceLoad);
		
		$html .= "<h2 id=\"teamPlannedGames\">Spiele</h2>\n";
		if ($plannedHtml = self::getPlannedGames($team, $forceLoad)) {
			$html .= $plannedHtml;
		} else {
			$html .= "<p>Die (reguläre) Saison ist beendet.</p>\n";
		}
		
		return $html;
		
	}
	
	public static function outputTeamOverview(SuhvTeam $team, $forceLoad = false) {
		echo self::getTeamOverview($team, $forceLoad);
	}
	
	public static function getCupGames(SuhvTeam $team, $forceLoad = false) {
		$html = "";
		
		$i = 0;
		foreach ($team->getGames($forceLoad) as $game) {
			if ($game['leaguetype'] == 'Cup') {
				$class = $i % 2 == 1 ? 'alt' : null;
				$html .= self::getPlayedGameTableRow($game, $team->getTeamName(), $class);
				$i++;
			}
		}
		if (empty($html)) return null;
		
		$html = self::getPlayedGamesTableHeader()
			. $html 
			. self::getTableFooter();
		
		return $html;
	}
	
	public static function outputCupGames(SuhvTeam $team, $forceLoad = false) {
		echo self::getCupGames($team, $forceLoad);
	}
	
	public static function getPlayedGames(SuhvTeam $team, $forceLoad = false) {
		$html = ""; $grouptext = false;
		$i = 0;
		foreach ($team->getGames($forceLoad) as $game) {
			if ($game['leaguetype'] == 'Meisterschaft' && ($game['played'] == 'true' || $game['forfait'] == 'true')) {
				// show header for playoffs/-outs
				if ($grouptext && $grouptext != $game['grouptext']) {
					$html .= self::getTableFooter();
					$html .= "<h3>{$game['grouptext']}</h3>\n";
					$html .= self::getPlayedGamesTableHeader();
				} 
				$grouptext = $game['grouptext'];
				$class = $i % 2 == 1 ? 'alt' : null;
				$html .= self::getPlayedGameTableRow($game, $team->getTeamName(), $class);
				$i++;
			}
		}
		if (empty($html)) return null;
		
		$html = self::getPlayedGamesTableHeader()
			. $html
			. self::getTableFooter();
		
		return $html;
	}
	
	public static function outputPlayedGames(SuhvTeam $team, $forceLoad = false) {
		echo self::getPlayedGames($team, $forceLoad);
	}
	
	public static function getPlannedGames(SuhvTeam $team, $forceLoad = false) {
		$html = "";
		$i = 0;
		foreach ($team->getGames($forceLoad) as $game) {
			// @todo: canceled games have a gym id of -1
			// do not include forfait games $games['forfait'] == 'true' and cancled games $games['canceled] == 'true'
			if ($game['leaguetype'] == 'Meisterschaft' && $game['played'] == 'false' && $game['forfait'] == 'false' && $game['canceled'] == 'false') {
				$class = $i % 2 == 1 ? 'alt' : null;
				$html .= self::getPlannedGameTableRow($game, $team->getTeamName(), $class);
				$i++;
			}
		}
		if (empty($html)) return null;
		
		$html = self::getPlannedGamesTableHeader()
			. $html
			. self::getTableFooter();
		
		return $html;
	}
	
	public static function outputPlannedGames(SuhvTeam $team, $forceLoad = false) {
		echo self::getPlannedGames($team, $forceLoad);
	}
	
	public static function getTable(SuhvTeam $team, $forceLoad = false){
		try {
			$tableData = $team->getTable($forceLoad);
		} catch (SuhvNotFoundException $ex) {
			return 'Für dieses Team wurde keine Tabelle gefunden.';
		}
		$tableProps = array();
		foreach ($team->getTableProps() as $prop) {
			if ($prop == 0) continue;
			$tableProps[$prop - 1] = $prop - 1;
		}
		
		$html = self::getTableHeader();
		$i = 0;
		foreach ($team->getTable($forceLoad) as $rank) {
			$classes = array();
			if ($i % 2 == 1) $classes[] = 'alt';
			if (isset($tableProps[$i])) $classes[] = 'suhv-bar';
			if ($team->getTeamname() == $rank['teamname']) $classes[] = 'suhv-my-team';
			
			$html .= self::getTableRow($rank, implode(' ', $classes));
			$i++;
		}
		$html .= "</table>\n";
		
		return $html;
	}
	
	public static function outputTable(SuhvTeam $team, $forceLoad = false){
		echo self::getTable($team, $forceLoad);
	}
}

class SuhvLeagueHtmlBuilder extends SuhvHtmlBuilder {
	
	public static function getLastGames(SuhvLeague $league, $teamName = null, $forceLoad = false) {
		$games = $league->getGames();
		if (!$games || count($games) == 0) return null;
		
		$teamName = $teamName ? $teamName : CFG_SUHV_CLUB;
		
		$html = self::getPlayedGamesTableHeader();
		$i = 0;
		foreach ($games as $game) {
			$class = $i % 2 == 1 ? 'alt' : null;
			$class = ($game['hometeamname'] == $teamName || $game['awayteamname'] == $teamName ?  'suhv-my-team' : $class);
		 	$html .= self::getPlayedGameTableRow($game, $teamName, $class);
		 	$i++;
		}
		$html .= self::getTableFooter();
		
		return $html;
	}
	
	public static function outputLastGames(SuhvLeague $league, $teamName = null, $forceLoad = false) {
		echo self::getLastGames($league, $teamName, $forceLoad);
	}
	
	public static function getNextGames(SuhvLeague $league, $teamName = null, $forceLoad = false) {
		$games = $league->getGames($league->getCurrentRound() + 1);
		if (!$games || count($games) == 0) return null;
		
		$teamName = $teamName ? $teamName : CFG_SUHV_CLUB;
		
		$html = self::getPlannedGamesFullTableHeader();
		$i = 0;
		foreach ($games as $game) {
			$class = $i % 2 == 1 ? 'alt' : null;
			$class = ($game['hometeamname'] == $teamName || $game['awayteamname'] == $teamName ?  'suhv-my-team' : $class);
			$html .= self::getPlannedGameFullTableRow($game, $teamName, $class);
			$i++;
		}
		$html .= self::getTableFooter();
		
		return $html;
	}
	
	public static function outputNextGames(SuhvLeague $league, $teamName = null, $forceLoad = false) {
		echo self::getNextGames($league, $teamName, $forceLoad);
	}
	
	public static function getGames(SuhvLeague $league, $teamName = null, $header = 'h2', $forceLoad = false) {
		$games = $league->getAllGames($forceLoad);
		if (!$games || count($games) == 0) return null;
		
		$html = "";
		
		$currentRound = false; $i = 0; 
		foreach ($games as $game) {
			if ($currentRound != $game['round']) {
				if ($currentRound) {
					$html .= self::getTableFooter();
				}
				$html .= "<{$header}>Runde {$game['round']}</{$header}>\n"
					. self::getPlayedGamesTableHeader();
				$currentRound = $game['round'];
				$i = 0;
			}
			$class = $i % 2 == 1 ? 'alt' : null;
			$class = ($game['hometeamname'] == $teamName || $game['awayteamname'] == $teamName ?  'suhv-my-team' : $class);
			
			$html .= self::getPlayedGameTableRow($game, $teamName, $class);
			$i++;
		}
		
		$html .= self::getTableFooter();
		
		return $html;
	}
	
	public static function outputGames(SuhvLeague $league, $teamName = null, $header = 'h2', $forceLoad = false) {
		echo self::getGames($league, $teamName, $header, $forceLoad);
	}
	
	public static function getTable(SuhvLeague $league, $round = null, $teamName = null, $forceLoad = false) {
		$ranks = $league->getTable($round, $forceLoad);
		$tableProps = array();
		foreach ($league->getTableProps() as $prop) {
			if ($prop == 0) continue;
			$tableProps[$prop - 1] = $prop - 1;
		}
		
		$html = self::getTableHeader();
		$i = 0;
		foreach ($ranks as $rank) {
			$classes = array();
			if ($i % 2 == 1) $classes[] = 'alt';
			if (isset($tableProps[$i])) $classes[] = 'suhv-bar';
			if ($teamName == $rank['teamname']) $classes[] = 'suhv-my-team';
			
			$html .= self::getTableRow($rank, implode(' ', $classes));
			$i++;
		}
		$html .= self::getTableFooter();
		
		return $html;
	}
	
	public static function outputTable(SuhvLeague $league, $round = null, $teamName = null, $forceLoad = false) {
		echo self::getTable($league, $round, $teamName, $forceLoad);
	}
}