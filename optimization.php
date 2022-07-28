<?php

function mcwTimestamp($label = 'timestamp'){
    $curTime = microtime();
    ?>
        <script>
            console.log(<?php echo $label ?>, <?php echo $curTime ?>);
        </script>

    <?php
}


?>