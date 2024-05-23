<?
use Bitrix\Sale;



// обработчик количества товаров в корзине
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler("sale", "\Bitrix\Sale\Internals\Basket::OnAfterAdd", ['CustomEventHandlers', 'increaseCartCounter']);

class CustomEventHandlers
{
  function increaseCartCounter(\Bitrix\Main\Entity\Event $event)
  {
    \Bitrix\Main\Loader::includeModule('iblock');

    $fields = $event->getParameter('fields');
    $catalogIblockId = 2; // IBLOCK_ID каталога

    $res = \Bitrix\Iblock\ElementTable::getList(array(
      'order' => array('SORT' => 'ASC'),
      'select' => array('ID'),
      'filter' => array('NAME' => $fields["NAME"], 'IBLOCK_ID' => $catalogIblockId),
    ));

    if ($arItem = $res->fetch()) {
      $currentCount = \CIBlockElement::GetProperty($catalogIblockId, $arItem["ID"], [], ["CODE" => "CART_COUNT"])->fetch();
      \CIBlockElement::SetPropertyValuesEx($arItem["ID"], $catalogIblockId, ['CART_COUNT' => $currentCount['VALUE'] + 1]);
    }

    
//    $currentCount = \CIBlockElement::GetProperty($catalogIblockId, $fields['PRODUCT_ID'], [], ["CODE" => "CART_COUNT"])->fetch();
//    \CIBlockElement::SetPropertyValuesEx($fields['PRODUCT_ID'], $catalogIblockId, ['CART_COUNT' => $currentCount['VALUE'] + 1]);
  }
}





// обработчик количества заказов товара
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
  'sale',
  'OnSaleOrderSaved',
  ['MyClass', 'onSaleOrderSaved']
);
class MyClass
{
  function onSaleOrderSaved(\Bitrix\Main\Event $event)
  {
    \Bitrix\Main\Loader::includeModule('iblock');
    $catalogIblockId = 2; // IBLOCK_ID каталога
    
    if(!$event->getParameter("IS_NEW"))
      return;
    $order = $event->getParameter("ENTITY");
    $orderId = $order->getId();
    $basket = Sale\Order::load($orderId)->getBasket();
    $basketItems = $basket->getBasketItems();

    foreach ($basket as $basketItem) {
      $prodIds[] = $basketItem->getField('PRODUCT_ID');
      $names[] = $basketItem->getField('NAME');
    }

//    $item = $basketItems[0];
//    $prodId = $item->getField('PRODUCT_ID');
//    $name = $item->getField('NAME');

    $res = \Bitrix\Iblock\ElementTable::getList(array(
      'order' => array('SORT' => 'ASC'),
      'select' => array('ID'),
      'filter' => array('NAME' => $names, 'IBLOCK_ID' => $catalogIblockId),
    ));

    $ids = array();
    while ($arItem = $res->fetch()) {
      $ids[] = $arItem["ID"];
    }


    foreach ($ids as $id) {
      $currentCount = \CIBlockElement::GetProperty($catalogIblockId, $id, [], ["CODE" => "ORDER_COUNT"])->fetch();
      \CIBlockElement::SetPropertyValuesEx($id, $catalogIblockId, ['ORDER_COUNT' => $currentCount['VALUE'] + 1]);
    }

//    $currentCount = \CIBlockElement::GetProperty($catalogIblockId, $prodId, [], ["CODE" => "ORDER_COUNT"])->fetch();
//    \CIBlockElement::SetPropertyValuesEx($prodId, $catalogIblockId, ['ORDER_COUNT' => $currentCount['VALUE'] + 1]);

//    $currentCount = \CIBlockElement::GetProperty($catalogIblockId, $prodId, [], ["CODE" => "ORDER_COUNT"])->fetch();
//    \CIBlockElement::SetPropertyValuesEx($prodId, $catalogIblockId, ['ORDER_COUNT' => $currentCount['VALUE'] + 1]);
  }
}




// обработчик для recaptcha v2
AddEventHandler('form', 'onBeforeResultAdd', 'BeforeResultAddHandler');
function BeforeResultAddHandler($WEB_FORM_ID, &$arFields, &$arrVALUES)
{
  global $APPLICATION;

  if ($_REQUEST['g-recaptcha-response']) {
    $httpClient = new \Bitrix\Main\Web\HttpClient;
    $result = $httpClient->post(
      'https://www.google.com/recaptcha/api/siteverify',
      array(
        'secret' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
        'response' => $_REQUEST['g-recaptcha-response'],
        'remoteip' => $_SERVER['HTTP_X_REAL_IP']
      )
    );
    $result = json_decode($result, true);
    if ($result['success'] !== true) {
      $APPLICATION->throwException("Вы не прошли проверку");
      return false;
    }
  } else {
    $APPLICATION->ThrowException('Вы не прошли проверку');
    return false;
  }
}


// удаление элемента с наибольшим рейтингом для votes_simple и votes_limit_users
AddEventHandler("iblock", "OnBeforeIBlockElementDelete", "OnBeforeIBlockElementDeleteHandler");
function OnBeforeIBlockElementDeleteHandler($ID)
{
  //берём айди инфоблока с удаляемым элементом
  $iblockId =  CIBlockElement::GetIBlockByID($ID);

  //выбираем элементы за которые проголосовали
  $res = CIBlockElement::GetList(
    array(),
    array("IBLOCK_ID" => $iblockId, "!PROPERTY_RATING" => false),
    false,
    false,
    array("ID", "IBLOCK_ID", "PROPERTY_RATING")
  );

  //выбираем значения свойств рейтинга
  $arProps = array();
  while ($arFields = $res->GetNext()) {
    $arProps[] = $arFields["PROPERTY_RATING_VALUE"];
  }

  //ищем максимальное значение
  $maxRating = max($arProps);

  //выбираем элементы с максимальным рейтингом
  $res = CIBlockElement::GetList(
    array(),
    array("IBLOCK_ID" => $iblockId, "PROPERTY_RATING" => $maxRating),
    false,
    false,
    array("ID", "IBLOCK_ID", "PROPERTY_RATING", "NAME")
  );

  $arRes = array();
  while ($arFields = $res->GetNext()) {
    if ($arFields["PROPERTY_RATING_VALUE"] > 0)
    {
      $arRes[$arFields["ID"]] = $arFields;
    }
  }

//проверяем есть ли среди них удаляемый элемент, если есть - посылаем уведомление админу
  if (array_key_exists($ID, $arRes))
  {
    $rsAdmins = CUser::GetList(
      $by = "ID",
      $order = "asc",
      array("GROUPS_ID" => 1)
    );

    while($admin = $rsAdmins->Fetch()) {
      CEvent::Send(
        "DELETE_MAX_RATING_ELEMENT",
        "s1",
        array(
          "EMAIL_TO" => $admin["EMAIL"],
          "ELEMENT_NAME" => $arRes[$ID]["NAME"],
        ),
        "N"
      );
    }
  }
}


// обработка голосований пользователей для  votes_limit_users
AddEventHandler("main", "OnBeforeProlog", "OnBeforePrologHandler");
function OnBeforePrologHandler()
{
	if ($_SERVER["REQUEST_METHOD"] == "POST" &&
			array_key_exists("rating", $_POST) && intval($_POST["rating"]) > 0 &&
	   	array_key_exists("id", $_POST) && intval($_POST["id"]) > 0 &&
      array_key_exists("iblock-id", $_POST) && intval($_POST["iblock-id"]) > 0) {

 //получаем ID текущего пользователя
    global $USER;
    $userid = $USER->GetID();

    //предполагаем, что в голосованиях участвуют только авторизованные пользователи
    if ($userid) {
			//изначально пользователь считается не голосовавшим
			$voted = false;
			$jsonObj = array();

			//проверяем, имеется ли факт голосования для данного элемента
			CModule::IncludeModule('iblock');

			$arFilter = array(
			 	"IBLOCK_ID" => $_POST["iblock-id"],
			  "=PROPERTY_USERS_ID" => $userid,
			  "=ID" => $_POST["id"],
			);
			$res = CIBlockElement::GetList(array(), $arFilter);

 			if ($ar_res = $res->GetNext()) {
 				$voted = true;
 			}

 			//если голосования не было, записываем соответствующие значения свойств
		  if (!$voted) {

		    $usersId = array();
		    $res = CIBlockElement::GetProperty($_POST["iblock-id"], $_POST["id"], array("sort"=>"asc"), array("CODE" => "USERS_ID"));
		    while ($ob = $res->GetNext()) {
		      $usersId[] = $ob['VALUE'];
		    }
				array_push($usersId, $userid);

				CIBlockElement::SetPropertyValuesEx($_POST["id"], $_POST["iblock-id"], array("USERS_ID" => $usersId, "RATING" => $_POST["rating"]));

        // сбрасываем тегированный кэш для вывода свойств
        //if(defined("BX_COMP_MANAGED_CACHE") && is_object($GLOBALS['CACHE_MANAGER'])) {
        // $GLOBALS['CACHE_MANAGER']->ClearByTag('votes_limit_users');
        //}

				$jsonObj["res"] = $_POST["rating"];
				$jsonObj["voted"] = false;

		   }
		   //если же пользователь уже проголосовал, сообщаем об этом. Теперь факт голосования обработан не будет
		   else {
        $jsonObj["res"] = "Вы уже проголосовали!";
        $jsonObj["voted"] = true;
		   }

        echo json_encode($jsonObj);
        die();
		 }
	}
}
