<?php
/**
 * Created by PhpStorm.
 * User: sayho
 * Date: 2018. 8. 23.
 * Time: PM 1:45
 */
?>

<? include $_SERVER["DOCUMENT_ROOT"] . "/common/classes/AdminBase.php";?>
<?
/*
 * Web process
 * add by cho
 * */
if(!class_exists("Management")){
    class Management extends  AdminBase {
        function __construct($req)
        {
            parent::__construct($req);
        }

        function conditionalQuery($destination, $elem, $sql){
            if($elem != "" && $elem != null){
                $destination .= " " . $sql;
            }
            return $destination;
        }

        function appendByReq($dest, $idx, $sql){
            return $this->conditionalQuery($dest, $_REQUEST[$idx], $sql);
        }

        function customerDetailWhere(){
            $pMonth = intval($_REQUEST["pMonth"]);
            $eMonth = intval($_REQUEST["eMonth"]);

            $where = "WHERE 1=1";
            $where = $this->appendByReq($where, "email", "AND `email` LIKE '%{$_REQUEST["email"]}%'");
            $where = $this->appendByReq($where, "phone", "AND `phone` LIKE '%{$_REQUEST["phone"]}%'");
            $where = $this->appendByReq($where, "name", "AND `name` LIKE '%{$_REQUEST["name"]}%'");
            $where = $this->appendByReq($where, "code", "AND tblCustomer.`id` IN (SELECT (SELECT customerId FROM tblPayMethod WHERE tblPayMethod.`id`=`payMethodId`) AS cid FROM tblPayment WHERE `primeIndex` LIKE '%{$_REQUEST["code"]}%')");
            $where = $this->appendByReq($where, "sMethod", "AND tblCustomer.`id` IN (SELECT `customerId` FROM tblSubscription WHERE `subType` = '{$_REQUEST["sMethod"]}')");
            $where = $this->appendByReq($where, "sName", "AND tblCustomer.`id` IN (SELECT `customerId` FROM tblSupport WHERE `assemblyName` LIKE '%{$_REQUEST["sName"]}%')");
            $where = $this->appendByReq($where, "addr", "AND CONCAT(`addr`, ' ', `addrDetail`) LIKE '%{$_REQUEST["addr"]}%'");

            if($_REQUEST["pYear"] != "" && $_REQUEST["pMonth"] != "") $where .= " AND tblCustomer.`id` IN (SELECT `customerId` FROM tblSubscription WHERE `pYear`='{$_REQUEST["pYear"]}' AND `pMonth`='{$pMonth}')";
            if($_REQUEST["eYear"] != "" && $_REQUEST["eMonth"] != "") $where .= " AND tblCustomer.`id` IN (SELECT `customerId` FROM tblSubscription WHERE `eYear`='{$_REQUEST["eYear"]}' AND `eMonth`='{$eMonth}')";

            $where = $this->appendByReq($where, "shippingType", "AND tblCustomer.`id` IN (SELECT `customerId` FROM tblSubscription WHERE `shippingType` = '{$_REQUEST["shippingType"]}')");
            $where = $this->appendByReq($where, "version", "AND tblCustomer.`id` IN (SELECT `customerId` FROM tblSubscription WHERE `publicationId` = '{$_REQUEST["version"]}')");
            $where = $this->appendByReq($where, "status", "AND (tblCustomer.`id` IN (SELECT `customerId` FROM tblSubscription WHERE `deliveryStatus` = '{$_REQUEST["status"]}')
                                                                          OR
                                                                            tblCustomer.`id` IN (SELECT `customerId` FROM tblSupport WHERE `status` = '{$_REQUEST["status"]}')
                                                                            )");
            $where = $this->appendByReq($where, "aDate", "AND 
            tblCustomer.`id` IN (SELECT customerId FROM tblSubscription WHERE DATE(regDate) = DATE('{$_REQUEST["aDate"]}') UNION ALL
                                SELECT customerId FROM tblSupport WHERE DATE(regDate) = DATE('{$_REQUEST["aDate"]}'))
            ");

            return $where;
        }

        function customerListDetail(){
            $orderBy = "ORDER BY `regDate` DESC";
            $where = $this->customerDetailWhere();

            $sql = "SELECT * FROM tblCustomer {$where} {$orderBy}";

            return $this->getArray($sql);
        }

        function customerDetailIDs(){
            $orderBy = "ORDER BY `regDate` DESC";
            $where = $this->customerDetailWhere();

            $sql = "SELECT `id` FROM tblCustomer {$where} {$orderBy}";

            return $this->getArray($sql);
        }

        function getCustomerDetailExcelList(){
            $arr = $this->customerDetailIDs();
            $imp = "";

            for($e = 0; $e < sizeof($arr); $e++){
                $imp .= "'";
                $imp .= $arr[$e]["id"];
                $imp .= "'";
                if($e + 1 < sizeof($arr)) $imp .= ",";
            }


            $where = "C.id IN ({$imp})";

            $sql = "
                SELECT 
                  *,
                  C.type as customerType,
                  PM.type as paymentType,
                  (SELECT `desc` FROM tblCardType WHERE `id` = cardTypeId) as cardDesc,
                  (SELECT `desc` FROM tblBankType WHERE `code` = bankCode) as bankDesc
                FROM(
                	SELECT
                		'-1' as supportId,
                		id as subscriptionId, 
                		customerId,
                		publicationId,
                		cnt,
                		pYear,
                		pMonth,
                		'' as sYear,
                		'' as sMonth,
                		eYear,
                		eMonth,
                		`type` as productType,
                		totalprice,
                		subType,
                		'' as supType,
                		shippingType,
                		rName,
                		'' as rEmail,
                		rPhone,
                		rZipCode,
                		rAddr,
                		rAddrDetail,
                		paymentId,
                		'' as assemblyName,
       					deliveryStatus,
       					regDate
                	FROM tblSubscription SUB
                	UNION ALL
                	SELECT
                		id as supportId,
                		'-1' as subscriptionId, 
                		customerId,
                		'-1' as publicationId,
                		'' as cnt,
                		'' as pYear,
                		'' as pMonth,
                		sYear,
                		sMonth,
                		eYear,
                		eMonth,
                		`type` as productType,
                		totalprice,
                		'' as subType,
                		supType,
                		'-1' as shippingType,
                		rName,
                		rEmail,
                		rPhone,
                		'' as rZipCode,
                		'' as rAddr,
                		'' as rAddrDetail,
                		paymentId,
                		assemblyName,
       					'-1' as deliveryStatus,
       					regDate
                	FROM tblSupport SUP
                ) tmp JOIN tblCustomer C ON C.id = tmp.`customerId` 
                LEFT JOIN tblPayment P ON P.id = tmp.`paymentId` 
                LEFT JOIN tblPayMethod PM ON PM.id = P.`payMethodId`
                LEFT JOIN tblPublication PUB ON PUB.id = tmp.publicationId
                WHERE {$where}
                ORDER BY tmp.regDate DESC;
            ";

            return $this->getArray($sql);
        }

        function customerList(){
            $orderBy = $_REQUEST["orderBy"];

            if($orderBy == "") $orderBy = "regDate";
            $orderByStmt = " ORDER BY {$orderBy} DESC";

            $searchType = $_REQUEST["searchType"];
            $searchText = $_REQUEST["searchText"];
            $where = "1=1";
            if($searchType == "name"){
                $where .= " AND `name` LIKE '%{$searchText}%'";
            }else if($searchType == "BO"){
                //TODO bank owner search
            }else if($searchType == "phone"){
                $where .= " AND `phone` LIKE '%{$searchText}%'";
            }else if($searchType == "email"){
                $where .= " AND `email` LIKE '%{$searchText}%'";
            }else if($searchType == "addr"){
                $where .= " AND (`addr` LIKE '%{$searchText}%' OR `addrDetail` LIKE '%{$searchText}%')";
            }

            $this->initPage();
            $sql = "
              SELECT COUNT(*) cnt 
              FROM tblCustomer 
              WHERE `status` = 1 AND {$where}
            ";
            $this->rownum = $this->getValue($sql, "cnt");
            $this->setPage($this->rownum);

            $sql = "
                SELECT *
                FROM tblCustomer
                WHERE `status` = 1 AND {$where} {$orderByStmt}
                LIMIT {$this->startNum}, {$this->endNum};
            ";
            return $this->getArray($sql);
        }

        function customerInfo(){
            $id = $_REQUEST["id"];
            if($id == "") $id = $_REQUEST["customerId"];

            $sql = "SELECT * FROM tblCustomer WHERE `id` = '{$id}' LIMIT 1";
            $userInfo = $this->getRow($sql);

            //TODO 결제 정보
            $sql = "
                SELECT 
                  *,
                  (SELECT `desc` FROM tblCardType WHERE id = cardTypeId) as cardTypeDesc,
                  (SELECT `desc` FROM tblBankType WHERE code = bankCode) as bankTypeDesc
                FROM(
                    SELECT PM1.id, cardTypeId, bankCode, validThruYear, validThruMonth, ownerName, monthlyDate, PM1.type AS pmType, info, totalPrice, PM1.regDate, P1.regDate AS paymentDate, 'SUB' AS productType, primeJumin
                    FROM tblPayMethod PM1 JOIN tblPayment P1 ON PM1.`id` = P1.`payMethodId` JOIN tblSubscription SUB ON SUB.paymentId = P1.id
                    WHERE SUB.customerId = '{$id}'
                    UNION ALL
                    SELECT PM2.id, cardTypeId, bankCode, validThruYear, validThruMonth, ownerName, monthlyDate, PM2.type AS pmType, info, totalPrice, PM2.regDate, P2.regDate AS paymentDate, 'SUP' AS productType, primeJumin 
                    FROM tblPayMethod PM2 JOIN tblPayment P2 ON PM2.`id` = P2.`payMethodId` JOIN tblSupport SUP ON SUP.paymentId = P2.id
                    WHERE SUP.customerId = '{$id}'
                ) tmp
                ORDER BY regDate DESC
            ";
            $paymentInfo = $this->getArray($sql);

            $sql = "
                SELECT 
                  S.*, PM.info, PM.type as pmType, 
                  (SELECT `name` FROM tblPublicationLang PL WHERE PL.publicationId = publicationId AND langCode = '{$userInfo["langCode"]}' LIMIT 1) publicationName,
                  (SELECT COUNT(*) FROM tblShipping S WHERE S.subsciptionId = id) lostCnt,
                  P.paymentResult,
                  P.id as idx
                FROM tblSubscription S LEFT JOIN tblPayment P ON S.paymentId = P.id  LEFT JOIN tblPayMethod PM ON PM.id = P.payMethodId
                WHERE S.customerId = '{$id}' 
                ORDER BY S.regDate DESC
            ";
            $subscriptionInfo = $this->getArray($sql);

            $sql = "
                SELECT S.*, PM.info, PM.type as pmType, (SELECT `desc` FROM tblNationGroup WHERE `id` = (SELECT nationId FROM tblSupportParent WHERE `id` = S.parentId)) nation, P.paymentResult, P.id as idx
                FROM tblSupport S LEFT JOIN tblPayment P ON S.paymentId = P.id LEFT JOIN tblPayMethod PM ON PM.id = P.payMethodId
                WHERE S.customerId = '{$id}'
                ORDER BY S.regDate DESC
            ";
            $supportInfo = $this->getArray($sql);
            $retVal = Array(
                "userInfo" => $userInfo,
                "paymentInfo" =>$paymentInfo,
                "subscriptionInfo" => $subscriptionInfo,
                "supportInfo" => $supportInfo
            );
            return $retVal;
        }

        function setNotiFlag(){
            $id = $_REQUEST["id"];
            $flag = $_REQUEST["flag"];
            $sql = "UPDATE tblCustomer SET `notiFlag` = '{$flag}' WHERE `id` = '{$id}'";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function setCommercial(){
            $id = $_REQUEST["id"];
            $type = $_REQUEST["type"];
            $check = $_REQUEST["check"];

            $sql = "
                UPDATE tblCustomer SET `commercial{$type}` = '{$check}' WHERE `id` = {$id}
            ";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function updateSubscription(){
            $id = $_REQUEST["id"];
            $customerLang = $_REQUEST["customerLang"];
            $sql = "SELECT * FROM tblPublicationLang WHERE publicationId = '{$_REQUEST["publicationId"]}' AND langCode = '{$customerLang}' LIMIT 1";
            $publication = $this->getRow($sql);
            $totalPrice = -1;
            if($_REQUEST["cnt"] < 10)
                $totalPrice = $publication["price"] * $_REQUEST["cnt"];
            else
                $totalPrice = $publication["discounted"] * $_REQUEST["cnt"] + 3000;

            $sql = "SELECT rName, rPhone, rZipCode, rAddr, rAddrDetail, publicationId, cnt, subType, shippingType, pYear, pMonth, eYear, eMonth, deliveryStatus FROM tblSubscription WHERE id='{$id}' LIMIT 1";
            $old = $this->getRow($sql);
            $sql = "
                INSERT INTO tblSubscription(id, customerId, publicationId, cnt, pYear, pMonth, eYear, eMonth, totalPrice, subType, shippingType, rName, rPhone, rZipCode, rAddr, rAddrDetail, deliveryStatus, regDate) 
                VALUES(
                  '{$id}',
                  '{$_REQUEST["customerId"]}',
                  '{$_REQUEST["publicationId"]}',
                  '{$_REQUEST["cnt"]}',
                  '{$_REQUEST["pYear"]}',
                  '{$_REQUEST["pMonth"]}',
                  '{$_REQUEST["eYear"]}',
                  '{$_REQUEST["eMonth"]}',
                  '{$totalPrice}',
                  '{$_REQUEST["subType"]}',
                  '{$_REQUEST["shippingType"]}',
                  '{$_REQUEST["rName"]}',
                  '{$_REQUEST["rPhone"]}',
                  '{$_REQUEST["rZipCode"]}',
                  '{$_REQUEST["rAddr"]}',
                  '{$_REQUEST["rAddrDetail"]}',
                  '{$_REQUEST["deliveryStatus"]}',
                  NOW()
                )
                ON DUPLICATE KEY UPDATE
                publicationId = '{$_REQUEST["publicationId"]}',
                cnt = '{$_REQUEST["cnt"]}',
                pYear = '{$_REQUEST["pYear"]}',
                pMonth = '{$_REQUEST["pMonth"]}',
                eYear = '{$_REQUEST["eYear"]}',
                eMonth = '{$_REQUEST["eMonth"]}',
                totalPrice = '{$totalPrice}',
                subType = '{$_REQUEST["subType"]}',
                shippingType = '{$_REQUEST["shippingType"]}',
                rName = '{$_REQUEST["rName"]}',
                rPhone = '{$_REQUEST["rPhone"]}',
                rZipCode = '{$_REQUEST["rZipCode"]}',
                rAddr = '{$_REQUEST["rAddr"]}',
                rAddrDetail = '{$_REQUEST["rAddrDetail"]}',
                deliveryStatus = '{$_REQUEST["deliveryStatus"]}'
            ";
            $this->update($sql);

            foreach(json_decode(json_encode($old), true) as $key => $value){
                if($value != $_REQUEST[$key]){
                    $tmp = "";
                    $result = "";
                    if($key == "rName") $tmp = "받는사람 변경";
                    if($key == "rPhone") $tmp = "받는사람 변경";
                    switch($key){
                        case "rName":
                            $tmp = "받는사람";
                            $result = $_REQUEST[$key];
                            break;
                        case "rPhone":
                            $tmp = "전화번호";
                            $result = $_REQUEST[$key];
                            break;
                        case "rZipCode":
                            $tmp = "우편번호";
                            $result = $_REQUEST[$key];
                            break;
                        case "rAddr":
                            $tmp = "주소";
                            $result = $_REQUEST[$key];
                            break;
                        case "rAddrDetail":
                            $tmp = "상세주소";
                            $result = $_REQUEST[$key];
                            break;
                        case "publicationId":
                            $tmp = "버전";
                            $sql = "SELECT `desc` FROM tblPublication WHERE id = '{$_REQUEST[$key]}'";
                            $result = $this->getValue($sql, "desc");
                            break;
                        case "cnt":
                            $tmp = "부수";
                            $result = $_REQUEST[$key];
                            break;
                        case "subType":
                            $tmp = "유형";
                            if($_REQUEST[$key] == "0") $result = "개인";
                            else if($_REQUEST[$key] == "1") $result = "단체";
                            else if($_REQUEST[$key] == "2") $result = "묶음배송";
                            else if($_REQUEST[$key] == "3") $result = "표지광고";
                            break;
                        case "shippingType":
                            $tmp = "배송";
                            $shippingType = $_REQUEST[$key];
                            $result = $_REQUEST[$key] == 0 ? "우편" : "택배";
                            break;
                        case "pYear":
                            $tmp = "시작월호(년)";
                            $result = $_REQUEST[$key];
                            break;
                        case "pMonth":
                            $tmp = "시작월호(월)";
                            $result = $_REQUEST[$key];
                            break;
                        case "eYear":
                            $tmp = "끝나는월호(년)";
                            $result = $_REQUEST[$key];
                            break;
                        case "eMonth":
                            $tmp = "끝나는월호(월)";
                            $result = $_REQUEST[$key];
                            break;
                        case "deliveryStatus":
                            $tmp = "상태";
                            if($_REQUEST[$key] == "0") $result = "정상";
                            if($_REQUEST[$key] == "1") $result = "취소";
                            if($_REQUEST[$key] == "2") $result = "발송보류";
                            break;
                    }

                    $sql = "
                    INSERT INTO tblCustomerHistory(customerId, modifier,`type`, content, regDate)
                    VALUES(
                      '{$_REQUEST["customerId"]}',
                      '{$this->admUser->account}',
                      'sub',
                      '{$tmp} 변경: {$result}',
                      NOW()
                    )
                ";
                    $this->update($sql);
                }
            }
            return $this->makeResultJson(1, "succ");
        }

        function updateSupport(){
            $id = $_REQUEST["id"];
            $sql = "SELECT `status`, rName, supType, sYear, sMonth, eYear, eMonth, assemblyName, totalPrice FROM tblSupport WHERE id='{$id}' LIMIT 1";
            $old = $this->getRow($sql);

            $sql = "
                UPDATE tblSupport SET
                    supType = '{$_REQUEST["supType"]}',
                    totalPrice = '{$_REQUEST["totalPrice"]}',
                    rName = '{$_REQUEST["rName"]}',
                    sYear = '{$_REQUEST["sYear"]}',
                    sMonth = '{$_REQUEST["sMonth"]}',
                    eYear = '{$_REQUEST["eYear"]}',
                    eMonth = '{$_REQUEST["eMonth"]}',
                    status = '{$_REQUEST["status"]}',
                    assemblyName = '{$_REQUEST["assemblyName"]}'
                WHERE `id` = '{$id}' 
            ";
            $this->update($sql);

            foreach(json_decode(json_encode($old), true) as $key => $value){
                if($value != $_REQUEST[$key]){
                    $tmp = "";
                    $result = "";
                    if($key == "rName") $tmp = "받는사람 변경";
                    if($key == "rPhone") $tmp = "받는사람 변경";
                    switch($key){
                        case "status":
                            $tmp = "상태";
                            if($_REQUEST[$key] == "0") $result = "정상";
                            if($_REQUEST[$key] == "1") $result = "취소";
                            break;
                        case "rName":
                            $tmp = "후원자명";
                            $result = $_REQUEST[$key];
                            break;
                        case "supType":
                            $tmp = "후원유형";
                            if($_REQUEST[$key] == "BTG") $result = "BTG";
                            else if($_REQUEST[$key] == "BTF") $result = "BTF";
                            break;
                        case "sYear":
                            $tmp = "시작년월(년)";
                            $result = $_REQUEST[$key];
                            break;
                        case "sMonth":
                            $tmp = "시작년월(월)";
                            $result = $_REQUEST[$key];
                            break;
                        case "eYear":
                            $tmp = "끝나는년월(년)";
                            $result = $_REQUEST[$key];
                            break;
                        case "eMonth":
                            $tmp = "끝나는년월(월)";
                            $result = $_REQUEST[$key];
                            break;
                        case "assemblyName":
                            $tmp = "후원집회명";
                            $result = $_REQUEST[$key];
                            break;
                        case "totalPrice":
                            $tmp = "가격";
                            $result = $_REQUEST[$key];
                    }
                    $sql = "
                    INSERT INTO tblCustomerHistory(customerId, modifier,`type`, content, regDate)
                    VALUES(
                      '{$_REQUEST["customerId"]}',
                      '{$this->admUser->account}',
                      'sup',
                      '{$tmp} 변경: {$result}',
                      NOW()
                    )
                ";
                    $this->update($sql);
                }
            }
            return $this->makeResultJson(1, "succ");
        }

        function historyData(){
            $id = $_REQUEST["id"];
            $typeArr = $_REQUEST["typeArr"];
            $where = "1=1 AND customerId = '{$id}'";
            if($typeArr[0] != "all"){
                $where .= " AND(";
                foreach($typeArr as $item) {
                    if(!next($typeArr)) $where .= "`type` = '{$item}'";
                    else $where .= "`type` = '{$item}' OR";
                }
                $where .= ")";
            }
            $sql = "SELECT * FROM tblCustomerHistory WHERE {$where} ORDER BY id DESC";
            return $this->makeResultJson(1, succ, $this->getArray($sql));
        }

        function upsertCustomer(){
            $historyIdArr = $_REQUEST["historyId"];
            $historyTypeArr = $_REQUEST["hType"];
            $historyContentArr = $_REQUEST["historyContent"];
            $historyModifierArr = $_REQUEST["modifier"];

            for($i=0; $i<sizeof($historyIdArr); $i++){
                $tmpId = $historyIdArr[$i] == "" ? "0" : $historyIdArr[$i];
                $sql = "
                    INSERT INTO tblCustomerHistory(`id`, `customerId`, modifier, `type`, `content`, `regDate`)
                    VALUES(
                      '{$tmpId}',
                      '{$_REQUEST["id"]}',
                      '{$historyModifierArr[$i]}',
                      '{$historyTypeArr[$i]}',
                      '{$historyContentArr[$i]}',
                      NOW()
                    )
                    ON DUPLICATE KEY UPDATE
                    `type` = '{$historyTypeArr[$i]}',
                    `modifier` = '{$historyModifierArr[$i]}',
                    `content` = '{$historyContentArr[$i]}'
                ";
                $this->update($sql);
            }
            return $this->makeResultJson(1, "succ");
        }

        function addForeignPub(){
            $year = $_REQUEST["year"];
            $print = $_REQUEST["print"];
            $country = $_REQUEST["country"];
            $language = $_REQUEST["language"];
            $text = $_REQUEST["text"];

            $sql = "
              INSERT INTO tblForeignPub(`year`, `print`, `country`, `language`, `text`, regDate)
              VALUES(
                '{$year}',
                '{$print}',
                '{$country}',
                '{$language}',
                '{$text}',
                NOW()
              )
            ";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function foreignPubList(){
            $year = $_REQUEST["year"];
            $where = "1=1";
            if($year != "") $where .= " AND `year` = '{$year}'";
            $sql = "
                SELECT * FROM tblForeignPub WHERE {$where} ORDER BY regDate DESC
            ";
            $list = $this->getArray($sql);
            foreach($list as $i=>$item){
                $sql = "SELECT * FROM tblForeignPubItem WHERE `foreignPubId` = '{$item["id"]}' ORDER BY regDate ASC";
                $childList = $this->getArray($sql);
                $list[$i]["childList"] = $childList;
            }
            return $list;
        }

        function foreignPubInfo(){
            $id = $_REQUEST["parentId"];
            $sql = "SELECT * FROM tblForeignPub WHERE `id` = '{$id}' LIMIT 1";
            return $this->getRow($sql);
        }

        function foreignPubChild(){
            $id = $_REQUEST["id"];
            $sql = "
                SELECT * FROM tblForeignPubItem WHERE `id` = '{$id}' LIMIT 1
            ";
            return $this->getRow($sql);
        }

        function upsertForeignPubChild(){
            $fileArray = array();
            $parentId = $_REQUEST["parentId"];
            $id = $_REQUEST["id"] == "" ? 0 : $_REQUEST["id"];
            $printCharge = str_replace(",", "", $_REQUEST["printCharge"]);
            $deliveryCharge = str_replace(",", "", $_REQUEST["deliveryCharge"]);

            $dueDate1 = $_REQUEST["dueDate1"] == "" ? "NULL" : "'" . $_REQUEST["dueDate1"] . "'";
            $dueDate2 = $_REQUEST["dueDate2"] == "" ? "NULL" : "'" . $_REQUEST["dueDate2"] . "'";
            $dueDate3 = $_REQUEST["dueDate3"] == "" ? "NULL" : "'" . $_REQUEST["dueDate3"] . "'";
            $dueDate4 = $_REQUEST["dueDate4"] == "" ? "NULL" : "'" . $_REQUEST["dueDate4"] . "'";
            $dueDate5 = $_REQUEST["dueDate5"] == "" ? "NULL" : "'" . $_REQUEST["dueDate5"] . "'";

            for($i = 0; $i < 3; $i++) {
                $check = file_exists($_FILES['docFile'.($i+1)]['tmp_name']);
                $fileName = $_FILES["docFile".($i+1)]["name"];
                $filePath = $_REQUEST["filePath".($i+1)];

                if ($check !== false) {
                    $fName = $this->makeFileName() . "." . pathinfo(basename($_FILES["docFile".($i+1)]["name"]), PATHINFO_EXTENSION);
                    $targetDir = $this->filePath . $fName;
                    if (move_uploaded_file($_FILES["docFile".($i+1)]["tmp_name"], $targetDir)) $filePath = $fName;
                    else return $this->makeResultJson(-1, "fail");
                }else{
                    if($filePath != ""){
                        $fileName = $_REQUEST["fileName".($i+1)];
                    }else {
                        $fileName = "";
                        $filePath = "";
                    }
                }
                $fileArray[$i]["fileName"] = $fileName;
                $fileArray[$i]["filePath"] = $filePath;
            }

            $sql = "
                INSERT INTO tblForeignPubItem(`id`, `foreignPubId`, `nd`, `startMonth`, `endMonth`, `type`, `cnt`, `client`, `printCharge`, 
                `deliveryCharge`, `note`, `dueDate1`, `dueDate2`, `dueDate3`, `dueDate4`, `dueDate5`, `fileName1`, `filePath1`, `fileName2`, `filePath2`, 
                `fileName3`, `filePath3`, `regDate`)
                VALUES(
                  '{$id}',
                  '{$parentId}',
                  '{$_REQUEST["nd"]}',
                  '{$_REQUEST["startMonth"]}',
                  '{$_REQUEST["endMonth"]}',
                  '{$_REQUEST["type"]}',
                  '{$_REQUEST["cnt"]}',
                  '{$_REQUEST["client"]}',
                  '{$printCharge}',
                  '{$deliveryCharge}',
                  '{$_REQUEST["note"]}',
                  {$dueDate1},
                  {$dueDate2},
                  {$dueDate3},
                  {$dueDate4},
                  {$dueDate5},
                  '{$fileArray[0]["fileName"]}', '{$fileArray[0]["filePath"]}',
                  '{$fileArray[1]["fileName"]}', '{$fileArray[1]["filePath"]}',
                  '{$fileArray[2]["fileName"]}', '{$fileArray[2]["filePath"]}',
                  NOW()
                )
                ON DUPLICATE KEY UPDATE
                  `nd` = '{$_REQUEST["nd"]}',
                  `startMonth` = '{$_REQUEST["startMonth"]}',
                  `endMonth` = '{$_REQUEST["endMonth"]}',
                  `type` = '{$_REQUEST["type"]}',
                  `cnt` = '{$_REQUEST["cnt"]}',
                  `client` = '{$_REQUEST["client"]}',
                  `printCharge` = '{$printCharge}',
                  `deliveryCharge` = '{$deliveryCharge}',
                  `note` = '{$_REQUEST["note"]}',
                  `paymentFlag` = '{$_REQUEST["paymentFlag"]}',
                  `dueDate1` = {$dueDate1},
                  `dueDate2` = {$dueDate2},
                  `dueDate3` = {$dueDate3},
                  `dueDate4` = {$dueDate4},
                  `dueDate5` = {$dueDate5},
                  `fileName1` = '{$fileArray[0]["fileName"]}',
                  `filePath1` = '{$fileArray[0]["filePath"]}',
                  `fileName2` = '{$fileArray[1]["fileName"]}',
                  `filePath2` = '{$fileArray[1]["filePath"]}',
                  `fileName3` = '{$fileArray[2]["fileName"]}',
                  `filePath3` = '{$fileArray[2]["filePath"]}'
            ";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function changeFpubStatus(){
            $id = $_REQUEST["id"];
            $next = $_REQUEST["next"];

            $set = "";
            switch($next){
                case "1":
                    $set = ",`endDate1` = NOW()";
                    break;
                case "2":
                    $set = ",`endDate2` = NOW()";
                    break;
                case "3":
                    $set = ",`endDate3` = NOW()";
                    break;
                case "4":
                    $set = ",`endDate4` = NOW()";
                    break;
            }
            $sql = "
                UPDATE tblForeignPubItem
                SET
                  `manufactureFlag` = `manufactureFlag` + 1
                  {$set}
                WHERE 
                `id` IN (SELECT * FROM (SELECT `id` FROM tblForeignPubItem WHERE `id` = '{$id}' LIMIT 1) tmp)
            ";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function fPubChildList(){
            $this->initPage();
            $sql = "SELECT COUNT(*) cnt FROM tblForeignPubItem FPI JOIN tblForeignPub FP ON FPI.foreignPubId = FP.id";
            $this->rownum = $this->getValue($sql, "cnt");

            $this->setPage($this->rownum);

            $sql = "
                SELECT FPI.*, FP.country, FP.language, FP.year 
                FROM tblForeignPubItem FPI JOIN tblForeignPub FP ON FPI.foreignPubId = FP.id
                ORDER BY regDate DESC
                LIMIT {$this->startNum}, {$this->endNum};
            ";
            return $this->getArray($sql);
        }

        function changeFpubFlag(){
            $id = $_REQUEST["id"];
            $flag = $_REQUEST["flag"];
            $sql = "
                UPDATE tblForeignPubItem 
                SET `paymentFlag` = '{$flag}'
                WHERE `id` = '{$id}'
            ";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function storePublication(){
            $adminId = $this->admUser->id;
            $shippingType = $_REQUEST["shippingType"];
            $shippingCo = $_REQUEST["shippingCo"];
            $shippingPrice = str_replace(",", "", $_REQUEST["shippingPrice"]);

            $publicationIdArr = $_REQUEST["publicationId"];
            $typeArr = $_REQUEST["type"];
            $cntArr = $_REQUEST["cnt"];
            $pYearArr = $_REQUEST["pYear"];
            $pMonthArr = $_REQUEST["pMonth"];
            $contentArr = $_REQUEST["content"];

            for($i=0; $i<sizeof($publicationIdArr); $i++){
                $sql = "
                    INSERT INTO tblWarehousing(`publicationId`, `adminId`, `shippingType`, `shippingCo`, `shippingPrice`, `type`, `cnt`, `pYear`, `pMonth`,
                    `content`, `regDate`)
                    VALUES(
                      '{$publicationIdArr[$i]}',
                      '{$adminId}',
                      '{$shippingType}',
                      '{$shippingCo}',
                      '{$shippingPrice}',
                      '{$typeArr[$i]}',
                      '{$cntArr[$i]}',
                      '{$pYearArr[$i]}',
                      '{$pMonthArr[$i]}',
                      '{$contentArr[$i]}',
                      NOW()
                    )
                ";
                $this->update($sql);
            }
            return $this->makeResultJson(1, "succ");
        }

        function stockHistory(){
            $startDate = $_REQUEST["startDate"];
            $endDate = $_REQUEST["endDate"];

            $year = $_REQUEST["year"];
            $month = $_REQUEST["month"];
            $where = "pYear = '{$year}' AND pMonth = '{$month}'";

            $sql = "SELECT COUNT(*) cnt FROM tblWarehousing WHERE {$where}";
            $this->initPage();
            $this->rownum = $this->getValue($sql, "cnt");
            $this->setPage($this->rownum);

            $sql = "
                SELECT 
                  *, 
                  (SELECT `desc` FROM tblPublication WHERE `id` = publicationId) publicationName,
                  (SELECT `name` FROM tblAdmin WHERE tblAdmin.id = adminId) adminName,
                  (SELECT `desc` FROM tblTypeManage WHERE `type`='0' AND `id` = shippingCo) shippingCoDesc
                FROM tblWarehousing
                WHERE {$where}
                ORDER BY regDate DESC
                LIMIT {$this->startNum}, {$this->endNum};
            ";
            return $this->getArray($sql);
        }

        function stockStat(){
            $startYear = $_REQUEST["startYear"];
            $startMonth = $_REQUEST["startMonth"];
            $endYear = $_REQUEST["endYear"];
            $endMonth = $_REQUEST["endMonth"];

            $sql = "
                SELECT *, SUM(cnt) AS summation
                FROM tblWarehousing
                WHERE 
                1=1
            ";
            if($startYear != ""){
                if($startMonth == "") $startMonth = 1;
                $sql .= "
                AND CASE
                  WHEN pYear = {$startYear} THEN pMonth >= {$startMonth}
                  WHEN pYear < {$startYear} THEN pYear > {$startYear}
                  WHEN pYear > {$startYear} THEN 1=1   
                  ELSE 1=1
                END
                ";
            }
            if($endYear != ""){
                if($endMonth == "") $endMonth = 1;
                $sql .= "
                AND CASE
                  WHEN pYear = {$endYear} THEN pMonth <= {$endMonth}
                  WHEN pYear < {$endYear} THEN 1=1
                  WHEN pYear > {$endYear} THEN pYear < {$endYear}
                  ELSE 1=1
                END
                ";
            }
            $sql .= "GROUP BY pYear, pMonth, publicationId ORDER BY pYear DESC, pMonth DESC";
            return $this->getArray($sql);
        }

        function stockDetail(){
            $year = $_REQUEST["year"];
            $month = $_REQUEST["month"];

            $sql = "
                SELECT 
                  *
                FROM tblPublication 
                ORDER BY regDate DESC
            ";
            $pubList = $this->getArray($sql);

            $sql = "
                SELECT *, SUM(cnt) AS summation
                FROM tblWarehousing
                WHERE pYear = '{$year}' AND pMonth = '{$month}'
                GROUP BY publicationId, `type` 
            ";
            $stat = $this->getArray($sql);

            for($i=0; $i<sizeof($pubList); $i++){
                for($e=0; $e<sizeof($stat); $e++){
                    if($pubList[$i]["id"] == $stat[$e]["publicationId"]){
                        $pubList[$i]["stat"][intval($stat[$e]["type"])] = $stat[$e]["summation"];
                    }
                }
            }
            return $pubList;
        }

        function shippingList($type){
            $sql = "
                SELECT 
                  S.*, 
                  (SELECT `desc` FROM tblPublication WHERE id = S.publicationId) publicationName,
                  (SELECT COUNT(*) FROM tblShipping WHERE subsciptionId = S.subsciptionId) lostCnt,
                  SUB.pYear,
                  SUB.pMonth,
                  SUB.eYear,
                  SUB.eMonth
                FROM tblShipping S JOIN tblSubscription SUB ON S.subsciptionId = SUB.id
                WHERE S.shippingType = '{$type}' AND `status` = '1' 
                ORDER BY regDate DESC
            ";
            return $this->getArray($sql);
        }

        function cardTypeList(){
            $sql = "
                SELECT * FROM tblCardType ORDER BY `id` ASC
            ";
            return $this->getArray($sql);
        }

        function bankTypeList(){
            $sql = "
                SELECT * FROM tblBankType ORDER BY `id` ASC
            ";
            return $this->getArray($sql);
        }

        function transactionList(){
            $type = $_REQUEST["type"];
            $month = $_REQUEST["month"];
            $customerType = $_REQUEST["customerType"];

            if($month != "")
                $where = " AND pMonth = '{$month}'";

            if($type == "sub"){
                $sql = "
                    SELECT * FROM tblSubscription WHERE 1=1 {$where} ORDER BY regDate DESC
                ";
            }
            else if($type == "sup"){
                $sql = "
                    SELECT * FROM tblSupport WHERE 1=1 {$where} ORDER BY regDate DESC
                ";
            }

            return $this->getArray($sql);
        }

        function lostList(){
            $id = $_REQUEST["id"];

            $sql = "
                SELECT *, (SELECT `name` FROM tblPublicationLang PL WHERE PL.publicationId = S.publicationId AND langCode = 'kr' LIMIT 1) publicationName 
                FROM tblSubscription S
                WHERE `customerId` = '{$id}' 
                ORDER BY regDate DESC
            ";
            return $this->getArray($sql);
        }

        function setLost(){
            $type = $_REQUEST["type"];
            $noArr = $_REQUEST["noArr"];
            $ymArr = $_REQUEST["ymArr"];
            $noStr = implode(',', $noArr);
            $sql = "SELECT * FROM tblSubscription WHERE `id` IN ({$noStr})";

            $targetArr = $this->getArray($sql);
            $retArr = array();

            for($w = 0; $w < sizeof($noArr); $w++){
                for($e = 0; $e < sizeof($targetArr); $e++){
                    if($noArr[$w] == $targetArr[$e]["id"]) array_push($retArr, $targetArr[$e]);
                }
            }

            $index = 0;
            foreach($retArr as $item){
                $ym = json_decode($ymArr[$index]);
                $pYear = $ym->pYear;
                $pMonth = $ym->pMonth;
                $sql = "
                    INSERT INTO tblShipping(`customerId`, `subsciptionId`, `type`, `rName`, `zipcode`, `phone`, `addr`, `addrDetail`, `publicationId`, `cnt`, `pYear`, `pMonth`, `shippingType`, `manager`, `regDate`)
                    VALUES(
                      '{$item["customerId"]}',
                      '{$item["id"]}',
                      '1',
                      '{$item["rName"]}',
                      '{$item["rZipCode"]}',
                      '{$item["rPhone"]}',
                      '{$item["rAddr"]}',
                      '{$item["rAddrDetail"]}',
                      '{$item["publicationId"]}',
                      '{$item["cnt"]}',
                      '{$pYear}',
                      '{$pMonth}',
                      '{$type}',
                      'SYSTEM',
                      NOW()
                    )
                ";
                $this->update($sql);
                $index++;
            }
            return $this->makeResultJson(1, "succ");
        }

        function setWarehousing(){
            $type = $_REQUEST["type"];
            $list = json_decode($_REQUEST["list"], true);
            $sPrice = 0;
            if($type == "1") $sPrice = 2000;
            foreach($list as $item){
                $cnt = intval($item["cnt"]) * -1;
                $sql = "
                    INSERT INTO tblWarehousing(`publicationId`, `adminId`, `shippingType`, `shippingCo`, `shippingPrice`, `type`, `cnt`, `pYear`, `pMonth`, `content`, `regDate`)
                    VALUES(
                      '{$item["publicationId"]}',
                      '{$this->admUser->id}',
                      '{$type}',
                      '',
                      '{$sPrice}',
                      '1',
                      '{$cnt}',
                      '{$item["pYear"]}',
                      '{$item["pMonth"]}',
                      '',
                      NOW()
                    )
                ";
                $this->update($sql);

                $sql = "
                    UPDATE tblShipping SET `status` = 0 WHERE id = '{$item["id"]}'
                ";
                $this->update($sql);
            }
            return $this->makeResultJson(1, "succ");
        }

        function processFC(){
            $sql = "
                SELECT * FROM tblPayment
                WHERE `type` = 'FC'
            ";
            $target = $this->getArray($sql);

            foreach($target as $item){
                $res = $this->getAuthorizeStatus($item["aSubscriptionId"]);
                $retStatus = $res->status;
                $retCode = $res->messages->message[0]->code;

                if($retStatus != "active" || $retCode != "I00001"){
                    $sql = "
                        UPDATE tblPayment SET paymentResult = '0' WHERE `id` = '{$item["id"]}'
                    ";
                    $this->update($sql);
                }
                else if($retStatus == "active" && $retCode == "I00001"){
                    $sql = "
                        UPDATE tblPayment SET paymentResult = '1' WHERE `id` = '{$item["id"]}'
                    ";
                    $this->update($sql);
                }
            }
            return $this->makeResultJson(1, "succ");
        }

        function processBA(){
            $sql = "
                SELECT * FROM tblPayment WHERE `type` = 'BA'
            ";
            $target = $this->getArray($sql);
            foreach($target as $item){
                $primeIndex = $item["primeIndex"];
                $sql = "
                    SELECT
                        (SELECT send_stat FROM member WHERE ext_inx = '{$primeIndex}') as memberStat, 
                        (SELECT send_stat FROM agreefile WHERE ext_inx = '{$primeIndex}') as agreeStat,
                        (SELECT userstat_kind FROM file_ea14 WHERE ext_inx = '[$primeIndex}') as memberStatus,
                        (SELECT result FROM file_ea14 WHERE ext_inx = '{$primeIndex}') as reserveRes,
                        (SELECT send_stat FROM file_ea14 WHERE ext_inx = '{$primeIndex}') as reserveStat,
                        (SELECT result FROM file_ea22 WHERE ext_inx = '{$primeIndex}') as chargeRes,
                        (SELECT send_stat FROM file_ea22 WHERE ext_inx = '{$primeIndex}') as chargeStat,
                        (SELECT result FROM file_ea11 WHERE ext_inx = '{$primeIndex}') as bankRes,
                        (SELECT send_stat FROM file_ea11 WHERE ext_inx = '{$primeIndex}') as bankStat
                    FROM DUAL;
                ";
                $this->connect_ext_db();
                $res = $this->getRow($sql);

                $this->connect_int_db();
                if($res["memberStat"] != 4 || $res["agreeStat"] != 4 || $res["reserveRes"] != 1 || $res["chargeRes"] != 1 || $res["bankRes"] != 1 || $res["userstat_kind"] != 1){
                    $sql = "
                        UPDATE tblPayment SET `paymentResult` = '0'
                        WHERE `id` = '{$item["id"]}'
                    ";
                    $this->update($sql);
                }
            }
            return $this->makeResultJson(1, "succ");
        }

        function paymentList(){
            $type = $_REQUEST["type"];

            $sql = "
                SELECT 
                *, 
                (SELECT `desc` FROM tblCardType WHERE id = PM.cardTypeId) cardDesc,
                (SELECT `desc` FROM tblBankType WHERE code = PM.bankCode) bankDesc,
                P.regDate pRegDate,
                P.id as idx,
                CASE P.buyType
                    WHEN 'SUB' THEN (SELECT totalPrice FROM tblSubscription SUB WHERE SUB.paymentId = P.id)
                    WHEN 'SUP' THEN (SELECT totalPrice FROM tblSupport SUP WHERE SUP.paymentId = P.id)
                END AS totalPrice
                FROM tblPayment P JOIN tblPayMethod PM ON P.payMethodId = PM.id JOIN tblCustomer C ON PM.customerId = C.id
                WHERE P.type = '{$type}'
                ORDER BY P.regDate DESC
            ";
            $res = $this->getArray($sql);
            if($type == "BA"){
                $this->connect_ext_db();
                for($i=0; $i < sizeof($res); $i++){
                    $primeIndex = $res[$i]["primeIndex"];
                    $sql = "
                        SELECT
                            (SELECT send_stat FROM member WHERE ext_inx = '{$primeIndex}') as memberStat,
                            (SELECT send_stat FROM agreefile WHERE ext_inx = '{$primeIndex}') as agreeStat,
                            (SELECT userstat_kind FROM file_ea14 WHERE ext_inx = '[$primeIndex}') as memberStatus,
                            (SELECT result FROM file_ea14 WHERE ext_inx = '{$primeIndex}') as reserveRes,
                            (SELECT send_stat FROM file_ea14 WHERE ext_inx = '{$primeIndex}') as reserveStat,
                            (SELECT result FROM file_ea22 WHERE ext_inx = '{$primeIndex}') as chargeRes,
                            (SELECT send_stat FROM file_ea22 WHERE ext_inx = '{$primeIndex}') as chargeStat,
                            (SELECT result FROM file_ea11 WHERE ext_inx = '{$primeIndex}') as bankRes,
                            (SELECT send_stat FROM file_ea11 WHERE ext_inx = '{$primeIndex}') as bankStat
                        FROM DUAL;
                    ";
                    $res[$i]["primeRes"] = $this->getRow($sql);
                }
                $this->connect_int_db();
            }
            return $res;
        }

        function changePaymentFlag(){
            $id = $_REQUEST["id"];
            $res = $_REQUEST["res"];
            $sql = "
                UPDATE tblPayment SET `flag` = '{$res}' WHERE `id` = '{$id}'";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function changePaymentStatus(){
            $id = $_REQUEST["id"];
            $res = $_REQUEST["res"];
            $sql = "
                UPDATE tblPayment SET paymentResult = '{$res}' WHERE `id` = '{$id}'";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function deliveryHistory(){
            $id = $_REQUEST["id"];
            $this->initPage();
            $sql = "SELECT COUNT(*) cnt FROM tblCustomerDeliveryHistory WHERE customerId ='{$id}'";
            $this->rownum = $this->getValue($sql, "cnt");
            $this->setPage($this->rownum);

            $sql = "
                SELECT * FROM tblCustomerDeliveryHistory WHERE customerId ='{$id}' ORDER BY regDate DESC LIMIT {$this->startNum}, {$this->endNum};
            ";
            return $this->getArray($sql);
        }

        function getStock(){
            $sql = "
                SELECT 
                	*, 
                	SUM(cnt) AS summation,
                	(SELECT `desc` FROM tblPublication WHERE id = publicationId) as publicationName,
                	(SELECT SUM(cnt) FROM tblWarehousing WHERE publicationId = P.publicationId AND `type` = 0) as aStock,
                	(SELECT SUM(cnt) FROM tblWarehousing WHERE publicationId = P.publicationId AND `type` = 1) as kStock,
                	(SELECT SUM(cnt) FROM tblWarehousing WHERE publicationId = P.publicationId AND `type` = 2) as pStock
                FROM tblWarehousing P
                GROUP BY publicationId;
            ";
            return $this->getArray($sql);
        }

        function deleteCustomer(){
            $id = $_REQUEST["id"];
            $sql = "
                DELETE FROM tblCustomer WHERE id = '{$id}'
            ";
            $this->update($sql);
            $sql = "DELETE FROM tblSubscription WHERE customerId = '{$id}'";
            $this->update($sql);
            $sql = "DELETE FROM tblSupport WHERE customerId = '{$id}'";
            $this->update($sql);
            $sql = "DELETE FROM tblPayMethod WHERE customerId = '{$id}'";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function deleteHistory(){
            $sql = "DELETE FROM tblCustomerHistory WHERE `id` = '{$_REQUEST["id"]}'";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function publicationDetail(){
            $id = $_REQUEST["publicationId"];
            $langCode = $_COOKIE["btLocale"];
            if($id == ""){
                $sql = "
                  SELECT * FROM tblPublication ORDER BY id ASC LIMIT 1
                ";
            }
            else{
                $sql = "
                    SELECT * FROM tblPublication WHERE id='{id}' LIMIT 1
                ";
            }
            return $this->getRow($sql);
        }

        function publicationList(){
            $langCode = $_COOKIE["btLocale"];

            $sql = "
                SELECT * 
                FROM tblPublication 
                ORDER BY regDate DESC;
            ";
            return $this->getArray($sql);
        }

        function setSubscriptionInfo(){
            $uc = new Uncallable($_REQUEST);
            $flag = $uc->getProperty("FLAG_VALUE_LOST");

            $type = $_REQUEST["type"];
            $customerId = $_REQUEST["customerId"];
            $publicationId = $_REQUEST["publicationId"];
            $publicationCnt = $_REQUEST["publicationCnt"];
            $rName = $_REQUEST["rName"];
            $rPhone = $_REQUEST["rPhone"];
            $rZipcode = $_REQUEST["rZipcode"];
            $rAddr = $_REQUEST["rAddr"];
            $rAddrDetail = $_REQUEST["rAddrDetail"];
            $totalPrice = $_REQUEST["totalPrice"];
            $monthlyDate = 5;
            if($type == "2"){
                $monthlyDate = 15;
            }

            //TODO paymethod/payment info insert
            $paymentType = $_REQUEST["paymentType"];
            $isOwner = $_REQUEST["isOwner"];
            $ownerName = $_REQUEST["ownerName"];
            $cardTypeId = $_REQUEST["cardType"] == "" ? -1 : $_REQUEST["cardType"];
            $bankCode = $_REQUEST["bankType"];
            $info = $_REQUEST["info"];
            $validThruYear = $_REQUEST["validThruYear"];
            $validThruMonth =  $_REQUEST["validThruMonth"];

            $aSubsciptionId = "";
            $aCustomerProfileId = "";
            $paymentResult = 0;

            $primeJumin = $_REQUEST["birth"] != "" ? substr($_REQUEST["birth"], 2, 6) : "";
            $primeSigPath = "";
            $primeIndex = -1;
            $primeExternal = "";

            if($paymentType == "CC"){
                $info = $_REQUEST["card1"] . $_REQUEST["card2"] .$_REQUEST["card3"] .$_REQUEST["card4"];
//                $paymentResult = 1;
            }
            if($paymentType == "BA"){
                $fileType = $_REQUEST["fileType"];

                $af_kind = $fileType == "jpg" ? 1 : 4;

                $check = file_exists($_FILES['signatureFile']['tmp_name']);
                if($check !== false){
                    $fName = "bt" . $this->makeFileName() . "." . pathinfo(basename($_FILES["signatureFile"]["name"]),PATHINFO_EXTENSION);
                    $targetDir = $_SERVER["DOCUMENT_ROOT"]."/uploadFiles/" . $fName;
                    $fileName = $fName;
                    if(move_uploaded_file($_FILES["signatureFile"]["tmp_name"], $targetDir)){
                        //TODO prime member row add
                        //TODO prime agreefile row add

                        $tmpTimestamp  = "bt" . $this->makeFileName();
                        $tmpSdate = date("Y") . "-" . date("m");
                        $primeIndex = $this->addPrime(
                            $tmpTimestamp,
                            $_REQUEST["rName"],
                            $_REQUEST["bankType"] . "0000",
                            $_REQUEST["info"],
                            $_REQUEST["rName"],
                            $primeJumin,
                            $tmpSdate,
                            $monthlyDate,
                            $totalPrice
                        );
                        $primeIndex = str_pad($primeIndex, 10, '0', STR_PAD_LEFT);
                        $this->ftpUpload($fName);

                        $tmpSdate = date("Y").date("m").date("d");
                        $this->addAgreeFile(
                            $primeIndex,
                            $tmpTimestamp,
                            $_REQUEST["bankType"],
                            $_REQUEST["info"],
                            $tmpSdate,
                            $af_kind,
                            $fName,
                            2
                        );
                        $primeExternal = $tmpTimestamp;
                        $primeSigPath = $fName;
                    }
                    else return $this->makeResultJson(-22, "signature upload fail");
                }
            }
            if($paymentType == "FC"){
                $info = $_REQUEST["cardForeign"];
                $validThruYear = $_REQUEST["validThruYearF"];
                $validThruMonth = $_REQUEST["validThruMonthF"];
                $monthlyDate = 15;
                /**
                 * Parameters
                 */
                $subscriptionName = "";
                $startDate = date("Y") . "-" . date("m") . "-" . "15";
                $totalOccurrences = "9999";
                $trialOccurrences = "";
                $amount = $totalPrice;
                $unit = "months";
                $trialAmount = "";
                $cardNo = $info;
                $cardExpiry = $validThruYear."-".$validThruMonth;
                $FirstName = $_REQUEST["firstName"];
                $LastName = $_REQUEST["lastName"];
                $intervalLength = "1";

                $address = $_REQUEST["aAddr"];
                $city = $_REQUEST["aCity"];
                $state = $_REQUEST["aState"];
                $zip = $_REQUEST["aZip"];
                /**
                 * End
                 */

                $payRes = $this->sendAuthrizeSubscription(
                    $subscriptionName,
                    $startDate,
                    $totalOccurrences,
                    $amount,
                    $unit,
                    $cardNo,
                    $cardExpiry,
                    $FirstName,
                    $LastName,
                    $intervalLength,
                    $address,
                    $city,
                    $state,
                    $zip
                );

                $returnCode = $payRes->messages->message[0]->code;
                $paymentId = -1;

                if($returnCode == "I00001"){
                    $aSubsciptionId = $payRes->subscriptionId;
                    $aCustomerProfileId = $payRes->profile->customerProfileId;
                    $paymentResult = 1;
                }
                else return $this->makeResultJson(-1, "payment failure");
            }

            $sql = "
              INSERT INTO tblPayment(`buyType`, `type`, monthlyDate, primeJumin, primeSigPath, primeIndex, `aSubscriptionId`, `aCustomerProfileId`, paymentResult, regDate)
              VALUES(
                'SUB',
                '{$paymentType}',
                '{$monthlyDate}',
                '{$primeJumin}',
                '{$primeSigPath}',
                '{$primeExternal}',
                '{$aSubsciptionId}',
                '{$aCustomerProfileId}',
                '{$paymentResult}',
                NOW()
              )
            ";
            $this->update($sql);
            $paymentId = $this->mysql_insert_id();

            $sql = "
                INSERT INTO tblPayMethod(customerId, isOwner, cardTypeId, bankCode, ownerName, `type`, info, aFirstname, aLastname, aAddr, aCity, aState, aZip, validThruYear, validThruMonth, regDate)
                VALUES(
                  '{$customerId}',
                  '{$isOwner}',
                  '{$cardTypeId}',
                  '{$bankCode}',
                  '{$ownerName}',
                  '{$paymentType}',
                  '{$info}',
                  '{$_REQUEST["firstName"]}',
                  '{$_REQUEST["lastName"]}',
                  '{$_REQUEST["aAddr"]}',
                  '{$_REQUEST["aCity"]}',
                  '{$_REQUEST["aState"]}',
                  '{$_REQUEST["aZip"]}',
                  '{$validThruYear}',
                  '{$validThruMonth}',
                  NOW()
                )
            ";
            $this->update($sql);
            $payMethodId = $this->mysql_insert_id();

            $sql = "
                UPDATE tblPayment SET payMethodId = '{$payMethodId}' WHERE `id` = {$paymentId}
            ";
            $this->update($sql);

            $publicationName = $_REQUEST["publicationName"];
            $curYear = intval(date("Y"));
            $curMonth = intval(date("m"));
            $curDate = intval(date("d"));
            $templateCode = "";
            $temp = "";
            if($curDate < 10){
                $curMonth++;
                $templateCode = "04_Delivery";
                $temp = 25;
            }
            else if($curDate >= 10 && $curDate <=20){
                $curMonth++;
                $templateCode = "02_Delivery";
                $temp = 30;
            }
            else if($curDate >=21 && $curDate <= 25){
                $curMonth++;
                $templateCode = "03_Delivery";
                $temp = 10;
            }
            else if($curDate > 25){
                $curMonth = $curMonth + 2;
                $templateCode = "04_Delivery";
                $temp = 25;
            }

            if($curMonth > 12){
                $curYear++;
                $curMonth = $curMonth - 12;
            }
            $pYear = $curYear;
            $pMonth = $curMonth;

            $shippingType = 0;
            if($publicationCnt >= 10) $shippingType = 1;

            $sql = "
                INSERT INTO tblSubscription(`customerId`, `publicationId`, `cnt`, `pYear`, `pMonth`, `totalPrice`, `shippingType`, `rName`, `rPhone`, `rZipcode`, `rAddr`, `rAddrDetail`, `paymentId`, `regDate`)
                VALUES(
                  '{$customerId}',
                  '{$publicationId}',
                  '{$publicationCnt}',
                  '{$pYear}',
                  '{$pMonth}',
                  '{$totalPrice}',
                  '{$shippingType}',
                  '{$rName}',
                  '{$rPhone}',
                  '{$rZipcode}',
                  '{$rAddr}',
                  '{$rAddrDetail}',
                  '{$paymentId}',
                  NOW()
                )
            ";
            $this->update($sql);
            $subscriptionId = $this->mysql_insert_id();

            if($flag == 1){
                $sql = "
                    INSERT INTO tblShipping(customerId, subsciptionId, rName, `type`, zipcode, phone, addr, addrDetail, publicationId, cnt, pYear, pMonth, shippingType, manager, regDate)
                    VALUES(
                      '{$customerId}',
                      '{$subscriptionId}',
                      '{$rName}',
                      '0',
                      '{$rZipcode}',
                      '{$rPhone}',
                      '{$rAddr}',
                      '{$rAddrDetail}',
                      '{$publicationId}',
                      '{$publicationCnt}',
                      '{$pYear}',
                      '{$pMonth}',
                      '{$shippingType}',
                      'SYSTEM',
                      NOW()
                    )
                ";

                $this->update($sql);
            }

            return $this->makeResultJson(1, "succ");
        }

        function getSupportParent(){
            $sql = "SELECT * FROM tblSupportParent ORDER BY id ASC";
            return $this->getArray($sql);
        }

        function setSupportInfo(){
            $customerId = $_REQUEST["customerId"];
            $parentId = $_REQUEST["parentId"];

            $sql = "SELECT * FROM tblCustomer WHERE id='{$customerId}' LIMIT 1";
            $customerInfo = $this->getRow($sql);

            $type = $_REQUEST["type"];

            $cnt = $_REQUEST["cnt"];
            $totalPrice = $_REQUEST["totalPrice"];
            $message = $_REQUEST["message"];
            $monthlyDate = 5;
            if($type == "2") $monthlyDate = 15;

            $paymentType = $_REQUEST["paymentType"];
            $isOwner = $_REQUEST["isOwner"];
            $ownerName = $_REQUEST["ownerName"];
            $cardTypeId = $_REQUEST["cardType"] == "" ? -1 : $_REQUEST["cardType"];
            $bankCode = $_REQUEST["bankType"];
            $info = $_REQUEST["info"];
            $validThruYear = $_REQUEST["validThruYear"];
            $validThruMonth =  $_REQUEST["validThruMonth"];

            $aSubsciptionId = "";
            $aCustomerProfileId = "";
            $paymentResult = 0;

            $primeJumin = $_REQUEST["birth"] != "" ? substr($_REQUEST["birth"], 2, 6) : "";
            $primeSigPath = "";
            $primeIndex = -1;
            $primeExternal = "";

            if($paymentType == "CC"){
                $info = $_REQUEST["card1"] . $_REQUEST["card2"] .$_REQUEST["card3"] .$_REQUEST["card4"];
            }
            if($paymentType == "BA"){
                $fileType = $_REQUEST["fileType"];
                $af_kind = $fileType == "jpg" ? 1 : 4;

                $check = file_exists($_FILES['signatureFile']['tmp_name']);
                if($check !== false){
                    $fName = "bt" . $this->makeFileName() . "." . pathinfo(basename($_FILES["signatureFile"]["name"]),PATHINFO_EXTENSION);
                    $targetDir = $_SERVER["DOCUMENT_ROOT"]."/uploadFiles/" . $fName;
                    $fileName = $fName;
                    if(move_uploaded_file($_FILES["signatureFile"]["tmp_name"], $targetDir)){
                        $tmpTimestamp  = "bt" . $this->makeFileName();
                        $tmpSdate = date("Y") . "-" . date("m");
                        $primeIndex = $this->addPrime(
                            $tmpTimestamp,
                            $_REQUEST["name"],
                            $_REQUEST["bankType"] . "0000",
                            $_REQUEST["info"],
                            $_REQUEST["name"],
                            $primeJumin,
                            $tmpSdate,
                            $monthlyDate,
                            $totalPrice
                        );
                        $primeIndex = str_pad($primeIndex, 10, '0', STR_PAD_LEFT);

                        $this->ftpUpload($fName);

                        $tmpSdate = date("Y").date("m").date("d");
                        $this->addAgreeFile(
                            $primeIndex,
                            $tmpTimestamp,
                            $_REQUEST["bankType"],
                            $_REQUEST["info"],
                            $tmpSdate,
                            $af_kind,
                            $fName,
                            2
                        );
                        $primeExternal = $tmpTimestamp;
                        $primeSigPath = $fName;
                    }
                    else return $this->makeResultJson(-22, "signature upload fail");
                }
            }
            if($paymentType == "FC"){
                $info = $_REQUEST["cardForeign"];
                $validThruYear = $_REQUEST["validThruYearF"];
                $validThruMonth = $_REQUEST["validThruMonthF"];
                $monthlyDate = 15;
                /**
                 * Parameters
                 */
                $subscriptionName = "";
                $startDate = date("Y") . "-" . date("m") . "-" . "15";
                $totalOccurrences = "9999";
                $trialOccurrences = "";
                $amount = $totalPrice;
                $unit = "months";
                $trialAmount = "";
                $cardNo = $info;
                $cardExpiry = $validThruYear."-".$validThruMonth;
                $FirstName = $_REQUEST["firstName"];
                $LastName = $_REQUEST["lastName"];
                $intervalLength = "1";

                $address = $_REQUEST["aAddr"];
                $city = $_REQUEST["aCity"];
                $state = $_REQUEST["aState"];
                $zip = $_REQUEST["aZip"];

                /**
                 * End
                 */
                $payRes = $this->sendAuthrizeSubscription(
                    $subscriptionName,
                    $startDate,
                    $totalOccurrences,
                    $amount,
                    $unit,
                    $cardNo,
                    $cardExpiry,
                    $FirstName,
                    $LastName,
                    $intervalLength,
                    $address,
                    $city,
                    $state,
                    $zip
                );

                $returnCode = $payRes->messages->message[0]->code;

                if($returnCode == "I00001"){
                    $aSubsciptionId = $payRes->subscriptionId;
                    $aCustomerProfileId = $payRes->profile->customerProfileId;
                    $paymentResult = 1;
                }
                else{
                    return $this->makeResultJson(-1, "payment failure");
                }
            }

            $sql = "
              INSERT INTO tblPayment(`buyType`, `type`, monthlyDate, primeJumin, primeSigPath, primeIndex, `aSubscriptionId`, `aCustomerProfileId`, paymentResult, regDate)
              VALUES(
                'SUP',
                '{$paymentType}',
                '{$monthlyDate}',
                '{$primeJumin}',
                '{$primeSigPath}',
                '{$primeExternal}',
                '{$aSubsciptionId}',
                '{$aCustomerProfileId}',
                '{$paymentResult}',
                NOW()
              )
            ";
            $this->update($sql);
            $paymentId = $this->mysql_insert_id();

            $sql = "
                INSERT INTO tblPayMethod(customerId, isOwner, cardTypeId, bankCode, ownerName, `type`, info, aFirstname, aLastname, aAddr, aCity, aState, aZip, validThruYear, validThruMonth, regDate)
                VALUES(
                  '{$customerId}',
                  '{$isOwner}',
                  '{$cardTypeId}',
                  '{$bankCode}',
                  '{$ownerName}',
                  '{$paymentType}',
                  '{$info}',
                  '{$_REQUEST["firstName"]}',
                  '{$_REQUEST["lastName"]}',
                  '{$_REQUEST["aAddr"]}',
                  '{$_REQUEST["aCity"]}',
                  '{$_REQUEST["aState"]}',
                  '{$_REQUEST["aZip"]}',
                  '{$validThruYear}',
                  '{$validThruMonth}',
                  NOW()
                )
            ";

            $this->update($sql);
            $payMethodId = $this->mysql_insert_id();

            $sql = "
                UPDATE tblPayment SET payMethodId = '{$payMethodId}' WHERE `id` = {$paymentId}
            ";
            $this->update($sql);

            $sYear = date("Y");
            $sMonth = date("m");
            $sql = "
                INSERT INTO tblSupport(`customerId`, `supType`, `parentId`, `cnt`, `totalPrice`, `rName`, `rEmail`, `rPhone`, `paymentId`, `message`, `sYear`, `sMonth`, `regDate`)
                VALUES(
                  '{$customerId}',
                  '{$_REQUEST["supportType"]}',
                  '{$parentId}',
                  '{$cnt}',
                  '{$totalPrice}',
                  '{$customerInfo["name"]}',
                  '{$customerInfo["email"]}',
                  '{$customerInfo["phone"]}',
                  '{$paymentId}',
                  '{$message}',
                  '{$sYear}',
                  '{$sMonth}',
                  NOW()
                )
            ";
            $this->update($sql);

            return $this->makeResultJson(1, "succ");
        }
    }
}
