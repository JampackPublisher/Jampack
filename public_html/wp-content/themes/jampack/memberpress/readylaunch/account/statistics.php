<?php if (!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
} else {
  $jampack_account = MeprCtrlFactory::fetch('JampackAccount');
  $nonce = wp_create_nonce('wp_rest');
} ?>

<script>
  fetch('/wp-json/<?php echo $jampack_account->analitycs_rest_route() ?>', {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': '<?php echo $nonce; ?>'
      },
    })
    .then(res => res.json())
    .then(({
      data
    }) => {
      const tableHTML = data.map(row => `
      <tr>
        <td>${row.time}</td>
        <td>${row.duration_display}</td>
        <td>${row.post_title}</td>
        <td>${row.value}</td>
        <td>${row.average}</td>
        <td>${row.votes}</td>
      </tr>
    `).join('');
      document.getElementById('rmp-user-analytics').innerHTML = `
      <table>
        <thead><tr><th>Date</th><th>Duration</th><th>Videogame</th><th>Rating</th></th><th>Average Rating</th><th>Total Votes</th></tr></thead>
        <tbody>${tableHTML}</tbody>
      </table>
    `;
    });
</script>

<div class="mp_wrapper">
  <h1 class="mepr_page_header"><?php echo esc_html_x('Statistics', 'ui', 'memberpress'); ?></h1>
  <div id="rmp-user-analytics">Loading your analytics...</div>
</div>