# Trusted CA certificates

Certificates placed here are copied into the container trust store
(`/usr/local/share/ca-certificates/`) and registered via `update-ca-certificates`
in the production image.

## Why this exists

Some geolocation APIs called by the app are hosted behind servers that serve an
incomplete TLS chain: they send only their leaf certificate and omit the
intermediate CA. curl and the Symfony HTTP client do not perform AIA chasing, so
they cannot rebuild the chain and the request fails with
`unable to get local issuer certificate`.

Trusting the missing intermediate here lets the container validate those chains.
The proper long-term fix is server-side (serve the full chain), but that is out
of our control.

## GandiCert.crt

Intermediate CA `GandiCert` (O = Gandi SAS), issued by `DigiCert Global Root G2`,
a publicly trusted root already present in the base image. Needed for the Jeedom
endpoint `jeedom.enghien95.fr`, whose server sends only its leaf certificate.

Source: http://cacerts.digicert.com/GandiCert.crt (converted from DER to PEM).
