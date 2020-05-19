<?php

namespace app\api\logic;


use app\common\helper\CommonHelper;
use app\common\helper\LogHelper;
use app\common\helper\OssHelper;
use app\common\helper\QueueHelper;
use OSS\Core\OssException;
use think\facade\Db;

class ZhengfxLogic extends CommonLogic
{
    private $version = '1.0';
    private $signMethod = 'md5';
    private $platform = 'STO';
    private $format = 'json';
    private $appKey = 'test';
    private $qhdm = '';
    private $token = '';
    private $productCode = '';
    private $warehouseCode = '';
    private $async = null;
    private $url;
    private $logNumber = '';
    public $company;

    const ACTION = [
        'zhengfx_order_create' => 'api.postorder.create',//换号下单
        'zhengfx_get_label' => 'api.postorder.getLabel',//获取面单
        'zhengfx_order_confirm' => 'api.postorder.confirm',//确认发货
        'zhengfx_get_manifest' => 'api.postorder.getManifest',//获取发货单
        'zhengfx_order_cancel' => 'api.postorder.cancel',//取消订单接口
    ];

    public function __construct($param=[])
    {
        $this->url = rtrim(env('zhengfx_exchange_number.domain'),'/').'/postkeeper/api/service';
        $this->setAttributes($param);
    }

    /**
     * 获取预报数据
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCreateData($param){
        $data = [];
        if (isset($param['order_info'])) {
            $tempData = [
                'reference_number' => $param['order_info']['reference_number'],
                'order_number' => $param['order_info']['orderNo'],
                'weight' => $param['order_info']['orderWeight'],
                'weight_unit' => isset($param['order_info']['weight_unit']) ? $param['order_info']['weight_unit'] : '',
                'length' => isset($param['order_info']['length']) ? $param['order_info']['length'] : '',
                'width' => isset($param['order_info']['width']) ? $param['order_info']['width'] : '',
                'height' => isset($param['order_info']['height']) ? $param['order_info']['height'] : '',
            ];
            $tempData['company_id'] = $this->company['id'];

            $receiverData['receiver_name'] = $param['consignee']['name'];
            $receiverData['receiver_country'] = $param['consignee']['countryCode'];
            $receiverData['receiver_province'] = $param['consignee']['province'];
            $receiverData['receiver_city'] = $param['consignee']['city'];
            $receiverData['receiver_district'] = $param['consignee']['district'];
            $receiverData['receiver_address'] = $param['consignee']['address'];
            $receiverData['receiver_postcode'] = isset($param['consignee']['postcode']) ? $param['consignee']['postcode'] : '';
            $receiverData['receiver_mobile'] = isset($param['consignee']['mobile']) ? $param['consignee']['mobile'] : '';
            $receiverData['receiver_email'] = isset($param['consignee']['email']) ? $param['consignee']['email'] : '';
            $receiverData['receiver_tel'] = isset($param['consignee']['phone']) ? $param['consignee']['phone'] : '';
            $receiverData['house_no'] = isset($param['consignee']['house_no']) ? $param['consignee']['house_no'] : '';
            $goodsData = [];
            foreach ($param['items'] as $key => $value) {
                $goodsData[$key]['barcode'] = $value['barcode'];
                $goodsData[$key]['en_goods_name'] = $value['enName'];
                $goodsData[$key]['goods_name'] = $value['cnName'];
                $goodsData[$key]['qty'] = $value['qty'];
                $goodsData[$key]['goods_price'] = $value['unitPrice'];
                $goodsData[$key]['declare_currency'] = $value['currency'];
            }
        }

        $data['customerOrderNo'] = $tempData['order_number'];
        if($this->qhdm == 1){
            $data['customerOrderNo'] = $tempData['reference_number'];
        }
        $data['productCode'] = $this->productCode;//正方形对应产品
        $data['warehouseCode'] = $this->warehouseCode;
        $data['outWhTime'] = '';
        $data['buyerName'] = isset($receiverData['receiver_name']) ? $receiverData['receiver_name'] : '';
        $data['buyerPhone'] = isset($receiverData['receiver_mobile'])&&!empty($receiverData['receiver_mobile']) ? $receiverData['receiver_mobile'] : (isset($receiverData['receiver_tel']) ? $receiverData['receiver_tel'] : '');
        $data['buyerEmail'] = isset($receiverData['receiver_email']) ? $receiverData['receiver_email'] : '';
        $data['buyerCountry'] = isset($receiverData['receiver_country']) ? $receiverData['receiver_country'] : '';
        $data['buyerState'] = isset($receiverData['receiver_province']) ? $receiverData['receiver_province'] : '';
        $data['buyerCity'] = isset($receiverData['receiver_city']) ? $receiverData['receiver_city'] : '';
        $data['buyerHouseNo'] = isset($receiverData['house_no']) ? $receiverData['house_no'] : '';
        $district = isset($receiverData['receiver_district']) ? $receiverData['receiver_district'] : '';
        $data['buyerPostcode'] = isset($receiverData['receiver_postcode']) ? $receiverData['receiver_postcode'] : '';
        $data['buyerAddress1'] = isset($receiverData['receiver_address'])&&!empty($receiverData['receiver_address']) ? $district.' '.$receiverData['receiver_address'] : '';
        $data['buyerAddress2'] = isset($receiverData['receiver_address2'])&&!empty($receiverData['receiver_address2']) ? $district.' '.$receiverData['receiver_address2'] : '';

        $data['parcels'] = [];
        //目前只支持一票单件
        //查找入库材积表
        if(!empty($tempData['weight'])&&($tempData['weight']>0)) {
            $unitArray = ['G', 'KG', 'LB', 'OZ'];
            $tempData['weight_unit'] = strtoupper($tempData['weight_unit']);
            if (!in_array($tempData['weight_unit'], $unitArray)){
                return array(
                    'status' => false,
                    'message' => '重量单位不符合，请检查是否在[G, KG, LB, OZ]区间内。',
                );
            }

            if (strtoupper($tempData['weight_unit']) == 'G') {
                $tempData['weight'] = bcdiv(floatval($tempData['weight']),1000,3);
            }

            if($tempData['weight_unit'] == 'LB') {
                $rate = get_unit_rate('LB', 'KG');
                if (!$rate) {
                    return array(
                        'status' => false,
                        'message' => '重量单位不符合，请维护[LB->KG]的转换数据',
                    );
                }
                $tempData['weight'] = bcmul(floatval($tempData['weight']),floatval($rate),3);
            }

            if($tempData['weight_unit'] == 'OZ') {
                $rate = get_unit_rate('OZ', 'KG');
                if (!$rate) {
                    return array(
                        'status' => false,
                        'message' => '重量单位不符合，请维护[OZ->KG]的转换数据',
                    );
                }
                $tempData['weight'] = bcmul(floatval($tempData['weight']),floatval($rate),3);
            }
        }else{
            return array(
                'status' => false,
                'message' => '未获取到重量',
            );
        }
        $tempData['weight'] = (string)round($tempData['weight'],2);
        if($tempData['weight'] <= 0){
            $tempData['weight'] = '0.01';
        }
        $tempData['volume'] = 0;
        if(!empty($tempData['length'])&&($tempData['length']>0)&&!empty($tempData['width'])&&($tempData['width']>0)&&!empty($tempData['height'])&&($tempData['height']>0)){
            $tempData['volume'] = bcmul($tempData['length'],$tempData['width'],6);
            $tempData['volume'] = bcmul($tempData['volume'],$tempData['height'],6);
            $tempData['volume'] = bcdiv($tempData['volume'],1000000,6);
        }else{
            $tempData['length'] = '10';
            $tempData['width'] = '10';
            $tempData['height'] = '6';
            $tempData['volume'] = '0.0006';
        }

        $parcels = [
            "parcelNo" => $tempData['reference_number'],//包裹编号
            "parcelDesc" => "",//包裹描述
            "length" => $tempData['length'],//单位cm
            "width" => $tempData['width'],//单位cm
            "height" => $tempData['height'],//单位cm
            "weight" => $tempData['weight'],//单位KG
            "volume" => $tempData['volume'],//单位CBM
        ];
        $parcelItems = [];
        $rate = [];
        $target_currency = 'USD';
        if(!empty($goodsData)) {
            foreach ($goodsData as $k => $goods) {
                $key = "{$goods['declare_currency']}-{$target_currency}";
                if(!isset($rate[$key])){
                    $rate_result = $this->formateCurrencyRate($goods['declare_currency'],$target_currency,$tempData['company_id'],1);
                    if(false == $rate_result){
                        return array(
                            'status' => false,
                            'message' => "币种[{$key}]换算失败",
                        );
                    }
                    $rate[$key] = $rate_result;
                }
                $declaredValue = bcmul(floatval($goods['goods_price']),floatval($rate[$key]),3);
                $declaredValue = (string)round($declaredValue,2);
                $goods_weight = '0';
                if($k == 0){//只传第一个商品重量，包裹重量的95%
                    $goods_weight = bcmul($tempData['weight'],'0.95',3);
                    $goods_weight = (string)round($goods_weight,2);
                    if($goods_weight <= 0){
                        $goods_weight = '0.01';
                    }
                }
                $item = [
                    "itemCode" => isset($goods['barcode'])&&!empty($goods['barcode']) ? $goods['barcode'] : sprintf("%03d", ($k+1)),
                    "itemName" => $goods['goods_name'],
                    "saleCurrency" => $target_currency,
                    "salePrice" => $declaredValue,
                    "declaredCurrency" => $target_currency,
                    "declaredNameCn" => $goods['goods_name'],
                    "declaredNameEn" => $goods['en_goods_name'],
                    "declaredValue" => $declaredValue,
                    "length" => '10',
                    "width" => '10',
                    "height" => '6',
                    "volume" => '0.0006',
                    "weight" => $goods_weight,
                    "qty" => $goods['qty'],
                ];
                $parcelItems[] = $item;
            }
        }
        $parcels['parcelItems'] = $parcelItems;
        $data['parcels'][] = $parcels;
        return array(
            'status' => true,
            'message' => '获取成功',
            'data' => $data
        );
    }

    /**
     * 异步预报
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function asyncExchangeNumber($param)
    {
        $logic = new CommonHelper();
        $order = Db::table('oms_order')->where(['order_number'=>$param['order_info']['orderNo'],'is_delete_flag'=>'1'])->find();
        if(empty($order)){
            return array(
                'status' =>false,
                'message' => '取号失败,原因：oms系统未找到订单信息',
            );
        }

        $result = $this->getCreateData($param);
        if(!$result['status']){
            $logic->create_abnormal($order['id'], 'TNF', "取号失败,".$result['message']);
            return $result;
        }
        $data = $result['data'];
        $timestamp = date('Y-m-d H:i:s');
        $this->logNumber = isset($param['order_info']['reference_number']) ? $param['order_info']['reference_number'] : '';
        $result = $this->_httpPostRequest($this->url,$this->token,'api.postorder.create',$this->appKey,$data,$this->format,$this->platform,$this->signMethod,$timestamp,$this->version);
        if($result['status'] == false){
            $msg = '取号失败,正方形渠道商返回原因：' . (isset($result['message']) ? $result['message'] : '');
            $logic->create_abnormal($order['id'], 'TNF', $msg);
            return array(
                'status' =>false,
                'message' => $msg,
            );
        }
        if(!isset($result['data']['orderNo']) || empty($result['data']['orderNo'])){
            $logic->create_abnormal($order['id'], 'TNF', '取号失败,原因：未获取到单号');
            return array(
                'status' =>false,
                'message' => '取号失败,原因：正方形渠道商未返回单号',
            );
        }
        //写入task任务表
        $channel_code = isset($param['order_info']['channel_code']) ? $param['order_info']['channel_code'] : '';
        $company_code = isset($param['order_info']['company_code']) ? $param['order_info']['company_code'] : '';
        $ext_param = [
            'class' => 'app\api\logic\ZhengfxLogic',
            'method' => 'getLabel',
            'param' => ['channel_code'=>$channel_code,'company_code'=>$company_code,'reference_number'=>$this->logNumber],
            'data' => [
                'orderNo' => $result['data']['orderNo']
            ]
        ];

        $task_data = [
            'order_id' => $order['id'],
            'type' => 'GET_ORDER_LABEL',
            'create_date' => date('Y-m-d H:i:s'),
            'status' => 'W',
            'ext_param' => json_encode($ext_param),
            'push_count' => 0,
            'exec_log' => '',
        ];
        $task_id = Db::table('oms_order_task')->insertGetId($task_data);
        if(!QueueHelper::pushDelayQueue('120','app\job\GetChannelLabel', 'async_get_label:common_queue',
            [
                'id' => $task_id,
                'order_number'=> $this->logNumber
            ]
        )){
            return array(
                'status' =>false,
                'message' => '入异步面单队列失败',
            );
        }
        return [
            'status' => true,
            'message' => '成功'
        ];
    }

    /**
     * 获取面单
     * @param $param
     * @return array
     * @throws \OSS\Core\OssException
     */
    public function getLabel($param)
    {
        $data = [];
        if (!isset($param['orderNo']) || empty($param['orderNo'])) {
            return array(
                'status' => false,
                'message' => '获取面单失败,原因：未获取到跟踪号',
            );
        }
        $data['orderNo'] = $param['orderNo'];
        $timestamp = date('Y-m-d H:i:s');
        $this->logNumber = isset($data['orderNo']) ? $data['orderNo'] : 'zhengfx_get_label';
        $result = $this->_httpPostRequest($this->url, $this->token, 'api.postorder.getLabel', $this->appKey, $data, $this->format, $this->platform, $this->signMethod, $timestamp, $this->version);
        if (!isset($result['status']) || ($result['status'] == false)) {
            return array(
                'status' => false,
                'message' => '获取面单失败,正方形渠道商返回原因：' . (isset($result['message']) ? $result['message'] : '未知'),
            );
        }
        //下载面单地址
        $label_base64 = $result['data']['labelList'][0]['label'];
        try {
            $res = OssHelper::uploadFileContent(base64_decode($label_base64), '', 'pdf','order_channel_label');
            if ($res['result'] === 0) {
                return array(
                    'status' => false,
                    'message' => '面单上传OSS失败',
                );
            }

            return [
                'status' => true,
                'message' => '获取成功',
                'data' => [
                    'trackingNo' => $result['data']['labelList'][0]['trackingNo'],
                    'labelUrl' => $res['url']
                ]
            ];
        }catch (OssException $e){
            return array(
                'status' => false,
                'message' => '面单上传OSS失败 '.$e->getMessage(),
            );
        }
    }

    /**
     * 取消订单
     * @param $order_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelOrder($order_id){
        $order_task = Db::table('oms_order_task')->where(['order_id'=>$order_id,'type'=>'GET_ORDER_LABEL'])->find();
        if(empty($order_task)){
            return array(
                'status' =>false,
                'message' => '未获取到需要取消的订单信息',
            );
        }
        $task_data = json_decode($order_task,true);
        $data = [
            'orderNo' => $task_data['data']['orderNo']
        ];
        $timestamp = date('Y-m-d H:i:s');
        $this->logNumber = $task_data['data']['orderNo'];
        $result = $this->_httpPostRequest($this->url,$this->token,'api.postorder.cancel',$this->appKey,$data,$this->format,$this->platform,$this->signMethod,$timestamp,$this->version);
        if($result['status'] == false){
            return array(
                'status' =>false,
                'message' => '取消失败',
            );
        }
        return array(
            'status' =>true,
            'message' => '取消成功',
        );
    }

    public function _httpPostRequest($url,$token,$action,$app_key,$data,$format,$platform,$sign_method,$timestamp,$version)
    {
        $ret = array(
            'status' => false,
            'message' => '',
            'data' => ''
        );

        $data = self::sortByKey($data);
        $sign = $this->createSign($token,$action,$app_key,json_encode($data,320),$format,$platform,$sign_method,$timestamp,$version);

        $post_data = [
            'action' => $action,
            'appKey' => $app_key,
            'format' => $format,
            'language' => "zh_CN",
            'platform' => $platform,
            'signMethod' => $sign_method,
            'sign' => $sign,
            'timestamp' => $timestamp,
            'version' => $version,
            'data' => $data
        ];

        $post_data = json_encode($post_data, 320);
        $curl = curl_init();
        $header = array(
            'Content-Type:application/json;charset=utf-8',
            'version:1.0',
            'Expect:'
        );
        curl_setopt($curl,CURLOPT_URL, $url);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $api_content['header'] = $header;
        $api_content['body'] = $post_data;
        $api_content['url'] = $url;
        $log_id = LogHelper::addApiLog(array_search($action,self::ACTION), $this->logNumber, $post_data);
        $result = curl_exec($curl);
        curl_close($curl);
        $return_data = json_decode($result, true);
        if (!$return_data){
            $message = '调取接口失败';
            LogHelper::updateApiLog($log_id, $result, 0);
            return array(
                'status' =>false,
                'message' => $message,
            );
        }
        if (!isset($return_data['code']) || $return_data['code'] != 0){
            $message = isset($return_data['msg'])&&!empty($return_data['msg']) ? $return_data['msg'] : '未知';
            LogHelper::updateApiLog($log_id, $result, 0);
            return array(
                'status' =>false,
                'message' => $message,
            );
        }

        $ret['status'] = true;
        $ret['message'] = '参考号：' . $this->logNumber . ';调取接口成功';
        $ret['data'] = $return_data['data'];
        LogHelper::updateApiLog($log_id, $result, 1);
        return $ret;
    }

    /**
     * 生成签名
     * @param $token
     * @param $action
     * @param $app_key
     * @param $data
     * @param $format
     * @param $platform
     * @param $sign_method
     * @param $timestamp
     * @param $version
     * @return string
     */
    public function createSign($token,$action,$app_key,$data,$format,$platform,$sign_method,$timestamp,$version){
        $sign_str = "{$token}action{$action}appKey{$app_key}data{$data}format{$format}platform{$platform}signMethod{$sign_method}timestamp{$timestamp}version{$version}{$token}";
        $sign = md5($sign_str);
        return strtoupper($sign);
    }

    /**
     * 排序
     * @param $param
     * @return mixed
     */
    public static function sortByKey($param){
        foreach ($param as $key=>$value){
            if(is_array($value)){
                $param[$key] = self::sortByKey($value);
            }
        }
        ksort($param);
        return $param;
    }

    /**
     * 设置属性
     * @param $attributes
     */
    public function setAttributes($attributes){
        if(is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }
}