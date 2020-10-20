$(document).ready(function() {
	$("#content").on("click", "#addFilter", function() {
		addCriteriaFilter();
	});

	$("#content").on("change", "#downloadAttributes", function() {
		addCriteriaFilter();
	});

	$("#criteriaFields").on("click", "[data-remove-criteria]", function(e) {
		e.preventDefault();
		$(this).closest('.field').remove();
	});

	$("#content").on("click", "#refreshResults", function() {
		$("#reportsForm").attr('action', '');
		$("#reportsForm").submit();
	});

	$("#content").on("click", "#exportAsCsv", function() {
		$("#reportsForm").attr('action', $("#reportsForm").attr('data-export-action'));
		$("#reportsForm").submit();
	});

	$("#content").on("click", "#saveReport",  function(e) {
		e.preventDefault();
		var saveReportUrl = $("#reportsForm").attr('data-save-report-action');
		var formData = $("#reportsForm").serializeArray();
		formData.push({'name' : window.csrfTokenName, 'value' : window.csrfTokenValue});
		console.log(formData);
		$.ajax({
			"type": "POST",
			"url": saveReportUrl,
			"dataType": "json",
			"data": formData,
			"success": function(data, textStatus, jqXHR) {
				if ( data['url'] !== undefined ) {
					window.location.href = data['url'];
				}
			},
			"error": function(jqXHR, textStatus, errorThrown) {

			}
		});
	});

	$("#content").on("click", "#linkVaultCheckAll", function(event) {
		if(this.checked) {
			$("[data-linkvault-checkbox]").each(function() {
				this.checked = true;
			});
		} else {
			$("[data-linkvault-checkbox]").each(function() {
				this.checked = false;
			});
		}
	});

	$("#content").on("click", "#linkVaultDeleteSubmit", function(event) {
		if ( !confirm("Are you sure you want to delete the checked records?") ) {
			return false;
		}
	});
});

function addCriteriaFilter()
{
	var fieldName = $("#downloadAttributes").val();
	// Make sure the specified criteria field doesn't already exist.
	if ( fieldName && ! $("#criteria_"+fieldName).length ) {
		var html = $("<div class='field' ><div class='heading'><label>"+fieldName+"</label></div><div class='input ltr' ><input type='text' class='text' name='criteria["+fieldName+"]' ><a href='#' class='light remove-criteria' title='Remove criteria' data-remove-criteria >X</a></div></div>");
		$(html).appendTo("#criteriaFields");
	}
}
