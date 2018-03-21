
https://etherscan.io/solcbuginfo

# Bugs by Serverity

## high
#### HighOrderByteCleanStorage

For short types, the high order bytes were not cleaned properly and could overwrite existing data.

Types shorter than 32 bytes are packed together into the same 32 byte storage slot, but storage writes always write 32 bytes. For some types, the higher order bytes were not cleaned properly, which made it sometimes possible to overwrite a variable in storage when writing to another one.

https://blog.ethereum.org/2016/11/01/security-alert-solidity-variables-can-overwritten-storage/

- First Introduced: 0.1.6
- Fixed in version: 0.4.4
- Severity: high

#### AncientCompiler

## medium/high
#### CleanBytesHigherOrderBits
#### ArrayAccessCleanHigherOrderBits

## medium
#### ECRecoverMalformedInput
#### OptimizerStateKnowledgeNotResetForJumpdest
#### OptimizerStaleKnowledgeAboutSHA3

## low
#### DelegateCallReturnValue
#### SkipEmptyStringLiteral
#### ConstantOptimizerSubtraction
#### IdentityPrecompileReturnIgnored
#### LibrariesNotCallableFromPayableFunctions
#### SendFailsForZeroEther
#### DynamicAllocationInfiniteLoop
#### OptimizerClearStateOnCodePathJoin

## very low
#### ZeroFunctionSelector
