
@lang "default"

@import std.stdio;

#define TEST 1

int main(int argc, char const *argv[])
{
    @helloworld;
    printf("%s, TEST=%d\n", "Great TEST", TEST);

    return 0;
}
