<?php
namespace ABCD\RewriteHistory;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

// documentation:
//     http://localhost/redcap_v8.7.1/Plugins/index.php?page=ext_mods_docs

class RewriteHistory extends AbstractExternalModule {

    public function generateRewriteHistory() {
?>
        <div style="text-align: left; width: 100%">
            <div style="height: 50px;"></div>
        </div>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
        <script src="<?= $this->getUrl("/js/rewriteHistory.js") ?>"></script>
	
<?php

        $restrictedAccess = ($_REQUEST['page'] == 'executiveView' && (in_array(USERID, $this->getSystemSetting("executive-users")) || SUPER_USER) ? 1 : 0);
	// do we have to set title here?
        $title = "Rewrite History";
	//$pathparts = explode("/", $this->getModulePath());
	//array_pop($pathparts);
	//$path = array_pop($pathparts);
	// I need this:
	//    http://localhost/redcap_v8.7.1/ExternalModules/?prefix=rewrite_history&page=index

	echo("<script> home = \"".$this->getUrl("/index.php")."\"; </script>");
?>


  <h2><?php echo($title); ?></h2>
  <p>Start with a single item we want to rename. Once this works we are 100% there...</p>

  <div class="form-group">
    <label for="oldname">Project List</label>
    <select id="project-list" class="form-control">
	 <option></option>

<?php
	// get list of projects
	// do a sql query
	$query = "SELECT project_id,app_title,project_name FROM redcap_projects";
        $result = db_query($query);
	while ($row = db_fetch_assoc( $result ) ) {
	   echo("<option value='" . $row['project_id'] . "'>" . $row['app_title'] . "</option>");
	}
?>

    </select>
    <small aria-describedby="oldNameHelp" class="form-text text-muted">Yeah, only super users can do this right now...</small>
  </div>

  <div class="form-group">
    <label for="oldname">Old Item Name</label>
    <input type="text" class="form-control" id="oldname" aria-describedby="oldNameHelp" placeholder="Enter existing item name you want to replace">
    <small aria-describedby="oldNameHelp" class="form-text text-muted">Does this item exist? Where?</small>
  </div>

  <div class="form-group">
    <label for="newname">New Item Name</label>
    <input type="text" class="form-control" id="newname" aria-describedby="newNameHelp" placeholder="Enter the new name">
    <small aria-describedby="newNameHelp" class="form-text text-muted">Is this entry correctly formatted? Is it new - should not exist already.</small>
  </div>

<?php if (!$restricedAccess) : ?>
  <div class="form-group">
     <label><input type="checkbox" id="sure"> Are you sure?</label>&nbsp;
     <button id="start-dryrun" class="btn btn-danger">DryRun</button>
     <button id="start-rewrite" class="btn btn-danger">Start rewrite</button>
  </div>
<?php endif; ?>

  <textarea id="error-messages" title="debugging information" rows="30" cols="90"></textarea>

<?php
    }

    public function dryRun($oldVal, $newVal) {
       echo(json_encode(array('message' => 'rename: '.$oldVal.' into '.$newVal)));    
    }
    
    public function run() {
       echo(json_encode(array('message' => 'hi')));

    }

}
?>
