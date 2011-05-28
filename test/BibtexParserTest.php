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
class BibtexParserTest extends PHPUnit_Framework_TestCase {
	public function test() {
	}
}
?>