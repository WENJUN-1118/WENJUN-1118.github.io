<?php
header('content-type:application/json');
/* 
   模块A：对上游API的数据获取
   实现：Curl
   相关参数：
   1.URL为请求的API接口地址
   2.post_data为POST的数组存放
*/

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

/*
    实现离线读取line_id信息
    传入线路名即可得到线路id
*/

function get_line_id_v2($roadline)
{
        $file = "./lines.json";
        $data = file_get_contents($file); //读取缓存
        $data = json_decode($data,true);
        foreach ($data as $k => $v)
        {
            if ($v['name'] == $roadline) return $v;
            //根据线路名称查找线路id，找到返回id
        }
    
}

function gps_data_get($line_id,$zbh)
{
    
    $json_string = file_get_contents('./nbbm2PlateNo.json');
    $arr = json_decode($json_string,true);
    //去程
    $url = "http://180.166.5.82:9777/gps/findByLineAndUpDown?lineCode=$line_id&upDown=0";
    $data = json_decode(data_get($url,'','GET'),true);
    $data = $data['list'];
    $x=0;
    foreach ($data as $k => $v)
    {

        $res[$x] = array('lon' => $v['lon'],'lat' => $v['lat'],'zbh' => $v['nbbm'],'timestamp' => $v['timestamp'],'upDown' => $v['upDown'],'time' => $v['seconds'],'distance'=>$v['distance']);
        
        foreach($arr as $t1 => $t2)
        {
                if ($t1 == $v['nbbm'] ) $res[$x]['vid'] = $t2;
        }
        $x++;
    }
    $url = "http://180.166.5.82:9777/gps/findByLineAndUpDown?lineCode=$line_id&upDown=1";
    $data = json_decode(data_get($url,'','GET'),true);
    $data = $data['list'];

    foreach ($data as $k => $v)
    {
        $res[$x] = array('lon' => $v['lon'],'lat' => $v['lat'],'zbh' => $v['nbbm'],'timestamp' => $v['timestamp'],'upDown' => $v['upDown']);
        foreach($arr as $t1 => $t2)
        {
                if ($t1 == $v['nbbm'] ) $res[$x]['vid'] = $t2;
        }
        $x++;
    }        

    return json_encode($res);
}

$roadline = $_GET['roadline'];
$roadline=  strtr("$roadline","%","\\");
$rzw = '{"zw":'.'"'.$roadline.'"'."}";
$rzw = json_decode($rzw);
$roadline = $rzw -> zw ; 

//检查是否为数字线路
    if(preg_match("/^\d*$/",$roadline)) $shuzi = true;
        if ($shuzi){
            $roadline = $roadline.'路';
        }else
        {
            $roadline = $roadline;
        }

$line_info = get_line_id_v2($roadline);
$line_id = $line_info['lineCode'];
$zbh = $_GET['zbh'];
$gps = gps_data_get($line_id,$zbh);
print_r( $gps);