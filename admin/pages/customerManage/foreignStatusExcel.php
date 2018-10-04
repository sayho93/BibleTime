
<? include $_SERVER["DOCUMENT_ROOT"] . "/common/classes/AdminMain.php";?>
<? include $_SERVER["DOCUMENT_ROOT"] . "/common/classes/Management.php";?>
<?
    $management = new Management($_REQUEST);
    $list = $management->foreignPubList();
//    echo json_encode($list);
?>

<table class="table table-bordered" border="1">
    <thead>
    <tr>
        <th rowspan="2" class="col-sm-">인쇄</th>
        <th rowspan="2">국가</th>
        <th rowspan="2">언어</th>
        <th colspan="18">진행상황</th>
        <th rowspan="2">번역판</th>
    </tr>
    <tr>
        <th>월호</th>
        <th>ND</th>
        <th>구분</th>
        <th>수량</th>
        <th>인쇄거래처</th>
        <th>인쇄비</th>
        <th>배송비</th>
        <th>합계</th>
        <th>번역(예정)</th>
        <th>데이터(예정)</th>
        <th>인쇄(예정)</th>
        <th>배송(예정)</th>
        <th>입금(예정)</th>
        <th>번역(완료)</th>
        <th>데이터(완료)</th>
        <th>인쇄(완료)</th>
        <th>배송(완료)</th>
        <th>입금(완료)</th>
    </tr>
    </thead>
    <tbody>
    <? foreach($list as $item){?>
        <tr>
            <td rowspan="<?=sizeof($item["childList"]) + 1?>"><?=$item["print"]?></td>
            <td rowspan="<?=sizeof($item["childList"]) + 1?>"><?=$item["country"]?></td>
            <td rowspan="<?=sizeof($item["childList"]) + 1?>"><?=$item["language"]?></td>
            <?if(sizeof($item["childList"]) == 0){?>
                <th colspan="18">진행상황 없음</th>
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
<!--            <td></td>-->
            <?}else{?>
                <th colspan="18">진행상황 총 <?=sizeof($item["childList"])?>개 항목</th>
            <?}?>
            <td rowspan="<?=sizeof($item["childList"]) + 1?>"><?=$item["text"]?></td>
        </tr>
        <?foreach($item["childList"] as $cItem){?>
            <tr>
                <td><?=$cItem["startMonth"]?>-<?=$cItem["endMonth"]?></td>
                <td><?=$cItem["nd"]?></td>
                <td><?=$cItem["type"]?></td>
                <td><?=$cItem["cnt"]?></td>
                <td><?=$cItem["client"]?></td>
                <td><?=$cItem["printCharge"]?></td>
                <td><?=$cItem["deliveryCharge"]?></td>
                <td><?=$cItem["deliveryCharge"] + $cItem["printCharge"]?></td>
                <td><?=$cItem["dueDate1"]?></td>
                <td><?=$cItem["dueDate2"]?></td>
                <td><?=$cItem["dueDate3"]?></td>
                <td><?=$cItem["dueDate4"]?></td>
                <td><?=$cItem["dueDate5"]?></td>
                <td><?=$cItem["endDate1"]?></td>
                <td><?=$cItem["endDate2"]?></td>
                <td><?=$cItem["endDate3"]?></td>
                <td><?=$cItem["endDate4"]?></td>
                <td><?
                    switch ($cItem["paymentFlag"]){
                        case "0": echo "처리중"; break;
                        case "-1": echo "미결제"; break;
                        case "1": echo "완료"; break;
                    }
                    ?></td>
            </tr>
        <?}?>
    <?}?>
    </tbody>
</table>
