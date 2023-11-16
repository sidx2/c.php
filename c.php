<?php

define("TOKEN_NAME", "TOKEN_NAME");
define("TOKEN_OPAREN", "TOKEN_OPAREN");
define("TOKEN_CPAREN", "TOKEN_CPAREN");
define("TOKEN_OCURLY", "TOKEN_OCURLY");
define("TOKEN_CCURLY", "TOKEN_CCURLY");
define("TOKEN_SEMICOLON", "TOKEN_SEMICOLON");
define("TOKEN_NUMBER", "TOKEN_NUMBER");
define("TOKEN_STRING", "TOKEN_STRING");
define("TOKEN_RETURN", "TOKEN_RETURN");

class Loc {
    public $file_path;
    public $row;
    public $col;

    public function __construct($file_path, $row, $col) {
        $this -> file_path = $file_path;
        $this -> row = $row;
        $this -> col = $col;
    }

    public function display() {
        return sprintf("%s:%d:%d", $this -> file_path, $this -> row, $this -> col);
    }
}

class Token {
    public $type;
    public $text;
    public $loc;

    public function __construct($loc, $type, $text) {
        $this -> loc = $loc;
        $this -> type = $type;
        $this -> text = $text;
    }
} 
    
class Lexer {
	public $source;
    public $cur;
    public $bol;
    public $row;

	public function __construct($file_path, $source) {
        $this -> file_path = $file_path;
		$this -> source = $source;
        $this -> cur = 0;
        $this -> bol = 0;
        $this -> row = 0;

	}

    public function is_not_empty() {
        return $this -> cur < strlen($this -> source);
    }

    public function is_empty() {
        return !$this -> is_not_empty();
    }

    public function chop_char() {
        if ($this -> is_not_empty()) {
            $x = $this -> source[$this -> cur];
            $this -> cur++;
            if ($x === "\n") {
                $this -> bol = $this -> cur;
                $this -> row++;
            }
        }
    }
    function loc() {
        return new Loc($this->file_path, $this->row, $this->cur - $this->bol);
    }

    public function trim_left() {
        while($this -> is_not_empty() && ctype_space($this -> source[$this -> cur])) {
            $this -> chop_char();
        }
    }

    public function drop_line() {
        while($this -> is_not_empty() && $this -> source[$this -> cur] != "\n") {
            $this -> chop_char();
        }
        if ($this -> is_not_empty()) $this -> chop_char();
    }

    public function next_token() {
        $this -> trim_left();

        while ($this -> is_not_empty() && $this -> source[$this -> cur] == "#") {
            $this -> drop_line();
            $this -> trim_left();
        }

        $loc = $this -> loc();
        $first = $this -> source[$this -> cur];

        if ($this -> is_not_empty() && ctype_alpha($first)) {
            $index = $this -> cur;
            while ($this -> is_not_empty() && ctype_alnum($this -> source[$this -> cur])) {
                $this -> chop_char();
            }
            
            $text = substr($this -> source, $index, $this -> cur - $index);
            return new Token($this -> loc(), TOKEN_NAME, $text);
        }

        $literal_tokens = array(
            "(" => TOKEN_OPAREN,
            ")" => TOKEN_CPAREN,
            "{" => TOKEN_OCURLY,
            "}" => TOKEN_CCURLY,
            ";" => TOKEN_SEMICOLON
        );
                        
        if (isset($literal_tokens[$first])) {
            $this -> chop_char();
            return new Token($loc, $literal_tokens[$first], $first);
        }

        if ($first === '"') {
            $this -> chop_char();
            $start = $this -> cur;
            while($this -> is_not_empty() && $this -> source[$this -> cur] != '"') {
                $this -> chop_char();
            }

            if ($this -> is_not_empty()) {
                $text = substr($this -> source, $start, $this -> cur - $start);
                $this -> chop_char();
                return new Token($loc, TOKEN_STRING, $text);
            }

            echo sprintf("%s: Error: Unclosed string literal\n", $loc -> display());
            return false;
        }

        if (ctype_digit($first)) {
            $start = $this -> cur;
            while($this -> is_not_empty() && ctype_digit($this -> source[$this -> cur])) {
                $this -> chop_char();
            }

            $text = (int) substr($this -> source, $start, $this -> cur - $start);
            if ($this -> is_not_empty()) {
                return new Token($loc, TOKEN_NUMBER, $text);
            }

            echo sprintf("%s: Error: Expected Semicolon\n", $loc -> display());
        }
    }
}

$iota = 0;
define("TYPE_INT", "TYPE_INT");

class FuncallStmt {
    public $name;
    public $args;

    public function __construct($name, $args) {
        $this -> name = $name;
        $this -> args = $args;
    }
}

class RetStmt {
    public $expr;

    public function __construct($expr) {
        $this -> expr = $expr;
    }
}

class Func {
    public $name;
    public $body;

    public function __construct($name, $body) {
        $this -> name = $name;
        $this -> body = $body;
    }
}

function expect_token($lexer, ...$types) {
    $token = $lexer -> next_token();
    if (!$token) {
        // echo sprintf("%s: ERROR: Expected %s but got EOF\n", $lexer -> loc() -> display(), $type);
        return false;
    }

    foreach ($types as &$type) {
        if ($token -> type == $type) {
            return $token;
        }
        
    }
    if ($token -> type != $type) {
        echo sprintf("%s: ERROR: Expected %s but got %s\n", 
            $lexer -> loc() -> display(),
            join(" or ", $types),
            $token -> type
        );
    }
    return false;
}

function parse_type($lexer) {
    $return_type = expect_token($lexer, TOKEN_NAME);
    if ($return_type -> text !== "int") {
        /*
        echo sprintf("%s: ERROR: Unexpected type %s\n",
            $return_type -> loc -> display(),
            $return_type -> text
        );
         */
        return false;
    }
    return TYPE_INT;
}

function parse_arglist($lexer) {
    if (!expect_token($lexer, TOKEN_OPAREN)) return false;
    $arglist = array();
    while (true) {
        $expr = expect_token($lexer, TOKEN_STRING, TOKEN_NUMBER, TOKEN_CPAREN);
        if (!$expr) return false;
        if ($expr -> type == TOKEN_CPAREN) break;

        array_push($arglist, $expr -> text);
    }
    return $arglist;
}

function parse_block($lexer) {
    if (!expect_token($lexer, TOKEN_OCURLY)) return false;

    $block = array();

    while (true) {
        $name = expect_token($lexer, TOKEN_NAME, TOKEN_CCURLY);
        if (!$name) return false;
        if ($name -> type == TOKEN_CCURLY) break;
        if ($name -> text === "return") {
            // return
            $expr = expect_token($lexer, TOKEN_NUMBER);
            if (!$expr) return false;
            array_push($block, new RetStmt($expr -> text));

        } else {
            $arglist = parse_arglist($lexer);
            if (!$arglist) return false;
            array_push($block, new FuncallStmt($name, $arglist));
            // funcall
        }

        if(!expect_token($lexer, TOKEN_SEMICOLON)) return false;
    }

    return $block;
}
function parse_function($lexer) {
    $return_type = parse_type($lexer);
    if (!$return_type) return false;
    assert($return_type === TYPE_INT);

    $name = expect_token($lexer, TOKEN_NAME);
    if (!$name) return false;

    if (!expect_token($lexer, TOKEN_OPAREN)) return false;
    if (!expect_token($lexer, TOKEN_CPAREN)) return false;

    $body = parse_block($lexer);

    return new Func($name, $body);
}

if ($argc < 2) {
    echo "No C Source file provided\n";
    exit(1);
}

$file_path = $argv[1];
$source = file_get_contents($file_path);

$lexer = new Lexer($file_path, $source);

while (true) {
    $func = parse_function($lexer);
    if (!$func) break;
    echo sprintf("\ndef %s():\n", $func -> name -> text);
    foreach($func -> body as &$stmt) {
        if ($stmt instanceof FuncallStmt) {
            if ($stmt -> name -> text == "printf") {
                echo sprintf("\tprint(\"%s\")\n", join(", ", $stmt -> args));
            } else {
                echo sprintf("%s: Error: Unknown function '%s'\n",
                    $stmt -> name -> loc -> display(),
                    $stmt -> name -> text
                );
            }
        }
    }
    echo sprintf("\n%s()\n", $func -> name -> text);
}
?>
