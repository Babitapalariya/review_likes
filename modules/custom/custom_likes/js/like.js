(function ($, Drupal) {
  Drupal.behaviors.customLikes = {
    attach: function (context, settings) {

     $('.like-btn', context).click(function () {
  const button = $(this);
  const nid = button.data('nid');
  const img = button.find('img');

  // 🔒 Check if the user is logged in
  if (!settings.customLikes.isUserLoggedIn) {
    alert('You must be logged in to like this post.');
    window.location.href = settings.customLikes.loginUrl;
    return;
  }

  // Prevent multiple clicks by disabling the button
  if (button.hasClass('processing')) {
    return; // Already processing
  }

  button.addClass('processing');

  $.ajax({
    url: '/custom-likes/toggle/' + nid,
    type: 'POST',
    dataType: 'json',
    success: function (response) {
      button.removeClass('processing');

      if (response.status === 'liked') {
        button.find('.like-count').text(response.count);
        button.addClass('liked');
        img.attr('src', '/themes/custom/review_likes/images/like.svg');

      } else if (response.status === 'unliked') {
        button.find('.like-count').text(response.count);
        button.removeClass('liked');
        img.attr('src', '/themes/custom/review_likes/images/unlike.svg');

      } else {
        alert(response.message);
      }
    }
  });
});


      // Attach event listener to the comment submit button
      $('.submit-comment', context).click(function () {

        if (!settings.customLikes.isUserLoggedIn) {
          alert('You must be logged in to comment.');
          window.location.href = settings.customLikes.loginUrl;
          return;
        }

        var nid = $(this).data('nid');
        var comment = $('#comment' + nid).val();

        if (comment.trim() !== '') {
          $.ajax({
            url: '/custom-comments/submit',
            type: 'POST',
            dataType: 'json',
            data: {
              comment: comment,
              nid: nid
            },
            success: function (response) {
              if (response.status === 'success') {
                $('#comment' + nid).val('');
                $('#comments-section' + nid).prepend(`
            <div class="cmnt">${response.comment}</div>
          `);
              } else {
                alert(response.message);
              }
            },
            error: function () {
              alert('An error occurred while submitting the comment.');
            }
          });
        } else {
          alert('Please enter a comment!');
        }
     
      // Get the node ID (nid) for this specific post
      var nid = $(this).data('nid');  // Fetch the `nid` from the button's data attribute

      // Get the comment text for this specific post's comment input
      var comment = $('#comment' + nid).val();  // Fetch the comment based on the dynamic ID

      // Only proceed if the comment is not empty
      if (comment.trim() !== '') {
        $.ajax({
          url: '/custom-comments/submit',  // The URL for the AJAX call
          type: 'POST',
          dataType: 'json',
          data: {
            comment: comment,
            nid: nid  // Send the node ID (nid) along with the comment text
          },
          success: function (response) {
            if (response.status === 'success') {
              // Clear the comment input field after successful submission
              $('#comment' + nid).val('');

              // Dynamically add the new comment to the comment section for the specific post
              $('#comments-section' + nid).prepend(`
                    <div class="cmnt">${response.comment}</div>
                `);
            } else {
              alert(response.message);  // If an error occurs, show the message
            }
          },
          error: function () {
            alert('An error occurred while submitting the comment.');
          }
        });
      } else {
        alert('Please enter a comment!');  // Prompt the user if the comment is empty
      }
    });

     // ✅ SHARE BUTTON HANDLER WITH LOGIN CHECK
      $('.share-button', context).click(function () {
        console.log('Share button clicked'); // DEBUG

        // 🔒 Check if the user is logged in
        if (!settings.customLikes.isUserLoggedIn) {
          alert('You must be logged in to share.');
          window.location.href = settings.customLikes.loginUrl;
          return;
        }

        const nid = $(this).data('nid');

        // 📤 Send POST to backend to log the share
        fetch(`/custom-likes/share/${nid}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
        })
        .then((res) => res.json())
        .then((data) => {
          if (data.status === 'shared') {
            $(`#share-count-${nid}`).text(data.count);
          } else {
            alert(data.message || 'Error sharing content.');
          }
        });

        // 📣 Show the share popup/modal
        $(`#share-popup-${nid}`).fadeIn();
      });

      // ❌ Close the share popup
      $('.close-share-popup', context).click(function () {
        $(this).closest('.share-popup').fadeOut();
      });

    }
  };
})(jQuery, Drupal);