$(function() {
	
	// sync recipient email to proposal email
	var oldEmail = '';
	$("#cover_recip_email").change(function() {
		var to = $("#message_to");
		if(!to.val() || to.val() == oldEmail) {
			to.val($(this).val());
		}
		oldEmail = $(this).val();
	});

	// check whether to show products or not
	var checkShowProducts = function() {
			var show =	$("#proposal_type_proposal").is(":checked") ||
						($("#proposal_type_proposal_aso").is(":checked") && $("#cover_letter").is(":checked"));
			$("#show_products").val(show ? 'true' : '').change();
		};
	$("#proposal_type_proposal, #proposal_type_proposal_aso, #cover_letter").click(checkShowProducts);
	checkShowProducts();

	// Add select all / none buttons for "Products Included"
	$(".row_products .rowLabel").html(
		$('<span class="button selAllButton">Select All</span>').click(function() {
			$(".row_products .rowField input").attr("checked", false).click();
		})).append($('<span class="button selNoneButton">Select None</span>').click(function() {
			$(".row_products .rowField input").attr("checked", true).click();
		}));

	var showDocSortPage = function() {
		$("#show_docs_list").val(
			($("#docs_list").val().indexOf('|') != -1) ?
				'true' :
				'false'
		).trigger("change");
	};

	// Add pre-approved and uploaded docs to the document sorter.
	$("input[name^=approved_docs]").each(function(i, check) {
		var update = function() {
			var id = check.value, name;
			if(check.checked) {
				name = $.trim($(check).parent().text());
				$("#docs_list").docsorter("add", id, name);
			} else {
				$("#docs_list").docsorter("remove", id);
			}
			showDocSortPage();
		};
		update();
		$(check).click(update);
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


	/*
	 * Handle the logic for the statement tables
	 */
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
						var text = specialtySavings.find("span.text");
						if(text.length) {
							text.text(val);
						} else {
							specialtySavings.html('<span class="text">'+val+"</span>");
						}
						addVal(specialtySavings, specialtySavings.attr("id"), val);
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
	$("#st1_statement_table input[type=text]").change(premiumCostUpdates).keyup(premiumCostUpdates).blur(blurMoney).triggerHandler("change");
	$("#st1_statement_table input[type=checkbox], #st2_statement_table input[type=checkbox]").click(checkStatementEnabled).each(checkStatementEnabled);
	
	// ensure that everything is kosher.
	premiumUpdates.apply($("#st1_totalmonthlypremium")[0]);
});

