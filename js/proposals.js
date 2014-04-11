/*
 * Shared code for proposals.
 */

$(function() {

	// common field syncing
	sync_fields("#group_name, #group_name_2", function(val) {
		$("#message_subject").val("Proposal For: "+val);
		$("#message_attach_name").val("Proposal for "+val.replace(/[\/\\\?\%\*\:\|"\<\>]+/, '_'));
	});
	sync_fields("#effective_date, #effective_date_2");

	/*
	 * Opportunity ID code.
	 */
	var oppMap = {
			group_name: "groupName",
			effective_date: "effectiveDate",
			number_of_employees: "totalLives",
			zip_code: "zipCode",
			sic: "sicCode",
			message_to: "brokerEmail"
		},
		oppBackup = {},
		oppRestore = function() {
			$.each(oppBackup, function(i, v) {
				$("#"+i).val(v);
			});
		};
		$.each(oppMap, function(i, v) {
			oppBackup[i] = $("#"+i).val();
		});
	$("#opportunity_id_button").click(function() {
		showWaitingSpinner(true, "Connecting to iAvenue...");
		var timeout = setTimeout(function() {
			// if we need to re-establish the VPN, it can sometimes take a little while.
			// set a second message so the user feels that something is happening.
			showWaitingSpinner(true, "Looking up Opportunity ID...");
		}, 1500);
		$.post(
			window.iavenue_url,
			{
				opportunity_id: $("#opportunity_id").val()
			},
			function(response) {
				clearTimeout(timeout);
				showWaitingSpinner(false);
				$resp = $("#iavenue_response");
				if(!response) {
					$resp.addClass("error").html("A communication error has occurred.");
					oppRestore();
				} else if(response.error) {
					$resp.addClass("error").html(response.error);
					oppRestore();
				} else {
					$resp.empty();
					$resp.removeClass("error");
					$resp.append("<h3>iAvenue Opportunity Response:</h3>");
					if(response.srvMessage) {
						$resp.append("<p><em>NOTE: "+response.srvMessage+"</em><p>");
					}
					var f = function(l, v) {
						var r = $("<tr><th></th><td></td></tr>");
						r.find("th").text(l+":");
						r.find("td").text(v);
						return r;
					};
					var t = $('<table class="opportunityResultsTable"/>');
					t.append(f('Broker Email', response.brokerEmail));
					t.append(f('Group Name', response.groupName));
					t.append(f('Effective Date', response.effectiveDate));
					t.append(f('Total Lives', response.totalLives));
					t.append(f('ZIP Code', response.zipCode));
					t.append(f('SIC', response.sicCode));
					$resp.append(t);

					$resp.append("If this is correct, please click <b>Next</b> to continue, otherwise you may look up another ID, or click <b>Clear</b> to restore the default values.");

					$.each(oppMap, function(i, v) {
						$("#"+i).val(response[v]).change();
					});
				}
			}
		);
	});
	$("#opportunity_id_clear").click(function() {
		oppRestore();
		$("#opportunity_id").val('');
		$("#iavenue_response").empty();
	});


	/*
	 * Proposals generation Code
	 */
	var needs_generation = true;
	$(".wizardPage").not("#preview_page, #send_proposal").bind("pageShown", function() {needs_generation = true;});
	$("#preview_page").bind("pageShown", function() {
		var $form = $("#proposal_form");
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
			loadedFiles = false,
			undisable = function() {
				$form.find("input.ajaxSubmitDisabled, select.ajaxSubmitDisabled, textarea.ajaxSubmitDisabled")
					.attr("disabled", false).removeClass("ajaxSubmitDisabled disabled");
			},
			showError = function(msg, emptyStatus) {
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
								var preview = $('<a class="pdficon" target="_blank" href="'+response.pdf+'"><img width="24" height="24" alt="icon" src="'+site_url('images/icons/pdf.png')+'"/> Download the Completed Proposal</a>');
								$spinner.prepend(preview);
								$spinner.addClass("complete");
								undisable();
								$wizard.wizardFreeze(false);
								$status.slideUp("fast");
								// everything's OK
								needs_generation = false;
								$(".row_message_attach_name .size").html("("+response.size+")");
							} else {
								if(response.files) {
									if(!loadedFiles) {
										$status.hide().empty().append('<table class="fileStatus" />');
										setTimeout(function() {
											$status.slideDown("fast");
										}, 10);
										loadedFiles = true;
									}
									$.each(response.files, function(i, file) {
										var table = $status.find("table");
										var tr = table.find("tr[job="+i+"]"),
											status;
										if(!tr.length) {
											tr = $('<tr job="'+i+'"/>').appendTo(table);
											$('<td class="fileName"/>').text(file.desc).appendTo(tr),
											status = $('<td class="status"/>').text(file.message).appendTo(tr),
											$('<td><span class="icon"/></td>').appendTo(tr);
										} else {
											status = tr.find(".status").text(file.message);
										}
										if(file.progress == 100) {
											tr.addClass("complete");
										} else if(file.progress == -1) {
											tr.addClass("error");
										} else {
											tr.addClass("busy");
											if(file.progress > 0) {
												status.append(" ("+file.progress+"%)");
											}
										}
									});
								}
								
								$status.toggleClass("combining", !!response.combining);
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
				loadedFiles = false;
				$response.removeClass("error");
				$spinner.empty().removeClass().addClass("spinner").show();
				$message.text("Please wait while the proposal is generated...");
				$status.empty().css("opacity", 1).removeClass("combining");

				$wizard.wizardFreeze(true);

				// undisable all submittable fields
				$form.find("input[disabled], select[disabled], textarea[disabled]").not("#send_proposal *, .dontSubmit").attr("disabled", false);
				$form.find("input[type=upload]").attr("disabled", true);
				$form.ajaxSubmit({
					url: document.location + "/preview_generate?random="+new Date().getTime(),
					iframe: false,
					success: function(response) {
						undisable();
						if(typeof response == "string") {
							showError("A server error has occurred.");
						} else if(response.error) {
							if(typeof response.error != "string") {
								// form error
								$form.showFormErrors(response.error);
							} else {
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

	$(".wizardContainer").bind("complete", function() {
		// send and verify the information
		var $form = $("#proposal_form"),
			sendPage = $("#send_proposal");
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
		// except uploads, we ignore those
		$form.find("input[type=upload]").attr("disabled", true);
		
		$form.ajaxSubmit({
			url: document.location + "/send_proposal",
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
					showWaitingSpinner(true, "Your Message Was Sent Successfully!", true);
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
		showWaitingSpinner(true, "Sending Your Message...");
	});

	if(window.skip_iavenue) {
		$("#iavenue_page").bind("pageShown", function() {
			if(window.skip_iavenue) {
				window.skip_iavenue = false;
				setTimeout(function() {
					$(".wizardContainer").wizardShowPage(2, false);
					$("#iavenue_page").find("> table").show();
				},10);
			}
		}).find("> table").hide();
	}

});
