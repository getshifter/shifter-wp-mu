(function($) {
  "use strict";

  function call_shifter_operation(action, extraData) {
    return $.ajax({
      method: "POST",
      url: ajax_object.ajax_url,
      dataType: "json",
      data: Object.assign({ action: action, security: ajax_object.nonce }, extraData || {})
    }).done(response => {
      console.log(response);
      console.log(ajax_object.ajax_url);
    });
  }

  function normalize_active_users_payload(response) {
    const payload = response && response.data ? response.data : {};
    const users = Array.isArray(payload.users) ? payload.users : [];
    const others = users.filter(user => !user.is_me);
    return {
      count: Number(payload.count || users.length || 0),
      others_count: Number(payload.others_count || others.length || 0),
      users: users,
      others: others
    };
  }

  function active_users_preview(users) {
    if (!users || users.length === 0) {
      return "No active users";
    }

    const names = users.slice(0, 5).map(user => user.display_name);
    const extra = users.length - names.length;
    if (extra > 0) {
      names.push(`+${extra} more`);
    }
    return names.join(", ");
  }

  function update_active_users_admin_bar(payload) {
    const summaryEl = $("#wp-admin-bar-shifter_active_users > .ab-item");
    const listEl = $("#wp-admin-bar-shifter_active_users_list > .ab-item");
    if (!summaryEl.length || !listEl.length) {
      return;
    }

    summaryEl.text(`Working now: ${payload.count}`);
    listEl.text(active_users_preview(payload.users));
  }

  function fetch_active_users() {
    return call_shifter_operation("shifter_get_active_users").then(
      response => normalize_active_users_payload(response),
      () => ({
        count: 0,
        others_count: 0,
        users: [],
        others: []
      })
    );
  }

  function escape_html(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function build_operation_modal_content(baseText, payload) {
    if (!payload || payload.others_count <= 0) {
      return { text: baseText };
    }

    const otherNames = active_users_preview(payload.others || payload.users || []);
    const warning = `${payload.others_count} member(s) are currently working: ${otherNames}.`;

    return {
      html: `<div style="text-align:left;">
        <p style="margin:0 0 10px 0; color:#d33; font-weight:600;">${escape_html(warning)}</p>
        <p style="margin:0;">${escape_html(baseText)}</p>
      </div>`
    };
  }

  function generate_artifact() {
    const baseText = "While generating an Artifact you will not be able to access your WordPress app.";
    fetch_active_users().then(payload => {
      const modalContent = build_operation_modal_content(baseText, payload);
      swal({
        title: "Generate Artifact?",
        text: modalContent.text || undefined,
        html: modalContent.html || undefined,
        showCancelButton: true,
        confirmButtonColor: "#bc4e9c",
        cancelButtonColor: "#333",
        confirmButtonText: "Generate",
        cancelButtonText: "Cancel",
        padding: "3em"
      }).then(result => {
        if (result.value) {
          call_shifter_operation("shifter_app_generate");
          swal("Generating artifact!", "Please check the Shifter dashboard", "success").then(() => window.close());
        }
      });
    });
  }

  function terminate_app() {
    const baseText = "Confirm to power down your Shifter app.";
    fetch_active_users().then(payload => {
      const modalContent = build_operation_modal_content(baseText, payload);
      swal({
        title: "Are you sure?",
        text: modalContent.text || undefined,
        html: modalContent.html || undefined,
        padding: "3em",
        showCancelButton: true,
        confirmButtonColor: "#bc4e9c",
        cancelButtonColor: "#333",
        confirmButtonText: "Terminate",
        cancelButtonText: "Cancel"
      }).then(result => {
        if (result.value) {
          call_shifter_operation("shifter_app_terminate");
          swal("App Terminated", "Check the Shifter Dashboard for status or to restart.", "success").then(() =>
            window.close()
          );
        }
      });
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
      padding: "3em",
      showLoaderOnConfirm: true,
      allowOutsideClick: false,
      allowEscapeKey: false,
      preConfirm: () => {
        try {
          const cancelBtn =
            (window.Swal && Swal.getCancelButton && Swal.getCancelButton()) ||
            document.querySelector(".swal2-cancel");
          if (cancelBtn) {
            cancelBtn.disabled = true;
            cancelBtn.style.display = "none";
          }
          if (window.Swal && Swal.update) {
            Swal.update({ title: "Uploading..." });
          } else {
            const titleEl =
              (window.Swal && Swal.getTitle && Swal.getTitle()) || document.querySelector(".swal2-title");
            if (titleEl) {
              titleEl.textContent = "Uploading...";
            }
          }
          if (window.Swal && Swal.showLoading) {
            Swal.showLoading();
          }
        } catch (e) {}
        return new Promise(resolve => {
          call_shifter_operation("shifter_app_upload_single", { path: path })
            .done(resp => {
              try {
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
                  if (!isNaN(httpStatus)) {
                    parts.push(`HTTP ${httpStatus}`);
                  }
                  if (!isNaN(apiStatus)) {
                    parts.push(`API ${apiStatus}`);
                  }
                  const msg = parts.length ? `Server returned ${parts.join(" / ")}` : "Request failed";
                  resolve({ ok: false, payload: payload, msg: msg });
                  return;
                }
                resolve({ ok: true, payload: payload });
              } catch (e) {
                resolve({ ok: false, payload: null, msg: "Unexpected response" });
              }
            })
            .fail(xhr => {
              const httpStatus = xhr.status || 500;
              const res = xhr && xhr.responseJSON ? xhr.responseJSON : {};
              const apiStatus =
                res && typeof res === "object" && typeof res.statusCode !== "undefined" ? res.statusCode : undefined;
              const parts = [];
              if (httpStatus) {
                parts.push(`HTTP ${httpStatus}`);
              }
              if (typeof apiStatus !== "undefined") {
                parts.push(`API ${apiStatus}`);
              }
              const msg = parts.length ? `Server returned ${parts.join(" / ")}` : xhr.statusText || "Request failed";
              resolve({ ok: false, resp: null, msg: msg });
            });
        });
      }
    }).then(result => {
      if (!result.value) {
        return;
      }
      const out = result.value;
      if (!out.ok) {
        swal("Upload failed", out.msg || "Request failed", "error");
        return;
      }
      swal("Upload completed", "Upload succeeded.", "success");
    });
  }

  function setup_presence_refresh() {
    fetch_active_users().then(update_active_users_admin_bar);

    $(document).on("heartbeat-tick.shifter", function(event, data) {
      if (!data || !data.shifter_active_users) {
        return;
      }
      update_active_users_admin_bar(normalize_active_users_payload({ data: data.shifter_active_users }));
    });

    // Fallback polling in case heartbeat is throttled/disabled on the current screen.
    window.setInterval(function() {
      if (document.visibilityState && document.visibilityState !== "visible") {
        return;
      }
      fetch_active_users().then(update_active_users_admin_bar);
    }, 30000);
  }

  setup_presence_refresh();

  $(document).on("click", "#wp-admin-bar-shifter_support_generate", function(event) {
    event.preventDefault();
    generate_artifact();
  });

  $(document).on("click", "#wp-admin-bar-shifter_support_terminate", function(event) {
    event.preventDefault();
    terminate_app();
  });

  $(document).on("click", "#wp-admin-bar-shifter_support_upload_single", function(event) {
    event.preventDefault();
    upload_single_page();
  });
})(jQuery);
