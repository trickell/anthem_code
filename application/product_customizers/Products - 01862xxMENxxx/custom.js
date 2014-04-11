$(function() {  
	                       
	// This is just an example!!
	$("#reward_amount_2").bind("change", function() {
		var selected = $("#reward_amount_2 option:selected").val();
		if(selected != 'custom') {
			$("#reward_amount_1").attr('readonly', true);
			$("#reward_amount_1").toggleClass('disabledtext', true);
			$("#reward_amount_1").val($("#reward_amount_2 option:selected").val());		
		} else {
			$("#reward_amount_1").attr('readonly', false);
			$("#reward_amount_1").toggleClass('disabledtext', false);
			$("#reward_amount_1").val('');
		}
	});
	
	
	$("#generateProduct").bind("click", function() { 
		
		if ($("#package_type_0").attr("checked")) {
			
			if ($("#reward_type_0").attr("checked")){
				$("#reward_text").val("up to a $" + $("#reward_amount_1").val() + " gift card");	             									//Direct + Gift card
			    $("#reward").val($("#reward_type_0").parent().text());
			} else if ($("#reward_type_1").attr("checked")) {
				$("#reward_text").val("up to $" + $("#reward_amount_1").val() + " in your health account");											//Direct + Health account deposit 
			    $("#reward").val($("#reward_type_1").parent().text());
			} else if ($("#reward_type_2").attr("checked")) {
				$("#reward_text").val("up to $" + $("#reward_amount_1").val() + " towards your employer's health care premium contribution");		//Direct + Premium discount
			    $("#reward").val($("#reward_type_2").parent().text());
			}
			$("#package").val($("#package_type_0").parent().text());
			$("#package_text").val("up to $" + $("#reward_amount_1").val());   
			
		} else {
			
			var reward_amount = $("#reward_amount_1").val();

			reward_amount += ((reward_amount == 1) ? ' point' : ' points');

			if ($("#reward_type_0").attr("checked")){
				$("#reward_text").val("up to " + reward_amount + " for a gift card");																//Points + Gift card
			    $("#reward").val($("#reward_type_0").parent().text());  
			} else if ($("#reward_type_1").attr("checked")) {
				$("#reward_text").val("up to " + reward_amount + " in your health account");														//Points + Health account deposit
			    $("#reward").val($("#reward_type_1").parent().text()); 
			} else if ($("#reward_type_2").attr("checked")) {
				$("#reward_text").val("up to " + reward_amount + " towards your employer's health care premium contribution");						//Points + Premium discount
			    $("#reward").val($("#reward_type_2").parent().text()); 
			}
			$("#package").val($("#package_type_1").parent().text()); 
			$("#package_text").val("up to " + reward_amount);       	
		}
		
		
	});

});
