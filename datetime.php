<?php
    // test datetime stuff
    $datetime = new DateTime('2010-11-07 19:00:00');
    echo $datetime->format('F j, Y');  
    echo '<br/>';
    echo $datetime->format('g:i a');
?>
