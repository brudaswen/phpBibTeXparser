<?php
/** PHPUnit Framework */
require_once 'PHPUnit/Autoload.php';
require_once '../src/phpBibTeXparser.php';

class BibtexLexerTest extends PHPUnit_Framework_TestCase {
	public function testString() {
		$iter = new TextIterator_pBTXp("@string(foo={bar})");
		$lexer = new BibtexLexer_pBTXp();
		$tokens = $lexer->tokenize($iter);
		
		$this->assertEquals(7, count($tokens));
		
		$t = $tokens[0];
		$this->assertEquals(Token_pBTXp::AT, $t->getType());
		
		$t = $tokens[1];
		$this->assertEquals(Token_pBTXp::NAME, $t->getType());
		$this->assertEquals("string", $t->getValue());
		
		$t = $tokens[2];
		$this->assertEquals(Token_pBTXp::ENTRY_OPEN, $t->getType());
		
		$t = $tokens[3];
		$this->assertEquals(Token_pBTXp::NAME, $t->getType());
		$this->assertEquals("foo", $t->getValue());
		
		$t = $tokens[4];
		$this->assertEquals(Token_pBTXp::EQUALS, $t->getType());
		
		$t = $tokens[5];
		$this->assertEquals(Token_pBTXp::STRING, $t->getType());
		$this->assertEquals("bar", $t->getValue());
		
		$t = $tokens[6];
		$this->assertEquals(Token_pBTXp::ENTRY_CLOSE, $t->getType());
	}
}
?>