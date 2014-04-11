$(function() {
  
	// Copied from Phil -- thanks!! :-)	
	var needs_generation = true;
	$(".wizardPage").not("#preview_page, #summary_page").bind("pageShown", function() {needs_generation = true;});
	$("#preview_page").bind("pageShown", function() {
		var $form = $("#postcard_form");
		if(!$form.length || !needs_generation) {
			// don't attempt to generate if there's no form, or if
			// it's been generated already and no changes were made.
			return;
		}

		var $wizard = $(".wizardAutoContainer"),
			$response = $("#preview_response"),
			$spinner = $response.find(".spinner"),
			$message = $response.find(".message"),
			$status = $response.find(".status"),
			undisable = function() {
        $("#save_and_close_button").disable(false);
				$form.find("input.ajaxSubmitDisabled, select.ajaxSubmitDisabled, textarea.ajaxSubmitDisabled")
					.attr("disabled", false).removeClass("ajaxSubmitDisabled disabled");
			},
			showError = function(msg, emptyStatus) {
        needs_generation = true;
				$response.addClass("error");
				$message.text(msg);
				var btn = $('<input type="button" class="button" value="Click to Try Again" />');
				btn.click(startGeneration);
				$message.append(btn);
				btn.wrap("<p>");
				if(emptyStatus) {
					$status.empty().css("opacity", 1);
				} else {
					$status.fadeTo("normal", 0.5);
				}

				$wizard.wizardFreeze("forward");
			},

			statusCheck = function() {
				$.ajax({
					type: 'GET',
					url: site_url(document.location + "/preview_check?random="+new Date().getTime()),
					success: function(response) {
						if(typeof response == "string") {
							showError("A server error has occurred.");
						} else if(response.error) {
							showError(response.error);
							undisable();
						} else {
							$message.html(response.message);
							if(response.complete) {
								var preview = $('<a class="pdficon" target="_blank" href="'+response.pdf+'"><img width="24" height="24" alt="icon" src="'+site_url('images/icons/pdf.png')+'"/> Download the Preview</a>');
								$spinner.html(preview);
								$spinner.addClass("complete");
								undisable();
								$wizard.wizardFreeze(false);
								$status.slideUp("fast");
															
								// everything's OK
								// needs_generation = false;
							} else {
								setTimeout(statusCheck, 2000);
							}
						}
					},
					error: function() {
						showError("An unexpected error has occurred.");
						undisable();
					}
				});
			},
			startGeneration = function() {
        needs_generation = false;
				loadedFiles = false;
				$response.removeClass("error");
				$spinner.empty().removeClass().addClass("spinner").show();
				$message.text("Please wait while the campaign preview is generated...");

				$wizard.wizardFreeze(true);

				// undisable all submittable fields
				$form.find("input[disabled], select[disabled], textarea[disabled]").not("#summary_page *, .dontSubmit").attr("disabled", false);
        $("#save_and_close_button").disable();
				$form.ajaxSubmit({
					url: document.location + "/preview_generate?random="+new Date().getTime(),
					iframe: false,
					success: function(response) {
						// undisable();
						if(typeof response == "string") {
							showError("A server error has occurred.");
						} else if(response.error) {
							if(typeof response.error != "string") {
								// form error
								$form.showFormErrors(response.error);
							} else {
                undisable();
								showError(response.error, true);
							}
						} else {
							statusCheck();
						}
					},
					error: function() {
						showError("An unexpected error has occurred.", true);
						undisable();
					}
				});

				$form.find("input, select, textarea").not("input[type=upload]").attr("disabled", true).not(".disabled").addClass("ajaxSubmitDisabled disabled");
			};

		// start the product preview generation process.
		startGeneration();
	});
	
	// Also modified from Phil's code -- thanks again!! :-)
	$(".wizardContainer").bind("complete", function() {
		// send and verify the information
    $("input[name$='current_step']").val('2');		
		var $form = $("#postcard_form"),
			sendPage = $("#summary_page");
		if(!$form.length || !sendPage.length) {
			// don't attempt to submit if there's no form
			return;
		}
		var undisable = function() {
				$form.find("input.ajaxSubmitDisabled, select.ajaxSubmitDisabled, textarea.ajaxSubmitDisabled")
					.attr("disabled", false).removeClass("ajaxSubmitDisabled disabled");
				showWaitingSpinner(false);
			};
		// re-enable everything, so we can save the values
		$form.find("input[disabled], select[disabled], textarea[disabled]").not(".dontSubmit").attr("disabled", false);
		
		$form.ajaxSubmit({
			url: document.location + "/submit_postcard",
			iframe: false,
			success: function(response) {
				undisable();
				if(typeof response == "string") {
					alert("A server error has occurred.");
				} else if(response.error) {
					if(typeof response.error != "string") {
						// form error
						$form.showFormErrors(response.error);
					} else {
						alert(response.error);
					}
				} else {
					showWaitingSpinner(true, "Your Postcard Was Ordered Successfully!", true);
					setTimeout(function() {
						window.parent.needsRefresh = true;
						closeFancybox();
					}, 1500);
				}
			},
			error: function() {
				alert("An unexpected error has occurred.");
				undisable();
			}
		});

		$form.find("input, select, textarea").not("input[type=upload]").attr("disabled", true).not(".disabled").addClass("ajaxSubmitDisabled disabled");
		showWaitingSpinner(true, "Saving Your Postcard...");
	});	
  
  //
  // Hack for save and close button
  //
  $("#save_and_close_button").bind("click", function() {
    $("input[name$='current_step']").val('1');		
		var $form = $("#postcard_form"), sendPage = $("#summary_page");
		if(!$form.length || !sendPage.length) {
			return;
		}
		var undisable = function() {
				$form.find("input.ajaxSubmitDisabled, select.ajaxSubmitDisabled, textarea.ajaxSubmitDisabled")
					.attr("disabled", false).removeClass("ajaxSubmitDisabled disabled");
				showWaitingSpinner(false);
			};
		$form.find("input[disabled], select[disabled], textarea[disabled]").not(".dontSubmit").attr("disabled", false);
		
		$form.ajaxSubmit({
			url: document.location + "/submit_postcard",
			iframe: false,
			success: function(response) {
				undisable();
				if(typeof response == "string") {
					alert("A server error has occurred.");
				} else if(response.error) {
					if(typeof response.error != "string") {
						// form error
						$form.showFormErrors(response.error);
					} else {
						alert(response.error);
					}
				} else {
					showWaitingSpinner(true, "Your Postcard Was Saved Successfully!", true);
					setTimeout(function() {
						window.parent.needsRefresh = true;
						closeFancybox();
					}, 1500);
				}
			},
			error: function() {
				alert("An unexpected error has occurred.");
				undisable();
			}
		});

		$form.find("input, select, textarea").not("input[type=upload]").attr("disabled", true).not(".disabled").addClass("ajaxSubmitDisabled disabled");
		showWaitingSpinner(true, "Saving Your Postcard...");
	});	  
  
  //
  // Hack for clone button
  //
  $("#clone_button").bind("click", function() {
		var $form = $("#postcard_form"), sendPage = $("#summary_page");
		if(!$form.length || !sendPage.length) {
			return;
		}
		var undisable = function() {
				$form.find("input.ajaxSubmitDisabled, select.ajaxSubmitDisabled, textarea.ajaxSubmitDisabled")
					.attr("disabled", false).removeClass("ajaxSubmitDisabled disabled");
				showWaitingSpinner(false);
			};
		$form.find("input[disabled], select[disabled], textarea[disabled]").not(".dontSubmit").attr("disabled", false);

		$form.ajaxSubmit({
			url: document.location + "/clone_postcard",
			iframe: false,
			success: function(response) {
				undisable();
				if(typeof response == "string") {
					alert("A server error has occurred.");
				} else if(response.error) {
					if(typeof response.error != "string") {
						// form error
						$form.showFormErrors(response.error);
					} else {
						alert(response.error);
					}
				} else {
					showWaitingSpinner(true, "Your Campaign Information Was Successfully Copied!", true);
					setTimeout(function() {
						window.parent.needsRefresh = true;
						document.location = response.url;
					}, 2000);
				}
			},
			error: function() {
				alert("An unexpected error has occurred.");
				undisable();
			}
		});

		$form.find("input, select, textarea").not("input[type=upload]").attr("disabled", true).not(".disabled").addClass("ajaxSubmitDisabled disabled");
		showWaitingSpinner(true, "Copying Your Campaign Information...");
	});  
  
  $("#email_proof").click(function() {
    $("#save_and_close_button").val( $(this).is(":checked") ? 'Send and Close' : 'Save and Close' );
  }).triggerHandler("click");
  
  function setHidden(field, value) {
    $(field).val(value);
    $(field).change();
  }
  
  function update_summary_fields() {    
    $('#summary_filename').html($('#temp_summary_filename').val());
    $('#summary_records').html($('#temp_summary_records').val());
    $('#summary_total_cost').html($('#temp_summary_total_cost').val());
    $('#summary_unit_price').html($('#temp_summary_unit_price').val());
    $('#summary_shipping_cost').html($('#temp_summary_shipping_cost').val());
  }
  
  $('#postcard_csv')
    .bind('uploadifyFileAdded', function(event, name, displayName) {
      $.ajax({
			type: 'POST',
			url: site_url(document.location + "/file_check"),
      data: { filename: name },
			success: function(data, filename) {      
        if(!data.error) {
          $("#uploaded_filename").val(data['filename']);
          $("#csv_preview").html(data['preview_html']);
          $("#image_selections").html(data['image_html']);
          $("#quantity").val(data['row_count']);	            
          $('#temp_summary_filename').val(displayName);
          $('#temp_summary_records').val(data['row_count']);
          $('#temp_summary_total_cost').val(data['total_price']);
          $('#temp_summary_unit_price').val(data['unit_price']);    
          $('#temp_summary_shipping_cost').val(data['shipping_cost']); 
          update_summary_fields();
        } else {          
          alert(data.error);
          $('#postcard_csvQueue .delete').click();          
        }
			},
			error: function() {
				alert("An unexpected error has occurred.");
				undisable();
			}
      })
    })
    .bind('uploadifyFileRemoved', function(obj, name) { 
      $("#quantity").val('0');	    
      $("#csv_preview").html('');
      $("#image_selections").html('');
      $('#summary_filename').val('');
      $('#summary_records').val('');
      $('#summary_total_cost').val('');
      $('#summary_unit_price').val('');
      $('#summary_shipping_cost').val('');
      update_summary_fields();
		});
    
  $('#summary_page').bind('pageShown', function() {
    $("#summary_campaign").text($('#campaign_name').val());
    $("#summary_state").text($('#anthem_region_id option:selected').text());
    $("#summary_phone").text($('#phone_number').val());
    $("#summary_event_code").text($('#event_code').val());
    $("#summary_microsite_url").text($('#microsite_url').val());
    $("#summary_return_address_1").text($('#return_address_1').val());
    $("#summary_return_address_2").text($('#return_address_2').val());
    $("#summary_return_address_city").text($('#return_address_city').val());
    $("#summary_return_address_state").text($('#return_address_state').val());
    $("#summary_return_address_zip").text($('#return_address_zip').val());
  });
  
  if($("input[name$='current_step']").val() == '1') {	    
    var hasExecuted = false;
    $("#start_page").bind("pageShown", function() {
      if(!hasExecuted) {
        hasExecuted = true;
        $(".wizardContainer").wizardShowPage(15, false);
      }
    });
  } else if($("input[name$='current_step']").val() == '2') {	
    $("#start_page").bind("pageShown", function() {
      $("#shipping_date").disable();
      $("#continueText").html('');
      $(".wizardContainer").wizardShowPage(18, false);
      $(".wizardBar").hide();
      $(".wizardTitle").hide();
      update_summary_fields();   		
    });  
  }
  
});
