<?php
namespace ABCD\RewriteHistory;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class RewriteHistory extends AbstractExternalModule {

    // rewrite for one item
    public static $rewriteData = array(
        "name" => "rewrite data entries",
        "sql" => "SELECT  * FROM redcap_data"
    );

    public function generateRewriteHistory() {

        ?>
        <div style="text-align: left; width: 100%">
            <div style="height: 50px;"></div>
        </div>

        <!-- <script src="<?= $this->getUrl("/resources/tablesorter/jquery.tablesorter.min.js") ?>"></script> -->

        <script src="<?= $this->getUrl("/rewriteHistory.js") ?>"></script>

        <?php

        $restrictedAccess = ($_REQUEST['page'] == 'executiveView' && (in_array(USERID, $this->getSystemSetting("executive-users")) || SUPER_USER) ? 1 : 0);
	// do we have to set title here?
        $title = "SOMETHING";
	
        ?>
        <h2><?php echo($title); ?></h2>
        <p>Lets start with a single item we want to rename.</p>

  <div class="form-group">
    <label for="oldname">Old Name</label>
    <input type="text" class="form-control" id="oldname" aria-describedby="oldNameHelp" placeholder="Enter name you want to replace">
    <small aria-describedby="oldNameHelp" class="form-text text-muted">This is for testing only - a spreadsheet renaming will likely follow.</small>
  </div>

  <div class="form-group">
    <label for="newname">Old Name</label>
    <input type="text" class="form-control" id="newname" aria-describedby="newNameHelp" placeholder="Enter name you want to replace">
    <small aria-describedby="newNameHelp" class="form-text text-muted">This is for testing only - a spreadsheet renaming will likely follow.</small>
  </div>

  <textarea id="error-messages" title="debugging information"></textarea>

?>
<?php


?>