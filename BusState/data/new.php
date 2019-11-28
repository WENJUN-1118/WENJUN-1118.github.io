<?php
    header('content-type:application/json');
    $file = "all.json";
    $data = file_get_contents($file);
    $data = json_decode($data,true);
    $x=0;
    foreach ($data as $key => $value) 
    {
        $arr[$x]['parkName'] = $value['parkName'];
        $arr[$x]['parkCode'] = $value['parkCode'];
        $x++;
    }
    print(json_encode($arr));