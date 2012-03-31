<?php

/**
 *@author Amila Ariyarathna
 *
 * This class is a block that will work as an addon for Moodle.
 * The function of the class is to get course updates, forum posts,
 * and upcoming events of courses that a particular user is enrolled.
 */
	
	require_once($CFG->dirroot.'../config.php');
	require_once($CFG->dirroot.'/enrol/externallib.php');	
	require_once($CFG->dirroot.'/course/lib.php');
	
class course_updates{	
	
	public $activityset, $timestart;
	public $forum;

	
	function get_content() {	
		global $USER, $CFG, $activityset, $forum, $timestart;
				
		if(isloggedin()){
			$timestart = $this->fetch_updates();			//first fetch course updates
			$sortedactivity = $this->sort_activity();		//then sort them	
			$upcoming = $this->fetch_upcoming_eventslist(); //get a list of upcoming events
			$this->fetch_forum_updates();					//finally, fetch forum updates =)
			$data = array("updates"=>$sortedactivity, "upcoming"=>$upcoming, "forumposts"=>$forum);// return the sorted course updates, upcoming events, and forum posts
			return $data;
		}		
		else{
			return NULL;
		}
    }
	
	/**
 	* Fetches new events relating to all the course modlues the current user is enrolled to.
 	* Stores the fetched events in an array.
 	* 
 	* @global current_user $USER  
  	* @global array $activityset 
 	* @return $timestart The last time the user logged in to Moodle.
 	*/
	function fetch_updates(){
		global $USER, $activityset;
		
		$activityset = array('Added'=>array(), 'Updated'=>array());
		$timestart = round(time() - COURSE_MAX_RECENT_PERIOD, -2); // better db caching for guests - 100 seconds
		
		//determine the last time the user logged into Moodle.	
		if(!isguestuser()) {
			if( array_key_exists('lasttime',$_SESSION)){
               		$timestart = $_SESSION['lasttime'];            		
			}
			else{
				if ($USER->lastlogin) {
					$_SESSION['lasttime'] = $USER->lastlogin;
					$timestart = $_SESSION['lasttime'];
				}
			}
		}
			
		if ($mycourses = enrol_get_users_courses($USER->id, true)) {
			//for each course the user is enrolled to, get course updates
        	foreach ($mycourses as $mycourse) {
                if ($mycourse->category) {
					$this->get_course_activity($mycourse, $timestart);
				}
			}				
        }        
        return $timestart;
	}
	
	function fetch_forum_posts(){
		
	}
	
	
	/**
 	* Fetches new posts in all forums the current user is enrlled to. This is a very stupid way to 
 	* do this.
 	* 
 	* @global current_user $USER  
  	* @global array $forum
  	* @global int $timestart
  	* @global configuration $CFG
 	*/
	function fetch_forum_updates(){
		global $USER, $CFG, $forum, $timestart;
		$forum  = array();
		
		if ($mycourses = enrol_get_users_courses($USER->id, true)) {
			//for each course the user is enrolled to, get recent forum posts
        	foreach ($mycourses as $mycourse) {
                if ($mycourse->category) {
					$mycourse->modinfo = '';
					ob_start();
					forum_print_recent_activity($mycourse, true, $timestart);
					$posts = ob_get_contents();
					ob_end_clean();
					if($posts != ''){
						$posts = str_replace('</ul>', '', $posts);
						$list = explode('<li><div class="head"><div class="date">', $posts);
						array_shift($list);
						foreach($list as $post){							
							$post = '<a class="data_forum">'.$post;
							$post = str_replace('</div><div class="name">', ', ', $post);
							$post = str_replace('</div></div>', '</a><a class="data2" href=\"'.$CFG->wwwroot.'/course/view.php?id='.$mycourse->id.'\">'.$mycourse->fullname.'</a>#*#', $post);
							$post = str_replace('info bold','infobold',$post);
							$post = str_replace('</li>', '', $post);
							array_push($forum, $post);
						}						
					}
				}
			}				
        }
	}
	
	/**
 	* Sorts the course updates in $activityset array in to categories. These categories are
 	* Assignments, Resources, Quizzes, Forums, and Other Events.
 	*  
  	* @global array $activityset 
 	* @return array The sorted list of course updates.
 	*/
	function sort_activity(){
		global $activityset;
		
		/*This $modset array contains the list of categories and corresponding mods. You can add more
		 * Categories or their mods by addin them to this array.*/
		$modset = array("Resource"=>array("URL", "File", "Folder", "Page", "IMS content package"),
						"Assignment"=>array("Assignment"),
						"Quiz"=>array("Quiz"),
						"Other"=>array("Chat", "Forum", "Choice", "Database", "External Tool", "Lesson", "Glossary", "Wiki",
								 "Workshop", "Survey"));
		$sortedactivity =  array('Added'=>array(), 'Updated'=>array());
		
		/*for each added or updaed activity in $activityset:
		 * first identify the category of which the activity belongs
		 * then add it to the coresponding category of $sortedactivity 
		*/
		foreach($activityset as $AU=>$val){
			foreach($val as $modname=>$act){
				$set = 0;
				foreach($modset as $mod=>$sortedmodule){
					foreach($sortedmodule as $submod){
						if($submod == $modname){
							if(!array_key_exists ( $mod , $sortedactivity[$AU] )){
								$sortedactivity[$AU][$mod] = array();
							}
							foreach($act as $res){
								array_push($sortedactivity[$AU][$mod], $res);
							}
							$set = 1;
							continue;
						}
					}
				}
				if($set == 1){
					continue;
				}
				if(!array_key_exists ( $modname , $sortedactivity[$AU] )){
					$sortedactivity[$AU][$modname] = array();
				}
				$sortedactivity[$AU][$modname] = $act;
			}
		}				
		return $sortedactivity;
	}
	
	/**
 	* Fetches upcoming events relating to all the course modlues the current user is enrolled to.
 	* 
 	* @global current_user $USER  
 	* @return array A list of all upcoming events.
 	*/
	function fetch_upcoming_eventslist(){
		global $CFG, $USER;
		$upcoming = array();
		
		if ($mycourses = enrol_get_users_courses($USER->id, true)) {
        		foreach ($mycourses as $mycourse) {
                	if ($mycourse->category) {
                		$uptext = $this->get_upcoming_events($mycourse, 7);
						foreach($uptext as $event){
							$new = array("course"=>"<a class=\"data2\" href=\"$CFG->wwwroot/course/view.php?id={$mycourse->id}\">{$mycourse->fullname}</a>", 
                							"events"=>$event);
							array_push($upcoming, $new );
						}
					}
            	}			
        }        
        return $upcoming;		
	}
	
	/**
 	* Fetches upcoming events relating to the given course upto the no.of dates given in. Formats the events list into a printable text.
 	* 
 	* @param object $course The course object to fetch upcoming events from.
 	* @param int $lookahead Number of days the function should look into future for coming events.
 	* @return string Formatted string containing the upcoming event data.
 	*/
	function get_upcoming_events($course, $lookahead = NULL){
		
		$upcoming = '';
		
		$filtercourse = array(($course->id) => $course);
		list($courses, $group, $user) = calendar_set_filters($filtercourse);

        $defaultlookahead = CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD;
        if (isset($CFG->calendar_lookahead)) {
            $defaultlookahead = intval($CFG->calendar_lookahead);
        }
        //how many days to look ahead for upcoming events.
        if($lookahead == NULL){
        	$lookahead = get_user_preferences('calendar_lookahead', $defaultlookahead);
        }
        
        $defaultmaxevents = CALENDAR_DEFAULT_UPCOMING_MAXEVENTS;
        if (isset($CFG->calendar_maxevents)) {
            $defaultmaxevents = intval($CFG->calendar_maxevents);
        }
        $maxevents = get_user_preferences('calendar_maxevents', $defaultmaxevents);
        $events = calendar_get_upcoming($courses, $group, $user, $lookahead, $maxevents);
        
        $upcoming = $this->get_listed_upcoming_events($events, 'view.php?view=day&amp;course='.$course->id.'&amp;');

		return $upcoming;		
	}
	
	/**
 	* Lists the events provided into an array and formats the events in the list in a printable mannar.
 	* 
 	* @param array $events Array of upcoming events
 	* @param string $linkhref a link to the relevent events.
 	* @return array Formatted list of events.
 	*/
	function get_listed_upcoming_events($events, $linkhref = NULL) {
		$lines = count($events);
		$cnt = array();
		if (!$lines) {
			return $cnt;
		}

		for ($i = 0; $i < $lines; ++$i) {
			$temp = '';
			if (!isset($events[$i]->time)) {   // Just for robustness
				continue;
			}
			$events[$i] = calendar_add_event_metadata($events[$i]);
			$temp .= '<span class="icon c0">'.$events[$i]->icon.'</span> ';
			if (!empty($events[$i]->referer)) {
				// That's an activity event, so let's provide the hyperlink
				$temp .= $events[$i]->referer;
			} else {
				if(!empty($linkhref)) {
					$ed = usergetdate($events[$i]->timestart);
					$href = calendar_get_link_href(new moodle_url(CALENDAR_URL.$linkhref), $ed['mday'], $ed['mon'], $ed['year']);
					$href->set_anchor('event_'.$events[$i]->id);
					$temp .= html_writer::link($href, $events[$i]->name);
				}
				else {
					$temp .= $events[$i]->name;
				}
			}
			$events[$i]->time = str_replace('&raquo;', '<br />&raquo;', $events[$i]->time);
			$events[$i]->time = str_replace('<a','<a style="font-weight:normal; opacity:1;"', $events[$i]->time);
			$temp .= '<div class="date">'.$events[$i]->time.'</div>';
		
			array_push($cnt, $temp);
		}
		return $cnt;
	}
	
	/**
 	* Fetches new events relating to a particular course. All the events that were added or updated after the given time will be fetched and stored in $activityset array.
 	* 
 	* @param object $course The course that new events will be fetched from.
 	* @param int $timestart The time from when the events should be fetched.
 	*/
	function get_course_activity($course, $timestart) {
    	global $CFG, $USER, $SESSION, $DB, $OUTPUT, $activityset;
    	
/**
 * The follwing code is extracted from 'lib.php'.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core
 * @subpackage course
 */
/**********************************************************************************************************************************************************************************************************************/
    	$context = get_context_instance(CONTEXT_COURSE, $course->id);
    	$viewfullnames = has_capability('moodle/site:viewfullnames', $context);
    	$content = false;
		
		$course->modinfo = '';
    	$modinfo =& get_fast_modinfo($course);

    	$changelist = array();

    	$logs = $DB->get_records_select('log', "time > ? AND course = ? AND
                                            module = 'course' AND
                                            (action = 'add mod' OR action = 'update mod' OR action = 'delete mod')",
                                    array($timestart, $course->id), "id ASC");

    	if ($logs) {
        	$actions  = array('add mod', 'update mod', 'delete mod');
        	$newgones = array(); // added and later deleted items
        	foreach ($logs as $key => $log) {
            	if (!in_array($log->action, $actions)) {
                	continue;
            	}
            	$info = explode(' ', $log->info);

            	// note: in most cases I replaced hardcoding of label with use of
            	// $cm->has_view() but it was not possible to do this here because
            	// we don't necessarily have the $cm for it
            	if ($info[0] == 'label') {     // Labels are ignored in recent activity
             	   continue;
            	}

            	if (count($info) != 2) {
                	debugging("Incorrect log entry info: id = ".$log->id, DEBUG_DEVELOPER);
                	continue;
            	}

            	$modname    = $info[0];
            	$instanceid = $info[1];
				//echo" modname = {$modname}";
            	if ($log->action == 'delete mod') {
                	// unfortunately we do not know if the mod was visible
                	if (!array_key_exists($log->info, $newgones)) {
                   		$strdeleted = get_string('deletedactivity', 'moodle', get_string('modulename', $modname));
                    	$changelist[$log->info] = array ('operation' => 'delete', 'text' => $strdeleted);
                	}
            	} else {
                	if (!isset($modinfo->instances[$modname][$instanceid])) {
                    	if ($log->action == 'add mod') {
                        	// do not display added and later deleted activities
                        	$newgones[$log->info] = true;
                    	}
                    	continue;
                	}
                	$cm = $modinfo->instances[$modname][$instanceid];
                	if (!$cm->uservisible) {
                    	continue;
                	}
/**********************************************************************************************************************************************************************************************************************/
                	
                	if ($log->action == 'add mod') {
                    	$op = 'Added';
                    } else if ($log->action == 'update mod' and empty($changelist[$log->info])) {
                    	$op = 'Updated';
                    }
                    $coursename = $course->fullname;
                    $vari = get_string('modulename', $modname);
					if(!array_key_exists ( $vari , $activityset[$op] )){
						$activityset[$op][$vari] = array();
					}
					array_push($activityset[$op][$vari], "<a class=\"data1\" href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id={$cm->id}\">".format_string($cm->name, true)."</a>#*#<a class=\"data2\" href=\"$CFG->wwwroot/course/view.php?id={$course->id}\">$coursename</a>");
				}
        	}
    	}

	}
	
	function get_user_pic(){
		global $USER, $OUTPUT, $CFG;
		$right = '<div class="logout"><a href="'.$CFG->wwwroot.'/login/logout.php?sesskey='.$USER->sesskey.'"><span class="log">Logout</span></a></div>';
		$pic =  $OUTPUT->user_picture($USER);
		$pic = str_replace('</a>','<span class="username">'.$USER->firstname.'</span></a>', $pic);
		$pic = str_replace('class="userpicture" width="35" height="35"', '', $pic);
		$pic = str_replace('class="userpicture defaultuserpic" width="35" height="35"', '', $pic);			
		$right .= '<div class="userPic">'.$pic.'</div>';
		
		//$right .= '<a href="'.$CFG->wwwroot.'/login/logout.php?sesskey='.$USER->sesskey.'">Logout</a></div>';
		return $right;
	}
	
}