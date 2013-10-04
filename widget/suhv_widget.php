<?php
/* Function that registers our widget. */


function suhv_widgets()
{
  register_widget( 'Suhv_Widgets' );
}

class Suhv_Widgets extends WP_Widget
  {
    function Suhv_Widgets()
    {
      /* Widget settings. */
      $widget_ops = array( 'classname' => 'suhv-widget', 'description' => 'Kann Daten von swissunihockey.ch anzeigen' );

      /* Widget control settings. */
      $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'suhv-widget' );

      /* Create the widget. */
      $this->WP_Widget( 'suhv-widget', 'SUHV Widget', $widget_ops, $control_ops );

    }

  function widget( $args, $instance )
  {
    extract( $args );

    // load library and api key
    include_once(__DIR__ . '/../lib/_api_key.php');
    include_once(__DIR__ . '/../lib/suhv_lib.php');

    // our variables from the widget settings.
    $teamId = apply_filters('teamid', $instance['teamid'] );

    $games = apply_filters('games', $instance['games'] );
    $resultsCup = apply_filters('resultscup', $instance['resultscup'] );
    $resultsChampionsship = apply_filters('resultschampionsship', $instance['resultschampionsship'] );
    $rankings = apply_filters('rankings', $instance['rankings'] );

    // variables for league, games and so on
    $clubNameSaved = get_option('clubname');
    $clubId = SuhvClub::getClubIdByName($clubNameSaved);
    $club = new SuhvClub($clubId);
    $teams = $club->getTeams();
    $team = $teams[$teamId];

    echo $before_widget;

    // Display the widget title
    if ($teamId)

      echo $before_title . $team->getTeam() . $after_title;

    // setup for display
    include_once('suhv_gameplan.php');
    include_once('suhv_results_championship.php');
    include_once('suhv_results_cup.php');
    include_once('suhv_rankings.php');
    echo $after_widget;
  }


  /**
   * Update the widget settings.
   */
  function update( $new_instance, $old_instance )
  {
    $instance = $old_instance;

    //Strip tags from title and name to remove HTML
    $instance['teamid'] = strip_tags( $new_instance['teamid'] );
    $instance['games'] = strip_tags( $new_instance['games'] );
    $instance['gamescount'] = strip_tags( $new_instance['gamescount'] );
    $instance['resultscup'] = strip_tags( $new_instance['resultscup'] );
    $instance['resultcupcount'] = strip_tags( $new_instance['resultcupcount'] );
    $instance['resultschampionsship'] = strip_tags( $new_instance['resultschampionsship'] );
    $instance['resultschampionsshipcount'] = strip_tags( $new_instance['resultschampionsshipcount'] );
    $instance['rankings'] = strip_tags( $new_instance['rankings'] );

    return $instance;
  }

  /**
   * Displays the widget settings controls on the widget panel.
   * Make use of the get_field_id() and get_field_name() function
   * when creating your form elements. This handles the confusing stuff.
   */
  function form($instance)
  {
    include_once(__DIR__ . '/../lib/_api_key.php');
    include_once(__DIR__ . '/../lib/suhv_lib.php');

    //Set up some default widget settings.
    $defaults = array( 'teamid' => '', 'games' => '','resultscup' => '','resultschampionsship' => '', 'rankings' => '', 'show_info' => true );
    $instance = wp_parse_args( (array) $instance, $defaults );

    $cid = isset($_GET['cid']) ? $_GET['cid'] : null;

    if (!$cid) $cid = SuhvClub::getClubIdByName();

    $club = new SuhvClub($cid);

    try {
      $teams = $club->getTeams();
    } catch (SuhvNotFoundException $ex) {
      $teams = array();
    }

    $sortedTeams = array();
    foreach ($teams as $team) {
      $sortedTeams[$team->getLeague() . sprintf('%02d', $team->getGroup())] = $team;
    }
    ksort($sortedTeams);


    echo '<table>';
    echo '<tr>';
    echo '<td>';
    echo '<label>Team</label>';
    echo '</td>';
    echo '<td style="padding-left: 10px;">';
    echo '<select name="'.$this->get_field_name('teamid').'">';
    echo '<optgroup>';
    
    $teamIdSaved = $instance['teamid'];

    foreach ($sortedTeams as $team) {
      echo '<option value="'.$team->getId().'" '.selected($team->getId(), $teamIdSaved, true).'>'.$team->getTeam().'</option>';
    }

    echo '</optgroup>';
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    $optionGameSaved = $instance['games'];
    $optionGameCountSaved = $instance['gamescount'];
    $optionResultsCupSaved = $instance['resultscup'];
    $optionResultsCupCountSaved = $instance['resultcupcount'];
    $optionResultsChampionsshipSaved = $instance['resultschampionsship'];
    $optionResultsChampionsshipCountSaved = $instance['resultschampionsshipcount'];
    $optionRankingsSaved = $instance['rankings'];

    echo '<table style="margin-top: 20px; width: 100%;">';
    echo '<tr>';
    echo '<td>';
    echo '<input type="checkbox" id="'.$this->get_field_id('games').'" name="'.$this->get_field_name('games').'" '.checked('on', $optionGameSaved, false).' >';
    echo '</td>';
    echo '<td style="padding-left: 30px;">';
    echo '<label for="'.$this->get_field_id('games').'">Spielplan</label>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<input style="width: 40px;" id="'.$this->get_field_id('gamescount').'" name="'.$this->get_field_name('gamescount').'" value="' . $optionGameCountSaved . '">';
    echo '</td>';
    echo '<td style="padding-left: 30px;">';
    echo '<label for="'.$this->get_field_id('gamescount').'">Anzahl Spiele</label>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="2">';
    echo '<hr>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<input type="checkbox" id="'.$this->get_field_id('resultscup').'" name="'.$this->get_field_name('resultscup').'" '.checked('on', $optionResultsCupSaved, false).'>';
    echo '</td>';
    echo '<td style="padding-left: 30px;">';
    echo '<label for="'.$this->get_field_id('resultscup').'">Resultate Cup</label>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<input style="width: 40px;" id="'.$this->get_field_id('resultcupcount').'" name="'.$this->get_field_name('resultcupcount').'" value="' . $optionResultsCupCountSaved . '">';
    echo '</td>';
    echo '<td style="padding-left: 30px;">';
    echo '<label for="'.$this->get_field_id('resultcupcount').'">Anzahl Spiele</label>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="2">';
    echo '<hr>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<input type="checkbox" id="'.$this->get_field_id('resultschampionsship').'" name="'.$this->get_field_name('resultschampionsship').'" '.checked('on', $optionResultsChampionsshipSaved, false).'>';
    echo '</td>';
    echo '<td style="padding-left: 30px;">';
    echo '<label for="'.$this->get_field_id('resultschampionsship').'">Resultate Meisterschaft</label>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<input style="width: 40px;" id="'.$this->get_field_id('resultschampionsshipcount').'" name="'.$this->get_field_name('resultschampionsshipcount').'" value="' . $optionResultsChampionsshipCountSaved . '">';
    echo '</td>';
    echo '<td style="padding-left: 30px;">';
    echo '<label for="'.$this->get_field_id('resultschampionsshipcount').'">Anzahl Spiele</label>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="2">';
    echo '<hr>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<input type="checkbox" id="'.$this->get_field_id('rankings').'" name="'.$this->get_field_name('rankings').'" '.checked('on', $optionRankingsSaved, false).'>';
    echo '</td>';
    echo '<td style="padding-left: 30px;">';
    echo '<label for="'.$this->get_field_id('rankings').'">Rangliste</label>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
  }
}
/* Add our function to the widgets_init hook. */
add_action( 'widgets_init', 'Suhv_Widgets' );
