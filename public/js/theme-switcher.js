(function () {
  function getCookie(name) {
    const m = document.cookie.match(
      new RegExp(
        "(?:^|; )" + name.replace(/([$?*|{}\\^])/g, "\\$1") + "=([^;]*)"
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

  var sunSVG =
    '<svg class="ae-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1zm0 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm7-5a1 1 0 0 1 1 1h1a1 1 0 1 1 0 2h-1a1 1 0 1 1-2 0 1 1 0 0 1 1-1zm-14 0a1 1 0 0 1 1 1 1 1 0 1 1-2 0H3a1 1 0 1 1 0-2h1a1 1 0 0 1 1-1zm10.95 6.364a1 1 0 0 1 1.414 0l.707.707a1 1 0 0 1-1.414 1.414l-.707-.707a1 1 0 0 1 0-1.414zM5.343 5.343a1 1 0 0 1 1.414 0l.707.707A1 1 0 0 1 6.05 7.464l-.707-.707a1 1 0 0 1 0-1.414zm0 12.728a1 1 0 0 1 1.414 0l.707.707A1 1 0 1 1 6.05 20.19l-.707-.707a1 1 0 0 1 0-1.414zm12.728-12.728a1 1 0 0 1 0 1.414l-.707.707A1 1 0 1 1 16.95 6.05l.707-.707a1 1 0 0 1 1.414 0z"/></svg>';
  var moonSVG =
    '<svg class="ae-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';

  function applyMode(mode) {
    document.body.classList.remove(
      "ai-events-theme-dark",
      "ai-events-theme-light"
    );
    var btn = document.getElementById("theme-toggle");
    if (mode === "dark") {
      document.body.classList.add("ai-events-theme-dark");
      if (btn) {
        btn.classList.add("is-dark");
        var label = btn.querySelector(".ae-toggle__label");
        var icon = btn.querySelector(".ae-toggle__iconwrap");
        if (label) label.textContent = "NIGHT MODE";
        if (icon) icon.innerHTML = moonSVG;
      }
    } else {
      document.body.classList.add("ai-events-theme-light");
      if (btn) {
        btn.classList.remove("is-dark");
        var label2 = btn.querySelector(".ae-toggle__label");
        var icon2 = btn.querySelector(".ae-toggle__iconwrap");
        if (label2) label2.textContent = "DAY MODE";
        if (icon2) icon2.innerHTML = sunSVG;
      }
    }
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
    // Respect saved cookie; otherwise fall back to admin setting (default_mode), otherwise system preference
    var saved = getCookie("ai_events_theme_mode"); // may be undefined
    var defaultMode =
      window.ai_events_public && ai_events_public.default_mode
        ? ai_events_public.default_mode
        : "auto";
    var startMode = saved
      ? saved === "auto"
        ? preferred()
        : saved
      : defaultMode === "auto"
      ? preferred()
      : defaultMode;

    applyMode(startMode);

    var btn = document.getElementById("theme-toggle");
    if (!btn) return;

    btn.addEventListener("click", function () {
      var current = document.body.classList.contains("ai-events-theme-dark")
        ? "dark"
        : "light";
      var next = current === "dark" ? "light" : "dark";
      applyMode(next);
      setCookie("ai_events_theme_mode", next, 30);

      // Optional server notify (will no-op for visitors if action isn’t registered server-side)
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
