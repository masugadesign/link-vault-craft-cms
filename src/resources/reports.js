$(document).ready(function() {
	$("#content").on("click", "#addFilter", function() {
		var fieldName = $("#downloadAttributes").val();
		var html = $("<div class='field' ><div class='heading'><label>"+fieldName+"</label></div><div class='input ltr' ><input type='text' class='text' name='criteria["+fieldName+"]' ></div></div>");
		$(html).appendTo("#criteriaFields");
	});

	$("#sidebar").on("click", "#refreshResults", function() {
		$("#reportsForm").submit();
	});
});
