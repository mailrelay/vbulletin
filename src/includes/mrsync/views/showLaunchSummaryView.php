<?php

// Controller: mrsyncLauchController
// Action: launchCampaign

print_cp_header($this->_vbphrase['campaign_launch_summary']);
print_table_start();
print_table_header($this->_vbphrase['campaign_launch_result']);
print_description_row($message . '<br/><br/>');
print_table_footer();
print_cp_footer(); 