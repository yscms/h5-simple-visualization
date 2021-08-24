<?php
namespace app\ecop\controller;
use app\mc\model\UserModel;
use think\Controller;
use think\Db;
use app\threeD\model\BuildingCheckModel;
use app\threeD\model\BuildingCheckResultModel;
use app\threeD\model\BuildingModel;
use think\Request;
use app\ecop\model\UserSmartMonitorModel;


class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }


    /*
     *
     * 用于展示地图
     *
     * */
    public function showMap()
    {
        //获得所有的区
        $regions = Db::query("select * from xt_t_organise WHERE `level`=1 AND LEVEL_UP_KEYID=11");
        //获得所有组织的keyId
        $organizeKeyId = Db::query("SELECT keyid FROM xt_t_organise WHERE xt_t_organise.LEVEL_UP_KEYID=?", [$regions[0]['keyid']]);
        $keyId = array();
        // dump($organizeKeyId);
        for ($i = 0; $i < count($organizeKeyId); $i++) {
            $keyId[$i] = $organizeKeyId[$i]["keyid"];
        }
        $keyIds = implode(',', $keyId);
        $building = Db::query("select * from e_equipment WHERE FK_Company_Id IN ($keyIds)");
        //dump($building);
        $this->assign('id',0);
        $this->assign('regions',$regions);
        $this->assign('buildings', json_encode($building));
        return $this->fetch();
    }


    /*
     *
     * 用于展示建筑的具体信息
     *
     * */
    public function showBuildingInfo()
    {
        $id = input('id');
        $building = BuildingModel::getByKeyId($id);
        /* $check=BuildingCheckModel::getByFkEquipmentId($id);
         $checkDetail=BuildingCheckResultModel::where('FK_Check_Id',$check->Key_Id)->select();
        $this->assign('checkDetail',$checkDetail);
        */
        $this->assign('building', $building);
        //获得大类步骤($step类型为obj数组)
        $step = BuildingCheckResultModel::where('FK_Equipment_Id', '=', $id)->where('level', '=', 1)->select();
        //小类步骤
        $little_step = array();
        for ($i = 0; $i < count($step); $i++) {
            $little_step[$i] = BuildingCheckResultModel::where('FK_Equipment_Id', '=', $id)->where('parentID', '=', $step[$i]->basic_equ_id)->where('level', '=', 2)->select();
        }
        //判断这个建筑是否进行了录入
        if (empty($step) || empty($little_step)) {
            $this->error("该建筑信息还没有完善，请完善后再试。");
        } else {
            $this->assign('BigStep', $step);
            $this->assign('LittleStep', $little_step);
            return $this->fetch();
        }


    }


    /*
     * wj
     * 2017-10-16
     * 点击地图上的某个点显示的界面
     */
    public function showPointInfo()
    {
//        $id = input('id');
//        $building = BuildingModel::getByKeyId($id);
//        /* $check=BuildingCheckModel::getByFkEquipmentId($id);
//         $checkDetail=BuildingCheckResultModel::where('FK_Check_Id',$check->Key_Id)->select();
//        $this->assign('checkDetail',$checkDetail);
//        */
//        $this->assign('building', $building);
//        //获得大类步骤($step类型为obj数组)
//        $step = BuildingCheckResultModel::where('FK_Equipment_Id', '=', $id)->where('level', '=', 1)->select();
//        //小类步骤
//        $little_step = array();
//        for ($i = 0; $i < count($step); $i++) {
//            $little_step[$i] = BuildingCheckResultModel::where('FK_Equipment_Id', '=', $id)->where('parentID', '=', $step[$i]->basic_equ_id)->where('level', '=', 2)->select();
//        }
//        //判断这个建筑是否进行了录入
//        if (empty($step) || empty($little_step)) {
//            $this->error("该建筑信息还没有完善，请完善后再试。");
//        } else {
//            $this->assign('BigStep', $step);
//            $this->assign('LittleStep', $little_step);
//            return $this->fetch();
//        }

        return $this->fetch();
    }


    public function test()
    {

        /*  $checkDetail=BuildingCheckResultModel::where('FK_Check_Id',"F04AC117-35A1-7DB4-53D3-88F8AA95745B")->select();
          echo dump($checkDetail);*/

        //获得大类步骤($step类型为obj数组)
        $step = BuildingCheckResultModel::where('FK_Equipment_Id', '=', 208)->where('level', '=', 1)->select();
        //小类步骤
        $little_step = array();
        for ($i = 0; $i < count($step); $i++) {
            $little_step[$i] = BuildingCheckResultModel::where('FK_Equipment_Id', '=', 208)->where('parentID', '=', $step[$i]->basic_equ_id)->where('level', '=', 2)->select();
        }

        dump($step);
        echo "++++++++++++++++++++++++++++++++++++<br>";

        dump($little_step);

    }
    /**
     * 通过ajax获取某个区下所有的建筑
     *
     *
     * */
    public function getBuildings(){
        $id=input('id');
        //获得所有的区
        $regions = Db::query("select * from xt_t_organise WHERE `level`=1 AND LEVEL_UP_KEYID=11");
        //获得所有组织的keyId
        $organizeKeyId = Db::query("SELECT keyid FROM xt_t_organise WHERE xt_t_organise.LEVEL_UP_KEYID=?", [$regions[$id]['keyid']]);
        $keyId = array();
        // dump($organizeKeyId);
        for ($i = 0; $i < count($organizeKeyId); $i++) {
            $keyId[$i] = $organizeKeyId[$i]["keyid"];
        }
        $keyIds = implode(',', $keyId);
        $building = Db::query("select Key_Id,Equipment_Name,Equipment_Address,Global_Positioning_latitude,Global_Positioning_longitude from e_equipment WHERE FK_Company_Id IN ($keyIds)");
        //dump($building);
        // echo json_encode($building);
        $this->assign('id',input('id'));
        $this->assign('regions',$regions);
        $this->assign('buildings', json_encode($building));
        return $this->fetch('showmap');
        //dump($building);
    }

    /**
     *
     * ajax
     * 获取模糊查询的结果
     *
     *
     * */
    public function fuzzyQueryByName()
    {
        $name = trim(input('name'));
        $buildings = Db::query("select * from e_equipment WHERE Equipment_Name LIKE '%$name%' ");
        //dump($buildings);
        if (empty($buildings)) {
            echo json_encode('no');
        } else {
            echo json_encode($buildings);
        }
    }

    public function testPage(){
        $check=BuildingCheckModel::paginate(3);
        $page=$check->render();
        $this->assign('check',$check);
        return $this->fetch('test_page');
    }

    public function showThreeModel()
    {

        return $this->fetch();
    }

    public function test1()
    {

        return $this->fetch();
    }

    public function showModel()
    {

        return $this->fetch();
    }


    /*
     * 2017-11-27
     * wangjian
     * 从数据库获得消防的 姓名 温度 安全状态 位置信息
     */
    public function getFireManMessage()
    {
        $pdo = new \PDO("mysql:host=119.29.189.235;dbname=ifp", "remote", "87BFE21D-5E20-A3B8-87B0-1E4B771C9E2C");
        $rs = $pdo->query("select * from u_smart_monitoring order by operate_date desc  limit 1");
        $result = $rs->fetchAll(\PDO::FETCH_ASSOC);
//        print_r($result);
        $result = json_encode($result);
        $pdo = null;//关闭连接
//        $result="kkkkk";
        return $result;
    }


    /**
     * @return int
     * User: wangjian
     * Date: 2017-11-29
     * Time: ${TIME}
     * Function： 获得最大的监测批次
     */
    public function getMaxBatchCode()
    {
        $pdo = new \PDO("mysql:host=119.29.189.235;dbname=ifp", "remote", "87BFE21D-5E20-A3B8-87B0-1E4B771C9E2C");
        $rs = $pdo->query("select max(batch_code) as maxNum from u_smart_monitoring");
        $result = $rs->fetchAll(\PDO::FETCH_ASSOC);
//        print_r($result);
        $result = $result[0]['maxNum'];
//        print_r($result);
        $pdo = null;//关闭连接
//        $result="kkkkk";
        return $result;
    }

    /**
     * @return array|string   返回一个json数据
     * User: wangjian
     * Date: 2017-11-29
     * Time: ${TIME}
     * Function：根据最大监测批次，获取所有的消防员列表
     * [{"user":"hzc1","code":"17","count(*)":"26"},{"user":"hzc2","code":"18","count(*)":"25"},{"user":"hzc3","code":"19","count(*)":"26"}]
     */
    public function getAllFireManList()
    {
        $pdo = new \PDO("mysql:host=119.29.189.235;dbname=ifp", "remote", "87BFE21D-5E20-A3B8-87B0-1E4B771C9E2C");
        $rs = $pdo->query("select user,code,count(*) from u_smart_monitoring where batch_code=(select max(batch_code) from u_smart_monitoring) group by code");
        $result = $rs->fetchAll(\PDO::FETCH_ASSOC);
//        print_r($result);
//        $result = $result[0];
        $result=json_encode($result);
//        print_r($result);

        $pdo = null;//关闭连接
        return $result;
    }


    /**
     * @return array int
     * User: wangjian
     * Date: 2017-11-29
     * Time: ${TIME}
     * Function：查询得到数据库中最大监测批次消防员的个数
     */
    public function getNewFireManCount()
    {
        $pdo = new \PDO("mysql:host=119.29.189.235;dbname=ifp", "remote", "87BFE21D-5E20-A3B8-87B0-1E4B771C9E2C");
        $rs = $pdo->query("select count(code) as fireManCount from (select code,count(*) from u_smart_monitoring where batch_code=(select max(batch_code) from u_smart_monitoring) group by code)  aaa");
        $result = $rs->fetchAll(\PDO::FETCH_ASSOC);
//        print_r($result);
        $result = $result[0]['fireManCount'];
//        $result=json_encode($result);
//        print_r($result);

        $pdo = null;//关闭连接
        return $result;
    }


    /**
     * @param $user  消防员
     * @return array|string
     * User: wangjian
     * Date: 2017-11-29
     * Function：根据消防员的 姓名 查询得到其最大监测批次时 最新的一条数据信息 （根据最新的日期）
     */
    public function getFireManMessageByUser($user)
    {
        $pdo = new \PDO("mysql:host=119.29.189.235;dbname=ifp", "remote", "87BFE21D-5E20-A3B8-87B0-1E4B771C9E2C");
        $rs = $pdo->query("select * from u_smart_monitoring where batch_code=(select max(batch_code) from u_smart_monitoring) and user='".$user."' order by operate_date desc  limit 1");
        $result = $rs->fetchAll(\PDO::FETCH_ASSOC);
//        print_r($result);
        $result=json_encode($result[0]);
//        print_r($result);

        $pdo = null;//关闭连接
        return $result;
    }


    /**
     * @return string json
     * User: wangjian
     * Date: 2017-11-29
     * Function：得到所有在作战消防员的具体信息  这种方法对数据进行了整合 延迟比较高
     */
    public function getAllFireManMessage()
    {
        //1.获取最大监测批次的消防员列表
        $fireManList=$this->getAllFireManList();
        $fireManList=json_decode($fireManList);
//        $fireManArray=array();
        $fireManJson='';
//        print_r($fireManList);
        foreach($fireManList as $list)
        {
            $fireMan=$list->user;

            $fireManJsonMess=$this->getFireManMessageByUser($fireMan);
            $fireManJson=$fireManJson.$fireManJsonMess.",";

//            print_r($fireMan."<br>");
        }

        $fireManJson=rtrim($fireManJson, ",");
        $fireManJson="[".$fireManJson."]";

//        $fireManJson=json_decode($fireManJson);

//        print_r($fireManJson);

        return $fireManJson;

    }


    /**
     * @param Request $request
     * @return array
     * User: wangjian
     * Date: 2017-12-20
     * Function：通过发送的ajax请求，将指挥员发送的命令 存储到数据库中
     */
    public function sendCommanderOrder($commander_order,$code)
    {
        $order=$commander_order;
        $code=$code;
        $status=0;
        $message="";

//        $UserMonitor=new UserSmartMonitorModel();

        $USmartMonitoring=UserSmartMonitorModel::get(['code'=>$code]);
//        $user=$USmartMonitoring->user;

        $USmartMonitoring->commander_order=$commander_order;
        if(false!==$USmartMonitoring->save())
        {
            $status=1;
            $message="存储成功";

        }
        else{
            $message=$USmartMonitoring->getError();
        }



        return ['status'=>$status,'message'=>$message,'commander_order'=>$commander_order];
    }



    public function websocket()
    {
        return $this->fetch();
    }

    public function showPointInfo1()
    {
        return $this->fetch();
    }


    public function showModel1()
    {

        return $this->fetch();
    }


    /**
     * @return mixed
     * User: wangjian
     * Date: 2018-1-23
     * Function：基于HTML 显示楼层的作战安全模块
     */
    public function ecopHtml()
    {
        return $this->fetch();
    }


    /**
     * @return mixed
     * User: wangjian
     * Date: 2018-1-23
     * Function：基于HTML 显示楼层的 具体模块
     */
    public function ecopHtmlModel()
    {

        return $this->fetch();
    }


    public function getAllFireManInfoLouceng()
    {
        $list=UserSmartMonitorModel::all();
//        dump($list);
        return json_encode($list);

    }

}
