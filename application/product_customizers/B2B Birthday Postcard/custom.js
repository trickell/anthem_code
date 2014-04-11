$(function() {

	$('.notavailable').disable();

	// Copied from Phil -- thanks!! :-)	
	var needs_generation = true;
	var uploadedfilename = '';
	$(".wizardPage").not("#preview_page").bind("pageShown", function() {needs_generation = true;});
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
				$message.text("Please wait while the campaign is generated...");

				$wizard.wizardFreeze(true);

				// undisable all submittable fields
				$form.find("input[disabled], select[disabled], textarea[disabled]").not("#send_email *, .dontSubmit").attr("disabled", false);
        $("#save_and_close_button").disable();
				$form.ajaxSubmit({
					url: document.location + "/preview_generate?random="+new Date().getTime(),
					iframe: false,
					success: function(response) {
						if(typeof response == "string") {
							showError("A server error has occurred.");
						} else if(response.error) {
							if(typeof response.error != "string") {
								// form error
								$form.showFormErrors(response.error);
							} else {
								showError(response.error, true);
                undisable();
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
    $("input[name$='current_step']").val('2');
		// send and verify the information
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
					showWaitingSpinner(true, "Your Campaign Was Sent Successfully!", true);
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
		showWaitingSpinner(true, "Sending Your Campaign...");
	});	
  
  $('#postcard_csv')
    .bind('uploadifyFileAdded', function(obj, name) {
      $.ajax({
			type: 'POST',
			url: site_url(document.location + "/file_check"),
			data: { filename: name },
			success: function(data) {
        if(!data.error) {
          $("#csv_preview").html(data['preview_html']);
          uploadedfilename = data['filename'];
          $("#uploaded_filename").val(uploadedfilename);
        } else {
          alert("Error: " + data.error);
          $('#postcard_csvQueue .delete').click();  
        }
			},
			error: function() {
				showError("An unexpected error has occurred.");
				undisable();
			}
      });
    })
    .bind('uploadifyFileRemoved', function(obj, name) {    
      $("#csv_preview").html('');
	});

	$("#summary_page").bind("pageShown", function() {
  
    // Only do this if we haven't already finished...
    if($("input[name$='current_step']").val() != '2') {
		
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
        url: document.location + "/summary",
        data: { filename: uploadedfilename },
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
            $("#summary_html").val(response['html']);
            $("#summary_response").html(response['html']);
          }
        },
        error: function() {
          alert("An unexpected error has occurred.");
          undisable();
        }
      });

      $form.find("input, select, textarea").not("input[type=upload]").attr("disabled", true).not(".disabled").addClass("ajaxSubmitDisabled disabled");
      showWaitingSpinner(true, "Loading Summary...");
    } else {
      $("#summary_response").html($("#summary_html").val());    
    }		
	});

	$('input[name^="months"]').live('click',function(){
		var $wizard = $(".wizardAutoContainer");
		var boxes = $('input[name^="months"]');
		var start = false;
		var stop = false;
		//var ptr = $('label[for^="months"]').parent('td');

		//for (cobj in $('input[name^="months"]')) {
		for (var x=0; x<boxes.length;x=x+1) {
			$wizard.wizardFreeze(false);
			cobj = boxes[x];

			// block start
			if ($(cobj).attr('checked') === true && start === false) {
				start = true;

			// good checkmark
			} else if ($(cobj).attr('checked') === true && start === true && stop === false) {

			// a break found
			} else if ($(cobj).attr('checked') !== true && start === true && stop === false) {
				stop = true;

			// uh oh, more checkboxes
			} else if ($(cobj).attr('checked') === true && stop === true) {
				$wizard.wizardFreeze('forward');
				//$(ptr).siblings('td.rowError').html('');
				$("<div class='month_error'>Dates must be consecutive!</div>").prependTo('#monthslist .fieldsetContent');
				return;
			}
			//$(ptr).siblings('td.rowError').html('');
			$('.month_error').remove();
		}
	});

  //
  // Hack for save and close button from frederick
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
					showWaitingSpinner(true, "Your Campaign Was Saved Successfully!", true);
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
		showWaitingSpinner(true, "Saving Your Campaign...");
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
  
  
  if($("input[name$='current_step']").val() == '1') {	    
    var hasExecuted = false;
    $("#start_page").bind("pageShown", function() {
      if(!hasExecuted) {
        hasExecuted = true;
        $(".wizardContainer").wizardShowPage(9, false);        
      }
    });
  } else if($("input[name$='current_step']").val() == '2') {	
    $("#start_page").bind("pageShown", function() {
      $("#shipping_date").disable();
      // $("#continueText").html('');
      $(".wizardContainer").wizardShowPage(12, false);
      $(".wizardBar").hide();
      $(".wizardTitle").hide();
    });  
  }

});