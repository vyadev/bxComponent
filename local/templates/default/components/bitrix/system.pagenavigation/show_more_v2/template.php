<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
  die();
}
$this->createFrame()->begin("Загрузка навигации");
?>

<? if ($arResult["NavPageCount"] > 1): ?>
  <? if ($arResult["NavPageNomer"] + 1 <= $arResult["nEndPage"]): ?>
    <?
      $plus = $arResult["NavPageNomer"] + 1;
      $url = $arResult["sUrlPathParams"] . "PAGEN_" . $arResult["NavNum"] . "=" . $plus;
    ?>
    <div class="loadmore" data-url="<?= $url ?>">
      Показать еще
    </div>
  <? else: ?>
    <div class="loadmore loadmore_all-loaded">
      Загружено все
    </div>
  <? endif ?>
<? endif ?>


