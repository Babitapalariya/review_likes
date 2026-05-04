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
      
      // CORRECT — uses Drupal base path automatically
var url = drupalSettings.path.baseUrl + 'api/debate-comments/' + rebuttalId + '?offset=' + offset;
  //var url        = '/api/debate-comments/' + rebuttalId + '?offset=' + offset;

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



  // ── Sidebar support buttons: scroll to form + pre-select radio ────────────
$(document).on('click.debate', '.sidebar-support-btn', function (e) {
  e.preventDefault();
  var support    = $(this).attr('data-support'); // seller | buyer | neutral
  var rebuttalId = $(this).attr('data-rebuttal');

  // Scroll to the debate form.
  var $formCard = $('#debate-form-card-' + rebuttalId);
  if ($formCard.length) {
    $('html,body').animate({ scrollTop: $formCard.offset().top - 80 }, 500);
    // Pre-select the matching radio.
    $formCard.find('.debate-support-radios input[value="' + support + '"]').prop('checked', true);
    // Focus textarea.
    setTimeout(function () {
      $formCard.find('.debate-comment-textarea').focus();
    }, 600);
  } else {
    // Anonymous: scroll to login notice.
    $('html,body').animate({
      scrollTop: $('#debate-section-' + rebuttalId + ' .debate-login-notice').offset().top - 80
    }, 500);
  }
});


// ── Claim modal auto-close after success ──────────────────────────────────
$('body').on('claim:autoclose', function (e, rebuttalId) {
  setTimeout(function () {
    var modalEl = document.getElementById('claimBuyerModal-' + rebuttalId);
    if (modalEl) {
      var modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) {
        modal.hide();
      }
    }
  }, 2500); // closes after 2.5 seconds
});

// ── @mention autocomplete in comment textarea ─────────────────────────────
// Shows dropdown of audience names when user types @
var mentionTimeout;

/*$(document).on('input.debate', '.debate-comment-textarea', function () {
  var $ta       = $(this);
  var $card     = $ta.closest('.debate-input-card');
  var val       = $ta.val();
  var cursorPos = this.selectionStart;
  var textBefore = val.substring(0, cursorPos);

  // Find the last @ before the cursor.
  var atMatch = textBefore.match(/@([\w\.]*)$/);

  // Remove existing dropdown.
  $card.find('.mention-dropdown').remove();

  if (!atMatch) { return; }

  var query = atMatch[1].toLowerCase();

  // Collect mentionable names from audience pills.
  var names = [];
  $card.find('.debate-audience-pill').each(function () {
    names.push($(this).attr('data-value'));
  });

  // Filter by what user typed after @.
  var filtered = names.filter(function (n) {
    return n.toLowerCase().indexOf(query) === 0;
  });

  if (!filtered.length) { return; }

  // Build dropdown.
  var $drop = $('<div class="mention-dropdown" style="'
    + 'position:absolute;z-index:9999;background:#fff;border:1px solid #d1d1cb;'
    + 'border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.12);'
    + 'min-width:160px;overflow:hidden;margin-top:2px;"></div>');

  filtered.forEach(function (name) {
    $drop.append(
      '<div class="mention-item" data-name="' + name + '" style="'
      + 'padding:8px 14px;font-size:13px;cursor:pointer;'
      + 'display:flex;align-items:center;gap:8px;">'
      + '<span style="font-weight:600;">@' + name + '</span>'
      + '</div>'
    );
  });

  // Position below textarea.
  $ta.css('position', 'relative');
  $ta.after($drop);
});*/








// Click on mention suggestion — insert into textarea.
/*$(document).on('click.debate', '.mention-item', function () {
  var name   = $(this).attr('data-name');
  var $drop  = $(this).closest('.mention-dropdown');
  var $ta    = $drop.prev('.debate-comment-textarea');
  var val    = $ta.val();
  var cursor = $ta[0].selectionStart;

  // Replace the partial @query with the full @name.
  var before  = val.substring(0, cursor);
  var after   = val.substring(cursor);
  var newBefore = before.replace(/@([\w\.]*)$/, '@' + name + ' ');

  $ta.val(newBefore + after);
  $ta.focus();

  // Move cursor after inserted mention.
  var newPos = newBefore.length;
  $ta[0].setSelectionRange(newPos, newPos);

  $drop.remove();
});*/

// Close dropdown when clicking outside.
$(document).on('click.debate', function (e) {
  if (!$(e.target).closest('.mention-dropdown, .debate-comment-textarea').length) {
    $('.mention-dropdown').remove();
  }
});







// ── Login modal: set destination before opening ───────────────────────────
$(document).on('show.bs.modal', '#debateLoginModal', function () {
  // Update the login form action with current page as destination.
  var currentPath = window.location.pathname + window.location.search;
  var $form = $(this).find('form#user-login-form');
  if ($form.length) {
    var action = $form.attr('action');
    // Replace or add destination param.
    var newAction = drupalSettings.path.baseUrl + 'user/login?destination=' + encodeURIComponent(currentPath);
    $form.attr('action', newAction);
    console.log('[Debate] Login form action updated to:', newAction);
  }
});



// ── @mention autocomplete — searches ALL registered users ────────────────
$(document).on('input.debate', '.debate-comment-textarea', function () {
  var $ta       = $(this);
  var $card     = $ta.closest('.debate-input-card');
  var val       = $ta.val();
  var cursorPos = this.selectionStart;
  var textBefore = val.substring(0, cursorPos);

  // Find @ before cursor.
  var atMatch = textBefore.match(/@([\w\.]*)$/);

  // Remove existing dropdown.
  $card.find('.mention-dropdown').remove();

  if (!atMatch) { return; }

  var query = atMatch[1];

  // Need at least 1 character after @ to search.
  if (query.length < 1) { return; }

  var basePath = drupalSettings.path.baseUrl;

  // Search users via API.
  $.getJSON(basePath + 'api/users/search?q=' + encodeURIComponent(query), function (users) {
    $card.find('.mention-dropdown').remove();
    if (!users.length) { return; }

    var $drop = $('<div class="mention-dropdown" style="'
      + 'position:absolute;z-index:9999;background:#fff;'
      + 'border:1px solid #d1d1cb;border-radius:12px;'
      + 'box-shadow:0 4px 16px rgba(0,0,0,.12);'
      + 'min-width:200px;max-height:220px;overflow-y:auto;'
      + 'margin-top:4px;"></div>');

    users.forEach(function (u) {
      $drop.append(
        '<div class="mention-item" data-name="' + u.name + '" style="'
        + 'padding:8px 14px;font-size:13px;cursor:pointer;'
        + 'display:flex;align-items:center;gap:10px;'
        + 'border-bottom:1px solid #f5f5f3;">'
        + '<span style="width:28px;height:28px;border-radius:50%;'
        + 'background:#1a1a17;color:#fff;display:inline-flex;'
        + 'align-items:center;justify-content:center;'
        + 'font-size:12px;font-weight:700;flex-shrink:0;">'
        + u.initials + '</span>'
        + '<span><strong>@' + u.name + '</strong></span>'
        + '</div>'
      );
    });

    // Position dropdown below textarea cursor area.
    $ta.css('position', 'relative');
    $ta.after($drop);
  });
});

// ── Click mention suggestion — insert into textarea ───────────────────────
$(document).on('click.debate', '.mention-item', function (e) {
  e.stopPropagation();
  var name  = $(this).attr('data-name');
  var $drop = $(this).closest('.mention-dropdown');
  var $ta   = $drop.prev('.debate-comment-textarea');
  var val   = $ta.val();
  var cursor = $ta[0].selectionStart;

  // Replace partial @query with full @name.
  var before    = val.substring(0, cursor);
  var after     = val.substring(cursor);
  var newBefore = before.replace(/@([\w\.]*)$/, '@' + name + ' ');

  $ta.val(newBefore + after);
  $ta.focus();

  var newPos = newBefore.length;
  $ta[0].setSelectionRange(newPos, newPos);

  $drop.remove();
});

// ── Close dropdown on outside click ──────────────────────────────────────
$(document).on('click.debate', function (e) {
  if (!$(e.target).closest('.mention-dropdown, .debate-comment-textarea').length) {
    $('.mention-dropdown').remove();
  }
});

// ── Close dropdown on Escape key ─────────────────────────────────────────
$(document).on('keydown.debate', '.debate-comment-textarea', function (e) {
  if (e.key === 'Escape') {
    $(this).closest('.debate-input-card').find('.mention-dropdown').remove();
  }
  // Arrow keys to navigate dropdown.
  if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
    var $items = $('.mention-dropdown .mention-item');
    if (!$items.length) { return; }
    var $active = $('.mention-dropdown .mention-item.active');
    var idx     = $items.index($active);
    $items.removeClass('active').css('background', '');
    if (e.key === 'ArrowDown') {
      idx = (idx + 1) % $items.length;
    } else {
      idx = (idx - 1 + $items.length) % $items.length;
    }
    $items.eq(idx).addClass('active').css('background', '#f5f5f3');
    e.preventDefault();
  }
  // Enter to select highlighted item.
  if (e.key === 'Enter') {
    var $active = $('.mention-dropdown .mention-item.active');
    if ($active.length) {
      e.preventDefault();
      $active.trigger('click');
    }
  }
});


})(jQuery, Drupal);