// Client-side validation (Task 2). Errors are shown in red right below the field that has the problem.
document.addEventListener('DOMContentLoaded', function () {
  var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  var configs = {
    'register-form': {
      full_name: function (v) { return v.trim() ? '' : 'Full name is required.'; },
      email: function (v) { return !v.trim() ? 'Email is required.' : (emailPattern.test(v) ? '' : 'Enter a valid email address.'); },
      username: function (v) { return /^[A-Za-z0-9_]{4,20}$/.test(v.trim()) ? '' : 'Username must be 4-20 letters, digits or _.'; },
      password: function (v) { return v.length >= 6 ? '' : 'Password must be at least 6 characters.'; },
      confirm_password: function (v, f) {
        if (!v) return 'Confirm your password.';
        return v === f.elements['password'].value ? '' : 'Passwords do not match.';
      }
    },
    'accuse-form': {
      suspect_id: function (v) { return v ? '' : 'Choose the suspect you are accusing.'; },
      reasoning: function (v) {
        var n = v.trim().length;
        if (n < 20) return 'Explain your reasoning in at least 20 characters.';
        if (n > 1000) return 'Keep your reasoning under 1000 characters.';
        return '';
      },
      confirm: function (v) { return v ? '' : 'You must confirm that this accusation is final.'; }
    }
  };

  Object.keys(configs).forEach(function (formId) {
    var form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', function (e) {
      var ok = true;
      form.querySelectorAll('.error').forEach(function (x) { x.textContent = ''; });
      Object.keys(configs[formId]).forEach(function (name) {
        var field = form.elements[name];
        var value = (field.type === 'checkbox') ? (field.checked ? '1' : '') : field.value;
        var msg = configs[formId][name](value, form);
        if (msg) { document.getElementById('err-' + name).textContent = msg; ok = false; }
      });
      if (!ok) e.preventDefault();
    });
  });
});
