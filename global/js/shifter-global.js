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
    try {
      // console.log("[Shifter] upload_single: preparing request", {
      //   ajaxUrl: (window.ajax_object && ajax_object.ajax_url) || "(missing)",
      //   hasNonce: !!(window.ajax_object && ajax_object.nonce),
      //   path: path
      // });
    } catch (e) {}
    swal({
      title: "Upload Single Page?",
      text: `Only this page will be uploaded.\n${currentUrl}`,
      showCancelButton: true,
      confirmButtonColor: "#bc4e9c",
      cancelButtonColor: "#333",
      confirmButtonText: "Upload",
      padding: "3em",
      showLoaderOnConfirm: true,
      allowOutsideClick: false,
      allowEscapeKey: false,
      preConfirm: () => {
        try {
          const cancelBtn = (window.Swal && Swal.getCancelButton && Swal.getCancelButton()) || document.querySelector(".swal2-cancel");
          if (cancelBtn) {
            cancelBtn.disabled = true;
            cancelBtn.style.display = "none";
          }
          // Prefer API, fallback to DOM manipulation for broader compatibility.
          if (window.Swal && Swal.update) {
            Swal.update({ title: "Uploading…" });
          } else {
            const titleEl = (window.Swal && Swal.getTitle && Swal.getTitle()) || document.querySelector(".swal2-title");
            if (titleEl) titleEl.textContent = "Uploading…";
          }
          if (window.Swal && Swal.showLoading) {
            Swal.showLoading();
          }
        } catch (e) {}
        return new Promise((resolve, reject) => {
          call_shifter_operation("shifter_app_upload_single", { path: path })
            .done(resp => {
              // Response is now: { success: boolean, statusCode: number, httpStatusCode: number }
              try {
                // console.log("[Shifter] upload_single: ajax done", resp);
                const payload = resp;
                const apiRaw = payload && payload.statusCode;
                const httpRaw = payload && payload.httpStatusCode;
                const apiStatus = apiRaw !== undefined && apiRaw !== null ? parseInt(apiRaw, 10) : NaN;
                const httpStatus = httpRaw !== undefined && httpRaw !== null ? parseInt(httpRaw, 10) : NaN;
                const apiOk = !isNaN(apiStatus) ? apiStatus >= 200 && apiStatus < 300 : true;
                const httpOk = !isNaN(httpStatus) ? httpStatus >= 200 && httpStatus < 300 : true;
                const isOk = !!(payload && payload.success === true) && apiOk && httpOk;
                if (!isOk) {
                  const parts = [];
                  if (!isNaN(httpStatus)) parts.push(`HTTP ${httpStatus}`);
                  if (!isNaN(apiStatus)) parts.push(`API ${apiStatus}`);
                  const msg = parts.length ? `Server returned ${parts.join(" / ")}` : "Request failed";
                  resolve({ ok: false, payload: payload, msg: msg });
                  return;
                }
                resolve({ ok: true, payload: payload });
              } catch (e) {
                // console.warn("[Shifter] upload_single: parse error", e);
                resolve({ ok: false, payload: null, msg: "Unexpected response" });
              }
            })
            .fail(xhr => {
              const httpStatus = xhr.status || 500;
              const res = xhr && xhr.responseJSON ? xhr.responseJSON : {};
              const apiStatus = res && typeof res === "object" && typeof res.statusCode !== "undefined" ? res.statusCode : undefined;
              const parts = [];
              if (httpStatus) parts.push(`HTTP ${httpStatus}`);
              if (typeof apiStatus !== "undefined") parts.push(`API ${apiStatus}`);
              const msg = parts.length ? `Server returned ${parts.join(" / ")}` : (xhr.statusText || "Request failed");
              // console.warn("[Shifter] upload_single: ajax fail", { httpStatus, res, msg });
              resolve({ ok: false, resp: null, msg: msg });
            });
        });
      }
    }).then(result => {
      if (!result.value) return;
      const out = result.value;
      if (!out.ok) {
        try {
          // console.warn("[Shifter] upload_single: showing error modal", out);
        } catch (e) {}
        swal("Upload failed", out.msg || "Request failed", "error");
        return;
      }
      // console.log("[Shifter] upload_single: success modal");
      const message = "Upload succeeded.";
      swal("Upload completed", message, "success");
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
