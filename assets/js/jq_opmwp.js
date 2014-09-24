jQuery(document).ready( function($){

  $("#boptin_subscribe").click(function() {
        var $em = $('#boptin_email').val();
	var $fn = $('#boptin_fname').val();
	var $ln = $('#boptin_lname').val();

        $.ajax({
                type: "POST",
                data: "boptin_email=" + $em + "&boptin_fname=" + $fn + "&boptin_lname=" + $ln + "&action=opmwp_boptin_subscribe",
                url: ajaxurl,
                beforeSend: function() {
			$("#boptinInfo").show();
                        $("#boptinInfo").html("<p class=\"description\">Subscribing...</p>");
                },
                success: function(data) {
                        if (data == "ok") {
                                $("#boptinInfo").html("<p class=\"description\"><strong>Success!</strong><p>");
				$("#boptinInfo").delay(1500).fadeOut("slow", function(){});
				$('#boptin_email').val("");
				$('#boptin_fname').val("");
				$('#boptin_lname').val("");
			} else if (data == "invalid") {
				$("#boptinInfo").html("<p class=\"description\"><strong>Please enter a valid email address.</strong><p>");
				$("#boptinInfo").delay(1500).fadeOut("slow", function(){});
			} else if (data == "exists") {
				$("#boptinInfo").html("<p class=\"description\"><strong>You have already subscribed.</strong><p>");
				$("#boptinInfo").delay(1500).fadeOut("slow", function(){});
                        } else {
                                $("#boptinInfo").html("<p class=\"description\"><strong>Sorry - There was an error.<strong></p>");
				$("#boptinInfo").delay(1500).fadeOut("slow", function(){});
                        } // EO If
                } // EO Success
        }); // EO ajax
  }); // EO Function

});
