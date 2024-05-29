$(function() {
  $(document).on('click', '.loadmore', function() {
    var targetContainer = $('.loadmore-wrap'), //  Контейнер, в котором хранятся элементы
      url = $('.loadmore').attr('data-url'); //  URL, из которого будем брать элементы
      console.log(url)
    if (url !== undefined) {
      BX.ajax({
        type: 'GET',
        url: url,
        onsuccess: function(data) {
          console.log(data)
          //  Удаляем старую навигацию
          $('.loadmore').remove();
          var elements = $(data).find('.loadmore-item'), //  Ищем элементы
              pagination = $(data).find('.loadmore'); //  Ищем навигацию
          // console.log(elements)
          targetContainer.append(elements); //  Добавляем посты в конец контейнера
          targetContainer.append(pagination); //  добавляем навигацию следом
        }
      })
    }
  });
});