(function ($) {
  const $type = $("#sc-type");
  const $loc = $("#sc-location");
  const $rad = $("#sc-radius");
  const $lim = $("#sc-limit");
  const $cat = $("#sc-category");
  const $src = $("#sc-source");
  const $lay = $("#sc-layout");
  const $showSearch = $("#sc-show-search");
  const $showFilters = $("#sc-show-filters");
  const $orderby = $("#sc-orderby");
  const $order = $("#sc-order");
  const $out = $("#sc-output");

  function buildShortcode() {
    const name = $type.val();
    const attrs = [];

    function push(name, val) {
      if (val !== "" && val !== undefined && val !== null) {
        attrs.push(`${name}="${String(val).replace(/"/g, "&quot;")}"`);
      }
    }

    // shared
    push("location", $loc.val().trim());
    push("radius", $rad.val());
    push("limit", $lim.val());
    push("category", $cat.val().trim());

    if (name === "ai_events_list") {
      push("source", $src.val().trim() || "all");
      push("orderby", $orderby.val());
      push("order", $order.val());
      push("layout", $lay.val());
    }

    if (name === "ai_events_widget") {
      push("layout", "list");
      // show_image/show_date/show_location defaults handled by shortcode
    }

    if (name === "ai_events_page") {
      push("layout", $lay.val());
      push("show_filters", $showFilters.is(":checked") ? "true" : "false");
      push("show_search", $showSearch.is(":checked") ? "true" : "false");
    }

    const code = `[${name}${attrs.length ? " " + attrs.join(" ") : ""}]`;
    $out.text(code);
    return code;
  }

  function copy(text) {
    navigator.clipboard.writeText(text).then(() => {
      const $btn = $("#btn-copy");
      $btn.text(ai_events_admin.i18n.copied);
      setTimeout(() => $btn.text("📋 " + ai_events_admin.i18n.copy), 900);
    });
  }

  // live update
  $(".ai-e-shortcodes-wrap input, .ai-e-shortcodes-wrap select").on(
    "input change",
    buildShortcode
  );
  buildShortcode();

  $("#btn-copy").on("click", function (e) {
    e.preventDefault();
    copy($out.text());
  });

  // Preview modal
  function showModal(html) {
    $("#preview-inner").html(html);
    $("#preview-modal").css("display", "flex");
  }
  $("#preview-close").on("click", () => $("#preview-modal").hide());
  $(document).on("click", function (e) {
    if ($(e.target).is("#preview-modal")) $("#preview-modal").hide();
  });

  $("#btn-preview").on("click", function (e) {
    e.preventDefault();
    const shortcode = buildShortcode();
    $.post(ai_events_admin.ajax_url, {
      action: "preview_shortcode",
      nonce: ai_events_admin.nonce,
      shortcode,
    }).done(function (res) {
      if (res && res.success) showModal(res.data.html || "");
    });
  });

  // Save preset
  $("#btn-save-preset").on("click", function (e) {
    e.preventDefault();
    const name = $("#preset-name").val().trim();
    if (!name) {
      alert("Please enter a preset name");
      return;
    }
    const shortcode = buildShortcode();
    const $btn = $(this);
    const txt = $btn.text();
    $btn.text("💾 " + ai_events_admin.i18n.saving).prop("disabled", true);

    $.post(ai_events_admin.ajax_url, {
      action: "save_shortcode_preset",
      nonce: ai_events_admin.nonce,
      name,
      shortcode,
    })
      .done(function (res) {
        if (res && res.success && res.data && res.data.preset) {
          const p = res.data.preset;
          const html = `
          <div class="ai-e-preset" data-id="${p.id}">
            <h4>${$("<div>").text(p.name).html()}</h4>
            <div class="ai-e-code" style="margin-bottom:8px;">${$("<div>")
              .text(p.shortcode)
              .html()}</div>
            <div class="buttons">
              <button class="ai-e-btn btn-copy-preset">📋 ${
                ai_events_admin.i18n.copy
              }</button>
              <button class="ai-e-btn btn-preview-preset">👁️ ${
                ai_events_admin.i18n.preview
              }</button>
              <button class="ai-e-btn btn-delete-preset" style="border-color:rgba(255,107,107,.4)">🗑️ Delete</button>
            </div>
          </div>`;
          $("#presets-list").prepend(html);
          $("#preset-name").val("");
        }
      })
      .always(function () {
        $btn.text(txt).prop("disabled", false);
      });
  });

  // Preset actions
  $("#presets-list")
    .on("click", ".btn-copy-preset", function (e) {
      e.preventDefault();
      const code = $(this).closest(".ai-e-preset").find(".ai-e-code").text();
      navigator.clipboard.writeText(code);
    })
    .on("click", ".btn-preview-preset", function (e) {
      e.preventDefault();
      const code = $(this).closest(".ai-e-preset").find(".ai-e-code").text();
      $.post(ai_events_admin.ajax_url, {
        action: "preview_shortcode",
        nonce: ai_events_admin.nonce,
        shortcode: code,
      }).done(function (res) {
        if (res && res.success) showModal(res.data.html || "");
      });
    })
    .on("click", ".btn-delete-preset", function (e) {
      e.preventDefault();
      if (!confirm("Delete this preset?")) return;
      const $preset = $(this).closest(".ai-e-preset");
      const id = $preset.data("id");
      const $btn = $(this);
      const txt = $btn.text();
      $btn.text("🗑️ " + ai_events_admin.i18n.deleting).prop("disabled", true);

      $.post(ai_events_admin.ajax_url, {
        action: "delete_shortcode_preset",
        nonce: ai_events_admin.nonce,
        id,
      })
        .done(function (res) {
          if (res && res.success) $preset.remove();
        })
        .always(function () {
          $btn.text(txt).prop("disabled", false);
        });
    });

  // Toggle search/filter row based on type
  function toggleSearchFilterRow() {
    if ($type.val() === "ai_events_page") $("#row-search-filters").show();
    else $("#row-search-filters").hide();
  }
  $type.on("change", toggleSearchFilterRow);
  toggleSearchFilterRow();
})(jQuery);
