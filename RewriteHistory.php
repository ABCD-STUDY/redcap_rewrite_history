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
        $title = "Rewrite History";

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

    // match a REDCap piping reference as [<string>] or [<string>:value] or [<string>:checked] or [<string>:unchecked]
    public function pipingRegExp( $str ) {
       return "\\\[".preg_quote($str)."(\\\:value|\\\:checked|\\\:unchecked)*\\\]";
    }

    public function dryRun($oldVal, $newVal, $project_id) {

       $results = array();

       //
       // check in the data table how often we have the old value
       //
       $query  = "SELECT field_name FROM redcap_data WHERE field_name = '".prep($oldVal)."' AND project_id = ".$project_id;
       $result = db_query($query);
       $count  = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
          // trivial replace
          $ar[] = array( "old" => $row['field_name'], "new" => $newVal );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does data exist for this item (field_name in redcap_data)?',
                          'redcap_data' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));

       //
       // check the data dictionary       
       //
       $query  = "SELECT field_name FROM redcap_metadata WHERE field_name = '".prep($oldVal)."' AND project_id = ".$project_id;
       $result = db_query($query);
       $count  = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
          $ar[] = array( "old" => $row['field_name'], "new" => $newVal );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the oldVar exist in data dictionary?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));


       //
       // check the data dictionary (new variable)
       //
       $query  = "SELECT field_name FROM redcap_metadata WHERE field_name = '".prep($newVal)."' AND project_id = ".$project_id;
       $result = db_query($query);
       $count  = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
          $ar[] = array( "old" => $row['field_name'], "new" => $newVal );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the newVar exist in data dictionary (field_name)?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));


       //
       // check the branching logic (old variable)
       //
       $query  = "SELECT branching_logic,field_name FROM redcap_metadata WHERE branching_logic REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $count  = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
          // ok, this is more complex. We need to replace the LIKE entry in the branching_logic column
	  // this needs to work even if the item name is part of another item name (leading/trailing stuff)
	  $v = $row['branching_logic'];
	  // here it is, should capture the piping structure with repeated qualifiers
	  // Question: is this working if there are more than one of these pattern in the same string?
	  $nv = preg_replace('/\['.preg_quote($oldVal).'(\\\:value|\\\:checked|\\\:unchecked)*\]/', '['.$newVal.'${1}]', $v);

          $ar[] = array( "old" => $row['branching_logic'], "new" => $nv );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the newVar exist in any branching logic (branching_logic)?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));       



       //
       // check the logs for any changes on this variable
       //
       $query  = "SELECT sql_log FROM redcap_log_event WHERE sql_log LIKE '%\\'".preg_quote($oldVal)."\\'%' AND project_id = ".$project_id;
       $result = db_query($query);
       $count  = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
	  $v  = $row['sql_log'];
	  $nv = preg_replace('/\''.preg_quote($oldVal).'\'/', '\''.$newVal.'\'', $v);

          $ar[]  = array( "old" => $row['sql_log'], "new" => $nv );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the newVar exist in the log (sql_log)?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));       


       //
       // check the logs for any changes on this variable REGEXP version (MySQL > 5.6)
       //
       $query = "SELECT data_values FROM redcap_log_event WHERE data_values REGEXP \"^".preg_quote($oldVal)." ="."\""." AND project_id = ".$project_id;
       $result = db_query($query);
       $count = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
	  $v  = $row['data_values'];
	  $nv = preg_replace('/^'.preg_quote($oldVal).' = /', $newVal.' = ', $v);
       
          $ar[] = array( "old" => $row['data_values'], "new" => $nv );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the newVar exist in the log (data_values, regexp)?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));       


       //
       // check the reports for any changes on this variable REGEXP version (MySQL > 5.6)
       //
       $query  = "SELECT field_name FROM redcap_reports_fields WHERE field_name REGEXP \"^".preg_quote($oldVal)."$\"";
       $result = db_query($query);
       $count  = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
	  $v  = $row['field_name'];
	  $nv = preg_replace('/^'.preg_quote($oldVal).'$/', $newVal, $v);

	  $ar[] = array( "old" => $row['field_name'], "new" => $nv );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the oldVar exist in any reports (field_name)?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));       

       //
       // check for piping (in element descriptions)
       // TODO: there references can contain appended ':value', ':checked', ':unchecked', 
       //
       $query = "SELECT element_label FROM redcap_metadata WHERE element_label REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $count = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
	  $v = $row['element_label'];
	  $nv = preg_replace('/\['.preg_quote($oldVal).'(\\\:value|\\\:checked|\\\:unchecked)*\]/', '['.$newVal.'\1]', $v);

          $ar[] = array( "old" => $row['element_label'], "new" => $nv );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the oldVar exist in any piping (element_label)?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));


       //
       // check for piping (in element note)
       //
       $query = "SELECT element_note FROM redcap_metadata WHERE element_note REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $count = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
	  $v = $row['element_note'];
	  $nv = preg_replace('/\['.preg_quote($oldVal).'(\\\:value|\\\:checked|\\\:unchecked)*\]/', '['.$newVal.'${1}]', $v);

	  $ar[] = array( "old" => $row['element_note'], "new" => $nv );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the oldVar exist in any piping (element_note)?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));



       //
       // check for piping (in tags)
       //
       $query = "SELECT misc FROM redcap_metadata WHERE misc REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $count = 0;
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
	  $v = $row['misc'];
	  $nv = preg_replace('/\['.preg_quote($oldVal).'(\\\:value|\\\:checked|\\\:unchecked)*\]/', '['.$newVal.'${1}]', $v);

	  $ar[] = array( "old" => $row['misc'], "new" => $nv );
          $count = $count + 1;
       }
       $results[] = array('type' => 'Does the oldVar exist in any piping (tags/misc)?',
                          'redcap_metadata' => $count,
                          'values' => $ar,
                          'query' => json_encode($query));


       echo(json_encode($results));
    }
    
    public function run() {
       echo(json_encode(array('message' => 'hi')));

    }

}
?>
