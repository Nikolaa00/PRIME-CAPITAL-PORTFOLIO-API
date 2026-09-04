# Enums

Place domain enums in `app/Enums/` as backed string enums.

Use TitleCase for enum case names (for example `Deposit`, `Buy`).

Add small domain helpers on the enum when they encode ledger semantics (for example `isTrade()`, `cashSign()`, `quantitySign()`).
