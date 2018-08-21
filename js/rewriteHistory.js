
jQuery(document).ready(function () {

    jQuery('#start-rewrite').on('click', function() {
	alert('not implemented yet');
    });
    
    jQuery('#start-dryrun').on('click', function() {
	
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

	// can I call now php again?
	jQuery.post(home, { "action": "runDry", "oldVal": oldV, "newVal": newV }, function(data) {
	    console.log("got something done");
	    jQuery('#error-messages').text(JSON.stringify(data));
	});
	
    });
    
});

