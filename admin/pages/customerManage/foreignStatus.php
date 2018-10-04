<?php
/**
 * Created by PhpStorm.
 * User: sayho
 * Date: 2018. 7. 30.
 * Time: PM 3:10
 */
?>

<? include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/inc/header.php"; ?>
<? include $_SERVER["DOCUMENT_ROOT"] . "/common/classes/AdminMain.php";?>
<? include $_SERVER["DOCUMENT_ROOT"] . "/common/classes/Management.php";?>
<?
    $management = new Management($_REQUEST);
    $list = $management->foreignPubList();
//    echo json_encode($list);
?>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
    $(document).ready(function(){
        var addPop = $("#jAddPop");
        addPop.draggable();

        $("#jYear").change(function(){$("#yForm").submit();});

        $(".jAdd").click(function(){addPop.fadeIn();});

        $(".jClosePop").click(function(){addPop.fadeOut();});

        $(".jSave").click(function(){
            var params = new sehoMap().put("year", $("#aYear").val()).put("print", $("#aPrint").val()).put("country", $("#aCountry").val())
                .put("language", $("#aLanguage").val()).put("text", $("#aText").val());
            var ajax = new AjaxSender("/route.php?cmd=Management.addForeignPub", true, "json", params);
            ajax.send(function(data){
                if(data.returnCode === 1){
                    alert("저장되었습니다.");
                    location.reload();
                }
            });
        });

        $(".jAddChild").click(function(){
            var id = $(this).attr("id");
            location.href = "/admin/pages/customerManage/foreignHistory.php?parentId=" + id;
        });

        $(".jHistory").click(function(){
            var id = $(this).attr("id");
            var parentId = $(this).attr("parentId");
            location.href = "/admin/pages/customerManage/foreignHistory.php?id=" + id + "&parentId=" + parentId;
        });

        $(".jModStatus").click(function(){
            var id = $(this).attr("id");
            var next = $(this).attr("next");
            if(confirm("상태를 변경하시겠습니까?")){
                var ajax = new AjaxSender("/route.php?cmd=Management.changeFpubStatus", true, "json", new sehoMap().put("id", id).put("next", next));
                ajax.send(function(data){
                    if(data.returnCode === 1){
                        alert("변경되었습니다");
                        location.reload();
                    }
                })
            }
        });

        function exportToExcel(htmls){

            var uri = 'data:application/vnd.ms-excel;base64,';
            var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta charset="utf-8"></head><body><table>{table}</table></body></html>';
            var base64 = function(s) {
                return window.btoa(unescape(encodeURIComponent(s)))
            };

            var format = function(s, c) {
                return s.replace(/{(\w+)}/g, function(m, p) {
                    return c[p];
                })
            };

//            htmls = "YOUR HTML AS TABLE"

            var ctx = {
                worksheet : 'Worksheet',
                table : htmls
            };

            var isIE = false;
            if (navigator.userAgent.indexOf('MSIE') !== -1 || navigator.appVersion.indexOf('Trident/') > 0 || window.navigator.userAgent.indexOf("Edge") > -1) {
                isIE = true;
            }
            var link = document.createElement("a");
            link.download = "해외진행현황.xls";
            link.href = uri + base64(format(template, ctx));
            link.click();

            // window.close();
        }

        $(".jDownExcel").click(function(){
            $.ajax({
                url : "/admin/pages/customerManage/foreignStatusExcel.php?year=<?=$_REQUEST["year"]?>",
                async : true,
                type : "get",
                dataType : "html",
                success : function(data){
                    exportToExcel(data);
                },
                error : function(){
                    alert("데이터를 불러오는 중 오류가 발생했습니다.");
                }
            });
        });

    });
</script>

<style>
    .dotWrap{
        padding-left: 5px;
        padding-right: 5px;
        float:left;
        margin-left : 5px;
        margin-right : 5px;
        height:70px;
        border-radius: 5px;
        background: lightblue;
        text-align: center;
    }

    .dotWrap h3{
        font-size: 1.5em;
    }

    .dot {
        background : green;
        border-radius: 20px;
        width : 14px;
        height : 14px;
        float : left;
        margin : 2px;
    }

    .dot.disabled{
        background: gray;
    }

    .dot:not(.text) pickle{
        display: none;
    }

    .dot.text{
        color:white;
        background : #AAAAAA;
        padding : 5px;
        line-height: 10px;
        border-radius: 3px;
        font-size : 0.8em;
        width : auto;
        margin-top: 0px;
        height : 20px;
    }
</style>

<div id="jAddPop" style="padding : 30px 30px; width : 500px;border : 1px solid black; position : absolute; left : calc(50vw - 250px); top : calc(25vh); background : white; display: none;">
    <a href="#" class="jClosePop float-right" ><img src="../siteManage/attr/btn_close.png" width="30px" height="30px" /></a>
    년도
    <br/><br/>
    <select class="form-control" id="aYear">
        <option value="">전체</option>
        <?for($i=-50; $i<50; $i++){?>
            <option value="<?=intval(date("Y")) + $i?>"><?=intval(date("Y")) + $i?></option>
        <?}?>
    </select>
    인쇄
    <br/>
    <input type="text" class="form-control" id="aPrint"/>
    국가
    <br/>
    <input type="text" class="form-control" id="aCountry"/>
    언어
    <br/>
    <input type="text" class="form-control" id="aLanguage"/>
    번역판(비고)
    <br/>
    <input type="text" class="form-control" id="aText"/>
    <br/><br/>
    <button type="button" class="btn btn-secondary mb-2 jSave">추가</button>
    <button type="button" class="btn btn-danger mb-2 jClosePop">취소</button>
</div>

<div id="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a>고객관리</a>
            </li>
            <li class="breadcrumb-item active">해외진행 현황</li>
        </ol>

        <div class="float-left mb-2">
            <form id="yForm">
                <select class="form-control" id="jYear" name="year">
                    <option value="">전체</option>
                    <?for($i=-50; $i<50; $i++){
                        $tmp = intval(date("Y")) + $i;
                     ?>
                        <option value="<?=$tmp?>" <?=$_REQUEST["year"] == $tmp ? "selected" : ""?>><?=$tmp?></option>
                    <?}?>
                </select>
            </form>
        </div>
        <div class="btn-group float-right mb-2" role="group">
            <button type="button" class="btn btn-secondary jDownExcel mr-1">Excel</button>
            <button type="button" class="btn btn-secondary jAdd">추가</button>
        </div>

        <table class="table table-bordered">
            <thead>
            <tr>
                <th class="col-sm-">인쇄</th>
                <th>국가</th>
                <th>언어</th>
                <th>진행상황</th>
                <th>번역판</th>
            </tr>
            </thead>
            <tbody>
            <?foreach($list as $item){?>
                <tr>
                    <td><?=$item["print"]?></td>
                    <td><?=$item["country"]?></td>
                    <td><?=$item["language"]?></td>
                    <td style="padding-left: 0; padding-right: 0;">
                        <?foreach($item["childList"] as $cItem){?>
                            <div class="dotWrap">
                                <h3><?=$cItem["startMonth"]?>-<?=$cItem["endMonth"]?>월 <i class="fas fa-fw fa-list jHistory" id="<?=$cItem["id"]?>" parentId="<?=$item["id"]?>"></i></h3>
                                <div class="dot <?=$cItem["manufactureFlag"] == 0 ? "text" : ""?> <?=$cItem["manufactureFlag"] < 1 ? "disabled" : ""?>">
                                    <pickle class="jModStatus" id="<?=$cItem["id"]?>" next="1">번역</pickle>
                                </div>
                                <div class="dot <?=$cItem["manufactureFlag"] == 1 ? "text" : ""?> <?=$cItem["manufactureFlag"] < 2 ? "disabled" : ""?>">
                                    <pickle class="jModStatus" id="<?=$cItem["id"]?>" next="2">데이터</pickle>
                                </div>
                                <div class="dot <?=$cItem["manufactureFlag"] == 2 ? "text" : ""?> <?=$cItem["manufactureFlag"] < 3 ? "disabled" : ""?>">
                                    <pickle class="jModStatus" id="<?=$cItem["id"]?>" next="3">인쇄</pickle>
                                </div>
                                <div class="dot <?=$cItem["manufactureFlag"] == 3 ? "text" : ""?> <?=$cItem["manufactureFlag"] < 4 ? "disabled" : ""?>">
                                    <pickle class="jModStatus" id="<?=$cItem["id"]?>" next="4">배송</pickle>
                                </div>
                            </div>
                        <?}?>
                        <button type="button" class="btn btn-secondary float-left mt-3 jAddChild" id="<?=$item["id"]?>">+</button>
                    </td>
                    <td><?=$item["text"]?></td>
                </tr>
            <?}?>
            </tbody>
        </table>
    </div>
</div>

<? include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/inc/footer.php"; ?>
