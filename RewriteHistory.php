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
        // do a sql query (is there a better way using REDCap::?)
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
    // or [<string>[:value|:checked|:unchecked]]
    //   [SQL]
    public function pipingRegExp( $str ) {
       return "\\\[".preg_quote($str)."(\\\:value|\\\:checked|\\\:unchecked)*\\\]";
    }

    // replace variable name in piping structure like [a] or [a:value] or [a:value:checked:unchecked]
    public function replacePiping( $oldVal, $newVal, $v) {
       return preg_replace('/\['.preg_quote($oldVal).'(:value|:checked|:unchecked)?(:value|:checked|:unchecked)?(:value|:checked|:unchecked)?\]/', '['.$newVal.'$1$2$3]', $v);
    }

    // During a dry run no actual changes are performed, instead a list of suggested changes towards the database
    // is generated (shown in the textfield).
    public function dryRun($oldVal, $newVal, $project_id) {


       //
       // check the data dictionary for the new variable - refuse to do something if this variable exists already
       //
       $query  = "SELECT field_name FROM redcap_metadata WHERE field_name = '".prep($newVal)."' AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $a = array( "old" => $row['field_name'], "new" => $newVal );
           $ar[] = $a;
       }
       if (count($ar) > 0) {
           echo(json_encode(array('message' => 'Error: the new variable already exists in this redcap database')));
           return;
       }
        
       $results = array();

       //
       // check in the data table how often we have the old value
       //
       $query  = "SELECT field_name FROM redcap_data WHERE field_name = '".prep($oldVal)."' AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while ($row = db_fetch_assoc( $result ) ) {
           // trivial replace
           $a = array( "old" => $row['field_name'],
                       "new" => $newVal
           );
           $a["update"] = "UPDATE IGNORE redcap_data SET field_name = '".$a['new']."' WHERE field_name = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does data exist for this item (field_name in redcap_data for different records)?',
                          'redcap_data' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));

       //
       // check the data dictionary       
       //
       $query  = "SELECT field_name FROM redcap_metadata WHERE field_name = '".prep($oldVal)."' AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $a = array( "old" => $row['field_name'],
                       "new" => $newVal
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata SET field_name = '".$a['new']."' WHERE field_name = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in data dictionary?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));


       //
       // check the branching logic (old variable)
       //
       $query  = "SELECT branching_logic,field_name FROM redcap_metadata WHERE branching_logic REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           // ok, this is more complex. We need to replace the LIKE entry in the branching_logic column
           // this needs to work even if the item name is part of another item name (leading/trailing stuff)
           $nv = self::replacePiping( $oldVal, $newVal, $row['branching_logic']);
           
           $a = array( "old" => $row['branching_logic'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata SET branching_logic = '".$a['new']."' WHERE branching_logic = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any branching logic (branching_logic)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));       


       //
       // check the logs for any changes on this variable
       //
       $query  = "SELECT sql_log FROM redcap_log_event WHERE sql_log LIKE '%\\'".preg_quote($oldVal)."\\'%' AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $nv = preg_replace('/\''.preg_quote($oldVal).'\'/', '\''.$newVal.'\'', $row['sql_log']);
           $a  = array( "old" => $row['sql_log'],
                        "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_log_event SET sql_log = '".$a['new']."' WHERE sql_log = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in the log (sql_log)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));       


       //
       // check the logs for any changes on this variable REGEXP version (MySQL > 5.6)
       //
       $query = "SELECT data_values FROM redcap_log_event WHERE data_values REGEXP \"^".preg_quote($oldVal)." ="."\""." AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $nv = preg_replace('/^'.preg_quote($oldVal).' = /', $newVal.' = ', $row['data_values']);
           
           $a = array( "old" => $row['data_values'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_log_event SET data_values = '".$a['new']."' WHERE data_values = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in the log (data_values, regexp)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));       


       //
       // check the reports for any changes on this variable
       //       
       $query  = "SELECT report_id,project_id FROM redcap_reports WHERE project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
         $query2  = "SELECT field_name FROM redcap_reports_fields WHERE field_name REGEXP \"^".preg_quote($oldVal)."$\" AND report_id = ".$row['report_id'];
         $result2 = db_query($query2);
         while($row2 = db_fetch_assoc( $result2 ) ) {
           $nv = preg_replace('/^'.preg_quote($oldVal).'$/', $newVal, $row2['field_name']);

           $a = array( "old" => $row2['field_name'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_reports_fields SET field_name = '".$a['new']."' WHERE field_name = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
         }
       }
       $results[] = array('type' => 'Does the oldVar exist in any redcap_reports_fields (field_name)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));       

       //
       // check for piping (in element descriptions)
       // TODO: there references can contain appended ':value', ':checked', ':unchecked', 
       //
       $query = "SELECT element_label FROM redcap_metadata WHERE element_label REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $nv = self::replacePiping( $oldVal, $newVal, $row['element_label']);
           
           $a = array( "old" => $row['element_label'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata SET element_label = '".$a['new']."' WHERE element_label = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any piping (element_label)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));


       //
       // check for piping (in element note)
       //
       $query = "SELECT element_note FROM redcap_metadata WHERE element_note REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $nv = self::replacePiping( $oldVal, $newVal, $row['element_note']);
           
           $a = array( "old" => $row['element_note'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata SET element_note = '".$a['new']."' WHERE element_note = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any piping (element_note)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));


       //
       // check for piping (in tags)
       //
       $query = "SELECT misc FROM redcap_metadata WHERE misc REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $nv = self::replacePiping( $oldVal, $newVal, $row['misc']);          
           $a = array( "old" => $row['misc'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata SET misc = '".$a['new']."' WHERE misc = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any piping (tags/misc)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));


       //
       // check for log events
       //
       $query = "SELECT data_values FROM redcap_log_event WHERE data_values REGEXP \"\\'".preg_quote($oldVal)."\\'\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           // $nv = self::replacePiping( $oldVal, $newVal, $row['data_values']);
           $nv = preg_replace('/\''.preg_quote($oldVal).'\'/', '\''.$newVal.'\'', $row['data_values']);
           
           $a = array( "old" => $row['data_values'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_log_event SET data_values = '".$a['new']."' WHERE data_values = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any log events (data_values)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));


       //
       // check for log events
       //
       $query = "SELECT pk FROM redcap_log_event WHERE pk = \"".preg_quote($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           //$nv = self::replacePiping( $oldVal, $newVal, $row['pk']);
           $nv = preg_replace('/'.preg_quote($oldVal).'/', $newVal, $row['pk']);
           
           $a = array( "old" => $row['pk'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_log_event SET pk = '".$a['new']."' WHERE pk = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any log events (pk)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));
       
       //
       // check for log views
       //
       $query = "SELECT miscellaneous FROM redcap_log_view WHERE miscellaneous REGEXP \"\\'".preg_quote($oldVal)."\\'\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           //$v = self::replacePiping( $oldVal, $newVal, $row['miscellaneous']);
           $nv = preg_replace('/\''.preg_quote($oldVal).'\'/', '\''.$newVal.'\'', $row['miscellaneous']);
           $a = array( "old" => $row['miscellaneous'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_log_view SET miscellaneous = '".$a['new']."' WHERE miscellaneous = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any log event view (miscellaneous)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));

       
       //
       // check for redcap_metadata_archive
       //
       $query = "SELECT field_name FROM redcap_metadata_archive WHERE field_name = \"".preg_quote($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           //$v = self::replacePiping( $oldVal, $newVal, $row['miscellaneous']);
           $nv = preg_replace('/\''.preg_quote($oldVal).'\'/', '\''.$newVal.'\'', $row['field_name']);           
           $a = array( "old" => $row['field_name'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata_archive SET field_name = '".$a['new']."' WHERE field_name = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any redcap_metadata_archive (field_name)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));

       //
       // check the branching logic archive (old variable)
       //
       $query  = "SELECT branching_logic,field_name FROM redcap_metadata_archive WHERE branching_logic REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           // ok, this is more complex. We need to replace the LIKE entry in the branching_logic column
           // this needs to work even if the item name is part of another item name (leading/trailing stuff)
           $nv = self::replacePiping( $oldVal, $newVal, $row['branching_logic']);
           
           $a = array( "old" => $row['branching_logic'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata_archive SET branching_logic = '".$a['new']."' WHERE branching_logic = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any branching logic archive (branching_logic)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));       


       //
       // check for redcap_metadata_archive (in misc)
       //
       $query = "SELECT misc FROM redcap_metadata_archive WHERE misc REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $nv = self::replacePiping( $oldVal, $newVal, $row['misc']);
           
           $a = array( "old" => $row['misc'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata_archive SET misc = '".$a['new']."' WHERE misc = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any piping (tags/misc)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));


       
       //
       // check for piping (in element note)
       //
       $query = "SELECT element_note FROM redcap_metadata_archive WHERE element_note REGEXP \"".self::pipingRegExp($oldVal)."\" AND project_id = ".$project_id;
       $result = db_query($query);
       $ar = array();
       while($row = db_fetch_assoc( $result ) ) {
           $nv = self::replacePiping( $oldVal, $newVal, $row['element_note']);
           
           $a = array( "old" => $row['element_note'],
                       "new" => $nv
           );
           $a["update"] = "UPDATE IGNORE redcap_metadata_archive SET element_note = '".$a['new']."' WHERE element_note = '".$a['old']."' AND project_id = ".$project_id;
           $ar[] = $a;
       }
       $results[] = array('type' => 'Does the oldVar exist in any piping (element_note)?',
                          'redcap_metadata' => count($ar),
                          'values' => $ar,
                          'query' => json_encode($query));



       // return the results if dryrun was called from website
       echo(json_encode($results));
    }

    // we should use dryrun code to create the list of commands we want to execute against the database
    public function run() {
       echo(json_encode(array('message' => 'hi')));

    }
}
?>
