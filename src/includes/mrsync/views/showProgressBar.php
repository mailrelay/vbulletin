<?php

// Controller: mrsync_admin.php
// Action: sync

print_cp_header($vbphrase['mailrelay_user_sync']);
print_form_header('mrsync_admin', 'sync');
print_description_row('<strong>' . $vbphrase['mrsync_sync_in_process'] . $vbphrase['dont_close_window'] . '</strong><br/><br/>');

print_description_row($vbphrase['syncing'] . ': ' . $page . ' ' . $vbphrase['users_of_a_total_of'] . ': ' . $numUsers . '<br/><br/>');

print_description_row('<div id="progress_bar" class="ui-progress-bar ui-container" style="width: 500px;">
                        <div class="ui-progress" style="width:' . ($page * 100 / $numUsers) . '%;">
                            <span class="ui-label" style="display:none;">' . $vbphrase['processing'] . ' <b class="value">' . ($page * 100 / $numUsers) . '%</b></span></div>
                       </div>');

print_description_row('<br/><br/>');

construct_hidden_code('syncedNewUsers', $syncedNewUsers);
construct_hidden_code('syncedUpdatedUsers', $syncedUpdatedUsers);
construct_hidden_code('syncedDeletedUsers', $syncedDeletedUsers);


construct_hidden_code('vBulletinGroups[]', $vBulletinGroups);
construct_hidden_code('vBulletinSocialGroups[]', $vBulletinSocialGroups);
construct_hidden_code('groups[]', $groups);
construct_hidden_code('hostname', $hostname);
construct_hidden_code('apiKey', $apiKey);

$page = $page + $limit;
construct_hidden_code('page', $page);


print_submit_row($vbphrase['sync_send']);
print_form_auto_submit('cpform');
print_cp_footer();