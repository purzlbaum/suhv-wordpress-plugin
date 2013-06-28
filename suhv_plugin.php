<?php
/*

Plugin Name: SUHV Plugin
Plugin URI: http://claudioschwarz.com
Description: Connects to the API of swissunihockey.ch and serves data. <strong> Tanks to <a href="http://zepi.org">Matthias Zobrist</a> and <a href="http://pascalbirchler.ch">Pascal Birchler</a> for their help.</strong>
Version: 1.0
Author: Claudio Schwarz
Author URI: http://claudioschwarz.com
*/


function setup_theme_admin_menus()
{
  add_submenu_page('options-general.php',
    'SUHV Plugin', 'SUHV Plugin', 'manage_options',
    'suhv-plugin-settings', 'suhv_plugin_page_settings');
}

add_action('admin_menu', 'setup_theme_admin_menus');

function suhv_plugin_page_settings()
{
  /* Speichern */

  if (isset($_POST['update_settings_api_key'])) {

    $apiKey = esc_attr($_POST['api_key_suhv']);
    update_option('api_key_suhv', $apiKey);

  }

  if (isset($_POST['update_settings_club'] )) {

    $clubName = esc_attr($_POST['clubname']);
    update_option('clubname', $clubName);

  }

  if (isset($_POST['update_settings_customcss'] )) {

    $customCss = esc_attr($_POST['customcss']);
    update_option('customcss', $customCss);

  }



  $apiKeySaved = get_option('api_key_suhv');

  $url = get_bloginfo('wpurl');

  $pluginSettingsContent = ''
    . '<div class="wrap">'
    .   '<h2>SUHV Plugin Einstellungen</h2>'
    .   '<h3>API Key  (<a target="_blank" href="http://api.swissunihockey.ch/webservices/registration">Registrieren</a>)</h3>'
    .   '<form method="POST" action="'.$url.'/wp-admin/options-general.php?page=suhv-plugin-settings">'
    .     '<table class="form-table">'
    .       '<tr valign="top">'
    .         '<th scope="row">'
    .           '<label for="api_key_suhv">API Key</label>'
    .         '</th>'
    .         '<td>'
    .           '<input type="text" name="api_key_suhv" value="'.$apiKeySaved.'" size="35" />'
    .         '</td>'
    .       '</tr>'
    .     '</table>'
    .     '<p>'
    .       '<input type="submit" value="Einstellungen speichern" class="button-primary"/>'
    .       '<input type="hidden" name="update_settings_api_key" value="Y" />'
    .     '</p>'
    .   '</form>'
    . '</div>';
  if (!empty($apiKeySaved)) {
    include_once('lib/_api_key.php');
    include_once('lib/suhv_lib.php');

    $pluginSettingsContent .= ''
      . '<div class="wrap">'
      .   '<h3>Clubauswahl</h3>'
      .   '<form method="POST" action="'.$url.'/wp-admin/options-general.php?page=suhv-plugin-settings">'
      .     '<table class="form-table">'
      .       '<tr valign="top">'
      .         '<th scope="row">'
      .           '<label for="clubname">Club</label>'
      .         '</th>'
      .         '<td>';
    $clubs = SuhvClub::getAllClubs();
    $pluginSettingsContent .= ''
      .           '<select name="clubname">'
      .             '<optgroup>';
    foreach ($clubs as $club) {
      if (!isset($club['club'])) continue;
      $clubNameSaved = get_option('clubname');
      $pluginSettingsContent .= ''
      .                 '<option value="'.$club['club'].'" '.selected($club['club'], $clubNameSaved, false).'>'.$club['club'].'</option>';
    }

    $customCssSaved = get_option('customcss');
    $pluginSettingsContent .= ''
      .              '</optgroup>'
      .           '</select>'
      .         '</td>'
      .       '</tr>'
      .     '</table>'
      .     '<p>'
      .       '<input type="submit" value="Einstellungen speichern" class="button-primary"/>'
      .       '<input type="hidden" name="update_settings_club" value="Y" />'
      .     '</p>'
      .   '</form>'
      .   '<form method="POST" action="'.$url.'/wp-admin/options-general.php?page=suhv-plugin-settings">'
      .     '<h3>Möchtest du dein eigenes CSS verwenden?</h3>'
      .     '<table class="form-table">'
      .       '<tr valign="top">'
      .         '<th scope="row">'
      .           '<label for="customcss">Custom CSS</label>'
      .         '</th>'
      .         '<td>'
      .           '<input type="checkbox" id="customcss" name="customcss" '.checked('on', $customCssSaved, false).'>'
      .         '</td>'
      .       '</tr>'
      .     '</table>'
      .     '<p>'
      .       '<input type="submit" value="Einstellungen speichern" class="button-primary"/>'
      .       '<input type="hidden" name="update_settings_customcss" value="Y" />'
      .     '</p>'
      .   '</form>'
      .   '<br />'
      .   '<p>Herzlichen Dank an n2n für das <a href="http://www.n2n.ch/de/suhv-framework" title="SUHV-Framework">SUHV-Framework</a>.</p>'
      . '</div>';


  }

  echo $pluginSettingsContent;

  ?>
  <script>
    function sortAlpha(a,b){
      return a.innerHTML.toLowerCase() > b.innerHTML.toLowerCase() ? 1 : -1;
    };

    jQuery('optgroup option').sort(sortAlpha).appendTo('optgroup');
  </script>
  <?php
}

include_once('widget/suhv_widget.php');

$customCssSaved = get_option('customcss');
if ($customCssSaved != 'on') {
  add_action('wp_head', 'suhv_css');
  function suhv_css() {
    echo '<link rel="stylesheet" href="/wp-content/plugins/suhv_plugin/css/suhv_plugin.css">';
  }
}
