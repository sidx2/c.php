<?php

$iota = 0;
define("TOKEN_NAME", $iota++);
define("TOKEN_OPAREN", $iota++);
define("TOKEN_CPAREN", $iota++);
define("TOKEN_OCURLY", $iota++);
define("TOKEN_CCURLY", $iota++);
define("TOKEN_SEMICOLON", $iota++);
define("TOKEN_NUMBER", $iota++);
define("TOKEN_STRING", $iota++);
define("TOKEN_RETURN", $iota++);

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

$file_path = "hello.c";
$source = file_get_contents($file_path);

$lexer = new Lexer($file_path, $source);

$token = $lexer -> next_token();
while ($token) {
    echo sprintf("%s: %s\n", $token -> loc -> display(), $token -> text);
    $token = $lexer -> next_token();
}
?>
