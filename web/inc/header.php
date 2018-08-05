<?php
/**
 * Created by PhpStorm.
 * User: sayho
 * Date: 2018. 8. 3.
 * Time: PM 5:03
 */
?>

<? include $_SERVER["DOCUMENT_ROOT"] . "/common/classes/WebUser.php";?>
<? include $_SERVER["DOCUMENT_ROOT"] . "/web/inc/language.php";?>
<?
    $url = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>BibleTime</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="/web/assets/css/main.css" />

    <script src="/web/assets/js/jquery.min.js"></script>
    <script src="/web/assets/js/skel.min.js"></script>
    <script src="/web/assets/js/util.js"></script>
    <script src="/web/assets/js/main.js"></script>

    <script type="text/javascript" src="/modules/ajaxCall/ajaxClass.js"></script>
    <script type="text/javascript" src="/modules/sehoMap/sehoMap.js"></script>
</head>
<body class="subpage">

<script>
    $(document).ready(function(){
        var url = window.location.pathname;
        $(".headerMenu").each(function(){
            $(this).removeClass("selected");
            var match = $(this).attr("match");
            if(url.includes(match)) $(this).addClass("selected");
        });

        if(url === "/web/pages/") $(".headerMenu").eq(0).addClass("selected");
    });
</script>

<!-- Header -->
<header id="header">
    <div class="inner">
        <a href="/web" class="logo"><?=$HEADER_ELEMENTS["webTitle"]?></a>
        <nav id="nav">
            <a class="selected headerMenu" href="/web"><?=$HEADER_ELEMENTS["headerMenu_home"]?></a>
            <a class="headerMenu" match="/web/pages/introduction.php" href="/web/pages/introduction.php"><?=$HEADER_ELEMENTS["headerMenu_introduce"]?></a>
            <a class="headerMenu" match="/web/pages/subscription.php" href="/web/pages/subscription.php"><?=$HEADER_ELEMENTS["headerMenu_subscribe"]?></a>
            <a class="headerMenu" match="/web/pages/contribution.php" href="/web/pages/contribution.php"><?=$HEADER_ELEMENTS["headerMenu_support"]?></a>
            <a class="headerMenu" match="/web/pages/donation.php" href="/web/pages/donation.php"><?=$HEADER_ELEMENTS["headerMenu_share"]?></a>
            <a class="headerMenu" match="/web/pages/faq.php" href="/web/pages/faq.php"><?=$HEADER_ELEMENTS["headerMenu_faq"]?></a>
        </nav>
        <div class="rightBox">
            <a class="langBtn" href="#"><img src="/web/images/lang_ko.png" />KO | </a>
            <a class="langBtn" href="#"><img src="/web/images/lang_en.png" />EN | </a>
            <a class="langBtn" href="#"><img src="/web/images/lang_es.png" />ES | </a>
            <a class="langBtn" href="#"><img src="/web/images/lang_zh.png" />ZH</a>

            <a class="link" href="/web/pages/mypage.php">마이페이지</a>
        </div>
        <a href="#navPanel" class="navPanelToggle"><span class="fa fa-bars"></span></a>
    </div>
</header>



