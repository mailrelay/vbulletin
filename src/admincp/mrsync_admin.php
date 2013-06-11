<?php
/**
 * @package MRSync for vBulletin
 * @author Jose Argudo Blanco
 * @website www.consultorpc.com
 * @email jose@consultorpc.com
 * @version 1.0.0
 * @date 26/08/11
 * @copyright ConsultorPC
 * @license Proprietary
**/

require_once('./global.php');
require_once(DIR . '/includes/mrsync/mrsyncInit.php');

 // Look for params or set a default
 // 'do' is the default param name generated by vBulletin print_form_header function
 if ( empty($_REQUEST['do']) ) {
    $_REQUEST['do'] = 'showLogin';
}

 // Syncing is a four steps process, here you can find:
 // Step 1, showLogin: this action shows the login form
 // Step 2, authenticate: here we authenticate, get possible groups and show a form where users can select groups to sync
 // Step 3, sync: here takes part the sync process
 // Step 4, summary: and then show a summary of the process
 switch( $_REQUEST['do'] ) {
    case 'authenticate':
         // Step 2
         // Authenticate the user against the API
         // Get all existing groups
        try {
            list( $hostname, $apiKey ) = array( $_REQUEST['hostname'], $_REQUEST['key'] );

            $mrsyncController->checkRequiredFormValues( $hostname, $apiKey );
            $mrsyncController->initCurl( $hostname, $apiKey );

            $mrsyncController->showGroupsForm( $hostname, $apiKey, $mrsyncController->getGroups(), $mrsyncController->getVbulletinGroups(), $mrsyncController->getVbulletinSocialGroups()  );

        } catch ( Exception $e ) {
            echo '(mrsync_admin.php) An error has been found in step 2: ' . $e->getMessage();
        }
        break;

    case 'sync':
        // Step 3
        // Start user sync process
        try {
            session_start();

            list( $vBulletinGroups, $vBulletinSocialGroups, $groups, $hostname, $apiKey ) = array( $_REQUEST['vBulletinGroups'], $_REQUEST['vBulletinSocialGroups'], $_REQUEST['groups'], $_REQUEST['hostname'], $_REQUEST['apiKey'] );

            $mrsyncController->checkAtLeastOneGroupWasSelected( $groups );

            $numUsers = $mrsyncController->countUsers();

            if ( !empty($_REQUEST['page']) ) {
                $page = $_REQUEST['page'];
            } else {
                $_SESSION['groups'] = $groups;
                $page = 0;
            }

            $users = $mrsyncController->getAllUsers( $vBulletinGroups, $vBulletinSocialGroups, $page, $limit );

            $curl = $mrsyncController->initCurl( $hostname, $apiKey );

            $result = $mrsyncController->syncUsers( $users, $apiKey, $_SESSION['groups'], $page );

            list( $syncedNewUsers, $syncedUpdatedUsers, $syncedDeletedUsers ) = $result;


            /*if( $syncedNewUsers == 1 || $syncedUpdatedUsers == 1 || $syncedDeletedUsers == 1 ) {
                echo '1';
                exit();
            }*/

            if ( !empty($_REQUEST['syncedNewUsers']) ) {
                $syncedNewUsers = $syncedNewUsers + $_REQUEST['syncedNewUsers'];
            }

            if ( !empty($_REQUEST['syncedUpdatedUsers']) ) {
                $syncedUpdatedUsers = $syncedUpdatedUsers + $_REQUEST['syncedUpdatedUsers'];
            }

            if ( !empty($_REQUEST['syncedDeletedUsers']) ) {
                $syncedDeletedUsers = $syncedDeletedUsers + $_REQUEST['syncedDeletedUsers'];
            }

            require_once(DIR . '/includes/mrsync/views/progressBarStyles.php');

            if ($page < $numUsers) {
                require_once(DIR . '/includes/mrsync/views/showProgressBar.php');
            } else {
                $mrsyncController->redirecToSummary( array($syncedNewUsers, $syncedUpdatedUsers, $syncedDeletedUsers) );
            }

        } catch ( Exception $e ) {
            echo '(mrsync_admin.php) An error has been found in step 3: ' . $e->getMessage();
        }
        break;
    case 'summary':
        // Step 4
        try {
            $mrsyncController->showSummary( $_REQUEST['syncedNewUsers'], $_REQUEST['syncedUpdatedUsers'], $_REQUEST['syncedDeletedUsers']  );
        } catch ( Exception $e ) {
            echo '(mrsync_admin.php) An error has been found in step 4: ' . $e->getMessage();
        }

        break;
    case 'showLogin':
    default:
        // Step 1
        try {
            $settingsId = $mrsyncController->checkIfSavedSettingsExist();
            $mrsyncController->showLogin( $_REQUEST['message'],  $settingsId );
        } catch ( Exception $e ) {
            echo '(mrsync_admin.php) An error has been found in step 1: ' . $e->getMessage();
        }
}