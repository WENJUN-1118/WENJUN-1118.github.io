<?php


/*
    2019-11-17 浦交模拟图V2.5
    Made by Leo
    W: 在使用本版本前请先清空line文件夹，否则会有意外BUG出现
    W: 请务必创建一个line文件夹，存放线路缓存
*/

/*
主控制模块：
   gpsdata 获取gps等车辆实时站点信息
   station 获取站点信息
   departscreen 获取发车屏信息
*/
function main($roadline,$function) {
    $line_info = get_line_id_v2($roadline);
    $line_id = $line_info['lineCode'];
    //系统中的线路ID [Example:10068]
    $line_start = $line_info['startStationName'];
    //线路的起始站 [Example:永泰路东明路]
    $line_end = $line_info['endStationName'];
    //线路的终点站 [Example:济阳路永耀路]

    switch ($function) {
        case 'gpsdata':
            $station_data = station_output_A($roadline,stop_info($roadline,$line_id));
            print_r(json_encode((gps_data_get($roadline,$line_start,$line_end,$line_id,json_decode($station_data,true)))));
            break;

        case 'station':

            $station_data = station_output_A($roadline,stop_info($roadline,$line_id));
            print_r(($station_data));
            break;
        case 'departscreen':

            $depart_time = departscreen($line_id,$roadline);
            print_r($depart_time);
            break;
    }


}


/*
    实现离线读取line_id信息
    传入线路名即可得到线路id
*/

function get_line_id_v2($roadline) {
    $file = "./lines.json";
    $data = file_get_contents($file);
    $data = json_decode($data,true);
    foreach ($data as $k => $v) {
        if ($v['name'] == $roadline) return $v;
        //根据线路名称查找线路id，找到返回id
    }

}

/*
   模块A：对上游API的数据获取
   实现：Curl
   相关参数：
   1.URL为请求的API接口地址
   2.post_data为POST的数组存放
*/

function data_get($url,$post_data,$method) {
    $ch = curl_init();
    //curl初始化
    switch ($method) {
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
    if (! mb_check_encoding($output, 'utf-8')) {
        $output = mb_convert_encoding($output,'UTF-8',['ASCII','UTF-8','GB2312','GBK']);
    }
    curl_close($ch);

    return $output;
}

/*
   模块C：获取上海交通系统中的某一线路的站点信息
   接口来源：浦东交通云
   相关参数：
   1.roadline 线路名称
   2.line_id  线路ID号
*/

function stop_info($roadline,$line_id) {
    $file = "./line/$roadline.json";
    if (!file_exists($file)) {
        //判断线路站点是否已经被缓存
        $url = "http://180.166.5.82:9777/geo_data/stations/".$line_id;
        $result = data_get($url,'','GET');
    }
    return $result;
    //如缓存 此模块直接跳过，不执行
}

/*
   模块DA：站点数据格式化处理
   相关参数：
   1.data 站点数据
*/

function station_output_A($roadline,$data) {
    //降低api服务器压力，加快反馈速度（此处做缓存）
    $file = "./line/$roadline.json";
    if (file_exists($file)) {
        $data = file_get_contents($file);
        //读取缓存
    }
    //如果没有缓存，那么正常将function stop_info进行数据处理
    else
    {
        $data = json_decode($data,true);

        $data = $data['stations'];

        $sum = count($data[0]) + count($data[1]);
        //计算来回的站点总数
        $data0 = station_output_B($roadline,$data,0);
        //去程站点数据
        $data1 = station_output_B($roadline,$data,1);
        //回程站点数据

        $data = array_merge($data0,$data1);
        //回程去程的2个数组合并
        $data = array('Count' => $sum , 'data' => $data);
        $data = json_encode($data);
        file_put_contents($file,$data);
        //获取的文件缓存，未来此处增加一个判断缓存有效性的逻辑功能

    }
    return $data;
}

/*
   模块DB：站点数据格式化处理
   相关参数：
   1.data 站点数据
*/

function station_output_B($roadline,$output,$direction) {
    //此函数，为了与前端相匹配，从而对数据做整理

    /*
    模拟图相关必要参数说明：
        Upstream、Downstream 2者有且只能有一个为true,表明上下行
        sumup 分别存储上行、下行的站点个数
        LevelName 站点名称
        ToDirection 该方向的终点站名称
        LevelId 站点序列 —— 1为首项，1为公差的等差数列
        （LevelId可以通俗地理解为那一站是在该方向中的第几站）
    其他与模拟图非相关，仅预留的参数，测试用途：
        Stationid  在公交网系统中的站点ID
    */
    switch ($direction) {
        case 0:
            $Upstream = '';
            $Downstream = 'true';
            $sumup = count($output[0]);
            $finalup = $output[0][$sumup-1]['name'];
            break;

        case 1:
            $Upstream = 'true';
            $Downstream = '';
            $sumup = count($output[1]);
            $finalup = $output[1][$sumup-1]['name'];
            break;
    }

    $data = array();
    foreach ($output[$direction] as $id => $zdmc) {
        $data1 = array('RoadLine' => $roadline , 'Upmax' => $sumup, 'LevelName' => $zdmc['name'] , 'LevelId' => $id+1 ,'Stationid' => $zdmc['stationCode'],'shapesType' => $zdmc['shapesType'],'radius' => $zdmc['radius'],'Upstream' => $Upstream,'Downstream' => $Downstream,'ToDirection' => $finalup);
        $x++;
        $data = array_merge($data,array($x => $data1));
        //数据合并
    }
    return $data;
}

function pd_group_corp($roadline) {
    //Set Url
    $url = "http://103.56.60.48:51481/interface/Handler.ashx?action=getgpsdata&roadline=".urlencode(iconv('utf-8', 'gb2312',$roadline));
    $data = data_get($url,'','GET');
    $data = json_decode($data,true);
    return $data['data'];

}

/*
   模块E：实时公交数据格式化处理
*/

function gps_data_get($roadline,$line_start,$line_end,$line_id,$station_data) {
    $json_string = file_get_contents('./zb_data.json');
    $arr = json_decode($json_string,true);
    //去程
    $url = "http://180.166.5.82:9777/gps/findByLineAndUpDown?lineCode=$line_id&upDown=0&t=".rand();
    $data = json_decode(data_get($url,'','GET'),true);
    //计算配车
    $jhpc = count($data['list']);
    //计算当前运营车辆
    //$file = "./pc/$roadline.json";
    //配车数据缓存文件
    if (file_exists($file)) {
        $data2 = file_get_contents($file);
        //读取缓存
        $data2 = json_decode($data2,true);
    }
    //$base_data = array ('jhpc'=>$jhpc,'dqyy'=>$jhpc);

    //初始化变量
    $x = 0;
    $res = array();

    foreach ($data['list'] as $order => $v_info) {

        //将自编转化为车牌
        foreach ($arr as $t1 => $t2) {
            if ($t1 == $v_info['nbbm']) $res[$x]['vid'] = $t2;
        }

        $res[$x]['vnumber'] = $v_info['nbbm'];
        //自编号

        $res[$x]['todir'] = 0;
        //方向

        $res[$x]['Speed'] = round($v_info['speed']);
        //车辆速度（GPS计算）

        $upmax = $station_data['data'][0]['Upmax'];
        //获取上行站点个数
        $mid = round($upmax * 0.65);

        if ($v_info['seconds'] < 60) {
            $res[$x]['dzsj'] = '剩余'.$v_info['seconds'].'秒 '.$v_info['distance'].'米';
        } else
        {
            $res[$x]['dzsj'] = '剩余'.round($v_info['seconds']/60) .'分钟 '.$v_info['distance'].'米';
        }
        //公交车到站预测

        if ($v_info['distance'] <210) $res[$x]['dzsj'] ='即将进站';

        $res[$x]['lpname'] = $v_info['sch']['lpName'].'路牌 '.$v_info['sch']['fcsj'];

        $res[$x]['DWTime'] = date('Y-m-d H:i:s', $v_info['serverTimestamp']/1000);
        //车上设备的定位时间戳格式转换

        // res[$x]['stationCode'] = $v_info['stationCode'];

        //该段功能为根据站点ID、名字查找现在车辆行驶在第几站（nextlevel = nowlevel + 1）
        foreach ($station_data['data'] as $xl => $zd) {
            if ($zd['Stationid'] == $v_info['stationCode']) {
                $res[$x]['nextlevel'] = $xl+2;
                break;
            }

            //if($zd['Stationid'] == $v_info['stationCode']) $res[$x]['nextlevel'] = $xl+2-$upmax;
        }
        //判断是否在最后一站

        //if ($res[$x]['nextlevel']+2 == $upmax) $rate = 0;

        if (empty($v_info['sch']['lpName'])) $res[$x]['drivername'] = '未知班次';
        else $res[$x]['drivername'] = '全程 '.$res[$x]['lpname'];

        //判断运营模式是否为区间
        if ($v_info['sch']['bcType'] == 'region') {
            $res[$x]['drivername'] = '区间 '.$res[$x]['lpname'].' '.$v_info['sch']['qdzName'].'->'.$v_info['sch']['zdzName'];
            $res[$x]['busrun'] = 'interval';
        } else $res[$x]['busrun'] = 'all';
        //busrun参数用于模拟图区间标识的判断，busrun == interval时，表明本车为区间车（即时本车在模拟图中将变为绿色）

        $res[$x]['state'] = '营运车辆';
        //默认认为所有车都是运营车辆

        if ($res[$x]['nextlevel'] == 2) {
            $res[$x]['state'] = $v_info['stationName'];
            //先把所有有可能在终点站的车，状态全部定义为在终点站
            if ($res[$x]['drivername'] <> '未知班次') $res[$x]['state'] = '营运车辆';
            //显示车辆nextlevel=2只有2种情况 ——位于终点站或者在首站路途中。拥有路牌那么就是在运营，故归于首站车
            if ((time() - $v_info['serverTimestamp']/1000) > 1200) {
                $res[$x]['state'] = '停车场';
                $sjyy++;
            }
        }

        $v_info['seconds2'] = -$v_info['seconds2'];

        if ($res[$x]['state'] == '营运车辆') {
            if (!empty($v_info['sch']['lpName'])) {
                if ($v_info['sch']['inOut'] == 'true' or (time() - $v_info['serverTimestamp']/1000) > 720) {
                    $res[$x]['state'] = '停车场';
                    $sjyy++;
                }
            } elseif (!empty($v_info['parkCode']) and $res[$x]['state'] <> $v_info['stationName']) {
                $res[$x]['state'] = '停车场';
                $sjyy++;
            }/* elseif ($res[$x]['nextlevel'] > 2 and $res[$x]['nextlevel'] < $mid and empty($v_info['sch']['lpName'])) {
                $res[$x]['state'] = '停车场';
                $sjyy++;
            } */elseif (empty($v_info['distance']) or $v_info['seconds2'] > 60 or (time() - $v_info['serverTimestamp']/1000) > 90) {
                $res[$x]['state'] = '停车场';
                $sjyy++;
            }
        }

        /*
            相关参数说明：
                        $v_info['distance'] 离下一站距离（m）
                        $v_info['seconds2'] 到达下一站所需时间（s）
                        $v_info['lpName']   路牌信息
            逻辑判断说明：
            对于车辆是否为运营状态的判断主要在于对预计到达时间、路牌目的地、是否有停车场标签判定
            判断逻辑为：
                优先判断本车有没有路牌，如果有路牌且终点不为停车场那么就不再继续判断直接纳入运营车辆
                                        如果有路牌且终点为停车场那么就不再继续判断直接纳入停车场状态（非运营车）
                如果没有路牌：
                            判断是否有停车场标签(parkCode)，如果有就不再继续判断直接纳入停车场状态
                                                            如果没有 判断是否有distance 并且 seconds2 > -30s ***
                                                                     如果2个条件都满足则纳入运营车辆
                                                                     否则纳入停车场状态（非运营车）
                *** 由于浦交系统的设计缺陷，seconds2可能会因为gps偏差导致为负值，但是运营车一定大于-60s
                    部分时候车辆GPS掉线，为了防止车辆一直滞留在一站引起误导，超时12分钟即从模拟图上移除
            模拟图必须的可常值参数，此为默认值，如无必要请勿修改
            rate 表明当前车辆在行驶在2站之间的哪个位置 rate∈ [0,1]
                1为已经到达下一站 该段路程行驶完成
                0为刚刚到达本站 该段行驶路程开始 默认为0
                箭头的判断随每辆车的rate值决定
                注意：此值在手机版模拟图已经废弃，但是在电脑版巴士通依旧正常
                当然你可以通过各类信息源使得rate值可以被算出，请在此处添加rate计算代码
            stationid 此值已经废弃 但是如果不赋予默认值模拟图会报错
        */

        $res[$x]['stationid'] = -1;
        $res[$x]['rate'] = 0;
        $res[$x]['wz'] = $station_data['data'][$res[$x]['nextlevel']-1]['LevelName'];
        //展现当前位置（离本车最近的站点）

        $x++;
        //累加器
    }

    //回程
    $url = "http://180.166.5.82:9777/gps/findByLineAndUpDown?lineCode=$line_id&upDown=1&t=".rand();
    $data = json_decode(data_get($url,'','GET'),true);

    foreach ($data['list'] as $order => $v_info) {

        //将自编转化为车牌
        foreach ($arr as $t1 => $t2) {
            if ($t1 == $v_info['nbbm']) $res[$x]['vid'] = $t2;
        }

        $res[$x]['vnumber'] = $v_info['nbbm'];
        //自编号
        $res[$x]['todir'] = 1;
        //方向
        $res[$x]['Speed'] = round($v_info['speed']);
        //速度
        $res[$x]['DWTime'] = date('Y-m-d H:i:s', $v_info['serverTimestamp']/1000);
        //设备时间戳转化

        if ($v_info['seconds'] < 60) {
            $res[$x]['dzsj'] = '剩余'.$v_info['seconds'].'秒 '.$v_info['distance'].'米';
        } else
        {
            $res[$x]['dzsj'] = '剩余'.round($v_info['seconds']/60) .'分钟 '.$v_info['distance'].'米';
        }
        //到站预测 判定如在一分钟以内则改显秒数

        if ($v_info['distance'] < 210) $res[$x]['dzsj'] = '即将进站';
        //进站提醒

        $res[$x]['lpname'] = $v_info['sch']['lpName'].'路牌 '.$v_info['sch']['fcsj'];
        //路牌信息

        if (empty($v_info['sch']['lpName'])) $res[$x]['drivername'] = '未知班次';
        else $res[$x]['drivername'] = '全程 '.$res[$x]['lpname'];

        //判断运营模式是否为区间
        if ($v_info['sch']['bcType'] == 'region') {
            $res[$x]['drivername'] = '区间 '.$res[$x]['lpname']." ".$v_info['sch']['qdzName'].'->'.$v_info['sch']['zdzName'];
            $res[$x]['busrun'] = 'interval';
        } else $res[$x]['busrun'] = 'all';


        foreach ($station_data['data'] as $xl => $zd) {
            if ($zd['Stationid'] == $v_info['stationCode']) {
                $res[$x]['nextlevel'] = $xl+2-$upmax;
            }
        }

        //判断是否到达最后一站
        if ($res[$x]['nextlevel'] == $upmax) $rate = 0;

        $res[$x]['state'] = '营运车辆';
        $v_info['seconds2'] = -$v_info['seconds2'];

        if (!empty($v_info['sch']['lpName'])) {
            if ($v_info['sch']['inOut'] == 'true') {
                $res[$x]['state'] = '停车场';
                $sjyy++;
            }
        } elseif (empty($v_info['stationName'])) {
            $res[$x]['state'] = '停车场';
            $sjyy++;
        } /*elseif ($res[$x]['nextlevel'] > 2 and $res[$x]['nextlevel'] < $mid and empty($v_info['sch']['lpName'])) {
            $res[$x]['state'] = '停车场';
            $sjyy++;
        } */elseif (empty($v_info['distance']) or $v_info['seconds2'] > 60 or (time() - $v_info['serverTimestamp']/1000) > 100) {
            $res[$x]['state'] = '停车场';
            $sjyy++;
        }
        $res[$x]['stcsecond'] = $v_info['seconds2'];

        if ($res[$x]['nextlevel'] == 2) {
            $res[$x]['state'] = $v_info['stationName'];
            if ($res[$x]['drivername'] <> '未知班次') $res[$x]['state'] = '营运车辆';
            //如果拥有路牌，那么就是在运营，由于nextlevel=2，故归于首站车（解释原因看dir0中的代码），但是如果GPS超时，则回终点站。
            if ((time() - $v_info['serverTimestamp']/1000) > 1200) {
                $res[$x]['state'] = '停车场';
            }
        }
        //整理结束

        $res[$x]['stationid'] = -1;
        $res[$x]['rate'] = 0;
        $res[$x]['wz'] = $station_data['data'][$res[$x]['nextlevel']+$upmax-1]['LevelName'];
        $x++;
    }
    if (strpos($roadline,'申崇') !== false) {
        $group_data = pd_group_corp($roadline);
        $res = array_merge($group_data,$res);
    }

    //并入数组并返回值
    $res = array('Count' => $x+$x1 , 'data' => $res);
    return $res;
}


/*
   2019-06-17 Update
   模块F：实时公交发车时间处理
*/

function departscreen($line_id,$roadline) {
    $json_string = file_get_contents('./zb_data.json');
    //读取车牌与自编号信息文件

    $arr = json_decode($json_string,true);


    /*去程*/
    $url = "http://180.166.5.82:9777/xxfb/getdispatchScreen?lineid=$line_id&direction=0&t=";
    //此为发车时间获取的API
    $data = data_get($url,'','GET');
    $xml = simplexml_load_string($data);
    //由于获得的数据为XML类型，需要转化为Json后才能顺利转化为数组
    $data = json_decode(json_encode($xml),TRUE);
    //将转化后的Json进一步转化为数组

    $data = $data['cars']['car'];
    $x = 0;

    //将车牌转化为自编
    foreach ($arr as $t1 => $t2) {
        if ($t2 == $data[0]['vehicle']) $d0_car = $t1;
        if (empty($data[0]['time'])) {
            if ($t2 == $data['vehicle']) $d0_car = $t1;
        }
    }


    $jhjg = (strtotime($data[1]['time']) - strtotime($data[0]['time']))/60;
    //第二辆车的发车时间减去第一辆车的发车时间为计划发车时间
    if ($jhjg < 0) $jhjg = 0;
    $yjjg = (strtotime($data[2]['time']) - strtotime($data[1]['time']))/60;
    //第三辆车的发车时间减去第二辆车的发车时间为预计发车时间
    if ($yjjg < 0) $yjjg = $jhjg;
    if (empty($data[0]['time'])) {
        $data[0]['time'] = $data['time'];
    }

    if (strtotime($data[0]['time']) < time()) {
        if (!empty($data[1]['time'])) {
            if (strtotime($data[1]['time']) > time()) {
                $data[0]['time'] = $data[1]['time'];
                foreach ($arr as $t1 => $t2) if ($t2 == $data[1]['vehicle']) $d0_car = $t1;
                $jhjg = $yjjg;
            } elseif (!empty($data[2]['time'])) {
                if (strtotime($data[2]['time']) > time()) {
                    $data[0]['time'] = $data[2]['time'];
                    foreach ($arr as $t1 => $t2) if ($t2 == $data[2]['vehicle']) $d0_car = $t1;
                    $jhjg = $yjjg;
                }
            }
        }
    }


    /*
            判断发车时间是否正常
            判断第一个时间是否在当前时间后
                在当前时间后 -- 正常，跳出判断
                在当前时间前 -- 异常，
                判断第二个时间是否存在
                    存在 -- 判断第二个时间是否在当前时间后
                            在当前时间后 -- 正常，把二的时间显示，跳出判断
                            在当前时间前 -- 异常，判断第三个时间是否存在
                                存在 -- 判断第三个时间是否在当前时间后
                                    在当前时间后 -- 正常，把三的时间显示，跳出判断
                                    在当前时间前 -- 异常，返回null
                                不存在 -- 返回null
                    不存在 -- 返回null
        */

    if (!empty($data[0]['time'])) $data[0]['time'] = date('H:i',strtotime($data[0]['time']));

    $data0 = array('dir' => 0,'VEHICLENUMBERING' => $d0_car,'PLANTIME' => $data[0]['time'],'jhjg' => $jhjg,'yjjg' => $yjjg);
    //发车时间整理，封装成模拟图能正常读取的API


    /*回程*/
    $url = "http://180.166.5.82:9777/xxfb/getdispatchScreen?lineid=$line_id&direction=1&t=";
    $data = data_get($url,'','GET');
    $xml = simplexml_load_string($data);
    $data = json_decode(json_encode($xml),TRUE);
    $data = $data['cars']['car'];
    $x = 0;
    foreach ($arr as $t1 => $t2) {
        if ($t2 == $data[0]['vehicle']) $d1_car = $t1;
        if (empty($data[0]['time'])) {
            if ($t2 == $data['vehicle']) $d1_car = $t1;
        }
    }

    $jhjg = (strtotime($data[1]['time']) - strtotime($data[0]['time']))/60;
    if ($jhjg < 0) $jhjg = 0;
    $yjjg = (strtotime($data[2]['time']) - strtotime($data[1]['time']))/60;
    if ($yjjg < 0) $yjjg = $jhjg;
    if (empty($data[0]['time'])) $data[0]['time'] = $data['time'];

    if ((strtotime($data[0]['time']) + 600) < time()) {
        if (!empty($data[1]['time'])) {
            if (strtotime($data[1]['time']) > time()) {
                $data[0]['time'] = $data[1]['time'];
                foreach ($arr as $t1 => $t2) if ($t2 == $data[1]['vehicle']) $d1_car = $t1;
                $jhjg = $yjjg;
            } elseif (!empty($data[2]['time'])) {
                if (strtotime($data[2]['time']) > time()) {
                    $data[0]['time'] = $data[2]['time'];
                    foreach ($arr as $t1 => $t2) if ($t2 == $data[2]['vehicle']) $d1_car = $t1;
                    $jhjg = $yjjg;
                }
            }
        }
    }
    if (!empty($data[0]['time'])) $data[0]['time'] = date('H:i',strtotime($data[0]['time']));
    //转换时间，去掉秒位
    $data1 = array('dir' => 1,'VEHICLENUMBERING' => $d1_car,'PLANTIME' => $data[0]['time'],'jhjg' => $jhjg,'yjjg' => $yjjg);

    $data2['jhpc'] = 0;
    $data2['dqyy'] = 0;

    $data = array('Count' => 2,'jhpc' => $data2['jhpc'],'dqyy' => $data2['dqyy'],'data' => array($data0,$data1));
    return json_encode($data);
}

$function = $_GET['Method'];

$roadline = $_GET['roadline'];

//roadline 格式处理
$roadline = strtr("$roadline","%","\\");
$rzw = '{"zw":'.'"'.$roadline.'"'."}";
$rzw = json_decode($rzw);
$roadline = $rzw -> zw ;

//正则判断车迷输入的roadline是否为纯数字，如果是则自动在最后加入“路”
if (preg_match("/^\d*$/",$roadline)) $roadline = $roadline.'路';
//准备工作就绪，调用主函数
main($roadline,$function);
?>
