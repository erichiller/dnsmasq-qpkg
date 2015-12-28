$(function() {
	$("#tabs" ).tabs();
});

$(function (){
	$( ".convertTable" ).click( function() {
		var target = this.id.substring(this.id.indexOf("_")+1);
		var table = "table_" + target;
		table = $('#'+table).tableToJSON(); // Convert the table into a javascript object
		sendJSON(JSON.stringify(table),target);
	})
})

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

function getAjax(destURL,callback){
	var xhr = new XMLHttpRequest();
	xhr.open("GET", destURL, true)
	xhr.send()
	xhr.onloadend = function() {
        callback(xhr.responseText);
    }
}

function sendJSON(jsonstring, target, destURL){
	//json should already be stringify()
	//default destURL
	destURL = typeof destURL !== 'undefined' ? destURL : '/postdat.php';
	destURL = destURL + '?target=' + target;
	var xhr = new XMLHttpRequest();
	xhr.open("POST", destURL , true);
	xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
	console.log("sending:"+jsonstring);
	// send the collected data as JSON
	xhr.send(jsonstring);
	
	xhr.onloadend = function () {
		if(xhr.response != null && xhr.response){
			alert(xhr.response);
		} else {
			alert('Connection Failure');
		}
	}
}

function notify(text){
	console.log("notify("+text+")")
	var button = document.createElement("div");
	button.addEventListener("click",function(event){
		var el = event.target
		el.parentElement.parentElement.removeChild(el.parentElement)
	});
	button.className='button';
	var buttonText = document.createTextNode("Close");
	button.appendChild(buttonText);
	
	var header = document.createElement("h5");
	var headerText = document.createTextNode("Notification");
	header.appendChild(headerText);
	
	var popup = document.createElement("div");
	popup.className = 'notify';	
	var notification = document.createTextNode(text);
	var p = document.createElement('p');
	p.appendChild(notification)
	popup.appendChild(header)
	popup.appendChild(p);
	popup.appendChild(button)
	document.body.appendChild(popup);
}

window.onload = function() {
	var $enableDHCP = document.getElementById('enable-dhcp')
	
	/**
	// callback for ajaxForm (jQuery) to provide result
	$('.ajaxForm').ajaxForm(function(){
		alert("Thank you for your comment!"); 
	});
	**/ 
	
	$('.ajaxForm').submit(function() { // catch the form's submit event
		var formData = new FormData($(this)[0]);
		$.ajax({ // create an AJAX call...
			type: 'POST',
			data: formData,
			async: true,
			cache: false,
			contentType: false,
			processData: false,
			url: $(this).attr('action'), // the file to call
			success: function(response) { // on success..
				notify(response)
			},
			error: function(xhr, status, error) {
				notify(xhr.responseText)
			}
		});
		return false; // cancel original event to prevent form submitting
	});
	
	
	var toggleDisplayDiv = function(){
		var el = document.getElementById("el-"+$enableDHCP.id);
		console.log("display="+el.style.display+",checked="+$enableDHCP.checked)
		if($enableDHCP.checked == true){
			el.style.display = 'block';
		} else {
			el.style.display = 'none';
		}

	};
	setTimeout(toggleDisplayDiv, 0)
	$enableDHCP.addEventListener('click',toggleDisplayDiv)
}