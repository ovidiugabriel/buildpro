
This **C preprocessor** replacement allows you to use flexible and more powerful macros without having to rely on **m4**.
Basicaly **PHP**+**Smarty** combination becomes your new preprocessor. You can use it for any language...

###### Replacements for standard accepted tokens

* `#define`
* `#if`
* `#elif`
* `#endif`
* `#else`
* `#ifdef`
* `#ifndef`
* `#include`

###### Extensions

* `@include_once`
* `#import` - ensures that a file is only ever included once so that you never have a problem with recursive includes. However, most decent header files protect themselves against this anyway, so it's not really that much of a benefit; For Microsoft C++ is used to incorporate information from a type library. The content of the type library is converted into C++ classes, mostly describing the COM interfaces.
* `#include_next`
* `@require_once`
* `@require`
* `@require_once`
* `config_load`

###### Not supported
 
**IMPORTANT:** `#undef` and `#pragma` are not supported

##### Other Pragmatic Features
 * add expression based syntax for C/C++
 * no side effect expressions
 * return keyword usage
 * break keyword usage
 * goto keyword usage

##### Links

* [GNU M4 Manual](https://www.gnu.org/software/m4/manual/m4.html)
* [Smarty 3 Manual](http://www.smarty.net/docs/en/)
* [Yay is a high level PHP preprocessor](https://github.com/marcioAlmada/yay)
* [SitePoint - PHP Macros for Fun and Profit!, March 18, 2016 - By Christopher Pitt](http://www.sitepoint.com/php-macros-for-fun-and-profit/)
* [re2c - a free and open-source lexer generator for C and C++](http://re2c.org/)
