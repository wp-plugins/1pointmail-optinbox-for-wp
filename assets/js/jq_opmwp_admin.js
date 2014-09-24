jQuery(document).ready( function($){

  $("#checkMyLogin").click(function() {
	var $ep = $('#opmwp-endpoint').val();
	var $un = $('#opmwp-username').val();
	var $pw = $('#opmwp-password').val();

	$.ajax({
		type: "POST",
		data: "opmwp-endpoint=" + $ep + "&opmwp-username=" + $un + "&opmwp-password=" + $pw + "&action=opmwp_login_check",
		url: ajaxurl,
		beforeSend: function() {
			$("#checkMyLogin").html("Authenticating...");
			$("#checkInfo").html("<p class=\"description\">&nbsp;</p>");
		},
		success: function(data) {
			if (data == "valid") {
				$("#checkInfo").html("<p class=\"description\"><strong>Login Successful!</strong> Make sure you '<strong>Update Settings</strong>' below...</p>");
				//$('#checkMyLogin').hide();
				$('#checkMyLogin').html("Test Login");
			} else {
				$("#checkInfo").html("<p class=\"description\"><strong>Nope - Invalid<strong></p>");
			}
		} // EO Success
	}); // EO Ajax
  }); // EO Function



  $("#syncButton").click(function() {

        $.ajax({
                type: "POST",
                data: "?a=1&action=opmwp_runsync",
                url: ajaxurl,
                beforeSend: function() {
                        $("#syncInfo").html("");
			$('#syncButton').html("Syncing...");
                },
                success: function(data) {
                        if (data == "ok") {
                                //$('#syncButton').fadeOut("fast", function(){});
                                $('#syncButton').html("Sync Now");
                                $("#syncInfo").html("Complete. You will need to reload this page to see the update(s).");
                        } // EO If
                } // EO Success
        }); // EO ajax
  }); // EO Function


});

