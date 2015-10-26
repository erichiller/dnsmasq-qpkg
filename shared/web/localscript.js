$(function() {
	$( "#tabs" ).tabs();
});
$(function (){
	$( ".convertTable" ).click( function() {
		var target = this.id.substring(this.id.indexOf("_")+1);
		var table = "table_" + target;
		table = $('#'+table).tableToJSON(); // Convert the table into a javascript object
		sendJSON(JSON.stringify(table),target);
	})
})


$(".ajaxForm").ajaxForm();
$(function(){
	$( ".addRow").click( function(){
		var target = this.id.substring(this.id.indexOf("_")+1);
		var table = document.getElementById("table_" + target);
		var row = table.getElementsByTagName('tbody')[0].insertRow(-1);
		var tdCount = table.firstElementChild.firstElementChild.childElementCount;
		for(i = 0; i < tdCount; i++){
			var cell = row.insertCell(i);
			var newText = document.createTextNode("testText");
			cell.appendChild(newText);
		}
	})
})	


function sendJSON(jsonstring, target, destURL){
	//json should already be stringify()
	//default destURL
	destURL = typeof destURL !== 'undefined' ? destURL : '/dnsmasq/postdat.php';
	destURL = destURL + '?target=' + target;
	var xhr = new XMLHttpRequest();
	xhr.open("POST", destURL , true);
	xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
	console.log("sending:"+jsonstring);
	// send the collected data as JSON
	xhr.send(jsonstring);
	
	xhr.onloadend = function () {
		// done
	};
}
