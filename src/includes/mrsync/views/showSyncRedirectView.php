<?php

// Controller: mrsyncController
// Action: redirecToSummary

print_cp_header($this->_vbphrase['mailrelay_user_sync']);
print_form_header('mrsync_admin', 'summary');
print_description_row('<strong>' . $this->_vbphrase['mrsync_sync_in_process'] .'</strong><br/><br/>');
construct_hidden_code('syncedNewUsers', $syncedNewUsers);
construct_hidden_code('syncedUpdatedUsers', $syncedUpdatedUsers);
construct_hidden_code('syncedDeletedUsers', $syncedDeletedUsers);
print_submit_row($this->_vbphrase['sync_send']);
print_form_auto_submit('cpform');
print_cp_footer();