<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_User_Field extends PMXI_Addon_Field {

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
	    // Attempt to get user by ID.
	    $user = get_user_by('id', $value);
	    if (!$user) {
		    // Attempt to get user by Login.
		    $user = get_user_by('login', $value);
		    if (!$user) {
			    // Attempt to get user by Email.
			    $user = get_user_by('email', $value);
		    }
	    }
	    // Return user id if found, otherwise return an empty string.
	    return $user ? $user->ID : '';
    }
}
