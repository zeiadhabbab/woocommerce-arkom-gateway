// console.log('sssss');
//  var iframe = document.getElementById("PaymentIframe");
//  	iframe.addEventListener("DOMAttrModified", function(event) {
//  	    if (event.attrName == "src") {
//  	       // The "src" attribute changed
// 			console.log('aaaaaaa');
//  			top.window.location.href='URLGoesHere';
//  	    }
//  	});


	function iframeChanged(t) {
		top.window.location.href=t;
	}