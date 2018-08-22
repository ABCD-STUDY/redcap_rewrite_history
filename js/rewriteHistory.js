
jQuery(document).ready(function () {

    jQuery('#oldname').val('pmq1');
    jQuery('#newname').val('pmq1_newname');

    jQuery('#start-rewrite').on('click', function() {
        alert('not implemented yet');

        // only do something if the checkbox is on
        var oldV = jQuery('#oldname').val();
        var newV = jQuery('#newname').val();
        if (oldV === "" || newV === "") {
            alert("Error: no value provided");
            return;
        }
        
        if (jQuery('#sure').is(':checked')) {
            // replace
            console.log("rewrite now:" + jQuery('#oldname').val() + " -> " + jQuery('#newname').val());
        } else {
            alert('Nothing will be done if you are not sure');
            return;
        }
        // now check if we can do this
        if (oldV == "id_redcap") {
            alert("Error: changing id_redcap is not allowed");
            return;
        }
        project_id = jQuery('#project-list').val();

    });
    
    jQuery('#start-dryrun').on('click', function() {
        
        // only do something if the checkbox is on
        var oldV = jQuery('#oldname').val();
        var newV = jQuery('#newname').val();
        if (oldV === "" || newV === "") {
            alert("Error: no value provided");
            return;
        }
        
        // now check if we can do this
        if (oldV == "id_redcap") {
            alert("Error: changing id_redcap is not allowed");
            return;
        }
        project_id = jQuery('#project-list').val();
        
        // can I call now php again?
        jQuery.post('', { "action": "runDry", "oldVal": oldV, "newVal": newV, "project_id": project_id }, function(data) {
            console.log("got something done");
            data = JSON.parse(data);
            jQuery('#error-messages').html(JSON.stringify(data, undefined, 4));
        }, "json");
        
    });
    
});
