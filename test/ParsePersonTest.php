<?php
/**
 * phpBibTeXparser.php
 *
 * This library contains a BibTeX parser that is able to read all the entries 
 * from a given BibTeX file or string. The output is a simple array with all 
 * the relevant information. String/Macro entries are automatically replaced 
 * and are not part of the output. Comments and preambles are currently also 
 * ignored.
 * The parser is based on the grammar description of btparse 
 * (http://search.cpan.org/~gward/btparse-0.34/doc/bt_language.pod) and 
 * consists of a lexer, a parser and some helper classes.
 * 
 * For more information have a look at the README file.
 * 
 * @see http://dev.brudaswen.de/projects/phpbibtexparser
 * @see https://github.com/brudaswen/phpBibTeXparser
 * @see http://search.cpan.org/~gward/btparse-0.34/doc/bt_language.pod
 * 
 * @author Sven Obser <dev@brudaswen.de>
 * @copyright Sven Obser <dev@brudaswen.de>
 * @package phpBibTeXparser
 */

/** PHPUnit Framework */
require_once 'PHPUnit/Autoload.php';
require_once '../src/phpBibTeXparser.php';

/**
 * 
 * 
 * @author Sven Obser <dev@brudaswen.de>
 * @package phpBibTeXparser
 * @version 1.0
 */
class ParsePersonTest extends PHPUnit_Framework_TestCase {
	
	public function testSimple() {
		$result = BibtexParser::parsePerson(' AA  BB ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('', $result['von']);
		$this->assertEquals('BB', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSimpleLastOnly() {
		$result = BibtexParser::parsePerson(' AA ');
		$this->assertEquals('', $result['forename']);
		$this->assertEquals('', $result['von']);
		$this->assertEquals('AA', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSimpleLastOnlyLower() {
		$result = BibtexParser::parsePerson(' aa ');
		$this->assertEquals('', $result['forename']);
		$this->assertEquals('', $result['von']);
		$this->assertEquals('aa', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSimpleLastLower() {
		$result = BibtexParser::parsePerson(' AA  bb ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('', $result['von']);
		$this->assertEquals('bb', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSimpleVon() {
		$result = BibtexParser::parsePerson(' AA  bb  CC ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('bb', $result['von']);
		$this->assertEquals('CC', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSimpleVonInnerUpper() {
		$result = BibtexParser::parsePerson(' AA  bb  CC  dd  EE ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('bb CC dd', $result['von']);
		$this->assertEquals('EE', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSimpleVonInnerUpperNoForename() {
		$result = BibtexParser::parsePerson(' aa  bb  CC  dd  EE ');
		$this->assertEquals('', $result['forename']);
		$this->assertEquals('aa bb CC dd', $result['von']);
		$this->assertEquals('EE', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSeparatedSimple() {
		$result = BibtexParser::parsePerson(' bb  CC ,  AA  ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('bb', $result['von']);
		$this->assertEquals('CC', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSeparatedSimpleLowerForename() {
		$result = BibtexParser::parsePerson(' bb  CC ,  aa ');
		$this->assertEquals('aa', $result['forename']);
		$this->assertEquals('bb', $result['von']);
		$this->assertEquals('CC', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSeparatedVonInnerUpper() {
		$result = BibtexParser::parsePerson('  bb  CC  dd  EE ,  AA ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('bb CC dd', $result['von']);
		$this->assertEquals('EE', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSeparatedLastNotEmpty() {
		$result = BibtexParser::parsePerson(' bb  ,  AA  ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('', $result['von']);
		$this->assertEquals('bb', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSeparatedFirstEmpty() {
		$result = BibtexParser::parsePerson('  BB ,  ');
		$this->assertEquals('', $result['forename']);
		$this->assertEquals('', $result['von']);
		$this->assertEquals('BB', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
	
	public function testSeparatedSuffix() {
		$result = BibtexParser::parsePerson('  bb   CC  ,  XX  ,  AA ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('bb', $result['von']);
		$this->assertEquals('CC', $result['surname']);
		$this->assertEquals('XX', $result['suffix']);
	}
	
	public function testSeparatedSuffixLower() {
		$result = BibtexParser::parsePerson('  bb  CC , xx ,  AA  ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('bb', $result['von']);
		$this->assertEquals('CC', $result['surname']);
		$this->assertEquals('xx', $result['suffix']);
	}
	
	public function testSeparatedSuffixEmpty() {
		$result = BibtexParser::parsePerson('  bb   CC  ,  ,   AA  ');
		$this->assertEquals('AA', $result['forename']);
		$this->assertEquals('bb', $result['von']);
		$this->assertEquals('CC', $result['surname']);
		$this->assertEquals('', $result['suffix']);
	}
}
?>