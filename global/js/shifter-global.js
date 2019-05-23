(function($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  function call_shifter_operation(action) {
    $.ajax({
      method: "POST",
      url: ajax_object.ajax_url,
      data: { action: action }
    }).done(response => {
      console.log(response);
      console.log(ajax_object.ajax_url);
    });
  }

  function generate_artifact() {
    swal({
      title: "Generate Artifact?",
      text:
        "While generating an Artifact you will not be able to access your WordPress app.",
      showCancelButton: true,
      confirmButtonColor: "#bc4e9c",
      cancelButtonColor: "#333",
      confirmButtonText: "Generate",
      padding: "3em"
    }).then(result => {
      if (result.value) {
        call_shifter_operation("shifter_app_generate");
        swal(
          "Generating artifact!",
          "Please check the Shifter dashboard",
          "success"
        ).then(() => window.close());
      }
    });
  }

  function terminate_app() {
    swal({
      title: "Are you sure?",
      text: "Confirm to power down your Shifter app.",
      padding: "3em",
      showCancelButton: true,
      confirmButtonColor: "transparent",
      cancelButtonColor: "#333",
      confirmButtonText: "Terminate"
    }).then(result => {
      if (result.value) {
        call_shifter_operation("shifter_app_terminate");
        swal(
          "App Terminated",
          "Check the Shifter Dashboard for status or to restart.",
          "success"
        ).then(() => window.close());
      }
    });
  }

  $(document).on("click", "#wp-admin-bar-shifter_support_generate", function() {
    generate_artifact();
  });

  $(document).on(
    "click",
    "#wp-admin-bar-shifter_support_terminate",
    function() {
      terminate_app();
    }
  );
})(jQuery);
