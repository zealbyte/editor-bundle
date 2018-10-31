// $();

function post(path, params, method) {
  method = method || "post"; // Set method to post by default if not specified.

  // The rest of this code assumes you are not using a library.
  // It can be made less wordy if you use one.
  var form = document.createElement("form");
  form.setAttribute("method", method);
  form.setAttribute("action", path);

  for(var key in params) {
    if(params.hasOwnProperty(key)) {
      var hiddenField = document.createElement("input");
      hiddenField.setAttribute("type", "hidden");
      hiddenField.setAttribute("name", key);
      hiddenField.setAttribute("value", params[key]);

      form.appendChild(hiddenField);
    }
  }

  document.body.appendChild(form);
  form.submit();
}

if ( "undefined" != typeof(PhpDebugBar) ) {
  var TiStateIndicator = PhpDebugBar.DebugBar.Indicator.extend({
    tagName: 'a',
    render: function () {
      TiStateIndicator.__super__.render.apply(this);
      this.bindAttr('state', function ( state ) {
        this.$el.attr('data-state', state);
      });
      this.$el.click(function () {
        var form = document.createElement("form");
        form.setAttribute("method", "post");

        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", "rst["+jQuery(this).attr('data-state')+"]");
        hiddenField.setAttribute("value", "phpdebugbar");

        form.appendChild(hiddenField);
        document.body.appendChild(form);
        form.submit();
      });
    }
  });

  jQuery(document).ready(function () {
    if ( "undefined" != typeof(phpdebugbar) ) {
      phpdebugbar.addIndicator(
        'phpdoc',
        new TiStateIndicator({
          state: '1',
          title: 'Clear State',
          tooltip: 'Clear Page State Cache',
          icon: 'eraser'
        })
      );
    }
  });
}
