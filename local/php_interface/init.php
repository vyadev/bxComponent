<?
AddEventHandler("iblock", "OnBeforeIBlockElementDelete", "OnBeforeIBlockElementDeleteHandler");
AddEventHandler("main", "OnBeforeProlog", "OnBeforePrologHandler");
 
// удаление элемента
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



// обработчик для votes_limit_users, ограничение голосований одного пользователя, раскомментировать при подключении votes_limit_users
/*function OnBeforePrologHandler()
{
	if ($_SERVER["REQUEST_METHOD"] == "POST" &&
			array_key_exists("rating", $_POST) && intval($_POST["rating"]) > 0 &&
	   	array_key_exists("id", $_POST) && intval($_POST["id"]) > 0 &&
      array_key_exists("iblock-id", $_POST) && intval($_POST["iblock-id"]) > 0) {

 //получаем ID текущего пользователя
    global $USER;
    $userid = $USER->GetID();

    //предполагаем, что  в голосованиях участвуют только авторизованные пользователи
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
        if(defined("BX_COMP_MANAGED_CACHE") && is_object($GLOBALS['CACHE_MANAGER'])){
          $GLOBALS['CACHE_MANAGER']->ClearByTag('votes_limit_users');
        }

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
}*/