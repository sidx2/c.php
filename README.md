# c.php

C Compiler in PHP 

## Compile C Source code to Python

This project supports following things as of now

- Functions with return type of 'int' only
- printf functions
- return statements are ignored
- preprocessor directives are ignored

## Example
example.c
```C
#include <stdio.h>

int hello() {
	printf("Hellooooo.....");
	return 0;
}

int foo() {
	printf("Foo, Bar");
	return 0;
}

int main() {
	printf("Hello, World");
}
```
output.py
```python

def hello():
	print("Hellooooo.....")

hello()

def foo():
	print("Foo, Bar")

foo()

def main():
	print("Hello, World")

main()

```
## Requirements

- PHP 7+
- Python 3

## Usage

### Don't Use Powershell. Use cmd instead

```console
php c.php example.c > output.py
```
```console
python output.py
```
