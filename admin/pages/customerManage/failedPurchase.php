<?php
/**
 * Created by PhpStorm.
 * User: sayho
 * Date: 2018. 7. 27.
 * Time: PM 4:20
 */
?>

<? include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/inc/header.php"; ?>
<? include $_SERVER["DOCUMENT_ROOT"] . "/common/classes/Management.php";?>
<?
    $obj = new Management($_REQUEST);
//    $obj->processFC();
//    $obj->processBA();
    $list = $obj->paymentList();
    $type = $_REQUEST["type"];
?>
<link rel="stylesheet" href="/admin/scss/smSheet.css">
<script>
    $(document).ready(function(){
        $(".jType").click(function(){
            var type = $(this).val();
            var year = "<?=$_REQUEST["year"] == "" ? intval(date("Y")) : $_REQUEST["year"]?>";
            var month = "<?=$_REQUEST["month"] == "" ? date("m") : $_REQUEST["month"]?>";
            location.href = "/admin/pages/customerManage/failedPurchase.php?type=" + type
                + "&year=" + year + "&month=" + month;
        });

        $(document).on("click", ".jChange", function(e){
            var id = $(this).attr("id");
            var res = $(this).attr("flag");
            var ajax = new AjaxSender("/route.php?cmd=Management.changePaymentStatus", true, "json", new sehoMap().put("id", id).put("res", res));
            ajax.send(function(data){
                if(data.returnCode === 1){
                    alert("변경되었습니다.");
                    location.reload();
                }
            })
        });

        $(document).on("click", ".jFlag", function(e){
            var id = $(this).attr("id");
            var res = $(this).attr("flag");
            var ajax = new AjaxSender("/route.php?cmd=Management.changePaymentFlag", true, "json", new sehoMap().put("id", id).put("res", res));
            ajax.send(function(data){
                if(data.returnCode === 1){
                    // alert("변경되었습니다.");
                    location.reload();
                }
            })
        });

        $("#jYear").change(function(){
            changeRange();
        });

        $("#jMonth").change(function(){
            changeRange();
        });

        function changeRange(){
            var year = $("#jYear").val();
            var month = $("#jMonth").val();
            location.href = "/admin/pages/customerManage/failedPurchase.php?type=<?=$_REQUEST["type"]?>&year="+year+"&month=" + month;
        }

        $(".jAlterExcel").click(exportExcel);

        function exportExcel(){

            var target = $("table");
            $(".jsss").empty();
            $(".jssss").empty();

            if($(".alterTarget").length > 0) target = $(".alterTarget").eq(0);
            if(target.length < 1) alert("출력 대상이 없습니다.");
            var divToPrint= target.eq(0);
            var optionCss = "";//"#toPrint{width : 210mm;}";
            var htmls = "<style>" + optionCss + "</style>" + divToPrint.prop("outerHTML");

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

            var ctx = {
                worksheet : 'Worksheet',
                table : htmls
            }

            var link = document.createElement("a");
            link.download = "export.xls";
            link.href = uri + base64(format(template, ctx));
            link.click();

            location.reload();
        }

        $(".jRefresh").click(function(){
            var ajax = new AjaxSender("/route.php?cmd=Management.processBA", false, "json", new sehoMap());
            ajax.send(function(data){
                if(data.returnCode === 1){
                    var innerAjax = new AjaxSender("/route.php?cmd=Management.processFC", false, "json", new sehoMap());
                    innerAjax.send(function(data){
                        if(data.returnCode === 1){
                            alert("갱신되었습니다.");
                            location.reload();
                        }
                    });
                }
            })
        });

        $(".jView").click(function(){
            var id = $(this).parent().attr("id")
           location.href = "/admin/pages/customerManage/customerDetail.php?id=" + id;
        });

        $(".jUploadExcel").click(function(){
            $("[name=docFile]").click();
        });

        $("[name=docFile]").change(function(){
            if($(this).val() != ""){
                $("#spinner").fadeIn();
                var ajax = new AjaxSubmit("/route.php?cmd=ExcelParser.parsePaymentList", "post", true, "json", "#form");
                ajax.send(function(data){
                    if(data.returnCode === 1){
                        alert("저장되었습니다.");
                    }else if(data.returnCode == -1){
                        var err = data.entity;
                        if(err == null || err == "") err = "#";
                        alert("파일을 읽는 중 오류가 발생하였습니다. (" + err + ")");
                    }
                    $("#spinner").fadeOut();
                });
            }
        });

    });
</script>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a>고객관리</a>
            </li>
            <li class="breadcrumb-item active">결제관리</li>
        </ol>

        <p class="mt-2">※ 취소여부는 관리 편의 및 통계 집계를 위한 것으로 타 데이터 및 결제사와 연동되지 않습니다.</p>

        <select class="custom-select jOpt" id="jYear" style="width: 20%">
            <?for($e = 1950; $e < intval(date("Y")) + 50; $e++){?>
                <option value="<?=$e?>" <?=$e == intval($_REQUEST["year"]) ? "SELECTED" : ""?>><?=$e?>년</option>
            <?}?>
        </select>
        <select class="custom-select jOpt" id="jMonth" style="width: 20%">
            <?for($e = 1; $e <= 12; $e++){?>
                <option value="<?=$e < 10 ? "0".$e : $e?>" <?=$e == intval($_REQUEST["month"]) ? "SELECTED" : ""?>><?=$e < 10 ? "0".$e : $e?>월</option>
            <?}?>
        </select>

        <br/><br/>

        <form id="form">
            <input type="hidden" name="page" />
            <input type="file" name="docFile" style="display: none;"/>
            <div class="btn-group float-left" role="group">
                <button type="button" class="btn jType <?=$type == "BA" ? "btn-secondary" : ""?>" value="BA">자동이체</button>
                <button type="button" class="btn jType <?=$type == "FC" ? "btn-secondary" : ""?>" value="FC">해외카드</button>
                <button type="button" class="btn jType <?=$type == "CC" ? "btn-secondary" : ""?>" value="CC">직접관리</button>
            </div>

            <div class="btn-group float-right mb-2" role="group" aria-label="Basic example">
                <button type="button" class="btn btn-primary jRefresh">자동이체/해외카드 새로고침</button>
                <button type="button" class="btn btn-secondary jUploadExcel"><i class="fas fa-upload fa-fw"></i>Excel</button>
                <button type="button" class="btn btn-secondary jAlterExcel"><i class="fas fa-download fa-fw"></i>Excel</button>
            </div>
        </form>

        <div style="width: 100%; height: 600px; overflow-y: scroll">
            <?if($type == "BA"){?>
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>이름</th>
                        <th>회원상태</th>
                        <th>CMS상태</th>
                        <th>은행</th>
                        <th>계좌번호</th>
                        <th>외부참조키</th>
                        <th>출금일</th>
                        <th>출금금액</th>
                        <th>등록일시</th>
                        <th>처리현황</th>
                        <th>취소여부</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?foreach($list as $item){?>
                        <tr id="<?=$item["customerId"]?>">
                            <td class="jView"><?=$item["ownerName"]?></td>
                            <td class="jView"><?=$item["primeRes"]["memberStatus"] == "1" ? "정상" : "해지"?></td>
                            <td class="jView">
                                <?
                                    if($item["primeRes"]["chargeRes"] != 1) echo "출금불능";
                                    else echo "<br>출금완료";
                                    if($item["primeRes"]["bankRes"] != 1) echo "<br>해지";
                                ?>
                            </td>
                            <td class="jView"><?=$item["bankDesc"]?></td>
                            <td  class="jView" style='mso-number-format:"\@"'><?=$item["info"]?></td>
                            <td class="jView"><?=$item["primeIndex"]?></td>
                            <td class="jView"><?=$item["monthlyDate"]?></td>
                            <td class="jView"><?=$item["totalPrice"]?></td>
                            <td class="jView"><?=$item["pRegDate"]?></td>
                            <td>
                                <button type="button" class="btn btn-sm <?
                                switch($item["paymentResult"]){
                                    case "0":
                                        echo "btn-danger";
                                        break;
                                    case "1":
                                        echo "btn-primary";
                                        break;
                                    case "2":
                                        echo "btn-success";
                                        break;
                                }
                                ?> dropdown-toggle" data-toggle="dropdown">
                                    <?
                                    switch($item["paymentResult"]){
                                        case "0":
                                            echo "미결제";
                                            break;
                                        case "2":
                                            echo "처리중";
                                            break;
                                        case "1":
                                            echo "완료";
                                            break;
                                    }
                                    ?>
                                </button>
                                <div class="dropdown-menu jsss">
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="0">미결제</a>
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="1">완료</a>
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="2">처리중</a>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm <?
                                switch($item["flag"]){
                                    case "1":
                                        echo "btn-danger";
                                        break;
                                    case "0":
                                        echo "btn-success";
                                        break;
                                }
                                ?> dropdown-toggle" data-toggle="dropdown">
                                    <?
                                    switch($item["flag"]){
                                        case "0":
                                            echo "정상";
                                            break;
                                        case "1":
                                            echo "취소됨";
                                            break;
                                    }
                                    ?>
                                </button>
                                <div class="dropdown-menu jFlag">
                                    <a class="dropdown-item jFlag" id="<?=$item["idx"]?>" flag="0">정상</a>
                                    <a class="dropdown-item jFlag" id="<?=$item["idx"]?>" flag="1">취소됨</a>
                                </div>
                            </td>
                        </tr>
                    <?}?>
                    </tbody>
                </table>
            <?} else if($type == "CC"){?>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>계좌/카드 소유주</th>
                        <th>카드사</th>
                        <th>카드번호</th>
                        <th>유효월 / 유효년</th>
                        <th>출금일</th>
                        <th>출금금액</th>
                        <th>등록일시</th>
                        <th>처리현황</th>
                        <th>취소여부</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?foreach($list as $item){?>
                        <tr id="<?=$item["customerId"]?>">
                            <td class="jView"><?=$item["ownerName"]=="" ? "(미입력)" : $item["ownerName"]?></td>
                            <td class="jView"><?=$item["cardDesc"]?><?if($item["cardTypeId"]==-1) echo "(직접입금)";?></td>
                            <td class="jView" style='mso-number-format:"\@"'><?=$item["cardTypeId"]==-1 ? "" : $item["info"]?></td>
                            <td class="jView"><?=$item["cardTypeId"]==-1 ? "" : $item["validThruMonth"] . " / " . $item["validThruYear"]?></td>
                            <td class="jView"><?=$item["monthlyDate"]?></td>
                            <td class="jView"><?=$item["totalPrice"]?></td>
                            <td class="jView"><?=$item["pRegDate"]?></td>
                            <td>
                                <button type="button" class="btn btn-sm <?
                                switch($item["paymentResult"]){
                                    case "0":
                                        echo "btn-danger";
                                        break;
                                    case "1":
                                        echo "btn-primary";
                                        break;
                                    case "2":
                                        echo "btn-success";
                                        break;
                                }
                                ?> dropdown-toggle" data-toggle="dropdown">
                                    <?
                                    switch($item["paymentResult"]){
                                        case "0":
                                            echo "미결제";
                                            break;
                                        case "2":
                                            echo "처리중";
                                            break;
                                        case "1":
                                            echo "완료";
                                            break;
                                    }
                                    ?>
                                </button>
                                <div class="dropdown-menu jsss">
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="0">미결제</a>
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="1">완료</a>
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="2">처리중</a>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm <?
                                switch($item["flag"]){
                                    case "1":
                                        echo "btn-danger";
                                        break;
                                    case "0":
                                        echo "btn-success";
                                        break;
                                }
                                ?> dropdown-toggle" data-toggle="dropdown">
                                    <?
                                    switch($item["flag"]){
                                        case "0":
                                            echo "정상";
                                            break;
                                        case "1":
                                            echo "취소됨";
                                            break;
                                    }
                                    ?>
                                </button>
                                <div class="dropdown-menu jFlag">
                                    <a class="dropdown-item jFlag" id="<?=$item["idx"]?>" flag="0">정상</a>
                                    <a class="dropdown-item jFlag" id="<?=$item["idx"]?>" flag="1">취소됨</a>
                                </div>
                            </td>
                        </tr>
                    <?}?>
                    </tbody>
                </table>
            <?} else if($type == "FC"){?>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>이름</th>
                        <th>카드번호</th>
                        <th>유효월 / 유효년</th>
                        <th>출금일</th>
                        <th>출금금액</th>
                        <th>등록일시</th>
                        <th>처리현황</th>
                        <th>취소여부</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?foreach($list as $item){?>
                        <tr id="<?=$item["customerId"]?>">
                            <td class="jView"><?=$item["aFirstname"] . " " . $item["aLastname"]?></td>
                            <td class="jView" style='mso-number-format:"\@"'><?=$item["info"]?></td>
                            <td class="jView"><?=$item["validThruMonth"] . " / " . $item["validThruYear"]?></td>
                            <td class="jView"><?=$item["monthlyDate"]?></td>
                            <td class="jView"><?=$item["totalPrice"]?></td>
                            <td class="jView"><?=$item["pRegDate"]?></td>
                            <td>
                                <button type="button" class="btn btn-sm <?
                                switch($item["paymentResult"]){
                                    case "0":
                                        echo "btn-danger";
                                        break;
                                    case "1":
                                        echo "btn-primary";
                                        break;
                                    case "2":
                                        echo "btn-success";
                                        break;
                                }
                                ?> dropdown-toggle" data-toggle="dropdown">
                                    <?
                                    switch($item["paymentResult"]){
                                        case "0":
                                            echo "미결제";
                                            break;
                                        case "2":
                                            echo "처리중";
                                            break;
                                        case "1":
                                            echo "완료";
                                            break;
                                    }
                                    ?>
                                </button>
                                <div class="dropdown-menu jsss">
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="0">미결제</a>
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="1">완료</a>
                                    <a class="dropdown-item jChange" id="<?=$item["idx"]?>" flag="2">처리중</a>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm <?
                                switch($item["flag"]){
                                    case "1":
                                        echo "btn-danger";
                                        break;
                                    case "0":
                                        echo "btn-success";
                                        break;
                                }
                                ?> dropdown-toggle" data-toggle="dropdown">
                                    <?
                                    switch($item["flag"]){
                                        case "0":
                                            echo "정상";
                                            break;
                                        case "1":
                                            echo "취소됨";
                                            break;
                                    }
                                    ?>
                                </button>
                                <div class="dropdown-menu jssss">
                                    <a class="dropdown-item jFlag" id="<?=$item["idx"]?>" flag="0">정상</a>
                                    <a class="dropdown-item jFlag" id="<?=$item["idx"]?>" flag="1">취소됨</a>
                                </div>
                            </td>
                        </tr>
                    <?}?>
                    </tbody>
                </table>
            <?}?>
        </div>
    </div>
</div>


<? include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/inc/footer.php"; ?>
