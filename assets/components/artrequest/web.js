$(function () {
	"use strict";
	
	function initializeFields() {
		$(".with-federal-subjects").each(function () {
			var options = [], cities = {}, subjects;
			
			$("<option></option>")
				.attr("value", "")
				.appendTo(this);
			
			for (var district in artRequest.federalDistrictsSubjects) {
				subjects = artRequest.federalDistrictsSubjects[district];
				for (var i = 0; i < subjects.length; ++i) {
					if ($.inArray(subjects[i], artRequest.federalCities) == -1) {
						options.push($("<option></option>").text(subjects[i]).data("district", district));
					} else {
						cities[subjects[i]] = district;
					}
				}
			}
			
			for (var i = 0; i < artRequest.federalCities.length; ++i) {
				$("<option></option>")
					.text(artRequest.federalCities[i])
					.data("district", cities[artRequest.federalCities[i]])
					.appendTo(this)
				;
			}
			
			$("<option disabled></option>").appendTo(this);
			
			$(this).append(options.sort(function (a, b) {
				return (a.text() == b.text()) ? 0 : ((a.text() < b.text()) ? -1 : 1);
			}));
			
			$(this).change(function () {
				$(this).siblings(".with-federal-district").text($(this).find("option:selected").data("district") || "");
			}).change();
		});
		
		$(".with-person-reasons").each(function () {
			for (var i = 0; i < artRequest.requestReasons.length; ++i) {
				$("<input type=\"radio\" name=\"person_reason\">")
					.attr("required", "required")
					.attr("value", i)
					.appendTo(this)
					.wrap("<div class=\"radio\"><label></label></div>")
					.after(" " + artRequest.requestReasons[i])
				;
			}
		});
		
		$(".with-orgperson-reasons").each(function () {
			for (var i = 0; i < artRequest.requestReasons.length; ++i) {
				$("<input type=\"radio\" name=\"orgperson_reason\">")
					.attr("required", "required")
					.attr("value", i)
					.appendTo(this)
					.wrap("<div class=\"radio\"><label></label></div>")
					.after(" " + artRequest.requestReasons[i])
				;
			}
		});
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	function initializeApplicants() {
		var personCount = 1;
		var orgPersonCount = $("#requestFormApplicantsOrgPersons + .dropdown-menu > *").length;
		
		var fieldsets = [];
		for (var i = 0; i < personCount + orgPersonCount; ++i) {
			fieldsets[i] = $(i < personCount ? "#requestFormPerson" : "#requestFormOrgPerson").clone(true);
			
			fieldsets[i].attr("id", "").find("*").each(function () {
				if ($(this).attr("id")) {
					$(this).attr("id", $(this).attr("id") + i);
				}
				if ($(this).attr("for")) {
					$(this).attr("for", $(this).attr("for") + i);
				}
				if ($(this).attr("name")) {
					$(this).attr("name", $(this).attr("name") + "[" + i + "]");
				}
			});
			
			fieldsets[i].addClass((i < personCount ? i : i - personCount) % 2 ? "even" : "odd");
			
			fieldsets[i].appendTo("#requestFormApplicants");
		}
		
		$("#requestFormPerson, #requestFormOrgPerson").remove();
		
		function switchApplicants(persons, count) {
			for (var i = 0; i < personCount; ++i) {
				if (persons && i < count) {
					fieldsets[i].removeAttr("disabled").show();
				} else {
					fieldsets[i].hide().attr("disabled", "disabled");
				}
			}
			for (var i = 0; i < orgPersonCount; ++i) {
				if (!persons && i < count) {
					fieldsets[personCount + i].removeAttr("disabled").show();
				} else {
					fieldsets[personCount + i].hide().attr("disabled", "disabled");
				}
			}
			
			$("#requestFormApplicantsPerson").toggleClass("active", persons);
			$("#requestFormApplicantsOrgPersons").toggleClass("active", !persons);
			
			if (!persons) {
				$("#requestFormApplicantsOrgPersons").data("last", count);
				$("#requestFormApplicantsOrgPersons > span").text(
					$("#requestFormApplicantsOrgPersons + .dropdown-menu > *:eq(" + (count - 1) + ")").text()
				);
			}
			
			$("#requestForm .for-persons").toggle(!!persons);
			$("#requestForm .for-orgpersons").toggle(!persons);
			
			if (!!persons) {
				$("#requestFormContactsOrganization, #requestFormContactsPosition")
					.attr("disabled", "disabled")
					.closest(".form-group").hide();
			} else {
				$("#requestFormContactsOrganization, #requestFormContactsPosition")
					.removeAttr("disabled")
					.closest(".form-group").show();
			}
		};
		
		$("#requestFormApplicantsPerson").click(function () {
			switchApplicants(true, 1);
		});
		$("#requestFormApplicantsOrgPersons").click(function () {
			switchApplicants(false, $(this).data("last") || 1);
		});
		$("#requestFormApplicantsOrgPersons + .dropdown-menu > *").click(function () {
			switchApplicants(false, $(this).index() + 1);
		});
		
		switchApplicants(false, 1);
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	function initializeWorks() {
		var worksCount = 3;
		
		for (var i = worksCount; i > 0; --i) {
			var $fieldset = $("#requestFormPersonWork").clone(true);
			
			$fieldset.attr("id", "").find("*").each(function () {
				if ($(this).attr("id")) {
					$(this).attr("id", $(this).attr("id") + i);
				}
				if ($(this).attr("for")) {
					$(this).attr("for", $(this).attr("for") + i);
				}
				if ($(this).attr("name")) {
					$(this).attr("name", $(this).attr("name") + i);
				}
			});
			
			$fieldset.attr("disabled", "disabled").hide();
			
			$fieldset.insertAfter("#requestFormPersonWork");
		}
		
		for (var i = worksCount; i > 0; --i) {
			var $fieldset = $("#requestFormOrgPersonWork").clone(true);
			
			$fieldset.attr("id", "").find("*").each(function () {
				if ($(this).attr("id")) {
					$(this).attr("id", $(this).attr("id") + i);
				}
				if ($(this).attr("for")) {
					$(this).attr("for", $(this).attr("for") + i);
				}
				if ($(this).attr("name")) {
					$(this).attr("name", $(this).attr("name") + i);
				}
			});
			
			$fieldset.attr("disabled", "disabled").hide();
			
			$fieldset.insertAfter("#requestFormOrgPersonWork");
		}
		
		$("#requestFormPersonWork, #requestFormOrgPersonWork").remove();
		
		$("#requestForm").on("click", ".js-works-add", function () {
			var $fieldset = $(this).closest("fieldset");
			
			var $works = $fieldset.find(".work");
			var $work = $works.filter(":hidden:first");
			var index = $works.index($work);
			
			if (!$work.length) {
				$(this).hide();
				return;
			}
			
			$work.removeAttr("disabled").show();
			$fieldset.find(".js-works-add").toggle(index < worksCount - 1);
			$fieldset.find(".js-works-remove").toggle(index > 0);
		});
		
		$("#requestForm").on("click", ".js-works-remove", function () {
			var $fieldset = $(this).closest("fieldset");
			
			var $works = $fieldset.find(".work");
			var $work = $works.filter(":visible:last");
			var index = $works.index($work);
			
			if (!$work.length) {
				$(this).hide();
				return;
			}
			
			$work.attr("disabled", "disabled").hide();
			$fieldset.find(".js-works-add").toggle(index - 1 < worksCount - 1);
			$fieldset.find(".js-works-remove").toggle(index - 1 > 0);
		});
		
		$("#requestForm .js-works-add").click();
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	function initializeForm() {
		$(".with-datetimepicker").datetimepicker({
			"language": "ru",
			"pickTime": false,
			"useCurrent": false
		});
		
		$("#requestForm :input").on("invalid", function () {
			$("#requestForm").addClass("submitted");
		});
		
		$("#requestForm").submit(function (e) {
			$(this).addClass("submitted");
			
			$(this).find("[required]:enabled").each(function () {
				if ($(this).parents("[disabled]").length) {
					return;
				}
				
				if ($(this).attr("type") == "checkbox") {
					if (!this.checked) {
						alert("Пожалуйста, поставьте галочку для продолжения.");
						$(this).focus();
						
						e.preventDefault();
						return false;
					}
				} else if ($(this).val() == "") {
					alert($(this).attr("type") == "file" ? "Пожалуйста, выберите файл." : "Пожалуйста, заполните поле.");
					$(this).focus();
					
					e.preventDefault();
					return false;
				}
			});
			
			$(this).find("input[type=file]:enabled").each(function () {
				if ($(this).parents("[disabled]").length) {
					return;
				}
				
				if ($(this).val()) {
					if ($(this).attr("accept") == "image/*" && $(this).val().search(/\.(jpe?g|png)$/i) < 0) {
						alert("Пожалуйста, выберите файл формата JPEG или PNG.");
						$(this).focus();
						
						e.preventDefault();
						return false;
					}
				}
			});
		});
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	
	initializeFields();
	initializeWorks();
	initializeApplicants();
	initializeForm();
	$('#requestFormApplicantsPerson').click();
	$("#requestForm[action]").removeClass("hidden");
});
