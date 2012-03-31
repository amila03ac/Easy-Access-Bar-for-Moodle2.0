<?php
require_once('moodle/simpletest/unit_tester.php');
require_once('moodle/simpletest/reporter.php');

class BlockTest extends UnitTestCase {
    
    function testGetContent() {
        @unlink('/temp/test.log');
        $log = new Log('/temp/test.log');
        $this->assertFalse(file_exists('/temp/test.log'));
        
    }
    
    function testSorter() {
        @unlink('/temp/test.log');
        $log = new Log('/temp/test.log');
        $this->assertFalse(file_exists('/temp/test.log'));
    }
    
    function testDataFetcher() {
        @unlink('/temp/test.log');
        $log = new Log('/temp/test.log');
        $this->assertFalse(file_exists('/temp/test.log'));
    }
    
}
$test = &new TestOfLogging();
$test->run(new HtmlReporter());
?>