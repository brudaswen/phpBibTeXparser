<?php
/** PHPUnit Framework */
require_once 'PHPUnit/Autoload.php';
require_once '../src/phpBibTeXparser.php';

class TokenTest extends PHPUnit_Framework_TestCase {
	public function testType() {
		$type = Token_pBTXp::AT;
		
		$t = new Token_pBTXp($type);
		$this->assertEquals($type, $t->getType());
	}
	
	public function testValue() {
		$type = Token_pBTXp::AT;
		$value = 13;
		
		$t = new Token_pBTXp($type, $value);
		$this->assertEquals($type, $t->getType());
	}
	
	public function testTypeAndValue() {
		$type = Token_pBTXp::NEWLINE;
		$value = "isbdfgzu4g380ßfbia8ßzvbg8ß9egb";
		
		$t = new Token_pBTXp($type, $value);
		$this->assertEquals($value, $t->getValue());
		$this->assertEquals($type, $t->getType());
	}
}
?>
