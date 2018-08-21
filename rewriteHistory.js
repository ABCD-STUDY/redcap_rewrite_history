jQuery(document).ready(function () {

    jQuery('#start-rewrite').on('click', function() {
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
	}
    });
    
});

