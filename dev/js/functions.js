			var myElement = document.getElementById('root');
			var hammertime = new Hammer(myElement);
			hammertime.on('swiperight', function(ev) {
				myElement.className += " animated slideOutRight";
				redirect(document.head.querySelector("meta[chapter_prev]").getAttribute("chapter_prev"));
			});
			hammertime.on('swipeleft', function(ev) {
				myElement.className += " animated slideOutLeft";
				redirect(document.head.querySelector("meta[chapter_next]").getAttribute("chapter_next"));
			});
			function redirect(url) {
			  setTimeout(
			    function() {
			      window.location.href = url;
			    }, 800);
			}
			document.body.onload = function(){
				//myElement.className += " animated fadeIn";
			};
