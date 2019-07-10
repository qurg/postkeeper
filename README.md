# 账号管家API对接文档
对接流程描述
1. 签约完成后，由客服提供账号和token.
2. 使用：HTTP协议，JSON数据格式。http请求头（header）设置：Content-Type: application/json 
3. 统一默认请求方法：POST。


生成签名
1. 拼接签名串
按字段名的字母顺序拼接（包括data中的json字符串也按此规则），将token放在字符串的两端，如下所示:
签名串 = token + action + actionValue + appKey + appKeyValue + data + dataValue + format + formatValue + platform + platformValue + signMethod + signMethodValue + timestamp + timestampValue + version + versionValue + token

*特别说明*：生成签名时需要注意一下几点：
签名时参数必须按字母顺序从小到大排序（字典序）；
* 如果参数的值为空不参与签名，如JAVA中参数值为null的，不会参与签名（"" 与 '' 认      为非空，需要参与签名）；
* 参数名区分大小写；
* 在向WINIT发送HTTP请求时必须使用正确的编码格式（默认UTF-8）

示例

签名串 =

3956C49B4525EAF246B640C9A6F3CE6BactioncreateOutboundOrderappKey1378353828@qq.comdata{"address1":"1 Stafford Crt.","address2":"aaaaaaa","city":"Bayswater North",
"deliveryWayID":1000020,"eBayOrderID":"3298472983749823480","emailAddress":"abc@winit.com","insuranceTypeID":1000010,"phoneNum":"15900001111","productList":[{"eBayBuyerID":"PowerBuyerDEF456","eBayItemID":"34BayItemI34245","eBaySellerID":"PowerSellerABC123","eBayTransactionID":"2433ctionI234","productCode":"EA0000201","productNum":"1","specification":""}],"recipientName":"mingbao","region":"Victoria","repeatable":"N","sellerOrderNo":"PowerSellerABC123","state":"AU","warehouseID":1000001,"zipCode":"3153"}formatjsonplatformsignMethodmd5timestampversion1.03956C49B4525EAF246B640C9A6F3CE6B

2.生成签名
对以上拼接后的签名串进行MD5运算，并转换成大写的32位签名。
签名 =  toUpperCase(MD5(签名串))
上述示例生成的签名为：3E3D21E8BB9D39BF84B159B88D3BB11F

备注：以下所有接口中的sign字段按照此方法生成。


请求说明

正式环境URL	http://postkeeper.zhengfx.com/postkeeper/api/service
验证方式	Token,md5
格式	json
字符编码	UTF-8
请求方式	http



一、创建订单接口（api.postorder.create）
接口说明
通过本接口，用户可以创建订单



请求参数：

参数|类型|是否必填|说明|举例
----|----|----|----|----
action|string|是|接口动作|默认为：api.postorder.create
appKey|string|是|用户名	



format	string	是	格式	默认为：json
language	string	是	语言	默认zh_CN
platform	string	是	平台	
signMethod	string	是	加密方式	默认为：md5
sign	string	是	签名	
timestamp	string	是	时间戳	默认为当前时间；格式:年-月-日 时:分:秒
version	string	是	版本号	默认为：1.0
customerOrderNo	string	是	客户订单号	
productCode	string	是	产品编码	
warehouseCode	string	是	仓库编码	
buyerName	string	是	买方姓名	
buyerPhone	string	是	买方手机号码	
buyerEmail	string	是	买方邮箱	
buyerCountry	string	是	买方国家	
buyerState	string	是	买方所在州	
buyerCity	string	是	买方所在城市	
buyerHouseNo	string	否	买方房屋编号	
buyerPostcode	string	是	买方邮政编码	
buyerAddress1	string	是	买方地址1	
buyerAddress2	string	是	买方地址2	
parcelNo	string	是	包裹编号	
parcelDesc	string	是	包裹描述	
length	double	是	包裹长度	
width	double	是	包裹宽度	
height	double	是	包裹高度	
weight	double	是	包裹重量	
volume	double	是	包裹体积	
itemCode	string	是	商品编码	
itemName	string	是	商品名称	
saleCurrency	string	是	销售币种	
salePrice	double	是	销售单价	
declaredCurrency	string	是	报关币种	
declaredNameCn	string	是	报关中文名称	
declaredNameEn	string	是	报关英文名称	
declaredValue	double	是	申报价值	
length	double	是	商品长度	
width	double	是	商品宽度	
height	double	是	商品高度	
volume	double	是	商品体积	
weight	double	是	商品重量	
qty	double	是	商品数量	

参数示列：
{
"action": "api.postorder.create",
"appKey": "",
"format": "json",
"language": "zh_CN",
"platform": "FS",
"signMethod": "md5",
"sign": "B2E8A93EFC1D8E39467415A017CC1ADA",
"timestamp": "2019-06-24 13:59:46",
"version": "1.0",
"data": {
"customerOrderNo": "Test0000013",
"productCode": "P2019052600000001",
"warehouseCode": "ZFX_US_LA",
"buyerName": "test",
"buyerPhone": "0231234111",
"buyerEmail": "test@test.com",
"buyerCountry": "US",
"buyerState": "TX",
"buyerCity": "Humble",
"buyerHouseNo": "",
"buyerPostcode": "77346",
"buyerAddress1": "address1",
"buyerAddress2": "address2",
"parcels": [
{
"parcelNo": "010",
"parcelDesc": "pacel 004",
"length": 10,
"width": 8,
"height": 2,
"weight": 114,
"volume": 0.0001,
"parcelItems": [
{
"itemCode": "TT0001",
"itemName": "test item 0001",
"saleCurrency": "USD",
"salePrice": 12,
"declaredCurrency": "USD",
"declaredNameCn": "测试商品",
"declaredNameEn": "test item",
"declaredValue": 10,
"length": 10,
"width": 10,
"height": 10,
"volume": 0.0001,
"weight": 400,
"qty": 1
}]}]}}

响应参数：
参数	类型	是否必填	说明	举例
code
	string	是	返回码	0:代码成功
msg	string	是	返回消息	
orderNo	string	是	生成订单号	

参数示列：
{
  "code": "0",
  "msg": "操作成功",
  "data": {
    "orderNo": "PO2019062400000026"
  }
}

二、取消订单接口（api.postorder.cancel）
接口说明
通过本接口，用户可以取消订单

请求参数：

参数	类型	是否必填	说明	举例
action	string	是	接口动作	默认为：api.postorder. cancel
appKey	string	是	用户名	
format	string	是	格式	默认为：json
language	string	是	语言	默认zh_CN
platform	string	是	平台	
signMethod	string	是	加密方式	默认为：md5
sign	string	是	签名	
timestamp	string	是	时间戳	默认为当前时间；格式:年-月-日 时:分:秒
version	string	是	版本号	默认为：1.0
orderNo	string	是	订单号	
参数示列：
{
    "action": "api.postorder.cancel",
  "appKey": "XXX",
  "format": "json",
  "language": "zh_CN",
  "platform": "ECPP",
  "sighMethod": "md5",
  "sign": "string",
  "timestamp": "2019-05-16 13:07:46",
  "version": "1.0",
  "data": {
     "orderNo":"PO2019062100000019"
  }
}
响应参数：
参数	类型	是否必填	说明	举例
code
	string	是	返回码	0:代码成功
msg	string	是	返回消息	
data	string	是	返回数据	

参数示列：
{
  "code": "01040007",
  "msg": "订单已经获取面单，无法作废！订单号：PO2019062100000019",
  "data": ""
}


三、打印面单接口（api.postorder.print）
接口说明
通过本接口，用户可以打印面单

请求参数：

参数	类型	是否必填	说明	举例
action	string	是	接口动作	默认为：api.postorder. print
appKey	string	是	用户名	
format	string	是	格式	默认为：json
language	string	是	语言	默认zh_CN
platform	string	是	平台	默认为：
signMethod	string	是	加密方式	默认为：md5
sign	string	是	签名	
timestamp	string	是	时间戳	默认为当前时间；格式:年-月-日 时:分:秒
version	string	是	版本号	默认为：1.0
orderNo	string	是	订单号	
参数示列：
{
  "action": "api.postorder.print",
  "appKey": "XXX",
  "format": "json",
  "language": "zh_CN",
  "platform": "ECPP",
  "signMethod": "md5",
  "sign": "string",
  "timestamp": "2019-05-16 13:07:46",
  "version": "1.0",
  "data": {
    "orderNo": "PO2019062100000020"
  }
}
响应参数：
参数	类型	是否必填	说明	举例
code
	string	是	返回码	0:代码成功
msg	string	是	返回消息	
trackingNo	string	是	运单轨迹	
label	string	是	标签	

参数示列：
{"code":"0","msg":"","data":{"labelList":[{"trackingNo":"9400110200882066932722","label":"iVBORw0KGgoAAAANSUhEUgAABLAAAAcICAIAAACjMq8dAAAAAXNSR0IArs4c6QAAAARnQU1BAACx\njwv8“}]}}


四、确认发货接口（api.postorder.confirm）
接口说明
通过本接口，用户可以打印面单

请求参数：

参数	类型	是否必填	说明	举例
action	string	是	接口动作	默认为：api.postorder. confirm
appKey	string	是	用户名	
format	string	是	格式	默认为：json
language	string	是	语言	默认zh_CN
platform	string	是	平台	默认为：
signMethod	string	是	加密方式	默认为：md5
sign	string	是	签名	
timestamp	string	是	时间戳	默认为当前时间；格式:年-月-日 时:分:秒
version	string	是	版本号	默认为：1.0
orderNos	string	是	订单号	确认发货的订单号
参数示列：
{
  "action": "api.postorder.confirm",
  "appKey": "XXX",
  "format": "json",
  "language": "zh_CN",
  "platform": "FS",
  "signMethod": "md5",
  "sign": "string",
  "timestamp": "2019-05-16 13:07:46",
  "version": "1.0",
  "data": {
"customerDeliveryNo": "12341",
"deliveryTime": "2019-07-16 13:07:46",
"orderNos":["PO2019062100000019","PO2019062100000020"]
  }
}
响应参数：
参数	类型	是否必填	说明	举例
code
	string	是	返回码	0:代码成功
msg	string	是	返回消息	
trackingNo	string	是	运单轨迹	
label	string	是	标签	

参数示列：
{"code":"0","msg":"","data":{"labelList":[{"trackingNo":"9400110200882066932722","label":"iVBORw0KGgoAAAANSUhEUgAABLAAAAcICAIAAACjMq8dAAAAAXNSR0IArs4c6QAAAARnQU1BAACx\njwv8“}]}}


五、获取发货单接口（api.postorder.getManifest）
接口说明
通过本接口，用户可以打印面单

请求参数：

参数	类型	是否必填	说明	举例
action	string	是	接口动作	默认为：api.postorder. getManifest
appKey	string	是	用户名	
format	string	是	格式	默认为：json
language	string	是	语言	默认zh_CN
platform	string	是	平台	默认为：
signMethod	string	是	加密方式	默认为：md5
sign	string	是	签名	
timestamp	string	是	时间戳	默认为当前时间；格式:年-月-日 时:分:秒
version	string	是	版本号	默认为：1.0
orderNos	string	是	订单号	确认发货的订单号
参数示列：
{
  "action": "api.postorder.confirm",
  "appKey": "XXX",
  "format": "json",
  "language": "zh_CN",
  "platform": "FS",
  "signMethod": "md5",
  "sign": "string",
  "timestamp": "2019-05-16 13:07:46",
  "version": "1.0",
  "data": {
"manifestNo": "MF2019070700000002"
  }
}
响应参数：
参数	类型	是否必填	说明	举例
code
	string	是	返回码	0:代码成功
msg	string	是	返回消息	
trackingNo	string	是	运单轨迹	
label	string	是	标签	

参数示列：
{"code":"0","msg":"","data":{"labelList":[{"trackingNo":"9400110200882066932722","label":"iVBORw0KGgoAAAANSUhEUgAABLAAAAcICAIAAACjMq8dAAAAAXNSR0IArs4c6QAAAARnQU1BAACx\njwv8“}]}}

