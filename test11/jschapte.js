document.querySelectorAll('.like-btn').forEach(button => {
    button.addEventListener('click', function() {
        const mangaId = this.dataset.mangaId;

        fetch('like_follow.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                'action': 'like',
                'manga_id': mangaId
            })
        }).then(response => response.json())
          .then(data => {
              if (data.liked) {
                  this.textContent = 'Đã thích';
              } else {
                  this.textContent = 'Thích';
              }
          });
    });
});

document.querySelectorAll('.follow-btn').forEach(button => {
    button.addEventListener('click', function() {
        const mangaId = this.dataset.mangaId;

        fetch('like_follow.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                'action': 'follow',
                'manga_id': mangaId
            })
        }).then(response => response.json())
          .then(data => {
              if (data.followed) {
                  this.textContent = 'Đã theo dõi';
              } else {
                  this.textContent = 'Theo dõi';
              }
          });
    });
});
