<?php
/*
Template Name: Update User Info
*/
?>

<?php
if (!isset($_GET['key'])) exit ('missing key');
$key = $_GET['key'];

if (!isset($_GET['start'])) $start = false;
else $start = intval($_GET['start']);

if (!isset($_GET['end'])) $end = false;
else $end = intval($_GET['end']);

function cpi_pretty_debug($val, $label = '') {
    ?>
        <pre>
            <?php if ($label) echo $label . "<br>"; ?>
            <?php var_dump($val); ?>
        </pre>
    <?php
}

/*
 * IMPORTANT:
 * The exported list must be the one local to server. In other words, dev must use an exported list from dev; production must use an exported list from production
 */

require_once (get_template_directory() . '/includes/php-lib/binary-search.php');

function cpi_load_exported_user_list($local_export_name) {
    $file = fopen(get_template_directory() . "/data/$local_export_name", 'r');
    $export_list = [];
    while ( ($data = fgetcsv($file)) ) $export_list[] = $data;
    fclose($file);
    return $data;
}

function cpi_update_user_by_email($email, $description) {
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

function cpi_update_description_by_user_id($id, $description) {
    $result = wp_update_user( [ 
        'ID'       => $id, 
        'description' => $description
    ] );
    
    if ( is_wp_error( $result ) ) {
        echo "Could not update description for id: $id<br>";
        return;
    } 
}

function cpi_update_roles_by_user_id($id, $role) {
    if (empty($role)) return;

    $roles = explode(',', $role);
    $numRoles = count($roles);
   

    for ($i = 0; $i < $numRoles; ++$i) {
        $roles[$i] = trim($roles[$i]);   
    }
    
    $user_object = get_user_by('id', $id);

    if ( is_wp_error( $user_object ) ) {
        echo "Cannot find user by id: $id<br>";
        return;
    }

    $user_object->set_role($roles[0]);

    if ($numRoles > 1) {
        for ($i = 1; $i < $numRoles; ++$i) $user_object->add_role($roles[$i]);
    }
}


function cpi_get_user_by_first_name_last_name($first_name, $last_name) {
    $users_query = new WP_User_Query(
        array(
          'meta_query' => array(
          'relation' => 'AND',
            array(
              'key' => 'first_name',
              'value' => $first_name,
              'compare' => 'LIKE'
            ),
          array(
              'key' => 'last_name',
              'value' => $last_name,
              'compare' => 'LIKE'
            )
          )
        )
       );
      
      $results = $users_query->get_results();

      return $results;
}

function cpi_get_user_id_by_first_name_last_name($first_name, $last_name, $nickname, $debug = false) {
    $user_info = cpi_get_user_by_first_name_last_name($first_name, $last_name);

    $num_users = count($user_info);

    if ($num_users === 1) return $user_info[0]->ID;
    if ($num_users === 0) return false;

    for ($i = 0; $i < $num_users; ++$i) {
        $user_login = $user_info[$i]->user_login;
        if ($user_login === $nickname) return $user_info[$i]->ID;
    }

    if ($debug) echo "Cannot resolve: $fist_name\t$last_name\t$nickname<br>";
    return false;
}

function cpi_ingest_csv($path) {
    $file = fopen(get_template_directory() . $path, 'r');

    if ($file === false) {
        echo "Error: Could not open $path.<br>";
        return false;
    }
    $data = [];
    while ( ($info = fgetcsv($file) )) $data[] = $info;

    $numRows = count($data);

    echo "$path contains $numRows rows.<br>";
    fclose($file);
    return $data;
}

function cpi_check_field_consistency($data) {
    $standard = count($data[0]);

    $numRows = count($data);

    for ($i = 0; $i < $numRows; ++$i) {
        $test = count($data[$i]);
        if ($test !== $standard) {
            echo "Error: Found $test fields at index $i. Expected $standard fields.<br>";
            return false;
        }
    }

    return true;
}

function cpi_get_field_index_by_field_name($data, $fieldName) {
    // The first row should contain the field names
    $row = $data[0];
    $numFields = count($row);

    for ($i = 0; $i < $numFields; ++$i) {
        if ($row[$i] === $fieldName) return $i;
    }

    return false;
}

function cpi_check_field_uniqueness($data, $fieldIndex) {
    $numRows = count($data);

    // the first row is the name of the fields themselves
    if ($numRows <= 1) return false;

    $value = [];
    
    // skip the first row when pushing field values onto the array
    for ($i = 1; $i < $numRows; ++$i) {
        $value[] = $data[$i][$fieldIndex];
    }

    asort($value);

    $numValues = count($value);
    $numRepeats = 0;
    $prevVal = $value[0];

    for ($i = 1; $i < $numValues; ++$i) {
        $curVal = $value[$i];

        if ($prevVal === $curVal) ++$numRepeats;
        $prevVal = $curVal;
    }
    echo "$numRepeats repeats<br>";

    return $numRepeats;
}

function cpiGetFieldKeyValuePairs($data, $keyIndex, $valIndex) {
    $pairs = [];
    $numRows = count($data);
    
    for ($i = 1; $i < $numRows; ++$i) {
        // if ($i < 10) {
        //     var_dump($data[$i]);
        //     echo $data[$i][$keyIndex] . "<br>";
        // }
        $pairs[] = [trim($data[$i][$keyIndex]) => trim($data[$i][$valIndex])];
    }

    return $pairs;
}


function cpiGetMatchingUserId($sortedPairs, $target) {
    $index = cpiIterativeBinaryIndexSearch($sortedPairs, $target);
    if ($index === false) {
        // echo "No userId for nickname: $target<br>";
        return false;
    }

    // echo "$target: ";
    // cpiDisplayKeyValuePair($sortedPairs[$index]);

    if ($index > 0) {
        $testIndex = $index - 1;
        $testVal = cpiKeyValVal($sortedPairs[$testIndex]);

        if ($target === $testVal) {
            echo "Duplicate nickname: $target<br>";
            return false;
        }
    }

    $testIndex = $index + 1;
    $testVal = cpiKeyValVal($sortedPairs[$testIndex]);

    if ($target === $testVal) {
        echo "Duplicate nickname: $target<br>";
        return false;
    }

    $key = key($sortedPairs[$index]);
    return $key;

}

function cpiDisplayKeyValuePair($pair) {
    $key = key($pair);
    $val = $pair[$key];
    echo "$key => $val<br>";
}

function cpiDisplayRolesById($id) {
    $userMeta = get_userdata($id);
    if (!$userMeta) {
        echo "<br>";
        return;
    }

    $userRoles = $userMeta->roles;
    $numRoles = count($userRoles);

    for ($i = 0; $i < $numRoles; ++$i) echo $userRoles[$i] . " ";
    echo "<br>";
    return;
}

// ingest each CSV and confirm that every row is being parsed into the same number of fields.

$originalExport = cpi_ingest_csv('/data/original-brian-export.csv');
$isConsistent = cpi_check_field_consistency($originalExport);
if (!$isConsistent) exit ("Fields are not consistent.");

$cpiAuthorList = cpi_ingest_csv('/data/cpi-list-of-authors.csv');
$isConsistent = cpi_check_field_consistency($cpiAuthorList);
if (!$isConsistent) exit ("Fields are not consistent.");

$currentExport = cpi_ingest_csv('/data/current-cpi-production-users-2022-07-22-reformatted.csv');
$isConsistent = cpi_check_field_consistency($currentExport);
if (!$isConsistent) exit ("Fields are not consistent.");
echo "<br>";

// Ensure that we can correlate based on nicknames

$originalExportUserLoginIndex = cpi_get_field_index_by_field_name($originalExport, 'user_login');
if ($originalExportUserLoginIndex === false) exit ("Could not locate user_login index for original export");
echo "user_login is found at index $originalExportUserLoginIndex in original export.<br>";


$cpiAuthorListNicknameIndex = cpi_get_field_index_by_field_name($cpiAuthorList, 'nickname');
if ($cpiAuthorListNicknameIndex === false) exit ("Could not locate nickname index for cpi author list");
echo "nickname is found at index $cpiAuthorListNicknameIndex in cpi author list.<br>";

$cpiAuthorListDescriptionIndex = cpi_get_field_index_by_field_name($cpiAuthorList, 'description');
if ($cpiAuthorListDescriptionIndex === false) exit ("Could not locate description index for cpi author list");
echo "description is found at index $cpiAuthorListDescriptionIndex in cpi author list.<br>";

$cpiAuthorListRoleIndex = cpi_get_field_index_by_field_name($cpiAuthorList, 'role');
if ($cpiAuthorListRoleIndex === false) exit ("Could not locate role index for cpi author list");
echo "role is found at index $cpiAuthorListRoleIndex in cpi author list.<br>";


$currentExportSourceUserIdIndex = cpi_get_field_index_by_field_name($currentExport, 'source_user_id');
if ($currentExportSourceUserIdIndex === false) exit ("Could not locate source_user_id index for current export");
echo "source_user_id is found at index $currentExportSourceUserIdIndex in current export.<br>";

$currentExportNicknameIndex = cpi_get_field_index_by_field_name($currentExport, 'nickname');
if ($currentExportNicknameIndex === false) exit ("Could not locate nickname index for current export");
echo "nickname is found at index $currentExportNicknameIndex in current export.<br>";

echo "<br>\n";

// Create a searchable list mapping source_user_id => nickname
$currentExportSourceUserIdsToNickname = cpiGetFieldKeyValuePairs($currentExport, $currentExportSourceUserIdIndex, $currentExportNicknameIndex); 
usort($currentExportSourceUserIdsToNickname, 'cpiKeyValValCompare');

// $index = cpiIterativeBinaryIndexSearch($currentExportSourceUserIdsToNickname, "A.bazley@uea.ac.uk");
// echo "Index: $index<br>";
// cpiDisplayKeyValuePair($currentExportSourceUserIdsToNickname[$index]);

// $userId = cpiGetMatchingUserId($currentExportSourceUserIdsToNickname, "A.bazley@uea.ac.uk");
// echo "UserId: $userId<br>";
$numMissing = 0;

if ($start && $end) {
    $numAuthors = count($cpiAuthorList);
   
    
    for ($i = $start; $i <= $end; ++$i) {
        // try to find userId using source_user_id => nickname map from current export
        $nickname = trim($cpiAuthorList[$i][$cpiAuthorListNicknameIndex]);
        $userId = cpiGetMatchingUserId($currentExportSourceUserIdsToNickname, $nickname);
        
        // if we cannot find userId from map then see if the nickname is an email and then try to find user by email address
        if ($userId === false) {
            // if nickname is an email address try to get the userId using the email address
            if (filter_var($nickname, FILTER_VALIDATE_EMAIL)) {
                // echo "$nickname is an email address<br>";
                $userObject = get_user_by('email', $nickname);
                if ($userObject) $userId = $userObject->ID;
                else $userId = false;
            } else {
                ++$numMissing;
                echo "Missing ID for [$i] $nickname<br>";
            }
        }
    
        // Make changes based on userId

        if ($userId) {
            // echo "[$i] $nickname $userId ";
            // cpiDisplayRolesById($userId);
            
            $description = $cpiAuthorList[$i][$cpiAuthorListDescriptionIndex];
            $role = $cpiAuthorList[$i][$cpiAuthorListRoleIndex];

            // echo "$nickname [$userId]: $role<br>";
            cpi_update_description_by_user_id($userId, $description);
            cpi_update_roles_by_user_id($userId, $role);
        }
        
        $test = $i % 50;

        if ($test === 0) {
            sleep(5);
        }
        
    }
    
    echo "numMissing: $numMissing<br>";
    echo "<br><br><br>";
} 
    ?>
        <input type="text" id="startNumber">
        <input type="text" id="endNumber">
        <button id="submitButton">Submit</button>

        <script>
            const button = document.getElementById('submitButton');

            button.addEventListener('click', () => {
                const start = document.getElementById('startNumber');
                const end = document.getElementById('endNumber');
                const key = 'S3cr3TK3y';
                const { location } = window;
                let url = `https://${location.hostname}${location.pathname}?key=${key}&start=${start.value}&end=${end.value}`;
                // console.log(url);
                window.location = url;
            })
        </script>
    <?php

exit ();



?>