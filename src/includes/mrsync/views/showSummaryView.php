<?php

// Controller: mrsyncController
// Action: showSummary

print_cp_header($this->_vbphrase['mailrelay_user_sync']);
print_table_start();
print_table_header($this->_vbphrase['sync_result']);
print_description_row('<strong>' . $this->_vbphrase['synced_new_users'] . '</strong> ' . $syncedNewUsers . '' . $this->_vbphrase['sync_new_users_text'] . '<br/><br/>');
print_description_row('<strong>' . $this->_vbphrase['synced_updated_users'] . '</strong> ' . $syncedUpdatedUsers . '' . $this->_vbphrase['sync_update_users_text'] . '<br/><br/>');
print_description_row('<strong>' . $this->_vbphrase['deleted_synced_users'] . '</strong>: ' . $syncedDeletedUsers . '<br/><br/>');
print_table_footer();
print_cp_footer(); 