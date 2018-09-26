$(document).ready(function() {
	$("#content").on("click", "#addFilter", function() {
		addCriteriaFilter();
	});

	$("#content").on("change", "#downloadAttributes", function() {
		addCriteriaFilter();
	});

	$("#criteriaFields").on("click", "[data-remove-criteria]", function(e) {
		e.preventDefault();
		$(this).parent().remove();
	});

	$("#sidebar").on("click", "#refreshResults", function() {
		$("#reportsForm").attr('action', '');
		$("#reportsForm").submit();
	});

	$("#sidebar").on("click", "#exportAsCsv", function() {
		$("#reportsForm").attr('action', $("#reportsForm").attr('data-export-action'));
		$("#reportsForm").submit();
	});
});

function addCriteriaFilter()
{
	var fieldName = $("#downloadAttributes").val();
	if ( fieldName ) {
		var html = $("<div class='field' ><a href='#' class='remove-criteria' data-remove-criteria >X</a><div class='heading'><label>"+fieldName+"</label></div><div class='input ltr' ><input type='text' class='text' name='criteria["+fieldName+"]' ></div></div>");
		$(html).appendTo("#criteriaFields");
	}
}
