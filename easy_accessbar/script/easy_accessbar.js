$(document).ready(function(){  
  	$('.leftside').click(function(event) {
		event.stopPropagation();  
	});
	
	$(document).click(function() {
		if(shownList!=null){
			toggleVisiblility(document.getElementById(shownList));
			shownList = null;
		}
		init();
	});
	
	for (i=0; i<idArray.length; i++){
		var list = '#'+idArray[i].toLowerCase()+'List';
		$(list).slimScroll({
			position: 'right',
			height: getListHeight(idArray[i].toLowerCase()+'List')+'px',
			railVisible: true,
			railOpacity: 0.3,
			wheelStep: 10,
			allowPageScroll: true
		});
	}
	
	if (!document.styleSheets[0].cssRules){  // IF IE        
		IE = true;
    }
});
	
	var idArray = ['Assignment','Quiz','Resource','UE','Forum','Other'];
	var IE = false;
	
	window.onfocus = function(){init();}

	function listMouseOver(item){
		var divs = item.children;
		divs[1].style.color='white';
	}
	function listMouseOut(item){
		var divs = item.children;
		divs[1].style.color='gray';
	}
	
	function init(){	
		setMaxHeights();		
		setTipDisplay();
	}
	
	function setMaxHeights(){
		if (typeof window.innerHeight != 'undefined') {
			var height = window.innerHeight-40 +'px';
			for (i=0; i<idArray.length; i++){
				setListMaxHeight(idArray[i].toLowerCase()+'List', height);
			}
      	} 		
 	}
	
	function getListHeight(id){
		if(document.getElementById(id) != null){
			return document.getElementById(id).style.maxHeight-100;
		}else{
			return 300;
		}
	}
	
	function setListMaxHeight(id, maxHeight){
		if(document.getElementById(id) != null){
			document.getElementById(id).style.maxHeight = maxHeight;
		}
	}
	
	function setTipDisplay(){
		for (i=0; i<idArray.length; i++){
			setCountVisibility(idArray[i]);
		}
	}
	
	function setCountVisibility(id){
		var state = readCookie(id+'CountState');		
		if(state =='Y'){
			if(document.getElementById(id+'Count') != null)
				document.getElementById(id+'Count').style.display = 'none';
		}
	}
	
 	var shownList;
 	
 	function toggleVisiblility(item) { 	 		
 	 	var id = item.id;
 		if(shownList){
 			toggleDisplay(shownList);
			changeBGColor('inherit', shownList);
			
 			if(shownList==id){				
 				shownList=null;
 			}
 			else{ 				
 				toggleDisplay(id);
				changeBGColor('white', id);
 				shownList = id;
 			} 			
 		}
 		else{ 			
       		toggleDisplay(id);
        	shownList = id;
			changeBGColor('white', id);
        }
		setMaxHeights();	
    } 
	
	function changeBGColor(color, id){
		if(!IE){
			document.getElementById(id).style.backgroundColor = color;
		}
	}
    
    function toggleDisplay(id){
    	
    	var divId = 'notify'+id;
    	
    	var state = document.getElementById(divId).style.display;
        if (state == 'block') {
        	document.getElementById(divId).style.display = 'none';
        } else {
        	document.getElementById(divId).style.display = 'block';
        }		
		createCookie(id+'CountState','Y');
		setCountVisibility(id);
    }
	
	function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}

	function readCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ')
				c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) 
				return c.substring(nameEQ.length,c.length);
		}
		return null;
	}
	
