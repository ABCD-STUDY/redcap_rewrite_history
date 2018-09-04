<?php


// test if we are being called using query parameters
$action = "showPage";
if (isset($_POST['action'])) {
   $action = $_POST['action'];
}

if ( $action == "showPage" ) {
   $page = new HtmlPage();
   $page->PrintHeaderExt();
   include APP_PATH_VIEWS . 'HomeTabs.php';

   $rewriteHistory = new \ABCD\RewriteHistory\RewriteHistory();
   $rewriteHistory->generateRewriteHistory();
} else if ( $action == "runDry" ) {
   $oldVal = "";
   if (isset($_POST['oldVal'])) {
      $oldVal = $_POST['oldVal'];
   }
   $newVal = "";
   if (isset($_POST['newVal'])) {
      $newVal = $_POST['newVal'];
   }
   $project_id = "";
   if (isset($_POST['project_id'])) {
      $project_id = $_POST['project_id'];
   }
   $rewriteHistory = new \ABCD\RewriteHistory\RewriteHistory();
   $rewriteHistory->run($oldVal, $newVal, $project_id, TRUE);
} else if ( $action == "run" ) {
   $oldVal = "";
   if (isset($_POST['oldVal'])) {
      $oldVal = $_POST['oldVal'];
   }
   $newVal = "";
   if (isset($_POST['newVal'])) {
      $newVal = $_POST['newVal'];
   }
   $project_id = "";
   if (isset($_POST['project_id'])) {
      $project_id = $_POST['project_id'];
   }
   $rewriteHistory = new \ABCD\RewriteHistory\RewriteHistory();
   $rewriteHistory->run($oldVal, $newVal, $project_id, FALSE);
} else {
   echo("{ \"message\": \"unknown action\" }");
}

