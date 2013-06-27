<?php
/**
 * SUHVphp: a framework to import floorball results from the REST API of swiss unihockey
 *
 * @author Bert Hofmänner, www.hnm.ch, www.n2n.ch
 * @version beta 5
 * @license http://creativecommons.org/licenses/by-nc/2.5/ch/
 */

// define values for webservice -> constants can be defined outside this script!
if (!defined('CFG_SUHV_CLUB')) define('CFG_SUHV_CLUB', 'Winterthur United');
if (!defined('CFG_SUHV_API_KEY')) define('CFG_SUHV_API_KEY', '');
if (!defined('CFG_SUHV_CACHE_DIR')) define('CFG_SUHV_CACHE_DIR', '/cache');
if (!defined('CFG_SUHV_VERSION')) define('CFG_SUHV_VERSION', 'beta 6');

/******************************************************************************
 * Documentation of the REST API
 * http://api.swissunihockey.ch/rest/v1.0/teams/docs
 * http://api.swissunihockey.ch/rest/v1.0/leagues/docs
 * http://api.swissunihockey.ch/rest/v1.0/games/docs
 * http://api.swissunihockey.ch/rest/v1.0/clubs/docs
 * http://api.swissunihockey.ch/rest/v1.0/gyms/docs
 *
 * not supported yet:
 * http://api.swissunihockey.ch/rest/v1.0/tournaments/docs
 *
 * Google Group for information and questions about the webservice:
 * Swiss Unihockey Webmaster (https://groups.google.com)
 *****************************************************************************/

/******************************************************************************
 * CHANGELOG
 *
 * beta 6:
 * - force load bug fixed
 *
 * beta 5:
 * - bug SuhvLeague->getCurrentRound fixed. Thanks to Thomas
 * - the number of games showed on club level can be influenced by $limit
 *
 * beta 4:
 * - updated documentation
 * - suhv_lib.php throws exception, when api_key is not set
 * - removed getLogo() from club -> is no longer supported by REST service
 * - added getUrl() for club
 *
 * beta 3:
 * - adaptions to api_key restriction by swissunihockey
 *
 * beta 2:
 * - bug fixing in SuhvLeague class
 * - support for gyms (SuhvGym)
 * - added SuhvLeague::getAll()
 *
 * beta 1:
 * - made adaptions to bugfixed framework (see mail from thorsten.brigmann@swissunihockey.ch
 *   23.05.2012
 * - requests to old season work now -> thanks to bugfixed framework
 * - SUHVTeam: new method loadRegistrations() replaces to loadLeagueAndGroup() and
 *   loadTeamNames()
 * - minor change in SuhvTeamHtmlBuilder->getTeamOverview() - league name is displayed
 *   instead of 'Meisterschaft'
 *
 * alpha 6:
 * - bug fixed: SuhvClub generated uncaught SuhvNotFoundException, when season is over
 *
 * alpha 5:
 * - bug fixed: crash with clubs having teams paricipating in GF-cup, but playing
 *   championsship on KF
 * - constants (CFG_SUHV_*) can be defined outside the scripts
 * - corrected calculation of SuhvTeam->ranking
 *
 * alpha 4:
 * - HTMLBuilder Classes display link to gym (thanks to uhtl.ch)
 * - Bugfixing in HTMLLeagueBuilder (thanks to thomas bühler)
 * - Bug fixing in SuhvTeam->getSuhvLeague() (thanks to thomas bühler)
 * - removed Club Name from SuhvTeam constructor
 * - added support for bars in tables
 * - added serialize() and unserialize() methods to Suhv class in order to
 *   make serialization on db easier
 *
 * alpha 3:
 * - added club services
 * - added getLastRank(): returns the teams last rank before the current round
 *   (only works correct, when object is saved serialized in the db)
 * - removed SuhvTeam->leagueGames --> use SuhvLeague class instead
 * - SuhvTeam->lastLoad on team changed to YYYY-MM-DD
 * - added class SuhvLeague
 * - moved ->setUpdated(), ->isUpdated(), ->needsUpdate() to class Suhv
 * - if REST returns http 503 (service unavailable) the latest cache files is
 *   used to display results
 *
 * alpha 2:
 * - changed caching system: cache file is valid for the day only on which it
 *   was created --> CFG_SUHV_CACHE_EXPIRATION is not needed anymore
 * - force reload implemented: SuhvApiManager->setCacheUseOffForNextRequest()
 *   will reload the results from the SUHV REST interface
 * - SuhvApiException caught, when there are no games on club (thanks
 *   to Stockfreunde Horriwil)
 *****************************************************************************/


if (!function_exists('test')) {
  /**
   * function to test and debug outputs
   * @param mixed $var
   */
  function test($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
  }
}

// check if api key exists
if (!CFG_SUHV_API_KEY) throw new SuhvApiException("No api key defined! Please define CFG_SUHV_API_KEY with your api key. If you haven't got one, get yours at http://api.swissunihockey.ch!");


abstract class Suhv {
  protected $id;
  protected $season = null;
  protected $lastLoad;
  protected $updated;

  public function getId() {
    if (!$this->id) throw new SuhvException("can't load object without an id");
    return $this->id;
  }

  public function setSeason($season = null) {
    $season = intval($season);
    if (!$season) $season = SuhvApiManager::getCurrentSeason();
    $this->season = $season;
  }

  public function getSeason() {
    if (empty($this->season)) {
      $this->season = SuhvApiManager::getCurrentSeason();
    }
    return $this->season;
  }

  /**
   * @param bool $forceLoad
   * @return SuhvApiManager
   */
  public function getManager($forceLoad = false) {
    $manager = SuhvApiManager::getInstance();
    if ($forceLoad) $manager->setCacheUseOffForNextRequest();
    $this->setUpdate();
    return $manager;
  }

  public function setUpdate() {
    $this->lastLoad = date('Y-m-d H:i:s');
    $this->updated = true;
    // throw new Exception('updated');
  }

  public function isUpdated() {
    return (bool)$this->updated;
  }

  public function needsUpdate() {
    if (!$this->lastLoad) return true;
    $lastLoad = explode(' ', $this->lastLoad);
    if (!isset($lastLoad[0])) return true;
    $lastLoad = intval(str_replace('-', '', $lastLoad[0]));
    return ($lastLoad < intval(date('Ymd')));
  }

  public function getLastLoad() {
    return $this->lastLoad;
  }

  /**
   * is called when unserializing an object
   */
  public function __wakeup() {
    $this->season = null;
    if ($this->needsUpdate()) {
      $this->load();
      $this->updated = true;
    } else {
      $this->updated = false;
    }
  }

  /**
   * use this function to save any Suhv object in the database
   */
  public function serialize() {
    return base64_encode(serialize($this));
  }

  /**
   * use this function to load the object from a serialized string
   *
   * @param string $serializedString
   */
  public static function unserialize($serializedString) {
    return unserialize(base64_decode($serializedString));
  }

  /**
   * abstract function to force child classes to implement load method
   * @param bool $forceLoad
   */
  abstract protected function load($forceLoad = false);

}

class SuhvClub extends Suhv {
  private $name;
  private $logo;
  private $url;
  private $address = array();
  private $teams = array();
  private $playedGames = array();
  private $plannedGames = array();

  private $suhvClubHtmlBuilder;

  /**
   * @param int $clubId
   * @param int $season
   * @throws SuhvException
   */
  public function __construct($clubId = null, $season = null) {
    $clubId = intval($clubId);
    if (empty($clubId)) $clubId = self::getClubIdByName(CFG_SUHV_CLUB);
    if (empty($clubId)) throw new SuhvException("can't create club without an id");

    $this->id = $clubId;
    $this->setSeason($season);
  }

  /**
   * returns the name of the club
   * @param bool $forceLoad
   * @return string
   */
  public function getName($forceLoad = false) {
    if (!$this->name) $this->loadAddress();
    return $this->name;
  }

  /**
   *
   * gets the logo
   * @todo: there seams to be no more support for this by the REST interface of swiss unihockey
   * @param bool $forceLoad
   */
  public function getLogo($forceLoad = false) {
    if (is_null($this->logo) || $forceLoad) $this->loadLogo($forceLoad);

    return $this->logo;
  }

  /**
   * loads the logo
   * @param bool $forceLoad
   */
  private function loadLogo($forceLoad = false) {
    $this->logo = $this->getManager($forceLoad)->getClubLogo($this->id);
  }

  /**
   * gets the url of a club.
   * @param bool $forceLoad
   */
  public function getUrl($forceLoad = false) {
    if (count($this->address) || $forceLoad) $this->loadAddress($forceLoad);
    return $this->url;
  }

  /**
   * gets address
   * @param bool $forceLoad
   */
  public function getAddress($forceLoad = false) {
    if (count($this->address) == 0) $this->loadAddress($forceLoad);

    return $this->address;
  }

  /**
   * loads address and url
   * @param bool $forceLoad
   * @throws SuhvException
   */
  private function loadAddress($forceLoad = false) {
    $data = $this->getManager($forceLoad)->getClub($this->id);

    if (!isset($data['name'])) {
      throw new SuhvException('no club name found!');
    }

    $this->name = $data['name'];
    unset($data['name']);
    $this->address = $data;
    if (isset($data['url'])) $this->url = $data['url'];
  }

  /**
   * returns an array with the SuhvTeam objects of the given club
   * @param bool $forceLoad
   * @return multitype:SuhvTeam
   */
  public function getTeams($forceLoad = false) {
    if (count($this->teams) == 0) {
      $this->loadTeams($forceLoad);
    }
    return $this->teams;
  }

  /**
   * returns a team object
   * @param int $teamId
   * @param bool $forceLoad
   * @return SuhvTeam
   * @throws SuhvException
   */
  public function getTeamById($teamId, $forceLoad = false) {
    $teams = $this->getTeams($forceLoad);
    if (!isset($teams[$teamId])){
      throw new SuhvException("team with id {$teamId} not found for club '{$this->getName()}'");
    }
    return $teams[$teamId];
  }

  /**
   * loads the teams
   * @param bool $forceLoad
   */
  private function loadTeams($forceLoad = false) {
    $data = $this->getManager($forceLoad)->getClubTeams($this->id, $this->getSeason());
    foreach ($data as $teamData) {
      $this->teams[$teamData['id']] = new SuhvTeam($teamData['id'], $this->getSeason(), $this->getName());
    }
  }

  /**
   * gets played games of a club
   * @param bool $forceLoad
   */
  public function getPlayedGames($forceLoad = false, $limit = 15) {
    if (!count($this->playedGames) || $forceLoad) {
      $this->loadPlayedGames($forceLoad, $limit);
    }

    return $this->playedGames;
  }

  /**
   * loads the last played games of the club
   * @param bool $forceLoad
   */
  private function loadPlayedGames($forceLoad = false, $limit = 15) {
    $clubName = $this->getName();
    $this->playedGames = $this->getManager($forceLoad)->searchGames($clubName, null, null, 'played', 'desc', $this->season, $limit);
  }

  /**
   * gets the next games of a club
   * @param bool $forceLoad
   */
  public function getPlannedGames($forceLoad = false, $limit = 15) {
    if (!count($this->plannedGames) || $forceLoad) {
      $this->loadPlannedGames($forceLoad, $limit);
    }
    return $this->plannedGames;
  }

  /**
   * loads the next planned games of the club
   * @param bool $forceLoad
   */
  private function loadPlannedGames($forceLoad = false, $limit = 15) {
    $clubName = $this->getName();
    $this->plannedGames = $this->getManager($forceLoad)->searchGames($clubName, null, null, 'planned', 'asc', $this->season, $limit);
  }

  /**
   * searchs for a club with a given name
   * @param string $clubName
   * @throws SuhvApiException
   * @throws SuhvException
   * @throws SuhvNotFoundException
   */
  public static function searchClub($clubName = null) {
    if (!$clubName) $clubName = CFG_SUHV_CLUB;

    return SuhvApiManager::getInstance()->searchClubs($clubName);
  }

  public static function getAllClubs($activeOnly = true) {
    return SuhvApiManager::getInstance()->getClubs($activeOnly);
  }

  /**
   * returns the id of a club
   * @param string $clubName
   * @throws SuhvNotFoundException
   * @throws SuhvException
   * @return int
   */
  public static function getClubIdByName($clubName = null) {
    if (!$clubName) $clubName = CFG_SUHV_CLUB;

    $data = SuhvApiManager::getInstance()->searchClubs($clubName);
    if (count($data) == 0) {
      throw new SuhvNotFoundException("no club with '{$clubName}' found");
    } else if (count($data) > 1) {
      throw new SuhvException("'more than one club found for '{$clubName}'");
    }
    return $data[0]['id'];
  }

  /**
   * loads when waking up
   * @see Suhv::load()
   */
  protected function load($forceLoad = false, $limit = 15) {
    $this->loadPlannedGames($forceLoad, $limit);
    $this->loadPlayedGames($forceLoad, $limit);
  }
}


class SuhvTeam extends Suhv {
  private $clubId;

  private $league;
  private $group;
  private $team;
  private $teamName;
  private $ranking = array();
  private $nbGamesPlayed;

  private $dataGames;
  private $dataTable;
  private $dataTableProps;

  /**
   * save the team object serialzied in your db for better performance!
   *
   * @param int $teamId
   * @param int $season
   */
  public function __construct($teamId, $season = null) {
    $this->id = $teamId;
    $this->setSeason($season);
  }

  public function getClubId() {
    if (!$this->clubId) $this->loadRegistrations();

    return $this->clubId;
  }

  /**
   * @return SuhvClub
   */
  public function getSuhvClub() {
    return new SuhvClub($this->getClubId(), $this->getSeason());
  }

  public function getLeague() {
    if (!$this->league) $this->loadRegistrations();

    return $this->league;
  }

  public function getGroup() {
    if (!$this->group) $this->loadRegistrations();

    return $this->group;
  }

  /**
   * @return SuhvLeague
   */
  public function getSuhvLeague() {
    return new SuhvLeague($this->getLeague(), $this->getGroup(), $this->getSeason());
  }

  public function getTeam() {
    if (!$this->team) $this->loadRegistrations();

    return $this->team;
  }

  public function getTeamName() {
    if (!$this->teamName) $this->loadRegistrations();

    return $this->teamName;
  }

  public function getRanking() {
    if (is_null($this->nbGamesPlayed)) $this->loadTable();

    return $this->ranking;
  }

  public function getRank() {
    if (is_null($this->nbGamesPlayed)) $this->loadTable();

    if (!isset($this->ranking[$this->nbGamesPlayed])) return null;

    return $this->ranking[$this->nbGamesPlayed];
  }

  public function getLastRank() {
    if (is_null($this->nbGamesPlayed)) $this->loadTable();

    if (!isset($this->ranking[$this->nbGamesPlayed])) return null;

    // only one game played
    if ($this->nbGamesPlayed == 1) return null;

    $lastRank = $this->ranking[$this->nbGamesPlayed - 1];

    if (empty($lastRank) && isset($this->ranking[$this->nbGamesPlayed - 2])) {
      $lastRank = $this->ranking[$this->nbGamesPlayed - 2];
    }
    return $lastRank;
  }

  public function getNbGamesPlayed() {
    if (is_null($this->nbGamesPlayed)) $this->loadTable();

    return $this->nbGamesPlayed;
  }

  /**
   * gets all the games of a team (played and planned)
   * @param bool $forceLoad
   */
  public function getGames($forceLoad = false) {
    // check if data needs to be loaded
    if (!$this->dataGames || $forceLoad) $this->loadGames($forceLoad);

    return $this->dataGames;
  }

  private function loadGames($forceLoad = false) {
    $this->dataGames = $this->getManager($forceLoad)->getTeamGames($this->getId(), null, 'asc', $this->getSeason());
  }

  /**
   * gets the current table of a team
   * @param bool $forceLoad
   */
  public function getTable($forceLoad = false) {
    // check if data needs to be loaded
    if (empty($this->dataTable) || $forceLoad) $this->loadTable($forceLoad);

    return $this->dataTable;
  }

  public function getTableProps() {
    return $this->dataTableProps;
  }

  private function loadTable($forceLoad = false) {
    $table = $this->getManager($forceLoad)->getTeamTable($this->getId(), $this->getSeason());
    $this->dataTable = $table['teams'];
    unset($table['teams']);
    $this->dataTableProps = $table;

    $lastGamesPlayed = $this->nbGamesPlayed;
    $lastRank = $lastGamesPlayed ? $this->ranking[$lastGamesPlayed] : null;
    $nbTeamsWithMoreGames = 0;

    $currentRank = null;
    foreach ($this->dataTable as $row) {
      if ($row['teamname'] == $this->getTeamName()) {
        if ($row['games'] > 0) {
          $currentRank = $row['place'];
          $this->nbGamesPlayed = $row['games'];
        } else {
          $this->nbGamesPlayed = 0;
          $this->ranking = array();
        }
      }
      if ($row['games'] > $lastGamesPlayed) $nbTeamsWithMoreGames++;
    }

    // ranking must not be saved, when there are teams with more games (all teams play saturday, our team plays sunday)
    // save ranking, if the team has more games than last time
    // or no other team has more played games
    // or there is no ranking for the current game round
    if ($currentRank > 0 &&
      ($this->nbGamesPlayed > $lastGamesPlayed
        || $nbTeamsWithMoreGames == 0
        || empty($this->ranking[$this->nbGamesPlayed]))) {
      $this->ranking[$this->nbGamesPlayed] = $currentRank;
    }

    if ($this->nbGamesPlayed > 0) {
      for ($i = 1; $i <= $this->nbGamesPlayed; $i++) {
        if (!isset($this->ranking[$i])) {
          $this->ranking[$i] = null;
        }
      }
      ksort($this->ranking);
    } else {
      $this->nbGamesPlayed = 0;
      $this->ranking = array();
    }

    $this->setUpdate();
  }

  private function loadRegistrations($forceLoad = false) {
    $data = $this->getManager($forceLoad)->getTeamRegistrations($this->getId(), $this->getSeason());
    $this->clubId = $data['clubid'];
    $this->teamName = $data['teamname'];
    $this->group = null; $team = null;

    foreach ($data['registrations'] as $registration) {
      if ($registration['leaguetype'] != 'Meisterschaft') continue;

      if (!$this->group || $this->group > $registration['group']) {
        $this->league = $registration['leaguecode'];
        $this->group = $registration['group'];

        $team = $registration['leaguetext'];
      }
    }
    $this->team = $team;
  }

  protected function load($forceLoad = false) {
    $this->loadRegistrations($forceLoad);
    $this->loadGames($forceLoad);
    $this->loadTable($forceLoad);
  }
}

class SuhvLeague extends Suhv {

  private $group;
  private $name;

  private $dataGroups = array();
  private $dataTables = array();
  private $dataTableProps = array();
  private $dataGames = array();
  private $currentRound = null;
  private $maxRound = null;

  /**
   * @param int $leagueId
   * @param int $groupId
   * @param int $season
   * @throws SuhvException
   */
  public function __construct($leagueId, $groupId, $season = null) {
    $this->id = $leagueId;
    $this->setSeason($season);

    // load groups and check if group is valid!
    $this->loadGroups();
    if (!isset($this->dataGroups[$groupId])) {
      throw new SuhvException("group {$groupId} not found for league {$leagueId}");
    }
    $this->groupId = $groupId;
    $this->name = $this->dataGroups[$groupId]['leaguetext'];

  }

  public function getGames($round = null, $forceLoad = false) {
    if ($forceLoad || !$this->dataGames || count($this->dataGames) == 0) {
      $this->load($forceLoad);
    }

    if (!isset($this->dataGames[$round])) return null;

    if ($round == null) return null;

    return $this->dataGames[$round];
  }

  public function getAllGames($forceLoad = false) {
    if (!$this->dataGames || count($this->dataGames) == 0) {
      $this->load($forceLoad);
    }

    $games = array();
    foreach ($this->dataGames as $round) {
      foreach ($round as $game) {
        $games[] = $game;
      }
    }
    return $games;
  }

  public function getTable($round = null, $forceLoad = false) {
    if (!$this->currentRound) $this->load($forceLoad);

    if ($round == null) $round = $this->currentRound;

    return $this->dataTables[$round];
  }

  public function getTableProps() {
    return $this->dataTableProps;
  }

  public function getName() {
    return $this->name;
  }

  /**
   * returns if a league is KF or GF
   * @todo: check if there is a better way to define type of league
   */
  public function getType() {
    if (strstr($this->name, ' GF ') || strstr($this->name, ' U1')  || strstr($this->name, ' U2')) {
      return 'GF';
    }
    return 'KF';
  }

  public function getGroup() {
    return $this->groupId;
  }

  public function getCurrentRound() {
    if (!$this->dataGames) $this->loadGames();
    if (is_null($this->currentRound) && $this->dataGames && count($this->dataGames) > 0) return 0;
    return $this->currentRound;
  }

  public static function getAll($forceLoad = false, $season = null) {
    $manager = SuhvApiManager::getInstance();
    if ($forceLoad) $manager->setCacheUseOffForNextRequest();
    return $manager->getLeagues($season);
  }

  private function loadGroups() {
    $dataGroups = $this->getManager()->getLeagueGroups($this->getId(), $this->getSeason());
    foreach ($dataGroups as $group) {
      $this->dataGroups[$group['id']] = $group;
    }
  }

  private function loadGames($forceLoad = false) {
    $dataGames = $this->getManager($forceLoad)->getLeagueGames($this->getId(), $this->getGroup(), null, null, 'asc', $this->getSeason());
    $this->dataGames = array();

    $currentRound = null; $maxRound = null;
    foreach ($dataGames as $game) {
      $maxRound = $game['round'];
      if ($game['played'] == 'true') {
        $currentRound = $game['round'];
      }
      $this->dataGames[$game['round']][] = $game;
    }
    $this->currentRound = $currentRound;
    $this->maxRound = $maxRound;
  }

  private function loadTable($forceLoad = false) {
    if (!$this->currentRound) $this->loadGames($forceLoad);

    $table = $this->getManager($forceLoad)->getLeagueTable($this->getId(), $this->getGroup(), $this->getSeason());
    $this->dataTables[$this->currentRound] = $table['teams'];
    unset($table['teams']);
    $this->dataTableProps = $table;
  }

  protected function load($forceLoad = false) {
    $this->loadGames($forceLoad);
    $this->loadTable($forceLoad);
  }
}

class SuhvGym extends Suhv {
  private $name;
  private $street;
  private $zip;
  private $city;
  private $country;
  private $position = array();

  public function __construct($id) {
    $this->id = $id;
    $this->load();
  }

  public function getName() {
    return $this->name;
  }

  public function getStreet() {
    return $this->street;
  }

  public function getZip() {
    return $this->zip;
  }

  public function getCity() {
    return $this->city;
  }

  public function getCountry() {
    return $this->country;
  }

  public function getPosition() {
    return $this->position;
  }

  public function load($forceLoad = false) {
    $gym = $this->getManager($forceLoad)->getGym($this->getId());

    $this->name = $gym['name'];
    $this->street = $gym['street'];
    $this->zip = $gym['zip'];
    $this->city = $gym['city'];
    $this->country = $gym['country'];
    $this->position = $gym['position'];
  }

  public static function searchGyms($search, $forceLoad = false, $limit = 10) {
    $manager = SuhvApiManager::getInstance();
    if ($forceLoad) $manager->setCacheUseOffForNextRequest();
    return $manager->searchGyms($search, $limit);
  }
}

/**
 * Returns REST Request as array. Singleton Pattern -> create object like this
 * $manager = SuhvapiManager::getInstance();
 *
 * @author Bert Hofmänner
 */
class SuhvApiManager {
  private $results = array();
  private $log = array();
  private $useCache = true;

  private static $apiAddress = 'http://api.swissunihockey.ch/rest/';
  private static $apiVersion = 'v1.0';
  private static $instance = null;

  private function __construct(){}
  private function __clone(){}

  /**
   * @return SuhvApiManager
   */
  public static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * gets the games of a team (including cup)
   *
   * @param int $teamId
   * @param string $status (played|planned)
   * @param string $order (asc|desc)
   * @param int $season
   * @param int limit
   *
   * @return array
   */
  public function getTeamGames($teamId, $status = null, $order = 'asc', $season = null, $limit = 100) {
    if (empty($teamId)) throw new SuhvApiException("can't get games without a team id");
    $status = $this->validateStatus($status);
    $order = $this->validateOrder($order);
    $season = $this->validateSeason($season);

    $queries = array('status' => $status, 'order' => $order, 'season' => $season, 'limit' => $limit);

    $xml = $this->loadCurl("teams/{$teamId}/games", $queries);
    return $this->simpleXmlToArray($xml);
  }

  /**
   * gets the table of a team
   *
   * @param int $teamId
   * @param int $season
   *
   * @return array
   */
  public function getTeamTable($teamId, $season = null) {
    if (empty($teamId)) throw new SuhvApiException("can't get table without a team id");
    $season = $this->validateSeason($season);
    $queries = array('season' => $season);
    $xml = $this->loadCurl("teams/{$teamId}/table", $queries);
    $teams = $this->simpleXmlToArray($xml);
    $xml = array('bar1' => (string)$xml['bar1'], 'bar2' =>(string)$xml['bar2'], 'bar3' => (string)$xml['bar3']);
    foreach ($teams as $team) {
      $xml['teams'][] = $team;
    }
    return $xml;
  }

  /**
   * gets registrations for a team
   *
   * @param int $teamId
   * @param int $season
   *
   * @return array
   */
  public function getTeamRegistrations($teamId, $season = null) {
    if (empty($teamId)) throw new SuhvApiException("can't get registration without a team id");
    $season = $this->validateSeason($season);
    $queries = array('season' => $season);

    $xml = $this->loadCurl("teams/{$teamId}/registrations", $queries);

    $teamData = array();
    foreach ($xml->attributes() as $key=>$value) {
      $teamData[$key] = (string)$value;
    }
    $teamData['registrations'] = array();
    foreach ($xml as $teilnahme) {
      $data = array();
      foreach ($teilnahme->attributes() as $key => $value) {
        if ($key == 'clubid') continue;
        $data[$key] = (string)$value;
      }
      $teamData['registrations'][] = $data;
    }

    return $teamData;
  }

  /**
   * returns all teams of a club
   *
   * @param string $clubName
   * @param int $season
   * @param int $limit
   *
   * @return array
   */
  public function searchTeams($clubName = null, $season = null, $limit = 50) {
    $clubName = $clubName ? $clubName : CFG_SUHV_CLUB;
    $season = $this->validateSeason($season);
    $queries = array('q' => $clubName, 'season' => $season, 'limit' => $limit);
    $xml = $this->loadCurl("teams/search", $queries);
    return $this->simpleXmlToArray($xml);
  }

  /**
   * gets all the infos about the different groups within a league
   *
   * @param int $leagueCode
   * @param int $season
   *
   * @return array
   */
  public function getLeagueGroups($leagueCode, $season = null) {
    $leagueCode = $this->validateLeagueCode($leagueCode);

    $season = $this->validateSeason($season);
    $queries = array('season' => $season);

    $xml = $this->loadCurl("leagues/{$leagueCode}/groups", $queries);
    return $this->simpleXmlToArray($xml);
  }

  /**
   * gets all games of a given league
   *
   * @param int $leagueCode
   * @param int $groupNb
   * @param string $status (planned|played)
   * @param mixed $club id or name of club, if set, only games of the club team are returned
   * @param string $order (asc|desc)
   * @param int $season
   * @param int $limit
   *
   * @return array
   */
  public function getLeagueGames($leagueCode, $groupNb, $status = null, $club = null, $order = 'asc', $season = null, $limit = 200) {
    $leagueCode = $this->validateLeagueCode($leagueCode);

    $status = $this->validateStatus($status);
    $order = $this->validateOrder($order);
    $season = $this->validateSeason($season);
    $limit = intval($limit);
    $queries = array('season' => $season, 'club' => $club, 'status' => $status, 'order' => $order, 'limit' => $limit);

    $xml = $this->loadCurl("leagues/{$leagueCode}/groups/{$groupNb}/games", $queries);
    return $this->simpleXmlToArray($xml);
  }

  /**
   * gets all teams of a given league
   *
   * @param int $leagueCode
   * @param int $groupNb
   * @param int $season
   *
   * @return array
   */
  public function getLeagueTeams($leagueCode, $groupNb, $season = null) {
    $leagueCode = $this->validateLeagueCode($leagueCode);

    $season = $this->validateSeason($season);
    $queries = array('season' => $season);

    $xml = $this->loadCurl("leagues/{$leagueCode}/groups/{$groupNb}/teams", $queries);
    return $this->simpleXmlToArray($xml);
  }

  /**
   * gets the table of a given league
   *
   * @param int $leagueCode
   * @param int $groupNb
   * @param int $season
   *
   * @return array
   */
  public function getLeagueTable($leagueCode, $groupNb, $season = null) {
    $leagueCode = $this->validateLeagueCode($leagueCode);

    $season = $this->validateSeason($season);
    $queries = array('season' => $season);

    $xml = $this->loadCurl("leagues/{$leagueCode}/groups/{$groupNb}/table", $queries);
    $teams = $this->simpleXmlToArray($xml);
    $xml = array('bar1' => (string)$xml['bar1'], 'bar2' =>(string)$xml['bar2'], 'bar3' => (string)$xml['bar3']);
    foreach ($teams as $team) {
      $xml['teams'][] = $team;
    }
    return $xml;
  }

  /**
   * returns an array with information about all leagues
   *
   * @param int $season
   *
   * @return array
   */
  public function getLeagues($season = null) {
    $season = $this->validateSeason($season);
    $queries = array('season' => $season);

    $xml = $this->loadCurl("leagues", $queries);
    $data = $this->simpleXmlToArray($xml);
    foreach ($data as $key => $value) {
      $leagues[$value['leaguecode']] = $value;
    }
    return $leagues;
  }

  /**
   * gets all the information about a game
   *
   * @param int $gameId
   *
   * @return array
   */
  public function getGame($gameId) {
    $xml = $this->loadCurl("games/{$gameId}");
    // @todo: not all the data is returned
    return $this->simpleXmlToArray($xml);
  }

  public function searchGames($clubName = null, $leaguecode = null, $group = null, $status = null, $order = 'asc', $season = null, $limit = 10) {
    $clubName = $clubName ? $clubName : CFG_SUHV_CLUB;
    $status = $this->validateStatus($status);
    $order = $this->validateOrder($order);
    $season = $this->validateSeason($season);

    $queries = array('q' => $clubName, 'leaguecode' => $leaguecode, 'group' => $group, 'status' => $status, 'order' => $order, 'season' => $season, 'limit' => $limit);
    try {
      $xml = $this->loadCurl('games/search', $queries);
    } catch (SuhvNotFoundException $ex) {
      return array();
    }

    return $this->simpleXmlToArray($xml);
  }

  public function getClubs($activeOnly = true) {
    $activeOnly = (bool)$activeOnly ? null : array('active' => '1');
    $xml = $this->loadCurl('clubs', $activeOnly);
    return $this->simpleXmlToArray($xml);
  }

  public function getClub($clubId) {
    if (empty($clubId)) throw new SuhvApiException("can't get club, without an id");

    $xml = $this->loadCurl("clubs/" . $clubId);
    $data = $this->simpleXmlToArray($xml);
    $club = array();
    foreach ($data as $key => $value) {
      foreach ($value as $k => $v) {
        $club[$k] = $v;
      }
    }
    return $club;
  }

  public function getClubTeams($clubId, $season = null) {
    if (empty($clubId)) throw new SuhvApiException("can't get club teams, without a club id");

    $season = $this->validateSeason($season);
    $xml = $this->loadCurl("clubs/{$clubId}/teams", array('season' => $season));
    return $this->simpleXmlToArray($xml);
  }

  public function getClubLogo($clubId) {
    try {
      $xml = $this->loadCurl("clubs/{$clubId}/logo");
    } catch (SuhvNotFoundException $ex) {
      return '';
    }
    return (string)$xml[0];
  }

  public function searchClubs($clubName, $limit = 10) {
    try {
      $xml = $this->loadCurl("clubs/search", array('q' => $clubName));
    } catch (SuhvNotFoundException $ex) {
      return array();
    }
    return $this->simpleXmlToArray($xml);
  }

  public function getGym($gymId) {
    $xml = $this->loadCurl('gyms/' . $gymId);
    $gym = array();
    $gym['id'] = (string)$xml['id'];
    foreach ($xml->children() as $key => $value) {
      if ($key == 'position') {
        $gym['position']['lat'] = (string)$value['lat'];
        $gym['position']['lng'] = (string)$value['lng'];

      } else {
        $gym[$key] = (string)$value[0];
      }
    }
    return $gym;
  }

  public function searchGyms($search, $limit = 10) {
    $queries = array('q' => $search, 'limit' => $limit);
    $xml = $this->loadCurl('gyms/search', $queries);
    return $this->simpleXmlToArray($xml);
  }

  /**
   * converts a SimpleXMLElement into an array
   * @param SimpleXMLElement $xml
   * @return array
   */
  private function simpleXmlToArray(SimpleXMLElement $xml) {
    $data = array();
    $i = 0;
    foreach ($xml->children() as $name => $node) {
      if ((string)$node) {
        $data[$i][$name] = (string)$node;
      } else {
        foreach ($this->simpleXmlToArray($node) as $key => $value) {
          $id = false;
          foreach ($value as $k => $v) {
            if (!$id) {
              $id = $k;
              $data[$i][$k] = $v;
            } else {
              $data[$i][$id . '_' . $k] = $v;
            }
          }
        }
      }
      // add attributes
      foreach ($node->attributes() as $key => $value) {
        $data[$i][$key] = (string)$value;
      }
      $i++;
    }
    return $data;
  }

  public function setCacheUseOffForNextRequest() {
    $this->useCache = false;
  }

  private function useCache() {
    $cache = $this->useCache;
    $this->useCache = true;
    return $cache;
  }

  /**
   *
   * calls the API and returns a SimpleXMLElement
   * @param string $action
   * @param string $queries
   * @throws SuhvException
   * @return SimpleXMLElement
   */
  private function loadCurl($action, array $queries = null) {
    // check if curl is installed
    if (!function_exists('curl_init')) {
      throw new SuhvApiException('CURL is not installed on your server');
    }
    $ch = curl_init();

    if (!is_array($queries)) $queries = array();

    $queries['apikey'] = CFG_SUHV_API_KEY;

    $get = array();
    foreach ($queries as $key => $value) {
      if (is_null($value)) continue;
      $get[] = $key . '=' . rawurlencode($value);
    }
    $action .= '?' . implode('&', $get);

    $url = $this->getApiAddress() . $action;

    // get cache status
    $cache = $this->useCache();

    // check internal cache
    if ($cache && ($xml = $this->getResult($action))) {
      $this->addLog($action, 'internal');
      return $xml;
    }

    // check if the request is cached
    if ($cache && $xml = SuhvCacheManager::getCacheXml($action)) {
      $this->addLog($action, 'cache');
      $this->addResult($action, $xml);
      return $xml;
    }

    // set curl options url and that the result should be the return value
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    // get xml from suhv server
    $xml = curl_exec($ch);
    $info = curl_getinfo($ch);

    $this->addLog($action, $info['http_code']);

    // the curl request was not successfull, if the returned value is false or the http code is not 200
    if ($xml === false || $info['http_code'] != '200') {
      $error = curl_error($ch);
      if (empty($error)) $error = $info['http_code'];
      // check for connection error
      if ($info['http_code'] == 0 ) {
        throw new SuhvApiException('no connection to the internet');
      }
      if ($info['http_code'] == 400) {
        throw new SuhvBadRequestException('bad request - check queries');
      }
      if ($info['http_code'] == 404) {
        throw new SuhvNotFoundException('no results found');
      }
      // service unavailable or down
      if ($info['http_code'] == 503) {
        // try to get old cache file
        if ($xml = SuhvCacheManager::getCacheXml($action, false)) {
          return $xml;
        }
        throw new SuhvApiException('service down or unavailable');
      }
      throw new SuhvApiException("curl error: {$error}");
    }

    // close curl handler
    curl_close($ch);

    try {
      $xml = @new SimpleXMLElement($xml);
    } catch (Exception $ex) {
      throw new SuhvApiException('no xml returned: ' . $ex->getMessage());
    }

    // save result on object
    $this->addResult($action, $xml);

    // create new cache file
    SuhvCacheManager::saveFile($action, $xml);

    return $xml;
  }



  private static function getApiAddress() {
    return self::$apiAddress . self::$apiVersion . '/';
  }

  /**
   * simple function to validate a league code
   * @param int $leagueCode
   * @throws SuhvException
   * @return int
   */
  private function validateLeagueCode($leagueCode) {
    $leagueCode = intval($leagueCode);
    if ($leagueCode < 111 || $leagueCode > 599) {
      throw new SuhvException('invalid league code (' . $leagueCode . ')');
    }
    return $leagueCode;
  }

  private function validateStatus($status) {
    if (empty($status)) return null;
    return $status == 'planned' ? 'planned' : 'played';
  }

  private function validateOrder($order) {
    return $order == 'asc' ? 'asc' : 'desc';
  }

  private function validateSeason($season) {
    return (intval($season)) ? intval($season) : $this->getCurrentSeason();
  }

  private function addLog($url, $httpCode) {
    $url = str_replace(array('&apikey=' . urlencode(CFG_SUHV_API_KEY), '?apikey=' . urlencode(CFG_SUHV_API_KEY)), '', $url);
    $this->log[] = date('Y-m-d H:i:s') . " - {$httpCode} - {$url}";
  }

  public function getLog() {
    return $this->log;
  }

  private function addResult($action, SimpleXMLElement $xml) {
    $this->results[SuhvCacheManager::getName($action)] = $xml;
  }

  private function getResult($action, $getOld = false) {
    $name = SuhvCacheManager::getName($action);
    if (isset($this->results[$name])) {
      return $this->results[$name];
    }
    return null;
  }

  public function getResults() {
    return $this->results;
  }

  public static function getCurrentSeason() {
    $season = date('Y');
    if (date('m') < 6) {
      $season = $season - 1;
    }
    return $season;
  }


}

class SuhvCacheManager {

  public static function saveFile($action , SimpleXMLElement $xml) {
    $cacheFile = new SuhvCacheFile($xml);

    file_put_contents(self::getFilePath($action), serialize($cacheFile));
  }

  public static function getCacheXml($action, $checkExpiration = true) {
    $filePath = self::getFilePath($action);

    // return false, if there is no cache file
    if (!is_file($filePath)) {
      return false;
    }

    // get cache file from file
    $fileContents = file_get_contents($filePath);
    $cacheFile = @unserialize($fileContents);
    if (!$cacheFile) return false;
    $cacheFile instanceof SuhvCacheFile;

    // check expiration
    if ($checkExpiration && $cacheFile->isExpired()) {
      return false;
    }

    return $cacheFile->getXml();
  }

  public static function deleteCache() {
    foreach (glob(self::getCachePath() . DIRECTORY_SEPARATOR . '*') as $file) {
      unlink($file);
    }
  }

  private static function getFilePath($action) {
    return self::getCachePath() . DIRECTORY_SEPARATOR . self::getName($action);
  }

  public static function getName($action) {
    $action = str_replace(array('&apikey=' . CFG_SUHV_API_KEY, '?apikey=' . CFG_SUHV_API_KEY), '', $action);
    $name = str_replace(array('%20', '&', '=', "\\", '/', ':', '*', '?', '"', '<', '>', '|'), '-', $action);
    $name = preg_replace("/[^0-9a-z_-]/", '', strtolower($name));
    return $name;
  }

  private static function getCachePath() {
    $path = realpath(dirname(__FILE__) . CFG_SUHV_CACHE_DIR);
    if (!$path) {
      throw new SuhvException('cache dir not found. create cache dir: ' . dirname(__FILE__) . CFG_SUHV_CACHE_DIR);
    }
    if (!is_writable($path)) {
      throw new SuhvException('no write permission on cache path. set write permission on chache dir (' . $path . ')');
    }
    return $path;
  }
}

class SuhvCacheFile {
  private $creationDate;
  private $xml;

  public function __construct(SimpleXMLElement $xml) {
    $this->xml = $xml->asXML();
    $this->creationDate = date('Ymd');
  }

  public function isExpired() {
    return (date('Ymd') > $this->creationDate);
  }

  /**
   * @return SimpleXMLElement
   */
  public function getXml() {
    return simplexml_load_string($this->xml);
  }
}

class SuhvException extends RuntimeException {

}

class SuhvBadRequestException extends SuhvException {

}

class SuhvNotFoundException extends SuhvException {

}

class SuhvApiException extends SuhvException {

}
