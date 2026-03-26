// (function ($, Drupal) {
//   Drupal.behaviors.postInteractions = {
//     attach: function (context, settings) {
//       $('.post-interactions', context).once('postInteractions').each(function () {
//         const nid = $(this).data('nid');

//         $(this).find('.like').on('click', function () {
//           alert('Liked node ' + nid);
//           // Here you can implement an AJAX call to update the DB.
//         });

//         $(this).find('.share').on('click', function () {
//           alert('Share clicked on node ' + nid);
//         });

//         $(this).find('.comment').on('click', function () {
//           alert('Comment clicked on node ' + nid);
//         });
//       });
//     }
//   };
// })(jQuery, Drupal);
