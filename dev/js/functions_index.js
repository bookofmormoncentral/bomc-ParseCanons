			var myElement = document.getElementsByClassName('content')[0];

			document.body.onload = function(){
					window.parent.postMessage({
					    'function': 'onLoadIndex',
					    'url': window.location.href,
					}, "*");
			};
			/*window.onbeforeunload = function(){
				console.log("onbeforeunload");
				//var myElement = document.getElementById('root');
	            myElement.className += " animated slideOutUp";
	        };*/
	        function openLink(link, modal) {
	        	if (modal == 0) {
	        		myElement.className += " animated slideOutLeft";
	        		redirect(link);
	        	} else {
					window.parent.postMessage({
					    'function': 'openLink',
					    'link': link,
					    'url': window.location.href,
					}, "*");
	        	}
	        }
			function redirect(url) {
			  setTimeout(
			    function() {
			      window.location.href = url;
			    }, 400);
			}
	        function goBack() {
			    window.history.back();
			}

			window.addEventListener("message", receiveMessage, false);

			function receiveMessage(event)
			{
			  //console.log("receiveMessage " + event.data);
			  if (event.data == "back") {
			  	goBack();
			  }
			}
