(function ($, Drupal) {
  Drupal.behaviors.productInteractions = {
    attach: function (context, settings) {

      const basePath = drupalSettings.path.baseUrl;

      // Cookie helper functions
      function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
      }

      function getCookie(name) {
        const nameEQ = name + '=';
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
          let c = cookies[i].trim();
          if (c.indexOf(nameEQ) === 0) {
            return c.substring(nameEQ.length, c.length);
          }
        }
        return null;
      }

      function hasShared(nid) {
        return getCookie('shared_product_' + nid) === '1';
      }

      function markShared(nid) {
        setCookie('shared_product_' + nid, '1', 365); // 1 year
      }

      // LIKE
      $('.like-btn').on('click', function () {
        const $btn = $(this);
        const nid  = $btn.data('nid');

        $.ajax({
          url: basePath + 'api/product/like',
          method: 'POST',
          data: {
            nid: nid,
            token: drupalSettings.productInteractions.token
          },
          success: function (response) {
            if (response.error) {
              alert(response.error);
              return;
            }
            $('.like-count').text(response.likes);
            $btn.toggleClass('active');
          },
          error: function (xhr) {
            if (xhr.status === 403) {
              alert('Please login to like this product.');
            }
          }
        });
      });

      // SHARE - only count once per user via cookie
      $('.share-btn').on('click', function () {
        const nid     = $(this).data('nid');
        const $count  = $('.share-count');
        const alreadyShared = hasShared(nid);

        function recordShare() {
          if (!alreadyShared) {
            $.ajax({
              url: basePath + 'api/product/share',
              method: 'POST',
              data: {
                nid: nid,
                token: drupalSettings.productInteractions.token
              },
              success: function (response) {
                $count.text(response.shares);
                markShared(nid);   // set cookie so won't count again
                console.log('Share counted for nid:', nid);
              },
              error: function (xhr) {
                console.log('Share error:', xhr.status);
              }
            });
          } else {
            console.log('Already shared - not counting again');
          }
        }

        // Native share (mobile)
        if (navigator.share) {
          navigator.share({
            title: document.title,
            url: window.location.href,
          })
          .then(() => {
            recordShare();
          })
          .catch((err) => {
            console.log('Share cancelled or failed:', err);
          });

        } else {
          // Fallback - copy to clipboard
          navigator.clipboard.writeText(window.location.href)
          .then(() => {
            alert('Link copied to clipboard!');
            recordShare();
          })
          .catch(() => {
            // Final fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = window.location.href;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Link copied to clipboard!');
            recordShare();
          });
        }
      });

      // On page load - grey out share button if already shared
      $('.share-btn').each(function () {
        const nid = $(this).data('nid');
        if (hasShared(nid)) {
          $(this).addClass('already-shared')
                 .attr('title', 'Already shared');
        }
      });

      // On page load - mark like button if already liked
      if (drupalSettings.productInteractions.alreadyLiked) {
        $('.like-btn').addClass('active');
      }

    }
  };
})(jQuery, Drupal);