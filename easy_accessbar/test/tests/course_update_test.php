<?php
require_once(dirname(__FILE__) . '/simpletest/autorun.php');
require_once(dirname(__FILE__) . '/../../course_updates.php');

class TestofDataFetching extends UnitTestCase {
	
	public $dataClass, $updateArray;
	
    function __construct() {
        parent::__construct('Log test');
    }

    function testUpdatesArray(){
    	global $dataClass,$updateArray;   
    	$dataClass = new course_updates;
    	$updateArray = $dataClass->get_content();
    	$this->assertNotNull($updateArray, 'updates array null');
    }
    
    function testUpdatesAct(){
    	global $updateArray;
    	$mods = array("Resource", "Assignment", "Quiz", "Other");
    	$count = 0;
    	$acts = $updateArray['updates']['Added'];
    	$this->assertTrue(count($acts)>0, 'No new added events');
    	foreach($acts as $modname=>$dat){
    		$this->assertTrue($modname==$mods[$count++]);
    	}
    }
    
    function testUpdateUE(){
    	global $updateArray;
    	$ue = $updateArray['upcoming'];
    	$this->assertTrue(count($ue)>0, 'No upcoming events');
    }
    
    function testUpdateForum(){
    	global $updateArray;
    	$forum = $updateArray['forumposts'];
    	$this->assertTrue(count($forum)>0, 'No forum posts');
    }
    
    function write($message) {
        $file = fopen(dirname(__FILE__) . '/../temp/test.log', 'a');
        fwrite($file, $message . "\n");
        fclose($file);
    }
}
?>