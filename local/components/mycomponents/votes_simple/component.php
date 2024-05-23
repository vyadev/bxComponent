<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader,
  Bitrix\Iblock;

if (!isset($arParams['CACHE_TIME'])) {
  $arParams['CACHE_TIME'] = 3600;
}

// тип инфоблока
$arParams['IBLOCK_TYPE'] = trim($arParams['IBLOCK_TYPE']);

// идентификатор инфоблока
$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);

// количество элементов на страницу
$arParams['ELEMENTS_PER_PAGE'] = intval($arParams['ELEMENTS_PER_PAGE']);
if ($arParams['ELEMENTS_PER_PAGE'] <= 0) {
  $arParams['ELEMENTS_PER_PAGE'] = 10;
}

// запрещаем сохранение в сессии номера последней страницы
CPageOption::SetOptionString('main', 'nav_page_in_session', 'N');

// получаем параметры постраничной навигации, от которых будет зависеть кеш
$arNavParams = array(
  "nPageSize" => $arParams["ELEMENTS_PER_PAGE"],
);
$arNavigation = CDBResult::GetNavParams($arNavParams);

if ($this->StartResultCache(false, $arNavigation))
{

  if(!Loader::includeModule("iblock"))
  {
    $this->abortResultCache();
    ShowError(GetMessage("IBLOCK_MODULE_NONE"));
    return;
  }


    // создаём свойство для рейтинга пользователя
    $PROPERTY_CODE = "RATING";

    // проверяем наличие свойства рейтинга в инфоблоке
    $dbRes = CIBlockProperty::GetList(["ID" => "ASC"], [
      "IBLOCK_ID" => $arParams['IBLOCK_ID'],
      "CODE"      => $PROPERTY_CODE
    ]);

    // если свойства нет - создаём
    if (!$prop = $dbRes->GetNext()) {
      $arFields = Array(
        "NAME"          => "Текущий рейтинг",
        "CODE"          => $PROPERTY_CODE,
        "DEFAULT_VALUE" => 0,
        "PROPERTY_TYPE" => 'N',
        "IBLOCK_ID"     => $arParams['IBLOCK_ID'],
      );
      $ibp = new CIBlockProperty();
      $propID = $ibp->Add($arFields);

      // иначе - заполняем данными из формы
      // проверяем пришли ли данные из формы
    } else if (isset($_POST["id"]) && isset($_POST["rating"])) {
        $ELEMENT_ID = $_POST["id"];
        $PROPERTY_VALUE = $_POST["rating"];

        CIBlockElement::SetPropertyValues($ELEMENT_ID, $arParams['IBLOCK_ID'], $PROPERTY_VALUE, $PROPERTY_CODE);
        // CIBlockElement::SetPropertyValueCode($ELEMENT_ID, $PROPERTY_CODE, $PROPERTY_VALUE);
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
//   $arResult['NAV_STRING'] = $obNews->GetPageNavString("", "show_more_v2");

  $arResult["NAV_STRING"] = $obNews->GetPageNavStringEx(
    $navComponentObject,
    "",
    "show_more_v2",
    false,
    $this
  );

  // регистрируем тегированный кэш для вывода свойств
  if (defined("BX_COMP_MANAGED_CACHE") && is_object($GLOBALS['CACHE_MANAGER'])){
    $GLOBALS['CACHE_MANAGER']->RegisterTag('votes_simple');
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

// сбрасываем тегированный кэш
if (defined("BX_COMP_MANAGED_CACHE") && is_object($GLOBALS['CACHE_MANAGER'])) {
  $GLOBALS['CACHE_MANAGER']->ClearByTag('votes_simple');
}

