
@lang["default"]

@import["core.stdc.stdio"]
@import["test_debug_print_backtrace"]

#ifdef TEST
#error "Already defined"
#endif

#define TEST 1

int main(int argc, char const *argv[])
{
    printf("%s, TEST=%d\n", "Great TEST", TEST);
    return 0;
}
