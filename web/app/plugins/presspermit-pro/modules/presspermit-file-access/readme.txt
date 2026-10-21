== PP File URL Filter for WordPress ==
	An extension to the PublishPress Permissions Pro plugin, an advanced content permissions engine and management interface. 
	
	This extension plugin blocks direct file url access to attachments whose parent post is unreadable to the logged user.
	
	Author: Kevin Behrens
	
	Copyright 2020 PublishPress

	See license.txt and copyright notices within the code for further details.

	To receive a copy of the current version, one-click updates and expert support, purchase a license key at https://publishpress.com/pricing/
	
== Change Log ==

	When an update is available, bug fixes and other changes made since your currently installed version can be retrieved by clicking the "View version details" link
	in your wp-admin Plugins listing.
	
== Nginx Integration ==

	To output Nginx rewrite rules, define the following constants in wp-config.php:
	
		define( 'PP_NGINX_CFG_PATH', '/path/to/your/supplemental/file.conf' );
		define( 'PP_FILE_ROOT', '/wp-content' );  // typical configuration (modify with actual path to folder your uploads folder is in, relative to http root) 

	NOTE that you will need to provide your own server scripts to trigger an Nginx reload upon config file update.
	
	On network installations, rules from all sites are inserted into the same file, specified by PP_NGINX_CFG_PATH.  Each site's rules are preceded by a distinguishing comment tag.
	
	To disable .htaccess output, define the following constant (in addition to PP_NGINX_CFG_PATH) :
	
		define( 'PP_NO_HTACCESS', true );
	
	You may manually force regeneration of Nginx or .htaccess rules by creating the file defined in this constant:
	
		define( 'PP_FILE_REGEN_TRIGGER', '/path/to/your/trigger/file' );