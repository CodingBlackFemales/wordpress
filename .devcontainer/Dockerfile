FROM mcr.microsoft.com/devcontainers/php:0-8.1
LABEL maintainer="Coding Black Females <tech@codingblackfemales.com>"

# Define nvm and Node versions
ENV NVM_VERSION 0.39.3
ENV NODE_VERSION 18.16.0

# Add Node and nvm to path so the commands are available
ENV NODE_PATH $NVM_DIR/v$NODE_VERSION/lib/node_modules
ENV PATH $NVM_DIR/versions/node/v$NODE_VERSION/bin:$PATH

# Replace sh with bash so we can source files
# RUN rm /bin/sh && ln -s /bin/bash /bin/sh
SHELL ["/bin/bash", "--login", "-c"]

# Override xdebug settings
RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "# xdebug.client_host=192.168.1.201" >> /usr/local/etc/php/conf.d/xdebug.ini

# update the repository sources list
# and install dependencies
RUN apt-get update \
    && apt-get -y autoclean

# Install Node
RUN source $NVM_DIR/nvm.sh \
	&& nvm install $NODE_VERSION

# confirm installation
RUN node -v && npm -v

# COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh
# RUN chmod +x /usr/local/bin/entrypoint.sh
# ENTRYPOINT [ "entrypoint.sh" ]
