<?php

$page = new HtmlPage();
$page->PrintHeaderExt();
include APP_PATH_VIEWS . 'HomeTabs.php';

$rewriteHistory = new \ABCD\RewriteHistory\RewriteHistory();
$rewriteHistory->generateRewriteHistory();