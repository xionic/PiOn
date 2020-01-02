$().ready(function(){
	
	//build menu from sitemap
	$(Object.keys(sitemap)).each(function(k, room){
		//console.log(k);
		//console.log(room);
		var roomli = $("<li>", {class: "roomrow"}).append($("<h3>",{text:room, class:"roomname"}));
		var roomul = $("<ul>");
		
		
		
		
		$(sitemap[room]).each(function(key, value){
			itemli = $("<li>");
			itemli.append($("<span>",{class: "itemname", text: value.item_name}));
			var item_value_span = $("<span>",{class: "itemvalue", "data-item_name":value.item_name, "data-type": value.type});
			itemli.append(item_value_span);
			$.ajax({
				url: "../?action=get&item_name="+value.item_name
			}).done(function(data){
				console.log(data);
				//console.log(value);
				var findstr = "span[data-item_name='" + value.item_name + "']";
				//var temp = "span[data-item_name='date test']";
				//console.log(temp);
				//console.log("span[data-item_name='date test']");
				//console.log($.find(temp));
				$.find(findstr)[0].innerHTML = data.value;
			});
			itemli.append();
			roomul.append(itemli);
		});
		roomli.append(roomul);
		$("#rooms").append(roomli);
		
	});
	

	
});