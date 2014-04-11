// Common SmartDox functions

/*
 * Handle setting up content on page load
 */
$(function(){
	// because this is CodeIgniter, default all AJAX requests to POST.
	$.ajaxSetup({type: "post"});

	// add function to disable fields
	$.fn.disable = function(disable) {
		if(disable !== false) {
			disable = true;
		}
		var $this = $(this);
		$this.attr("disabled", disable);
		$this.each(function(i,e) {
			e = $(e);
			if(e.hasClass("button")) {
				e.toggleClass("buttonDisabled", disable);
			} else {
				e.toggleClass("disabled", disable);
			}
			if(e.is("input[type=checkbox],input[type=radio]") && e.parent().is("label")) {
				e.parent().toggleClass("disabled", disable);
			}
		});
		if($.fn.cleditor && $this.is(".richtext")) {
			$.each($this.cleditor(), function(i, editor) {editor.disable(disable);});
		}
		return this;
	};

	// Configure "blink" (suggest fields) text.
    $('.blink').
        focus(function() {
            if(this.title==this.value) {
                this.value = '';
            }
			$(this).removeClass('hint');
        }).
        blur(function(){
			var showBlank = this.value=='' || this.value == this.title;
            if(showBlank) {
                this.value = this.title;
            }
			$(this).toggleClass('hint', showBlank);
        }).each(function() {
			if(this.title==this.value) {
                $(this).addClass('hint');
            }
		});

	$('.ellipsis').each(function() {
		$(this).attr("title", $(this).text());
	}).textOverflow();

	setTimeout(function() {
		$('.blink').blur();
	}, 100);

	configureCompanyBox();
	configSideCart();


	var finishFunction = function() {
			configPreviews();
		};

	if($.browser.msie && $.browser.version < 7) {
		// delayed on IE6 to prevent conflicts with PNG correction
		setTimeout(finishFunction, 500);
	} else {
		finishFunction();
	}

	if($.fn.cluetip) {
		$(".costPopup").cluetip({width: 170});
	}

	if($("#toptabs .tabsLeft").length) {
		// handle tab height
		var	updateTabSize = function() {
				var t = $("#toptabs .tabsLeft li:last a").offset().top,
					twotabs = t > 140; // top row of tabs should be 120px or 140px from doc top (more for beta and dev)
				$("#toptabs .tasbLeft li:first span").text(t);
				$("#content").toggleClass("twotabs", twotabs);
				if(twotabs) {
					// see if spacer exists
					if(!$("#toptabs .tabSpacerParent").length) {
						$('<li class="tabSpacerParent"><a class="tabSpacer"></a></li>').prependTo("#toptabs .tabsLeft");
					}
				} else {
					$("#toptabs .tabSpacerParent").remove();
				}
			};
		updateTabSize();
		$(window).resize(updateTabSize);
	}
});

//---------------------------------------------------------------------------

/**
 * Returns a non-relative URL for the given partial URL
 */
function site_url(url) {
    if(url.indexOf(':') == -1) {
		return SMARTDOX_ROOT + url;
	} else {
		return url;
	}
}

//---------------------------------------------------------------------------

/**
 * Configures the choose company, if it exists.
 */
function configureCompanyBox() {
	var company = $('#orderAsCompany');
	// assume autocomplete has been loaded if the box exists
	if(company.length === 0) {
		return;
	}

	var selCompanyId = '',
		isChecking = false,
		originalCompany = company.val(),
		setCompany = function(e) {
			if(e && e.preventDefault) {
				e.preventDefault();
			}
			if(isChecking || originalCompany == company.val()) {
				// de nada
				return;
			}
			isChecking = true;
			// send the requested company to the server
			$.post(
				site_url('company/set'),
				{
					'companyId': selCompanyId,
					'companyName': company.val()
				}, function(result) {
					if(result != 'OK') {
						alert('Invalid Company - ' + company.val() + ', please try again.');
						company.val(originalCompany);
						selCompanyId = '';
						company.select();
						isChecking = false;
					} else {
						if(window.SMARTDOX_FANCYBOX) {
							window.parent.$.fancybox.close();
						} else {
							// force a page refresh
							refresh();
						}
					}
				}
			);
		},
		sizeText = function() {
			// shink text for wide names
			var valLen = company.val().length;
			if(valLen*5.2 > company.width()) {
				company.css("fontSize", "10px");
			} else if(company.val().length*5.6 > company.width()) {
				company.css("fontSize", "11px");
			} else {
				company.css("fontSize", "12px");
			}
		};
	company.focus(function(){
		company.select();
	}).keyup(function(e) {
		if(e.keyCode == '13') {
			e.preventDefault();
			setCompany();
		} else {
			if(originalCompany != company.val()) {
				selCompanyId = '';
			}
		}
		sizeText();
	}).autocomplete(site_url('company/search'), {
		width: 260,
		autoFill: false,
		max: 50
	}).result(function(event, data, formatted) {
		selCompanyId = data[1];
		setCompany();
		sizeText();
	});

	sizeText();

	$('#orderAsCompanySubmit').click(setCompany);
	if($("#orderAsCompanyEdit").length > 0) {
		$("#orderAsCompanyEdit").fancyboxStretch({
			href: site_url("company/edit"),
			type: "iframe",
			width: 700,
			height: 450
		});
	}
	$("#orderAsCompanyDualBrand").click(function() {
		$.post(
			site_url('company/toggleDualBrand'),
			{},
			function(response) {
				$("#orderAsCompanyDualBrand")
					.toggleClass("enabled", !!response)
					.toggleClass("disabled", !response);
			}
		);
	});
}

//---------------------------------------------------------------------------

function showWelcomePage() {
	if(window.parent == window) {
		$(function() {
			setTimeout(function() {
				// show the company popup to force the user to select a company.
				$.fancyboxStretch({
					href: site_url('welcome'),
					type: 'iframe',
					width: 600,
					height: 375,
					onClosed: function() {
						if(window.showCompanyPopupAfterWelcome) {
							showCompanyPopup();
						}
					}
				});
			}, 250);
		});
	}
}

function showCompanyPopup() {
	if(window.parent == window) {
		$(function() {
			setTimeout(function() {
				// show the company popup to force the user to select a company.
				$.fancyboxStretch({
					modal: true,
					href: site_url('company/force'),
					type: 'iframe',
					orig: $("#orderAsCompany"),
					width: 500,
					height: 325,
					onClosed: refresh
				});
			}, 250);
		});
	}
}

function showFixCompanyPopup(id) {
	if(window.parent == window) {
		$(function() {
			setTimeout(function() {
				// show the company popup to force the user to select a company.
				$.fancyboxStretch({
					modal: true,
					href: site_url('company/edit/'+id+'/forceEdit'),
					type: 'iframe',
					width: 700,
					height: 450,
					onClosed: refresh
				});
			}, 500);
		});
	}
}

function switchToAddCompany() {
	if(window.parent) {
		window.parent.$.fancyboxStretch.setSize(700, 450);
		document.location = site_url("company/add/force");
	}
}

function backToCompanyPopup() {
	if(window.parent) {
		window.parent.$.fancyboxStretch.setSize(500, 325);
		document.location = site_url("company/force");
	}
}

//---------------------------------------------------------------------------

// reload the page
function refresh(url) {
	// disable all links
	$("a").each(function() {
      $(this).attr('href',"javascript:").click(function(){});
    });
	if(url && typeof url == "string") {
		window.location = site_url(url);
	} else {
		// forcibly reload the page
		window.location.reload(true);
	}
}

//---------------------------------------------------------------------------

// reload the parent page
function refresh_parent(url) {
	// disable all links
	$("a").attr('href',"javascript:").click(function(){});

	if(url && typeof url == "string") {
		window.parent.location = site_url(url);
	} else {
		// forcibly reload the page
		window.parent.location.reload(true);
	}
}

//---------------------------------------------------------------------------

// closes the fancybox from the parent window.
function closeFancybox() {
	if(window.parent && window.parent.$ && window.parent.$.fancybox) {
		window.parent.$.fancybox.close();
		return true;
	} else if($ && $.fancybox) {
		$.fancybox.close();
		return true;
	}
	return false;
}

//---------------------------------------------------------------------------

function configPreviews() {
	if($.fn.cluetip) {
		$(".previewPopup:not(.hasCluetip)").addClass("hasCluetip").cluetip({width: 462, clickThrough: true});
		$(".packagePreviewPopup:not(.hasCluetip)").addClass("hasCluetip").cluetip({width: 500});
	}
}

//---------------------------------------------------------------------------

function configSideCart() {
	$("#sideCartCount").click(function() {
		if($("#sideCart").hasClass("open")) {
			closeSideCart();
		} else {
			openSideCart();
		}
	});
}

//---------------------------------------------------------------------------

function openSideCart() {
	var sc = $("#sideCart");
	if(!sc.hasClass("open") && !sc.hasClass("empty")) {
		sc.addClass("open");
		$("#sideCartContents").slideDown("fast", function() {
			if(!sc.hasClass("loaded")) {
				updateSideCart();
			}
		});
	}
}

//---------------------------------------------------------------------------

function closeSideCart() {
	var sc = $("#sideCart");
	if(sc.hasClass("open")) {
		sc.removeClass("open");
		$("#sideCartContents").slideUp("fast");
	}
}

//---------------------------------------------------------------------------

function updateSideCart(count) {
	var sc = $("#sideCart");
	if(count !== undefined && count !== null) {
		var countMsg;
		if(count === 0) {
			countMsg = 'Your cart is empty.';
			closeSideCart();
			sc.addClass("empty");
		} else {
			sc.removeClass("empty");
			countMsg = 'Your cart contains ' + count + ' item';
			if(count != 1) {
				countMsg += 's';
			}
			countMsg += ".";
		}
		$("#sideCartCount").text(countMsg);
	}
	if(!sc.hasClass("open")) {
		sc.removeClass("loaded");
		$("#sideCartContents").text('');
		return;
	}
	if(!sc.hasClass("loaded")) {
		sc.addClass("loading");
	}
	$("#sideCartContents").load(site_url("catalog/sidecart_contents"),{},function() {
		sc.removeClass("loading").addClass("loaded");
		configPreviews();
	});
}

//---------------------------------------------------------------------------

function showWaitingSpinner(show, msg, success) {
	if(show === undefined) {
		show = true;
	}
	var $spinner = $("#waitingSpinner");
	if(show) {
		if(!$spinner.length) {
			$spinner = $('<div id="waitingSpinner"><div id="waitingSpinnerOverlay"/><div id="waitingSpinnerAnim"></div></div>');
			$(document.body).append($spinner);
		}
		if(msg) {
			msg = $("<span/>").text(msg);
		} else {
			msg = "";
		}
		$spinner.toggleClass("success", !!success);
		$spinner.find("#waitingSpinnerAnim").html(msg).toggleClass("msg", !!msg);
		$spinner.show();
	} else {
		$spinner.hide();
	}
}