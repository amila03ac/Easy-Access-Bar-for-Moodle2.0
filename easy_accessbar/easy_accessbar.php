<?php

//Easy Access Bar for Moodle2.0 is free software: you can redistribute 
//it and/or modify it under the terms of the GNU General Public License 
//as published by the Free Software Foundation, either version 3 of the 
//License, or (at your option) any later version.
//
//Easy Access Bar for Moodle2.0 is distributed in the hope that it will 
//be useful, but WITHOUT ANY WARRANTY; without even the implied warranty 
//of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with Easy Access Bar for Moodle2.0.  If not, 
//see <http://www.gnu.org/licenses/>.

/*--------------------------------------------------------------------------------------*/
/*---------------------------  EASY ACCESS BAR FOR MOODLE2.0 ---------------------------*/
/*--------------------------------------------------------------------------------------*/
/*	By: Amila Ariyarathna																*/
/*		Department of Computer Science and Engineering									*/
/*		University of Moratuwa.															*/
/*--------------------------------------------------------------------------------------*/

require_once('course_updates.php');
global $AddedActs, $UpdatedActs, $OtherAdded, $OtherUpdated, $UpcomingEvents, $ForumPosts, $userPic;

/*If previously fetched data are in the SESSION variable, then use that data*/	
if(array_key_exists('got_data',$_SESSION)){
	get_data_from_session();	
}else{ //else get data from the data base
	$dataclass = new course_updates;
	$data = $dataclass->get_content();
	
	if($data != NULL){
		$_SESSION['got_data'] = true;
		$AddedActs = array("Assignment"=>array(), "Quiz"=>array(), "Resource"=>array());
		$UpdatedActs = array("Assignment"=>array(), "Quiz"=>array(), "Resource"=>array());
		
		$sortedData = $data['updates'];
		$UpcomingEvents = $data['upcoming'];
		$ForumPosts = $data['forumposts'];
		$userPic = $dataclass->get_user_pic();
		$_SESSION['userpic'] = $userPic;
		
		init_variables($sortedData, $UpcomingEvents, $ForumPosts);
	}else{
		$_SESSION['loggedIn'] = false;
		//echo '<script type="text/javascript">alert(\'Y U NO LOGIN\');</script>';
	}
}
	
if($_SESSION['loggedIn']){	
   
    print_bar_head();
	
	print_new_activities($AddedActs, $UpdatedActs);
		
	print_upcoming_events($UpcomingEvents);
	
	print_forum_posts($ForumPosts);
	
	print_other_events($OtherAdded, $OtherUpdated);
		
    print_bar_tail();
    
    //include('\test\tests\course_update_test.php');
}else{
	delete_cookies();
}

/**
 * Fetches previously stored data from the SESSION variable and assignes them to global variables.
 */
function get_data_from_session(){
	global $AddedActs, $UpdatedActs, $UpcomingEvents, $ForumPosts, $OtherAdded, $OtherUpdated, $userPic;
	$AddedActs = $_SESSION['addedActs'];
	$UpdatedActs = $_SESSION['updatedActs']; 
	$UpcomingEvents = $_SESSION['upcomingEvents'];
	$ForumPosts = $_SESSION['forumPosts'];
	$OtherAdded = $_SESSION['otherAdded'];
	$OtherUpdated = $_SESSION['otherUpdated'];
	$userPic = $_SESSION['userpic'];
}

/**
 * Extracts the data from given variables and stores them in global variables.
 */
function init_variables($sortedData, $UpcomingEvents, $ForumPosts){
	global $AddedActs, $UpdatedActs, $UpcomingEvents, $ForumPosts, $OtherAdded, $OtherUpdated;
	if(array_key_exists('Other',$sortedData['Added'])){
		$OtherAdded = $sortedData['Added']['Other'];
		unset($sortedData['Added']['Other']);
	}else{$OtherAdded = array();}
			
	if(array_key_exists('Other',$sortedData['Updated'])){
		$OtherUpdated = $sortedData['Updated']['Other'];
		unset($sortedData['Updated']['Other']);
	}else{$OtherUpdated = array();}	
			
	foreach($sortedData['Added'] as $modname=>$addedData){
		$AddedActs[$modname] = $addedData;
	}
	foreach($sortedData['Updated'] as $modname=>$updatedData){
		$UpdatedActs[$modname] = $updatedData;
	}
	$_SESSION['addedActs'] = $AddedActs;
	$_SESSION['updatedActs'] = $UpdatedActs;
	$_SESSION['otherAdded'] = $OtherAdded;
	$_SESSION['otherUpdated'] = $OtherUpdated;
	$_SESSION['upcomingEvents'] = $UpcomingEvents;
	$_SESSION['forumPosts'] = $ForumPosts;
	$_SESSION['loggedIn'] = true;
}

/**
 * Prints the data in the given arrays to be diaplayed in a web page.
 * 
 * @param array $AddedActs contains an array of newly added course activities
 * @param array $UpdatedActs contains an array of updated course activities
 */
function print_new_activities($AddedActs, $UpdatedActs){

	foreach($AddedActs as $modname=>$acts){
		$lcmodname = strtolower($modname);
		
		echo '<li class="listContainer"><a id="'.$modname.'" title="'.$modname.'s" class="'.$lcmodname.'" onclick="toggleVisiblility(this)">';
		$count = count($acts) + count($UpdatedActs[$modname]);
		if($count>0){
			echo '<span id="'.$modname.'Count" class="count">'.$count.'</span>';
		}
		echo '</a>
				<div id="notify'.$modname.'" class="notifications">
					<ul id="'.$lcmodname.'List" class="updates">';
							
		if(count($acts)>0){
			echo '<li class="listObj">
					<a class="NotifyTip">Added '.$modname.'s</a>
				</li>';				
				foreach($acts as $activity){
					$details = explode("#*#", $activity);
					print_list_item($details);
				}
		}else{
			if(count($UpdatedActs[$modname])==0){
				echo '<li class="listObj">
						<a class="NotifyTip">No new '.$modname.'s</a>
					  </li>';
			}
		}
							
		if(count($UpdatedActs[$modname])>0){
			echo '<li class="listObj">
					<a class="NotifyTip">Updated '.$modname.'s</a>
				  </li>';
				  
			foreach($UpdatedActs[$modname] as $activity){
				$details = explode("#*#", $activity);
				print_list_item($details);															
			}
		}
							
		echo '</ul>
			</div>
		</li>';           		
    }
	
}

/**
 * Prints the data in the given arrays to be diaplayed in a web page.
 * 
 * @param array $eventList contains an array of upcoming events
 */
function print_upcoming_events($eventList){
	echo '<li class="listContainer"><a id="UE" title="Upcoming Events" class="upcoming" onclick="toggleVisiblility(this)"></a>';
		if(count($eventList)>0){
			echo '<span id="UECount" class="count">'.count($eventList).'</span>';
		}
		echo '</a>
			<div id="notifyUE" class="notifications">
				<ul id="ueList" class="updates">';					
	if(count($eventList)>0){
		echo '<li class="listObj">
				<a class="NotifyTip">Upcoming Events</a>
			  </li>';	
		foreach($eventList as $event){
			$details = array($event['events'], $event['course']);
			print_list_item($details);
		}
	}else{
		echo '<li class="listObj">
				<a class="NotifyTip">No Upcoming Events</a>
			  </li>';
	}	
			echo '</ul>
			</div>
		</li>'; 
}

/**
 * Prints the data in the given arrays to be diaplayed in a web page.
 * 
 * @param array $postList contains an array of recent forum posts
 */
function print_forum_posts($postList){
	
	echo '<li class="listContainer"><a id="Forum" title="Forum Posts" class="forum" onclick="toggleVisiblility(this)">';
		if(count($postList)>0){
			echo '<span id="ForumCount" class="count">'.count($postList).'</span>';
		}
			echo '</a>
			<div id="notifyForum" class="notifications">
				<ul id="forumList" class="updates">';
	if(count($postList)>0){
		echo '<li class="listObj">
				<a class="NotifyTip">Recent Forum Posts</a>
		      </li>';
		foreach($postList as $post){
			$details = explode("#*#", $post);
			$postData = array($details[1], $details[0]);
			print_list_item($postData);	
		}
	}else{
		echo '<li class="listObj">
				<a class="NotifyTip">No recent Forum posts</a>
			  </li>';
	}	
			echo '</ul>
			</div>
		</li>';	
}

/**
 * Prints the data in the given arrays to be diaplayed in a web page if there are data.
 * 
 * @param array $postList contains an array of recent forum posts
 */
function print_other_events($OtherAdded, $OtherUpdated){
	if(count($OtherAdded)>0 or count($OtherUpdated)>0){
		$added = array('Other'=>$OtherAdded);
		$updated = array('Other'=>$OtherUpdated);
		print_new_activities($added, $updated);
	}
}

/**
 * Print the head of the easy access bar.
 */
function print_bar_head(){
	global $CFG;
	$themelayoutwww = $CFG->wwwroot.'/theme/'.$CFG->theme.'/layout';
	
	echo '<link href="'.$themelayoutwww.'/easy_accessbar/css/easy_accessbar.css" rel="stylesheet" type="text/css"/>
		<script src="'.$themelayoutwww.'/easy_accessbar/script/jquery_min.js" type="text/javascript"></script>
		<script src="'.$themelayoutwww.'/easy_accessbar/script/easy_accessbar.js" type="text/javascript"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.js"></script>
		<script type="text/javascript" src="'.$themelayoutwww.'/easy_accessbar/script/slimScroll.js"></script>

		<div id="toolbar_container">
			<div id="toolbar">
				<div class="image">
					<a href="http://www.moodle.org" target="_blank" >
						<img class="logo" alt="" src="'.$themelayoutwww.'/easy_accessbar/css/images/moodle.png" title="www.moodle.org"/>
					</a>
				</div> 
				<div class="leftside"> 
					<ul id="social">';
}

/**
 * Print the tail of the easy access bar.
 */
function print_bar_tail(){
	global $userPic, $CFG;
	echo '			</ul>
				</div>';
		
		
		//echo '<div class="userPic">';
		echo $userPic;
	//	echo '</div>';
	//	echo '<div class="logout"><a href=\"'.$CFG->wwwroot.'/login/logout.php\">Logout</a></div>
		echo	'</div></div>
		<script type="text/javascript">init();</script>';
}

/**
 * Print the data given in the array as 2 sub data items in a list.
 * 
 * @param array $details Contains data about a particular events like a new forum post or an added assignment.
 */
function print_list_item($details){
	echo '<li class ="listObj">
			<div class="listObjdata" onmouseover="listMouseOver(this)" onmouseout="listMouseOut(this)">
				<div class="data1div">						
					'.$details[0].
				'</div>
				<div class="data2div">
					'.$details[1].
				'</div>
			</div>
		</li>';
}

/**
 * Deletes the cookies saved in the local disk when the user loggs out of the system.
 */
function delete_cookies(){
	echo '<script type="text/javascript">
		createCookie(\'AssignmentCountState\',\'\',-1);
		createCookie(\'QuizCountState\',\'\',-1);
		createCookie(\'ResourceCountState\',\'\',-1);
		createCookie(\'UECountState\',\'\',-1);
		createCookie(\'ForumCountState\',\'\',-1);
		createCookie(\'OtherCountState\',\'\',-1);
		
		function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
		}
		</script>';
}

?>