document.addEventListener('DOMContentLoaded', function () {
  var cards = document.querySelectorAll('.card');
  cards.forEach(function (card) {
    card.addEventListener('mouseenter', function () {
      card.classList.add('shadow');
      card.style.cursor = 'pointer';
    });

    card.addEventListener('mouseleave', function () {
      card.classList.remove('shadow');
    });
  });

  var navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      if (window.scrollY >= 600) {
        navbar.style.backgroundColor = '#002244';
      } else {
        navbar.style.backgroundColor = '#003366';
      }
    });
  }

  var playlistItems = document.querySelectorAll('#playlist li');
  var videoArea = document.getElementById('videoarea');
  if (playlistItems.length > 0 && videoArea) {
    playlistItems.forEach(function (item) {
      item.addEventListener('click', function () {
        var movieUrl = item.getAttribute('movieurl');
        if (movieUrl) {
          videoArea.setAttribute('src', movieUrl);
        }
      });
    });

    var firstMovieUrl = playlistItems[0].getAttribute('movieurl');
    if (firstMovieUrl) {
      videoArea.setAttribute('src', firstMovieUrl);
    }
  }
});
