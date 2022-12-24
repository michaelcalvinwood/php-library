<?php
function mcw_update_user_description_by_email($email, $description) {
    $user_object = get_user_by('email', $email);
    if ($user_object === false) {
        echo "email does not exist for $email<br>";
        return;
    } 
    $user_id = $user_object->ID;
    $result = wp_update_user( [ 
        'ID'       => $user_id, 
        'description' => $description
    ] );
    
    if ( is_wp_error( $result ) ) {
        echo "oops!<br>";
    } else {
        echo "all good! <br>";
    }
}
