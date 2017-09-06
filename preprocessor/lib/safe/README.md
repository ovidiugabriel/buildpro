### Problems solved

#### Indexitis

**Problem 1 - Infinite loops**

Since i (as 8-bits unsigned integer) takes values between 0 and 255, the condition is always true, the the loop will never break.

```cpp
uint8_t i = 0;
uint8_t bytes[256] = {0};

for (i = 0; i < sizeof(bytes); i++) {
    // This is an infinite loop
}
```

**Solutions**

* automatically infer counter type
* (avoid indexitis) use a foreach construct that automatically traverses the vector from the first element to the last one

