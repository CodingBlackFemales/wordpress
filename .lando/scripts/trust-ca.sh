#!/bin/bash

# Add the certificate to the macos trust store
security verify-cert -c ~/.lando/certs/lndo.site.pem > /dev/null 2>&1
if [ $? != 0 ]; then
    echo "Adding certificate to trust store ..."
    sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ~/.lando/certs/lndo.site.pem
fi
