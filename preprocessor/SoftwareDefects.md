#### On Software Defects

Many people think that *the number of bugs in a section of code is proportional to the skill of the person who coded it*.
Most of them are developers *too proud and over-confident* to their own coding skills. 

Here we can include also that many believe that a piece a code can have more bugs because it is using web technologies and the same piece of code 
will have fewer bugs if used in an embedded environment. The reality is that if you are using the same C library in your both 
Apache webserver module and your automotive device firmware, you will inherit, of course, the same bugs.

Others think that code quality *can be achieved by enforcing an enteprise process through the means of bureaucracy*
(including code reviews, product quality certification and intensive testing). Typically they belong to classical management school of thought.


As you can easily see, a big problem is that all these are **reactionary counter-measures**. **They are not proactive** (as they 
don't attempt to remove the cause of bugs, to ask the objective question: why was possible for that bug to appear?).

Now, from a compiler and language design standpoint, all the approaches above are **skewed away from the statistical
reality**. 

That is because none of the following: high programmer skill and experience, tight development process, review procedures, intensive testing, running coverage tools, static code checking and other 
tools over the code already written, etc. **are not statistically proven to limit the number of possible bugs produced by the written code** (at the moment you are writing it).

The density of bugs in a section of code:
* **is proportional with the number of lexical atoms used to write that code**
* doesn't depends on what the code does (the application domain)
* doesn't depends on who wrote that code (the experience and skill)
* doesn't depends on the used platform/language (as long as the degree of abstraction is the same)

So we can conclude that bugs density is a function of the degree of abstraction.
The greater the abstraction is, the lower bug density. 

Given that reused code, abstract constructs provider, has no bugs. We discuss about the code written in a given section, not about libraries.
Of course, you can say, then it means that we moved the bugs from our section to the libraries. Well, not exactly, because 
the bugs in the library can be fixed, and we can reuse the library with the bug fixed, and if the library is used in many sections 
then we fixed all the occurrences of that bug, so we multiplied the bug density reduction.

Of course it is not only about libraries here. I am advertising **Language Oriented Programming** indeed.

And we now found that good developer skill means to know how to choose the right library, how to design your interfaces
and how to decouple concerns, etc. But all of these are already included in the law of abstraction. *"The greater the abstraction is, the lower bug density. "*

You don't have to trust me. Here we have an example from C++ Core Guidelines (by Bjarne Stroustrup and Herb Sutter).

Section P.1: Express ideas directly in code

Example of code not using enough abstraction:

```cpp
void f(vector<string>& v) {
    string val;
    cin >> val;
    // ...
    int index = -1;                    // bad
    for (int i = 0; i < v.size(); ++i) {
        if (v[i] == val) {
            index = i;
            break;
        }
    }
    // ...
}
```

Code using a minimum degree of abstraction:

```cpp
void f(vector<string>& v) {
    string val;
    cin >> val;
    // ...
    auto p = find(begin(v), end(v), val);  // better
    // ...
}
```

#### Inherently Unsafe Languages