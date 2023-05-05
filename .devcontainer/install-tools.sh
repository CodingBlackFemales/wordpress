#!/bin/bash

# Install mkcert
if ! command -v mkcert &> /dev/null; then
    brew install mkcert
	brew install nss
fi

# Create and install local CA
mkcert -install

CERT_DIR='./nginx/ssl'
# Check if the directory already exists
if [ ! -d $CERT_DIR ]
then
    # Create the directory if it doesn't exist
    mkdir $CERT_DIR
    echo "$CERT_DIR created successfully!"
else
    echo "$CERT_DIR already exists."
fi


# Generate certificate and key files
mkcert -key-file "$CERT_DIR/ssl.key" -cert-file "$CERT_DIR/ssl.crt" codingblackfemales.local "*.codingblackfemales.local"
