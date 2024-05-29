document.addEventListener("DOMContentLoaded", function() {

  document.addEventListener('click', function(event) {

    if (event.target.classList.contains('loadmore')) {

      let targetContainer = document.querySelector('.loadmore-wrap');
      let url = event.target.dataset.url;

      if (url !== undefined) {
        fetch(url)
          .then(response => response.text())
          .then(responseText => {
              var doc = new DOMParser().parseFromString(responseText, "text/html");

              document.querySelector('.loadmore').remove();

              let elements = doc.querySelectorAll('.loadmore-item');
              let pagination = doc.querySelector('.loadmore');

              elements.forEach((item, index) => {
                  targetContainer.insertAdjacentHTML('beforeEnd', item.outerHTML);
              })
              targetContainer.insertAdjacentHTML('beforeEnd', pagination.outerHTML);

              // дополнительно - класс для всех подгруженных элементов, кроме последнего
              let targetCnt = targetContainer.querySelectorAll('.loadmore-item').length
              let targetElems = document.querySelectorAll('.news-item')
              targetElems.forEach((item, idx) => {
                if ((idx !== targetCnt - 1) && !(item.classList.contains('addItem'))) {
                  item.classList.add('addItem')
                }
              })
          })
        // .then(responseText => console.log(responseText))
      }

    } else {
      return false;
    }
  });

});



