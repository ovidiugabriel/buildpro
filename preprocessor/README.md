
This **C preprocessor** replacement allows you to use flexible and more powerful macros without having to rely on **m4**.
Basicaly **PHP**+**Smarty** combination becomes your new preprocessor. You can use it for any language...

Accepted tokens:

* `#define`
* `#if`
* `#elif`
* `#endif`
* `#else`
* `#ifdef`
* `#ifndef`
* `#include` - the same as `@require`
* `#import` - the same as `@require_once`
* `@require` 
* `@require_once`
* `@using`

**IMPORTANT:** `#undef` and `#pragma` are not supported

* `{{expression}}` - expands a Smarty expression
