<?php

function findbus($zbh)
{
    $file = "data/lineId_Vehicle.json";
    $data = file_get_contents($file);
    $data = json_decode($data,true);
    $x = 0;
    foreach ($data['data'] as $k => $v)
    {
        if (strpos($v['vehicle'],$zbh)!==false and $v['lineId'] !== 'null' and $x<10) {$res[$x] = $v['vehicle'];$x++;}
        
    }
    return $res;
}

function findbusline($zbh)
{
    $file = "data/lineId_Vehicle.json";
    $data = file_get_contents($file);
    $data = json_decode($data,true);
    foreach ($data['data'] as $k => $v)
    {
        if ($v['vehicle'] == $zbh) return $v['lineId'];
    }
}

function get_line_name($lineId)
{
        $file = "data/lineId_linename.json";
        $data = file_get_contents($file);
        $data = json_decode($data,true);
        foreach ($data as $k => $v)
        {
            if ($v['lineCode'] == $lineId) return $v['name'];
        }
    
}

function driver_name($driver_number)
{
    
}

function main($lineId,$zbh)
{
    
    $url = "http://180.166.5.82:9777/gps/findByLineAndUpDown?lineCode=$lineId&upDown=0";
    $data_dir_0 = data_get($url,'','GET');
    $data_dir_0 = json_decode($data_dir_0,true);
    foreach ($data_dir_0['list'] as $k => $v)
    {
        if ($v['nbbm'] == $zbh)
        {
            //$bus_info['latlon'] = $v['point'];
            //print_r( $bus_info['latlon']);
            $bus_info['状态'] = '上行计划';
            if (!empty($v['parkCode'])) $bus_info['状态'] = find_park_name($v['parkCode']);
            else
            {
            
            $bus_info['当前计划'] = $v['sch']['fcsj'].' '.$v['sch']['lpName'].'路牌';
            
            $bus_info['所在站点'] = $v['stationName'];
            
            
                if(!empty($v['sch']['jName'])) 
                {
                    //$bus_info['驾驶员工号'] = '隐藏';
                    if (!empty($_SESSION['name'])) $bus_info['驾驶员工号'] = $v['sch']['jName'];
                } 
            }
            $bus_info['速度'] = $v['speed'];
            $bus_info['GPStime'] = date('Y-m-d H:i:s',substr($v['timestamp'],0,10)); 
        }
        
    }
    $url = "http://180.166.5.82:9777/gps/findByLineAndUpDown?lineCode=$lineId&upDown=1";
    $data_dir_1 = data_get($url,'','GET');
    $data_dir_1 = json_decode($data_dir_1,true);
    foreach ($data_dir_1['list'] as $k => $v)
    {
        if ($v['nbbm'] == $zbh)
        {
            //$bus_info['latlon'] = $v['point'];
            //print_r( $bus_info['latlon']);
            $bus_info['状态'] = '下行计划';
            $bus_info['当前计划'] = $v['sch']['fcsj'].' '.$v['sch']['lpName'].'路牌';
            if (!empty($v['parkCode'])) $bus_info['状态'] = find_park_name($v['parkCode']);
            $bus_info['所在站点'] = $v['stationName'];
            if(!empty($v['sch']['jName'])) 
            {
                //$bus_info['驾驶员工号'] = '隐藏';
                if (!empty($_SESSION['name'])) $bus_info['驾驶员工号'] = $v['sch']['jName'];
            }            
            
            
            $bus_info['速度'] = $v['speed'];
            $bus_info['GPStime'] = date('Y-m-d H:i:s',substr($v['timestamp'],0,10)); 
        }
        
    }
    return $bus_info;
    
    
}

function find_park_name($parkCode)
{
    $file = "data/park.json";
    $data = file_get_contents($file);
    $data = json_decode($data,true);
    foreach ($data as $k => $v)
    {
        if ($v['parkCode'] == $parkCode) return $v['parkName'];
    }
}

function find_plate($zbh)
{
    $json_string = file_get_contents('data/nbbm2PlateNo.json');
    $arr = json_decode($json_string,true);
    foreach ($arr as $k => $v)
    {
        if ($k == $zbh) return $v;
    }
    
}

function data_get($url,$post_data,$method)
{
    $ch = curl_init();
    //curl初始化
    switch ($method){
        case 'GET':
    //GET模式
        break;
        
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, 1);
            //设置curl需要POST请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            //设置curl相关要POST的数据
            break;
   }
   
    curl_setopt($ch, CURLOPT_URL, $url); 
    //设置curl的url地址
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $output = curl_exec($ch); 
    //以下为转码，防中文乱码
    if(! mb_check_encoding($output, 'utf-8')) {
        $output = mb_convert_encoding($output,'UTF-8',['ASCII','UTF-8','GB2312','GBK']);
    }
    curl_close($ch);
    
    return $output;
}
