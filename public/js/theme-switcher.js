(function () {
  function getCookie(name) {
    const m = document.cookie.match(
      new RegExp(
        "(?:^|; )" + name.replace(/([$?*|{}\]\\^])/g, "\\$1") + "=([^;]*)"
      )
    );
    return m ? decodeURIComponent(m[1]) : "";
  }
  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var d = new Date();
      d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + d.toUTCString();
    }
    document.cookie =
      name + "=" + encodeURIComponent(value) + expires + "; path=/";
  }

  function applyMode(mode) {
    document.body.classList.remove(
      "ai-events-theme-dark",
      "ai-events-theme-light"
    );
    if (mode === "dark") document.body.classList.add("ai-events-theme-dark");
    if (mode === "light") document.body.classList.add("ai-events-theme-light");
    var label = document.querySelector("#theme-toggle .ae-toggle__label");
    var icon = document.querySelector("#theme-toggle .ae-toggle__icon");
    if (label) label.textContent = mode === "dark" ? "Dark" : "Light";
    if (icon) icon.textContent = mode === "dark" ? "🌙" : "☀️";
  }

  function preferred() {
    try {
      return matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
    } catch (_) {
      return "light";
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    var saved = getCookie("ai_events_theme_mode") || "auto";
    var mode = saved === "auto" ? preferred() : saved;
    applyMode(mode);

    var btn = document.getElementById("theme-toggle");
    if (!btn) return;

    btn.addEventListener("click", function () {
      var current = document.body.classList.contains("ai-events-theme-dark")
        ? "dark"
        : "light";
      var next = current === "dark" ? "light" : "dark";
      applyMode(next);
      setCookie("ai_events_theme_mode", next, 30);

      // Also notify WP (optional; ignores result if admin-ajax unavailable for visitors)
      try {
        if (
          window.ai_events_public &&
          ai_events_public.ajax_url &&
          ai_events_public.nonce
        ) {
          var fd = new FormData();
          fd.append("action", "toggle_theme_mode");
          fd.append("nonce", ai_events_public.nonce);
          fd.append("mode", next);
          fetch(ai_events_public.ajax_url, {
            method: "POST",
            credentials: "same-origin",
            body: fd,
          });
        }
      } catch (_) {}
    });
  });
})();
