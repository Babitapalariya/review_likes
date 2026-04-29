(function ($, Drupal) {
  'use strict';

  // ══════════════════════════════════════════════════════════════════════════
  // ONE-TIME SETUP — runs after DOM ready via Drupal.behaviors
  // ALL click handlers use $(document).on() delegation so they work on
  // every element: initial render, AJAX-injected, and load-more cards.
  // ══════════════════════════════════════════════════════════════════════════

  var debateReady = false;

  Drupal.behaviors.debateInit = {
    attach: function (context, settings) {

      // Only bind document-level handlers once ever.
      if (debateReady) {
        console.log('[Debate] behaviors.attach called again — handlers already bound, skipping');
        return;
      }
      debateReady = true;
      console.log('[Debate] First attach — binding all delegated handlers now');

      // ── REPLY BUTTON ──────────────────────────────────────────────────────
      $(document).on('click.debate', '.debate-reply-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn       = $(this);
        var parentId   = $btn.attr('data-id');
        var name       = $btn.attr('data-name');
        var rebuttalId = $btn.attr('data-rebuttal');

        console.log('[Debate] REPLY CLICKED');
        console.log('  parentId  :', parentId);
        console.log('  name      :', name);
        console.log('  rebuttalId:', rebuttalId);

        if (!rebuttalId || rebuttalId === '0' || rebuttalId === 'undefined') {
          console.error('[Debate] REPLY FAILED — rebuttalId is empty/zero on button. Check renderCommentCard() PHP output.');
          alert('Debug: rebuttalId is missing on this reply button. Check PHP logs.');
          return;
        }

        var $formCard = $('#debate-form-card-' + rebuttalId);
        console.log('  #debate-form-card-' + rebuttalId + ' found:', $formCard.length > 0);

        if (!$formCard.length) {
          console.warn('[Debate] Form card not found — user may be anonymous');
          var $notice = $('#debate-section-' + rebuttalId + ' .debate-login-notice');
          if ($notice.length) {
            $('html,body').animate({ scrollTop: $notice.offset().top - 80 }, 400);
          }
          return;
        }

        // Write parent ID into the hidden field.
        var $parentField = $formCard.find('.debate-parent-id');
        console.log('  .debate-parent-id found inside form card:', $parentField.length, '| id attr:', $parentField.attr('id'));
        $parentField.val(parentId);
        console.log('  parent_id hidden field value after set:', $parentField.val());

        // Show replying-to banner.
        $formCard.find('.debate-reply-banner').remove();
        $formCard.prepend(
          '<div class="debate-reply-banner" style="display:flex;align-items:center;justify-content:space-between;background:#f5f5f3;border-radius:12px;padding:8px 14px;font-size:13px;margin-bottom:10px;">'
          + '<span>↩ Replying to <strong>' + $('<span>').text(name).html() + '</strong></span>'
          + '<a href="#" class="debate-cancel-reply-btn" data-rebuttal="' + rebuttalId + '" style="color:#e84040;font-weight:600;text-decoration:none;margin-left:12px;">✕ Cancel</a>'
          + '</div>'
        );

        // Open parent comment replies so user sees context.
        var $repliesBox = $('#replies-' + parentId);
        console.log('  #replies-' + parentId + ' found:', $repliesBox.length, '| visible:', $repliesBox.is(':visible'));
        if ($repliesBox.length && !$repliesBox.is(':visible')) {
          $repliesBox.slideDown(300);
          $('[data-toggle-replies="' + parentId + '"]')
            .text(function (i, t) { return t.replace('▶', '▼'); })
            .show();
        }

        // Focus textarea.
        var $ta = $formCard.find('.debate-comment-textarea');
        console.log('  textarea found:', $ta.length);
        $ta.attr('placeholder', 'Replying to ' + name + '...').focus();

        // Scroll to form.
        $('html,body').animate({ scrollTop: $formCard.offset().top - 80 }, 400);
        console.log('[Debate] REPLY SETUP COMPLETE');
      });

      // ── CANCEL REPLY ──────────────────────────────────────────────────────
      $(document).on('click.debate', '.debate-cancel-reply-btn', function (e) {
        e.preventDefault();
        var rebuttalId = $(this).attr('data-rebuttal');
        console.log('[Debate] Cancel reply — rebuttalId:', rebuttalId);
        var $formCard = $('#debate-form-card-' + rebuttalId);
        $formCard.find('.debate-parent-id').val('0');
        $formCard.find('.debate-comment-textarea').attr('placeholder', 'Write your statement here...');
        $(this).closest('.debate-reply-banner').remove();
      });

      // ── TOGGLE REPLIES ────────────────────────────────────────────────────
      $(document).on('click.debate', '.debate-toggle-replies', function (e) {
        e.preventDefault();
        var nodeId = $(this).attr('data-toggle-replies');
        var $box   = $('#replies-' + nodeId);
        console.log('[Debate] Toggle replies — nodeId:', nodeId, '| visible:', $box.is(':visible'));
        if ($box.is(':visible')) {
          $box.slideUp(250);
          $(this).text($(this).text().replace('▼', '▶'));
        } else {
          $box.slideDown(300);
          $(this).text($(this).text().replace('▶', '▼'));
        }
      });

      // ── AUDIENCE PILL ─────────────────────────────────────────────────────
      $(document).on('click.debate', '.debate-audience-pill', function (e) {
        e.preventDefault();
        var $pill = $(this);
        var val   = $pill.attr('data-value');
        var $card = $pill.closest('.debate-input-card');

        $pill.closest('.audience-pills')
          .find('.debate-audience-pill')
          .removeClass('debate-pill-active btn-pill-emerald')
          .addClass('btn-pill-rose');
        $pill.removeClass('btn-pill-rose').addClass('debate-pill-active btn-pill-emerald');

        $card.find('.debate-speaking-to').val(val);
        console.log('[Debate] Audience pill selected:', val);
      });

      // ── LOAD MORE COMMENTS ────────────────────────────────────────────────
      $(document).on('click.debate', '.debate-load-more-btn', function (e) {
        e.preventDefault();
        var $btn       = $(this);
        var rebuttalId = $btn.attr('data-rebuttal-id');
        var offset     = parseInt($btn.attr('data-offset'), 10) || 0;
        var url        = '/api/debate-comments/' + rebuttalId + '?offset=' + offset;

        console.log('[Debate] LOAD MORE CLICKED');
        console.log('  rebuttalId:', rebuttalId);
        console.log('  offset    :', offset);
        console.log('  url       :', url);

        $btn.text('Loading...').prop('disabled', true);

        $.ajax({
          url: url,
          type: 'GET',
          dataType: 'json',
          success: function (data) {
            console.log('[Debate] Load more SUCCESS');
            console.log('  total    :', data.total);
            console.log('  has_more :', data.has_more);
            console.log('  comments :', data.comments.length);
            console.log('  new offset:', data.offset);

            var $list = $('#debate-comments-list-' + rebuttalId);
            console.log('  #debate-comments-list-' + rebuttalId + ' found:', $list.length);

            $list.find('.debate-no-comments').remove();
            $.each(data.comments, function (i, html) {
              $list.append($(html));
            });

            $btn.data('offset', data.offset).attr('data-offset', data.offset);

            if (!data.has_more) {
              console.log('[Debate] No more comments — removing load more button');
              $btn.closest('.debate-load-more-wrap').remove();
            } else {
              $btn.text('Load More (' + data.total + ' total)').prop('disabled', false);
            }
          },
          error: function (xhr, status, error) {
            console.error('[Debate] Load more FAILED');
            console.error('  HTTP status:', xhr.status);
            console.error('  statusText :', xhr.statusText);
            console.error('  response   :', xhr.responseText);
            console.error('  url was    :', url);

            // Show the actual error to help debug.
            if (xhr.status === 404) {
              console.error('[Debate] 404 — Route /api/debate-comments/{id} not found. Check merchant_dashboard.routing.yml');
              alert('Debug: Route /api/debate-comments/' + rebuttalId + ' returned 404.\nCheck that merchant_dashboard.routing.yml has this route and run drush cr.');
            } else if (xhr.status === 403) {
              console.error('[Debate] 403 — Access denied. Check route _access setting in routing.yml');
            } else if (xhr.status === 500) {
              console.error('[Debate] 500 — PHP error in DebateController. Check /admin/reports/dblog');
            }

            $btn.text('Failed — try again').prop('disabled', false);
          }
        });
      });

      // ── RECOUNT REPLIES (triggered by PHP after reply saved) ──────────────
      $(document).on('debate:recount.debate', '.debate-toggle-replies', function () {
        var $btn   = $(this);
        var nodeId = $btn.attr('data-toggle-replies');
        var count  = $('#replies-' + nodeId).children('.debate-comment-card').length;
        var word   = count === 1 ? 'reply' : 'replies';
        var arrow  = $('#replies-' + nodeId).is(':visible') ? '▼' : '▶';
        $btn.text(arrow + ' View ' + count + ' ' + word).show();
        console.log('[Debate] Recount — nodeId:', nodeId, 'count:', count);
      });

      console.log('[Debate] All handlers bound successfully');

    } // end attach
  };

  // ══════════════════════════════════════════════════════════════════════════
  // Trust snapshot update
  // ══════════════════════════════════════════════════════════════════════════
  Drupal.behaviors.debateTrust = {
    attach: function (context, settings) {
      $(context).find('.debate-section').once('trust-evt').on('updateTrust', function (e, counts) {
        var $s = $(this);
        console.log('[Debate] updateTrust:', counts);
        $s.find('.trust-seller-pct').text(counts.seller_pct + '%');
        $s.find('.trust-buyer-pct').text(counts.buyer_pct   + '%');
        $s.find('.trust-neutral-pct').text(counts.neutral_pct + '%');
        $s.find('.trust-seller-bar').css('width', counts.seller_pct  + '%');
        $s.find('.trust-buyer-bar').css('width',  counts.buyer_pct   + '%');
        $s.find('.trust-neutral-bar').css('width',counts.neutral_pct + '%');
        $s.find('.trust-total').text(counts.total);
      });
    }
  };

  // ══════════════════════════════════════════════════════════════════════════
  // Init form card defaults
  // ══════════════════════════════════════════════════════════════════════════
  Drupal.behaviors.debateFormDefaults = {
    attach: function (context, settings) {
      $(context).find('.debate-input-card').once('debate-defaults').each(function () {
        var $card      = $(this);
        var rebuttalId = $card.attr('data-rebuttal');
        $card.find('.debate-speaking-to').val('Product Owner');
        $card.find('.debate-parent-id').val('0');
        console.log('[Debate] Form defaults set for rebuttal:', rebuttalId,
          '| speaking-to el found:', $card.find('.debate-speaking-to').length,
          '| parent-id el found:', $card.find('.debate-parent-id').length
        );
      });
    }
  };

  // ══════════════════════════════════════════════════════════════════════════
  // Re-attach after AJAX (body trigger from PHP)
  // ══════════════════════════════════════════════════════════════════════════
  $('body').on('debate:reattach', function () {
    console.log('[Debate] debate:reattach — calling Drupal.attachBehaviors');
    Drupal.attachBehaviors(document.body);
  });

  // ══════════════════════════════════════════════════════════════════════════
  // Auto-dismiss success messages
  // ══════════════════════════════════════════════════════════════════════════
  Drupal.behaviors.debateMessages = {
    attach: function (context, settings) {
      $(context).find('.debate-messages').once('debate-msg-obs').each(function () {
        var $wrap = $(this);
        var observer = new MutationObserver(function () {
          $wrap.find('.debate-success').each(function () {
            var $el = $(this);
            if (!$el.data('fading')) {
              $el.data('fading', true);
              setTimeout(function () { $el.fadeOut(500, function () { $el.remove(); }); }, 3000);
            }
          });
        });
        observer.observe($wrap[0], { childList: true, subtree: true });
      });
    }
  };

})(jQuery, Drupal);