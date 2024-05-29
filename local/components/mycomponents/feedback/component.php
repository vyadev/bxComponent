<?php
if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

$arResult["PARAMS_HASH"] = md5(serialize($arParams).$this->GetTemplateName());

$arParams["USE_CAPTCHA"] = (($arParams["USE_CAPTCHA"] != "N" && !$USER->IsAuthorized()) ? "Y" : "N");
$arParams["EVENT_NAME"] = trim($arParams["EVENT_NAME"]);
if($arParams["EVENT_NAME"] == '')
	$arParams["EVENT_NAME"] = "FEEDBACK_FORM";
$arParams["EMAIL_TO"] = trim($arParams["EMAIL_TO"]);
if($arParams["EMAIL_TO"] == '')
	$arParams["EMAIL_TO"] = COption::GetOptionString("main", "email_from");
$arParams["OK_TEXT"] = trim($arParams["OK_TEXT"]);
if($arParams["OK_TEXT"] == '')
	$arParams["OK_TEXT"] = GetMessage("MF_OK_MESSAGE");

if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["submit"] <> '' && (!isset($_POST["PARAMS_HASH"]) || $arResult["PARAMS_HASH"] === $_POST["PARAMS_HASH"]))
{
	$arResult["ERROR_MESSAGE"] = array();
	if(check_bitrix_sessid())
	{
		if(empty($arParams["REQUIRED_FIELDS"]) || !in_array("NONE", $arParams["REQUIRED_FIELDS"]))
		{
			if((empty($arParams["REQUIRED_FIELDS"]) || in_array("NAME", $arParams["REQUIRED_FIELDS"])) && strlen($_POST["user_name"]) <= 1)
				$arResult["ERROR_MESSAGE"][] = GetMessage("MF_REQ_NAME");

      if((empty($arParams["REQUIRED_FIELDS"]) || in_array("PHONE", $arParams["REQUIRED_FIELDS"])) && empty($_POST["user_phone"]))
        $arResult["ERROR_MESSAGE"][] = GetMessage("MF_REQ_PHONE");

			if((empty($arParams["REQUIRED_FIELDS"]) || in_array("MESSAGE", $arParams["REQUIRED_FIELDS"])) && strlen($_POST["MESSAGE"]) <= 3)
				$arResult["ERROR_MESSAGE"][] = GetMessage("MF_REQ_MESSAGE");

			if((empty($arParams["REQUIRED_FIELDS"]) || in_array("FILES", $arParams["REQUIRED_FIELDS"])) && empty($_FILES['file']['name'][0]))
				$arResult["ERROR_MESSAGE"][] = GetMessage("MF_REQ_FILES");
		}

//		if($arParams["USE_CAPTCHA"] == "Y")
//		{
//			$captcha_code = $_POST["captcha_sid"];
//			$captcha_word = $_POST["captcha_word"];
//			$cpt = new CCaptcha();
//			$captchaPass = COption::GetOptionString("main", "captcha_password", "");
//			if (strlen($captcha_word) > 0 && strlen($captcha_code) > 0)
//			{
//				if (!$cpt->CheckCodeCrypt($captcha_word, $captcha_code, $captchaPass))
//					$arResult["ERROR_MESSAGE"][] = GetMessage("MF_CAPTCHA_WRONG");
//			}
//			else
//				$arResult["ERROR_MESSAGE"][] = GetMessage("MF_CAPTHCA_EMPTY");
//
//		}

		if(empty($arResult["ERROR_MESSAGE"]))
		{
			$arFields = Array(
				"AUTHOR" => $_POST["user_name"],
        "AUTHOR_PHONE" => $_POST["user_phone"],
				"EMAIL_TO" => $arParams["EMAIL_TO"],
				"TEXT" => $_POST["MESSAGE"],
				//"FILES" => $_FILES['file'],
			);


        /** сохранение данных в инфоблок **/
// подключаем модуль инфоблоков
        CModule::IncludeModule('iblock');
// инициализируем
        $elem = new CIBlockElement;
// создаем пустой массив и собираем в него поля
        $PROP = array();
       // $PROP['NAME'] = $_POST['user_name'];
        $PROP['PHONE'] = $_POST['user_phone'];
        $PROP['TEXT'] = $_POST['MESSAGE'];

        $arF = array();   // пустой массив для сбора id файлов
        $count = count($_FILES['file']['name']);  // количество файлов
// соханяем файлы и получаем id
        for($i = 0; $i < $count; $i++) {
          $arIMAGE["name"] = $_FILES['file']['name'][$i];
          $arIMAGE["size"] = $_FILES['file']['size'][$i];
          $arIMAGE["tmp_name"] = $_FILES['file']['tmp_name'][$i];
          $arIMAGE["type"] = $_FILES['file']['type'][$i];
          $arIMAGE["MODULE_ID"] = "vote";
          $fid = CFile::SaveFile($arIMAGE, "vote");
          $arF[] = $fid;
        }
// все id файлов присваиваем свойству
        $PROP['FILES'] = $arF;

// настройки
        $arLoadProductArray = Array(
          "MODIFIED_BY"    => 1,
          "IBLOCK_SECTION_ID" => false,    // элемент лежит в корне раздела
          "IBLOCK_ID"      => $arParams["IBLOCK_ID"],    // ID инфоблока
          "PROPERTY_VALUES"=> $PROP,       // массив со свойствами
          "NAME"           => $_POST['user_name'],      // имя записи
          "ACTIVE"         => "Y",
        );
// сохраняем
      $PRODUCT_ID = $elem->Add($arLoadProductArray);

      if(!empty($arParams["EVENT_MESSAGE_ID"]))
      {
        foreach($arParams["EVENT_MESSAGE_ID"] as $v)
          if(IntVal($v) > 0)
            CEvent::Send($arParams["EVENT_NAME"], SITE_ID, $arFields, "N", IntVal($v));
      } else {
        CEvent::Send($arParams["EVENT_NAME"], SITE_ID, $arFields);
      }


			$_SESSION["MF_NAME"] = htmlspecialcharsbx($_POST["user_name"]);
      $_SESSION["MF_PHONE"] = htmlspecialcharsbx($_POST["user_phone"]);
			LocalRedirect($APPLICATION->GetCurPageParam("success=".$arResult["PARAMS_HASH"], Array("success")));
		}
		
		$arResult["MESSAGE"] = htmlspecialcharsbx($_POST["MESSAGE"]);
		$arResult["FILES"] = $_FILES['file']['name'];
		$arResult["AUTHOR_NAME"] = htmlspecialcharsbx($_POST["user_name"]);
    $arResult["AUTHOR_PHONE"] = htmlspecialcharsbx($_POST["user_phone"]);
	}
	else
		$arResult["ERROR_MESSAGE"][] = GetMessage("MF_SESS_EXP");
}
elseif($_REQUEST["success"] == $arResult["PARAMS_HASH"])
{
	$arResult["OK_MESSAGE"] = $arParams["OK_TEXT"];
}

//if(empty($arResult["ERROR_MESSAGE"]))
//{
//	if($USER->IsAuthorized())
//	{
//		$arResult["AUTHOR_NAME"] = $USER->GetFormattedName(false);
//		$arResult["AUTHOR_EMAIL"] = htmlspecialcharsbx($USER->GetEmail());
//	}
//	else
//	{
//		if(strlen($_SESSION["MF_NAME"]) > 0)
//			$arResult["AUTHOR_NAME"] = htmlspecialcharsbx($_SESSION["MF_NAME"]);
//		if(strlen($_SESSION["MF_EMAIL"]) > 0)
//			$arResult["AUTHOR_EMAIL"] = htmlspecialcharsbx($_SESSION["MF_EMAIL"]);
//	}
//}

//if($arParams["USE_CAPTCHA"] == "Y")
//	$arResult["capCode"] =  htmlspecialcharsbx($APPLICATION->CaptchaGetCode());



$this->IncludeComponentTemplate();
