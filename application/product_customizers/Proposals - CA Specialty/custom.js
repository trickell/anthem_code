$(function() {
	
	// dental blue plans 15-30 only have a network of 100 or 200
	$(".dentalblue_plan_dbnum").each(function(i, node) {
		var getPlan = function() {
				var ret = parseInt($(node).val(), 10) || 0;
				return ret;
			},
			oldPlan = 0,
			id = node.id.replace('_dbnum', '_100num'),
			$otherSelect = $("#"+id),
			otherSelect = $otherSelect[0],
			db100nums = $("#"+id+" option"),
			db100nums2 = db100nums.not("[value=300]").clone();

		$(node).change(function() {
			var plan = getPlan(),
				value = otherSelect.selectedIndex;
			if(plan > 14 && oldPlan <= 14) {
				$otherSelect.html(db100nums2);
				// prevent the selected index from being too high
				if(value >= db100nums2.length) {
					value = 0;
				}
			} else if(plan <= 14 && oldPlan > 14) {
				$otherSelect.html(db100nums);
			}
			// restore value
			otherSelect.selectedIndex = value;

			oldPlan = plan;
		});
		$(node).change();
	});

	// change the title of the Life / Disablity quote as needed
	var updateLDQuote = function() {
		var life = $("#modules_life").is(":checked"),
			dis = $("#modules_disability").is(":checked"),
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
	$("#modules_life, #modules_disability").click(updateLDQuote);
	updateLDQuote();

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
	$("#dentalblue_plans_plan_1").attr("disabled", true).hide();
	$("#dentalnet_plans_plan_1").attr("disabled", true).hide();
	$("#bvvision_plans_plan_1").attr("disabled", true).hide();
	
	// ensure that everything is kosher.
	premiumUpdates.apply($("#st1_totalmonthlypremium")[0]);


	// handle dynamic custom forms
	$(".dentalBlueCustomRate .fieldsetContent, .dentalnetCustomRate .fieldsetContent, .bvvisionCustomRate .fieldsetContent").bind("onlyShowIfChange", function(e, showing) {
		var $t = $(this);
		if(!showing || $t.hasClass('loaded') || $t.is(":hidden")) {
			return;
		}
		setTimeout(function() {
			$t.load(
				$t.find('.url').text(),
				{},
				function() {
					$t.addClass("loaded");
					processForms();
				}
			);
		}, 500);
	});

});

