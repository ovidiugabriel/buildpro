
This **C preprocessor** replacement allows you to use flexible and more powerful macros without having to rely on **m4**.
Basicaly **PHP**+**Smarty** combination becomes your new preprocessor. You can use it for any language...

Standard accepted tokens:

* `#define`
* `#if`
* `#elif`
* `#endif`
* `#else`
* `#ifdef`
* `#ifndef`
* `#include` - the same as `@require`
* `#import` - the same as `@require_once`
 
**IMPORTANT:** `#undef` and `#pragma` are not supported

Other accepted tokens (provided by extension):

* `@require` 
* `@require_once`

Advanced:

* `@using`
* `{{expression}}` - expands to a php expression
