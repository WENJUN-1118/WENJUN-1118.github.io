var map;
var markerList =[];
//初始化地图
function init(){
	map = new AMap.Map('container', {
		zoom:13,
		zooms:[12,18],
		center: [121.51233,31.175169],
		viewMode:'2D',
		resizeEnable: true,
		lang:'zh_cn',
		mapStyle: 'amap://styles/whitesmoke',
		features: ['bg','road'],
	});
	//地图加载完成后获取车辆数据
	map.on('complete', function(){
		//refreshBuses();
		new_center();
		//lineSearch();
	});
}

//从GPS坐标转换成高德地图坐标
function transGPS(lon, lat){
	lon = lon + 0.0045;
	lat = lat - 0.0019;
	lonLat = new AMap.LngLat(lon,lat);
	return lonLat;
}

var x_PI = 3.14159265358979324 * 3000.0 / 180.0;
var PI = 3.1415926535897932384626;
var a = 6378245.0;
var ee = 0.00669342162296594323;

function wgs84togcj02(lng, lat) {
        var dlat = transformlat(lng - 105.0, lat - 35.0);
        var dlng = transformlng(lng - 105.0, lat - 35.0);
        var radlat = lat / 180.0 * PI;
        var magic = Math.sin(radlat);
        magic = 1 - ee * magic * magic;
        var sqrtmagic = Math.sqrt(magic);
        dlat = (dlat * 180.0) / ((a * (1 - ee)) / (magic * sqrtmagic) * PI);
        dlng = (dlng * 180.0) / (a / sqrtmagic * Math.cos(radlat) * PI);
        var mglat = lat + dlat;
        var mglng = lng + dlng;
        return [mglng, mglat]
    
}

function transformlat(lng, lat) {
    var ret = -100.0 + 2.0 * lng + 3.0 * lat + 0.2 * lat * lat + 0.1 * lng * lat + 0.2 * Math.sqrt(Math.abs(lng));
    ret += (20.0 * Math.sin(6.0 * lng * PI) + 20.0 * Math.sin(2.0 * lng * PI)) * 2.0 / 3.0;
    ret += (20.0 * Math.sin(lat * PI) + 40.0 * Math.sin(lat / 3.0 * PI)) * 2.0 / 3.0;
    ret += (160.0 * Math.sin(lat / 12.0 * PI) + 320 * Math.sin(lat * PI / 30.0)) * 2.0 / 3.0;
    return ret
}
 
function transformlng(lng, lat) {
    var ret = 300.0 + lng + 2.0 * lat + 0.1 * lng * lng + 0.1 * lng * lat + 0.1 * Math.sqrt(Math.abs(lng));
    ret += (20.0 * Math.sin(6.0 * lng * PI) + 20.0 * Math.sin(2.0 * lng * PI)) * 2.0 / 3.0;
    ret += (20.0 * Math.sin(lng * PI) + 40.0 * Math.sin(lng / 3.0 * PI)) * 2.0 / 3.0;
    ret += (150.0 * Math.sin(lng / 12.0 * PI) + 300.0 * Math.sin(lng / 30.0 * PI)) * 2.0 / 3.0;
    return ret
}



/*公交线路查询*/
function lineSearch() {

	var busLineName = getQueryVariable("roadline");
	//var busLineName = document.getElementById('BusLineName').value;
	var linesearch = new AMap.LineSearch({
		pageIndex: 1,
		city: '上海',
		pageSize: 2,
		extensions: 'all'
	});

	//搜索“985”相关公交线路
	linesearch.search(busLineName, function(status, result) {
		if (status === 'complete' && result.info === 'OK') {
			lineSearch_Callback(result);
		}
	});
}
/*公交路线查询服务返回数据解析概况*/
function lineSearch_Callback(data) {
	var lineArr = data.lineInfo;
	var lineNum = data.lineInfo.length;
	if (lineNum > 0) {
		for (var i = 0; i < lineNum; i++) {
			var pathArr = lineArr[i].path;
			drawbusLine(pathArr,i);
		}
	}
}
/*绘制路线*/
function drawbusLine(BusArr,updown) {
	//定义上下行颜色
	var color = 'blue';
	if (updown==0){
		color = 'red';
	}
	//绘制路线
	busPolyline = new AMap.Polyline({
		map: map,
		path: BusArr,
		strokeColor: color,
		strokeOpacity: 0.5,
		strokeWeight: 1,
	});
	map.setFitView();//根据线路范围自动调整地图显示位置
	

}


