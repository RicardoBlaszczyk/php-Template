# php-ScanHUB
Interface zur Verarbeitung von Dokumentenstapeln aus Parashift

## JWT (RS256): Private/Public Key erzeugen

Die RSA-Schlüssel für RS256 werden im Verzeichnis `scanhub/sicher` erzeugt und dort als PEM-Dateien abgelegt.

### Schritte (OpenSSL)

1. In das Zielverzeichnis wechseln:

   ```bash
   cd scanhub/sicher
   ```

2. Private Key erzeugen (RSA 2048):

   ```bash
   openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out jwt_private.pem
   ```

3. Public Key aus dem Private Key ableiten:

   ```bash
   openssl pkey -in jwt_private.pem -pubout -out jwt_public.pem
   ```

### Ergebnis

Nach den Befehlen liegen folgende Dateien vor:

- `scanhub/sicher/jwt_private.pem` (Private Key – vertraulich behandeln, nicht committen)
- `scanhub/sicher/jwt_public.pem` (Public Key – kann zur Verifikation genutzt werden)