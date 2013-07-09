<?php

// This file is part of the Huygens Remote Manager
// Copyright and license notice: see license.txt

require_once '../inc/User.inc.php';
require_once '../inc/JobQueue.inc.php';
require_once '../inc/Database.inc.php';
require_once '../inc/System.inc.php';
require_once '../inc/Mail.inc.php';

// This is not strictly necessary for the Ajax communication, but will be 
// necessary for accessing session data to create the response.
session_start();

// If the user is not logged on, we return without doing anything
if (!isset($_SESSION['user']) || !$_SESSION['user']->isLoggedIn()) {
    return;
}

// ============================================================================
//
// PROCESS THE POSTED ARGUMENTS
//
// ============================================================================

// Check that we have a valid request
if (!isset($_POST)) {
    die("Nothing POSTed!");
}

// Do we jave a JSON-RPC 2.0 request? We do NOT test for the value of id.
if (!(isset($_POST['id']) &&
        isset($_POST['jsonrpc']) && $_POST['jsonrpc'] == "2.0")) {

    // Invalid JSON-RPC 2.0 call
    die("Invalid JSON-RPC 2.0 call.");
};

// Do we have a method with params?
if (!isset($_POST['method']) && !isset($_POST['params'])) {

    // Expected 'method' and 'params'
    die("Expected 'method' and 'params'.");
}

// Get the method
$method = $_POST['method'];

// TODO
// Currently, none of the implemented method require arguments.
// For methods that require input parameters, these can be obtained as follows:
//
// $params = $_POST['params'];
//
// where $params is an array.

// Call the requested method and collect the JSON-encoded response
switch ($method) {
    
    case 'jsonGetUserAndTotalNumberOfJobsInQueue':
        
        $json = jsonGetUserAndTotalNumberOfJobsInQueue();
        break;
      
    case 'jsonCheckForUpdates':
      
        $json = jsonCheckForUpdates();
        break;

    case 'jsonSendTestEmail':
      
        $json = jsonSendTestEmail();
        break;
    
    default:
        
        // Unknown method
        die("Unknown method.");
}

// Return the JSON object
header("Content-Type: application/json", true);
echo $json;

return true;

// ============================================================================
//
// METHOD IMPLEMENTATIONS
//
// ============================================================================

/**
 * Create default (PHP) array with "success" and "message" properties. Methods 
 * should initialize their JSON output array with this function, to make sure 
 * that there are a "success" and a "message" properties in the returned object
 * (defaulting to "true" and "", respectivey) and then expand it as needed. 
 * 
 * Before the method functions return, they must call json_encode() on it!
 * 
 * The two valid values for the property "success" are the strings (and not
 * booleans!) "true" and "false".
 * 
 * @return PHP array with "success" = "true" and "message" = "" properties.
 */
function initJSONArray() {

    // Initialize the JSON array with success
    return (array("success" => "true", "message" => ""));
}

/**
 * Get the total number and the number of jobs owned by the specified user
 * currently in the queue. 
 *
 * @return JSON-encoded array with keys 'numAllJobsInQueue' and 'numUserJobsInQueue'
 */
function jsonGetUserAndTotalNumberOfJobsInQueue() {

    // Prepare the output array
    $json = initJSONArray();
    
    // Get the total number of jobs
    $db = new DatabaseConnection();
    $json["numAllJobsInQueue"] = $db->getTotalNumberOfQueuedJobs();
    
    // Get the number of jobs for current user
    $user = $_SESSION['user'];
    if ($user->isAdmin()) {
        $numUserJobsInQueue = 0;
    } else {
        $numUserJobsInQueue = $user->numberOfJobsInQueue();
    }
    $json["numUserJobsInQueue"] = $numUserJobsInQueue;
    
    // Also add time of update
    $json["lastUpdateTime"] = date('H:i:s');
    
    // Return as a JSON string
    return (json_encode($json));
}


/**
 * Check whether there is an update for the HRM. 
 * @return JSON-encoded array with key 'newerVersionExist' and 'newVersion'
 */
function jsonCheckForUpdates() {

  // Prepare the output array
  $json = initJSONArray();

  try {
    
    // Check if there is a newer version
    $isNew = System::isThereNewHRMRelease();
    
    if ($isNew) {
        $json["newerVersionExist"] = "true";
        $json["newVersion"] = System::getLatestHRMVersionFromRemoteAsString();
    } else {
        $json["newerVersionExist"] = "false";
        $json["newVersion"] = "";
    }

  } catch (Exception $e) {
      $json["success"] = "false";
      $json["message"] = $e->getMessage();
      $json["newerVersionExist"] = "false";
      $json["newVersion"] = "";
  }

  // Return as a JSON string
  return (json_encode($json));
}

/**
 * Send a test email to the administrator to check that the email system is set
 * up properly.
 * @return JSON-encoded array with key 'success' and 'message'
 */
function jsonSendTestEmail() {

  // Include configuration file
  include( dirname( __FILE__ ) . "/../config/hrm_client_config.inc" );
  
  // Prepare the output array
  $json = initJSONArray();

  // Send an email
  $mail = new Mail($email_sender);
  $mail->setReceiver($email_admin);
  $mail->setSubject('HRM test e-mail');
  $mail->setMessage('Congratulations! You have successfully ' .
          'configured your e-mail server!');
  if ($mail->send()) {
      $json['success'] = "true";
      $json['message'] = "Sent!";
  } else {
      $json['success'] = "false";
      $json['message'] = "Failed!";
  }

  // Return as a JSON string
  return (json_encode($json));
}

?>