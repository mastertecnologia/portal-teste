var icons = [
	{ icon: "fa fa-glass" },
	{ icon: "fa fa-music" },
	{ icon: "fa fa-search" },
	{ icon: "fa fa-envelope-o" },
];

var itemTemplate = '<li> <a data-class="{{item}} {{activeState}}" data-index="{{index}}"> <i class="{{item}}"></i> <span class="name-class">{{item}}</span> </a> </li>';

$(".icon-picker-list").html("");


// Loop through JSON and appends content to show icons
$(icons).each(function(index) {
	var itemtemp = itemTemplate;
	var item = icons[index].icon;

	if (index == selectedIcon) {
		var activeState = "active";
	} else {
		var activeState = "";
	}

	itemtemp = itemtemp
		.replace(/{{item}}/g, item)
		.replace(/{{index}}/g, index)
        .replace(/{{activeState}}/g, activeState);
	$(".icon-picker-list").append(itemtemp);
});

    // Variable that's passed around for active states of icons
var selectedIcon = null;

    $(".icon-class-input").each(function() {
	if ($(this).val() != null) {
		$(this)
			.siblings(".demo-icon")
			.addClass($(this).val());
	}
});

// To be set to which input needs updating
var iconInput = null;

$(document).on('click', '#abremodal', function() {
	// Sets var to which input is being updated
	iconInput = $(this).siblings(".icon-class-input");
	// Shows Bootstrap Modal
	$("#iconPicker").modal("show");
	// Sets active state by looping through the list with the previous class from the picker input
	selectedIcon = findInObject(
		icons,
		"icon",
		$(this)
			.siblings(".icon-class-input")
			.val()
	);
	// Removes any previous active class
	$(".icon-picker-list a").removeClass("active");
	// Sets active class
	$(".icon-picker-list a")
		.eq(selectedIcon)
		.addClass("active");
});


// Click function to select icon
$(document).on("click", ".icon-picker-list a", function() {
	// Sets selected icon
	selectedIcon = $(this).data("index");
	// Removes any previous active class
	$(".icon-picker-list a").removeClass("active");
	// Sets active class
	$(".icon-picker-list a")
		.eq(selectedIcon)
		.addClass("active");
});

// Update icon input
$("#change-icon").click(function() {
	iconInput.val(icons[selectedIcon].icon);
	iconInput.siblings(".demo-icon").attr("class", "demo-icon");
	iconInput.siblings(".demo-icon").addClass(icons[selectedIcon].icon);
	$("#iconPicker").modal("hide");
	console.log(iconInput);
	console.log(icons[selectedIcon].icon);
});

function findInObject(object, property, value) {
	for (var i = 0; i < object.length; i += 1) {
		if (object[i][property] === value) {
			return i;
		}
	}
}
