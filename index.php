<?php

$page = new HtmlPage();
$page->PrintHeaderExt();
include APP_PATH_VIEWS . 'HomeTabs.php';

$rewriteHistory = new \ABCD\RewriteHistory\RewriteHistory();

// test if we are being called using query parameters
$action = "showPage";
if (isset($_POST['action'])) {
   $action = $_POST['action'];
}

if ( $action == "showPage" ) {
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
   $rewriteHistory->dryRun($oldVal, $newVal);
} else if ( $action == "run" ) {
   $rewriteHistory->run();
} else {
   echo("{ \"message\": \"unknown action\" }");
}

