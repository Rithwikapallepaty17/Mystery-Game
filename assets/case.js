// Case page: suspect lookup via AJAX (Task 12), paid clue reveal via AJAX, and a to-do style notebook (Task 4)
$(function () {
  var C = window.CASE;

  // ---------- Suspect dropdown -> statement and alibi without reloading ----------
  $('#suspect-select').on('change', function () {
    var id = $(this).val();
    if (!id) { $('#suspect-panel').hide(); return; }

    $.ajax({
      url: C.base + 'ajax/suspect.php',
      method: 'GET',
      data: { id: id },
      dataType: 'json',
      success: function (d) {
        if (d.error) { alert(d.error); return; }
        $('#s-name').text(d.name);
        $('#s-occ').text(d.occupation);
        $('#s-statement').text(d.statement);
        $('#s-alibi').text(d.alibi);
        $('#s-motive').text(d.motive);
        $('#suspect-panel').show();
      },
      error: function () { alert('Could not load the suspect file.'); }
    });
  });

  // ---------- Reveal a paid clue ----------
  $(document).on('click', '.reveal', function () {
    var $btn = $(this);
    var id = $btn.data('id');
    var cost = $btn.data('cost');
    if (!confirm('Revealing this clue costs ' + cost + ' points. Continue?')) return;

    $btn.prop('disabled', true);
    $.ajax({
      url: C.base + 'ajax/reveal_evidence.php',
      method: 'POST',
      data: { id: id },
      dataType: 'json',
      success: function (d) {
        if (d.error) { alert(d.error); $btn.prop('disabled', false); return; }
        var $row = $('#ev-' + d.id);
        $row.removeClass('locked');
        $row.find('.desc').text(d.description);
        $row.find('.found').text(d.found_at);
        $row.find('.action').text('Revealed');
        $('#clues-used').text(d.clues_used);
        $('#points-spent').text(d.points_spent);
        $('#potential').text(d.potential_score);
      },
      error: function (xhr) {
        var msg = 'Could not reveal this clue.';
        try { msg = JSON.parse(xhr.responseText).error || msg; } catch (e) {}
        alert(msg);
        $btn.prop('disabled', false);
      }
    });
  });

  // ---------- Detective notebook (checklist, saved in localStorage) ----------
  if (!$('#notes-list').length) return;
  var key = 'case_notes_' + C.uid + '_' + C.caseId;
  var notes = [];
  try { notes = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { notes = []; }

  function save() { try { localStorage.setItem(key, JSON.stringify(notes)); } catch (e) {} }

  function render() {
    var $ul = $('#notes-list').empty();
    if (!notes.length) {
      $ul.append('<li class="empty">No notes yet. Jot down your theories.</li>');
      return;
    }
    notes.forEach(function (n, i) {
      var $li = $('<li>').toggleClass('done', !!n.done);
      var $cb = $('<input type="checkbox">').prop('checked', !!n.done).on('change', function () {
        notes[i].done = this.checked; save(); render();
      });
      var $txt = $('<span class="note-text">').text(n.text);
      var $del = $('<button type="button" class="small danger">Delete</button>').on('click', function () {
        notes.splice(i, 1); save(); render();
      });
      $li.append($cb, $txt, $del).appendTo($ul);
    });
  }

  $('#note-form').on('submit', function (e) {
    e.preventDefault();
    var text = $.trim($('#note-input').val());
    if (!text) { $('#note-error').text('Write a note first.'); return; }
    $('#note-error').text('');
    notes.push({ text: text, done: false });
    save();
    $('#note-input').val('');
    render();
  });

  render();
});
