			var myElement = document.getElementById('root');
			var hammertime = new Hammer(myElement);
			//hammertime.get('pan').set({ direction: Hammer.DIRECTION_ALL });
			hammertime.on('swiperight', function(ev) {
				var url = document.head.querySelector("meta[chapter_prev]").getAttribute("chapter_prev");
				if (url !== '') {
					myElement.className += " animated slideOutRight";
					redirect(url);
				}
			});
			hammertime.on('swipeleft', function(ev) {
				var url = document.head.querySelector("meta[chapter_next]").getAttribute("chapter_next");
				if (url !== '') {
					myElement.className += " animated slideOutLeft";
					redirect(url);
				}
			});
			hammertime.on('pandown', function(ev) {
				if (window.scrollY == 0) {
					var url = document.head.querySelector("meta[chapter_prev]").getAttribute("chapter_prev");
					if (url !== '') {
				        myElement.className += " animated slideOutDown";
						redirect(url);
					}
			    }
			});
			hammertime.on('panup', function(ev) {
				if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight) {
					var url = document.head.querySelector("meta[chapter_next]").getAttribute("chapter_next");
					if (url !== '') {
				        myElement.className += " animated slideOutUp";
						redirect(url);
					}
			    }
			});
			function redirect(url) {
			  setTimeout(
			    function() {
			      window.location.href = url;
			    }, 800);
			}
			document.body.onload = function(){

				var metas = document.getElementsByTagName('meta'); 

		        var prev = '';
		        var next = '';
		         for (var i=0; i<metas.length; i++) { 
		            if (metas[i].getAttribute("chapter_prev") != null) {
		              //console.log("prev " + metas[i].getAttribute("chapter_prev"));
		              prev = metas[i].getAttribute("chapter_prev");
		            } else if (metas[i].getAttribute("chapter_next") != null) {
		              //console.log("next " + metas[i].getAttribute("chapter_next"));
		              next = metas[i].getAttribute("chapter_next");
		            }
		         } 

		        var url = window.location.href;
		        //console.log(url);
		        var segements = url.split("/");
		        if (prev !== '' && prev !== null) {
		          segements[segements.length - 1] = "" + prev;
		          prev = segements.join("/");
		        }
		        if (next !== '' && next !== null) {
		          segements[segements.length - 1] = "" + next;
		          next = segements.join("/");
		        }

		        /*console.log("url: " + url);
		        console.log("next: " + next);
		        console.log("prev: " + prev);*/

				window.parent.postMessage({
				    'function': 'onLoad',
				    'url': window.location.href,
				    'title': document.title,
				    'prev': prev,
				    'next': next,
				}, "*");
			};
			/*window.onbeforeunload = function(){
				console.log("onbeforeunload");
				//var myElement = document.getElementById('root');
	            //myElement.className += " animated slideOutUp";
	        };*/
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