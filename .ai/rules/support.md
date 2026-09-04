# Support helpers

Place stateless boundary helpers in `app/Support/`, not in services or controllers.

`Money` converts decimal strings to integer cents (`fromDecimal`) and back (`toDecimal`) for API input/output. Assume two decimal places (EUR/MKD style).

Do not embed validation in support helpers; Form Requests validate shape, services enforce account rules.
