<? include $_SERVER["DOCUMENT_ROOT"] . "/common/classes/AdminBase.php" ;?>
<?
/*
 * Web process
 * add by cho
 * */
if(!class_exists("AdminMain")){
    class AdminMain extends  AdminBase {
        function __construct($req)
        {
            parent::__construct($req);
        }

        function login(){
            $account = $_REQUEST["account"];
            $password = md5($_REQUEST["password"]);

            $sql = "
                SELECT * FROM tblAdmin
                WHERE `account` = '{$account}' AND `password` = '{$password}' AND status = 1
                LIMIT 1
            ";
            $res = $this->getRow($sql);
            if($res != ""){
                LoginUtil::doAdminLogin($res);
                return $this->makeResultJson(1, "succ", $res);
            }
            else return $this->makeResultJson(-1, "fail");
        }

        function logout(){
            LoginUtil::doAdminLogout();
            return $this->makeResultJson(1, "succ");
        }

        function getLangList(){
            $sql = "SELECT * FROM tblLang ORDER BY `order` ASC;";
            return $this->getArray($sql);
        }

        function deleteLang(){
            $code = $_REQUEST['code'];
            if($code == "") return;
            else{
                $sql = "DELETE FROM tblLang WHERE `code` = '{$code}'";
                $this->update($sql);
            }
        }

        function upsertLang(){
            $code = $_REQUEST['code'];
            $order = $_REQUEST['order'];
            $desc = $_REQUEST['desc'];
            $sql = "INSERT 
                    INTO tblLang(`code`, `order`, `desc`) 
                    VALUES('{$code}', '{$order}', '{$desc}')
                    ON DUPLICATE KEY UPDATE `order`='{$order}', `desc`='{$desc}'";
            $this->update($sql);
        }

        function _upsertLangJson(){
            $this->upsertLangJson($_REQUEST["code"], $_REQUEST["json"]);
        }

        function upsertLangJson($code, $jsonArray){
            $json = mysql_escape_string($jsonArray);

            $sql = "
            INSERT INTO tblLangJson(`code`, `json`, `regDate`)
            VALUES ('{$code}', '{$json}', NOW())
            ON DUPLICATE KEY UPDATE `json`='{$json}'
            ";
            $this->update($sql);
        }

        function _getLangJson(){
            return json_encode($this->getLangJson($_REQUEST["code"]));
        }

        function getLangJson($code){
            $sql = "SELECT * FROM tblLangJson WHERE `code` = '{$code}'";
            return $this->getRow($sql);
        }

        function getLocale(){
            $sql = "SELECT * FROM tblLang ORDER BY `order` ASC";
            return $this->getArray($sql);
        }

        function shareCategoryList(){
            $code = $_REQUEST["code"];
            if($code == "") $sql = "SELECT *, (SELECT `desc` FROM tblLang WHERE `code` = `lang`) AS langDesc FROM tblBoardType ORDER BY regDate DESC";
            else $sql = "SELECT *, (SELECT `desc` FROM tblLang WHERE `code` = `lang`) AS langDesc FROM tblBoardType WHERE `lang` = '{$_REQUEST["code"]}' ORDER BY regDate DESC";
            return $this->getArray($sql);
        }

        function shareCategoryDetail(){
            $sql = "SELECT * FROM tblBoardType WHERE `id` = '{$_REQUEST["id"]}' LIMIT 1";
            return $this->getRow($sql);
        }

        function getExposures(){
            $sql = "SELECT * FROM tblLayoutExposure ORDER BY `desc` ASC";
            $arr = $this->getArray($sql);
//            $retVal = Array();
//            for($e = 0; $e < sizeof($arr); $e++){
//                $retVal[$arr[$e]["code"]] = $arr[$e]["exposure"];
//            }

            return $arr;
        }

        function saveExposure(){
            $value = $_REQUEST["checked"];
            $code = $_REQUEST["code"];
            $sql = "UPDATE tblLayoutExposure SET `exposure` = '{$value}' WHERE `code`='{$code}'";

            $this->update($sql);
        }

        function upsertCategory(){
            $check = getimagesize($_FILES["imgFile"]["tmp_name"]);

            $id = $_REQUEST["id"];
            $lang = $_REQUEST["lang"];
            $name = $_REQUEST["name"];
            $subTitle = $_REQUEST["subTitle"];
            $writePermission = $_REQUEST["writePermission"];
            $readPermission = $_REQUEST["readPermission"];

            $imgPath = NULL;

            if($check !== false){
                $fName = $this->makeFileName() . "." . pathinfo(basename($_FILES["imgFile"]["name"]),PATHINFO_EXTENSION);
                $targetDir = $this->filePath . $fName;
                if(move_uploaded_file($_FILES["imgFile"]["tmp_name"], $targetDir)) $imgPath = $fName;
                else return $this->makeResultJson(-1, "fail");
            }
            else
                $imgPath = $_REQUEST["imgPath"];

            $sql = "
                INSERT INTO tblBoardType(`lang`, `name`, `subTitle`, `writePermission`, `readPermission`, `imgPath`, `regDate`)
                VALUES(
                  '{$lang}',
                  '{$name}',
                  '{$subTitle}',
                  '{$writePermission}',
                  '{$readPermission}',
                  '{$imgPath}',
                  NOW()
                )
                ON DUPLICATE KEY UPDATE
                `lang` = '{$lang}',
                `name` = '{$name}',
                `subTitle` = '{$subTitle}',
                `writePermission` = '{$writePermission}',
                `readPermission` = '{$readPermission}',
                `imgPath` = '{$imgPath}'        
            ";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function publicationList(){
            $sql = "
                SELECT 
                  *
                FROM tblPublication 
                ORDER BY regDate DESC
            ";
            return $this->getArray($sql);
        }

        function publicationDetail(){
            $id = $_REQUEST["id"];
            $langCode = $_REQUEST["langCode"];

            $sql = "
                SELECT * FROM tblPublicationLang WHERE publicationId = '{$id}' AND `langCode` = '{$langCode}' LIMIT 1
            ";
            return $this->getRow($sql);
        }

        function initPublication(){
            $desc = $_REQUEST["desc"];
            $sql = "INSERT INTO tblPublication(regDate, `desc`) VALUES(NOW(), '{$desc}')";
            $this->update($sql);
            return $this->makeResultJson(1, "succ", $this->mysql_insert_id());
        }

        function upsertPublication(){
            $check = getimagesize($_FILES["imgFile"]["tmp_name"]);
            $check2 = getimagesize($_FILES["imgFileIntro"]["tmp_name"]);

            $id = $_REQUEST["id"];
            $langCode = $_REQUEST["langCode"];
            $name = $_REQUEST["name"];
            $price = $_REQUEST["price"];
            $discounted = $_REQUEST["discounted"];
            $subTitle = $_REQUEST["subTitle"];
            $description = nl2br($_REQUEST["description"]);
            $exposure = $_REQUEST["exposure"] == "" ? "0" : "1";

            $imgPath = NULL;
            $imgPathIntro = NULL;

            if($check !== false){
                $fName = $this->makeFileName() . "." . pathinfo(basename($_FILES["imgFile"]["name"]),PATHINFO_EXTENSION);
                $targetDir = $this->filePath . $fName;
                if(move_uploaded_file($_FILES["imgFile"]["tmp_name"], $targetDir)) $imgPath = $fName;
                else return $this->makeResultJson(-1, "fail");
            }
            else $imgPath = $_REQUEST["imgPath"];

            if($check2 !== false){
                $fName = $this->makeFileName() . "." . pathinfo(basename($_FILES["imgFileIntro"]["name"]),PATHINFO_EXTENSION);
                $targetDir = $this->filePath . $fName;
                if(move_uploaded_file($_FILES["imgFileIntro"]["tmp_name"], $targetDir)) $imgPathIntro = $fName;
                else return $this->makeResultJson(-2, "fail");
            }
            else $imgPathIntro = $_REQUEST["imgPathIntro"];

            $sql = "
                INSERT INTO tblPublicationLang(`publicationId`, `langCode`, `name`, `price`, `discounted`, `imgPath`, `subTitle`, `description`, `imgPathIntro`, `exposure`, `regDate`)
                VALUES(
                  '{$id}',
                  '{$langCode}',
                  '{$name}',
                  '{$price}',
                  '{$discounted}',
                  '{$imgPath}',
                  '{$subTitle}',
                  '{$description}',
                  '{$imgPathIntro}',
                  '{$exposure}',
                  NOW()
                )
                ON DUPLICATE KEY UPDATE
                `name` = '{$name}',
                `price` = '{$price}',
                `discounted` = '{$discounted}',
                `imgPath` = '{$imgPath}',
                `subTitle` = '{$subTitle}',
                `description` = '{$description}',
                `imgPathIntro` = '{$imgPathIntro}',        
                `exposure` = '{$exposure}'
            ";
            $this->update($sql);
            return $this->makeResultJson(1, "succ", $id);
        }

        function faqList(){
            $sql = "SELECT * FROM tblFaq ORDER BY regDate DESC";
            return $this->getArray($sql);
        }

        function faqDetail(){
            $id = $_REQUEST["id"];
            $langCode = $_REQUEST["langCode"];
            $sql = "
              SELECT * FROM tblFaqLang WHERE faqId = '{$id}' AND `langCode` = '{$langCode}' LIMIT 1
            ";
            return $this->getRow($sql);
        }

        function initFaq(){
            $desc = $_REQUEST["desc"];
            $sql = "INSERT INTO tblFaq(regDate, `desc`) VALUES(NOW(), '{$desc}')";
            $this->update($sql);
            return $this->makeResultJson(1, "succ", $this->mysql_insert_id());
        }

        function initNation(){
            $cont = $_REQUEST["fContinent"];
            $desc = $_REQUEST["desc"];
            $sql = "INSERT INTO tblNationGroup(`fContinent`, `desc`) VALUES('{$cont}', '{$desc}')";
            $this->update($sql);
            return $this->makeResultJson(1, "succ", $this->mysql_insert_id());
        }

        function initSupport(){
            $nationId = $_REQUEST["nationId"];
            $desc = $_REQUEST["desc"];
            $sql = "INSERT INTO tblSupportParent(`nationId`, `title`, `regDate`) VALUES('{$nationId}', '{$desc}', NOW())";
            $this->update($sql);
            return $this->makeResultJson(1, "succ", $this->mysql_insert_id());
        }

        function getNationName(){
            $nid = $_REQUEST["nid"];
            $loc = $_REQUEST["loc"];
            $sql = "SELECT * FROM tblNationLang WHERE `nationId` = '{$nid}' AND `lang` = '{$loc}'";
            return json_encode($this->getRow($sql));
        }

        function setNationName(){
            $lang = $_REQUEST["lang"];
            $nationId = $_REQUEST["nationId"];
            $name = $_REQUEST["name"];
            $sql = "INSERT INTO tblNationLang(`lang`, `nationId`, `name`) VALUES('{$lang}', '{$nationId}', '{$name}')
                    ON DUPLICATE KEY UPDATE `name` = '{$name}'";
            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

        function deleteSupport(){
            $id = $_REQUEST["id"];
            $sql = "DELETE FROM tblSupportParent WHERE `id`='{$id}'";
            $this->update($sql);
            $sql = "DELETE FROM tblSupportArticle WHERE `parentId` ='{$id}'";
            $this->update($sql);

            return $this->makeResultJson(1, "succ");
        }

        function deleteNation(){
            $id = $_REQUEST["id"];
            $sql = "DELETE FROM tblNationGroup WHERE `id`='{$id}'";
            $this->update($sql);
            $sql = "DELETE FROM tblNationLang WHERE `nationId` ='{$id}'";
            $this->update($sql);
            $sql = "DELETE FROM tblSupportArticle WHERE parentId IN (SELECT `id` FROM tblSupportParent WHERE nationId='{$id}')";
            $this->update($sql);
            $sql = "DELETE FROM tblSupportParent WHERE `nationId` ='{$id}'";
            $this->update($sql);

            return $this->makeResultJson(1, "succ");
        }

        function upsertFaq(){
            $id = $_REQUEST["id"];
            $langCode = $_REQUEST["langCode"];
            $question = $_REQUEST["question"];
            $content = nl2br($_REQUEST["content"]);
            $exposure = $_REQUEST["exposure"] == "" ? "0" : "1";

            $sql = "
                INSERT INTO tblFaqLang(`faqId`, `langCode`, `question`, `content`, `exposure`, `regDate`)
                VALUES(
                  '{$id}',
                  '{$langCode}',
                  '{$question}',
                  '{$content}',
                  '{$exposure}',
                  NOW()
                )
                ON DUPLICATE KEY UPDATE
                `question` = '{$question}',
                `content` = '{$content}',
                `exposure` = '{$exposure}'
            ";

            $this->update($sql);
            return $this->makeResultJson(1, "succ");
        }

    }


}