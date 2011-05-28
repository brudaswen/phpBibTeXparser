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
 * @package phpBibTeXparser
 * 
 * @author Sven Obser <dev@brudaswen.de>
 * @copyright 2011 Sven Obser <dev@brudaswen.de>
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * 
 * @link http://dev.brudaswen.de/projects/phpbibtexparser
 * @link https://github.com/brudaswen/phpBibTeXparser
 * @see http://search.cpan.org/~gward/btparse-0.34/doc/bt_language.pod
 */

/**
 * The BibtexParser parses a BibTeX string or file into an array representation
 * 
 * This is the only file that has to be used when interacting with the 
 * framework. Just create a fresh object and call one of the appropriate parse
 * methods. A detailed example is given in the README file.
 * 
 * @package phpBibTeXparser
 * @author Sven Obser <dev@brudaswen.de>
 * @version 1.0
 */
class BibtexParser {
	
	/**
	 * Extracts to forename part from an array of name pieces - which means all
	 * parts starting with an upper-case letter.
	 * 
	 * NOTE: All read forename parts get REMOVED from the given array reference.
	 * 
	 * @param array $pieces The array from which to read the forename from. 
	 * 		NOTE: All read forename parts get REMOVED from the given array 
	 * 		reference.
	 * @return string The forename that was read from the given pieces.
	 */
	private static function extractForename(array &$pieces) {
		// Read all upper-case words into forenames array
		$forenames = array();
		while(!empty($pieces)) {
			$piece = trim(array_shift($pieces));
			if(empty($piece)) {
				// Ignore this one
			} elseif(preg_match('!^[A-Z]!', $piece)) {
				$forenames[] = $piece;
			} else {
				array_unshift($pieces, $piece);
				break;
			}
		}
		
		// If we read all, the last one is a surname
		if(empty($pieces)) {
			$last = array_pop($forenames);
			$pieces[] = $last;
		}
		
		return implode(' ', $forenames);
	}

	/**
	 * Extracts to von-part and the surname from an array of name pieces - 
	 * the von-part is the longest sequence of word, which first and last word 
	 * starts with an lower-case letter.
	 * 
	 * @param array $pieces The array from which to read the names from. 
	 * @return array An array with the von part (index 0) and the surname 
	 * 		(index 1)read from the given pieces.
	 */
	private static function extractVonAndSurname(array $pieces) {
		// Read all lower-case words into forenames array
		$vons = array();
		$surnames = array();
		while(!empty($pieces)) {
			$piece = trim(array_shift($pieces));
			if(empty($piece)) {
				// Ignore this one
			} elseif(preg_match('!^[a-z]!', $piece)) {
				$vons = array_merge($vons, $surnames);
				$vons[] = $piece;
				$surnames = array();
			} else {
				$surnames[] = $piece;
			}
		}
		
		// If we read all into vons, last has to be surname
		if(empty($surnames)) {
			$last = array_pop($vons);
			$surnames[] = $last;
		}
		
		return array(implode(' ', $vons), implode(' ', $surnames));
	}
	
	/**
	 * Parse a given persons string into its separate parts (forename, von, 
	 * surname and suffix).
	 * 
	 * The format if the string can be one of the following:
	 * <ul>
	 * <li>First von Last</li>
	 * <li>von Last, First</li>
	 * <li>von Last, Jr., First</li>
	 * </ul>
	 * 
	 * @param string $person The person string representation.
	 * @return array An array containing the persons name with indices 
	 * 		'forename', 'von', 'surname' and 'suffix'.
	 */
	public static function parsePerson($person) {
		$pieces = explode(',', $person, 3);
			
		if(count($pieces) == 1) {
			// "Forename von Surname"
			$pieces = explode(' ', $pieces[0]);
			
			$forename = self::extractForename($pieces);
			list($von, $surname) = self::extractVonAndSurname($pieces); 
			$suffix = '';
		} else if(count($pieces) == 2) {
			// "von Surname, Forename"
			$forename = trim($pieces[1]);
			list($von, $surname) = self::extractVonAndSurname(explode(' ', $pieces[0])); 
			$suffix = '';
		} else if(count($pieces) >= 3) {
			// "von Surname, Suffix, Forename"
			$forename = trim($pieces[2]);
			list($von, $surname) = self::extractVonAndSurname(explode(' ', $pieces[0])); 
			$suffix = trim($pieces[1]);
		}
		
		return array('forename' => $forename, 'von' => $von, 'surname' => $surname, 'suffix' => $suffix);
	}
	
	/**
	 * Parse a string into several persons.
	 * 
	 * @link http://artis.imag.fr/~Xavier.Decoret/resources/xdkbibtex/bibtex_summary.html#names
	 * 
	 * @param string $person A list of persons separated by "and"
	 * 
	 * @return array An arry with all person arrays.
	 */
	public static function parsePersons($person) {
		if(trim($person) == '') {
			$persons = array();
		} else {
			$persons = explode(' and ', $person);
		}
		
		$callback = function(&$value, $key) {
			$value = self::parsePerson($value);
		};
		
		array_walk($persons, $callback);
		
		return $persons;
	}
	
	/** An array with all user-defined and read @string macros. */
	private $macros;
	/** The iterator that iterates Token by Token over the BibTeX content. */
	private $iter;
	
	/**
	 * Create a new BibTeX parser object with predefined @string macros (optional).
	 * 
	 * @param array $userMacros (optional) A list of predefined @string macros.
	 */
	public function __construct($userMacros=array()) {
		$this->macros = $userMacros;
	}
	
	/**
	 * Parse the given BibTeX string into an array representation.
	 * 
	 * @param string $bibtex The BibTeX string.
	 * @return array An array with all entries - each entry as an array of 
	 * 		key=>value pairs.
	 */
	public function parseString($bibtex) {
		return $this->parse(new TextIterator_pBTXp($bibtex));
	}
	
	/**
	 * Parse the given BibTeX file into an array representation.
	 * 
	 * @param string $file The path to the BibTeX file.
	 * @return array An array with all entries - each entry as an array of 
	 * 		key=>value pairs.
	 */
	public function parseFile($file) {
		return $this->parse(new FileIterator_pBTXp($file));
	}
	
	/**
	 * Start parsing the BibTeX content.
	 * 
	 * The returned array contains for each entry its 'type', its 'key' and its 
	 * 'fields' of the entry.
	 * 
	 * @param Iterator $iter The iterator that iterates Token by Token over 
	 * 		the BibTeX content.
	 * @return array An array with all entries - each entry as an array of 
	 * 		key=>value pairs.
	 */
	private function parse(Iterator $iter) {
		$lexer = new BibtexLexer_pBTXp();
		$tokens = $lexer->tokenize($iter);
		$this->iter = new TokenIterator_pBTXp($tokens);
		
		$entries = array();
		while($this->iter->valid()) {
			$entry = $this->parseEntry();
			if($entry != null) {
				$entries[] = $entry;
			}
		}
		$this->iter = null;
		
		return $entries;
	}
	
	/**
	 * Parse a whole entry (e.g. '@article{...}') from the iterator and return 
	 * its array representation.
	 * 
	 * The returned array contains the 'type', the 'key' and the 'fields' of the
	 * entry.
	 * 
	 * @return array An array for the current entry.
	 */
	private function parseEntry() {
		$this->readToken(Token_pBTXp::AT);
		$t = $this->readToken(Token_pBTXp::NAME);
		$type = $t->getValue();
		$this->readToken(Token_pBTXp::ENTRY_OPEN);
		
		$entry = null;
		if(strtolower($type) == 'string') {
			list($key, $value) = $this->parseField();
			$this->macros[$key] = $value;
		} elseif(strtolower($type) == 'preamble') {
			$this->readToken(Token_pBTXp::STRING, true);
		} elseif(strtolower($type) == 'comment') {
			$this->readToken(Token_pBTXp::STRING, true);
		} else {
			$t = $this->readToken(Token_pBTXp::NAME);
			$key = $t->getValue();
			
			$result = $this->readToken(Token_pBTXp::COMMA, true);
			if($result != false) {
				$fields = $this->parseFields();
			} else {
				$fields = array();
			}
			
			$entry = array('type'=>$type, 'key'=>$key, 'fields'=>$fields);
		}
		
		$this->readToken(Token_pBTXp::ENTRY_CLOSE);
		
		return $entry;
	}
	
	/**
	 * Parse all fields inside an entry (e.g. 'author={Foo}, title=bar') into 
	 * an array.
	 * 
	 * @return array All fields as key=>value pairs.
	 */
	private function parseFields() {
		$fields = array();
		
		while($this->currentTokenIs(Token_pBTXp::NAME) != false) {
			$result = $this->parseField();
			$fields[$result[0]] = $result[1];
			
			if(false == $this->readToken(Token_pBTXp::COMMA, true)) {
				break;
			}
		}
		
		return $fields;
	}
	
	/**
	 * Parse one field (e.g. 'author={Foo}') and return its name and value as 
	 * an array.
	 * 
	 * @return array An array with the name (index 0) and the value (index 1) 
	 * 		of the field.
	 */
	private function parseField() {
		$t = $this->readToken(Token_pBTXp::NAME);
		$key = $t->getValue();
		
		$this->readToken(Token_pBTXp::EQUALS);
		
		$value = $this->parseValue();
		
		return array(strtolower($key), $value);
	}
	
	/**
	 * Parse a value (e.g. '"Foo" # macro # "42"') from the iterator; macros 
	 * are automatically replaced by their value.
	 * 
	 * @return string The read value.
	 */
	private function parseValue() {
		$value = '';
		do {
			$value .= $this->parseSimpleValue();
			
			$t = $this->readToken(Token_pBTXp::HASH, true);
			$proceed = ($t != false);
		} while($proceed);
		
		return $value;
	}
	
	/**
	 * Parse a simple value, which is either a string (e.g. '"Foo"' or '{Bar}'),
	 * a number (e.g. '42') or a macro (e.g. 'ieee').
	 * 
	 * @throws ParseException_pBTXp If to current Token is not a type string, number or macro.
	 * @return string The read value.
	 */
	private function parseSimpleValue() {
		$t = $this->readToken();
		
		if($t->getType() == Token_pBTXp::STRING) {
			return $t->getValue();
		} elseif($t->getType() == Token_pBTXp::NUMBER) {
			return $t->getValue();
		} elseif($t->getType() == Token_pBTXp::NAME) {
			return $this->getMacroValue($t->getValue());
		} else {
			throw new ParseException_pBTXp("Expected a value, but got '".Token_pBTXp::typeToString($t->getType())."' on line {$this->iter->getLine()}.");
		}
	}
	
	/**
	 * Read one token from the iterator
	 * 
	 * @param int $type The type the current token must have or null if we don't care.
	 * @param boolean $optional If set to true we don't throw an exception if types don't 
	 * 		match or iterator reached end, otherwise we do.
	 * @throws ParseException_pBTXp If token is not optional and types don't match or iterator reached end.
	 * @return Token The current token of the given type or false if types don't match.
	 */
	private function readToken($type=null, $optional=false) {
		$t = false;
		if($this->iter->valid()) {
			// Get current token
			$t = $this->iter->current();
			
			if($type == null || $t->getType() == $type) {
				// Token type matches or is not restricted 
				// => everything is fine => iterate
				$this->iter->next();
			} elseif(!$optional) {
				// Type restricted but does not match and token required
				// => throw exception
				throw new ParseException_pBTXp("Expected a token of type '".Token_pBTXp::typeToString($type)."', but got '".Token_pBTXp::typeToString($t->getType())."' on line {$this->iter->getLine()}.");
			} else {
				// Type restricted and does not match, but token is optional 
				// => just return false
				$t = false;
			}
		} elseif(!$optional) {
			// If iterator reach end and token is not optional => throw exception
			$errMsg = ($type != null) ? ", but expected '".Token_pBTXp::typeToString($type)."'" : '';
			throw new ParseException_pBTXp("Reached end of file $errMsg on line {$this->iter->getLine()}.");
		}
		
		return $t;
	}
	
	/**
	 * Check if the current token matches the given type.
	 * 
	 * @param int $type The type the token should have.
	 * @return boolean True if the current token has the given type, false otherwise.
	 */
	private function currentTokenIs($type) {
		if($this->iter->valid()) {
			$t = $this->iter->current();
			if($t->getType() == $type) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Look up a @string macros value.
	 * 
	 * @param string $key The name if the macro.
	 * @throws ParseException_pBTXp If the macro is not defined.
	 * @return string The value of the macro.
	 */
	private function getMacroValue($key) {
		if(isset($this->macros[$key])) {
			return $this->macros[$key];
		} else {
			throw new ParseException_pBTXp("Unknown macro '$key' on line {$this->iter->getLine()}.");
		}
	}
}

/**
 * The lexer transform a sequence of characters into a token stream.
 * 
 * @package phpBibTeXparser
 * @author Sven Obser <dev@brudaswen.de>
 * @version 1.0
 */
class BibtexLexer_pBTXp {
	
	/** The iterator that iterates char by char over the BibTeX content. */
	private $iter;
	/** The list of currently read tokens. */
	private $tokens;
	
	/**
	 * Create a new BibTeX lexer object.
	 */
	public function __construct() {
	}
	
	/**
	 * Tokenize the given character stream into a token stream.
	 * 
	 * @param Iterator $iter The character stream.
	 * @return array The read tokens.
	 */
	public function tokenize(Iterator $iter) {
		$this->iter = $iter;
		$this->tokens = array();
		$this->tokenize_topLevel();
		
		return $this->tokens;
	}
	
	/**
	 * Tokenize top-level structure, which adds NEWLINE tokens and proceeds 
	 * downwards if an entry starts.
	 * 
	 * @throws ParseException_pBTXp
	 * @return unknown_type
	 */
	private function tokenize_topLevel() {
		while($this->iter->valid()) {
			$c = $this->iter->current();
			$this->iter->next();
			
			if($c == '@') {
				$this->addToken(Token_pBTXp::AT);
				$this->tokenize_inEntry();
			} elseif($c == "\n") {
				$this->addToken(Token_pBTXp::NEWLINE);
			} elseif($c == "%") {
				$this->readLineComment();
			} elseif($c == ' '|| $c == "\t" || $c == "\r") {
				// Ignore Whitespace
			} else {
				// Ignore Junk
			}
		}
	}
	
	/**
	 * Tokenize with respect to the in-entry rules; adds EQUALS, HASH, COMMA, 
	 * STRING, NAME, NUMBER and NEWLINE tokens until entry is closed.
	 */
	private function tokenize_inEntry() {
		$name = $this->readNameOrNumber();
		$opener = $this->readEntryOpener();
		$closer = ($opener == '(') ? ')' : '}';
		
		if(strtolower($name) == 'comment') {
			$this->tokenize_string($closer);
			$this->addToken(Token_pBTXp::ENTRY_CLOSE);
		} else {
			$name = '';
			while($this->iter->valid()) {
				$c = $this->iter->current();
				
				if($c == $closer) {
					$this->iter->next();
					$this->addToken(Token_pBTXp::ENTRY_CLOSE);
					break;
				} elseif($c == "\n") {
					$this->addToken(Token_pBTXp::NEWLINE);
					$this->iter->next();
				} elseif($c == '%') {
					$this->readLineComment();
				} elseif($c == ' ' || $c == "\t" || $c == "\r") {
					// Ignore Whitespace
					$this->iter->next();
				} elseif($c == '{' || $c == '"') {
					$this->iter->next();
					$this->tokenize_string($c);
				} elseif($c == '=') {
					$this->addToken(Token_pBTXp::EQUALS);
					$this->iter->next();
				} elseif($c == '#') {
					$this->addToken(Token_pBTXp::HASH);
					$this->iter->next();
				} elseif($c == ',') {
					$this->addToken(Token_pBTXp::COMMA);
					$this->iter->next();
				} else {
					$this->readNameOrNumber();
				}
			}
		}
	}
	
	/**
	 * Tokenize a string value enclosed in double quotes or curly braces; adds 
	 * a STRING token and maybe NEWLINE tokens to the stream.
	 * 
	 * @param string $delimiter The character opening the string (either '"' or '{').
	 */
	private function tokenize_string($delimiter) {
		$string = '';
		$newlines = 0;
		
		if($delimiter == '"') {
			// Read string enclosed in double quotes
			$escaped = false;
			while($this->iter->valid()) {
				$c = $this->iter->current();
				$this->iter->next();
				
				if($c == "\n") {
					++$newlines;
				}
				
				if(!$escaped && $c == '\\') {
					$escaped = true;
				} elseif(!$escaped && $c == '"') {
					break;
				} else {
					$string .= $c;
					$escaped = false;
				}
			}
		} else {
			// Read string enclosed in curly braces
			$opened = 1;
			while($this->iter->valid()) {
				$c = $this->iter->current();
				$this->iter->next();
				
				if($c == "\n") {
					++$newlines;
				}
				
				if($c == '{') {
					++$opened;
				} elseif($c == '}') {
					--$opened;
				}
				
				if($opened <= 0) {
					break;
				}

				$string .= $c;
			}
		}
		
		$this->addToken(Token_pBTXp::STRING, $string);
		
		for($i = 0; $i < $newlines; $i++) {
			$this->addToken(Token_pBTXp::NEWLINE);
		}
	}
	
	/**
	 * Add a new token to the token stream.
	 * 
	 * @param int $type The type of the token.
	 * @param string $value (optional) The value of the token.
	 */
	private function addToken($type, $value=null) {
		$token = new Token_pBTXp($type, $value);
		$this->tokens[] = $token;
	}
	
	/**
	 * Read until the end of the line (ignoring all chars) and add a NEWLINE 
	 * token.
	 */
	private function readLineComment() {
		// Read until end of line and return
		while($this->iter->valid()) {
			$c = $this->iter->current();
			$this->iter->next();
			
			if($c == "\n") {
				$this->addToken(Token_pBTXp::NEWLINE);
				break;
			}
		}
	}
	
	/**
	 * Read a NAME or a NUMBER token from the character stream and add it to 
	 * the token stream.
	 * 
	 * @return string The read value.
	 */
	private function readNameOrNumber() {
		$name = '';
		while($this->iter->valid()) {
			$c = $this->iter->current();
			
			if(0 < preg_match("=^[a-z0-9\!\$\&\*\+\-\.\/\:\;\<\>\?\[\]\^\_\`\|]$=i", $c)) {
				$this->iter->next();
				$name .= $c;
			} else {
				break;
			}
		}
		
		if(!empty($name)) {
			$type = is_numeric($name) ? Token_pBTXp::NUMBER : Token_pBTXp::NAME;
			$this->addToken($type, $name);
		}
		
		return $name;
	}
	
	/**
	 * Read either a '(' or a '{' from the character stream and add it to the 
	 * token stream (preceeding whitespace is ignored).
	 * 
	 * @return string|boolean The read opener symbol ('(' or '{') or false 
	 */
	private function readEntryOpener() {
		$opener = false;
		while($this->iter->valid()) {
			$c = $this->iter->current();
			$this->iter->next();
			
			if(0 < preg_match("/\s/", $c)) {
				// Ignore Whitespace
			} elseif($c == '(' || $c == '{') {
				$opener = $c;
				break;
			}
		}
		if($opener !== false) {
			$this->addToken(Token_pBTXp::ENTRY_OPEN);
		}
		return $opener;
	}
}

/**
 * A Token with its corresponding type and value.
 * 
 * @package phpBibTeXparser
 * @author Sven Obser <dev@brudaswen.de>
 * @version 1.0
 */
class Token_pBTXp {
	const NEWLINE = 0;
	const AT = 1;
	const ENTRY_OPEN = 2;
	const ENTRY_CLOSE = 3;
	const NAME = 4;
	const STRING = 5;
	const NUMBER = 6;
	const EQUALS = 7;
	const COMMA = 8;
	const HASH = 9;
	
	/**
	 * Transform class constant (type of token) into human readable 
	 * representation.
	 * 
	 * @param int $type The type of the token.
	 * @return string A human readable representation for the token type.
	 */
	public static function typeToString($type) {
		switch ($type) {
			case self::NEWLINE:
				return 'NEWLINE';
			case self::AT:
				return 'AT';
			case self::ENTRY_OPEN:
				return 'ENTRY_OPEN';
			case self::ENTRY_CLOSE:
				return 'ENTRY_CLOSE';
			case self::NAME:
				return 'NAME';
			case self::STRING:
				return 'STRING';
			case self::NUMBER:
				return 'NUMBER';
			case self::EQUALS:
				return 'EQUALS';
			case self::COMMA:
				return 'COMMA';
			case self::HASH:
				return 'HASH';
			default:
				return "?$type?";
		}
	}
	
	private $type;
	private $value;
	
	/**
	 * Create a new token with the given type and value (optional).
	 * 
	 * @param int $type The token's type (should be one of the class constants.
	 * @param string $value (optional) The value of the token.
	 */
	public function __construct($type, $value=null) {
		$this->type = $type;
		$this->value = $value;
	}
	
	/**
	 * Get the type of the token (one of the class constants).
	 * 
	 * @return int The token's type.
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * Get the value of the token (maybe null, if there is none).
	 * 
	 * @return string The token's value.
	 */
	public function getValue() {
		return $this->value;
	}

	public function __toString() {
		return self::typeToString($this->type)."({$this->value})";
	}
}

/**
 * Exception class to inform about parse errors.
 * 
 * @package phpBibTeXparser
 * @author Sven Obser <dev@brudaswen.de>
 * @version 1.0
 */
class ParseException_pBTXp extends Exception {
}

/**
 * Iterator to iterate over a given character string - char by char.
 * 
 * @package phpBibTeXparser
 * @author Sven Obser <dev@brudaswen.de>
 * @version 1.0
 */
class TextIterator_pBTXp implements Iterator {
	private $str;
	private $pos;
	private $len;
	private $line;
	
	/**
	 * Create a new text iterator for the given string.
	 * 
	 * @param string $str The string used for iteration.
	 */
	public function __construct($str) {
		$this->str = $str;
		$this->len = strlen($this->str);
		$this->rewind();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current() {
		return $this->str[$this->pos];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key() {
		return $this->pos;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::next()
	 */
	public function next() {
		if($this->current() == "\n") {
			++$this->line;
		}
		++$this->pos;
		return $this->valid();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		$this->pos = 0;
		$this->line = 1;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid() {
		return $this->pos < $this->len;
	}
	
	/**
	 * Get the current line in the stream.
	 * 
	 * @return int The current line number.
	 */
	public function getLine() {
		return $this->line;
	}
}

/**
 * Iterator to iterate over a given file - char by char.
 * 
 * @package phpBibTeXparser
 * @author Sven Obser <dev@brudaswen.de>
 * @version 1.0
 */
class FileIterator_pBTXp implements Iterator {
	private $file;
	private $current;
	private $line;
	
	/**
	 * Create a new file iterator for the given file.
	 * 
	 * @param string $file The path to the file.
	 */
	public function __construct($file) {
		$this->file = fopen($file, 'r');
		$this->rewind();
	}
	
	/**
	 * Close the file handle.
	 */
	public function __destruct() {
		fclose($this->file);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current() {
		return $this->current;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key() {
		return ftell($this->file);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::next()
	 */
	public function next() {
		if($this->current() == "\n") {
			++$this->line;
		}
		$this->current = fgetc($this->file);
		return $this->current !== false;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		rewind($this->file);
		$this->current = '';
		$this->line = 1;
		$this->next();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid() {
		return !feof($this->file);
	}
	
	/**
	 * Get the current line in the stream.
	 * 
	 * @return int The current line number.
	 */
	public function getLine() {
		return $this->line;
	}
}

/**
 * Iterator to iterate over a given list of tokens.
 * 
 * @package phpBibTeXparser
 * @author Sven Obser <dev@brudaswen.de>
 * @version 1.0
 */
class TokenIterator_pBTXp implements Iterator {
	private $tokens;
	private $pos;
	private $len;
	private $line;
	
	/**
	 * Create a new token iterator.
	 * 
	 * @param array $tokens The tokens.
	 */
	public function __construct(array $tokens) {
		$this->tokens = $tokens;
		$this->len = count($this->tokens);
		$this->rewind();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current() {
		return $this->tokens[$this->pos];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key() {
		return $this->pos;
	}
	
	/**
	 * Get the current token of the token stream - NEWLINEs get ignored and are 
	 * only used to count line number.
	 * 
	 * @see Iterator::next()
	 * 
	 * @return Token_pBTXp The current token.
	 */
	public function next() {
		do {
			++$this->pos;
			$newline = false;
			if($this->valid() && $this->current()->getType() == Token_pBTXp::NEWLINE) {
				++$this->line;
				$newline = true;
			}
		} while($newline);
		
		return $this->valid();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		$this->pos = 0;
		$this->line = 1;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid() {
		return $this->pos < $this->len;
	}
	
	/**
	 * Get the current line in the token stream.
	 * 
	 * @return int The current line number.
	 */
	public function getLine() {
		return $this->line;
	}
}
?>