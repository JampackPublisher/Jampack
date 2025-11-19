<?php 
if (!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
} 

if(!$is_current_user_developer && !is_super_admin()) {
  wp_redirect(MeprUtils::get_account_url());
}
?>

<div id="loading-screen">
  <div class="spinner"></div>
</div>

<div class="mp_wrapper" id="main-content" style="display: none;">
  <h1 class="mepr_page_header"><?php echo esc_html_x('Statistics', 'ui', 'memberpress'); ?></h1>
  <table id="analytics-table">
    <thead>
      <tr>
        <!-- <th class="sortable" data-column="time">Date</th> -->
        <th class="sortable" data-column="post">Videogame</th>
        <!-- <th>Duration</th> -->
        <th class="sortable" data-column="average">Average Rating</th>
        <th class="sortable" data-column="votes">Total Votes</th>
        <!-- <th class="sortable" data-column="value">Rating</th> -->
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <div id="analytics-pagination"></div>
</div>