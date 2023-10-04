<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader,
	Bitrix\Iblock;

if (!isset($arParams['CACHE_TIME']))
{
    $arParams['CACHE_TIME'] = 3600;
}
$arParams['IBLOCK_TYPE'] = trim($arParams['IBLOCK_TYPE']);
$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);
$arParams['ELEMENTS_PER_PAGE'] = intval($arParams['ELEMENTS_PER_PAGE']);
if ($arParams['ELEMENTS_PER_PAGE'] <= 0)
{
    $arParams['ELEMENTS_PER_PAGE'] = 10;
}

$arNavParams = array(
  "nPageSize" => $arParams["ELEMENTS_PER_PAGE"],
);

$arNavigation = CDBResult::GetNavParams($arNavParams);

if ($this->StartResultCache(false,/* $USER->GetGroups(),*/ $arNavigation))
{

  if(!Loader::includeModule("iblock"))
  {
    $this->abortResultCache();
    ShowError(GetMessage("IBLOCK_MODULE_NONE"));
    return;
  }

  // проверяем пришли ли данные из формы
  if ($_SERVER["REQUEST_METHOD"] == "POST" &&
    array_key_exists("rating", $_POST) && intval($_POST["rating"]) > 0 &&
    array_key_exists("id", $_POST) && intval($_POST["id"]) > 0 &&
    array_key_exists("iblock-id", $_POST) && intval($_POST["iblock-id"]) > 0)
  {

    // создаём свойство для рейтинга пользователя
    $PROPERTY_CODE = "RATING";

    // проверяем наличие свойства рейтинга в инфоблоке
    $dbRes = CIBlockProperty::GetList(["ID" => "ASC"], [
      "IBLOCK_ID" => $arParams['IBLOCK_ID'],
      "CODE" => $PROPERTY_CODE
    ]);

    // если свойства нет - добавляем
    if (!$prop = $dbRes->GetNext())
    {
      $arFields = Array(
        "NAME" => "Текущий рейтинг",
        "CODE" => $PROPERTY_CODE,
        "DEFAULT_VALUE" => 0,
        "PROPERTY_TYPE" => 'N',
        "IBLOCK_ID" => $arParams['IBLOCK_ID'],
      );
      $ibp = new CIBlockProperty();
      $propID = $ibp->Add($arFields);
    }


    // создаём свойство для айди пользователей
    $PROPERTY_USERS_ID_CODE = "USERS_ID";

    // проверяем наличие свойства ID проголосовавшего пользователя в инфоблоке
    $dbRes = CIBlockProperty::GetList(["ID" => "ASC"], [
      "IBLOCK_ID" => $arParams['IBLOCK_ID'],
      "CODE" => $PROPERTY_USERS_ID_CODE
    ]);

    // если свойства нет - добавляем
    if (!$prop = $dbRes->GetNext())
    {
      $arFields = Array(
        "NAME" => "ID проголосовавшего пользователя",
        "CODE" => $PROPERTY_USERS_ID_CODE,
        "DEFAULT_VALUE" => 0,
        "PROPERTY_TYPE" => 'N',
        "MULTIPLE" => 'Y',
        "IBLOCK_ID" => $arParams['IBLOCK_ID'],
      );
      $ibp = new CIBlockProperty();
      $propID = $ibp->Add($arFields);
    }
  }


	// выборка данных из инфоблока
	$obNews = CIBlockElement::GetList(
		array("ID" => "desc"),
		array(
			"IBLOCK_ID" => $arParams['IBLOCK_ID'],
		 	"ACTIVE" => "Y"
		),
		false,
		$arNavParams,
		array(
			"ID",
			"PREVIEW_PICTURE",
			"PREVIEW_TEXT",
			"NAME",
			"PROPERTY_RATING",
		)
 );

 while ($resNews = $obNews->Fetch()) {
	 $arResult["ELEMENTS"][] = $resNews;
 }



// кастомная пагинация system.pagenavigation с шаблоном show_more_v2
//  $arResult['NAV_STRING'] = $obNews->GetPageNavString("", "show_more_v2");

  $arResult["NAV_STRING"] = $obNews->GetPageNavStringEx(
    $navComponentObject,
    "",
    "show_more_v2",
    false,
    $this
  );

  // регистрируем тегированный кэш для вывода свойств
  if(defined("BX_COMP_MANAGED_CACHE") && is_object($GLOBALS['CACHE_MANAGER'])){
    $GLOBALS['CACHE_MANAGER']->RegisterTag('votes_limit_users');
  }

  $this->SetResultCacheKeys(array(
	 	/*"ID",
	 	"PREVIEW_PICTURE",
	 	"PREVIEW_TEXT",
	 	"NAME",
	 	"PROPERTY_RATING",*/
	 ));

	$this->includeComponentTemplate();	
	
}


