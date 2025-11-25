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

  function call_shifter_operation(action, extraData) {
    return $.ajax({
      method: "POST",
      url: ajax_object.ajax_url,
      data: Object.assign({ action: action, security: ajax_object.nonce }, extraData || {})
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

  function upload_single_page() {
    const currentUrl = window.location.href;
    const path = window.location.pathname;
    swal({
      title: "Upload Single Page?",
      text: `Only this page will be uploaded.\n${currentUrl}`,
      showCancelButton: true,
      confirmButtonColor: "#bc4e9c",
      cancelButtonColor: "#333",
      confirmButtonText: "Upload",
      padding: "3em"
    }).then(result => {
      if (result.value) {
        call_shifter_operation("shifter_app_upload_single", { path: path })
          .done(resp => {
            const data = resp && resp.data ? resp.data : resp;
            const statusCode = data && data.statusCode !== undefined ? data.statusCode : "";
            const bucket = data && data.bucket ? data.bucket : "";
            const key = data && data.key ? data.key : "";
            const invalidated = data && typeof data.invalidated !== "undefined" ? data.invalidated : "";
            const contentType = data && data.contentType ? data.contentType : "";
            const message = [
              statusCode ? `statusCode: ${statusCode}` : "",
              bucket ? `bucket: ${bucket}` : "",
              key ? `key: ${key}` : "",
              invalidated !== "" ? `invalidated: ${invalidated}` : "",
              contentType ? `contentType: ${contentType}` : ""
            ].filter(Boolean).join("\\n");
            swal("Upload completed", message || "Done.", "success");
          })
          .fail(xhr => {
            const res = xhr && xhr.responseJSON ? xhr.responseJSON : {};
            const data = res && res.data ? res.data : {};
            const msg = (data && (data.message || data.response)) || xhr.statusText || "Request failed";
            swal("Upload failed", typeof msg === "string" ? msg : JSON.stringify(msg), "error");
          });
      }
    });
  }

  $(document).on("click", "#wp-admin-bar-shifter_support_generate", function () {
    generate_artifact();
  });

  $(document).on(
    "click",
    "#wp-admin-bar-shifter_support_terminate",
    function() {
      terminate_app();
    }
  );

  $(document).on(
    "click",
    "#wp-admin-bar-shifter_support_upload_single",
    function() {
      upload_single_page();
    }
  );
})(jQuery);
