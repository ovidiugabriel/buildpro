
@lang["default"]

@import["core.stdc.stdio"]

#define TEST 1
@debug_print_backtrace

int main(int argc, char const *argv[])
{
    printf("%s, TEST=%d\n", "Great TEST", TEST);
    return 0;
}
