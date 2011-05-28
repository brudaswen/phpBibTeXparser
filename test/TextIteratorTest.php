<?php
/** PHPUnit Framework */
require_once 'PHPUnit/Autoload.php';
require_once '../src/phpBibTeXparser.php';

class TextItertorTest extends PHPUnit_Framework_TestCase {
	
	public function testLine() {
		$str = "A\n\nB\n";
		
		$iter = new TextIterator_pBTXp($str);
		$this->assertEquals(1, $iter->getLine());
		$iter->next();
		$this->assertEquals(1, $iter->getLine());
		$iter->next();
		$this->assertEquals(2, $iter->getLine());
		$iter->next();
		$this->assertEquals(3, $iter->getLine());
		$iter->next();
		$this->assertEquals(3, $iter->getLine());
	}
	
	public function testValid() {
		$str = "AB";
		
		$iter = new TextIterator_pBTXp($str);
		$this->assertEquals(true, $iter->valid());
		$iter->next();
		$this->assertEquals(true, $iter->valid());
		$iter->next();
		$this->assertEquals(false, $iter->valid());
	}
	
	public function testInForLoop() { 
		$str = "AB\n\n_ÖÄ***##!§$\r\t\nEND";
		
		$iter = new TextIterator_pBTXp($str);
		$pos = 0;
		$line = 1;
		foreach ($iter as $key => $value) {
			$this->assertEquals($pos, $key);
			$this->assertEquals($str[$pos], $value);
			$this->assertEquals($line, $iter->getLine());
			
			$pos++;
			if($value == "\n") {
				$line++;
			}
		}
	}
	
	public function testInWhileLoop() { 
		$str = "AB\n\n_ÖÄ***##!§$\r\t\nEND";
		
		$iter = new TextIterator_pBTXp($str);
		$pos = 0;
		$line = 1;
		while($iter->valid()) {
			$key = $iter->key();
			$value = $iter->current();
			$this->assertEquals($pos, $key);
			$this->assertEquals($str[$pos], $value);
			$this->assertEquals($line, $iter->getLine());
			
			$iter->next();
			$pos++;
			if($value == "\n") {
				$line++;
			}
		}
	}
	
	public function testRewindInWhile() { 
		$str = "AB\n\nCD\nD";
		
		$iter = new TextIterator_pBTXp($str);
		
		for ($i = 0; $i < 2; $i++) {
			$pos = 0;
			$line = 1;
			$iter->rewind();
			while($iter->valid()) {
				$key = $iter->key();
				$value = $iter->current();
				$this->assertEquals($pos, $key);
				$this->assertEquals($str[$pos], $value);
				$this->assertEquals($line, $iter->getLine());
				
				$iter->next();
				$pos++;
				if($value == "\n") {
					$line++;
				}
			}
		}
	}
	
	public function testRewindInFor() { 
		$str = "AB\n\nCD\nD";
		
		$iter = new TextIterator_pBTXp($str);
		
		for ($i = 0; $i < 2; $i++) {
			$pos = 0;
			$line = 1;
			foreach ($iter as $key => $value) {
				$this->assertEquals($pos, $key);
				$this->assertEquals($str[$pos], $value);
				$this->assertEquals($line, $iter->getLine());
				
				$pos++;
				if($value == "\n") {
					$line++;
				}
			}
		}
	}
}
?>
