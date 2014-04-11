$(function() {
	//----------------------------------------------------------------------
	// Statement Tables
	//----------------------------------------------------------------------

	var money = function(val) {
			var num = parseFloat(val);
			if(!isNaN(num)) {
				return parseFloat(val).toFixed(2);
			} else {
				return val;
			}
		},
		blurMoney = function() {
			var num = parseFloat($(this).val());
			if(!isNaN(num)) {
				$(this).val(num.toFixed(2));
			}
		},
		premiumCostUpdatesIgnore = false,
		addVal = function(node, name, val) {
			var input = $(node).find("input");
			if(input.length) {
				input.val(val);
			} else {
				input = $('<input type="hidden"/>').attr("name", name).val(val).appendTo(node);
			}
		},
		premiumCostUpdates = function() {
			if(premiumCostUpdatesIgnore) {
				return;
			}
			premiumCostUpdatesIgnore = true;
			var base = parseFloat($("#st1_totalannualpremium").val());
			if(!isNaN(base)) {
				$(".statement_table tr.row").each(function(i, row) {
					var tr = $(row),
						perc = parseFloat(tr.find("td.percent").text().replace('%', '')),
						annSavings = perc * base / 100,
						specialtyCost = tr.find(".specialty_cost"),
						specialtySavings = tr.find(".specialty_savings");
					var name = tr.find("td.annual_savings span").text(money(annSavings)).attr("id");
					addVal(tr.find("td.annual_savings"), name, money(annSavings));
					if(specialtyCost.length) {
						var val = specialtyCost.find("input").val() || 0,
							cost = parseFloat(val);
						if(isNaN(cost)) {
							return;
						}
						val = '';
						if(cost === 0 || annSavings >= cost) {
							val = 'no additional cost';
						} else {
							val = money(annSavings / cost * 100)+'%';
						}
						name = specialtySavings.text(val).attr("id");
						addVal(specialtySavings, name, val);
					}
				});
			}

			premiumCostUpdatesIgnore = false;
		},
		premiumUpdateIgnore = false,
		premiumUpdates = function() {
			if(premiumUpdateIgnore) {
				return;
			}
			premiumUpdateIgnore = true;
			var val = parseFloat($(this).val());
			if(!isNaN(val)) {
				// update everyone but the
				$("#st1_totalmonthlypremium, #st2_totalmonthlypremium").not("#"+this.id).val(money(val));
				$("#st1_totalannualpremium, #st2_totalannualpremium").val(money(val * 12));

				// update costs
				premiumCostUpdates();
			}

			premiumUpdateIgnore = false;
		},
		checkStatementEnabled = function() {
			// determine ID
			var	$this = $(this),
				enabled = $this.is(":checked"),
				id = this.id,
				sibling;
			// match sibling
			if(id.indexOf('st1') != -1) {
				sibling = $('#'+id.replace('st1', 'st2'));
			} else {
				sibling = $('#'+id.replace('st2', 'st1'));
			}
			sibling.attr("checked", enabled);
			$.each([$this, sibling], function(i, n) {
				n.parents("tr:first")
					.toggleClass("notAvailable", !enabled)
					.find('input[type=text]').disable(!enabled);
			});
		};
	$("#st1_totalmonthlypremium, #st2_totalmonthlypremium").change(premiumUpdates).keyup(premiumUpdates).blur(blurMoney);
	$("#st1_statement_table input[type=text]").change(premiumCostUpdates).keyup(premiumCostUpdates).blur(blurMoney);
	$("#st1_statement_table input[type=checkbox], #st2_statement_table input[type=checkbox]").click(checkStatementEnabled).each(checkStatementEnabled);

	// force one plan
	$("#dental_plans_plan_1").attr("disabled", true).hide();
	$("#vision_plans_plan_1").attr("disabled", true).hide();
	
	// ensure that everything is kosher.
	premiumUpdates.apply($("#st1_totalmonthlypremium")[0]);


	//----------------------------------------------------------------------
	// Dental Templates
	//----------------------------------------------------------------------

	// handle template downloads
	$(".dental_template_button").click(function() {
		var $this = $(this),
			sel = $("#"+$this.attr("id").replace('_button', '')),
			// relative URL is important!
			url = document.location + '/get_dental_template/' + sel.val();
		window.open(url);
	});


	//----------------------------------------------------------------------
	// Medical Plans
	//----------------------------------------------------------------------

	// handle medical plan sorting
	var showMedicalSortPage = function() {
			var show = false;
			$(".medical_plans").each(function(i, group) {
				// ignore this group if the checkbox is not set
				if($(this).parents(".fieldset:first").find(".legend input.checkbox:checked").length) {
					// only show the sorter if there is more than one checked item
					// in a group.
					show = $(this).find("input.checkbox:checked").length > 1;
					if(show) {
						return false;
					}
				}
			});
			$("#show_medical_plans_sort").val(show ? "true" : "false").trigger("change");
		};
	$(".medical_plans input.checkbox").each(function(i, check) {
		var update = function() {
			var $check = $(this),
				id = $check.val(),
				name,
				list_id = $check.attr("name").replace('[]', '') + "_order",
				list = $("#"+list_id);
			if($check.is(":checked")) {
				name = $.trim($check.parent().text());
				list.docsorter("add", id, name);
			} else {
				list.docsorter("remove", id);
			}
		};
		update.apply(check);
		$(check).click(update).click(showMedicalSortPage);
	});
	// track when the groups are turned on or off
	$("input[name^=medical_plans].optionalGroupCheckbox").click(showMedicalSortPage);
	showMedicalSortPage();
	

	//----------------------------------------------------------------------
	// Life & Disability Quote Title
	//----------------------------------------------------------------------

	// change the title of the Life / Disablity quote as needed
	var updateLDQuote = function() {
		var life = $("#products_life").is(":checked"),
			dis = $("#products_disability").is(":checked"),
			type = "Life & Disability",
			oldTitle = $("#page_ldquote").attr("wizardTitle");
		if(!life) {
			type = "Disability";
		} else if(!dis) {
			type = "Life";
		}
		var title = "Upload "+type+" Quote";
		if(oldTitle != title) {
			$("#ldquote_text").text(type);
			$("#page_ldquote").attr("wizardTitle", title);
			$(".wizardContainer").wizardRefresh();
		}
	};
	$("#products_life, #products_disability").click(updateLDQuote);
	updateLDQuote();


	//----------------------------------------------------------------------
	// Exhibits
	//----------------------------------------------------------------------

	var showDocSortPage = function() {
		$("#show_docs_list").val(
			($("#docs_list").val().indexOf('|') != -1) ?
				'true' :
				'false'
		).trigger("change");
	};

	// Add pre-approved and uploaded docs to the document sorter.
	$(".row_approved_docs input.checkbox").each(function(i, check) {
		var update = function() {
			var id = check.value, name;
			if(check.checked) {
				name = $.trim($(check).parent().text());
				$("#docs_list").docsorter("add", id, name);
			} else {
				$("#docs_list").docsorter("remove", id);
			}
		};
		update();
		$(check).click(update).click(showDocSortPage);
	});

	$("#upload_docs").bind("uploadifyFileAdded", function(event, id, name) {
		$("#docs_list").docsorter("add", id, name);
		showDocSortPage();
	}).bind("uploadifyFileRemoved", function(event, id, name) {
		$("#docs_list").docsorter("remove", id, name);
		showDocSortPage();
	});
	showDocSortPage();

	// remove any missing documents from the sorter when switching to that page
	$("#documentSortPage").bind("pageShown", function() {
		var approved = $("#approvedDocsPage"),
			uploads = $("#upload_docs");
		$.each($("#docs_list").docsorter("list"), function(id, name) {
			if(approved.find("input[value="+id+"]:checked").length) {
				return;
			} else if(uploads.docuploadVal().indexOf(id) >= 0) {
				return;
			} else {
				$("#docs_list").docsorter("remove", id, name);
			}
		});
	});

});

